<div class="auth-dividida">
    <div class="auth-form">
        <div>
            <a href="<?= url('/registro') ?>" class="small fw-semibold">← Cambiar tipo de cuenta</a>
            <h1 class="mt-3">Únete como voluntario</h1>
            <p class="intro">Después de crear la cuenta completarás tu perfil con tus causas y habilidades.</p>

            <?php App\Core\View::parcial('boton_google', ['texto' => 'Registrarme con Google']); ?>
            <?php if (App\Services\FirebaseAuthService::habilitado()): ?>
                <div class="separador-o">o con tu correo</div>
            <?php endif; ?>

            <form method="post" action="<?= url('/registro/voluntario') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="row">
                    <?php campo(['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'requerido' => true, 'col' => 'col-sm-6 mb-3', 'atributos' => 'maxlength="100" autocomplete="given-name"']); ?>
                    <?php campo(['nombre' => 'apellido', 'etiqueta' => 'Apellido', 'requerido' => true, 'col' => 'col-sm-6 mb-3', 'atributos' => 'maxlength="100" autocomplete="family-name"']); ?>
                </div>
                <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true, 'atributos' => 'maxlength="150" autocomplete="email"']); ?>
                <?php campo(['nombre' => 'contrasena', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'requerido' => true, 'ayuda' => 'Mínimo 8 caracteres, con letras y números.', 'atributos' => 'minlength="8" autocomplete="new-password"']); ?>
                <?php campo(['nombre' => 'contrasena_confirmacion', 'etiqueta' => 'Confirmar contraseña', 'tipo' => 'password', 'requerido' => true, 'atributos' => 'autocomplete="new-password"']); ?>
                <div class="form-check mb-4">
                    <input class="form-check-input<?= clase_invalida('acepta') ?>" type="checkbox" value="1" id="acepta" name="acepta" required <?= old('acepta') ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="acepta">Autorizo el tratamiento de mis datos personales según la <a href="<?= url('/privacidad') ?>" target="_blank">política de tratamiento de datos</a> (Ley 1581 de 2012).</label>
                    <?php if (error_campo('acepta')): ?><div class="invalid-feedback d-block"><?= e(error_campo('acepta')) ?></div><?php endif; ?>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Crear cuenta</button>
            </form>
            <p class="mt-4 mb-0 text-body-secondary">¿Ya tienes cuenta? <a class="fw-semibold" href="<?= url('/login') ?>">Inicia sesión</a></p>
        </div>
    </div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
