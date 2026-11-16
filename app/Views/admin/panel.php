<?php use App\Core\View; ?>
<div class="container">
    <div class="encabezado-pagina">
        <span class="sobretitulo">Administración</span>
        <h1 class="mt-1">Estado de la plataforma</h1>
        <p><?= e(fecha(date('Y-m-d'))) ?></p>
    </div>
    <div class="metricas mb-5">
        <div><strong><?= (int) $resumen['voluntarios'] ?></strong><span>voluntarios</span></div>
        <div><strong><?= (int) $resumen['fundaciones'] ?></strong><span>fundaciones</span></div>
        <div><strong><?= (int) $resumen['abiertas'] ?></strong><span>convocatorias abiertas</span></div>
        <div><strong><?= (int) $resumen['postulaciones'] ?></strong><span>postulaciones</span></div>
        <div><strong><?= e(rtrim(horas($resumen['horas']), ' h')) ?></strong><span>horas registradas</span></div>
    </div>
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h2 class="titulo-seccion mb-0">Fundaciones por verificar <span class="text-body-secondary fw-normal">(<?= (int) $resumen['pendientes'] ?>)</span></h2>
                <a class="small fw-semibold" href="<?= url('/admin/fundaciones') ?>">Ver todas</a>
            </div>
            <?php if (!$pendientes): ?>
                <p class="text-body-secondary">No hay solicitudes pendientes.</p>
            <?php else: ?>
                <div class="card-vr px-4 py-2">
                    <ul class="lista-simple">
                        <?php foreach (array_slice($pendientes, 0, 6) as $f): ?>
                            <li class="d-flex justify-content-between align-items-center gap-2 py-3">
                                <div>
                                    <span class="fw-semibold"><?= e($f['razonSocial']) ?></span>
                                    <div class="small text-body-secondary">NIT <?= e($f['nit']) ?> · <?= e($f['ciudad']) ?> · registrada <?= e(hace($f['fechaCreacion'])) ?></div>
                                </div>
                                <a class="btn btn-sm btn-primary" href="<?= url('/admin/fundaciones/' . $f['idFundacion']) ?>">Revisar</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h2 class="titulo-seccion mt-5">Accesos</h2>
            <div class="row g-3">
                <?php foreach ([
                    ['/admin/usuarios', 'Usuarios', (int) $resumen['suspendidos'] . ' cuentas suspendidas'],
                    ['/admin/moderacion', 'Moderación', 'Oportunidades y evaluaciones recientes'],
                    ['/admin/catalogos', 'Categorías y habilidades', 'Catálogos del sistema'],
                    ['/admin/reportes', 'Reportes globales', 'Exportables a PDF y Excel'],
                ] as [$ruta, $titulo, $texto]): ?>
                    <div class="col-sm-6">
                        <a class="opcion-registro h-100" href="<?= url($ruta) ?>">
                            <h3 class="h5 mb-1"><?= $titulo ?></h3>
                            <p class="small"><?= e($texto) ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-5"><?php View::parcial('notificaciones_recientes', ['notificaciones' => $notificaciones]); ?></div>
    </div>
</div>
