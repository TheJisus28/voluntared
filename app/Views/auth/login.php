<div class="auth-dividida">
    <div class="auth-form">
        <div>
            <h1>Inicia sesión</h1>
            <p class="intro">Entra para postularte, conversar con las fundaciones y ver tus horas.</p>

            <?php App\Core\View::parcial('boton_google', ['texto' => 'Continuar con Google']); ?>
            <?php if (App\Services\FirebaseAuthService::habilitado()): ?>
                <div class="separador-o">o con tu correo</div>
            <?php endif; ?>

            <form method="post" action="<?= url('/login') ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="volver" value="<?= e($_GET['volver'] ?? '') ?>">
                <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true,
                    'valor' => old('email', $_GET['email'] ?? ''), 'atributos' => 'autocomplete="email"']); ?>
                <?php campo(['nombre' => 'contrasena', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'requerido' => true, 'atributos' => 'autocomplete="current-password"']); ?>
                <div class="d-flex justify-content-end mb-3">
                    <a href="<?= url('/recuperar') ?>" class="small fw-semibold">¿Olvidó su contraseña?</a>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Ingresar</button>
            </form>
            <p class="mt-4 mb-0 text-body-secondary">¿No tienes cuenta? <a class="fw-semibold" href="<?= url('/registro') ?>">Regístrate</a></p>
        </div>
    </div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
