<?php

namespace App\Controllers;

use App\Core\HttpException;
use App\Models\Notificacion;

/** Caso de uso Consultar notificaciones. */
final class NotificacionController extends Controller
{
    public function index(): void
    {
        $u = $this->usuario();
        $this->vista('notificaciones/index', [
            'titulo' => 'Notificaciones',
            'notificaciones' => Notificacion::deUsuario((int) $u['idUsuario'], 100),
            'noLeidas' => Notificacion::noLeidas((int) $u['idUsuario']),
        ]);
    }

    /** Marca la notificación como leída y redirige a la sección relacionada. */
    public function abrir(string $id): void
    {
        $u = $this->usuario();
        $n = Notificacion::delUsuario($this->entero($id), (int) $u['idUsuario']);
        if (!$n) {
            throw new HttpException(404);
        }
        Notificacion::marcarLeida((int) $n['idNotificacion'], (int) $u['idUsuario']);
        redirect($n['enlace'] ?: '/notificaciones');
    }

    public function leerTodas(): void
    {
        $n = Notificacion::marcarTodas((int) $this->usuario()['idUsuario']);
        $this->exito($n ? "Marcaste $n notificación(es) como leídas." : 'No tenías notificaciones sin leer.');
        back();
    }
}
