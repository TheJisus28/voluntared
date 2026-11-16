<?php

namespace App\Controllers;

use App\Core\HttpException;

/** Entrega segura de documentos privados almacenados fuera de la carpeta pública. */
final class ArchivoController
{
    public static function servirDocumento(?string $ruta, string $nombreDescarga): void
    {
        $base = realpath(__DIR__ . '/../../storage');
        $archivo = $ruta ? realpath($base . '/' . $ruta) : false;
        if (!$archivo || !str_starts_with($archivo, $base) || !is_file($archivo)) {
            throw new HttpException(404, 'El documento no está disponible.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo);
        $ext = pathinfo($archivo, PATHINFO_EXTENSION);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $nombreDescarga . '.' . $ext . '"');
        header('Content-Length: ' . filesize($archivo));
        header('Cache-Control: private, no-store');
        readfile($archivo);
    }
}
