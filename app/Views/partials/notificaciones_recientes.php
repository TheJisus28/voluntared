<div class="card-vr p-4">
    <div class="d-flex justify-content-between align-items-baseline mb-3">
        <h2 class="titulo-seccion mb-0">Novedades</h2>
        <a class="small fw-semibold" href="<?= url('/notificaciones') ?>">Ver todas</a>
    </div>
    <?php if (!$notificaciones): ?>
        <p class="small text-body-secondary mb-0">No tienes notificaciones.</p>
    <?php else: ?>
        <ul class="lista-simple">
            <?php foreach ($notificaciones as $n): ?>
                <li>
                    <a class="text-decoration-none text-reset d-block" href="<?= url('/notificaciones/' . $n['idNotificacion'] . '/abrir') ?>">
                        <span class="d-block fw-semibold small"><?php if (!$n['leida']): ?><span class="visually-hidden">No leída: </span><span class="estado estado-pendiente me-1" aria-hidden="true"></span><?php endif; ?><?= e($n['titulo']) ?></span>
                        <span class="small d-block text-body-secondary"><?= e(recortar($n['contenido'], 95)) ?></span>
                        <span class="small text-body-secondary"><?= e(hace($n['fechaCreacion'])) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
