<?php use App\Core\View; $u = usuario_actual(); ?>
<div class="container">
    <div class="encabezado-pagina">
        <span class="sobretitulo"><?= e(fecha(date('Y-m-d'))) ?></span>
        <h1 class="mt-1">Hola, <?= e($u['nombre']) ?></h1>
        <p>Esto es lo que pasa con tu voluntariado.</p>
    </div>

    <?php if (!$u['perfil']): ?>
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><strong>Tu perfil está incompleto.</strong> Complétalo para postularte y recibir recomendaciones.</span>
            <a class="btn btn-sm btn-warning" href="<?= url('/perfil') ?>">Completar perfil</a>
        </div>
    <?php endif; ?>

    <div class="metricas mb-5">
        <div><strong><?= (int) $conteo['pendiente'] ?></strong><span>postulaciones esperando respuesta</span></div>
        <div><strong><?= (int) $conteo['aceptada'] ?></strong><span>postulaciones aceptadas</span></div>
        <div><strong><?= e(rtrim(horas($horas), ' h')) ?></strong><span>horas de voluntariado</span></div>
        <div><strong><?= $calificacion['promedio'] !== null ? number_format($calificacion['promedio'], 1, ',', '') : '—' ?></strong><span>calificación de las fundaciones</span></div>
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h2 class="titulo-seccion mb-0">Tus próximas actividades</h2>
                <a class="small fw-semibold" href="<?= url('/postulaciones') ?>">Mis postulaciones</a>
            </div>
            <?php if (!$proximas): ?>
                <p class="text-body-secondary">Cuando una fundación acepte tu postulación, la actividad aparecerá aquí.</p>
            <?php else: ?>
                <div class="lista-oport mb-2">
                    <?php foreach ($proximas as $p): ?>
                        <article class="oport">
                            <?= bloque_fecha($p['fechaInicio']) ?>
                            <div class="oport-cuerpo">
                                <p class="oport-cat"><span class="estado estado-aceptada">Aceptada</span></p>
                                <h3><a class="stretched-link" href="<?= url('/oportunidades/' . $p['Oportunidad_idOportunidad']) ?>"><?= e($p['titulo']) ?></a></h3>
                                <p class="oport-org"><?= e($p['razonSocial']) ?></p>
                                <p class="oport-meta"><span><?= e(hora($p['horaInicio'])) ?> a <?= e(hora($p['horaFin'])) ?></span><span><?= e($p['ciudad']) ?></span></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 class="titulo-seccion mt-5">Podrían interesarte</h2>
            <?php if (!$recomendadas): ?>
                <p class="text-body-secondary">No hay convocatorias abiertas por ahora.</p>
            <?php else: ?>
                <div class="lista-oport">
                    <?php foreach ($recomendadas as $o): ?>
                        <?php View::parcial('tarjeta_oportunidad', ['o' => $o, 'mostrarAfinidad' => true]); ?>
                    <?php endforeach; ?>
                </div>
                <a class="fw-semibold small d-inline-block mt-3" href="<?= url('/oportunidades') ?>">Ver todas las oportunidades →</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-5">
            <?php View::parcial('notificaciones_recientes', ['notificaciones' => $notificaciones]); ?>
        </div>
    </div>
</div>
