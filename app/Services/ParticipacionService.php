<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Evaluacion;
use App\Models\Participacion;

/**
 * RF12: registro de participación (asistencia y horas).
 * RF13: evaluación mutua de 1 a 5 entre voluntario y fundación.
 */
final class ParticipacionService
{
    /**
     * $registros: [idPostulacion => ['asistio' => bool, 'horas' => float, 'observacion' => string]]
     * Devuelve la cantidad de participaciones guardadas.
     */
    public static function registrar(array $oportunidad, int $fundacionUsuarioId, array $registros): int
    {
        if ((int) $oportunidad['fundacionUsuarioId'] !== $fundacionUsuarioId) {
            throw new ReglaNegocioException('Oportunidad no encontrada.');
        }
        if ($oportunidad['estado'] !== 'finalizada') {
            throw new ReglaNegocioException('Primero marca la actividad como finalizada para registrar la participación.');
        }
        $aceptadas = array_map('intval', array_column(DB::filas(
            "SELECT idPostulacion FROM postulacion WHERE Oportunidad_idOportunidad = ? AND estado = 'aceptada'",
            [$oportunidad['idOportunidad']]
        ), 'idPostulacion'));

        $limpios = [];
        foreach ($registros as $idPostulacion => $r) {
            $idPostulacion = (int) $idPostulacion;
            if (!in_array($idPostulacion, $aceptadas, true)) {
                throw new ReglaNegocioException('Solo se registra la participación de voluntarios aceptados.');
            }
            $asistio = !empty($r['asistio']);
            $horasTexto = str_replace(',', '.', trim((string) ($r['horas'] ?? '0')));
            if ($horasTexto === '' || !is_numeric($horasTexto)) {
                throw new ReglaNegocioException('Las horas deben ser un número.');
            }
            $horas = round((float) $horasTexto, 2);
            if ($horas < 0) {
                throw new ReglaNegocioException('Las horas deben ser mayores o iguales a cero.');
            }
            if ($horas > 999.99) {
                throw new ReglaNegocioException('Las horas no pueden superar 999,99.');
            }
            $observacion = trim((string) ($r['observacion'] ?? ''));
            $limpios[$idPostulacion] = [$asistio, $asistio ? $horas : 0.0, $observacion !== '' ? mb_substr($observacion, 0, 300) : null];
        }

        $nuevas = DB::transaccion(function () use ($limpios) {
            $nuevas = [];
            foreach ($limpios as $idPostulacion => [$asistio, $horas, $obs]) {
                [$idPart, $esNueva] = Participacion::guardar($idPostulacion, $asistio, $horas, $obs);
                if ($esNueva) {
                    $nuevas[] = $idPart;
                }
            }
            return $nuevas;
        });

        foreach ($nuevas as $idPart) {
            $p = Participacion::porId($idPart);
            NotificacionService::notificar(
                (int) $p['voluntarioId'],
                'evaluacion',
                'Participación registrada',
                "{$p['razonSocial']} registró tu participación en «{$p['titulo']}». Ya puedes evaluar tu experiencia.",
                '/historial'
            );
        }
        return count($limpios);
    }

    /** Registra la evaluación de quien participó (voluntario o fundación) sobre la otra parte. */
    public static function evaluar(int $evaluadorId, int $idParticipacion, int $calificacion, ?string $comentario): int
    {
        $p = Participacion::porId($idParticipacion);
        if (!$p) {
            throw new ReglaNegocioException('Participación no encontrada.');
        }
        if ($evaluadorId === (int) $p['voluntarioId']) {
            $evaluadoId = (int) $p['fundacionUsuarioId'];
            $enlace = "/fundaciones/{$p['idFundacion']}";
            $quien = trim("{$p['voluntarioNombre']} {$p['voluntarioApellido']}");
        } elseif ($evaluadorId === (int) $p['fundacionUsuarioId']) {
            $evaluadoId = (int) $p['voluntarioId'];
            $enlace = '/historial';
            $quien = $p['razonSocial'];
        } else {
            throw new ReglaNegocioException('No participaste en esta actividad.');
        }
        if ($calificacion < 1 || $calificacion > 5) {
            throw new ReglaNegocioException('La calificación debe estar entre 1 y 5 estrellas.');
        }
        if (Evaluacion::existe($idParticipacion, $evaluadorId)) {
            throw new ReglaNegocioException('Ya evaluaste esta participación.');
        }
        $comentario = $comentario !== null && trim($comentario) !== '' ? mb_substr(trim($comentario), 0, 500) : null;
        $id = Evaluacion::crear($idParticipacion, $evaluadorId, $evaluadoId, $calificacion, $comentario);
        NotificacionService::notificar(
            $evaluadoId,
            'evaluacion',
            'Recibiste una evaluación',
            "$quien te calificó con $calificacion de 5 estrellas por «{$p['titulo']}».",
            $enlace,
            false
        );
        return $id;
    }
}
