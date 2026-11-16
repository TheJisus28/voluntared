<?php

namespace App\Core;

/** Mensajes de una sola lectura, errores de validación y datos anteriores del formulario. */
final class Flash
{
    private static ?array $actual = null;

    /** Mueve los datos guardados en la petición anterior para que estén disponibles solo en esta. */
    public static function iniciar(): void
    {
        self::$actual = $_SESSION['_flash'] ?? ['mensajes' => [], 'errores' => [], 'old' => []];
        unset($_SESSION['_flash']);
    }

    public static function set(string $tipo, string $mensaje): void
    {
        $_SESSION['_flash']['mensajes'][] = ['tipo' => $tipo, 'texto' => $mensaje];
    }

    public static function errores(): array
    {
        return self::$actual['errores'] ?? [];
    }

    /** Guarda errores y datos enviados para volver a mostrar el formulario. */
    public static function conErrores(array $errores, array $datos = []): void
    {
        unset($datos['contrasena'], $datos['contrasena_confirmacion'], $datos['_csrf']);
        $_SESSION['_flash']['errores'] = $errores;
        $_SESSION['_flash']['old'] = $datos;
    }

    public static function mensajes(): array
    {
        return self::$actual['mensajes'] ?? [];
    }

    public static function old(string $campo, mixed $defecto = ''): mixed
    {
        return self::$actual['old'][$campo] ?? $defecto;
    }

    public static function hayOld(): bool
    {
        return !empty(self::$actual['old']);
    }
}
