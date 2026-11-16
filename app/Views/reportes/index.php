<?php
use App\Core\View;

$f = static fn (string $k) => e($filtros[$k] ?? '');
$query = array_filter($filtros, static fn ($v) => is_string($v) && $v !== '');
unset($query['formato']);
?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Reportes', 'subtitulo' => $esAdmin ? 'Reportes globales de la plataforma.' : 'Reportes de las actividades y postulaciones de tu fundación.']); ?>
    <form class="card card-vr p-3 mb-4" method="get" action="<?= url($accion) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold" for="tipo">Tipo de reporte <span class="text-danger" aria-hidden="true">*</span></label>
                <select class="form-select" id="tipo" name="tipo" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($tipos as $k => $t): ?><option value="<?= $k ?>"<?= $tipo === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2"><label class="form-label small fw-semibold" for="desde">Desde</label><input class="form-control" type="date" id="desde" name="desde" value="<?= $f('desde') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small fw-semibold" for="hasta">Hasta</label><input class="form-control" type="date" id="hasta" name="hasta" value="<?= $f('hasta') ?>"></div>
            <?php if (!$esAdmin): ?>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="oportunidad">Oportunidad</label>
                    <select class="form-select" id="oportunidad" name="oportunidad"><option value="">Todas</option>
                        <?php foreach ($oportunidades as $o): ?><option value="<?= $o['idOportunidad'] ?>"<?= (int) ($filtros['oportunidad'] ?? 0) === (int) $o['idOportunidad'] ? ' selected' : '' ?>><?= e(recortar($o['titulo'], 45)) ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="categoria">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria"><option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?><option value="<?= $c['idCategoria'] ?>"<?= (int) ($filtros['categoria'] ?? 0) === (int) $c['idCategoria'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado"><option value="">Todos</option>
                    <?php foreach (['abierta' => 'Abierta', 'cerrada' => 'Cerrada', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada', 'pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'verificada' => 'Verificada', 'activo' => 'Activo', 'suspendido' => 'Suspendido'] as $k => $t): ?>
                        <option value="<?= $k ?>"<?= ($filtros['estado'] ?? '') === $k ? ' selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($esAdmin): ?>
                <div class="col-md-3"><label class="form-label small fw-semibold" for="rol">Rol (usuarios)</label>
                    <select class="form-select" id="rol" name="rol"><option value="">Todos</option>
                        <?php foreach (['voluntario' => 'Voluntario', 'fundacion' => 'Fundación', 'administrador' => 'Administrador'] as $k => $t): ?><option value="<?= $k ?>"<?= ($filtros['rol'] ?? '') === $k ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                    </select></div>
            <?php endif; ?>
            <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph me-1" aria-hidden="true"></i>Generar</button></div>
        </div>
    </form>

    <?php if (!$reporte): ?>
        <div class="card card-vr"><div class="vacio"><i class="bi bi-clipboard-data" aria-hidden="true"></i>Elige un tipo de reporte, aplica los filtros y pulsa «Generar».</div></div>
    <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-0"><?= e($reporte['titulo']) ?></h2>
                <div class="small text-body-secondary"><?= $reporte['filtros'] ? e(implode(' · ', $reporte['filtros'])) : 'Sin filtros' ?> · Generado <?= e(fecha($reporte['generado'], true)) ?></div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-danger" href="<?= url($accion, $query + ['formato' => 'pdf']) ?>"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Descargar PDF</a>
                <a class="btn btn-outline-success" href="<?= url($accion, $query + ['formato' => 'xlsx']) ?>"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Descargar Excel</a>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <?php foreach ($reporte['resumen'] as $etq => $val): ?>
                <div class="col-6 col-md-4 col-lg-2"><div class="stat p-3"><div class="etiqueta small"><?= e($etq) ?></div><div class="valor fs-4"><?= e(is_float($val) ? number_format($val, 1, ',', '.') : $val) ?></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="card card-vr">
            <div class="table-responsive">
                <table class="table table-vr table-sm table-striped mb-0">
                    <caption class="visually-hidden"><?= e($reporte['titulo']) ?></caption>
                    <thead><tr><?php foreach ($reporte['columnas'] as $c): ?><th scope="col"><?= e($c) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach ($reporte['filas'] as $fila): ?>
                        <tr><?php foreach ($fila as $celda): ?><td class="small"><?= e(is_float($celda) ? number_format($celda, 1, ',', '.') : $celda) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                    <?php if (!$reporte['filas']): ?><tr><td colspan="<?= count($reporte['columnas']) ?>" class="text-center text-body-secondary py-4">No hay datos para los filtros seleccionados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
