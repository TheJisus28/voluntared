<?php

namespace App\Controllers;

use App\Models\Catalogo;
use App\Models\Oportunidad;
use App\Services\Exportador;
use App\Services\ReporteService;

/** Casos de uso Generar reportes de la fundación y del administrador (RF15). */
final class ReporteController extends Controller
{
    public function fundacion(): void
    {
        $f = $this->usuario()['fundacion'];
        $this->procesar(ReporteService::TIPOS_FUNDACION, (int) $f['idFundacion'], $f['razonSocial'], [
            'oportunidades' => Oportunidad::deFundacion((int) $f['idFundacion']),
            'accion' => '/reportes',
        ]);
    }

    public function admin(): void
    {
        $this->procesar(ReporteService::TIPOS_ADMIN, null, 'Administrador VoluntaRed', ['accion' => '/admin/reportes']);
    }

    private function procesar(array $tipos, ?int $idFundacion, string $autor, array $extra): void
    {
        $tipo = (string) ($_GET['tipo'] ?? '');
        $reporte = null;
        if ($tipo !== '' && isset($tipos[$tipo])) {
            $reporte = ReporteService::generar($tipo, $_GET, $idFundacion);
            $formato = $_GET['formato'] ?? '';
            $archivo = 'voluntared-' . $tipo . '-' . date('Ymd-His');
            if ($formato === 'pdf') {
                Exportador::descargar(Exportador::pdf($reporte, $autor), "$archivo.pdf", 'application/pdf');
                return;
            }
            if ($formato === 'xlsx') {
                Exportador::descargar(Exportador::xlsx($reporte), "$archivo.xlsx",
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                return;
            }
        }
        $this->vista('reportes/index', $extra + [
            'titulo' => 'Reportes',
            'tipos' => $tipos,
            'tipo' => $tipo,
            'reporte' => $reporte,
            'esAdmin' => $idFundacion === null,
            'categorias' => Catalogo::categorias(),
            'filtros' => $_GET,
        ]);
    }
}
