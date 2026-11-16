<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\PerfilVoluntario;
use App\Models\Postulacion;

/**
 * RF8 y RF9: postulación del voluntario y gestión de postulaciones por la fundación,
 * con control automático de cupos. Las operaciones bloquean la fila de la
 * oportunidad (SELECT ... FOR UPDATE) para que dos aceptaciones simultáneas
 * no superen los cupos disponibles.
 */
final class PostulacionService
{
    public static function postular(int $idUsuario, int $idOportunidad, ?string $mensaje): int
    {
        if (!PerfilVoluntario::porUsuario($idUsuario)) {
            throw new ReglaNegocioException('Completa tu perfil de voluntario antes de postularte.');
        }
        $mensaje = $mensaje !== null && trim($mensaje) !== '' ? mb_substr(trim($mensaje), 0, 500) : null;

        [$id, $op] = DB::transaccion(function () use ($idUsuario, $idOportunidad, $mensaje) {
            $op = self::bloquearOportunidad($idOportunidad);
            if (!$op || !$op['visible'] || $op['estadoVerificacion'] !== 'verificada') {
                throw new ReglaNegocioException('La oportunidad no existe o no está disponible.');
            }
            if ($op['estado'] !== 'abierta') {
                throw new ReglaNegocioException('Esta oportunidad ya no recibe postulaciones.');
            }
            if ((int) $op['cuposDisponibles'] <= 0) {
                throw new ReglaNegocioException('No quedan cupos disponibles en esta oportunidad.');
            }
            $previa = Postulacion::existente($idUsuario, $idOportunidad);
            if ($previa && $previa['estado'] !== 'cancelada') {
                throw new ReglaNegocioException('Ya te postulaste a esta oportunidad.');
            }
            if ($previa) {
                // Una postulación cancelada por el voluntario puede volver a enviarse.
                DB::actualizar('postulacion', [
                    'estado' => 'pendiente', 'mensaje' => $mensaje,
                    'fechaPostulacion' => date('Y-m-d H:i:s'), 'fechaRespuesta' => null,
                ], 'idPostulacion = :id', ['id' => $previa['idPostulacion']]);
                return [(int) $previa['idPostulacion'], $op];
            }
            $id = DB::insertar('postulacion', [
                'Usuario_idUsuario' => $idUsuario,
                'Oportunidad_idOportunidad' => $idOportunidad,
                'mensaje' => $mensaje,
            ]);
            return [$id, $op];
        });

        $vol = DB::fila('SELECT nombre, apellido FROM usuario WHERE idUsuario = ?', [$idUsuario]);
        NotificacionService::notificar(
            (int) $op['fundacionUsuarioId'],
            'postulacion',
            'Nueva postulación',
            trim("{$vol['nombre']} {$vol['apellido']}") . " se postuló a «{$op['titulo']}».",
            "/fundacion/oportunidades/$idOportunidad/postulaciones"
        );
        return $id;
    }

    /** El voluntario cancela su postulación; si estaba aceptada se libera el cupo. */
    public static function cancelar(int $idUsuario, int $idPostulacion): void
    {
        $p = Postulacion::porId($idPostulacion);
        if (!$p || (int) $p['Usuario_idUsuario'] !== $idUsuario) {
            throw new ReglaNegocioException('Postulación no encontrada.');
        }
        if (!in_array($p['estado'], ['pendiente', 'aceptada'], true)) {
            throw new ReglaNegocioException('Solo puedes cancelar postulaciones pendientes o aceptadas.');
        }
        if (in_array($p['estadoOportunidad'], ['finalizada', 'cancelada'], true)) {
            throw new ReglaNegocioException('La actividad ya terminó o fue cancelada.');
        }

        DB::transaccion(function () use ($p) {
            $op = self::bloquearOportunidad((int) $p['Oportunidad_idOportunidad']);
            DB::actualizar('postulacion', ['estado' => 'cancelada', 'fechaRespuesta' => date('Y-m-d H:i:s')],
                'idPostulacion = :id', ['id' => $p['idPostulacion']]);
            if ($p['estado'] === 'aceptada') {
                $cupos = (int) $op['cuposDisponibles'] + 1;
                $estado = $op['estado'];
                if ($estado === 'cerrada' && (int) $op['cuposDisponibles'] === 0 && $op['fechaFin'] >= date('Y-m-d')) {
                    $estado = 'abierta'; // se había cerrado automáticamente al agotarse los cupos
                }
                DB::actualizar('oportunidad', ['cuposDisponibles' => $cupos, 'estado' => $estado],
                    'idOportunidad = :id', ['id' => $op['idOportunidad']]);
            }
        });

        NotificacionService::notificar(
            (int) $p['fundacionUsuarioId'],
            'postulacion',
            'Postulación cancelada',
            trim("{$p['voluntarioNombre']} {$p['voluntarioApellido']}") . " canceló su postulación a «{$p['titulo']}».",
            "/fundacion/oportunidades/{$p['Oportunidad_idOportunidad']}/postulaciones",
            false
        );
    }

