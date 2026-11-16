<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => $v['nombre'] . ' ' . $v['apellido'], 'subtitulo' => 'Perfil del voluntario']); ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-vr p-4 text-center">
                <div class="mx-auto mb-2"><?= avatar($v['foto'], $v['nombre'], $v['apellido'], 'lg') ?></div>
                <h2 class="h5 fw-bold mb-1"><?= e($v['nombre'] . ' ' . $v['apellido']) ?></h2>
                <div class="mb-2"><?= estrellas($v['calificacion']['promedio']) ?> <span class="small text-body-secondary">(<?= $v['calificacion']['total'] ?>)</span></div>
                <ul class="list-unstyled small text-start mb-0">
                    <?php if ($v['idPerfilVoluntario']): ?>
                        <li class="mb-1"><i class="bi bi-geo-alt text-primary me-2" aria-hidden="true"></i><?= e($v['ciudad'] . ', ' . $v['departamento']) ?></li>
                        <li class="mb-1"><i class="bi bi-calendar-week text-primary me-2" aria-hidden="true"></i>Disponibilidad: <?= e(etiqueta('disponibilidad', $v['disponibilidad'])) ?></li>
                        <li class="mb-1"><i class="bi bi-cake2 text-primary me-2" aria-hidden="true"></i><?= (new DateTime($v['fechaNacimiento']))->diff(new DateTime())->y ?> años</li>
                    <?php endif; ?>
                    <li class="mb-1"><i class="bi bi-envelope text-primary me-2" aria-hidden="true"></i><?= e($v['email']) ?></li>
                    <?php if ($v['telefono']): ?><li class="mb-1"><i class="bi bi-telephone text-primary me-2" aria-hidden="true"></i><?= e($v['telefono']) ?></li><?php endif; ?>
                    <li><i class="bi bi-clock-history text-primary me-2" aria-hidden="true"></i><?= e(horas($v['horas'])) ?> de voluntariado</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card card-vr p-4 mb-4">
                <?php if ($v['biografia']): ?>
                    <h2 class="h6 fw-bold">Sobre mí</h2>
                    <p><?= e($v['biografia']) ?></p>
                <?php endif; ?>
                <h2 class="h6 fw-bold">Habilidades</h2>
                <div class="mb-3"><?php if (!$v['habilidades']): ?><span class="small text-body-secondary">No registró habilidades.</span><?php endif; ?><?php foreach ($v['habilidades'] as $h): ?><span class="chip"><?= e($h['nombre']) ?></span><?php endforeach; ?></div>
                <h2 class="h6 fw-bold">Causas de interés</h2>
                <div><?php foreach ($v['categorias'] as $c): ?><span class="chip chip-verde"><?= e($c['nombre']) ?></span><?php endforeach; ?></div>
            </div>
            <div class="card card-vr p-4 mb-4">
                <h2 class="h6 fw-bold mb-3">Historial de participación</h2>
                <?php if (!$v['actividades']): ?>
                    <p class="small text-body-secondary mb-0">Aún no tiene participaciones registradas.</p>
                <?php else: ?>
                    <?php foreach ($v['actividades'] as $a): ?>
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span><strong><?= e($a['titulo']) ?></strong> · <?= e($a['razonSocial']) ?> · <?= e(fecha_corta($a['fechaInicio'])) ?></span>
                            <span><?= $a['asistio'] ? e(horas($a['horasCumplidas'])) : 'No asistió' ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card card-vr p-4">
                <h2 class="h6 fw-bold mb-3">Evaluaciones de otras fundaciones</h2>
                <?php if (!$v['evaluaciones']): ?>
                    <p class="small text-body-secondary mb-0">Aún no tiene evaluaciones.</p>
                <?php else: ?>
                    <?php foreach ($v['evaluaciones'] as $ev): ?>
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between"><strong class="small"><?= e($ev['razonSocial'] ?? $ev['nombre']) ?></strong><?= estrellas($ev['calificacion'], false) ?></div>
                            <?php if ($ev['comentario']): ?><p class="small mb-0"><?= e($ev['comentario']) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
