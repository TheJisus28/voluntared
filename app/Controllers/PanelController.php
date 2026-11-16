<?php

namespace App\Controllers;

use App\Core\Database as DB;
use App\Models\Notificacion;
use App\Models\Oportunidad;
use App\Models\Participacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\AdminService;

/** Panel principal según el rol del usuario autenticado. */
final class PanelController extends Controller
{
    public function index(): void
    {
        $u = $this->usuario();
        $id = (int) $u['idUsuario'];
        $notificaciones = Notificacion::deUsuario($id, 5);

        if ($u['rol'] === 'voluntario') {
            [$recomendadas] = Oportunidad::buscar(['excluir_postulado' => $id], 1, 3, $u['perfil']);
            $this->vista('voluntario/panel', [
                'titulo' => 'Mi panel',
                'recomendadas' => $recomendadas,
                'conteo' => Postulacion::contarPorEstadoVoluntario($id),
                'proximas' => array_slice(array_filter(
                    Postulacion::deVoluntario($id, 'aceptada'),
                    static fn ($p) => $p['fechaFin'] >= date('Y-m-d') && $p['estadoOportunidad'] !== 'cancelada'
                ), 0, 4),
                'horas' => Participacion::totalHoras($id),
                'calificacion' => Usuario::promedio($id),
                'notificaciones' => $notificaciones,
            ]);
            return;
        }

        if ($u['rol'] === 'fundacion') {
            $f = $u['fundacion'];
            $resumen = DB::fila(
                "SELECT COUNT(*) AS total,
                        SUM(o.estado = 'abierta') AS abiertas,
                        (SELECT COUNT(*) FROM postulacion p JOIN oportunidad o2 ON o2.idOportunidad = p.Oportunidad_idOportunidad
                           WHERE o2.Fundacion_idFundacion = :f1 AND p.estado = 'pendiente') AS pendientes,
                        (SELECT COALESCE(SUM(pa.horasCumplidas), 0) FROM participacion pa
                           JOIN postulacion p ON p.idPostulacion = pa.Postulacion_idPostulacion
                           JOIN oportunidad o3 ON o3.idOportunidad = p.Oportunidad_idOportunidad
                           WHERE o3.Fundacion_idFundacion = :f2 AND pa.asistio = 1) AS horas
                 FROM oportunidad o WHERE o.Fundacion_idFundacion = :f3",
                ['f1' => $f['idFundacion'], 'f2' => $f['idFundacion'], 'f3' => $f['idFundacion']]
            );
            $this->vista('fundacion/panel', [
                'titulo' => 'Panel de la fundación',
                'f' => $f,
                'resumen' => $resumen,
                'pendientes' => Postulacion::pendientesDeFundacion((int) $f['idFundacion']),
                'activas' => array_slice(Oportunidad::deFundacion((int) $f['idFundacion'], 'abierta'), 0, 4),
                'porRegistrar' => array_filter(
                    Oportunidad::deFundacion((int) $f['idFundacion'], 'finalizada'),
                    static fn ($o) => (int) $o['aceptadas'] > (int) $o['participaciones']
                ),
                'calificacion' => Usuario::promedio($id),
                'notificaciones' => $notificaciones,
            ]);
            return;
        }

        $this->vista('admin/panel', [
            'titulo' => 'Panel de administración',
            'resumen' => AdminService::resumen(),
            'pendientes' => \App\Models\Fundacion::porEstado('pendiente'),
            'notificaciones' => $notificaciones,
        ]);
    }
}
