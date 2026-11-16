<?php

namespace App\Core;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Autenticación con JSON Web Tokens (HS256).
 * En el navegador el token viaja en una cookie HttpOnly; en la API REST
 * también se acepta la cabecera "Authorization: Bearer <token>".
 * Al cerrar sesión el identificador del token (jti) se guarda en
 * token_revocado, de modo que deja de ser válido aunque no haya expirado.
 */
final class Auth
{
    private static ?array $usuario = null;
    private static bool $resuelto = false;

    public static function emitirToken(array $usuario): string
    {
        $ahora = time();
        $payload = [
            'iss' => 'voluntared',
            'sub' => (int) $usuario['idUsuario'],
            'rol' => $usuario['rol'],
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $ahora,
            'exp' => $ahora + Config::get('jwt.expiracion_horas') * 3600,
        ];
        return JWT::encode($payload, Config::get('app.clave'), 'HS256');
    }

    /** Decodifica y valida firma, expiración y revocación. Devuelve el payload o null. */
    public static function validarToken(string $token): ?object
    {
        try {
            $datos = JWT::decode($token, new Key(Config::get('app.clave'), 'HS256'));
        } catch (ExpiredException) {
            return null;
        } catch (Throwable) {
            return null;
        }
        $revocado = Database::valor('SELECT 1 FROM token_revocado WHERE jti = ?', [$datos->jti ?? '']);
        return $revocado ? null : $datos;
    }

    /** Inicia sesión en el navegador: emite el JWT y lo guarda en una cookie HttpOnly. */
    public static function iniciarSesion(array $usuario): string
    {
        $token = self::emitirToken($usuario);
        self::guardarCookie($token, time() + Config::get('jwt.expiracion_horas') * 3600);
        self::$usuario = null;
        self::$resuelto = false;
        return $token;
    }

    public static function cerrarSesion(): void
    {
        $token = self::tokenActual();
        if ($token) {
            self::revocar($token);
        }
        self::guardarCookie('', time() - 3600);
        self::$usuario = null;
        self::$resuelto = true;
    }

    public static function revocar(string $token): void
    {
        try {
            $datos = JWT::decode($token, new Key(Config::get('app.clave'), 'HS256'));
            Database::consulta(
                'INSERT IGNORE INTO token_revocado (jti, expiracion) VALUES (?, FROM_UNIXTIME(?))',
                [$datos->jti, (int) $datos->exp]
            );
            Database::consulta('DELETE FROM token_revocado WHERE expiracion < NOW()');
        } catch (Throwable) {
            // Token inválido o expirado: no hay nada que revocar.
        }
    }

    public static function tokenDesdeCabecera(): ?string
    {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $cabecera, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function tokenActual(): ?string
    {
        return self::tokenDesdeCabecera() ?? ($_COOKIE[Config::get('jwt.cookie')] ?? null);
    }

    /** Usuario autenticado con su rol y, si aplica, su fundación o perfil. */
    public static function user(): ?array
    {
        if (self::$resuelto) {
            return self::$usuario;
        }
        self::$resuelto = true;
        $token = self::tokenActual();
        if (!$token) {
            return null;
        }
        $datos = self::validarToken($token);
        if (!$datos) {
            if (!self::tokenDesdeCabecera()) {
                self::guardarCookie('', time() - 3600);
            }
            return null;
        }
        $usuario = \App\Models\Usuario::buscarConRol((int) $datos->sub);
        if (!$usuario || !$usuario['activo']) {
            // Cuenta suspendida después de emitido el token: se cierra la sesión.
            self::revocar($token);
            self::guardarCookie('', time() - 3600);
            return null;
        }
        if ($usuario['rol'] === 'fundacion') {
            $usuario['fundacion'] = \App\Models\Fundacion::porUsuario((int) $usuario['idUsuario']);
        } elseif ($usuario['rol'] === 'voluntario') {
            $usuario['perfil'] = \App\Models\PerfilVoluntario::porUsuario((int) $usuario['idUsuario']);
        }
        return self::$usuario = $usuario;
    }

    public static function id(): ?int
    {
        return isset(self::user()['idUsuario']) ? (int) self::user()['idUsuario'] : null;
    }

    /** Fuerza recargar el usuario (tras editar su perfil) o fija uno en pruebas. */
    public static function refrescar(?array $usuario = null): void
    {
        self::$usuario = $usuario;
        self::$resuelto = $usuario !== null;
    }

    private static function guardarCookie(string $valor, int $expira): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        setcookie(Config::get('jwt.cookie'), $valor, [
            'expires'  => $expira,
            'path'     => base_path_url() ?: '/',
            'httponly' => true,
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        if ($valor === '') {
            unset($_COOKIE[Config::get('jwt.cookie')]);
        } else {
            $_COOKIE[Config::get('jwt.cookie')] = $valor;
        }
    }
}
