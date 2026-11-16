<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => $f['razonSocial'], 'subtitulo' => 'Solicitud de verificación', 'migas' => ['Fundaciones' => '/admin/fundaciones']]); ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-vr p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex gap-3 align-items-center">
                        <?= avatar($f['foto'], $f['razonSocial'], null, 'md') ?>
                        <div><h2 class="h5 fw-bold mb-0"><?= e($f['razonSocial']) ?></h2><div class="small text-body-secondary">Registrada el <?= e(fecha($f['fechaCreacion'])) ?></div></div>
                    </div>
                    <?= badge_estado('verificacion', $f['estadoVerificacion']) ?>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">NIT</dt><dd class="col-sm-8"><?= e($f['nit']) ?></dd>
                    <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8"><?= e($f['direccion'] . ', ' . $f['ciudad']) ?></dd>
                    <dt class="col-sm-4">Contacto</dt><dd class="col-sm-8"><?= e($f['nombre'] . ' ' . $f['apellido']) ?> · <?= e($f['telefono']) ?><br><a href="mailto:<?= e($f['email']) ?>"><?= e($f['email']) ?></a></dd>
                    <?php if ($f['sitioWeb']): ?><dt class="col-sm-4">Sitio web</dt><dd class="col-sm-8"><a href="<?= e($f['sitioWeb']) ?>" target="_blank" rel="noopener"><?= e($f['sitioWeb']) ?></a></dd><?php endif; ?>
                    <dt class="col-sm-4">Descripción</dt><dd class="col-sm-8"><?= nl2br(e($f['descripcion'])) ?></dd>
                    <?php if ($f['mision']): ?><dt class="col-sm-4">Misión</dt><dd class="col-sm-8"><?= e($f['mision']) ?></dd><?php endif; ?>
                    <?php if ($f['fechaVerificacion']): ?><dt class="col-sm-4">Última decisión</dt><dd class="col-sm-8"><?= e(fecha($f['fechaVerificacion'], true)) ?></dd><?php endif; ?>
                    <?php if ($f['motivoRechazo']): ?><dt class="col-sm-4">Motivo de rechazo</dt><dd class="col-sm-8"><?= e($f['motivoRechazo']) ?></dd><?php endif; ?>
                </dl>
            </div>
            <?php if ($oportunidades): ?>
                <div class="card card-vr p-4">
                    <h2 class="h6 fw-bold">Oportunidades publicadas (<?= count($oportunidades) ?>)</h2>
                    <?php foreach ($oportunidades as $o): ?>
                        <div class="d-flex justify-content-between border-bottom py-2 small"><a href="<?= url('/oportunidades/' . $o['idOportunidad']) ?>"><?= e($o['titulo']) ?></a><?= badge_estado('estado_oportunidad', $o['estado']) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-5">
            <div class="card card-vr p-4 mb-4">
                <h2 class="h6 fw-bold mb-2">Documento soporte</h2>
                <p class="small text-body-secondary">RUT o certificado de existencia y representación legal adjuntado por la fundación.</p>
                <a class="btn btn-outline-primary w-100" href="<?= url('/admin/fundaciones/' . $f['idFundacion'] . '/documento') ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Abrir documento</a>
            </div>
            <div class="card card-vr p-4">
                <h2 class="h6 fw-bold mb-3">Decisión</h2>
                <?php if ($f['estadoVerificacion'] !== 'verificada'): ?>
                    <form method="post" action="<?= url('/admin/fundaciones/' . $f['idFundacion'] . '/verificar') ?>" class="mb-2"
                          data-confirmar="¿Aprobar a <?= e($f['razonSocial']) ?>? Podrá publicar oportunidades de inmediato." data-confirmar-boton="Aprobar" data-confirmar-estilo="btn-primary">
                        <?= csrf_field() ?><input type="hidden" name="decision" value="verificada">
                        <button class="btn btn-primary w-100"><i class="bi bi-patch-check me-1" aria-hidden="true"></i>Aprobar fundación</button>
                    </form>
                <?php endif; ?>
                <?php if ($f['estadoVerificacion'] !== 'rechazada'): ?>
                    <form method="post" action="<?= url('/admin/fundaciones/' . $f['idFundacion'] . '/verificar') ?>"
                          data-confirmar="Indica el motivo del rechazo. La fundación lo recibirá por notificación y correo." data-confirmar-boton="Rechazar" data-motivo="1">
                        <?= csrf_field() ?><input type="hidden" name="decision" value="rechazada">
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-octagon me-1" aria-hidden="true"></i>Rechazar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
