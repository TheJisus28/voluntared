<div class="container">
    <div class="auth-card card card-vr p-4 p-md-5">
        <h1>Cambiar contraseña</h1>
        <p class="text-body-secondary">Por seguridad, confirma tu contraseña actual.</p>
        <form method="post" action="<?= url('/cuenta/contrasena') ?>" novalidate>
            <?= csrf_field() ?>
            <?php campo(['nombre' => 'actual', 'etiqueta' => 'Contraseña actual', 'tipo' => 'password', 'requerido' => true, 'atributos' => 'autocomplete="current-password"']); ?>
            <?php campo(['nombre' => 'contrasena', 'etiqueta' => 'Nueva contraseña', 'tipo' => 'password', 'requerido' => true, 'ayuda' => 'Mínimo 8 caracteres, con letras y números.', 'atributos' => 'autocomplete="new-password"']); ?>
            <?php campo(['nombre' => 'contrasena_confirmacion', 'etiqueta' => 'Confirmar contraseña', 'tipo' => 'password', 'requerido' => true, 'atributos' => 'autocomplete="new-password"']); ?>
            <div class="d-flex gap-2">
                <a class="btn btn-light flex-fill" href="<?= url('/panel') ?>">Cancelar</a>
                <button class="btn btn-primary flex-fill" type="submit">Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>
