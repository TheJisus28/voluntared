<?php

namespace App\Core;

/** Validación y almacenamiento seguro de archivos subidos. */
final class Upload
{
    public const IMAGENES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const DOCUMENTOS = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    /** Devuelve un mensaje de error o null si el archivo es válido. */
    public static function validar(?array $archivo, array $tipos, bool $requerido = true): ?string
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $requerido ? 'Debes adjuntar un archivo.' : null;
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return 'No se pudo recibir el archivo. Inténtalo de nuevo.';
        }
        $maxMb = Config::get('subidas.max_mb');
        if ($archivo['size'] > $maxMb * 1024 * 1024) {
            return "El archivo no puede superar $maxMb MB.";
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset($tipos[$mime])) {
            return 'Formato no permitido. Usa: ' . strtoupper(implode(', ', array_unique($tipos))) . '.';
        }
        return null;
    }

    /**
     * Guarda el archivo con un nombre aleatorio.
     * $publico = true lo deja en public/uploads (fotos); false en storage (documentos privados).
     * Devuelve la ruta relativa que se almacena en la base de datos.
     */
    public static function guardar(array $archivo, array $tipos, string $subcarpeta, bool $publico): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $ext = $tipos[$mime];
        $nombre = bin2hex(random_bytes(12)) . '.' . $ext;
        $base = $publico ? __DIR__ . '/../../public/uploads/' : __DIR__ . '/../../storage/';
        $destino = $base . $subcarpeta;
        if (!is_dir($destino)) {
            mkdir($destino, 0775, true);
        }
        $ok = is_uploaded_file($archivo['tmp_name'])
            ? move_uploaded_file($archivo['tmp_name'], "$destino/$nombre")
            : rename($archivo['tmp_name'], "$destino/$nombre");
        if (!$ok) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }
        return ($publico ? 'uploads/' : '') . $subcarpeta . '/' . $nombre;
    }

    public static function eliminar(?string $ruta, bool $publico): void
    {
        if (!$ruta || preg_match('#^https?://#', $ruta)) {
            return;
        }
        $base = $publico ? __DIR__ . '/../../public/' : __DIR__ . '/../../storage/';
        $archivo = realpath($base . $ruta);
        if ($archivo && str_starts_with($archivo, realpath($base)) && is_file($archivo)) {
            unlink($archivo);
        }
    }
}
