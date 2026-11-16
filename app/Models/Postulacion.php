<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla postulacion: solicitudes de voluntarios a oportunidades. */
final class Postulacion
{
    public static function porId(int $id): ?array
    {
        return DB::fila(
            'SELECT p.*, o.titulo, o.estado AS estadoOportunidad, o.Fundacion_idFundacion, o.cuposDisponibles,
                    f.Usuario_idUsuario AS fundacionUsuarioId, f.razonSocial,
                    u.nombre AS voluntarioNombre, u.apellido AS voluntarioApellido, u.email AS voluntarioEmail
             FROM postulacion p
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             WHERE p.idPostulacion = ?',
            [$id]
        );
    }

    public static function existente(int $idUsuario, int $idOportunidad): ?array
    {
        return DB::fila(
            'SELECT * FROM postulacion WHERE Usuario_idUsuario = ? AND Oportunidad_idOportunidad = ?',
            [$idUsuario, $idOportunidad]
        );
    }

    /** "Mis postulaciones" del voluntario con el estado de la participación y de su evaluación. */
    public static function deVoluntario(int $idUsuario, ?string $estado = null): array
    {
        $params = ['usuario' => $idUsuario, 'evaluador' => $idUsuario];
        $filtro = '';
        if ($estado) {
            $filtro = ' AND p.estado = :estado';
            $params['estado'] = $estado;
        }
        return DB::filas(
            "SELECT p.*, o.titulo, o.fechaInicio, o.fechaFin, o.horaInicio, o.horaFin, o.ciudad, o.modalidad,
                    o.estado AS estadoOportunidad, f.razonSocial, f.idFundacion,
                    pa.idParticipacion, pa.asistio, pa.horasCumplidas,
                    (SELECT COUNT(*) FROM evaluacion e WHERE e.Participacion_idParticipacion = pa.idParticipacion
                       AND e.evaluador_id = :evaluador) AS evaluada
             FROM postulacion p
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
             WHERE p.Usuario_idUsuario = :usuario $filtro
             ORDER BY p.fechaPostulacion DESC",
            $params
        );
    }

    /** Postulaciones de una oportunidad con un resumen del perfil del voluntario (CU Gestionar postulaciones). */
    public static function deOportunidad(int $idOportunidad, ?string $estado = null): array
    {
        $params = [$idOportunidad];
        $filtro = '';
        if ($estado) {
            $filtro = ' AND p.estado = ?';
            $params[] = $estado;
        }
        return DB::filas(
            "SELECT p.*, u.nombre, u.apellido, u.email, u.foto, pv.ciudad, pv.disponibilidad,
                    (SELECT ROUND(AVG(e.calificacion), 1) FROM evaluacion e WHERE e.evaluado_id = u.idUsuario AND e.visible = 1) AS promedio,
                    (SELECT COALESCE(SUM(pa.horasCumplidas), 0) FROM participacion pa JOIN postulacion p2 ON p2.idPostulacion = pa.Postulacion_idPostulacion
                       WHERE p2.Usuario_idUsuario = u.idUsuario AND pa.asistio = 1) AS horas,
                    (SELECT GROUP_CONCAT(h.nombre ORDER BY h.nombre SEPARATOR ', ') FROM voluntario_has_habilidad vh
                       JOIN habilidad h ON h.idHabilidad = vh.Habilidad_idHabilidad WHERE vh.PerfilVoluntario_id = pv.idPerfilVoluntario) AS habilidades
             FROM postulacion p
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             LEFT JOIN perfil_voluntario pv ON pv.Usuario_idUsuario = u.idUsuario
             WHERE p.Oportunidad_idOportunidad = ? $filtro
             ORDER BY FIELD(p.estado, 'pendiente', 'aceptada', 'rechazada', 'cancelada'), p.fechaPostulacion",
            $params
        );
    }

    public static function contarPorEstadoVoluntario(int $idUsuario): array
    {
        $filas = DB::filas('SELECT estado, COUNT(*) total FROM postulacion WHERE Usuario_idUsuario = ? GROUP BY estado', [$idUsuario]);
        return array_column($filas, 'total', 'estado') + ['pendiente' => 0, 'aceptada' => 0, 'rechazada' => 0, 'cancelada' => 0];
    }

    /** Postulaciones pendientes recientes de una fundación (panel principal). */
    public static function pendientesDeFundacion(int $idFundacion, int $limite = 5): array
    {
        return DB::filas(
            "SELECT p.*, o.titulo, u.nombre, u.apellido, u.foto
             FROM postulacion p JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             WHERE o.Fundacion_idFundacion = ? AND p.estado = 'pendiente'
             ORDER BY p.fechaPostulacion DESC LIMIT ?",
            [$idFundacion, $limite]
        );
    }

    /** ¿La fundación (usuario) tiene relación con el voluntario a través de alguna postulación? */
    public static function hayRelacion(int $fundacionUsuarioId, int $voluntarioId): bool
    {
        return (bool) DB::valor(
            'SELECT 1 FROM postulacion p JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             WHERE f.Usuario_idUsuario = ? AND p.Usuario_idUsuario = ? LIMIT 1',
            [$fundacionUsuarioId, $voluntarioId]
        );
    }
}
