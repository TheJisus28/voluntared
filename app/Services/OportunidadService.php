<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Core\Validator;
use App\Models\Catalogo;
use App\Models\Oportunidad;
use App\Models\PerfilVoluntario;
use App\Models\Postulacion;

/**
 * RF6: crear, modificar, cerrar, cancelar y finalizar oportunidades.
 * Solo las fundaciones verificadas pueden publicar.
 */
final class OportunidadService
{
    public const REGLAS = [
        'titulo' => 'requerido|min:5|max:150',
        'descripcion' => 'requerido|min:20|max:5000',
        'requisitos' => 'max:500',
        'categoria' => 'requerido|entero',
        'modalidad' => 'requerido|en:presencial,virtual,mixta',
        'ubicacion' => 'max:200',
        'ciudad' => 'requerido|max:80',
        'fechaInicio' => 'requerido|fecha',
        'fechaFin' => 'requerido|fecha|despues_o_igual:fechaInicio',
        'horaInicio' => 'requerido|hora',
        'horaFin' => 'requerido|hora|hora_despues:horaInicio',
        'cuposTotales' => 'requerido|entero|min_valor:1|max_valor:1000',
    ];

    /** Valida los datos del formulario. $nueva = true exige que la fecha de inicio no sea pasada. */
    public static function validar(array $datos, bool $nueva = true): array
    {
        $reglas = self::REGLAS;
        if ($nueva) {
            $reglas['fechaInicio'] .= '|desde_hoy';
        }
        $errores = Validator::validar($datos, $reglas);
        if (!isset($errores['categoria']) && !Catalogo::existe('categoria', (int) $datos['categoria'])) {
            $errores['categoria'] = 'Selecciona una categoría válida.';
        }
        if (in_array($datos['modalidad'] ?? '', ['presencial', 'mixta'], true) && trim($datos['ubicacion'] ?? '') === '') {
            $errores['ubicacion'] = 'La ubicación es obligatoria para actividades presenciales o mixtas.';
        }
        foreach ((array) ($datos['habilidades'] ?? []) as $id) {
            if (!Catalogo::existe('habilidad', (int) $id)) {
                $errores['habilidades'] = 'Alguna de las habilidades seleccionadas no existe.';
                break;
            }
        }
        return $errores;
    }

    private static function columnas(array $datos): array
    {
        return [
            'titulo' => trim($datos['titulo']),
            'descripcion' => trim($datos['descripcion']),
            'requisitos' => trim($datos['requisitos'] ?? '') ?: null,
            'Categoria_idCategoria' => (int) $datos['categoria'],
            'modalidad' => $datos['modalidad'],
            'ubicacion' => trim($datos['ubicacion'] ?? '') ?: null,
            'ciudad' => trim($datos['ciudad']),
            'fechaInicio' => $datos['fechaInicio'],
            'fechaFin' => $datos['fechaFin'],
            'horaInicio' => $datos['horaInicio'],
            'horaFin' => $datos['horaFin'],
        ];
    }

    /** Publica la oportunidad con estado "abierta" y notifica a los voluntarios con intereses afines. */
    public static function crear(array $fundacion, array $datos): int
    {
        if (($fundacion['estadoVerificacion'] ?? '') !== 'verificada') {
            throw new ReglaNegocioException('Solo las fundaciones verificadas pueden publicar oportunidades.');
        }
        $id = DB::transaccion(function () use ($fundacion, $datos) {
            $cupos = (int) $datos['cuposTotales'];
            $id = Oportunidad::crear(self::columnas($datos) + [
                'Fundacion_idFundacion' => (int) $fundacion['idFundacion'],
                'cuposTotales' => $cupos,
                'cuposDisponibles' => $cupos,
                'estado' => 'abierta',
            ]);
            Oportunidad::sincronizarHabilidades($id, (array) ($datos['habilidades'] ?? []));
            return $id;
        });

        foreach (PerfilVoluntario::interesadosEnCategoria((int) $datos['categoria']) as $v) {
            NotificacionService::notificar(
                (int) $v['idUsuario'],
                'oportunidad',
                'Nueva oportunidad para ti',
                "{$fundacion['razonSocial']} publicó «{$datos['titulo']}» en {$datos['ciudad']}, afín a tus intereses.",
                "/oportunidades/$id"
            );
        }
        return $id;
    }

