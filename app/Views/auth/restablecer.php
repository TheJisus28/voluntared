<div class="auth-dividida">
    <div class="auth-form"><div>
        <h1>Crea una nueva contraseña</h1>
        <p class="intro">Escribe el código que recibiste por correo y tu nueva contraseña.</p>
        <form method="post" action="<?= url('/restablecer') ?>" novalidate>
            <?= csrf_field() ?>
            <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true, 'valor' => old('email', $email), 'atributos' => 'autocomplete="email"']); ?>
            <?php campo(['nombre' => 'codigo', 'etiqueta' => 'Código de verificación', 'requerido' => true, 'ayuda' => 'Código de 6 dígitos enviado a tu correo.',
                'atributos' => 'maxlength="6" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" style="letter-spacing:.4em;font-size:1.3rem;"']); ?>
            <?php campo(['nombre' => 'contrasena', 'etiqueta' => 'Nueva contraseña', 'tipo' => 'password', 'requerido' => true, 'ayuda' => 'Mínimo 8 caracteres, con letras y números.', 'atributos' => 'autocomplete="new-password"']); ?>
            <?php campo(['nombre' => 'contrasena_confirmacion', 'etiqueta' => 'Confirmar contraseña', 'tipo' => 'password', 'requerido' => true, 'atributos' => 'autocomplete="new-password"']); ?>
            <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
        </form>
        <p class="text-center mt-3 mb-0"><a href="<?= url('/recuperar') ?>">Solicitar otro código</a></p>
    </div></div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
