<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => $f['razonSocial'], 'migas' => ['Oportunidades' => '/oportunidades']]); ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-vr p-4 text-center">
                <div class="mx-auto mb-2"><?= avatar($f['foto'], $f['razonSocial'], null, 'lg') ?></div>
                <h2 class="h5 fw-bold mb-0"><?= e($f['razonSocial']) ?></h2>
                <div class="small text-body-secondary mb-2"><i class="bi bi-patch-check-fill text-primary" aria-hidden="true"></i> Verificada · NIT <?= e($f['nit']) ?></div>
                <div class="mb-3"><?= estrellas($f['calificacion']['promedio']) ?> <span class="small text-body-secondary">(<?= $f['calificacion']['total'] ?>)</span></div>
                <ul class="list-unstyled text-start small mb-0">
                    <li class="mb-1"><i class="bi bi-geo-alt text-primary me-2" aria-hidden="true"></i><?= e($f['direccion']) ?>, <?= e($f['ciudad']) ?></li>
                    <?php if ($f['sitioWeb']): ?>
                        <li class="mb-1"><i class="bi bi-globe text-primary me-2" aria-hidden="true"></i><a href="<?= e($f['sitioWeb']) ?>" target="_blank" rel="noopener"><?= e(preg_replace('#^https?://#', '', $f['sitioWeb'])) ?></a></li>
                    <?php endif; ?>
                    <li><i class="bi bi-calendar-check text-primary me-2" aria-hidden="true"></i>En VoluntaRed desde <?= e(fecha($f['fechaCreacion'])) ?></li>
                </ul>
            </div>
            <div class="row g-2 mt-1 text-center">
                <div class="col-4"><div class="stat p-2"><div class="valor fs-4"><?= (int) $f['totales']['oportunidades'] ?></div><div class="etiqueta small">Actividades</div></div></div>
                <div class="col-4"><div class="stat p-2"><div class="valor fs-4"><?= (int) $f['totales']['voluntarios'] ?></div><div class="etiqueta small">Voluntarios</div></div></div>
                <div class="col-4"><div class="stat p-2"><div class="valor fs-4"><?= number_format((float) $f['totales']['horas'], 0, ',', '.') ?></div><div class="etiqueta small">Horas</div></div></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card card-vr p-4 mb-4">
                <h2 class="h5 fw-bold">Sobre la fundación</h2>
                <p style="white-space: pre-line;"><?= e($f['descripcion']) ?></p>
                <?php if ($f['mision']): ?>
                    <h2 class="h5 fw-bold">Misión</h2>
                    <p class="mb-0"><?= e($f['mision']) ?></p>
                <?php endif; ?>
            </div>

            <h2 class="h5 fw-bold mb-3">Oportunidades abiertas</h2>
            <?php if (!$oportunidades): ?>
                <div class="card card-vr mb-4"><div class="vacio py-4">No tiene convocatorias abiertas en este momento.</div></div>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($oportunidades as $o): ?>
                        <div class="col-md-6"><?php View::parcial('tarjeta_oportunidad', ['o' => $o + ['razonSocial' => $f['razonSocial']]]); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 class="h5 fw-bold mb-3">Lo que dicen los voluntarios</h2>
            <?php if (!$f['evaluaciones']): ?>
                <div class="card card-vr"><div class="vacio py-4">Aún no tiene evaluaciones.</div></div>
            <?php else: ?>
                <div class="card card-vr">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($f['evaluaciones'] as $ev): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex justify-content-between flex-wrap gap-2">
                                    <div class="d-flex gap-2 align-items-center">
                                        <?= avatar($ev['foto'], $ev['nombre'], $ev['apellido'], 'sm') ?>
                                        <strong><?= e($ev['nombre'] . ' ' . mb_substr((string) $ev['apellido'], 0, 1) . '.') ?></strong>
                                    </div>
                                    <?= estrellas($ev['calificacion'], false) ?>
                                </div>
                                <?php if ($ev['comentario']): ?><p class="mb-1 mt-2"><?= e($ev['comentario']) ?></p><?php endif; ?>
                                <div class="small text-body-secondary"><?= e($ev['titulo']) ?> · <?= e(fecha_corta($ev['fecha'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
