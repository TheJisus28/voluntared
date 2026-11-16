<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;

/**
 * RF15: reportes de oportunidades, voluntarios, postulaciones y actividades.
 * Cada reporte devuelve un arreglo con título, columnas, filas y un resumen,
 * que se muestra en pantalla o se exporta a PDF / Excel con Exportador.
 */
final class ReporteService
{
    public const TIPOS_FUNDACION = [
        'actividades' => 'Actividades publicadas',
        'postulaciones' => 'Postulaciones recibidas',
        'voluntarios' => 'Voluntarios participantes',
    ];

    public const TIPOS_ADMIN = [
        'usuarios' => 'Usuarios registrados',
        'fundaciones' => 'Fundaciones',
        'oportunidades' => 'Oportunidades',
        'postulaciones' => 'Postulaciones',
        'participacion' => 'Participación y horas',
    ];

    /** Genera un reporte. Si $idFundacion no es null, se limita a los datos de esa fundación. */
    public static function generar(string $tipo, array $filtros, ?int $idFundacion = null): array
    {
        $tipos = $idFundacion ? self::TIPOS_FUNDACION : self::TIPOS_ADMIN;
        if (!isset($tipos[$tipo])) {
            throw new ReglaNegocioException('Tipo de reporte no válido.');
        }
        $f = self::normalizar($filtros);
        $reporte = match ($tipo) {
            'actividades', 'oportunidades' => self::oportunidades($f, $idFundacion),
            'postulaciones' => self::postulaciones($f, $idFundacion),
            'voluntarios', 'participacion' => self::participacion($f, $idFundacion),
            'usuarios' => self::usuarios($f),
            'fundaciones' => self::fundaciones($f),
        };
        $reporte['titulo'] = $tipos[$tipo];
        $reporte['tipo'] = $tipo;
        $reporte['filtros'] = self::describirFiltros($f);
        $reporte['generado'] = date('Y-m-d H:i');
        return $reporte;
    }

    private static function normalizar(array $f): array
    {
        $fecha = static fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        return [
            'desde' => $fecha($f['desde'] ?? null),
            'hasta' => $fecha($f['hasta'] ?? null),
            'estado' => preg_match('/^[a-z_]{3,15}$/', (string) ($f['estado'] ?? '')) ? $f['estado'] : null,
            'categoria' => (int) ($f['categoria'] ?? 0) ?: null,
            'oportunidad' => (int) ($f['oportunidad'] ?? 0) ?: null,
            'rol' => in_array($f['rol'] ?? '', ['voluntario', 'fundacion', 'administrador'], true) ? $f['rol'] : null,
        ];
    }

    private static function rangoFechas(string $columna, array $f, array &$where, array &$params): void
    {
        if ($f['desde']) {
            $where[] = "DATE($columna) >= :desde";
            $params['desde'] = $f['desde'];
        }
        if ($f['hasta']) {
            $where[] = "DATE($columna) <= :hasta";
            $params['hasta'] = $f['hasta'];
        }
    }

