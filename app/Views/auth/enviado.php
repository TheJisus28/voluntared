<div class="auth-dividida">
    <div class="auth-form"><div>
        <h1>Revisa tu correo</h1>
        <p>Enviamos un enlace de verificación a <strong><?= e($email) ?></strong>. Ábrelo para activar tu cuenta.</p>
        <?php if ($tipo === 'fundacion'): ?>
            <div class="alert alert-info text-start small">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                Notificamos al administrador para que revise tu documento soporte. Te avisaremos cuando la fundación quede verificada; mientras tanto podrás iniciar sesión y completar su perfil.
            </div>
        <?php endif; ?>
        <p class="small text-body-secondary">¿No te llegó? Revisa la carpeta de spam o <a href="<?= url('/verificar/reenviar') ?>">solicita un nuevo enlace</a>.</p>
        <?php if (config('app.entorno') === 'local' && config('correo.driver') !== 'smtp'): ?>
            <a class="btn btn-light btn-sm" href="<?= url('/dev/correos') ?>">Abrir bandeja de correos de desarrollo</a>
        <?php endif; ?>
    </div></div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
