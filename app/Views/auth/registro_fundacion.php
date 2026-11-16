<div class="auth-dividida">
    <div class="auth-form ancha">
        <div>
            <a href="<?= url('/registro') ?>" class="small fw-semibold">← Cambiar tipo de cuenta</a>
            <h1 class="mt-3">Registra tu fundación</h1>
            <p class="intro">Un administrador revisará el documento soporte antes de que puedas publicar convocatorias.</p>
            <form method="post" action="<?= url('/registro/fundacion') ?>" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <h2 class="titulo-seccion">La organización</h2>
                <div class="row">
                    <?php campo(['nombre' => 'razonSocial', 'etiqueta' => 'Razón social', 'requerido' => true, 'col' => 'col-md-7 mb-3', 'atributos' => 'maxlength="150" autocomplete="organization"']); ?>
                    <?php campo(['nombre' => 'nit', 'etiqueta' => 'NIT', 'requerido' => true, 'col' => 'col-md-5 mb-3', 'ayuda' => 'Ej.: 900123456-7', 'atributos' => 'maxlength="20" inputmode="numeric"']); ?>
                </div>
                <?php campo(['nombre' => 'descripcion', 'etiqueta' => 'A qué se dedican', 'tipo' => 'textarea', 'requerido' => true, 'filas' => 3, 'ayuda' => 'Mínimo 30 caracteres. Lo verán los voluntarios.', 'atributos' => 'maxlength="3000"']); ?>
                <div class="row">
                    <?php campo(['nombre' => 'direccion', 'etiqueta' => 'Dirección de la sede', 'requerido' => true, 'col' => 'col-md-7 mb-3', 'atributos' => 'maxlength="150" autocomplete="street-address"']); ?>
                    <?php campo(['nombre' => 'ciudad', 'etiqueta' => 'Ciudad', 'requerido' => true, 'col' => 'col-md-5 mb-3', 'atributos' => 'maxlength="80" autocomplete="address-level2"']); ?>
                </div>
                <div class="mb-4">
                    <label for="documento" class="form-label">RUT o certificado de existencia y representación legal <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="form-control<?= clase_invalida('documento') ?>" type="file" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png" required aria-describedby="ayuda-documento">
                    <div class="form-text" id="ayuda-documento">PDF, JPG o PNG de máximo <?= (int) config('subidas.max_mb') ?> MB. Solo lo verá el administrador.</div>
                    <?php if (error_campo('documento')): ?><div class="invalid-feedback d-block"><?= e(error_campo('documento')) ?></div><?php endif; ?>
                </div>

                <h2 class="titulo-seccion">Persona de contacto</h2>
                <div class="row">
                    <?php campo(['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'requerido' => true, 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="100" autocomplete="given-name"']); ?>
                    <?php campo(['nombre' => 'apellido', 'etiqueta' => 'Apellido', 'requerido' => true, 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="100" autocomplete="family-name"']); ?>
                    <?php campo(['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'tel', 'requerido' => true, 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="15" autocomplete="tel"']); ?>
                </div>
                <?php campo(['nombre' => 'email', 'etiqueta' => 'Correo electrónico (para iniciar sesión)', 'tipo' => 'email', 'requerido' => true, 'atributos' => 'maxlength="150" autocomplete="email"']); ?>
                <div class="row">
                    <?php campo(['nombre' => 'contrasena', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'requerido' => true, 'col' => 'col-md-6 mb-3', 'ayuda' => 'Mínimo 8 caracteres, con letras y números.', 'atributos' => 'minlength="8" autocomplete="new-password"']); ?>
                    <?php campo(['nombre' => 'contrasena_confirmacion', 'etiqueta' => 'Confirmar contraseña', 'tipo' => 'password', 'requerido' => true, 'col' => 'col-md-6 mb-3', 'atributos' => 'autocomplete="new-password"']); ?>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input<?= clase_invalida('acepta') ?>" type="checkbox" value="1" id="acepta" name="acepta" required <?= old('acepta') ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="acepta">Autorizo el tratamiento de los datos personales según la <a href="<?= url('/privacidad') ?>" target="_blank">política de tratamiento de datos</a> (Ley 1581 de 2012).</label>
                    <?php if (error_campo('acepta')): ?><div class="invalid-feedback d-block"><?= e(error_campo('acepta')) ?></div><?php endif; ?>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Registrar fundación</button>
            </form>
        </div>
    </div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
