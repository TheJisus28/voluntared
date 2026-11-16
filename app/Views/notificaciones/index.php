<?php
use App\Core\View;

$iconos = ['postulacion' => 'bi-person-plus', 'respuesta' => 'bi-reply', 'mensaje' => 'bi-chat-dots',
    'oportunidad' => 'bi-megaphone', 'evaluacion' => 'bi-star', 'sistema' => 'bi-info-circle'];
$acciones = $noLeidas ? '<form method="post" action="' . url('/notificaciones/leer-todas') . '">' . csrf_field()
    . '<button class="btn btn-outline-primary"><i class="bi bi-check2-all me-1" aria-hidden="true"></i>Marcar todas como leídas</button></form>' : '';
?>
<div class="container" style="max-width: 860px;">
    <?php View::parcial('encabezado', ['titulo' => 'Notificaciones', 'subtitulo' => $noLeidas ? "Tienes $noLeidas sin leer." : 'Estás al día.', 'acciones' => $acciones]); ?>
    <div class="card card-vr">
        <?php if (!$notificaciones): ?>
            <div class="vacio"><i class="bi bi-bell-slash" aria-hidden="true"></i>No tienes notificaciones.</div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notificaciones as $n): ?>
                    <li class="list-group-item p-0<?= $n['leida'] ? '' : ' bg-verde-claro' ?>">
                        <a class="d-flex gap-3 p-3 text-decoration-none text-reset" href="<?= url('/notificaciones/' . $n['idNotificacion'] . '/abrir') ?>">
                            <span class="stat icono flex-shrink-0 p-0 border-0" style="width:2.4rem;height:2.4rem;"><i class="bi <?= $iconos[$n['tipo']] ?? 'bi-bell' ?>" aria-hidden="true"></i></span>
                            <span class="flex-grow-1">
                                <span class="d-flex justify-content-between gap-2">
                                    <strong><?php if (!$n['leida']): ?><span class="visually-hidden">No leída: </span><?php endif; ?><?= e($n['titulo']) ?></strong>
                                    <span class="small text-body-secondary text-nowrap"><?= e(hace($n['fechaCreacion'])) ?></span>
                                </span>
                                <span class="d-block small"><?= e($n['contenido']) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