    private static function oportunidades(array $f, ?int $idFundacion): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($idFundacion) {
            $where[] = 'o.Fundacion_idFundacion = :fundacion';
            $params['fundacion'] = $idFundacion;
        }
        if ($f['estado']) {
            $where[] = 'o.estado = :estado';
            $params['estado'] = $f['estado'];
        }
        if ($f['categoria']) {
            $where[] = 'o.Categoria_idCategoria = :categoria';
            $params['categoria'] = $f['categoria'];
        }
        self::rangoFechas('o.fechaInicio', $f, $where, $params);
        $filas = DB::filas(
            "SELECT o.titulo, f.razonSocial, c.nombre AS categoria, o.ciudad, o.modalidad, o.fechaInicio, o.fechaFin, o.estado,
                    o.cuposTotales, o.cuposDisponibles,
                    COUNT(DISTINCT p.idPostulacion) AS postulaciones,
                    COUNT(DISTINCT CASE WHEN p.estado = 'aceptada' THEN p.idPostulacion END) AS aceptadas,
                    COUNT(DISTINCT CASE WHEN pa.asistio = 1 THEN pa.idParticipacion END) AS asistentes,
                    COALESCE(SUM(CASE WHEN pa.asistio = 1 THEN pa.horasCumplidas END), 0) AS horas
             FROM oportunidad o
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             JOIN categoria c ON c.idCategoria = o.Categoria_idCategoria
             LEFT JOIN postulacion p ON p.Oportunidad_idOportunidad = o.idOportunidad
             LEFT JOIN participacion pa ON pa.Postulacion_idPostulacion = p.idPostulacion
             WHERE " . implode(' AND ', $where) . '
             GROUP BY o.idOportunidad ORDER BY o.fechaInicio DESC',
            $params
        );
        $columnas = ['Actividad', 'Categoría', 'Ciudad', 'Modalidad', 'Fechas', 'Estado', 'Cupos', 'Postul.', 'Acept.', 'Asist.', 'Horas'];
        $anchos = [52, 28, 22, 20, 36, 20, 16, 16, 15, 15, 17];
        if (!$idFundacion) {
            array_splice($columnas, 1, 0, ['Fundación']);
            array_splice($anchos, 1, 0, [36]);
            $anchos[0] = 44;
            $anchos[4] = 18;
        }
        $datos = [];
        $tot = ['postulaciones' => 0, 'aceptadas' => 0, 'asistentes' => 0, 'horas' => 0.0];
        foreach ($filas as $r) {
            $fila = [
                $r['titulo'], $r['categoria'], $r['ciudad'], etiqueta('modalidad', $r['modalidad']),
                fecha_corta($r['fechaInicio']) . ' – ' . fecha_corta($r['fechaFin']), etiqueta('estado_oportunidad', $r['estado']),
                ($r['cuposTotales'] - $r['cuposDisponibles']) . '/' . $r['cuposTotales'],
                (int) $r['postulaciones'], (int) $r['aceptadas'], (int) $r['asistentes'], (float) $r['horas'],
            ];
            if (!$idFundacion) {
                array_splice($fila, 1, 0, [$r['razonSocial']]);
            }
            $datos[] = $fila;
            foreach ($tot as $k => $_) {
                $tot[$k] += $r[$k];
            }
        }
        return [
            'columnas' => $columnas,
            'anchos' => $anchos,
            'filas' => $datos,
            'resumen' => [
                'Actividades' => count($datos),
                'Postulaciones' => $tot['postulaciones'],
                'Aceptadas' => $tot['aceptadas'],
                'Asistentes' => $tot['asistentes'],
                'Horas de voluntariado' => horas($tot['horas']),
            ],
        ];
    }

    private static function postulaciones(array $f, ?int $idFundacion): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($idFundacion) {
            $where[] = 'o.Fundacion_idFundacion = :fundacion';
            $params['fundacion'] = $idFundacion;
        }
        if ($f['estado']) {
            $where[] = 'p.estado = :estado';
            $params['estado'] = $f['estado'];
        }
        if ($f['oportunidad']) {
            $where[] = 'o.idOportunidad = :oportunidad';
            $params['oportunidad'] = $f['oportunidad'];
        }
        if ($f['categoria']) {
            $where[] = 'o.Categoria_idCategoria = :categoria';
            $params['categoria'] = $f['categoria'];
        }
        self::rangoFechas('p.fechaPostulacion', $f, $where, $params);
        $filas = DB::filas(
            'SELECT CONCAT(u.nombre, " ", COALESCE(u.apellido, "")) AS voluntario, u.email, o.titulo, f.razonSocial,
                    p.fechaPostulacion, p.estado, p.fechaRespuesta
             FROM postulacion p
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             WHERE ' . implode(' AND ', $where) . ' ORDER BY p.fechaPostulacion DESC',
            $params
        );
        $conteo = ['pendiente' => 0, 'aceptada' => 0, 'rechazada' => 0, 'cancelada' => 0];
        $datos = [];
        foreach ($filas as $r) {
            $conteo[$r['estado']]++;
            $fila = [$r['voluntario'], $r['email'], $r['titulo'], fecha_corta($r['fechaPostulacion']),
                etiqueta('estado_postulacion', $r['estado']), fecha_corta($r['fechaRespuesta'])];
            if (!$idFundacion) {
                array_splice($fila, 3, 0, [$r['razonSocial']]);
            }
            $datos[] = $fila;
        }
        $columnas = ['Voluntario', 'Correo', 'Actividad', 'Fecha', 'Estado', 'Respuesta'];
        $anchos = [48, 58, 78, 30, 28, 30];
        if (!$idFundacion) {
            array_splice($columnas, 3, 0, ['Fundación']);
            $anchos = [40, 50, 62, 44, 26, 24, 26];
        }
        $total = count($datos);
        return [
            'columnas' => $columnas,
            'anchos' => $anchos,
            'filas' => $datos,
            'resumen' => [
                'Postulaciones' => $total,
                'Pendientes' => $conteo['pendiente'],
                'Aceptadas' => $conteo['aceptada'],
                'Rechazadas' => $conteo['rechazada'],
                'Canceladas' => $conteo['cancelada'],
                'Tasa de aceptación' => $total ? round($conteo['aceptada'] * 100 / $total) . ' %' : '—',
            ],
        ];
    }

    private static function participacion(array $f, ?int $idFundacion): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($idFundacion) {
            $where[] = 'o.Fundacion_idFundacion = :fundacion';
            $params['fundacion'] = $idFundacion;
        }
        if ($f['oportunidad']) {
            $where[] = 'o.idOportunidad = :oportunidad';
            $params['oportunidad'] = $f['oportunidad'];
        }
        if ($f['categoria']) {
            $where[] = 'o.Categoria_idCategoria = :categoria';
            $params['categoria'] = $f['categoria'];
        }
        self::rangoFechas('o.fechaInicio', $f, $where, $params);
        $filas = DB::filas(
            'SELECT CONCAT(u.nombre, " ", COALESCE(u.apellido, "")) AS voluntario, u.email, o.titulo, f.razonSocial,
                    o.fechaInicio, pa.asistio, pa.horasCumplidas, pa.observacion,
                    (SELECT e.calificacion FROM evaluacion e WHERE e.Participacion_idParticipacion = pa.idParticipacion
                       AND e.evaluado_id = u.idUsuario) AS calificacion
             FROM participacion pa
             JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
             JOIN usuario u ON u.idUsuario = p.Usuario_idUsuario
             JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
             JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             WHERE ' . implode(' AND ', $where) . ' ORDER BY o.fechaInicio DESC, voluntario',
            $params
        );
        $datos = [];
        $horasTotal = 0.0;
        $asistentes = [];
        foreach ($filas as $r) {
            if ($r['asistio']) {
                $horasTotal += (float) $r['horasCumplidas'];
                $asistentes[$r['email']] = true;
            }
            $fila = [$r['voluntario'], $r['titulo'], fecha_corta($r['fechaInicio']), $r['asistio'] ? 'Sí' : 'No',
                (float) $r['horasCumplidas'], $r['calificacion'] ? $r['calificacion'] . '/5' : '—', (string) $r['observacion']];
            if (!$idFundacion) {
                array_splice($fila, 2, 0, [$r['razonSocial']]);
            }
            $datos[] = $fila;
        }
        $columnas = ['Voluntario', 'Actividad', 'Fecha', 'Asistió', 'Horas', 'Calif.', 'Observación'];
        $anchos = [46, 70, 28, 18, 18, 18, 74];
        if (!$idFundacion) {
            array_splice($columnas, 2, 0, ['Fundación']);
            $anchos = [42, 58, 42, 26, 16, 16, 16, 56];
        }
        return [
            'columnas' => $columnas,
            'anchos' => $anchos,
            'filas' => $datos,
            'resumen' => [
                'Registros' => count($datos),
                'Voluntarios que asistieron' => count($asistentes),
                'Horas de voluntariado' => horas($horasTotal),
            ],
        ];
    }

    private static function usuarios(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($f['rol']) {
            $where[] = 'r.nombre = :rol';
            $params['rol'] = $f['rol'];
        }
        if ($f['estado'] === 'activo' || $f['estado'] === 'suspendido') {
            $where[] = 'u.activo = :activo';
            $params['activo'] = $f['estado'] === 'activo' ? 1 : 0;
        }
        self::rangoFechas('u.fechaCreacion', $f, $where, $params);
        $filas = DB::filas(
            'SELECT u.nombre, u.apellido, u.email, r.nombre AS rol, u.activo, u.emailVerificado, u.fechaCreacion,
                    COALESCE(pv.ciudad, fu.ciudad) AS ciudad, fu.razonSocial
             FROM usuario u JOIN rol r ON r.idRol = u.Rol_idRol
             LEFT JOIN perfil_voluntario pv ON pv.Usuario_idUsuario = u.idUsuario
             LEFT JOIN fundacion fu ON fu.Usuario_idUsuario = u.idUsuario
             WHERE ' . implode(' AND ', $where) . ' ORDER BY u.fechaCreacion DESC',
            $params
        );
        $roles = ['voluntario' => 0, 'fundacion' => 0, 'administrador' => 0];
        $datos = [];
        foreach ($filas as $r) {
            $roles[$r['rol']]++;
            $nombre = trim($r['nombre'] . ' ' . $r['apellido']) . ($r['razonSocial'] ? " ({$r['razonSocial']})" : '');
            $datos[] = [$nombre, $r['email'], etiqueta('rol', $r['rol']), (string) $r['ciudad'],
                $r['activo'] ? 'Activo' : 'Suspendido', $r['emailVerificado'] ? 'Sí' : 'No', fecha_corta($r['fechaCreacion'])];
        }
        return [
            'columnas' => ['Nombre', 'Correo', 'Rol', 'Ciudad', 'Estado', 'Correo verificado', 'Registro'],
            'anchos' => [70, 62, 28, 30, 24, 28, 30],
            'filas' => $datos,
            'resumen' => [
                'Usuarios' => count($datos),
                'Voluntarios' => $roles['voluntario'],
                'Fundaciones' => $roles['fundacion'],
                'Administradores' => $roles['administrador'],
            ],
        ];
    }

    private static function fundaciones(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (in_array($f['estado'], ['pendiente', 'verificada', 'rechazada'], true)) {
            $where[] = 'f.estadoVerificacion = :estado';
            $params['estado'] = $f['estado'];
        }
        self::rangoFechas('u.fechaCreacion', $f, $where, $params);
        $filas = DB::filas(
            "SELECT f.razonSocial, f.nit, f.ciudad, f.estadoVerificacion, u.email, u.fechaCreacion,
                    (SELECT COUNT(*) FROM oportunidad o WHERE o.Fundacion_idFundacion = f.idFundacion) AS oportunidades,
                    (SELECT COALESCE(SUM(pa.horasCumplidas), 0) FROM participacion pa
                       JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
                       JOIN oportunidad o ON o.idOportunidad = p.Oportunidad_idOportunidad
                       WHERE o.Fundacion_idFundacion = f.idFundacion AND pa.asistio = 1) AS horas,
                    (SELECT ROUND(AVG(e.calificacion), 1) FROM evaluacion e WHERE e.evaluado_id = u.idUsuario AND e.visible = 1) AS promedio
             FROM fundacion f JOIN usuario u ON u.idUsuario = f.Usuario_idUsuario
             WHERE " . implode(' AND ', $where) . ' ORDER BY f.razonSocial',
            $params
        );
        $datos = [];
        foreach ($filas as $r) {
            $datos[] = [$r['razonSocial'], $r['nit'], $r['ciudad'], $r['email'], etiqueta('verificacion', $r['estadoVerificacion']),
                (int) $r['oportunidades'], (float) $r['horas'], $r['promedio'] !== null ? (float) $r['promedio'] : '—', fecha_corta($r['fechaCreacion'])];
        }
        return [
            'columnas' => ['Razón social', 'NIT', 'Ciudad', 'Correo', 'Verificación', 'Oport.', 'Horas', 'Calif.', 'Registro'],
            'anchos' => [62, 28, 26, 56, 26, 16, 18, 16, 26],
            'filas' => $datos,
            'resumen' => ['Fundaciones' => count($datos)],
        ];
    }

    private static function describirFiltros(array $f): array
    {
        $txt = [];
        if ($f['desde']) {
            $txt[] = 'Desde ' . fecha($f['desde']);
        }
        if ($f['hasta']) {
            $txt[] = 'Hasta ' . fecha($f['hasta']);
        }
        if ($f['estado']) {
            $txt[] = 'Estado: ' . ucfirst($f['estado']);
        }
        if ($f['categoria']) {
            $txt[] = 'Categoría: ' . DB::valor('SELECT nombre FROM categoria WHERE idCategoria = ?', [$f['categoria']]);
        }
        if ($f['oportunidad']) {
            $txt[] = 'Actividad: ' . DB::valor('SELECT titulo FROM oportunidad WHERE idOportunidad = ?', [$f['oportunidad']]);
        }
        if ($f['rol']) {
            $txt[] = 'Rol: ' . etiqueta('rol', $f['rol']);
        }
        return $txt;
    }
}
