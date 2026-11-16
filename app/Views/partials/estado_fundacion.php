<?php /** @var array $f */ ?>
<?php if ($f['estadoVerificacion'] === 'pendiente'): ?>
    <div class="alert alert-warning banner-estado border-warning" role="status">
        <h2 class="h6 fw-bold mb-1">Tu fundación está pendiente de verificación</h2>
        <p class="mb-0 small">El administrador está revisando el NIT y el documento soporte. Mientras tanto solo puedes editar el perfil de la fundación; te notificaremos cuando puedas publicar oportunidades.</p>
    </div>
<?php elseif ($f['estadoVerificacion'] === 'rechazada'): ?>
    <div class="alert alert-danger banner-estado border-danger" role="status">
        <h2 class="h6 fw-bold mb-1">La verificación fue rechazada</h2>
        <p class="mb-2 small"><strong>Motivo:</strong> <?= e($f['motivoRechazo'] ?: 'No especificado') ?></p>
        <a class="btn btn-sm btn-danger" href="<?= url('/fundacion/perfil') ?>">Corregir datos y reenviar</a>
    </div>
<?php endif; ?>