    /** Guarda cambios; recalcula cupos y avisa a los postulados si cambian fecha, horario o lugar. */
    public static function actualizar(array $oportunidad, array $datos): void
    {
        if (in_array($oportunidad['estado'], ['finalizada', 'cancelada'], true)) {
            throw new ReglaNegocioException('No se puede modificar una oportunidad finalizada o cancelada.');
        }
        $id = (int) $oportunidad['idOportunidad'];
        $aceptadas = (int) DB::valor(
            "SELECT COUNT(*) FROM postulacion WHERE Oportunidad_idOportunidad = ? AND estado = 'aceptada'",
            [$id]
        );
        $cupos = (int) $datos['cuposTotales'];
        if ($cupos < $aceptadas) {
            throw new ReglaNegocioException("Ya hay $aceptadas voluntarios aceptados; los cupos no pueden ser menos que eso.");
        }
        $nuevos = self::columnas($datos);
        $cambioLogistico = $nuevos['fechaInicio'] !== $oportunidad['fechaInicio']
            || $nuevos['fechaFin'] !== $oportunidad['fechaFin']
            || substr($nuevos['horaInicio'], 0, 5) !== substr($oportunidad['horaInicio'], 0, 5)
            || substr($nuevos['horaFin'], 0, 5) !== substr($oportunidad['horaFin'], 0, 5)
            || (string) $nuevos['ubicacion'] !== (string) $oportunidad['ubicacion']
            || $nuevos['ciudad'] !== $oportunidad['ciudad'];

        $disponibles = $cupos - $aceptadas;
        $estado = $oportunidad['estado'];
        if ($estado === 'cerrada' && (int) $oportunidad['cuposDisponibles'] === 0 && $disponibles > 0) {
            $estado = 'abierta'; // se había cerrado sola por falta de cupos
        } elseif ($estado === 'abierta' && $disponibles === 0) {
            $estado = 'cerrada';
        }

        DB::transaccion(function () use ($id, $nuevos, $cupos, $disponibles, $estado, $datos) {
            Oportunidad::actualizar($id, $nuevos + [
                'cuposTotales' => $cupos,
                'cuposDisponibles' => $disponibles,
                'estado' => $estado,
            ]);
            Oportunidad::sincronizarHabilidades($id, (array) ($datos['habilidades'] ?? []));
        });

        if ($cambioLogistico) {
            self::avisarPostulados(
                $id,
                'Cambios en una actividad',
                "La actividad «{$nuevos['titulo']}» cambió su fecha, horario o lugar. Revisa los nuevos detalles.",
                ['pendiente', 'aceptada']
            );
        }
    }

    public static function cambiarEstado(array $oportunidad, string $accion): void
    {
        $id = (int) $oportunidad['idOportunidad'];
        $titulo = $oportunidad['titulo'];
        switch ($accion) {
            case 'cerrar':
                if ($oportunidad['estado'] !== 'abierta') {
                    throw new ReglaNegocioException('Solo se pueden cerrar oportunidades abiertas.');
                }
                Oportunidad::actualizar($id, ['estado' => 'cerrada']);
                self::avisarPostulados($id, 'Convocatoria cerrada', "La fundación cerró la convocatoria de «{$titulo}».", ['pendiente']);
                break;

            case 'reabrir':
                if ($oportunidad['estado'] !== 'cerrada') {
                    throw new ReglaNegocioException('Solo se pueden reabrir oportunidades cerradas.');
                }
                if ((int) $oportunidad['cuposDisponibles'] <= 0) {
                    throw new ReglaNegocioException('No hay cupos disponibles. Aumenta los cupos editando la oportunidad.');
                }
                if ($oportunidad['fechaFin'] < date('Y-m-d')) {
                    throw new ReglaNegocioException('La actividad ya terminó; no se puede reabrir.');
                }
                Oportunidad::actualizar($id, ['estado' => 'abierta']);
                break;

            case 'cancelar':
                if (!in_array($oportunidad['estado'], ['abierta', 'cerrada'], true)) {
                    throw new ReglaNegocioException('Esta oportunidad ya no se puede cancelar.');
                }
                $afectados = array_filter(
                    Postulacion::deOportunidad($id),
                    static fn ($p) => in_array($p['estado'], ['pendiente', 'aceptada'], true)
                );
                DB::transaccion(function () use ($id) {
                    Oportunidad::actualizar($id, ['estado' => 'cancelada']);
                    DB::consulta(
                        "UPDATE postulacion SET estado = 'cancelada', fechaRespuesta = NOW()
                         WHERE Oportunidad_idOportunidad = ? AND estado = 'pendiente'",
                        [$id]
                    );
                });
                foreach ($afectados as $p) {
                    NotificacionService::notificar((int) $p['Usuario_idUsuario'], 'oportunidad', 'Actividad cancelada',
                        "La fundación canceló la actividad «{$titulo}».", "/oportunidades/$id");
                }
                break;

            case 'finalizar':
                if (!in_array($oportunidad['estado'], ['abierta', 'cerrada'], true)) {
                    throw new ReglaNegocioException('Solo se pueden finalizar oportunidades abiertas o cerradas.');
                }
                if ($oportunidad['fechaInicio'] > date('Y-m-d')) {
                    throw new ReglaNegocioException('La actividad aún no ha comenzado; no se puede finalizar.');
                }
                Oportunidad::actualizar($id, ['estado' => 'finalizada']);
                break;

            default:
                throw new ReglaNegocioException('Acción no válida.');
        }
    }

    private static function avisarPostulados(int $idOportunidad, string $titulo, string $mensaje, array $estados): void
    {
        foreach (Postulacion::deOportunidad($idOportunidad) as $p) {
            if (in_array($p['estado'], $estados, true)) {
                NotificacionService::notificar((int) $p['Usuario_idUsuario'], 'oportunidad', $titulo, $mensaje, "/oportunidades/$idOportunidad");
            }
        }
    }
}
