<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla fundacion: organizaciones que publican oportunidades. */
final class Fundacion
{
    private const SELECT = 'SELECT f.*, u.nombre, u.apellido, u.email, u.telefono, u.foto, u.activo, u.fechaCreacion
                            FROM fundacion f JOIN usuario u ON u.idUsuario = f.Usuario_idUsuario';

    public static function porUsuario(int $idUsuario): ?array
    {
        return DB::fila(self::SELECT . ' WHERE f.Usuario_idUsuario = ?', [$idUsuario]);
    }

    public static function porId(int $id): ?array
    {
        return DB::fila(self::SELECT . ' WHERE f.idFundacion = ?', [$id]);
    }

    public static function nitExiste(string $nit, ?int $excluirId = null): bool
    {
        return (bool) DB::valor('SELECT 1 FROM fundacion WHERE nit = ? AND idFundacion <> ?', [$nit, $excluirId ?? 0]);
    }

    public static function crear(int $idUsuario, array $datos): int
    {
        return DB::insertar('fundacion', [
            'Usuario_idUsuario' => $idUsuario,
            'nit' => $datos['nit'],
            'razonSocial' => $datos['razonSocial'],
            'descripcion' => $datos['descripcion'],
            'mision' => $datos['mision'] ?? null,
            'direccion' => $datos['direccion'],
            'ciudad' => $datos['ciudad'],
            'sitioWeb' => $datos['sitioWeb'] ?? null,
            'documentoSoporte' => $datos['documentoSoporte'],
            'estadoVerificacion' => $datos['estadoVerificacion'] ?? 'pendiente',
            'fechaVerificacion' => $datos['fechaVerificacion'] ?? null,
        ]);
    }

    public static function actualizar(int $id, array $datos): void
    {
        DB::actualizar('fundacion', $datos, 'idFundacion = :id', ['id' => $id]);
    }

    public static function porEstado(string $estado): array
    {
        return DB::filas(self::SELECT . ' WHERE f.estadoVerificacion = ? ORDER BY u.fechaCreacion ASC', [$estado]);
    }

    public static function contarPorEstado(): array
    {
        $filas = DB::filas('SELECT estadoVerificacion, COUNT(*) total FROM fundacion GROUP BY estadoVerificacion');
        return array_column($filas, 'total', 'estadoVerificacion') + ['pendiente' => 0, 'verificada' => 0, 'rechazada' => 0];
    }

    /** Fundación verificada con sus estadísticas públicas (perfil visible para voluntarios). */
    public static function perfilPublico(int $id): ?array
    {
        $f = DB::fila(self::SELECT . " WHERE f.idFundacion = ? AND f.estadoVerificacion = 'verificada' AND u.activo = 1", [$id]);
        if (!$f) {
            return null;
        }
        $f['calificacion'] = Usuario::promedio((int) $f['Usuario_idUsuario']);
        $f['evaluaciones'] = Evaluacion::recibidas((int) $f['Usuario_idUsuario']);
        $f['totales'] = DB::fila(
            "SELECT COUNT(DISTINCT o.idOportunidad) AS oportunidades,
                    COUNT(DISTINCT CASE WHEN pa.asistio = 1 THEN po.Usuario_idUsuario END) AS voluntarios,
                    COALESCE(SUM(CASE WHEN pa.asistio = 1 THEN pa.horasCumplidas END), 0) AS horas
             FROM oportunidad o
             LEFT JOIN postulacion po ON po.Oportunidad_idOportunidad = o.idOportunidad
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = po.idPostulacion
             WHERE o.Fundacion_idFundacion = ? AND o.visible = 1",
            [$id]
        );
        return $f;
    }
}
