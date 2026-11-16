<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla participacion: asistencia y horas de los voluntarios aceptados. */
final class Participacion
{
    public static function porId(int $id): ?array
    {
        return DB::fila(
            'SELECT pa.*, p.Usuario_idUsuario AS voluntarioId, p.Oportunidad_idOportunidad, o.titulo,
                    f.Usuario_idUsuario AS fundacionUsuarioId, f.razonSocial, f.idFundacion,
                    u.nombre AS voluntarioNombre, u.apellido AS voluntarioApellido
             FROM participacion pa
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             WHERE pa.idParticipacion = ?',
            [$id]
        );
    }

    public static function totalHoras(int $idUsuario): float
    {
        return (float) DB::valor(
            'SELECT COALESCE(SUM(pa.horasCumplidas), 0) FROM participacion pa
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             WHERE p.Usuario_idUsuario = ? AND pa.asistio = 1',
            [$idUsuario]
        );
    }

    /** Historial del voluntario: actividades con participación registrada (CU Consultar historial). */
    public static function historial(int $idUsuario): array
    {
        return DB::filas(
            'SELECT pa.*, o.idOportunidad, o.titulo, o.fechaInicio, o.fechaFin, o.ciudad, c.nombre AS categoria,
                    f.razonSocial, f.idFundacion, f.Usuario_idUsuario AS fundacionUsuarioId,
                    (SELECT e.calificacion FROM evaluacion e WHERE e.Participacion_idParticipacion = pa.idParticipacion
                       AND e.evaluador_id = :yo1) AS miCalificacion,
                    (SELECT e.calificacion FROM evaluacion e WHERE e.Participacion_idParticipacion = pa.idParticipacion
                       AND e.evaluado_id = :yo2 AND e.visible = 1) AS calificacionRecibida
             FROM participacion pa
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             WHERE p.Usuario_idUsuario = :yo3
             ORDER BY o.fechaInicio DESC',
            ['yo1' => $idUsuario, 'yo2' => $idUsuario, 'yo3' => $idUsuario]
        );
    }

    /** Voluntarios aceptados de una oportunidad con su participación (si ya fue registrada). */
    public static function aceptadosDeOportunidad(int $idOportunidad, int $fundacionUsuarioId): array
    {
        return DB::filas(
            "SELECT p.idPostulacion, p.Usuario_idUsuario, u.nombre, u.apellido, u.foto, u.email,
                    pa.idParticipacion, pa.asistio, pa.horasCumplidas, pa.observacion, pa.fechaRegistro,
                    (SELECT e.calificacion FROM evaluacion e WHERE e.Participacion_idParticipacion = pa.idParticipacion
                       AND e.evaluador_id = ?) AS calificacionDada
             FROM postulacion p
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
             WHERE p.Oportunidad_idOportunidad = ? AND p.estado = 'aceptada'
             ORDER BY u.nombre, u.apellido",
            [$fundacionUsuarioId, $idOportunidad]
        );
    }

    /** Inserta o actualiza la participación de una postulación aceptada. Devuelve [id, esNueva]. */
    public static function guardar(int $idPostulacion, bool $asistio, float $horas, ?string $observacion): array
    {
        $existente = DB::valor('SELECT idParticipacion FROM participacion WHERE Postulacion_idPostulacion = ?', [$idPostulacion]);
        $datos = ['asistio' => (int) $asistio, 'horasCumplidas' => $horas, 'observacion' => $observacion];
        if ($existente) {
            DB::actualizar('participacion', $datos, 'idParticipacion = :id', ['id' => (int) $existente]);
            return [(int) $existente, false];
        }
        return [DB::insertar('participacion', $datos + ['Postulacion_idPostulacion' => $idPostulacion]), true];
    }
}
