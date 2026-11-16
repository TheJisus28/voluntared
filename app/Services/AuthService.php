<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database as DB;
use App\Core\Mailer;
use App\Core\ReglaNegocioException;
use App\Models\Fundacion;
use App\Models\Usuario;

/**
 * RF1 y RF2: registro con verificación de correo, inicio de sesión con bloqueo
 * temporal tras varios intentos fallidos y restablecimiento con código temporal.
 */
final class AuthService
{
    public static function registrarVoluntario(array $datos): int
    {
        $id = Usuario::crear($datos, 'voluntario');
        self::enviarVerificacion($id);
        return $id;
    }

    /** Registra el usuario y la fundación en estado "pendiente" y avisa a los administradores. */
    public static function registrarFundacion(array $datos, string $rutaDocumento): int
    {
        return DB::transaccion(function () use ($datos, $rutaDocumento) {
            $idUsuario = Usuario::crear($datos, 'fundacion');
            Fundacion::crear($idUsuario, $datos + ['documentoSoporte' => $rutaDocumento]);
            self::enviarVerificacion($idUsuario);
            NotificacionService::notificarAdministradores(
                'Nueva fundación por verificar',
                "{$datos['razonSocial']} (NIT {$datos['nit']}) se registró y espera la revisión de su documento soporte.",
                '/admin/fundaciones'
            );
            return $idUsuario;
        });
    }

    // ------------------------------------------------------------ verificación de correo

    /** Firma un enlace de verificación (HMAC) que vence en las horas configuradas. */
    public static function enlaceVerificacion(array $usuario): string
    {
        $expira = time() + Config::get('seguridad.horas_verificacion') * 3600;
        $firma = self::firmar($usuario['idUsuario'], $usuario['email'], $expira);
        return url('/verificar', ['u' => $usuario['idUsuario'], 'e' => $expira, 'f' => $firma]);
    }

    public static function enviarVerificacion(int $idUsuario): void
    {
        $u = Usuario::buscarConRol($idUsuario);
        Mailer::enviar($u['email'], trim($u['nombre'] . ' ' . $u['apellido']), 'Confirma tu correo | VoluntaRed', 'verificacion', [
            'enlace' => self::urlAbsoluta(self::enlaceVerificacion($u)),
            'rol' => $u['rol'],
        ]);
    }

    /** Valida el enlace y activa la cuenta. Devuelve el usuario verificado. */
    public static function verificarCorreo(int $idUsuario, int $expira, string $firma): array
    {
        $u = Usuario::buscarConRol($idUsuario);
        if (!$u || !hash_equals(self::firmar($idUsuario, $u['email'], $expira), $firma)) {
            throw new ReglaNegocioException('El enlace de verificación no es válido.');
        }
        if ($expira < time()) {
            throw new ReglaNegocioException('El enlace de verificación expiró. Solicita uno nuevo.');
        }
        if (!$u['emailVerificado']) {
            Usuario::actualizar($idUsuario, ['emailVerificado' => 1]);
        }
        return $u;
    }

    private static function firmar(int|string $id, string $email, int $expira): string
    {
        return hash_hmac('sha256', "$id|$email|$expira", Config::get('app.clave'));
    }

    // ------------------------------------------------------------ inicio de sesión

    /**
     * Valida credenciales aplicando el bloqueo temporal.
     * Lanza ReglaNegocioException con el mensaje a mostrar si no puede ingresar.
     */
    public static function autenticar(string $email, string $contrasena): array
    {
        $u = Usuario::porEmail($email);
        $mensajeGenerico = 'Correo o contraseña incorrectos.';
        if (!$u) {
            password_verify($contrasena, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG'); // tiempo constante
            throw new ReglaNegocioException($mensajeGenerico);
        }
        if ($u['bloqueadoHasta'] && strtotime($u['bloqueadoHasta']) > time()) {
            $minutos = (int) ceil((strtotime($u['bloqueadoHasta']) - time()) / 60);
            throw new ReglaNegocioException("Tu cuenta está bloqueada temporalmente por varios intentos fallidos. Inténtalo de nuevo en $minutos minuto(s) o restablece tu contraseña.");
        }
        if (!password_verify($contrasena, $u['contrasena'])) {
            $bloqueada = Usuario::registrarIntentoFallido(
                (int) $u['idUsuario'],
                Config::get('seguridad.max_intentos'),
                Config::get('seguridad.minutos_bloqueo')
            );
            if ($bloqueada) {
                throw new ReglaNegocioException('Superaste el número de intentos permitidos. Tu cuenta quedó bloqueada por '
                    . Config::get('seguridad.minutos_bloqueo') . ' minutos.');
            }
            throw new ReglaNegocioException($mensajeGenerico);
        }
        if (!$u['activo']) {
            throw new ReglaNegocioException('Tu cuenta está suspendida. Comunícate con el administrador de VoluntaRed.');
        }
        if (!$u['emailVerificado']) {
            throw new ReglaNegocioException('Debes confirmar tu correo electrónico antes de iniciar sesión.');
        }
        if (password_needs_rehash($u['contrasena'], PASSWORD_BCRYPT)) {
            Usuario::actualizar((int) $u['idUsuario'], ['contrasena' => password_hash($contrasena, PASSWORD_BCRYPT)]);
        }
        Usuario::actualizar((int) $u['idUsuario'], ['intentosFallidos' => 0, 'bloqueadoHasta' => null]);
        return $u;
    }

    // ------------------------------------------------------------ restablecimiento

    /** Genera un código de 6 dígitos y lo envía por correo. No revela si el correo existe. */
    public static function solicitarCodigo(string $email): void
    {
        $u = Usuario::porEmail($email);
        if (!$u || !$u['activo']) {
            return;
        }
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutos = Config::get('seguridad.minutos_codigo');
        Usuario::actualizar((int) $u['idUsuario'], [
            'resetCode' => $codigo,
            'resetCodeExpiration' => date('Y-m-d H:i:s', time() + $minutos * 60),
        ]);
        Mailer::enviar($u['email'], trim($u['nombre'] . ' ' . $u['apellido']), 'Código para restablecer tu contraseña | VoluntaRed', 'codigo', [
            'codigo' => $codigo,
            'minutos' => $minutos,
        ]);
    }

    public static function restablecer(string $email, string $codigo, string $nueva): void
    {
        $u = Usuario::porEmail($email);
        if (!$u || !$u['resetCode'] || !hash_equals($u['resetCode'], $codigo)) {
            throw new ReglaNegocioException('El código no es válido.');
        }
        if (strtotime($u['resetCodeExpiration']) < time()) {
            throw new ReglaNegocioException('El código expiró. Solicita uno nuevo.');
        }
        Usuario::cambiarContrasena((int) $u['idUsuario'], $nueva);
        if (!$u['emailVerificado']) {
            // Recibir el código prueba que el usuario controla el correo.
            Usuario::actualizar((int) $u['idUsuario'], ['emailVerificado' => 1]);
        }
    }

    private static function urlAbsoluta(string $ruta): string
    {
        if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
            return rtrim(getenv('VR_URL') ?: 'http://localhost:8000', '/') . $ruta;
        }
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $esquema . '://' . $_SERVER['HTTP_HOST'] . $ruta;
    }

    public static function absoluta(string $ruta): string
    {
        return self::urlAbsoluta($ruta);
    }
}
