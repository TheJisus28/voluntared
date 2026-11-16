<?php

namespace App\Services;

use App\Core\Database as DB;

/**
 * RF16: indicadores y datos para los gráficos del tablero de estadísticas.
 * Todos los indicadores respetan el periodo y la categoría seleccionados.
 */
final class EstadisticaService
{
    public static function tablero(?string $desde, ?string $hasta, ?int $categoria): array
    {
        $desde = $desde ?: date('Y-m-d', strtotime('-11 months', strtotime(date('Y-m-01'))));
        $hasta = $hasta ?: date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $cat = $categoria ? ' AND o.Categoria_idCategoria = :categoria' : '';
        $pc = $categoria ? ['categoria' => $categoria] : [];
        $rango = ['desde' => $desde, 'hasta' => $hasta];

        $indicadores = [
            'usuarios_nuevos' => (int) DB::valor('SELECT COUNT(*) FROM usuario WHERE DATE(fechaCreacion) BETWEEN :desde AND :hasta', $rango),
            'voluntarios' => (int) DB::valor('SELECT COUNT(*) FROM usuario WHERE Rol_idRol = 1 AND activo = 1'),
            'fundaciones_verificadas' => (int) DB::valor("SELECT COUNT(*) FROM fundacion WHERE estadoVerificacion = 'verificada'"),
            'oportunidades' => (int) DB::valor(
                "SELECT COUNT(*) FROM oportunidad o WHERE DATE(o.fechaPublicacion) BETWEEN :desde AND :hasta $cat", $rango + $pc),
            'postulaciones' => (int) DB::valor(
                "SELECT COUNT(*) FROM postulacion p JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
                 WHERE DATE(p.fechaPostulacion) BETWEEN :desde AND :hasta $cat", $rango + $pc),
            'aceptadas' => (int) DB::valor(
                "SELECT COUNT(*) FROM postulacion p JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
                 WHERE p.estado = 'aceptada' AND DATE(p.fechaPostulacion) BETWEEN :desde AND :hasta $cat", $rango + $pc),
            'horas' => (float) DB::valor(
                "SELECT COALESCE(SUM(pa.horasCumplidas), 0) FROM participacion pa
                 JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
                 JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
                 WHERE pa.asistio = 1 AND o.fechaInicio BETWEEN :desde AND :hasta $cat", $rango + $pc),
            'participantes' => (int) DB::valor(
                "SELECT COUNT(DISTINCT p.Usuario_idUsuario) FROM participacion pa
                 JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
                 JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
                 WHERE pa.asistio = 1 AND o.fechaInicio BETWEEN :desde AND :hasta $cat", $rango + $pc),
        ];
        $indicadores['tasa_aceptacion'] = $indicadores['postulaciones']
            ? round($indicadores['aceptadas'] * 100 / $indicadores['postulaciones']) : 0;

        // Postulaciones y oportunidades por mes dentro del periodo
        $meses = [];
        $cursor = strtotime(date('Y-m-01', strtotime($desde)));
        $fin = strtotime(date('Y-m-01', strtotime($hasta)));
        while ($cursor <= $fin && count($meses) < 36) {
            $meses[date('Y-m', $cursor)] = ['postulaciones' => 0, 'oportunidades' => 0];
            $cursor = strtotime('+1 month', $cursor);
        }
        foreach (DB::filas(
            "SELECT DATE_FORMAT(p.fechaPostulacion, '%Y-%m') AS mes, COUNT(*) AS total FROM postulacion p
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             WHERE DATE(p.fechaPostulacion) BETWEEN :desde AND :hasta $cat GROUP BY mes", $rango + $pc) as $r) {
            if (isset($meses[$r['mes']])) {
                $meses[$r['mes']]['postulaciones'] = (int) $r['total'];
            }
        }
        foreach (DB::filas(
            "SELECT DATE_FORMAT(o.fechaPublicacion, '%Y-%m') AS mes, COUNT(*) AS total FROM oportunidad o
             WHERE DATE(o.fechaPublicacion) BETWEEN :desde AND :hasta $cat GROUP BY mes", $rango + $pc) as $r) {
            if (isset($meses[$r['mes']])) {
                $meses[$r['mes']]['oportunidades'] = (int) $r['total'];
            }
        }
        $etiquetasMes = array_map(static function ($m) {
            [$a, $n] = explode('-', $m);
            return MESES_CORTOS[(int) $n - 1] . ' ' . substr($a, 2);
        }, array_keys($meses));

        $porCategoria = DB::filas(
            "SELECT c.nombre, COUNT(DISTINCT o.idOportunidad) AS oportunidades,
                    COALESCE(SUM(CASE WHEN pa.asistio = 1 THEN pa.horasCumplidas END), 0) AS horas
             FROM categoria c
             LEFT JOIN oportunidad o ON o.Categoria_idCategoria = c.idCategoria AND DATE(o.fechaPublicacion) BETWEEN :desde AND :hasta
             LEFT JOIN postulacion p ON p.Oportunidad_idOportunidad = o.idOportunidad
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
             " . ($categoria ? 'WHERE c.idCategoria = :categoria' : '') . '
             GROUP BY c.idCategoria ORDER BY oportunidades DESC, c.nombre',
            $rango + $pc
        );

        $porEstado = ['pendiente' => 0, 'aceptada' => 0, 'rechazada' => 0, 'cancelada' => 0];
        foreach (DB::filas(
            "SELECT p.estado, COUNT(*) AS total FROM postulacion p JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             WHERE DATE(p.fechaPostulacion) BETWEEN :desde AND :hasta $cat GROUP BY p.estado", $rango + $pc) as $r) {
            $porEstado[$r['estado']] = (int) $r['total'];
        }

        $topFundaciones = DB::filas(
            "SELECT f.razonSocial, COALESCE(SUM(CASE WHEN pa.asistio = 1 THEN pa.horasCumplidas END), 0) AS horas,
                    COUNT(DISTINCT o.idOportunidad) AS oportunidades
             FROM fundacion f JOIN oportunidad o ON o.Fundacion_idFundacion = f.idFundacion
             LEFT JOIN postulacion p ON p.Oportunidad_idOportunidad = o.idOportunidad
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
             WHERE o.fechaInicio BETWEEN :desde AND :hasta $cat
             GROUP BY f.idFundacion ORDER BY horas DESC, oportunidades DESC LIMIT 5",
            $rango + $pc
        );

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'categoria' => $categoria,
            'indicadores' => $indicadores,
            'graficos' => [
                'meses' => [
                    'etiquetas' => $etiquetasMes,
                    'postulaciones' => array_column($meses, 'postulaciones'),
                    'oportunidades' => array_column($meses, 'oportunidades'),
                ],
                'categorias' => [
                    'etiquetas' => array_column($porCategoria, 'nombre'),
                    'oportunidades' => array_map('intval', array_column($porCategoria, 'oportunidades')),
                    'horas' => array_map('floatval', array_column($porCategoria, 'horas')),
                ],
                'estados' => [
                    'etiquetas' => ['Pendientes', 'Aceptadas', 'Rechazadas', 'Canceladas'],
                    'valores' => array_values($porEstado),
                ],
            ],
            'top_fundaciones' => $topFundaciones,
        ];
    }
}
