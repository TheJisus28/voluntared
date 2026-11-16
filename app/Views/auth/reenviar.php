<div class="auth-dividida">
    <div class="auth-form"><div>
        <h1>Reenviar verificación</h1>
        <p class="intro">Ingresa tu correo y te enviaremos un nuevo enlace para activar tu cuenta.</p>
        <form method="post" action="<?= url('/verificar/reenviar') ?>" novalidate>
            <?= csrf_field() ?>
            <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true, 'atributos' => 'autocomplete="email"']); ?>
            <button class="btn btn-primary w-100" type="submit">Enviar enlace</button>
        </form>
        <p class="text-center mt-3 mb-0"><a href="<?= url('/login') ?>">Volver a iniciar sesión</a></p>
    </div></div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
