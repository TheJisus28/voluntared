<?php
/** @var string $contenido */
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;

$u = usuario_actual();
$rutaActual = '/' . ltrim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path_url())), '/');
$activo = static fn (string $prefijo) => ($prefijo === '/' ? $rutaActual === '/' : str_starts_with($rutaActual, $prefijo))
    ? ' active" aria-current="page' : '';

// Menú lateral según el rol: [grupo => [[ruta, ícono, texto, contador], ...]]
$menu = [];
if ($u && $u['rol'] === 'voluntario') {
    $menu = [
        'Explorar' => [['/oportunidades', 'bi-search', 'Oportunidades', null]],
        'Mi voluntariado' => [
            ['/panel', 'bi-house', 'Inicio', null],
            ['/postulaciones', 'bi-send', 'Mis postulaciones', null],
            ['/historial', 'bi-journal-check', 'Historial y certificado', null],
            ['/mensajes', 'bi-chat-left-text', 'Mensajes', 'mensajes'],
            ['/notificaciones', 'bi-bell', 'Notificaciones', 'notificaciones'],
        ],
        'Cuenta' => [['/perfil', 'bi-person', 'Mi perfil', null], ['/cuenta/contrasena', 'bi-key', 'Contraseña', null]],
    ];
} elseif ($u && $u['rol'] === 'fundacion') {
    $menu = [
        'Fundación' => [
            ['/panel', 'bi-house', 'Inicio', null],
            ['/fundacion/oportunidades', 'bi-calendar-event', 'Mis oportunidades', null],
            ['/mensajes', 'bi-chat-left-text', 'Mensajes', 'mensajes'],
            ['/notificaciones', 'bi-bell', 'Notificaciones', 'notificaciones'],
            ['/reportes', 'bi-file-earmark-text', 'Reportes', null],
        ],
        'Cuenta' => [['/fundacion/perfil', 'bi-building', 'Perfil de la fundación', null], ['/cuenta/contrasena', 'bi-key', 'Contraseña', null]],
    ];
} elseif ($u) {
    $menu = [
        'Administración' => [
            ['/panel', 'bi-house', 'Inicio', null],
            ['/admin/fundaciones', 'bi-patch-check', 'Fundaciones', null],
            ['/admin/usuarios', 'bi-people', 'Usuarios', null],
            ['/admin/moderacion', 'bi-shield-check', 'Moderación', null],
            ['/admin/catalogos', 'bi-tags', 'Categorías y habilidades', null],
        ],
        'Seguimiento' => [
            ['/admin/estadisticas', 'bi-graph-up', 'Estadísticas', null],
            ['/admin/reportes', 'bi-file-earmark-text', 'Reportes', null],
            ['/notificaciones', 'bi-bell', 'Notificaciones', 'notificaciones'],
        ],
        'Cuenta' => [['/cuenta/contrasena', 'bi-key', 'Contraseña', null]],
    ];
}
$nombreVisible = $u ? ($u['rol'] === 'fundacion' ? ($u['fundacion']['razonSocial'] ?? $u['nombre']) : trim($u['nombre'] . ' ' . $u['apellido'])) : '';
$marca = '<img src="' . asset('img/logo.svg') . '" alt=""><span>Volunta<em>Red</em></span>';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="VoluntaRed: plataforma digital para la vinculación entre voluntarios y fundaciones.">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <meta name="base-url" content="<?= e(base_path_url()) ?>">
    <meta name="theme-color" content="#f5f2ea">
    <title><?= e(($titulo ?? '') ? $titulo . ' · VoluntaRed' : 'VoluntaRed') ?></title>
    <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="<?= base_path_url() ?>/assets/fonts/fraunces-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= base_path_url() ?>/assets/fonts/public-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body<?= $u ? ' data-autenticado="1"' : '' ?>>
<a class="saltar-contenido" href="#contenido">Saltar al contenido principal</a>

