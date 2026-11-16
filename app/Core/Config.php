<?php

namespace App\Core;

/** Acceso a la configuración con notación de puntos: Config::get('db.host'). */
final class Config
{
    private static ?array $valores = null;

    public static function get(string $clave, mixed $defecto = null): mixed
    {
        self::$valores ??= require __DIR__ . '/../config.php';
        $valor = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }

    /** Permite a las pruebas reemplazar valores en tiempo de ejecución. */
    public static function set(string $clave, mixed $valor): void
    {
        self::$valores ??= require __DIR__ . '/../config.php';
        $ref = &self::$valores;
        foreach (explode('.', $clave) as $parte) {
            $ref = &$ref[$parte];
        }
        $ref = $valor;
    }
}
