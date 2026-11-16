<?php
use App\Core\View;

$i = $datos['indicadores'];
?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Estadísticas', 'subtitulo' => 'Del ' . fecha($datos['desde']) . ' al ' . fecha($datos['hasta']) . '.']); ?>
    <form class="card card-vr p-3 mb-4" method="get" action="<?= url('/admin/estadisticas') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-3"><label class="form-label small fw-semibold" for="desde">Desde</label><input class="form-control" type="date" id="desde" name="desde" value="<?= e($datos['desde']) ?>"></div>
            <div class="col-sm-6 col-md-3"><label class="form-label small fw-semibold" for="hasta">Hasta</label><input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($datos['hasta']) ?>"></div>
            <div class="col-sm-8 col-md-4"><label class="form-label small fw-semibold" for="categoria">Categoría</label>
                <select class="form-select" id="categoria" name="categoria"><option value="">Todas</option>
                    <?php foreach ($categorias as $c): ?><option value="<?= $c['idCategoria'] ?>"<?= (int) $datos['categoria'] === (int) $c['idCategoria'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-sm-4 col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Actualizar</button></div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <?php foreach ([
            [$i['usuarios_nuevos'], 'Usuarios nuevos en el periodo', 'bi-person-plus', ''],
            [$i['voluntarios'], 'Voluntarios activos', 'bi-people', ''],
            [$i['fundaciones_verificadas'], 'Fundaciones verificadas', 'bi-patch-check', ''],
            [$i['oportunidades'], 'Oportunidades publicadas', 'bi-megaphone', 'naranja'],
            [$i['postulaciones'], 'Postulaciones', 'bi-send', ''],
            [$i['tasa_aceptacion'] . ' %', 'Tasa de aceptación', 'bi-check2-circle', ''],
            [$i['participantes'], 'Voluntarios que participaron', 'bi-person-check', ''],
            [horas($i['horas']), 'Horas de voluntariado', 'bi-clock-history', 'naranja'],
        ] as [$valor, $texto, $icono, $clase]): ?>
            <div class="col-6 col-lg-3">
                <div class="stat d-flex gap-3 align-items-center">
                    <span class="icono <?= $clase ?>"><i class="bi <?= $icono ?>" aria-hidden="true"></i></span>
                    <div><div class="valor"><?= e($valor) ?></div><div class="etiqueta"><?= $texto ?></div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8"><div class="card card-vr p-3"><h2 class="h6 fw-bold">Postulaciones y oportunidades por mes</h2><div class="grafico"><canvas id="graficoMeses" role="img" aria-label="Gráfico de líneas de postulaciones y oportunidades publicadas por mes"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card card-vr p-3"><h2 class="h6 fw-bold">Postulaciones por estado</h2><div class="grafico"><canvas id="graficoEstados" role="img" aria-label="Gráfico circular de postulaciones por estado"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card card-vr p-3"><h2 class="h6 fw-bold">Oportunidades por categoría</h2><div class="grafico"><canvas id="graficoCategorias" role="img" aria-label="Gráfico de barras de oportunidades por categoría"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card card-vr p-3"><h2 class="h6 fw-bold">Horas de voluntariado por categoría</h2><div class="grafico"><canvas id="graficoHoras" role="img" aria-label="Gráfico de barras de horas por categoría"></canvas></div></div></div>
        <div class="col-12">
            <div class="card card-vr">
                <div class="p-3 pb-0"><h2 class="h6 fw-bold">Fundaciones con más horas de voluntariado en el periodo</h2></div>
                <div class="table-responsive">
                    <table class="table table-vr mb-0">
                        <thead><tr><th scope="col">#</th><th scope="col">Fundación</th><th scope="col">Oportunidades</th><th scope="col">Horas</th></tr></thead>
                        <tbody>
                        <?php foreach ($datos['top_fundaciones'] as $n => $f): ?>
                            <tr><td><?= $n + 1 ?></td><td><?= e($f['razonSocial']) ?></td><td><?= (int) $f['oportunidades'] ?></td><td><?= e(horas($f['horas'])) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$datos['top_fundaciones']): ?><tr><td colspan="4" class="text-center text-body-secondary">Sin datos en el periodo.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/json" id="datos-graficos"><?= json_encode($datos['graficos'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
