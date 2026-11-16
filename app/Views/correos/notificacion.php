<p>Hola, <strong><?= e($nombre) ?></strong>:</p>
<h2 style="font-size:18px;margin:0 0 8px;color:#145a38;"><?= e($titulo) ?></h2>
<p><?= e($contenido) ?></p>
<?php if (!empty($enlace)): ?>
    <p style="text-align:center;margin:26px 0;">
        <a href="<?= e(\App\Services\AuthService::absoluta(url($enlace))) ?>" style="background:#1f7a4d;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;display:inline-block;">Ver en VoluntaRed</a>
    </p>
<?php endif; ?>
