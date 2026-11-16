<?php

namespace App\Core;

/** Protección contra falsificación de peticiones (CSRF) con un token por sesión. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verificar(): bool
    {
        $enviado = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($enviado) && $enviado !== '' && hash_equals(self::token(), $enviado);
    }
}
