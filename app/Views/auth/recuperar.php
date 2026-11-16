<div class="auth-dividida">
    <div class="auth-form"><div>
        <h1>¿Olvidó su contraseña?</h1>
        <p class="intro">Ingresa el correo de tu cuenta. Te enviaremos un código temporal de 6 dígitos para crear una nueva contraseña.</p>
        <form method="post" action="<?= url('/recuperar') ?>" novalidate>
            <?= csrf_field() ?>
            <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true, 'atributos' => 'autocomplete="email" autofocus']); ?>
            <button class="btn btn-primary w-100" type="submit">Enviar código</button>
        </form>
        <p class="text-center mt-3 mb-0"><a href="<?= url('/login') ?>">Volver a iniciar sesión</a></p>
    </div></div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
