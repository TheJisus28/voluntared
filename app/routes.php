<?php
/**
 * Definición de rutas de VoluntaRed.
 * @var \App\Core\Router $router
 */

use App\Controllers\AdminController;
use App\Controllers\Api\ApiController;
use App\Controllers\AuthController;
use App\Controllers\DevController;
use App\Controllers\EvaluacionController;
use App\Controllers\FundacionController;
use App\Controllers\HomeController;
use App\Controllers\MensajeController;
use App\Controllers\NotificacionController;
use App\Controllers\OportunidadController;
use App\Controllers\PanelController;
use App\Controllers\ReporteController;
use App\Controllers\VoluntarioController;

// ---------------------------------------------------------------- públicas
$router->get('/', [HomeController::class, 'inicio']);
$router->get('/privacidad', [HomeController::class, 'privacidad']);
$router->get('/oportunidades', [OportunidadController::class, 'index']);
$router->get('/oportunidades/{id}', [OportunidadController::class, 'ver']);
$router->get('/fundaciones/{id}', [OportunidadController::class, 'fundacion']);

// ---------------------------------------------------------------- autenticación (RF1, RF2)
$router->get('/registro', [AuthController::class, 'registro'], ['invitado']);
$router->get('/registro/voluntario', [AuthController::class, 'formVoluntario'], ['invitado']);
$router->post('/registro/voluntario', [AuthController::class, 'registrarVoluntario'], ['invitado']);
$router->get('/registro/fundacion', [AuthController::class, 'formFundacion'], ['invitado']);
$router->post('/registro/fundacion', [AuthController::class, 'registrarFundacion'], ['invitado']);
$router->get('/registro/enviado', [AuthController::class, 'enviado']);
$router->get('/verificar', [AuthController::class, 'verificar']);
$router->get('/verificar/reenviar', [AuthController::class, 'formReenviar']);
$router->post('/verificar/reenviar', [AuthController::class, 'reenviar']);
$router->get('/login', [AuthController::class, 'formLogin'], ['invitado']);
$router->post('/login', [AuthController::class, 'login'], ['invitado']);
$router->post('/auth/google', [AuthController::class, 'google'], ['invitado']);
$router->get('/recuperar', [AuthController::class, 'formRecuperar'], ['invitado']);
$router->post('/recuperar', [AuthController::class, 'recuperar'], ['invitado']);
$router->get('/restablecer', [AuthController::class, 'formRestablecer'], ['invitado']);
$router->post('/restablecer', [AuthController::class, 'restablecer'], ['invitado']);
$router->get('/salir', [AuthController::class, 'confirmarSalir'], ['auth']);
$router->post('/salir', [AuthController::class, 'salir'], ['auth']);
$router->get('/cuenta/contrasena', [AuthController::class, 'formContrasena'], ['auth']);
$router->post('/cuenta/contrasena', [AuthController::class, 'cambiarContrasena'], ['auth']);

$router->get('/panel', [PanelController::class, 'index'], ['auth']);

// ---------------------------------------------------------------- voluntario (RF3, RF8, RF13)
$soloVoluntario = ['auth', 'rol:voluntario'];
$router->get('/perfil', [VoluntarioController::class, 'perfil'], $soloVoluntario);
$router->post('/perfil', [VoluntarioController::class, 'guardarPerfil'], $soloVoluntario);
$router->post('/oportunidades/{id}/postular', [VoluntarioController::class, 'postular'], $soloVoluntario);
$router->get('/postulaciones', [VoluntarioController::class, 'postulaciones'], $soloVoluntario);
$router->post('/postulaciones/{id}/cancelar', [VoluntarioController::class, 'cancelar'], $soloVoluntario);
$router->get('/historial', [VoluntarioController::class, 'historial'], $soloVoluntario);
$router->get('/historial/certificado', [VoluntarioController::class, 'certificado'], $soloVoluntario);

// ---------------------------------------------------------------- fundación (RF4, RF6, RF9, RF12, RF15)
$soloFundacion = ['auth', 'rol:fundacion'];
$verificada = ['auth', 'rol:fundacion', 'verificada'];
$router->get('/fundacion/perfil', [FundacionController::class, 'perfil'], $soloFundacion);
$router->post('/fundacion/perfil', [FundacionController::class, 'guardarPerfil'], $soloFundacion);
$router->get('/fundacion/documento', [FundacionController::class, 'documento'], $soloFundacion);
$router->get('/fundacion/oportunidades', [FundacionController::class, 'oportunidades'], $verificada);
$router->get('/fundacion/oportunidades/nueva', [FundacionController::class, 'nueva'], $verificada);
$router->post('/fundacion/oportunidades', [FundacionController::class, 'crear'], $verificada);
$router->get('/fundacion/oportunidades/{id}/editar', [FundacionController::class, 'editar'], $verificada);
$router->post('/fundacion/oportunidades/{id}', [FundacionController::class, 'actualizar'], $verificada);
$router->post('/fundacion/oportunidades/{id}/estado', [FundacionController::class, 'cambiarEstado'], $verificada);
$router->get('/fundacion/oportunidades/{id}/postulaciones', [FundacionController::class, 'postulaciones'], $verificada);
$router->post('/fundacion/postulaciones/{id}/aceptar', [FundacionController::class, 'aceptar'], $verificada);
$router->post('/fundacion/postulaciones/{id}/rechazar', [FundacionController::class, 'rechazar'], $verificada);
$router->get('/fundacion/voluntarios/{id}', [FundacionController::class, 'voluntario'], $verificada);
$router->get('/fundacion/oportunidades/{id}/participacion', [FundacionController::class, 'participacion'], $verificada);
$router->post('/fundacion/oportunidades/{id}/participacion', [FundacionController::class, 'guardarParticipacion'], $verificada);
$router->get('/reportes', [ReporteController::class, 'fundacion'], $verificada);

