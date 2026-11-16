<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', [
        'titulo' => 'Mis oportunidades',
        'subtitulo' => 'Crea, modifica y cierra tus actividades de voluntariado.',
        'acciones' => '<a class="btn btn-primary" href="' . url('/fundacion/oportunidades/nueva') . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear oportunidad</a>',
    ]); ?>
    <ul class="nav nav-pills mb-3 flex-wrap gap-1">
        <li class="nav-item"><a class="nav-link<?= !$estado ? ' active' : '' ?>" href="<?= url('/fundacion/oportunidades') ?>">Todas</a></li>
        <?php foreach (['abierta' => 'Abiertas', 'cerrada' => 'Cerradas', 'finalizada' => 'Finalizadas', 'cancelada' => 'Canceladas'] as $k => $t): ?>
            <li class="nav-item"><a class="nav-link<?= $estado === $k ? ' active' : '' ?>" href="<?= url('/fundacion/oportunidades', ['estado' => $k]) ?>"><?= $t ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="card card-vr">
        <?php if (!$oportunidades): ?>
            <div class="vacio"><i class="bi bi-megaphone" aria-hidden="true"></i>No hay oportunidades<?= $estado ? ' en este estado' : '' ?>.<br><a href="<?= url('/fundacion/oportunidades/nueva') ?>">Publicar una oportunidad</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vr table-hover mb-0">
                    <caption class="visually-hidden">Oportunidades de la fundación</caption>
                    <thead><tr><th scope="col">Oportunidad</th><th scope="col">Fechas</th><th scope="col">Cupos</th><th scope="col">Postulaciones</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($oportunidades as $o): $id = (int) $o['idOportunidad']; ?>
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="<?= url('/oportunidades/' . $id) ?>"><?= e($o['titulo']) ?></a>
                                <div class="small text-body-secondary"><?= e($o['categoria']) ?> · <?= e($o['ciudad']) ?> · <?= e(etiqueta('modalidad', $o['modalidad'])) ?></div>
                                <?php if (!$o['visible']): ?><span class="estado estado-cancelada mt-1">Oculta por moderación</span><?php endif; ?>
                            </td>
                            <td class="small text-nowrap"><?= e(fecha_corta($o['fechaInicio'])) ?><br><span class="text-body-secondary">al <?= e(fecha_corta($o['fechaFin'])) ?></span></td>
                            <td class="small"><?= (int) $o['cuposDisponibles'] ?>/<?= (int) $o['cuposTotales'] ?> libres</td>
                            <td class="small">
                                <a href="<?= url("/fundacion/oportunidades/$id/postulaciones") ?>"><?= (int) $o['pendientes'] ?> pendientes · <?= (int) $o['aceptadas'] ?> aceptadas</a>
                            </td>
                            <td><?= badge_estado('estado_oportunidad', $o['estado']) ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Acciones</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?= url("/fundacion/oportunidades/$id/postulaciones") ?>"><i class="bi bi-people me-2" aria-hidden="true"></i>Postulaciones</a></li>
                                        <?php if (in_array($o['estado'], ['abierta', 'cerrada'], true)): ?>
                                            <li><a class="dropdown-item" href="<?= url("/fundacion/oportunidades/$id/editar") ?>"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Modificar</a></li>
                                        <?php endif; ?>
                                        <?php View::parcial('acciones_estado', ['o' => $o]); ?>
                                        <?php if ($o['estado'] === 'finalizada'): ?>
                                            <li><a class="dropdown-item" href="<?= url("/fundacion/oportunidades/$id/participacion") ?>"><i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Registrar participación</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
