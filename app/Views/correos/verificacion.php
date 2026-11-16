<p>Hola, <strong><?= e($nombre) ?></strong>:</p>
<p>¡Gracias por registrarte en VoluntaRed<?= $rol === 'fundacion' ? ' como fundación' : '' ?>! Para activar tu cuenta confirma tu correo electrónico con el siguiente botón:</p>
<p style="text-align:center;margin:28px 0;">
    <a href="<?= e($enlace) ?>" style="background:#1f7a4d;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;display:inline-block;">Confirmar mi correo</a>
</p>
<p style="font-size:13px;color:#5b6b63;">El enlace vence en <?= (int) config('seguridad.horas_verificacion') ?> horas. Si el botón no funciona, copia esta dirección en tu navegador:<br><span style="word-break:break-all;"><?= e($enlace) ?></span></p>
<?php if ($rol === 'fundacion'): ?>
    <p>Después de confirmar tu correo, el administrador revisará el documento soporte de la fundación. Te avisaremos cuando puedas publicar oportunidades.</p>
<?php endif; ?>
<p>Si no creaste esta cuenta, ignora este mensaje.</p>