<?php ob_start(); ?>
<?php if (Flash::mensajes()): ?>
    <div class="container pt-3" aria-live="polite">
        <?php foreach (Flash::mensajes() as $m): ?>
            <div class="alert alert-<?= e($m['tipo']) ?> alert-dismissible fade show" role="<?= $m['tipo'] === 'danger' ? 'alert' : 'status' ?>">
                <?= e($m['texto']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $mensajesFlash = ob_get_clean(); ?>

<?php if ($u): ?>
<div class="shell">
    <aside class="lateral offcanvas-lg offcanvas-start" tabindex="-1" id="lateral" aria-label="Menú principal">
        <div class="offcanvas-header d-lg-none border-bottom">
            <a class="marca" href="<?= url('/panel') ?>"><?= $marca ?></a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#lateral" aria-label="Cerrar menú"></button>
        </div>
        <div class="offcanvas-body lateral-cuerpo">
            <a class="marca d-none d-lg-inline-flex px-2 mb-1" href="<?= url('/panel') ?>"><?= $marca ?></a>
            <?php foreach ($menu as $grupo => $enlaces): ?>
                <div class="grupo"><?= e($grupo) ?></div>
                <nav aria-label="<?= e($grupo) ?>">
                    <?php foreach ($enlaces as [$ruta, $icono, $texto, $contador]): ?>
                        <a class="enlace<?= $activo($ruta) ?>" href="<?= url($ruta) ?>">
                            <i class="bi <?= $icono ?>" aria-hidden="true"></i><span><?= e($texto) ?></span>
                            <?php if ($contador): ?><span class="badge rounded-pill text-bg-danger d-none" data-contador="<?= $contador ?>"></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($grupo === 'Cuenta'): ?>
                        <form method="post" action="<?= url('/salir') ?>" class="m-0" data-confirmar="¿Seguro que deseas cerrar sesión?" data-confirmar-boton="Cerrar sesión">
                            <?= csrf_field() ?>
                            <button class="enlace w-100 border-0 bg-transparent text-start"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Cerrar sesión</span></button>
                        </form>
                    <?php endif; ?>
                </nav>
            <?php endforeach; ?>
            <div class="usuario">
                <?= avatar($u['foto'], $u['rol'] === 'fundacion' ? $nombreVisible : $u['nombre'], $u['rol'] === 'fundacion' ? null : $u['apellido'], 'sm') ?>
                <div class="overflow-hidden">
                    <div class="nombre text-truncate"><?= e($nombreVisible) ?></div>
                    <div class="rol text-truncate"><?= e(etiqueta('rol', $u['rol'])) ?> · <?= e($u['email']) ?></div>
                </div>
            </div>
        </div>
    </aside>

    <div class="principal">
        <header class="barra-superior">
            <button class="icono-barra d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#lateral" aria-controls="lateral" aria-label="Abrir menú">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <a class="marca d-lg-none" href="<?= url('/panel') ?>"><?= $marca ?></a>
            <div class="ms-auto d-flex align-items-center gap-1">
                <?php if ($u['rol'] === 'voluntario'): ?>
                    <a class="btn btn-sm btn-light d-none d-md-inline-flex align-items-center gap-1 me-2" href="<?= url('/oportunidades') ?>"><i class="bi bi-search" aria-hidden="true"></i>Buscar oportunidades</a>
                <?php elseif ($u['rol'] === 'fundacion' && ($u['fundacion']['estadoVerificacion'] ?? '') === 'verificada'): ?>
                    <a class="btn btn-sm btn-primary d-none d-md-inline-flex align-items-center gap-1 me-2" href="<?= url('/fundacion/oportunidades/nueva') ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i>Nueva oportunidad</a>
                <?php endif; ?>
                <?php if ($u['rol'] !== 'administrador'): ?>
                    <a class="icono-barra<?= $activo('/mensajes') ?>" href="<?= url('/mensajes') ?>" title="Mensajes">
                        <i class="bi bi-chat-left-text" aria-hidden="true"></i><span class="visually-hidden">Mensajes</span>
                        <span class="badge rounded-pill text-bg-danger contador d-none" data-contador="mensajes"></span>
                    </a>
                <?php endif; ?>
                <div class="dropdown">
                    <a class="icono-barra" href="<?= url('/notificaciones') ?>" id="campana" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Notificaciones">
                        <i class="bi bi-bell" aria-hidden="true"></i><span class="visually-hidden">Notificaciones</span>
                        <span class="badge rounded-pill text-bg-danger contador d-none" data-contador="notificaciones"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-notificaciones" aria-labelledby="campana">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Notificaciones</strong>
                            <form method="post" action="<?= url('/notificaciones/leer-todas') ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button class="btn btn-link btn-sm p-0">Marcar todas como leídas</button>
                            </form>
                        </div>
                        <div id="lista-notificaciones" aria-live="polite"><div class="p-3 text-center text-body-secondary small">Cargando…</div></div>
                        <a class="d-block text-center py-2 small fw-semibold" href="<?= url('/notificaciones') ?>">Ver todas</a>
                    </div>
                </div>
                <a class="ms-1" href="<?= url($u['rol'] === 'fundacion' ? '/fundacion/perfil' : ($u['rol'] === 'voluntario' ? '/perfil' : '/panel')) ?>" title="<?= e($nombreVisible) ?>">
                    <?= avatar($u['foto'], $u['rol'] === 'fundacion' ? $nombreVisible : $u['nombre'], $u['rol'] === 'fundacion' ? null : $u['apellido'], 'sm') ?>
                </a>
            </div>
        </header>
        <main id="contenido" tabindex="-1">
            <?= $mensajesFlash ?>
            <?= $contenido ?>
        </main>
    </div>
</div>
<?php else: ?>
<nav class="navbar navbar-expand-lg nav-publica" aria-label="Navegación principal">
    <div class="container">
        <a class="marca navbar-brand me-4" href="<?= url('/') ?>"><?= $marca ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $activo('/oportunidades') ?>" href="<?= url('/oportunidades') ?>">Oportunidades</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>#como-funciona">Cómo funciona</a></li>
                <li class="nav-item"><a class="nav-link<?= $activo('/registro/fundacion') ?>" href="<?= url('/registro/fundacion') ?>">Para fundaciones</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                <a class="nav-link fw-semibold px-2" href="<?= url('/login') ?>">Iniciar sesión</a>
                <a class="btn btn-primary" href="<?= url('/registro') ?>">Crear cuenta</a>
            </div>
        </div>
    </div>
</nav>

<main id="contenido" tabindex="-1">
    <?= $mensajesFlash ?>
    <?= $contenido ?>
</main>

<footer class="pie py-4 mt-5">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <a class="marca" href="<?= url('/') ?>"><?= $marca ?></a>
            <div class="small mt-1">Facultad de Ingeniería · Universidad de Córdoba · Montería, <?= date('Y') ?></div>
        </div>
        <div class="small d-flex flex-wrap gap-3">
            <a href="<?= url('/oportunidades') ?>">Oportunidades</a>
            <a href="<?= url('/registro') ?>">Crear cuenta</a>
            <a href="<?= url('/privacidad') ?>">Tratamiento de datos (Ley 1581 de 2012)</a>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php View::parcial('modal_confirmar'); ?>

<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($usaGraficos)): ?>
    <script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
