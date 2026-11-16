<?php

namespace Tests\Unit;

use App\Core\Database as DB;
use App\Core\ReglaNegocioException;
use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Services\OportunidadService;
use App\Services\PostulacionService;
use Tests\TestCase;

/** RF6 y RF7: publicación, modificación, cambios de estado y búsqueda de oportunidades. */
final class OportunidadServiceTest extends TestCase
{
    public function testDatosValidosPasanLaValidacion(): void
    {
        $this->assertSame([], OportunidadService::validar($this->datosOportunidad()));
    }

    public function testValidaFechasHorasYCupos(): void
    {
        $errores = OportunidadService::validar($this->datosOportunidad([
            'fechaInicio' => date('Y-m-d', strtotime('+5 days')),
            'fechaFin' => date('Y-m-d', strtotime('+2 days')),
            'horaInicio' => '12:00',
            'horaFin' => '08:00',
            'cuposTotales' => '0',
        ]));
        $this->assertArrayHasKey('fechaFin', $errores);
        $this->assertArrayHasKey('horaFin', $errores);
        $this->assertArrayHasKey('cuposTotales', $errores);
    }

    public function testUbicacionObligatoriaSiEsPresencial(): void
    {
        $errores = OportunidadService::validar($this->datosOportunidad(['modalidad' => 'presencial', 'ubicacion' => '']));
        $this->assertArrayHasKey('ubicacion', $errores);
        $this->assertSame([], OportunidadService::validar($this->datosOportunidad(['modalidad' => 'virtual', 'ubicacion' => ''])));
    }

    public function testNoPermiteFechaDeInicioPasadaAlPublicar(): void
    {
        $errores = OportunidadService::validar($this->datosOportunidad(['fechaInicio' => date('Y-m-d', strtotime('-1 day'))]));
        $this->assertArrayHasKey('fechaInicio', $errores);
    }

    public function testSoloFundacionesVerificadasPublican(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('verificadas');
        OportunidadService::crear($this->crearFundacion('pendiente'), $this->datosOportunidad());
    }

    public function testPublicarDejaEstadoAbiertoYCuposCompletos(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion(), ['cuposTotales' => '7', 'habilidades' => [1, 2]]);
        $this->assertSame('abierta', $o['estado']);
        $this->assertSame(7, (int) $o['cuposDisponibles']);
        $this->assertCount(2, $o['habilidades']);
    }

    public function testPublicarNotificaAVoluntariosConInteresesAfines(): void
    {
        $interesado = $this->crearVoluntario(true, [3]);
        $noInteresado = $this->crearVoluntario(true, [5]);
        $this->crearOportunidad($this->crearFundacion(), ['categoria' => '3']);

        $this->assertCount(1, array_filter($this->notificacionesDe((int) $interesado['idUsuario']), static fn ($n) => $n['tipo'] === 'oportunidad'));
        $this->assertCount(0, $this->notificacionesDe((int) $noInteresado['idUsuario']));
    }

    public function testNoSePuedenReducirCuposPorDebajoDeLosAceptados(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['cuposTotales' => '3']);
        foreach ([1, 2] as $_) {
            $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
            PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $id);
        }
        $this->expectExceptionMessage('los cupos no pueden ser menos');
        OportunidadService::actualizar($this->oportunidad((int) $o['idOportunidad']), $this->datosOportunidad(['cuposTotales' => '1']));
    }

    public function testCambioDeFechaAvisaALosPostulados(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f);
        $v = $this->crearVoluntario();
        PostulacionService::postular((int) $v['idUsuario'], (int) $o['idOportunidad'], null);

        OportunidadService::actualizar($o, $this->datosOportunidad(['fechaFin' => date('Y-m-d', strtotime('+9 days'))]));

        $avisos = $this->notificacionesDe((int) $v['idUsuario']);
        $this->assertSame('Cambios en una actividad', end($avisos)['titulo']);
    }

    public function testCancelarOportunidadCancelaPostulacionesPendientes(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);

        OportunidadService::cambiarEstado($o, 'cancelar');

        $this->assertSame('cancelada', $this->oportunidad((int) $o['idOportunidad'])['estado']);
        $this->assertSame('cancelada', Postulacion::porId($id)['estado']);
    }

    public function testNoSePuedeFinalizarUnaActividadQueNoHaComenzado(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        $this->expectExceptionMessage('aún no ha comenzado');
        OportunidadService::cambiarEstado($o, 'finalizar');
    }

    public function testActividadesVencidasSeFinalizanAutomaticamente(): void
    {
        $o = $this->crearOportunidad($this->crearFundacion());
        DB::consulta('UPDATE oportunidad SET fechaInicio = CURDATE() - INTERVAL 3 DAY, fechaFin = CURDATE() - INTERVAL 1 DAY WHERE idOportunidad = ?', [$o['idOportunidad']]);
        Oportunidad::finalizarVencidas();
        $this->assertSame('finalizada', $this->oportunidad((int) $o['idOportunidad'])['estado']);
    }

    public function testBusquedaFiltraPorCategoriaYCiudad(): void
    {
        $f = $this->crearFundacion();
        $buscada = $this->crearOportunidad($f, ['categoria' => '4', 'ciudad' => 'Ciudad Prueba']);
        $this->crearOportunidad($f, ['categoria' => '2', 'ciudad' => 'Ciudad Prueba']);

        [$items, $total] = Oportunidad::buscar(['categoria' => 4, 'ciudad' => 'Ciudad Prueba'], 1, 10);

        $this->assertSame(1, $total);
        $this->assertSame((int) $buscada['idOportunidad'], (int) $items[0]['idOportunidad']);
    }

    public function testBusquedaNoMuestraOportunidadesOcultasNiDeFundacionesSinVerificar(): void
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['ciudad' => 'Ciudad Oculta']);
        Oportunidad::actualizar((int) $o['idOportunidad'], ['visible' => 0]);
        [, $total] = Oportunidad::buscar(['ciudad' => 'Ciudad Oculta'], 1, 10);
        $this->assertSame(0, $total);
    }

    public function testBusquedaPriorizaOportunidadesAfinesAlPerfil(): void
    {
        $f = $this->crearFundacion();
        $this->crearOportunidad($f, ['categoria' => '2', 'ciudad' => 'Ciudad Afin', 'fechaInicio' => date('Y-m-d', strtotime('+2 days'))]);
        $afin = $this->crearOportunidad($f, ['categoria' => '6', 'ciudad' => 'Ciudad Afin', 'fechaInicio' => date('Y-m-d', strtotime('+5 days'))]);
        $v = $this->crearVoluntario(true, [6]);
        $perfil = \App\Models\PerfilVoluntario::porUsuario((int) $v['idUsuario']);

        [$items] = Oportunidad::buscar(['ciudad' => 'Ciudad Afin'], 1, 10, $perfil);

        $this->assertSame((int) $afin['idOportunidad'], (int) $items[0]['idOportunidad']);
    }
}
