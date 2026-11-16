<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla notificacion: avisos del sistema para cada usuario. */
final class Notificacion
{
    public static function crear(int $idUsuario, string $tipo, string $titulo, string $contenido, ?string $enlace = null): int
    {
        return DB::insertar('notificacion', [
            'Usuario_idUsuario' => $idUsuario,
            'tipo' => $tipo,
            'titulo' => mb_substr($titulo, 0, 100),
            'contenido' => mb_substr($contenido, 0, 300),
            'enlace' => $enlace,
        ]);
    }

    public static function deUsuario(int $idUsuario, int $limite = 50, bool $soloNoLeidas = false): array
    {
        $filtro = $soloNoLeidas ? ' AND leida = 0' : '';
        return DB::filas(
            "SELECT * FROM notificacion WHERE Usuario_idUsuario = ? $filtro ORDER BY leida ASC, fechaCreacion DESC, idNotificacion DESC LIMIT ?",
            [$idUsuario, $limite]
        );
    }

    public static function noLeidas(int $idUsuario): int
    {
        return (int) DB::valor('SELECT COUNT(*) FROM notificacion WHERE Usuario_idUsuario = ? AND leida = 0', [$idUsuario]);
    }

    public static function delUsuario(int $id, int $idUsuario): ?array
    {
        return DB::fila('SELECT * FROM notificacion WHERE idNotificacion = ? AND Usuario_idUsuario = ?', [$id, $idUsuario]);
    }

    public static function marcarLeida(int $id, int $idUsuario): void
    {
        DB::consulta('UPDATE notificacion SET leida = 1 WHERE idNotificacion = ? AND Usuario_idUsuario = ?', [$id, $idUsuario]);
    }

    public static function marcarTodas(int $idUsuario): int
    {
        return DB::consulta('UPDATE notificacion SET leida = 1 WHERE Usuario_idUsuario = ? AND leida = 0', [$idUsuario])->rowCount();
    }

    /** Evita repetir avisos de chat: ¿ya hay uno sin leer para esta conversación? */
    public static function hayPendienteConEnlace(int $idUsuario, string $enlace): bool
    {
        return (bool) DB::valor(
            "SELECT 1 FROM notificacion WHERE Usuario_idUsuario = ? AND enlace = ? AND leida = 0 AND tipo = 'mensaje'",
            [$idUsuario, $enlace]
        );
    }
}
