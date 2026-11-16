<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Catalogo;
use App\Models\Fundacion;
use App\Models\Oportunidad;
use App\Models\Participacion;
use App\Models\PerfilVoluntario;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\NotificacionService;
use App\Services\OportunidadService;
use App\Services\ParticipacionService;
use App\Services\PostulacionService;

/** Casos de uso de la fundación: perfil (RF4), oportunidades (RF6), postulaciones (RF9) y participación (RF12). */
final class FundacionController extends Controller
{
    // ------------------------------------------------------------ perfil

    public function perfil(): void
    {
        $u = $this->usuario();
        $this->vista('fundacion/perfil', [
            'titulo' => 'Perfil de la fundación',
            'u' => $u,
            'f' => $u['fundacion'],
            'calificacion' => Usuario::promedio((int) $u['idUsuario']),
        ]);
    }

    public function guardarPerfil(): void
    {
        $u = $this->usuario();
        $f = $u['fundacion'];
        $datos = $_POST;
        $errores = Validator::validar($datos, [
            'razonSocial' => 'requerido|max:150',
            'nit' => 'requerido|nit',
            'descripcion' => 'requerido|min:30|max:3000',
            'mision' => 'max:500',
            'direccion' => 'requerido|max:150',
            'ciudad' => 'requerido|max:80',
            'sitioWeb' => 'url|max:200',
            'nombre' => 'requerido|max:100',
            'apellido' => 'requerido|max:100',
            'telefono' => 'requerido|telefono',
        ]);
        if (!isset($errores['nit']) && Fundacion::nitExiste(trim($datos['nit']), (int) $f['idFundacion'])) {
            $errores['nit'] = 'Ya hay otra fundación registrada con este NIT.';
        }
        if ($e = Upload::validar($_FILES['logo'] ?? null, Upload::IMAGENES, false)) {
            $errores['logo'] = $e;
        }
        if ($e = Upload::validar($_FILES['documento'] ?? null, Upload::DOCUMENTOS, false)) {
            $errores['documento'] = $e;
        }
        if ($errores) {
            $this->volverConErrores($errores, $datos);
        }

        $nit = trim($datos['nit']);
        $razon = trim($datos['razonSocial']);
        $nuevoDocumento = !empty($_FILES['documento']['tmp_name']);
        // Cambiar NIT, razón social o documento obliga a una nueva verificación (CU Gestionar perfil).
        $reverificar = $nit !== $f['nit'] || $razon !== $f['razonSocial'] || $nuevoDocumento
            || $f['estadoVerificacion'] === 'rechazada';

        DB::transaccion(function () use ($u, $f, $datos, $nit, $razon, $nuevoDocumento, $reverificar) {
            $cambiosUsuario = [
                'nombre' => trim($datos['nombre']),
                'apellido' => trim($datos['apellido']),
                'telefono' => trim($datos['telefono']),
            ];
            if (!empty($_FILES['logo']['tmp_name'])) {
                $cambiosUsuario['foto'] = Upload::guardar($_FILES['logo'], Upload::IMAGENES, 'fotos', true);
                Upload::eliminar($u['foto'], true);
            }
            Usuario::actualizar((int) $u['idUsuario'], $cambiosUsuario);

            $cambios = [
                'nit' => $nit,
                'razonSocial' => $razon,
                'descripcion' => trim($datos['descripcion']),
                'mision' => trim($datos['mision'] ?? '') ?: null,
                'direccion' => trim($datos['direccion']),
                'ciudad' => trim($datos['ciudad']),
                'sitioWeb' => trim($datos['sitioWeb'] ?? '') ?: null,
            ];
            if ($nuevoDocumento) {
                $cambios['documentoSoporte'] = Upload::guardar($_FILES['documento'], Upload::DOCUMENTOS, 'documentos', false);
                Upload::eliminar($f['documentoSoporte'], false);
            }
            if ($reverificar && $f['estadoVerificacion'] !== 'pendiente') {
                $cambios += ['estadoVerificacion' => 'pendiente', 'fechaVerificacion' => null, 'motivoRechazo' => null];
            }
            Fundacion::actualizar((int) $f['idFundacion'], $cambios);
        });

        if ($reverificar && $f['estadoVerificacion'] !== 'pendiente') {
            NotificacionService::notificarAdministradores(
                'Fundación por verificar nuevamente',
                "$razon actualizó sus datos legales o su documento soporte y requiere una nueva verificación.",
                '/admin/fundaciones/' . $f['idFundacion']
            );
            $this->exito('Perfil guardado. Como cambiaste datos legales o el documento, tu fundación vuelve a estado «pendiente» hasta que el administrador la verifique.');
        } else {
            $this->exito('El perfil de la fundación se guardó correctamente.');
        }
        Auth::refrescar();
        redirect('/fundacion/perfil');
    }

    /** Descarga del documento soporte propio. */
    public function documento(): void
    {
        $f = $this->usuario()['fundacion'];
        ArchivoController::servirDocumento($f['documentoSoporte'], 'documento-soporte');
    }

    // ------------------------------------------------------------ oportunidades

    public function oportunidades(): void
    {
        $f = $this->usuario()['fundacion'];
        $estado = in_array($_GET['estado'] ?? '', ['abierta', 'cerrada', 'finalizada', 'cancelada'], true) ? $_GET['estado'] : null;
        $this->vista('fundacion/oportunidades', [
            'titulo' => 'Mis oportunidades',
            'oportunidades' => Oportunidad::deFundacion((int) $f['idFundacion'], $estado),
            'estado' => $estado,
        ]);
    }

