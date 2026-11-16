<?php

namespace App\Models;

use App\Core\Database as DB;

/** Tabla oportunidad: actividades de voluntariado publicadas por fundaciones. */
final class Oportunidad
{
    private const SELECT = "SELECT o.*, c.nombre AS categoria, f.razonSocial, f.idFundacion, f.Usuario_idUsuario AS fundacionUsuarioId,
                                   f.estadoVerificacion, u.foto AS fundacionFoto
                            FROM oportunidad o
                            JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
                            JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
                            JOIN usuario u ON u.idUsuario = f.Usuario_idUsuario";

    public static function porId(int $id): ?array
    {
        $o = DB::fila(self::SELECT . ' WHERE o.idOportunidad = ?', [$id]);
        if ($o) {
            $o['habilidades'] = self::habilidades($id);
        }
        return $o;
    }

    public static function habilidades(int $id): array
    {
        return DB::filas(
            'SELECT h.* FROM oportunidad_has_habilidad oh JOIN habilidad h ON h.idHabilidad = oh.Habilidad_idHabilidad
             WHERE oh.Oportunidad_idOportunidad = ? ORDER BY h.nombre',
            [$id]
        );
    }

    public static function sincronizarHabilidades(int $id, array $ids): void
    {
        DB::consulta('DELETE FROM oportunidad_has_habilidad WHERE Oportunidad_idOportunidad = ?', [$id]);
        foreach (array_unique(array_map('intval', $ids)) as $idHabilidad) {
            DB::insertar('oportunidad_has_habilidad', ['Oportunidad_idOportunidad' => $id, 'Habilidad_idHabilidad' => $idHabilidad]);
        }
    }

    /**
     * Búsqueda pública paginada (RF7). Solo muestra oportunidades abiertas y visibles
     * de fundaciones verificadas. Si se indica un perfil de voluntario, ordena primero
     * las oportunidades afines a sus intereses, habilidades y ciudad.
     *
     * @return array{0: array, 1: int} [oportunidades, total]
     */
    public static function buscar(array $filtros, int $pagina, int $porPagina, ?array $perfil = null): array
    {
        $where = ["o.estado = 'abierta'", 'o.visible = 1', "f.estadoVerificacion = 'verificada'", 'u.activo = 1'];
        $params = [];
        if (!empty($filtros['q'])) {
            $where[] = '(o.titulo LIKE :q1 OR o.descripcion LIKE :q2 OR f.razonSocial LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['categoria'])) {
            $where[] = 'o.Categoria_idCategoria = :categoria';
            $params['categoria'] = (int) $filtros['categoria'];
        }
        if (!empty($filtros['ciudad'])) {
            $where[] = 'o.ciudad = :ciudad';
            $params['ciudad'] = $filtros['ciudad'];
        }
        if (!empty($filtros['modalidad'])) {
            $where[] = 'o.modalidad = :modalidad';
            $params['modalidad'] = $filtros['modalidad'];
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'o.fechaFin >= :desde';
            $params['desde'] = $filtros['desde'];
        }
        if (!empty($filtros['excluir_postulado'])) {
            // Recomendaciones: no repetir actividades a las que la persona ya se postuló.
            $where[] = "NOT EXISTS (SELECT 1 FROM postulacion px WHERE px.Oportunidad_idOportunidad = o.idOportunidad
                        AND px.Usuario_idUsuario = :excluir AND px.estado <> 'cancelada')";
            $params['excluir'] = (int) $filtros['excluir_postulado'];
        }
        if (!empty($filtros['habilidad'])) {
            $where[] = 'EXISTS (SELECT 1 FROM oportunidad_has_habilidad oh WHERE oh.Oportunidad_idOportunidad = o.idOportunidad AND oh.Habilidad_idHabilidad = :habilidad)';
            $params['habilidad'] = (int) $filtros['habilidad'];
        }

