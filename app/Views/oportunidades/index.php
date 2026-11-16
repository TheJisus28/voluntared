<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', [
        'titulo' => 'Oportunidades de voluntariado',
        'subtitulo' => $conPerfil ? 'Primero las que coinciden con tus causas, habilidades y ciudad.' : 'Convocatorias abiertas de fundaciones verificadas.',
    ]); ?>
    <div class="row g-4 g-lg-5">
        <aside class="col-lg-3" aria-label="Filtros de búsqueda">
            <form method="get" action="<?= url('/oportunidades') ?>" role="search">
                <div class="mb-3">
                    <label for="q" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Título o fundación">
                </div>
                <div class="mb-3">
                    <label for="categoria" class="form-label">Causa</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['idCategoria'] ?>"<?= (int) $filtros['categoria'] === (int) $c['idCategoria'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="ciudad" class="form-label">Ciudad</label>
                    <select class="form-select" id="ciudad" name="ciudad">
                        <option value="">Todas</option>
                        <?php foreach ($ciudades as $c): ?>
                            <option<?= $filtros['ciudad'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <fieldset class="mb-3">
                    <legend class="form-label">Modalidad</legend>
                    <?php foreach (['' => 'Todas', 'presencial' => 'Presencial', 'virtual' => 'Virtual', 'mixta' => 'Mixta'] as $v => $t): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modalidad" id="mod-<?= $v ?: 'todas' ?>" value="<?= $v ?>"<?= $filtros['modalidad'] === $v ? ' checked' : '' ?>>
                            <label class="form-check-label" for="mod-<?= $v ?: 'todas' ?>"><?= $t ?></label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
                <div class="mb-3">
                    <label for="desde" class="form-label">Disponibles desde</label>
                    <input type="date" class="form-control" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
                </div>
                <div class="mb-4">
                    <label for="habilidad" class="form-label">Habilidad que pide</label>
                    <select class="form-select" id="habilidad" name="habilidad">
                        <option value="">Cualquiera</option>
                        <?php foreach ($habilidades as $h): ?>
                            <option value="<?= $h['idHabilidad'] ?>"<?= (int) $filtros['habilidad'] === (int) $h['idHabilidad'] ? ' selected' : '' ?>><?= e($h['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100" type="submit">Aplicar filtros</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn-link w-100 mt-1" href="<?= url('/oportunidades') ?>">Quitar filtros</a>
                <?php endif; ?>
            </form>
        </aside>

        <section class="col-lg-9" aria-labelledby="resultados">
            <p id="resultados" class="text-body-secondary mb-3" aria-live="polite">
                <strong class="text-body"><?= $total ?></strong> oportunidad<?= $total === 1 ? '' : 'es' ?><?= $hayFiltros ? ' con estos filtros' : ' abiertas' ?>
            </p>
            <?php if (!$oportunidades): ?>
                <div class="card-vr vacio">
                    <p class="h5 text-body mb-1">No encontramos oportunidades con esos criterios.</p>
                    <p class="mb-3">Prueba ampliar los filtros: quita la ciudad, la fecha o la habilidad.</p>
                    <a class="btn btn-light" href="<?= url('/oportunidades') ?>">Ver todas las oportunidades</a>
                </div>
            <?php else: ?>
                <div class="lista-oport">
                    <?php foreach ($oportunidades as $o): ?>
                        <?php View::parcial('tarjeta_oportunidad', ['o' => $o, 'mostrarAfinidad' => $conPerfil]); ?>
                    <?php endforeach; ?>
                </div>
                <?php View::parcial('paginacion', ['pagina' => $pagina, 'paginas' => $paginas]); ?>
            <?php endif; ?>
        </section>
    </div>
</div>
