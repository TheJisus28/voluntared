<div class="container" style="max-width: 900px;">
    <div class="encabezado-pagina">
        <h1>Bandeja de correos (desarrollo)</h1>
        <p>Con <code>correo.driver = 'log'</code> los correos no se envían: se guardan en <code>storage/correos</code> y se pueden abrir aquí. Configura SMTP en <code>app/config.php</code> para enviarlos de verdad.</p>
    </div>
    <div class="card card-vr">
        <?php if (!$correos): ?>
            <div class="vacio"><i class="bi bi-inbox" aria-hidden="true"></i>No hay correos.</div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($correos as $c): ?>
                    <a class="list-group-item list-group-item-action py-3" href="<?= url('/dev/correos/' . $c['id']) ?>" target="_blank">
                        <div class="d-flex justify-content-between gap-2"><strong><?= e($c['asunto']) ?></strong><span class="small text-body-secondary text-nowrap"><?= e($c['fecha']) ?></span></div>
                        <div class="small text-body-secondary">Para: <?= e($c['para']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
