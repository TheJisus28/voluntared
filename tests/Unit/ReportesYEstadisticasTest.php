<?php

namespace Tests\Unit;

use App\Core\ReglaNegocioException;
use App\Services\EstadisticaService;
use App\Services\Exportador;
use App\Services\PostulacionService;
use App\Services\ReporteService;
use Tests\TestCase;

/** RF15 y RF16: reportes exportables a PDF/Excel y estadísticas. */
final class ReportesYEstadisticasTest extends TestCase
{
    private function datos(): array
    {
        $f = $this->crearFundacion();
        $o = $this->crearOportunidad($f, ['titulo' => 'Actividad para reportes']);
        $id = PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
        PostulacionService::aceptar((int) $f['Usuario_idUsuario'], $id);
        PostulacionService::postular((int) $this->crearVoluntario()['idUsuario'], (int) $o['idOportunidad'], null);
        return [$f, $o];
    }

    public function testReporteDePostulacionesDeLaFundacion(): void
    {
        [$f] = $this->datos();
        $r = ReporteService::generar('postulaciones', [], (int) $f['idFundacion']);
        $this->assertCount(2, $r['filas']);
        $this->assertSame(1, $r['resumen']['Aceptadas']);
        $this->assertSame('50 %', $r['resumen']['Tasa de aceptación']);
    }

    public function testReporteDeActividadesFiltradoPorEstado(): void
    {
        [$f] = $this->datos();
        $this->assertCount(1, ReporteService::generar('actividades', ['estado' => 'abierta'], (int) $f['idFundacion'])['filas']);
        $this->assertCount(0, ReporteService::generar('actividades', ['estado' => 'cancelada'], (int) $f['idFundacion'])['filas']);
    }

    public function testUnaFundacionNoPuedePedirReportesGlobales(): void
    {
        [$f] = $this->datos();
        $this->expectException(ReglaNegocioException::class);
        ReporteService::generar('usuarios', [], (int) $f['idFundacion']);
    }

    public function testTodosLosReportesGlobalesSeGeneran(): void
    {
        $this->datos();
        foreach (array_keys(ReporteService::TIPOS_ADMIN) as $tipo) {
            $r = ReporteService::generar($tipo, ['desde' => date('Y-m-d', strtotime('-1 year'))]);
            $this->assertSame(count($r['columnas']), count($r['anchos']), "Columnas y anchos de $tipo");
            foreach ($r['filas'] as $fila) {
                $this->assertCount(count($r['columnas']), $fila, "Cada fila de $tipo debe tener todas las columnas");
            }
        }
    }

    public function testExportarAPdf(): void
    {
        [$f] = $this->datos();
        $pdf = Exportador::pdf(ReporteService::generar('actividades', [], (int) $f['idFundacion']));
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testExportarAExcel(): void
    {
        [$f] = $this->datos();
        $xlsx = Exportador::xlsx(ReporteService::generar('postulaciones', [], (int) $f['idFundacion']));
        $this->assertStringStartsWith('PK', $xlsx, 'Un .xlsx es un archivo ZIP');
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($ruta, $xlsx);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($ruta) === true);
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $zip->close();
        unlink($ruta);
    }

    public function testEstadisticasCuentanPostulacionesDelPeriodo(): void
    {
        $antes = EstadisticaService::tablero(null, null, null)['indicadores']['postulaciones'];
        $this->datos();
        $despues = EstadisticaService::tablero(null, null, null);
        $this->assertSame($antes + 2, $despues['indicadores']['postulaciones']);
        $this->assertCount(4, $despues['graficos']['estados']['valores']);
        $this->assertSame(count($despues['graficos']['meses']['etiquetas']), count($despues['graficos']['meses']['postulaciones']));
    }

    public function testCertificadoDeHorasEnPdf(): void
    {
        $v = $this->crearVoluntario();
        $pdf = \App\Services\CertificadoService::generar($v, [
            ['asistio' => 1, 'titulo' => 'Siembra', 'razonSocial' => 'Fundación', 'fechaInicio' => '2026-05-01', 'horasCumplidas' => 6],
        ], 6.0);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
