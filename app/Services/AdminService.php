<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Evaluacion;
use App\Models\Fundacion;
use App\Models\Oportunidad;
use App\Models\Usuario;

/** RF5 y RF14: verificación de fundaciones, gestión de usuarios y moderación de contenido. */
final class AdminService
{
    public static function verificarFundacion(int $idFundacion, string $decision, ?string $motivo): void
    {
        $f = Fundacion::porId($idFundacion);
        if (!$f) {
            throw new ReglaNegocioException('Fundación no encontrada.');
        }
        if (!in_array($decision, ['verificada', 'rechazada'], true)) {
            throw new ReglaNegocioException('Decisión no válida.');
        }
        $motivo = trim((string) $motivo);
        if ($decision === 'rechazada' && mb_strlen($motivo) < 10) {
            throw new ReglaNegocioException('Indica el motivo del rechazo (mínimo 10 caracteres).');
        }
        Fundacion::actualizar($idFundacion, [
            'estadoVerificacion' => $decision,
            'fechaVerificacion' => date('Y-m-d H:i:s'),
            'motivoRechazo' => $decision === 'rechazada' ? mb_substr($motivo, 0, 300) : null,
        ]);
        if ($decision === 'verificada') {
            NotificacionService::notificar((int) $f['Usuario_idUsuario'], 'sistema', '¡Tu fundación fue verificada!',
                "{$f['razonSocial']} ya puede publicar oportunidades de voluntariado en VoluntaRed.", '/fundacion/oportunidades');
        } else {
            NotificacionService::notificar((int) $f['Usuario_idUsuario'], 'sistema', 'Verificación rechazada',
                "La verificación de {$f['razonSocial']} fue rechazada. Motivo: $motivo. Corrige los datos o el documento y vuelve a enviarlos desde tu perfil.",
                '/fundacion/perfil');
        }
    }

    public static function suspender(int $adminId, int $idUsuario, string $motivo): void
    {
        $u = Usuario::buscarConRol($idUsuario);
        if (!$u) {
            throw new ReglaNegocioException('Usuario no encontrado.');
        }
        if ($idUsuario === $adminId || $u['rol'] === 'administrador') {
            throw new ReglaNegocioException('No es posible suspender cuentas de administrador.');
        }
        if (mb_strlen(trim($motivo)) < 10) {
            throw new ReglaNegocioException('Indica el motivo de la suspensión (mínimo 10 caracteres).');
        }
        Usuario::actualizar($idUsuario, ['activo' => 0]);
        NotificacionService::notificar($idUsuario, 'sistema', 'Tu cuenta fue suspendida',
            'El administrador suspendió tu cuenta. Motivo: ' . trim($motivo));
    }

    public static function reactivar(int $idUsuario): void
    {
        $u = Usuario::buscarConRol($idUsuario);
        if (!$u) {
            throw new ReglaNegocioException('Usuario no encontrado.');
        }
        Usuario::actualizar($idUsuario, ['activo' => 1, 'intentosFallidos' => 0, 'bloqueadoHasta' => null]);
        NotificacionService::notificar($idUsuario, 'sistema', 'Tu cuenta fue reactivada', 'Ya puedes volver a iniciar sesión en VoluntaRed.');
    }

    public static function moderarOportunidad(int $id, bool $visible, ?string $motivo = null): void
    {
        $o = Oportunidad::porId($id);
        if (!$o) {
            throw new ReglaNegocioException('Oportunidad no encontrada.');
        }
        Oportunidad::actualizar($id, ['visible' => (int) $visible]);
        $texto = $visible
            ? "La oportunidad «{$o['titulo']}» vuelve a estar visible para los voluntarios."
            : "La oportunidad «{$o['titulo']}» fue retirada de la vista pública por moderación." . ($motivo ? " Motivo: $motivo" : '');
        NotificacionService::notificar((int) $o['fundacionUsuarioId'], 'sistema',
            $visible ? 'Oportunidad restablecida' : 'Oportunidad ocultada', $texto, '/fundacion/oportunidades');
    }

    public static function moderarEvaluacion(int $id, bool $visible, ?string $motivo = null): void
    {
        $e = Evaluacion::porId($id);
        if (!$e) {
            throw new ReglaNegocioException('Evaluación no encontrada.');
        }
        Evaluacion::cambiarVisibilidad($id, $visible);
        NotificacionService::notificar((int) $e['evaluador_id'], 'sistema',
            $visible ? 'Evaluación restablecida' : 'Evaluación ocultada',
            $visible ? 'Una de tus evaluaciones vuelve a estar visible.'
                : 'Una de tus evaluaciones fue retirada por no cumplir las normas de la comunidad.' . ($motivo ? " Motivo: $motivo" : ''),
            null, false);
    }

    /** Indicadores rápidos para el panel del administrador. */
    public static function resumen(): array
    {
        return DB::fila(
            "SELECT
                (SELECT COUNT(*) FROM usuario WHERE Rol_idRol = 1) AS voluntarios,
                (SELECT COUNT(*) FROM usuario WHERE Rol_idRol = 2) AS fundaciones,
                (SELECT COUNT(*) FROM fundacion WHERE estadoVerificacion = 'pendiente') AS pendientes,
                (SELECT COUNT(*) FROM oportunidad WHERE estado = 'abierta' AND visible = 1) AS abiertas,
                (SELECT COUNT(*) FROM postulacion) AS postulaciones,
                (SELECT COALESCE(SUM(horasCumplidas), 0) FROM participacion WHERE asistio = 1) AS horas,
                (SELECT COUNT(*) FROM usuario WHERE activo = 0) AS suspendidos"
        );
    }
}
