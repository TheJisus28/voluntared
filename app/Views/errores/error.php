<div class="container">
    <div class="auth-card text-center py-5">
        <div class="display-1 fw-bold text-primary"><?= (int) $codigo ?></div>
        <h1><?= e($titulo) ?></h1>
        <?php if (!empty($mensaje)): ?><p class="text-body-secondary"><?= e($mensaje) ?></p><?php endif; ?>
        <?php if (!empty($detalle)): ?><pre class="small text-start bg-light p-2 rounded"><?= e($detalle) ?></pre><?php endif; ?>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <a class="btn btn-light border" href="javascript:history.back()">Volver</a>
            <a class="btn btn-primary" href="<?= url(usuario_actual() ? '/panel' : '/') ?>">Ir al inicio</a>
        </div>
    </div>
</div>
