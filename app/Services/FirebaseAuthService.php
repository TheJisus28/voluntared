<?php

namespace App\Services;

use App\Core\Config;
use App\Core\ReglaNegocioException;
use App\Models\Usuario;
use Composer\CaBundle\CaBundle;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Inicio de sesión con Google a través de Firebase Authentication (RF2).
 *
 * El navegador abre la ventana de Google con el SDK de Firebase y envía a PHP el
 * "ID token" que Firebase emite. Aquí se valida la firma (RS256) con las llaves
 * públicas de Google y los datos del token (proyecto, emisor, vigencia, proveedor
 * y correo verificado). Después la sesión sigue siendo la misma de VoluntaRed (JWT
 * propio), así que los roles, la suspensión de cuentas y lo demás no cambian.
 */
final class FirebaseAuthService
{
    private const URL_LLAVES = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    private const CACHE = __DIR__ . '/../../storage/cache/firebase-llaves.json';

    public static function habilitado(): bool
    {
        return Config::get('firebase.project_id', '') !== '' && Config::get('firebase.api_key', '') !== '';
    }

    /** Configuración pública para el SDK web de Firebase. */
    public static function configWeb(): array
    {
        return [
            'apiKey' => Config::get('firebase.api_key'),
            'authDomain' => Config::get('firebase.auth_domain'),
            'projectId' => Config::get('firebase.project_id'),
            'appId' => Config::get('firebase.app_id'),
        ];
    }

    /**
     * Valida el ID token y devuelve los datos de la persona.
     * $certificados permite a las pruebas usar llaves propias: [kid => certificado PEM].
     *
     * @return array{uid: string, email: string, nombre: string, foto: ?string}
     */
    public static function verificarToken(string $idToken, ?array $certificados = null): array
    {
        $proyecto = (string) Config::get('firebase.project_id');
        $llaves = [];
        foreach ($certificados ?? self::certificadosGoogle() as $kid => $certificado) {
            $publica = openssl_pkey_get_public($certificado);
            if ($publica !== false) {
                $llaves[$kid] = new Key(openssl_pkey_get_details($publica)['key'], 'RS256');
            }
        }
        if (!$llaves) {
            throw new ReglaNegocioException('No fue posible obtener las llaves de Google. Intenta de nuevo.');
        }

        $leewayAnterior = JWT::$leeway;
        JWT::$leeway = 60; // tolerancia a pequeñas diferencias de reloj
        try {
            $datos = JWT::decode($idToken, $llaves);
        } catch (Throwable) {
            throw new ReglaNegocioException('No se pudo validar el inicio de sesión con Google. Intenta de nuevo.');
        } finally {
            JWT::$leeway = $leewayAnterior;
        }

        $valido = ($datos->aud ?? null) === $proyecto
            && ($datos->iss ?? null) === 'https://securetoken.google.com/' . $proyecto
            && !empty($datos->sub)
            && (int) ($datos->auth_time ?? PHP_INT_MAX) <= time() + 60;
        if (!$valido) {
            throw new ReglaNegocioException('El inicio de sesión con Google no corresponde a VoluntaRed.');
        }
        if (($datos->firebase->sign_in_provider ?? '') !== 'google.com') {
            throw new ReglaNegocioException('Solo se admite el inicio de sesión con Google por esta vía.');
        }
        if (empty($datos->email) || empty($datos->email_verified)) {
            throw new ReglaNegocioException('Tu cuenta de Google no tiene un correo verificado.');
        }

        $foto = isset($datos->picture) && strlen($datos->picture) <= 255 ? $datos->picture : null;
        return [
            'uid' => (string) $datos->sub,
            'email' => mb_strtolower(trim($datos->email)),
            'nombre' => trim((string) ($datos->name ?? '')),
            'foto' => $foto,
        ];
    }

    /**
     * Inicia sesión con la cuenta que tiene el mismo correo; si no existe, crea una cuenta
     * de voluntario (las fundaciones se registran con el formulario porque deben adjuntar
     * su documento soporte). Devuelve [usuario, esNueva].
     */
    public static function entrar(array $google): array
    {
        $u = Usuario::porEmail($google['email']);
        if ($u) {
            if (!$u['activo']) {
                throw new ReglaNegocioException('Tu cuenta está suspendida. Comunícate con el administrador de VoluntaRed.');
            }
            $cambios = ['intentosFallidos' => 0, 'bloqueadoHasta' => null];
            if (!$u['emailVerificado']) {
                $cambios['emailVerificado'] = 1; // Google ya verificó el correo
            }
            if (!$u['foto'] && $google['foto']) {
                $cambios['foto'] = $google['foto'];
            }
            Usuario::actualizar((int) $u['idUsuario'], $cambios);
            return [Usuario::buscarConRol((int) $u['idUsuario']), false];
        }

        [$nombre, $apellido] = self::separarNombre($google['nombre'], $google['email']);
        $id = Usuario::crear([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $google['email'],
            // Contraseña aleatoria: la cuenta entra con Google; si la persona quiere usar correo y
            // contraseña, la define con «¿Olvidó su contraseña?».
            'contrasena' => bin2hex(random_bytes(24)),
            'emailVerificado' => 1,
        ], 'voluntario');
        if ($google['foto']) {
            Usuario::actualizar($id, ['foto' => $google['foto']]);
        }
        return [Usuario::buscarConRol($id), true];
    }

    /** "Ana Sofía Gómez Ruiz" → ["Ana Sofía", "Gómez Ruiz"]; sin nombre se usa el correo. */
    public static function separarNombre(string $completo, string $email): array
    {
        $partes = preg_split('/\s+/u', trim($completo), -1, PREG_SPLIT_NO_EMPTY);
        if (!$partes) {
            return [mb_substr(ucfirst(strtok($email, '@')), 0, 100), null];
        }
        if (count($partes) === 1) {
            return [mb_substr($partes[0], 0, 100), null];
        }
        $corte = count($partes) >= 4 ? 2 : count($partes) - 1;
        return [
            mb_substr(implode(' ', array_slice($partes, 0, $corte)), 0, 100),
            mb_substr(implode(' ', array_slice($partes, $corte)), 0, 100),
        ];
    }

    /** Certificados públicos de Google, guardados en caché el tiempo que indica Cache-Control. */
    private static function certificadosGoogle(): array
    {
        if (is_file(self::CACHE)) {
            $cache = json_decode((string) file_get_contents(self::CACHE), true);
            if (($cache['expira'] ?? 0) > time() && !empty($cache['llaves'])) {
                return $cache['llaves'];
            }
        }
        $contexto = stream_context_create([
            'http' => ['timeout' => 10],
            'ssl' => ['cafile' => CaBundle::getSystemCaRootBundlePath(), 'verify_peer' => true],
        ]);
        $respuesta = @file_get_contents(self::URL_LLAVES, false, $contexto);
        $llaves = $respuesta ? json_decode($respuesta, true) : null;
        if (!is_array($llaves) || !$llaves) {
            return [];
        }
        $duracion = 3600;
        foreach ($http_response_header ?? [] as $cabecera) {
            if (preg_match('/max-age=(\d+)/i', $cabecera, $m)) {
                $duracion = (int) $m[1];
            }
        }
        if (!is_dir(dirname(self::CACHE))) {
            @mkdir(dirname(self::CACHE), 0775, true);
        }
        @file_put_contents(self::CACHE, json_encode(['expira' => time() + $duracion, 'llaves' => $llaves]));
        return $llaves;
    }
}
