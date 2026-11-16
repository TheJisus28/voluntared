<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpException;
use App\Models\Catalogo;
use App\Models\Fundacion;
use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Models\Usuario;

/** Casos de uso Buscar oportunidades (RF7) y consulta del detalle y de la fundación. */
final class OportunidadController extends Controller
{
    public function index(): void
    {
        $filtros = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'categoria' => (int) ($_GET['categoria'] ?? 0) ?: null,
            'ciudad' => trim((string) ($_GET['ciudad'] ?? '')),
            'modalidad' => in_array($_GET['modalidad'] ?? '', ['presencial', 'virtual', 'mixta'], true) ? $_GET['modalidad'] : '',
            'desde' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : '',
            'habilidad' => (int) ($_GET['habilidad'] ?? 0) ?: null,
        ];
        $porPagina = config('paginacion');
        $pagina = $this->pagina();
        $perfil = Auth::user()['perfil'] ?? null;
        [$items, $total] = Oportunidad::buscar($filtros, $pagina, $porPagina, $perfil);

        $this->vista('oportunidades/index', [
            'titulo' => 'Oportunidades de voluntariado',
            'oportunidades' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => (int) ceil($total / $porPagina),
            'filtros' => $filtros,
            'hayFiltros' => (bool) array_filter($filtros),
            'categorias' => Catalogo::categorias(),
            'habilidades' => Catalogo::habilidades(),
            'ciudades' => Oportunidad::ciudadesDisponibles(),
            'conPerfil' => (bool) $perfil,
        ]);
    }

    public function ver(string $id): void
    {
        $o = Oportunidad::porId($this->entero($id));
        $u = Auth::user();
        $esDuena = $u && (int) ($u['fundacion']['idFundacion'] ?? 0) === (int) ($o['idFundacion'] ?? -1);
        $esAdmin = $u && $u['rol'] === 'administrador';
        $publica = $o && $o['visible'] && $o['estadoVerificacion'] === 'verificada';
        if (!$o || (!$publica && !$esDuena && !$esAdmin)) {
            throw new HttpException(404, 'La oportunidad no existe o ya no está disponible.');
        }
        $postulacion = ($u && $u['rol'] === 'voluntario') ? Postulacion::existente((int) $u['idUsuario'], (int) $o['idOportunidad']) : null;

        $this->vista('oportunidades/ver', [
            'titulo' => $o['titulo'],
            'o' => $o,
            'calificacion' => Usuario::promedio((int) $o['fundacionUsuarioId']),
            'postulacion' => $postulacion,
            'esDuena' => $esDuena,
        ]);
    }

    public function fundacion(string $id): void
    {
        $f = Fundacion::perfilPublico($this->entero($id));
        if (!$f) {
            throw new HttpException(404, 'La fundación no existe o aún no está verificada.');
        }
        $this->vista('oportunidades/fundacion', [
            'titulo' => $f['razonSocial'],
            'f' => $f,
            'oportunidades' => Oportunidad::abiertasDeFundacion((int) $f['idFundacion']),
        ]);
    }
}
