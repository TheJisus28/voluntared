<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\ReglaNegocioException;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Fundacion;
use App\Models\Usuario;
use App\Services\AuthService;

/** Casos de uso Registrarse, Iniciar sesión, Restablecer contraseña y Cerrar sesión. */
final class AuthController extends Controller
{
    public function registro(): void
    {
        $this->vista('auth/registro', ['titulo' => 'Crear cuenta']);
    }

    public function formVoluntario(): void
    {
        $this->vista('auth/registro_voluntario', ['titulo' => 'Registro de voluntario']);
    }

    public function registrarVoluntario(): void
    {
        $datos = $_POST;
        $errores = Validator::validar($datos, [
            'nombre' => 'requerido|max:100',
            'apellido' => 'requerido|max:100',
            'email' => 'requerido|email|max:150',
            'contrasena' => 'requerido|contrasena|max:72',
            'contrasena_confirmacion' => 'requerido|igual:contrasena',
            'acepta' => 'acepta',
        ]);
        if (!isset($errores['email']) && Usuario::emailExiste($datos['email'])) {
            $errores['email'] = 'Ya existe una cuenta con este correo electrónico.';
        }
        if ($errores) {
            $this->volverConErrores($errores, $datos);
        }
        AuthService::registrarVoluntario(array_map('trim', array_intersect_key($datos, array_flip(['nombre', 'apellido', 'email']))) + ['contrasena' => $datos['contrasena']]);
        redirect('/registro/enviado', ['email' => mb_strtolower(trim($datos['email']))]);
    }

    public function formFundacion(): void
    {
        $this->vista('auth/registro_fundacion', ['titulo' => 'Registro de fundación']);
    }

    public function registrarFundacion(): void
    {
        $datos = $_POST;
        $errores = Validator::validar($datos, [
            'razonSocial' => 'requerido|max:150',
            'nit' => 'requerido|nit',
            'descripcion' => 'requerido|min:30|max:3000',
            'direccion' => 'requerido|max:150',
            'ciudad' => 'requerido|max:80',
            'nombre' => 'requerido|max:100',
            'apellido' => 'requerido|max:100',
            'telefono' => 'requerido|telefono',
            'email' => 'requerido|email|max:150',
            'contrasena' => 'requerido|contrasena|max:72',
            'contrasena_confirmacion' => 'requerido|igual:contrasena',
            'acepta' => 'acepta',
        ]);
        if (!isset($errores['email']) && Usuario::emailExiste($datos['email'])) {
            $errores['email'] = 'Ya existe una cuenta con este correo electrónico.';
        }
        if (!isset($errores['nit']) && Fundacion::nitExiste(trim($datos['nit']))) {
            $errores['nit'] = 'Ya hay una fundación registrada con este NIT.';
        }
        $errorArchivo = Upload::validar($_FILES['documento'] ?? null, Upload::DOCUMENTOS);
        if ($errorArchivo) {
            $errores['documento'] = $errorArchivo;
        }
        if ($errores) {
            $this->volverConErrores($errores, $datos);
        }
        $ruta = Upload::guardar($_FILES['documento'], Upload::DOCUMENTOS, 'documentos', false);
        $limpios = array_map('trim', array_intersect_key($datos, array_flip(
            ['razonSocial', 'nit', 'descripcion', 'direccion', 'ciudad', 'nombre', 'apellido', 'telefono', 'email']
        )));
        AuthService::registrarFundacion($limpios + ['contrasena' => $datos['contrasena']], $ruta);
        redirect('/registro/enviado', ['email' => mb_strtolower(trim($datos['email'])), 'tipo' => 'fundacion']);
    }

    public function enviado(): void
    {
        $this->vista('auth/enviado', [
            'titulo' => 'Revisa tu correo',
            'email' => (string) ($_GET['email'] ?? ''),
            'tipo' => $_GET['tipo'] ?? 'voluntario',
        ]);
    }

    public function verificar(): void
    {
        try {
            $u = AuthService::verificarCorreo((int) ($_GET['u'] ?? 0), (int) ($_GET['e'] ?? 0), (string) ($_GET['f'] ?? ''));
        } catch (ReglaNegocioException $e) {
            Flash::set('danger', $e->getMessage());
            redirect('/verificar/reenviar');
        }
        $mensaje = $u['rol'] === 'fundacion'
            ? 'Tu correo fue confirmado. Inicia sesión: mientras el administrador revisa tu documento podrás completar el perfil de la fundación.'
            : '¡Tu cuenta está activa! Inicia sesión y completa tu perfil para empezar a postularte.';
        Flash::set('success', $mensaje);
        redirect('/login', ['email' => $u['email']]);
    }

    public function formReenviar(): void
    {
        $this->vista('auth/reenviar', ['titulo' => 'Reenviar verificación']);
    }

    public function reenviar(): void
    {
        $this->validar($_POST, ['email' => 'requerido|email']);
        $u = Usuario::porEmail($_POST['email']);
        if ($u && !$u['emailVerificado']) {
            AuthService::enviarVerificacion((int) $u['idUsuario']);
        }
        Flash::set('success', 'Si el correo está registrado y pendiente de verificación, te enviamos un nuevo enlace.');
        redirect('/login');
    }

    public function formLogin(): void
    {
        $this->vista('auth/login', ['titulo' => 'Iniciar sesión']);
    }

