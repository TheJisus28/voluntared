<?php
use App\Core\View;

$conteo = array_count_values(array_column($postulaciones, 'estado'));
$editable = in_array($o['estado'], ['abierta', 'cerrada'], true);
?>
<div class="container">
    <?php View::parcial('encabezado', [
        'titulo' => 'Postulaciones',
        'subtitulo' => $o['titulo'],
        'migas' => ['Mis oportunidades' => '/fundacion/oportunidades'],
        'acciones' => $o['estado'] === 'finalizada'
            ? '<a class="btn btn-primary" href="' . url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/participacion') . '"><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>Registrar participación</a>'
            : '',
    ]); ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat"><div class="etiqueta">Estado</div><div class="mt-1"><?= badge_estado('estado_oportunidad', $o['estado']) ?></div></div></div>
        <div class="col-6 col-md-3"><div class="stat"><div class="etiqueta">Cupos disponibles</div><div class="valor"><?= (int) $o['cuposDisponibles'] ?> <span class="fs-6 text-body-secondary">de <?= (int) $o['cuposTotales'] ?></span></div></div></div>
        <div class="col-6 col-md-3"><div class="stat"><div class="etiqueta">Pendientes</div><div class="valor"><?= $conteo['pendiente'] ?? 0 ?></div></div></div>
        <div class="col-6 col-md-3"><div class="stat"><div class="etiqueta">Aceptadas</div><div class="valor"><?= $conteo['aceptada'] ?? 0 ?></div></div></div>
    </div>

    <?php if ((int) $o['cuposDisponibles'] === 0 && $o['estado'] === 'cerrada'): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Los cupos están completos: la convocatoria se cerró automáticamente.</div>
    <?php endif; ?>

    <div class="card card-vr">
        <?php if (!$postulaciones): ?>
            <div class="vacio"><i class="bi bi-people" aria-hidden="true"></i>Aún no hay postulaciones para esta oportunidad.</div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($postulaciones as $p): ?>
                    <li class="list-group-item p-3">
                        <div class="d-flex flex-wrap gap-3 align-items-start">
                            <?= avatar($p['foto'], $p['nombre'], $p['apellido'], 'md') ?>
                            <div class="flex-grow-1" style="min-width: 220px;">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a class="fw-semibold text-decoration-none" href="<?= url('/fundacion/voluntarios/' . $p['Usuario_idUsuario']) ?>"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></a>
                                    <?= badge_estado('estado_postulacion', $p['estado']) ?>
                                </div>
                                <div class="small text-body-secondary">
                                    <?= e($p['ciudad'] ?? 'Sin ciudad') ?> · <?= e(etiqueta('disponibilidad', $p['disponibilidad'])) ?> ·
                                    <?= e(horas($p['horas'])) ?> de voluntariado · <?= $p['promedio'] ? estrellas($p['promedio']) : 'Sin calificaciones' ?>
                                </div>
                                <?php if ($p['habilidades']): ?><div class="small mt-1"><i class="bi bi-tools me-1" aria-hidden="true"></i><?= e($p['habilidades']) ?></div><?php endif; ?>
                                <?php if ($p['mensaje']): ?><blockquote class="small bg-light rounded p-2 mt-2 mb-0">«<?= e($p['mensaje']) ?>»</blockquote><?php endif; ?>
                                <div class="small text-body-secondary mt-1">Postulado <?= e(hace($p['fechaPostulacion'])) ?></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-light border" href="<?= url('/fundacion/voluntarios/' . $p['Usuario_idUsuario']) ?>"><i class="bi bi-person-vcard me-1" aria-hidden="true"></i>Ver perfil</a>
                                <form method="post" action="<?= url('/mensajes/iniciar') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="voluntario" value="<?= (int) $p['Usuario_idUsuario'] ?>">
                                    <input type="hidden" name="oportunidad" value="<?= (int) $o['idOportunidad'] ?>">
                                    <button class="btn btn-sm btn-light border"><i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Mensaje</button>
                                </form>
                                <?php if ($p['estado'] === 'pendiente' && $editable): ?>
                                    <form method="post" action="<?= url('/fundacion/postulaciones/' . $p['idPostulacion'] . '/aceptar') ?>"
                                          data-confirmar="¿Aceptar a <?= e($p['nombre']) ?> en «<?= e($o['titulo']) ?>»? Se descontará un cupo." data-confirmar-boton="Aceptar" data-confirmar-estilo="btn-primary">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-primary"<?= (int) $o['cuposDisponibles'] === 0 ? ' disabled title="Sin cupos disponibles"' : '' ?>><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aceptar</button>
                                    </form>
                                    <form method="post" action="<?= url('/fundacion/postulaciones/' . $p['idPostulacion'] . '/rechazar') ?>"
                                          data-confirmar="¿Rechazar la postulación de <?= e($p['nombre']) ?>? Se le notificará." data-confirmar-boton="Rechazar">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Rechazar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
