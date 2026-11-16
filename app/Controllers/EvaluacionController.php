<?php

namespace App\Controllers;

use App\Core\HttpException;
use App\Models\Evaluacion;
use App\Models\Participacion;
use App\Services\ParticipacionService;

/** Casos de uso Evaluar fundación y Evaluar voluntario (RF13). */
final class EvaluacionController extends Controller
{
    public function formulario(string $id): void
    {
        $u = $this->usuario();
        $p = $this->participacion($id, (int) $u['idUsuario']);
        $esVoluntario = (int) $p['voluntarioId'] === (int) $u['idUsuario'];
        if (Evaluacion::existe((int) $p['idParticipacion'], (int) $u['idUsuario'])) {
            \App\Core\Flash::set('info', 'Ya evaluaste esta participación.');
            redirect($esVoluntario ? '/historial' : "/fundacion/oportunidades/{$p['Oportunidad_idOportunidad']}/participacion");
        }
        $this->vista('evaluacion/formulario', [
            'titulo' => $esVoluntario ? 'Evaluar fundación' : 'Evaluar voluntario',
            'p' => $p,
            'esVoluntario' => $esVoluntario,
            'evaluado' => $esVoluntario ? $p['razonSocial'] : trim($p['voluntarioNombre'] . ' ' . $p['voluntarioApellido']),
        ]);
    }

    public function guardar(string $id): void
    {
        $u = $this->usuario();
        $p = $this->participacion($id, (int) $u['idUsuario']);
        $calificacion = (int) ($_POST['calificacion'] ?? 0);
        $comentario = (string) ($_POST['comentario'] ?? '');
        if ($calificacion < 1 || $calificacion > 5 || mb_strlen($comentario) > 500) {
            $this->volverConErrores([
                'calificacion' => $calificacion < 1 || $calificacion > 5 ? 'Selecciona de 1 a 5 estrellas.' : null,
                'comentario' => mb_strlen($comentario) > 500 ? 'El comentario no puede superar 500 caracteres.' : null,
            ], $_POST);
        }
        ParticipacionService::evaluar((int) $u['idUsuario'], (int) $p['idParticipacion'], $calificacion, $comentario);
        $this->exito('¡Gracias! Tu evaluación fue registrada.');
        redirect((int) $p['voluntarioId'] === (int) $u['idUsuario']
            ? '/historial'
            : "/fundacion/oportunidades/{$p['Oportunidad_idOportunidad']}/participacion");
    }

    private function participacion(string $id, int $idUsuario): array
    {
        $p = Participacion::porId($this->entero($id));
        if (!$p || ($idUsuario !== (int) $p['voluntarioId'] && $idUsuario !== (int) $p['fundacionUsuarioId'])) {
            throw new HttpException(404, 'La participación no existe.');
        }
        return $p;
    }
}
