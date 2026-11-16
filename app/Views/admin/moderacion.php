<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Moderación de contenido', 'subtitulo' => 'Retira de la vista pública oportunidades o evaluaciones inapropiadas o fraudulentas.']); ?>
    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-oport" type="button" role="tab">Oportunidades recientes</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-eval" type="button" role="tab">Evaluaciones recientes</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-oport" role="tabpanel">
            <div class="card card-vr">
                <div class="table-responsive">
                    <table class="table table-vr mb-0">
                        <caption class="visually-hidden">Oportunidades para moderar</caption>
                        <thead><tr><th scope="col">Oportunidad</th><th scope="col">Fundación</th><th scope="col">Publicada</th><th scope="col">Visibilidad</th><th scope="col" class="text-end">Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($oportunidades as $o): ?>
                            <tr>
                                <td><a href="<?= url('/oportunidades/' . $o['idOportunidad']) ?>" class="fw-semibold text-decoration-none"><?= e($o['titulo']) ?></a><div class="small text-body-secondary"><?= e($o['categoria']) ?> · <?= e($o['ciudad']) ?> · <?= e(etiqueta('estado_oportunidad', $o['estado'])) ?></div></td>
                                <td class="small"><?= e($o['razonSocial']) ?></td>
                                <td class="small"><?= e(fecha_corta($o['fechaPublicacion'])) ?></td>
                                <td><?= $o['visible'] ? '<span class="estado estado-activo">Visible</span>' : '<span class="estado estado-cancelada">Oculta</span>' ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= url('/admin/moderacion/oportunidades/' . $o['idOportunidad']) ?>"
                                        <?= $o['visible'] ? 'data-confirmar="¿Ocultar «' . e($o['titulo']) . '» de la vista pública? Se notificará a la fundación." data-confirmar-boton="Ocultar" data-motivo="1"' : 'data-confirmar="¿Volver a mostrar esta oportunidad?" data-confirmar-boton="Mostrar" data-confirmar-estilo="btn-primary"' ?>>
                                        <?= csrf_field() ?><input type="hidden" name="visible" value="<?= $o['visible'] ? 0 : 1 ?>">
                                        <button class="btn btn-sm <?= $o['visible'] ? 'btn-outline-danger' : 'btn-primary' ?>"><i class="bi <?= $o['visible'] ? 'bi-eye-slash' : 'bi-eye' ?> me-1" aria-hidden="true"></i><?= $o['visible'] ? 'Ocultar' : 'Mostrar' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-eval" role="tabpanel">
            <div class="card card-vr">
                <?php if (!$evaluaciones): ?>
                    <div class="vacio">No hay evaluaciones registradas.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($evaluaciones as $ev): ?>
                            <li class="list-group-item p-3<?= $ev['visible'] ? '' : ' bg-light' ?>">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <div>
                                        <strong><?= e($ev['evaluadorFundacion'] ?? $ev['evaluadorNombre'] . ' ' . $ev['evaluadorApellido']) ?></strong>
                                        <span class="text-body-secondary small">evaluó a</span>
                                        <strong><?= e($ev['evaluadoFundacion'] ?? $ev['evaluadoNombre'] . ' ' . $ev['evaluadoApellido']) ?></strong>
                                        <?= estrellas($ev['calificacion'], false) ?>
                                        <?php if (!$ev['visible']): ?><span class="estado estado-cancelada ms-1">Oculta</span><?php endif; ?>
                                        <div class="small text-body-secondary"><?= e($ev['titulo']) ?> · <?= e(fecha_corta($ev['fecha'])) ?></div>
                                        <?php if ($ev['comentario']): ?><p class="mb-0 mt-1">«<?= e($ev['comentario']) ?>»</p><?php endif; ?>
                                    </div>
                                    <form method="post" action="<?= url('/admin/moderacion/evaluaciones/' . $ev['idEvaluacion']) ?>"
                                        <?= $ev['visible'] ? 'data-confirmar="¿Ocultar esta evaluación? Dejará de contar en el promedio y se notificará a su autor." data-confirmar-boton="Ocultar" data-motivo="1"' : 'data-confirmar="¿Volver a mostrar esta evaluación?" data-confirmar-boton="Mostrar" data-confirmar-estilo="btn-primary"' ?>>
                                        <?= csrf_field() ?><input type="hidden" name="visible" value="<?= $ev['visible'] ? 0 : 1 ?>">
                                        <button class="btn btn-sm <?= $ev['visible'] ? 'btn-outline-danger' : 'btn-primary' ?>"><?= $ev['visible'] ? 'Ocultar' : 'Mostrar' ?></button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
