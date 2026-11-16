<?php

namespace Tests\Unit;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Usuario;
use App\Services\OportunidadService;
use App\Services\ParticipacionService;
use App\Services\PostulacionService;
use Tests\TestCase;

/** RF12 y RF13: registro de participación y evaluación mutua. */
final class ParticipacionServiceTest extends TestCase
{
    /** Crea una actividad finalizada con un voluntario aceptado. */
    private function escenario(): array
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();
        $idPost = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $idPost);
        DB::consulta('UPDATE oportunidad SET fechaInicio = CURDATE(), fechaFin = CURDATE() WHERE idOportunidad = ?', [$o['idOportunidad']]);
        OportunidadService::cambiarEstado($this->oportunidad((int) $o['idOportunidad']), 'finalizar');
        return [$f, $this->oportunidad((int) $o['idOportunidad']), $v, $idPost];
    }

    private function registrar(array $f, array $o, int $idPost, array $datos): int
    {
        ParticipacionService::registrar($o, (int) $f['Usuario_idUsuario'], [$idPost => $datos]);
        return (int) DB::valor('SELECT idParticipacion FROM participacion WHERE Postulacion_idPostulacion = ?', [$idPost]);
    }

    public function testRegistrarAsistenciaYHoras(): void
    {
        [$f, $o, $v, $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '4,5', 'observacion' => 'Muy puntual']);
        $fila = DB::fila('SELECT * FROM participacion WHERE idParticipacion = ?', [$id]);
        $this->assertSame(1, (int) $fila['asistio']);
        $this->assertSame(4.5, (float) $fila['horasCumplidas']);
        $avisos = $this->notificacionesDe((int) $v['idUsuario']);
        $this->assertSame('evaluacion', end($avisos)['tipo']);
    }

    public function testHorasNegativasSonRechazadas(): void
    {
        [$f, $o, , $idPost] = $this->escenario();
        $this->expectExceptionMessage('mayores o iguales a cero');
        $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '-1']);
    }

    public function testSiNoAsistioLasHorasSonCero(): void
    {
        [$f, $o, , $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['horas' => '6']);
        $this->assertSame(0.0, (float) DB::valor('SELECT horasCumplidas FROM participacion WHERE idParticipacion = ?', [$id]));
    }

    public function testRequiereQueLaActividadEsteFinalizada(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $this->expectExceptionMessage('finalizada');
        ParticipacionService::registrar($o, (int) $f['Usuario_idUsuario'], []);
    }

    public function testSoloSeRegistranVoluntariosAceptados(): void
    {
        [$f, $o] = $this->escenario();
        $this->expectExceptionMessage('aceptados');
        ParticipacionService::registrar($o, (int) $f['Usuario_idUsuario'], [999999 => ['asistio' => '1', 'horas' => '2']]);
    }

    public function testEvaluacionMutuaYPromedio(): void
    {
        [$f, $o, $v, $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '4']);

        ParticipacionService::evaluar((int) $v['idUsuario'], $id, 4, 'Bien organizada');
        ParticipacionService::evaluar((int) $f['Usuario_idUsuario'], $id, 5, 'Excelente voluntario');

        $this->assertSame(4.0, Usuario::promedio((int) $f['Usuario_idUsuario'])['promedio']);
        $this->assertSame(5.0, Usuario::promedio((int) $v['idUsuario'])['promedio']);
    }

    public function testNoSePuedeEvaluarDosVeces(): void
    {
        [$f, $o, $v, $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '4']);
        ParticipacionService::evaluar((int) $v['idUsuario'], $id, 5, null);
        $this->expectExceptionMessage('Ya evaluaste');
        ParticipacionService::evaluar((int) $v['idUsuario'], $id, 3, null);
    }

    public function testCalificacionFueraDeRango(): void
    {
        [$f, $o, $v, $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '4']);
        $this->expectExceptionMessage('entre 1 y 5');
        ParticipacionService::evaluar((int) $v['idUsuario'], $id, 6, null);
    }

    public function testUnTerceroNoPuedeEvaluar(): void
    {
        [$f, $o, , $idPost] = $this->escenario();
        $id = $this->registrar($f, $o, $idPost, ['asistio' => '1', 'horas' => '4']);
        $this->expectException(ReglaNegocioException::class);
        ParticipacionService::evaluar((int) $this->crearVoluntario()['idUsuario'], $id, 5, null);
    }
}
