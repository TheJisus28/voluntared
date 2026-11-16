<?php
use App\Core\View;

$tab = ($_GET['tab'] ?? 'categorias') === 'habilidades' ? 'habilidades' : 'categorias';
?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Categorías y habilidades', 'subtitulo' => 'Catálogos usados en los perfiles de los voluntarios y en las oportunidades.']); ?>
    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link<?= $tab === 'categorias' ? ' active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-categorias" type="button" role="tab">Categorías (<?= count($categorias) ?>)</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link<?= $tab === 'habilidades' ? ' active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-habilidades" type="button" role="tab">Habilidades (<?= count($habilidades) ?>)</button></li>
    </ul>
    <div class="tab-content">
        <?php foreach (['categorias' => $categorias, 'habilidades' => $habilidades] as $tipo => $items): $pk = $tipo === 'categorias' ? 'idCategoria' : 'idHabilidad'; ?>
            <div class="tab-pane fade<?= $tab === $tipo ? ' show active' : '' ?>" id="tab-<?= $tipo ?>" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <form class="card card-vr p-4" method="post" action="<?= url('/admin/catalogos/' . $tipo) ?>">
                            <?= csrf_field() ?>
                            <h2 class="h6 fw-bold">Agregar <?= $tipo === 'categorias' ? 'categoría' : 'habilidad' ?></h2>
                            <label class="form-label small fw-semibold" for="nuevo-<?= $tipo ?>">Nombre</label>
                            <input class="form-control mb-2" id="nuevo-<?= $tipo ?>" name="nombre" maxlength="60" required>
                            <?php if ($tipo === 'categorias'): ?>
                                <label class="form-label small fw-semibold" for="desc-nueva">Descripción</label>
                                <textarea class="form-control mb-2" id="desc-nueva" name="descripcion" maxlength="200" rows="2"></textarea>
                            <?php endif; ?>
                            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Agregar</button>
                        </form>
                    </div>
                    <div class="col-lg-8">
                        <div class="card card-vr">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($items as $it): $id = (int) $it[$pk]; ?>
                                    <li class="list-group-item p-3">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <form class="d-flex flex-wrap gap-2 flex-grow-1 align-items-center" method="post" action="<?= url("/admin/catalogos/$tipo/$id") ?>">
                                                <?= csrf_field() ?>
                                                <label class="visually-hidden" for="n-<?= $tipo . $id ?>">Nombre</label>
                                                <input class="form-control form-control-sm" style="max-width: 230px;" id="n-<?= $tipo . $id ?>" name="nombre" value="<?= e($it['nombre']) ?>" maxlength="60" required>
                                                <?php if ($tipo === 'categorias'): ?>
                                                    <label class="visually-hidden" for="d-<?= $id ?>">Descripción</label>
                                                    <input class="form-control form-control-sm flex-grow-1" style="min-width: 180px; max-width: 320px;" id="d-<?= $id ?>" name="descripcion" value="<?= e($it['descripcion']) ?>" maxlength="200" placeholder="Descripción">
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-light border" title="Guardar cambios"><i class="bi bi-check2" aria-hidden="true"></i><span class="visually-hidden">Guardar</span></button>
                                            </form>
                                            <span class="small text-body-secondary"><?= (int) $it['usos'] ?> uso(s)</span>
                                            <form method="post" action="<?= url("/admin/catalogos/$tipo/$id/eliminar") ?>" data-confirmar="¿Eliminar «<?= e($it['nombre']) ?>»? Solo es posible si no está asociada a oportunidades ni perfiles." data-confirmar-boton="Eliminar">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"<?= (int) $it['usos'] > 0 ? ' disabled' : '' ?>><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Eliminar</span></button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
