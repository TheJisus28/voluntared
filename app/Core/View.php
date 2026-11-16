<?php

namespace App\Core;

/** Renderiza plantillas PHP de app/Views dentro de un layout. */
final class View
{
    public static function render(string $vista, array $datos = [], ?string $layout = 'layouts/main'): void
    {
        $contenido = self::capturar($vista, $datos);
        if ($layout === null) {
            echo $contenido;
            return;
        }
        echo self::capturar($layout, $datos + ['contenido' => $contenido]);
    }

    public static function capturar(string $__vista, array $__datos = []): string
    {
        // Nombres con prefijo "__" para que no choquen con las variables de la vista (p. ej. $datos).
        $__archivo = __DIR__ . '/../Views/' . $__vista . '.php';
        if (!is_file($__archivo)) {
            throw new \RuntimeException("Vista no encontrada: $__vista");
        }
        extract($__datos, EXTR_SKIP);
        unset($__datos);
        ob_start();
        require $__archivo;
        return ob_get_clean();
    }

    /** Incluye un fragmento reutilizable (partials/...). */
    public static function parcial(string $vista, array $datos = []): void
    {
        echo self::capturar('partials/' . $vista, $datos);
    }

    public static function json(mixed $datos, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