        $afinidad = '0';
        $paramsAfinidad = [];
        if ($perfil) {
            $afinidad = '(CASE WHEN o.Categoria_idCategoria IN (SELECT Categoria_idCategoria FROM voluntario_has_categoria WHERE PerfilVoluntario_id = :perfil1) THEN 3 ELSE 0 END
                + (SELECT COUNT(*) FROM oportunidad_has_habilidad oh2 JOIN voluntario_has_habilidad vh ON vh.Habilidad_idHabilidad = oh2.Habilidad_idHabilidad
                   AND vh.PerfilVoluntario_id = :perfil2 WHERE oh2.Oportunidad_idOportunidad = o.idOportunidad)
                + CASE WHEN o.ciudad = :ciudadPerfil THEN 1 ELSE 0 END)';
            $paramsAfinidad = [
                'perfil1' => (int) $perfil['idPerfilVoluntario'],
                'perfil2' => (int) $perfil['idPerfilVoluntario'],
                'ciudadPerfil' => $perfil['ciudad'],
            ];
        }

        $sqlWhere = implode(' AND ', $where);
        $desde = 'FROM oportunidad o JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
                  JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion JOIN usuario u ON u.idUsuario = f.Usuario_idUsuario';
        $total = (int) DB::valor("SELECT COUNT(*) $desde WHERE $sqlWhere", $params);
        $items = DB::filas(
            "SELECT o.*, c.nombre AS categoria, f.razonSocial, f.idFundacion, u.foto AS fundacionFoto, $afinidad AS afinidad
             $desde WHERE $sqlWhere
             ORDER BY afinidad DESC, o.fechaInicio ASC, o.idOportunidad DESC
             LIMIT :limite OFFSET :offset",
            $params + $paramsAfinidad + ['limite' => $porPagina, 'offset' => max(0, ($pagina - 1) * $porPagina)]
        );
        return [$items, $total];
    }

    public static function ciudadesDisponibles(): array
    {
        return array_column(DB::filas(
            "SELECT DISTINCT ciudad FROM oportunidad WHERE estado = 'abierta' AND visible = 1 ORDER BY ciudad"
        ), 'ciudad');
    }

    public static function deFundacion(int $idFundacion, ?string $estado = null): array
    {
        $params = [$idFundacion];
        $filtro = '';
        if ($estado) {
            $filtro = ' AND o.estado = ?';
            $params[] = $estado;
        }
        return DB::filas(
            "SELECT o.*, c.nombre AS categoria,
                    (SELECT COUNT(*) FROM postulacion p WHERE p.Oportunidad_idOportunidad = o.idOportunidad AND p.estado = 'pendiente') AS pendientes,
                    (SELECT COUNT(*) FROM postulacion p WHERE p.Oportunidad_idOportunidad = o.idOportunidad AND p.estado = 'aceptada') AS aceptadas,
                    (SELECT COUNT(*) FROM postulacion p JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
                       WHERE p.Oportunidad_idOportunidad = o.idOportunidad) AS participaciones
             FROM oportunidad o JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
             WHERE o.Fundacion_idFundacion = ? $filtro
             ORDER BY FIELD(o.estado, 'abierta', 'cerrada', 'finalizada', 'cancelada'), o.fechaInicio DESC",
            $params
        );
    }

    /** Oportunidades abiertas de una fundación para su perfil público. */
    public static function abiertasDeFundacion(int $idFundacion): array
    {
        return DB::filas(
            "SELECT o.*, c.nombre AS categoria FROM oportunidad o JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
             WHERE o.Fundacion_idFundacion = ? AND o.estado = 'abierta' AND o.visible = 1 ORDER BY o.fechaInicio",
            [$idFundacion]
        );
    }

    public static function crear(array $datos): int
    {
        return DB::insertar('oportunidad', $datos);
    }

    public static function actualizar(int $id, array $datos): void
    {
        DB::actualizar('oportunidad', $datos, 'idOportunidad = :id', ['id' => $id]);
    }

    /** Las actividades cuya fecha de fin ya pasó quedan finalizadas automáticamente. */
    public static function finalizarVencidas(): int
    {
        return DB::consulta(
            "UPDATE oportunidad SET estado = 'finalizada' WHERE fechaFin < CURDATE() AND estado IN ('abierta', 'cerrada')"
        )->rowCount();
    }

    /** Oportunidades recientes para moderación (incluye las ocultas). */
    public static function recientesParaModeracion(int $limite = 30): array
    {
        return DB::filas(
            'SELECT o.idOportunidad, o.titulo, o.estado, o.visible, o.fechaPublicacion, o.ciudad, f.razonSocial,
                    f.Usuario_idUsuario AS fundacionUsuarioId, c.nombre AS categoria
             FROM oportunidad o JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
             ORDER BY o.visible ASC, o.fechaPublicacion DESC LIMIT ?',
            [$limite]
        );
    }
}
