<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Catalogo;
use App\Models\Participacion;
use App\Models\PerfilVoluntario;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\CertificadoService;
use App\Services\Exportador;
use App\Services\PostulacionService;

/** Casos de uso del voluntario: perfil (RF3), postulación (RF8) e historial. */
final class VoluntarioController extends Controller
{
    public function perfil(): void
    {
        $u = $this->usuario();
        $perfil = $u['perfil'];
        $this->vista('voluntario/perfil', [
            'titulo' => 'Mi perfil',
            'u' => $u,
            'perfil' => $perfil,
            'misHabilidades' => $perfil ? array_column(PerfilVoluntario::habilidades((int) $perfil['idPerfilVoluntario']), 'idHabilidad') : [],
            'misCategorias' => $perfil ? array_column(PerfilVoluntario::categorias((int) $perfil['idPerfilVoluntario']), 'idCategoria') : [],
            'habilidades' => Catalogo::habilidades(),
            'categorias' => Catalogo::categorias(),
            'calificacion' => Usuario::promedio((int) $u['idUsuario']),
            'horas' => Participacion::totalHoras((int) $u['idUsuario']),
        ]);
    }

    public function guardarPerfil(): void
    {
        $u = $this->usuario();
        $datos = $_POST;
        $errores = Validator::validar($datos, [
            'nombre' => 'requerido|max:100',
            'apellido' => 'requerido|max:100',
            'telefono' => 'telefono',
            'fechaNacimiento' => 'requerido|fecha|no_futura|edad_minima:14',
            'biografia' => 'max:500',
            'disponibilidad' => 'requerido|en:mananas,tardes,noches,fines_de_semana,flexible',
            'ciudad' => 'requerido|max:80',
            'departamento' => 'requerido|max:80',
        ]);
        $habilidades = array_map('intval', (array) ($datos['habilidades'] ?? []));
        $categorias = array_map('intval', (array) ($datos['categorias'] ?? []));
        if (!$categorias) {
            $errores['categorias'] = 'Selecciona al menos una causa de interés para recibir recomendaciones.';
        }
        foreach ($habilidades as $id) {
            if (!Catalogo::existe('habilidad', $id)) {
                $errores['habilidades'] = 'Selecciona habilidades válidas.';
            }
        }
        foreach ($categorias as $id) {
            if (!Catalogo::existe('categoria', $id)) {
                $errores['categorias'] = 'Selecciona categorías válidas.';
            }
        }
        $errorFoto = Upload::validar($_FILES['foto'] ?? null, Upload::IMAGENES, false);
        if ($errorFoto) {
            $errores['foto'] = $errorFoto;
        }
        if ($errores) {
            $this->volverConErrores($errores, $datos);
        }

        DB::transaccion(function () use ($u, $datos, $habilidades, $categorias) {
            $cambios = [
                'nombre' => trim($datos['nombre']),
                'apellido' => trim($datos['apellido']),
                'telefono' => trim($datos['telefono'] ?? '') ?: null,
            ];
            if (!empty($_FILES['foto']['tmp_name'])) {
                $cambios['foto'] = Upload::guardar($_FILES['foto'], Upload::IMAGENES, 'fotos', true);
                Upload::eliminar($u['foto'], true);
            }
            Usuario::actualizar((int) $u['idUsuario'], $cambios);
            $idPerfil = PerfilVoluntario::guardar((int) $u['idUsuario'], array_map(
                static fn ($v) => is_string($v) ? trim($v) : $v,
                $datos + ['biografia' => '']
            ));
            PerfilVoluntario::sincronizarHabilidades($idPerfil, $habilidades);
            PerfilVoluntario::sincronizarCategorias($idPerfil, $categorias);
        });
        Auth::refrescar();
        $this->exito('Tu perfil se guardó correctamente.');
        redirect('/perfil');
    }

    public function postular(string $id): void
    {
        $u = $this->usuario();
        $idOportunidad = $this->entero($id);
        $mensaje = (string) ($_POST['mensaje'] ?? '');
        if (mb_strlen($mensaje) > 500) {
            $this->volverConErrores(['mensaje' => 'El mensaje no puede superar 500 caracteres.'], $_POST);
        }
        PostulacionService::postular((int) $u['idUsuario'], $idOportunidad, $mensaje);
        $this->exito('¡Postulación enviada! La fundación revisará tu solicitud y te avisaremos cuando responda.');
        redirect("/oportunidades/$idOportunidad");
    }

    public function postulaciones(): void
    {
        $u = $this->usuario();
        $estado = in_array($_GET['estado'] ?? '', ['pendiente', 'aceptada', 'rechazada', 'cancelada'], true) ? $_GET['estado'] : null;
        $this->vista('voluntario/postulaciones', [
            'titulo' => 'Mis postulaciones',
            'postulaciones' => Postulacion::deVoluntario((int) $u['idUsuario'], $estado),
            'estado' => $estado,
            'conteo' => Postulacion::contarPorEstadoVoluntario((int) $u['idUsuario']),
        ]);
    }

    public function cancelar(string $id): void
    {
        $u = $this->usuario();
        PostulacionService::cancelar((int) $u['idUsuario'], $this->entero($id));
        $this->exito('Cancelaste tu postulación.');
        back();
    }

    public function historial(): void
    {
        $u = $this->usuario();
        $id = (int) $u['idUsuario'];
        $this->vista('voluntario/historial', [
            'titulo' => 'Mi historial',
            'actividades' => Participacion::historial($id),
            'horas' => Participacion::totalHoras($id),
            'calificacion' => Usuario::promedio($id),
            'evaluaciones' => \App\Models\Evaluacion::recibidas($id),
        ]);
    }

    public function certificado(): void
    {
        $u = $this->usuario();
        $id = (int) $u['idUsuario'];
        $pdf = CertificadoService::generar($u, Participacion::historial($id), Participacion::totalHoras($id));
        Exportador::descargar($pdf, 'certificado-voluntariado.pdf', 'application/pdf');
    }
}
