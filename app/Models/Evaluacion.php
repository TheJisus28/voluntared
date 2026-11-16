<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla evaluacion: calificaciones mutuas entre voluntario y fundación. */
final class Evaluacion
{
    public static function existe(int $idParticipacion, int $evaluadorId): bool
    {
        return (bool) DB::valor(
            'SELECT 1 FROM evaluacion WHERE Participacion_idParticipacion = ? AND evaluador_id = ?',
            [$idParticipacion, $evaluadorId]
        );
    }

    public static function crear(int $idParticipacion, int $evaluadorId, int $evaluadoId, int $calificacion, ?string $comentario): int
    {
        return DB::insertar('evaluacion', [
            'Participacion_idParticipacion' => $idParticipacion,
            'evaluador_id' => $evaluadorId,
            'evaluado_id' => $evaluadoId,
            'calificacion' => $calificacion,
            'comentario' => $comentario,
        ]);
    }

    /** Evaluaciones visibles recibidas por un usuario, con el nombre de quien evaluó. */
    public static function recibidas(int $idUsuario, int $limite = 20): array
    {
        return DB::filas(
            'SELECT e.*, u.nombre, u.apellido, u.foto, f.razonSocial, o.titulo
             FROM evaluacion e
             JOIN usuario u ON u.idUsuario = e.evaluador_id
             LEFT JOIN fundacion f ON f.Usuario_idUsuario = u.idUsuario
             JOIN participacion pa ON pa.idParticipacion = e.Participacion_idParticipacion
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             WHERE e.evaluado_id = ? AND e.visible = 1
             ORDER BY e.fecha DESC LIMIT ?',
            [$idUsuario, $limite]
        );
    }

    public static function recientesParaModeracion(int $limite = 30): array
    {
        return DB::filas(
            'SELECT e.*, ev.nombre AS evaluadorNombre, ev.apellido AS evaluadorApellido, fe.razonSocial AS evaluadorFundacion,
                    eo.nombre AS evaluadoNombre, eo.apellido AS evaluadoApellido, fo.razonSocial AS evaluadoFundacion, o.titulo
             FROM evaluacion e
             JOIN usuario ev ON ev.idUsuario = e.evaluador_id
             LEFT JOIN fundacion fe ON fe.Usuario_idUsuario = ev.idUsuario
             JOIN usuario eo ON eo.idUsuario = e.evaluado_id
             LEFT JOIN fundacion fo ON fo.Usuario_idUsuario = eo.idUsuario
             JOIN participacion pa ON pa.idParticipacion = e.Participacion_idParticipacion
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             ORDER BY e.visible ASC, e.fecha DESC LIMIT ?',
            [$limite]
        );
    }

    /** Un comentario real de 5 estrellas de un voluntario a una fundación (portada y pantallas de acceso). */
    public static function testimonio(): ?array
    {
        return DB::fila(
            "SELECT e.comentario, u.nombre, u.apellido, f.razonSocial, o.titulo
             FROM evaluacion e
             JOIN usuario u ON u.idUsuario = e.evaluador_id AND u.Rol_idRol = 1
             JOIN fundacion f ON f.Usuario_idUsuario = e.evaluado_id
             JOIN participacion pa ON pa.idParticipacion = e.Participacion_idParticipacion
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             WHERE e.visible = 1 AND e.calificacion = 5 AND e.comentario IS NOT NULL AND CHAR_LENGTH(e.comentario) >= 40
             ORDER BY e.fecha DESC LIMIT 1"
        );
    }

    public static function porId(int $id): ?array
    {
        return DB::fila('SELECT * FROM evaluacion WHERE idEvaluacion = ?', [$id]);
    }

    public static function cambiarVisibilidad(int $id, bool $visible): void
    {
        DB::actualizar('evaluacion', ['visible' => (int) $visible], 'idEvaluacion = :id', ['id' => $id]);
    }
}