    /** La fundación acepta: descuenta un cupo y cierra la oportunidad si llega a cero. */
    public static function aceptar(int $fundacionUsuarioId, int $idPostulacion): void
    {
        $p = self::postulacionDeFundacion($fundacionUsuarioId, $idPostulacion);
        $cerrada = DB::transaccion(function () use ($p) {
            $op = self::bloquearOportunidad((int) $p['Oportunidad_idOportunidad']);
            if (!in_array($op['estado'], ['abierta', 'cerrada'], true)) {
                throw new ReglaNegocioException('La oportunidad ya no admite cambios en sus postulaciones.');
            }
            if ((int) $op['cuposDisponibles'] <= 0) {
                throw new ReglaNegocioException('No quedan cupos disponibles. Aumenta los cupos o rechaza la postulación.');
            }
            $cupos = (int) $op['cuposDisponibles'] - 1;
            DB::actualizar('postulacion', ['estado' => 'aceptada', 'fechaRespuesta' => date('Y-m-d H:i:s')],
                'idPostulacion = :id', ['id' => $p['idPostulacion']]);
            DB::actualizar('oportunidad', [
                'cuposDisponibles' => $cupos,
                'estado' => $cupos === 0 ? 'cerrada' : $op['estado'],
            ], 'idOportunidad = :id', ['id' => $op['idOportunidad']]);
            return $cupos === 0;
        });

        NotificacionService::notificar(
            (int) $p['Usuario_idUsuario'],
            'respuesta',
            '¡Tu postulación fue aceptada!',
            "{$p['razonSocial']} aceptó tu postulación a «{$p['titulo']}». Revisa los detalles de la actividad.",
            "/postulaciones"
        );
        if ($cerrada) {
            NotificacionService::notificar(
                $fundacionUsuarioId,
                'sistema',
                'Cupos completos',
                "La oportunidad «{$p['titulo']}» completó sus cupos y se cerró automáticamente.",
                "/fundacion/oportunidades/{$p['Oportunidad_idOportunidad']}/postulaciones",
                false
            );
        }
    }

    public static function rechazar(int $fundacionUsuarioId, int $idPostulacion): void
    {
        $p = self::postulacionDeFundacion($fundacionUsuarioId, $idPostulacion);
        DB::actualizar('postulacion', ['estado' => 'rechazada', 'fechaRespuesta' => date('Y-m-d H:i:s')],
            'idPostulacion = :id', ['id' => $p['idPostulacion']]);
        NotificacionService::notificar(
            (int) $p['Usuario_idUsuario'],
            'respuesta',
            'Respuesta a tu postulación',
            "{$p['razonSocial']} no pudo aceptar tu postulación a «{$p['titulo']}». ¡Hay más oportunidades esperándote!",
            "/oportunidades"
        );
    }

    private static function postulacionDeFundacion(int $fundacionUsuarioId, int $idPostulacion): array
    {
        $p = Postulacion::porId($idPostulacion);
        if (!$p || (int) $p['fundacionUsuarioId'] !== $fundacionUsuarioId) {
            throw new ReglaNegocioException('Postulación no encontrada.');
        }
        if ($p['estado'] !== 'pendiente') {
            throw new ReglaNegocioException('Esta postulación ya fue respondida o cancelada.');
        }
        return $p;
    }

    private static function bloquearOportunidad(int $id): ?array
    {
        return DB::fila(
            'SELECT o.*, f.estadoVerificacion, f.Usuario_idUsuario AS fundacionUsuarioId
             FROM oportunidad o JOIN fundacion f ON f.idFundacion = o.Fundacion_idFundacion
             WHERE o.idOportunidad = ? FOR UPDATE',
            [$id]
        );
    }
}