    public function nueva(): void
    {
        $this->formulario(null);
    }

    public function crear(): void
    {
        $f = $this->usuario()['fundacion'];
        $errores = OportunidadService::validar($_POST, true);
        if ($errores) {
            $this->volverConErrores($errores, $_POST);
        }
        $id = OportunidadService::crear($f, $_POST);
        $this->exito('¡Oportunidad publicada! Notificamos a los voluntarios con intereses afines.');
        redirect("/oportunidades/$id");
    }

    public function editar(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        if (in_array($o['estado'], ['finalizada', 'cancelada'], true)) {
            \App\Core\Flash::set('warning', 'Las oportunidades finalizadas o canceladas no se pueden modificar.');
            redirect('/fundacion/oportunidades');
        }
        $this->formulario($o);
    }

    public function actualizar(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        $errores = OportunidadService::validar($_POST, false);
        if ($errores) {
            $this->volverConErrores($errores, $_POST);
        }
        OportunidadService::actualizar($o, $_POST);
        $this->exito('Los cambios se guardaron.');
        redirect("/oportunidades/{$o['idOportunidad']}");
    }

    public function cambiarEstado(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        $accion = (string) ($_POST['accion'] ?? '');
        OportunidadService::cambiarEstado($o, $accion);
        $mensajes = [
            'cerrar' => 'La convocatoria quedó cerrada.',
            'reabrir' => 'La convocatoria está abierta nuevamente.',
            'cancelar' => 'La oportunidad fue cancelada y se notificó a los postulados.',
            'finalizar' => 'La actividad quedó finalizada. Ahora puedes registrar la participación.',
        ];
        $this->exito($mensajes[$accion] ?? 'Estado actualizado.');
        if ($accion === 'finalizar') {
            redirect("/fundacion/oportunidades/{$o['idOportunidad']}/participacion");
        }
        back();
    }

    private function formulario(?array $o): void
    {
        $this->vista('fundacion/oportunidad_form', [
            'titulo' => $o ? 'Modificar oportunidad' : 'Nueva oportunidad',
            'o' => $o,
            'seleccionadas' => $o ? array_column($o['habilidades'], 'idHabilidad') : [],
            'categorias' => Catalogo::categorias(),
            'habilidades' => Catalogo::habilidades(),
            'ciudadFundacion' => $this->usuario()['fundacion']['ciudad'],
        ]);
    }

    private function oportunidadPropia(string $id): array
    {
        $o = Oportunidad::porId($this->entero($id));
        if (!$o || (int) $o['idFundacion'] !== (int) $this->usuario()['fundacion']['idFundacion']) {
            throw new HttpException(404, 'La oportunidad no existe.');
        }
        return $o;
    }

    // ------------------------------------------------------------ postulaciones

    public function postulaciones(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        $this->vista('fundacion/postulaciones', [
            'titulo' => 'Postulaciones',
            'o' => $o,
            'postulaciones' => Postulacion::deOportunidad((int) $o['idOportunidad']),
        ]);
    }

    public function aceptar(string $id): void
    {
        PostulacionService::aceptar((int) $this->usuario()['idUsuario'], $this->entero($id));
        $this->exito('Postulación aceptada. Se notificó al voluntario.');
        back();
    }

    public function rechazar(string $id): void
    {
        PostulacionService::rechazar((int) $this->usuario()['idUsuario'], $this->entero($id));
        $this->exito('Postulación rechazada. Se notificó al voluntario.');
        back();
    }

    /** Ficha del voluntario: solo visible para fundaciones a las que se ha postulado. */
    public function voluntario(string $id): void
    {
        $idVoluntario = $this->entero($id);
        if (!Postulacion::hayRelacion((int) $this->usuario()['idUsuario'], $idVoluntario)) {
            throw new HttpException(403, 'Solo puedes ver el perfil de voluntarios que se postularon a tus oportunidades.');
        }
        $v = PerfilVoluntario::ficha($idVoluntario);
        if (!$v) {
            throw new HttpException(404);
        }
        $this->vista('fundacion/voluntario', ['titulo' => trim($v['nombre'] . ' ' . $v['apellido']), 'v' => $v]);
    }

    // ------------------------------------------------------------ participación

    public function participacion(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        $this->vista('fundacion/participacion', [
            'titulo' => 'Registrar participación',
            'o' => $o,
            'voluntarios' => Participacion::aceptadosDeOportunidad((int) $o['idOportunidad'], (int) $this->usuario()['idUsuario']),
            'horasSugeridas' => round((strtotime($o['horaFin']) - strtotime($o['horaInicio'])) / 3600, 2),
        ]);
    }

    public function guardarParticipacion(string $id): void
    {
        $o = $this->oportunidadPropia($id);
        $registros = (array) ($_POST['registro'] ?? []);
        if (!$registros) {
            \App\Core\Flash::set('warning', 'No hay voluntarios aceptados para registrar.');
            back();
        }
        $n = ParticipacionService::registrar($o, (int) $this->usuario()['idUsuario'], $registros);
        $this->exito("Se guardó la participación de $n voluntario(s). Ya pueden evaluarse mutuamente.");
        redirect("/fundacion/oportunidades/{$o['idOportunidad']}/participacion");
    }
}
