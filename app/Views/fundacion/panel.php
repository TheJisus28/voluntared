<?php use App\Core\View; $verificada = $f['estadoVerificacion'] === 'verificada'; ?>
<div class="container">
    <div class="encabezado-pagina">
        <span class="sobretitulo">Panel de la fundación</span>
        <h1 class="mt-1"><?= e($f['razonSocial']) ?></h1>
        <p><?= $verificada ? 'Fundación verificada · ' . e($f['ciudad']) : 'Registro en revisión' ?></p>
    </div>

    <?php View::parcial('estado_fundacion', ['f' => $f]); ?>

    <?php if ($verificada): ?>
        <div class="metricas mb-5">
            <div><strong><?= (int) $resumen['abiertas'] ?></strong><span>convocatorias abiertas</span></div>
            <div><strong><?= (int) $resumen['pendientes'] ?></strong><span>postulaciones por revisar</span></div>
            <div><strong><?= e(rtrim(horas($resumen['horas']), ' h')) ?></strong><span>horas de voluntariado registradas</span></div>
            <div><strong><?= $calificacion['promedio'] !== null ? number_format($calificacion['promedio'], 1, ',', '') : '—' ?></strong><span>calificación de los voluntarios (<?= $calificacion['total'] ?>)</span></div>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <div class="col-lg-7">
            <?php if ($verificada): ?>
                <?php if ($porRegistrar): ?>
                    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span><?= count($porRegistrar) ?> actividad(es) finalizada(s) sin registrar la participación.</span>
                        <a class="btn btn-sm btn-primary" href="<?= url('/fundacion/oportunidades/' . reset($porRegistrar)['idOportunidad'] . '/participacion') ?>">Registrar ahora</a>
                    </div>
                <?php endif; ?>
                <h2 class="titulo-seccion">Postulaciones por revisar</h2>
                <?php if (!$pendientes): ?>
                    <p class="text-body-secondary">No hay postulaciones pendientes.</p>
                <?php else: ?>
                    <div class="card-vr px-4 py-2 mb-5">
                        <ul class="lista-simple">
                            <?php foreach ($pendientes as $p): ?>
                                <li class="d-flex align-items-center gap-3 py-3">
                                    <?= avatar($p['foto'], $p['nombre'], $p['apellido'], 'sm') ?>
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></span>
                                        <div class="small text-body-secondary"><?= e($p['titulo']) ?> · <?= e(hace($p['fechaPostulacion'])) ?></div>
                                    </div>
                                    <a class="btn btn-sm btn-light" href="<?= url('/fundacion/oportunidades/' . $p['Oportunidad_idOportunidad'] . '/postulaciones') ?>">Revisar</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <h2 class="titulo-seccion mb-0">Convocatorias abiertas</h2>
                    <a class="small fw-semibold" href="<?= url('/fundacion/oportunidades') ?>">Ver todas</a>
                </div>
                <?php if (!$activas): ?>
                    <p class="text-body-secondary">No tienes convocatorias abiertas. <a href="<?= url('/fundacion/oportunidades/nueva') ?>">Publica una</a>.</p>
                <?php else: ?>
                    <div class="lista-oport">
                        <?php foreach ($activas as $o): ?>
                            <article class="oport">
                                <?= bloque_fecha($o['fechaInicio']) ?>
                                <div class="oport-cuerpo">
                                    <p class="oport-cat"><span><?= e($o['categoria']) ?></span></p>
                                    <h3><a class="stretched-link" href="<?= url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/postulaciones') ?>"><?= e($o['titulo']) ?></a></h3>
                                    <p class="oport-meta">
                                        <span class="cupos"><?= (int) $o['cuposDisponibles'] ?> de <?= (int) $o['cuposTotales'] ?> cupos libres</span>
                                        <span><?= (int) $o['pendientes'] ?> por revisar</span>
                                        <span><?= (int) $o['aceptadas'] ?> aceptados</span>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h2 class="titulo-seccion">Mientras tanto</h2>
                <p>Completa el perfil de tu fundación: descripción, misión, logo y sitio web. Es lo primero que verán los voluntarios cuando publiques tus convocatorias.</p>
                <a class="btn btn-primary" href="<?= url('/fundacion/perfil') ?>">Ir al perfil de la fundación</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-5">
            <?php View::parcial('notificaciones_recientes', ['notificaciones' => $notificaciones]); ?>
        </div>
    </div>
</div>
