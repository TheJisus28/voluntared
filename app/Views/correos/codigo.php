<p>Hola, <strong><?= e($nombre) ?></strong>:</p>
<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Usa este código temporal:</p>
<p style="text-align:center;margin:26px 0;">
    <span style="display:inline-block;font-size:32px;letter-spacing:10px;font-weight:800;background:#e7f3ec;color:#145a38;padding:12px 22px;border-radius:10px;"><?= e($codigo) ?></span>
</p>
<p>El código vence en <strong><?= (int) $minutos ?> minutos</strong>. Si no solicitaste el cambio, ignora este mensaje: tu contraseña actual sigue siendo válida.</p>
