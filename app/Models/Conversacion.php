<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tablas conversacion y mensaje: chat entre voluntarios y fundaciones. */
final class Conversacion
{
    public static function buscarOCrear(int $voluntarioId, int $fundacionId, ?int $idOportunidad): int
    {
        $id = DB::valor(
            'SELECT idConversacion FROM conversacion WHERE voluntario_id = ? AND fundacion_id = ? AND Oportunidad_idOportunidad <=> ?',
            [$voluntarioId, $fundacionId, $idOportunidad]
        );
        if ($id) {
            return (int) $id;
        }
        return DB::insertar('conversacion', [
            'voluntario_id' => $voluntarioId,
            'fundacion_id' => $fundacionId,
            'Oportunidad_idOportunidad' => $idOportunidad,
        ]);
    }

    /** Conversación con los datos de ambas partes, solo si el usuario participa en ella. */
    public static function paraUsuario(int $idConversacion, int $idUsuario): ?array
    {
        return DB::fila(
            'SELECT c.*, o.titulo AS oportunidad,
                    v.nombre AS voluntarioNombre, v.apellido AS voluntarioApellido, v.foto AS voluntarioFoto,
                    fu.foto AS fundacionFoto, f.razonSocial, f.idFundacion
             FROM conversacion c
             JOIN usuario v ON v.idUsuario = c.voluntario_id
             JOIN usuario fu ON fu.idUsuario = c.fundacion_id
             JOIN fundacion f ON f.Usuario_idUsuario = fu.idUsuario
             LEFT JOIN oportunidad o ON o.idOportunidad = c.Oportunidad_idOportunidad
             WHERE c.idConversacion = :id AND (c.voluntario_id = :u1 OR c.fundacion_id = :u2)',
            ['id' => $idConversacion, 'u1' => $idUsuario, 'u2' => $idUsuario]
        );
    }

    /** Bandeja de conversaciones con el último mensaje y los no leídos. */
    public static function deUsuario(int $idUsuario): array
    {
        return DB::filas(
            'SELECT c.idConversacion, c.voluntario_id, c.fundacion_id, c.fechaCreacion, o.titulo AS oportunidad,
                    v.nombre AS voluntarioNombre, v.apellido AS voluntarioApellido, v.foto AS voluntarioFoto,
                    fu.foto AS fundacionFoto, f.razonSocial,
                    (SELECT m.contenido FROM mensaje m WHERE m.Conversacion_idConversacion = c.idConversacion ORDER BY m.idMensaje DESC LIMIT 1) AS ultimoMensaje,
                    (SELECT m.fechaEnvio FROM mensaje m WHERE m.Conversacion_idConversacion = c.idConversacion ORDER BY m.idMensaje DESC LIMIT 1) AS ultimaFecha,
                    (SELECT COUNT(*) FROM mensaje m WHERE m.Conversacion_idConversacion = c.idConversacion AND m.leido = 0 AND m.emisor_id <> :u1) AS noLeidos
             FROM conversacion c
             JOIN usuario v ON v.idUsuario = c.voluntario_id
             JOIN usuario fu ON fu.idUsuario = c.fundacion_id
             JOIN fundacion f ON f.Usuario_idUsuario = fu.idUsuario
             LEFT JOIN oportunidad o ON o.idOportunidad = c.Oportunidad_idOportunidad
             WHERE c.voluntario_id = :u2 OR c.fundacion_id = :u3
             ORDER BY COALESCE(ultimaFecha, c.fechaCreacion) DESC',
            ['u1' => $idUsuario, 'u2' => $idUsuario, 'u3' => $idUsuario]
        );
    }

    public static function mensajes(int $idConversacion, int $despuesDe = 0): array
    {
        return DB::filas(
            'SELECT idMensaje, emisor_id, contenido, leido, fechaEnvio FROM mensaje
             WHERE Conversacion_idConversacion = ? AND idMensaje > ? ORDER BY idMensaje',
            [$idConversacion, $despuesDe]
        );
    }

    public static function enviar(int $idConversacion, int $emisorId, string $contenido): array
    {
        $id = DB::insertar('mensaje', [
            'Conversacion_idConversacion' => $idConversacion,
            'emisor_id' => $emisorId,
            'contenido' => $contenido,
        ]);
        return DB::fila('SELECT idMensaje, emisor_id, contenido, leido, fechaEnvio FROM mensaje WHERE idMensaje = ?', [$id]);
    }

    public static function marcarLeidos(int $idConversacion, int $lectorId): void
    {
        DB::consulta(
            'UPDATE mensaje SET leido = 1 WHERE Conversacion_idConversacion = ? AND emisor_id <> ? AND leido = 0',
            [$idConversacion, $lectorId]
        );
    }

    public static function noLeidosTotal(int $idUsuario): int
    {
        return (int) DB::valor(
            'SELECT COUNT(*) FROM mensaje m JOIN conversacion c ON c.idConversacion = m.Conversacion_idConversacion
             WHERE (c.voluntario_id = :u1 OR c.fundacion_id = :u2) AND m.emisor_id <> :u3 AND m.leido = 0',
            ['u1' => $idUsuario, 'u2' => $idUsuario, 'u3' => $idUsuario]
        );
    }
}
