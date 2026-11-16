<?php

namespace App\Controllers;

use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Models\Catalogo;
use App\Models\Evaluacion;
use App\Models\Fundacion;
use App\Models\Oportunidad;
use App\Models\Usuario;
use App\Services\AdminService;
use App\Services\EstadisticaService;

/** Casos de uso del administrador (RF5, RF14 y RF16). */
final class AdminController extends Controller
{
    public function estadisticas(): void
    {
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : null;
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : null;
        $categoria = (int) ($_GET['categoria'] ?? 0) ?: null;
        $this->vista('admin/estadisticas', [
            'titulo' => 'Estadísticas',
            'datos' => EstadisticaService::tablero($desde, $hasta, $categoria),
            'categorias' => Catalogo::categorias(),
            'usaGraficos' => true,
        ]);
    }

    // ------------------------------------------------------------ usuarios

    public function usuarios(): void
    {
        $filtros = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'rol' => (string) ($_GET['rol'] ?? ''),
            'estado' => in_array($_GET['estado'] ?? '', ['activo', 'suspendido'], true) ? $_GET['estado'] : '',
        ];
        $porPagina = 15;
        $pagina = $this->pagina();
        [$usuarios, $total] = Usuario::listar($filtros, $pagina, $porPagina);
        $this->vista('admin/usuarios', [
            'titulo' => 'Usuarios',
            'usuarios' => $usuarios,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => (int) ceil($total / $porPagina),
            'filtros' => $filtros,
        ]);
    }

    public function suspender(string $id): void
    {
        AdminService::suspender((int) $this->usuario()['idUsuario'], $this->entero($id), (string) ($_POST['motivo'] ?? ''));
        $this->exito('La cuenta fue suspendida y se notificó al usuario.');
        back();
    }

    public function reactivar(string $id): void
    {
        AdminService::reactivar($this->entero($id));
        $this->exito('La cuenta fue reactivada.');
        back();
    }

    // ------------------------------------------------------------ fundaciones

    public function fundaciones(): void
    {
        $estado = in_array($_GET['estado'] ?? '', ['pendiente', 'verificada', 'rechazada'], true) ? $_GET['estado'] : 'pendiente';
        $this->vista('admin/fundaciones', [
            'titulo' => 'Verificación de fundaciones',
            'fundaciones' => Fundacion::porEstado($estado),
            'estado' => $estado,
            'conteo' => Fundacion::contarPorEstado(),
        ]);
    }

    public function fundacion(string $id): void
    {
        $f = Fundacion::porId($this->entero($id));
        if (!$f) {
            throw new HttpException(404);
        }
        $this->vista('admin/fundacion', [
            'titulo' => $f['razonSocial'],
            'f' => $f,
            'oportunidades' => Oportunidad::deFundacion((int) $f['idFundacion']),
        ]);
    }

    public function documento(string $id): void
    {
        $f = Fundacion::porId($this->entero($id)) ?? throw new HttpException(404);
        ArchivoController::servirDocumento($f['documentoSoporte'], 'soporte-' . preg_replace('/\D/', '', $f['nit']));
    }

    public function verificar(string $id): void
    {
        $decision = (string) ($_POST['decision'] ?? '');
        AdminService::verificarFundacion($this->entero($id), $decision, $_POST['motivo'] ?? null);
        $this->exito($decision === 'verificada'
            ? 'Fundación verificada. Ya puede publicar oportunidades.'
            : 'Se rechazó la verificación y se notificó el motivo a la fundación.');
        redirect('/admin/fundaciones');
    }

    // ------------------------------------------------------------ catálogos

    public function catalogos(): void
    {
        $this->vista('admin/catalogos', [
            'titulo' => 'Categorías y habilidades',
            'categorias' => Catalogo::categorias(),
            'habilidades' => Catalogo::habilidades(),
        ]);
    }

    public function guardarCatalogo(string $tipo, ?string $id = null): void
    {
        $tabla = $this->tablaCatalogo($tipo);
        $idNum = $id !== null ? $this->entero($id) : null;
        $reglas = ['nombre' => 'requerido|min:3|max:60'];
        if ($tabla === 'categoria') {
            $reglas['descripcion'] = 'max:200';
        }
        $errores = Validator::validar($_POST, $reglas);
        if (!$errores && Catalogo::existeNombre($tabla, $_POST['nombre'], $idNum)) {
            $errores['nombre'] = 'Ya existe un elemento con ese nombre.';
        }
        if ($errores) {
            Flash::set('danger', reset($errores));
            back();
        }
        $datos = ['nombre' => trim($_POST['nombre'])];
        if ($tabla === 'categoria') {
            $datos['descripcion'] = trim($_POST['descripcion'] ?? '') ?: null;
        }
        Catalogo::guardar($tabla, $datos, $idNum);
        $this->exito(($tabla === 'categoria' ? 'Categoría' : 'Habilidad') . ($idNum ? ' actualizada.' : ' creada.'));
        redirect('/admin/catalogos', ['tab' => $tipo]);
    }

    public function eliminarCatalogo(string $tipo, string $id): void
    {
        $tabla = $this->tablaCatalogo($tipo);
        $idNum = $this->entero($id);
        if (Catalogo::enUso($tabla, $idNum)) {
            Flash::set('danger', 'No se puede eliminar: está asociada a oportunidades o perfiles de voluntarios.');
        } else {
            Catalogo::eliminar($tabla, $idNum);
            $this->exito('Elemento eliminado.');
        }
        redirect('/admin/catalogos', ['tab' => $tipo]);
    }

    private function tablaCatalogo(string $tipo): string
    {
        return match ($tipo) {
            'categorias' => 'categoria',
            'habilidades' => 'habilidad',
            default => throw new HttpException(404),
        };
    }

    // ------------------------------------------------------------ moderación

    public function moderacion(): void
    {
        $this->vista('admin/moderacion', [
            'titulo' => 'Moderación de contenido',
            'oportunidades' => Oportunidad::recientesParaModeracion(),
            'evaluaciones' => Evaluacion::recientesParaModeracion(),
        ]);
    }

    public function moderarOportunidad(string $id): void
    {
        $visible = ($_POST['visible'] ?? '0') === '1';
        AdminService::moderarOportunidad($this->entero($id), $visible, trim((string) ($_POST['motivo'] ?? '')) ?: null);
        $this->exito($visible ? 'La oportunidad vuelve a ser visible.' : 'La oportunidad se retiró de la vista pública y se notificó a la fundación.');
        back();
    }

    public function moderarEvaluacion(string $id): void
    {
        $visible = ($_POST['visible'] ?? '0') === '1';
        AdminService::moderarEvaluacion($this->entero($id), $visible, trim((string) ($_POST['motivo'] ?? '')) ?: null);
        $this->exito($visible ? 'La evaluación vuelve a ser visible.' : 'La evaluación se ocultó y se notificó a su autor.');
        back();
    }
}
