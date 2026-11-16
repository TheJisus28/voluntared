<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Verificación de fundaciones', 'subtitulo' => 'Revisa el NIT y el documento soporte antes de autorizar la publicación de oportunidades.']); ?>
    <ul class="nav nav-pills mb-3 gap-1">
        <?php foreach (['pendiente' => 'Pendientes', 'verificada' => 'Verificadas', 'rechazada' => 'Rechazadas'] as $k => $t): ?>
            <li class="nav-item"><a class="nav-link<?= $estado === $k ? ' active' : '' ?>" href="<?= url('/admin/fundaciones', ['estado' => $k]) ?>"><?= $t ?> <span class="badge text-bg-light ms-1"><?= (int) $conteo[$k] ?></span></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="card card-vr">
        <?php if (!$fundaciones): ?>
            <div class="vacio"><i class="bi bi-building-check" aria-hidden="true"></i>No hay fundaciones en este estado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vr table-hover mb-0">
                    <caption class="visually-hidden">Fundaciones en estado <?= e($estado) ?></caption>
                    <thead><tr><th scope="col">Fundación</th><th scope="col">NIT</th><th scope="col">Ciudad</th><th scope="col">Contacto</th><th scope="col">Registro</th><th scope="col" class="text-end">Acción</th></tr></thead>
                    <tbody>
                    <?php foreach ($fundaciones as $f): ?>
                        <tr>
                            <td><strong><?= e($f['razonSocial']) ?></strong><?php if (!$f['activo']): ?> <span class="estado estado-suspendido">Suspendida</span><?php endif; ?></td>
                            <td><?= e($f['nit']) ?></td>
                            <td><?= e($f['ciudad']) ?></td>
                            <td class="small"><?= e($f['nombre'] . ' ' . $f['apellido']) ?><br><span class="text-body-secondary"><?= e($f['email']) ?></span></td>
                            <td class="small"><?= e(fecha_corta($f['fechaCreacion'])) ?></td>
                            <td class="text-end"><a class="btn btn-sm <?= $estado === 'pendiente' ? 'btn-primary' : 'btn-light border' ?>" href="<?= url('/admin/fundaciones/' . $f['idFundacion']) ?>"><?= $estado === 'pendiente' ? 'Revisar' : 'Ver detalle' ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
