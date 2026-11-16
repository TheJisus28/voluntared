<?php /** @var string $titulo @var ?string $subtitulo @var ?string $acciones */ ?>
<div class="encabezado-pagina">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <?php if (!empty($migas)): ?>
                <nav aria-label="Ruta de navegación">
                    <ol class="breadcrumb small mb-1">
                        <?php foreach ($migas as $texto => $enlace): ?>
                            <li class="breadcrumb-item"><a href="<?= url($enlace) ?>"><?= e($texto) ?></a></li>
                        <?php endforeach; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($titulo) ?></li>
                    </ol>
                </nav>
            <?php endif; ?>
            <h1><?= e($titulo) ?></h1>
            <?php if (!empty($subtitulo)): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
        </div>
        <?php if (!empty($acciones)): ?><div class="d-flex flex-wrap gap-2"><?= $acciones ?></div><?php endif; ?>
    </div>
</div>
