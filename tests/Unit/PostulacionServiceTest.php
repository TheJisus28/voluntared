<?php

namespace Tests\Unit;

use App\Core\ReglaNegocioException;
use App\Models\Postulacion;
use App\Services\OportunidadService;
use App\Services\PostulacionService;
use Tests\TestCase;

/** RF8 y RF9: postulación, gestión de postulaciones y control automático de cupos. */
final class PostulacionServiceTest extends TestCase
{
    public function testPostularRegistraEstadoPendienteYNotificaALaFundacion(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();

        $id = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], 'Quiero ayudar');

        $this->assertSame('pendiente', Postulacion::porId($id)['estado']);
        $avisos = $this->notificacionesDe((int) $f['Usuario_idUsuario']);
        $this->assertSame('postulacion', end($avisos)['tipo']);
    }

    public function testNoPermitePostulacionesDuplicadas(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $v = $this->crearVoluntario();
        PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Ya te postulaste');
        PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
    }

    public function testRequierePerfilCompleto(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $v = $this->crearVoluntario(false);
        $this->expectExceptionMessage('Completa tu perfil');
        PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
    }

    public function testNoPermitePostularseAOportunidadCerrada(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        OportunidadService::cambiarEstado($o, 'cerrar');
        $this->expectExceptionMessage('ya no recibe postulaciones');
        PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
    }

    public function testAceptarDescuentaUnCupo(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['cuposTotales' => '3']);
        $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);

        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $id);

        $this->assertSame('aceptada', Postulacion::porId($id)['estado']);
        $this->assertSame(2, (int) $this->oportunidad((int) $o['idOportunidad'])['cuposDisponibles']);
    }

    public function testAlAgotarCuposLaOportunidadSeCierraAutomaticamente(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['cuposTotales' => '1']);
        $idA = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
        $idB = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);

        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $idA);
        $op = $this->oportunidad((int) $o['idOportunidad']);
        $this->assertSame(0, (int) $op['cuposDisponibles']);
        $this->assertSame('cerrada', $op['estado']);

        // No se puede superar el número de cupos.
        $this->expectExceptionMessage('No quedan cupos');
        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $idB);
    }

    public function testCancelarPostulacionAceptadaLiberaElCupoYReabre(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['cuposTotales' => '1']);
        $v = $this->crearVoluntario();
        $id = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $id);

        PostulacionService::cancelar((int) $v['idUsuario'], $id);

        $op = $this->oportunidad((int) $o['idOportunidad']);
        $this->assertSame('cancelada', Postulacion::porId($id)['estado']);
        $this->assertSame(1, (int) $op['cuposDisponibles']);
        $this->assertSame('abierta', $op['estado']);
    }

    public function testPuedeVolverAPostularseDespuesDeCancelar(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $v = $this->crearVoluntario();
        $id = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);
        PostulacionService::cancelar((int) $v['idUsuario'], $id);

        $id2 = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], 'Otra vez');
        $this->assertSame($id, $id2);
        $this->assertSame('pendiente', Postulacion::porId($id)['estado']);
    }

    public function testRechazarNotificaAlVoluntario(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();
        $id = PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);

        PostulacionService::rechazar((int) $f['Usuario_idUsuario'], $id);

        $this->assertSame('rechazada', Postulacion::porId($id)['estado']);
        $avisos = $this->notificacionesDe((int) $v['idUsuario']);
        $this->assertSame('respuesta', end($avisos)['tipo']);
    }

    public function testUnaFundacionNoPuedeResponderPostulacionesDeOtra(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
        $otra = $this->crearFundacion();

        $this->expectExceptionMessage('Postulación no encontrada');
        PostulacionService::aceptar((int) $otra['Usuario_idUsuario'], $id);
    }

    public function testNoSePuedeCancelarUnaPostulacionAjena(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
        $this->expectException(ReglaNegocioException::class);
        PostulacionService::cancelar((int) $this->crearVoluntario()['idUsuario'], $id);
    }
}
