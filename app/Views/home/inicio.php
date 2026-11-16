<?php
$u = usuario_actual();
$numero = static fn ($n) => number_format((float) $n, 0, ',', '.');
?>
<section class="portada">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-7">
                <span class="sobretitulo">Montería · Córdoba · Colombia</span>
                <h1>Tu tiempo, donde <em>más se necesita.</em></h1>
                <p class="lead mb-4">VoluntaRed conecta a personas que quieren ayudar con fundaciones verificadas de la región. Encuentra una actividad, postúlate en dos pasos y acumula horas que puedes certificar.</p>
                <form class="buscador" action="<?= url('/oportunidades') ?>" method="get" role="search">
                    <label for="busqueda-inicio" class="visually-hidden">Buscar oportunidades</label>
                    <input id="busqueda-inicio" type="search" name="q" placeholder="Reciclaje, lectura, adultos mayores…">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                </form>
                <div class="cifras-linea" aria-label="VoluntaRed en cifras">
                    <span><strong><?= $numero($cifras['abiertas']) ?></strong>convocatorias abiertas</span>
                    <span><strong><?= $numero($cifras['fundaciones']) ?></strong>fundaciones verificadas</span>
                    <span><strong><?= $numero($cifras['horas']) ?></strong>horas de voluntariado</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="agenda">
                    <h2><?= !empty($u['perfil']) ? 'Pensadas para ti' : 'Próximas actividades' ?></h2>
                    <p class="small text-body-secondary mb-2">Convocatorias con cupos disponibles.</p>
                    <?php if (!$proximas): ?>
                        <p class="text-body-secondary mb-0">No hay convocatorias abiertas por ahora.</p>
                    <?php endif; ?>
                    <?php foreach ($proximas as $o): ?>
                        <div class="agenda-item">
                            <?= bloque_fecha($o['fechaInicio']) ?>
                            <div class="min-w-0">
                                <a class="stretched-link" href="<?= url('/oportunidades/' . $o['idOportunidad']) ?>"><?= e($o['titulo']) ?></a>
                                <div class="small text-body-secondary"><?= e($o['razonSocial']) ?> · <?= e($o['ciudad']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a class="fw-semibold small d-inline-block mt-2" href="<?= url('/oportunidades') ?>">Ver todas las oportunidades →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4 align-items-end mb-3">
        <div class="col-md-6"><h2 class="mb-0">Causas que puedes apoyar</h2></div>
        <div class="col-md-6 text-md-end text-body-secondary">Elige una para ver sus convocatorias.</div>
    </div>
    <nav class="indice-causas" aria-label="Causas">
        <?php foreach ($causas as $c): ?>
            <a href="<?= url('/oportunidades', ['categoria' => $c['idCategoria']]) ?>">
                <span><?= e($c['nombre']) ?></span>
                <span><?= (int) $c['abiertas'] === 0 ? 'sin convocatorias' : (int) $c['abiertas'] . ' abierta' . ((int) $c['abiertas'] === 1 ? '' : 's') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</section>

<section class="container pb-5" id="como-funciona">
    <h2 class="mb-4">Cómo funciona</h2>
    <div class="row g-5">
        <div class="col-md-6">
            <h3 class="h5">Si quieres ayudar</h3>
            <ol class="pasos">
                <li><span>Crea tu cuenta con tu correo o con Google.</span></li>
                <li><span>Cuéntanos qué causas te mueven, qué sabes hacer y cuándo tienes tiempo.</span></li>
                <li><span>Postúlate a una actividad y conversa con la fundación si tienes dudas.</span></li>
                <li><span>Participa: la fundación registra tus horas y puedes descargar tu certificado.</span></li>
            </ol>
        </div>
        <div class="col-md-6">
            <h3 class="h5">Si eres una fundación</h3>
            <ol class="pasos">
                <li><span>Registra tu organización con el RUT o el certificado de existencia.</span></li>
                <li><span>Un administrador verifica tus datos; luego puedes publicar.</span></li>
                <li><span>Publica convocatorias con fechas, horario, lugar y cupos.</span></li>
                <li><span>Acepta voluntarios, registra la asistencia y descarga reportes en PDF o Excel.</span></li>
            </ol>
        </div>
    </div>
</section>

<?php if ($testimonio): ?>
    <section class="container pb-5">
        <figure class="border-top pt-5 mb-0" style="max-width: 52rem;">
            <blockquote class="cita mb-0">«<?= e($testimonio['comentario']) ?>»</blockquote>
            <figcaption class="cita-autor"><?= e($testimonio['nombre'] . ' ' . mb_substr((string) $testimonio['apellido'], 0, 1) . '.') ?> · participó en «<?= e($testimonio['titulo']) ?>» con <?= e($testimonio['razonSocial']) ?></figcaption>
        </figure>
    </section>
<?php endif; ?>

<?php if (!$u): ?>
    <section class="container pb-4">
        <div class="franja d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="mb-1">¿Representas una fundación?</h2>
                <p class="mb-0">Publica tus convocatorias y gestiona a tus voluntarios en un solo lugar.</p>
            </div>
            <a class="btn btn-light btn-lg" href="<?= url('/registro/fundacion') ?>">Registrar mi fundación</a>
        </div>
    </section>
<?php endif; ?>