    public function login(): void
    {
        $this->validar($_POST, ['email' => 'requerido|email', 'contrasena' => 'requerido']);
        try {
            $u = AuthService::autenticar($_POST['email'], $_POST['contrasena']);
        } catch (ReglaNegocioException $e) {
            Flash::conErrores([], ['email' => $_POST['email']]);
            Flash::set('danger', $e->getMessage());
            redirect('/login');
        }
        session_regenerate_id(true);
        Auth::iniciarSesion($u);
        $volver = (string) ($_POST['volver'] ?? '');
        $base = base_path_url();
        if ($volver !== '' && str_starts_with($volver, $base . '/') && !str_starts_with($volver, '//')) {
            header('Location: ' . $volver);
            exit;
        }
        if ($u['rol'] === 'voluntario' && !\App\Models\PerfilVoluntario::porUsuario((int) $u['idUsuario'])) {
            Flash::set('info', '¡Bienvenido! Completa tu perfil para que las fundaciones te conozcan y recibir recomendaciones.');
            redirect('/perfil');
        }
        redirect('/panel');
    }

    /**
     * «Continuar con Google»: recibe el ID token de Firebase (JSON) y responde a dónde ir.
     * La ventana de Google la abre el SDK de Firebase en el navegador (google-login.js).
     */
    public function google(): void
    {
        if (!\App\Services\FirebaseAuthService::habilitado()) {
            $this->json(['error' => 'El inicio de sesión con Google no está configurado.'], 404);
            return;
        }
        $datos = json_decode(file_get_contents('php://input') ?: '', true);
        $token = is_array($datos) ? (string) ($datos['idToken'] ?? '') : '';
        if ($token === '') {
            $this->json(['error' => 'Falta el token de Google.'], 422);
            return;
        }
        try {
            $google = \App\Services\FirebaseAuthService::verificarToken($token);
            [$u, $nueva] = \App\Services\FirebaseAuthService::entrar($google);
        } catch (ReglaNegocioException $e) {
            $this->json(['error' => $e->getMessage()], 422);
            return;
        }
        session_regenerate_id(true);
        Auth::iniciarSesion($u);
        if ($nueva) {
            Flash::set('success', '¡Bienvenido a VoluntaRed! Creamos tu cuenta de voluntario con Google. Completa tu perfil para empezar a postularte.');
            $destino = '/perfil';
        } elseif ($u['rol'] === 'voluntario' && !\App\Models\PerfilVoluntario::porUsuario((int) $u['idUsuario'])) {
            Flash::set('info', 'Completa tu perfil para que las fundaciones te conozcan y recibir recomendaciones.');
            $destino = '/perfil';
        } else {
            $destino = '/panel';
        }
        $this->json(['redirect' => url($destino)]);
    }

    public function formRecuperar(): void
    {
        $this->vista('auth/recuperar', ['titulo' => 'Recuperar contraseña']);
    }

    public function recuperar(): void
    {
        $this->validar($_POST, ['email' => 'requerido|email']);
        AuthService::solicitarCodigo($_POST['email']);
        Flash::set('success', 'Si el correo está registrado, te enviamos un código de 6 dígitos válido por '
            . config('seguridad.minutos_codigo') . ' minutos.');
        redirect('/restablecer', ['email' => mb_strtolower(trim($_POST['email']))]);
    }

    public function formRestablecer(): void
    {
        $this->vista('auth/restablecer', ['titulo' => 'Nueva contraseña', 'email' => (string) ($_GET['email'] ?? '')]);
    }

    public function restablecer(): void
    {
        $this->validar($_POST, [
            'email' => 'requerido|email',
            'codigo' => 'requerido|min:6|max:6',
            'contrasena' => 'requerido|contrasena|max:72',
            'contrasena_confirmacion' => 'requerido|igual:contrasena',
        ]);
        try {
            AuthService::restablecer($_POST['email'], trim($_POST['codigo']), $_POST['contrasena']);
        } catch (ReglaNegocioException $e) {
            Flash::conErrores(['codigo' => $e->getMessage()], ['email' => $_POST['email']]);
            back();
        }
        Flash::set('success', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.');
        redirect('/login', ['email' => mb_strtolower(trim($_POST['email']))]);
    }

    public function confirmarSalir(): void
    {
        $this->vista('auth/salir', ['titulo' => 'Cerrar sesión']);
    }

    public function salir(): void
    {
        Auth::cerrarSesion();
        session_regenerate_id(true);
        Flash::set('success', 'Cerraste sesión correctamente. ¡Hasta pronto!');
        redirect('/');
    }

    public function formContrasena(): void
    {
        $this->vista('auth/contrasena', ['titulo' => 'Cambiar contraseña']);
    }

    public function cambiarContrasena(): void
    {
        $u = $this->usuario();
        $this->validar($_POST, [
            'actual' => 'requerido',
            'contrasena' => 'requerido|contrasena|max:72',
            'contrasena_confirmacion' => 'requerido|igual:contrasena',
        ]);
        $registro = Usuario::buscarConRol((int) $u['idUsuario']);
        if (!password_verify($_POST['actual'], $registro['contrasena'])) {
            $this->volverConErrores(['actual' => 'La contraseña actual no es correcta.'], []);
        }
        Usuario::cambiarContrasena((int) $u['idUsuario'], $_POST['contrasena']);
        $this->exito('Contraseña cambiada. No la olvides.');
        redirect('/panel');
    }
}
