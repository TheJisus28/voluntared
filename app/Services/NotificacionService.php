<?php

namespace App\Services;

use App\Core\Database as DB;
use App\Core\Mailer;
use App\Models\Notificacion;

/**
 * RF11: notificaciones automáticas en la plataforma y por correo electrónico.
 */
final class NotificacionService
{
    /**
     * Crea la notificación y, si $correo es true, envía también un correo al usuario.
     * $enlace es una ruta interna (ej. /postulaciones) a la que lleva la notificación.
     */
    public static function notificar(int $idUsuario, string $tipo, string $titulo, string $contenido, ?string $enlace = null, bool $correo = true): int
    {
        $id = Notificacion::crear($idUsuario, $tipo, $titulo, $contenido, $enlace);
        if ($correo) {
            $u = DB::fila('SELECT nombre, apellido, email FROM usuario WHERE idUsuario = ?', [$idUsuario]);
            if ($u) {
                Mailer::enviar($u['email'], trim($u['nombre'] . ' ' . $u['apellido']), $titulo . ' | VoluntaRed', 'notificacion', [
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'enlace' => $enlace,
                ]);
            }
        }
        return $id;
    }

    public static function notificarAdministradores(string $titulo, string $contenido, ?string $enlace = null): void
    {
        foreach (\App\Models\Usuario::idsAdministradores() as $id) {
            self::notificar($id, 'sistema', $titulo, $contenido, $enlace);
        }
    }
}
