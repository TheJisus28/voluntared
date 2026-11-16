<?php

namespace App\Services;

use App\Core\ReglaNegocioException;
use App\Models\Conversacion;
use App\Models\Notificacion;
use App\Models\Oportunidad;
use App\Models\Postulacion;

/** RF10 (mensajería): conversaciones entre voluntarios y fundaciones. */
final class ChatService
{
    public const MAX_CARACTERES = 2000;

    /** Abre (o reutiliza) la conversación entre el usuario actual y la otra parte. */
    public static function iniciar(array $usuario, ?int $idOportunidad, ?int $idVoluntario): int
    {
        if ($usuario['rol'] === 'voluntario') {
            $op = $idOportunidad ? Oportunidad::porId($idOportunidad) : null;
            if (!$op || $op['estadoVerificacion'] !== 'verificada' || !$op['visible']) {
                throw new ReglaNegocioException('La oportunidad no está disponible.');
            }
            return Conversacion::buscarOCrear((int) $usuario['idUsuario'], (int) $op['fundacionUsuarioId'], $idOportunidad);
        }
        if ($usuario['rol'] === 'fundacion') {
            if (!$idVoluntario || !Postulacion::hayRelacion((int) $usuario['idUsuario'], $idVoluntario)) {
                throw new ReglaNegocioException('Solo puedes escribir a voluntarios que se hayan postulado a tus oportunidades.');
            }
            if ($idOportunidad) {
                $op = Oportunidad::porId($idOportunidad);
                if (!$op || (int) $op['fundacionUsuarioId'] !== (int) $usuario['idUsuario']) {
                    $idOportunidad = null;
                }
            }
            return Conversacion::buscarOCrear($idVoluntario, (int) $usuario['idUsuario'], $idOportunidad);
        }
        throw new ReglaNegocioException('Tu rol no puede iniciar conversaciones.');
    }

    public static function enviar(int $emisorId, int $idConversacion, string $contenido): array
    {
        $conv = Conversacion::paraUsuario($idConversacion, $emisorId);
        if (!$conv) {
            throw new ReglaNegocioException('Conversación no encontrada.');
        }
        $contenido = trim($contenido);
        if ($contenido === '') {
            throw new ReglaNegocioException('Escribe un mensaje antes de enviarlo.');
        }
        if (mb_strlen($contenido) > self::MAX_CARACTERES) {
            throw new ReglaNegocioException('El mensaje no puede superar ' . self::MAX_CARACTERES . ' caracteres.');
        }
        $mensaje = Conversacion::enviar($idConversacion, $emisorId, $contenido);

        $receptor = (int) $conv['voluntario_id'] === $emisorId ? (int) $conv['fundacion_id'] : (int) $conv['voluntario_id'];
        $nombreEmisor = (int) $conv['voluntario_id'] === $emisorId
            ? trim($conv['voluntarioNombre'] . ' ' . $conv['voluntarioApellido'])
            : $conv['razonSocial'];
        $enlace = "/mensajes/$idConversacion";
        // Un solo aviso por conversación mientras el receptor no lo lea, para no saturarlo.
        if (!Notificacion::hayPendienteConEnlace($receptor, $enlace)) {
            NotificacionService::notificar($receptor, 'mensaje', "Nuevo mensaje de $nombreEmisor", recortar($contenido, 120), $enlace, false);
        }
        return $mensaje;
    }
}