// ---------------------------------------------------------------- compartidas voluntario / fundación
$participantes = ['auth', 'rol:voluntario,fundacion'];
$router->get('/evaluar/{id}', [EvaluacionController::class, 'formulario'], $participantes);
$router->post('/evaluar/{id}', [EvaluacionController::class, 'guardar'], $participantes);
$router->get('/mensajes', [MensajeController::class, 'index'], $participantes);
$router->post('/mensajes/iniciar', [MensajeController::class, 'iniciar'], $participantes);
$router->get('/mensajes/{id}', [MensajeController::class, 'ver'], $participantes);
$router->post('/mensajes/{id}', [MensajeController::class, 'enviar'], $participantes);

// ---------------------------------------------------------------- notificaciones (RF11)
$router->get('/notificaciones', [NotificacionController::class, 'index'], ['auth']);
$router->get('/notificaciones/{id}/abrir', [NotificacionController::class, 'abrir'], ['auth']);
$router->post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'], ['auth']);

// ---------------------------------------------------------------- administrador (RF5, RF14–RF16)
$admin = ['auth', 'rol:administrador'];
$router->get('/admin/estadisticas', [AdminController::class, 'estadisticas'], $admin);
$router->get('/admin/usuarios', [AdminController::class, 'usuarios'], $admin);
$router->post('/admin/usuarios/{id}/suspender', [AdminController::class, 'suspender'], $admin);
$router->post('/admin/usuarios/{id}/reactivar', [AdminController::class, 'reactivar'], $admin);
$router->get('/admin/fundaciones', [AdminController::class, 'fundaciones'], $admin);
$router->get('/admin/fundaciones/{id}', [AdminController::class, 'fundacion'], $admin);
$router->get('/admin/fundaciones/{id}/documento', [AdminController::class, 'documento'], $admin);
$router->post('/admin/fundaciones/{id}/verificar', [AdminController::class, 'verificar'], $admin);
$router->get('/admin/catalogos', [AdminController::class, 'catalogos'], $admin);
$router->post('/admin/catalogos/{tipo}', [AdminController::class, 'guardarCatalogo'], $admin);
$router->post('/admin/catalogos/{tipo}/{id}', [AdminController::class, 'guardarCatalogo'], $admin);
$router->post('/admin/catalogos/{tipo}/{id}/eliminar', [AdminController::class, 'eliminarCatalogo'], $admin);
$router->get('/admin/moderacion', [AdminController::class, 'moderacion'], $admin);
$router->post('/admin/moderacion/oportunidades/{id}', [AdminController::class, 'moderarOportunidad'], $admin);
$router->post('/admin/moderacion/evaluaciones/{id}', [AdminController::class, 'moderarEvaluacion'], $admin);
$router->get('/admin/reportes', [ReporteController::class, 'admin'], $admin);

// ---------------------------------------------------------------- bandeja de correos (solo entorno local)
$router->get('/dev/correos', [DevController::class, 'correos']);
$router->get('/dev/correos/{id}', [DevController::class, 'correo']);

// ---------------------------------------------------------------- API REST (JSON)
$router->post('/api/auth/login', [ApiController::class, 'login'], ['api']);
$router->post('/api/auth/logout', [ApiController::class, 'logout'], ['api', 'auth']);
$router->get('/api/oportunidades', [ApiController::class, 'oportunidades'], ['api']);
$router->get('/api/oportunidades/{id}', [ApiController::class, 'oportunidad'], ['api']);
$router->post('/api/oportunidades/{id}/postulaciones', [ApiController::class, 'postular'], ['api', 'auth', 'rol:voluntario']);
$router->get('/api/contadores', [ApiController::class, 'contadores'], ['api', 'auth']);
$router->get('/api/notificaciones', [ApiController::class, 'notificaciones'], ['api', 'auth']);
$router->post('/api/notificaciones/{id}/leer', [ApiController::class, 'leerNotificacion'], ['api', 'auth']);
$router->get('/api/conversaciones/{id}/mensajes', [ApiController::class, 'mensajes'], ['api', 'auth', 'rol:voluntario,fundacion']);
$router->post('/api/conversaciones/{id}/mensajes', [ApiController::class, 'enviarMensaje'], ['api', 'auth', 'rol:voluntario,fundacion']);
$router->get('/api/estadisticas', [ApiController::class, 'estadisticas'], ['api', 'auth', 'rol:administrador']);
