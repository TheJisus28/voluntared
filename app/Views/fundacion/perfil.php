<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Perfil de la fundación', 'subtitulo' => 'La información que verán los voluntarios en tus oportunidades.']); ?>
    <?php View::parcial('estado_fundacion', ['f' => $f]); ?>
    <form method="post" action="<?= url('/fundacion/perfil') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card card-vr p-4 text-center mb-3">
                    <div class="mx-auto mb-2">
                        <?php if ($u['foto']): ?>
                            <img id="previa-logo" src="<?= e(upload_url($u['foto'])) ?>" alt="Logo de la fundación" class="avatar avatar-lg">
                        <?php else: ?>
                            <span class="avatar avatar-lg avatar-iniciales" aria-hidden="true"><?= e(iniciales($f['razonSocial'])) ?></span>
                            <img id="previa-logo" src="" alt="Vista previa del logo" class="avatar avatar-lg d-none">
                        <?php endif; ?>
                    </div>
                    <h2 class="h5 fw-bold mb-1"><?= e($f['razonSocial']) ?></h2>
                    <div class="mb-2"><?= badge_estado('verificacion', $f['estadoVerificacion']) ?></div>
                    <div class="mb-3"><?= estrellas($calificacion['promedio']) ?></div>
                    <label for="logo" class="btn btn-outline-primary btn-sm">Cambiar logo</label>
                    <input class="visually-hidden" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" data-vista-previa="previa-logo">
                    <?php if (error_campo('logo')): ?><div class="text-danger small mt-1"><?= e(error_campo('logo')) ?></div><?php endif; ?>
                    <?php if ($f['estadoVerificacion'] === 'verificada'): ?>
                        <a class="small d-block mt-3" href="<?= url('/fundaciones/' . $f['idFundacion']) ?>">Ver perfil público <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>
                <div class="card card-vr p-4">
                    <h2 class="h6 fw-bold">Documento soporte</h2>
                    <p class="small mb-2"><a href="<?= url('/fundacion/documento') ?>" target="_blank"><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Ver documento actual</a></p>
                    <label for="documento" class="form-label small fw-semibold">Reemplazar RUT o certificado</label>
                    <input class="form-control form-control-sm<?= clase_invalida('documento') ?>" type="file" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-text">Al cambiarlo, la fundación vuelve a estado «pendiente».</div>
                    <?php if (error_campo('documento')): ?><div class="invalid-feedback d-block"><?= e(error_campo('documento')) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-vr p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Datos de la organización</h2>
                    <div class="alert alert-light border small"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Si cambias el NIT o la razón social, la fundación vuelve a estado «pendiente» hasta una nueva verificación.</div>
                    <div class="row">
                        <?php campo(['nombre' => 'razonSocial', 'etiqueta' => 'Razón social', 'requerido' => true, 'valor' => old('razonSocial', $f['razonSocial']), 'col' => 'col-md-7 mb-3', 'atributos' => 'maxlength="150"']); ?>
                        <?php campo(['nombre' => 'nit', 'etiqueta' => 'NIT', 'requerido' => true, 'valor' => old('nit', $f['nit']), 'col' => 'col-md-5 mb-3', 'atributos' => 'maxlength="20"']); ?>
                    </div>
                    <?php campo(['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'requerido' => true, 'valor' => old('descripcion', $f['descripcion']), 'atributos' => 'maxlength="3000"']); ?>
                    <?php campo(['nombre' => 'mision', 'etiqueta' => 'Misión', 'tipo' => 'textarea', 'filas' => 3, 'valor' => old('mision', $f['mision']), 'atributos' => 'maxlength="500"']); ?>
                    <div class="row">
                        <?php campo(['nombre' => 'direccion', 'etiqueta' => 'Dirección', 'requerido' => true, 'valor' => old('direccion', $f['direccion']), 'col' => 'col-md-7 mb-3', 'atributos' => 'maxlength="150"']); ?>
                        <?php campo(['nombre' => 'ciudad', 'etiqueta' => 'Ciudad', 'requerido' => true, 'valor' => old('ciudad', $f['ciudad']), 'col' => 'col-md-5 mb-3', 'atributos' => 'maxlength="80"']); ?>
                    </div>
                    <?php campo(['nombre' => 'sitioWeb', 'etiqueta' => 'Sitio web o red social', 'tipo' => 'url', 'valor' => old('sitioWeb', $f['sitioWeb']), 'ayuda' => 'Ej.: https://www.mifundacion.org', 'atributos' => 'maxlength="200"']); ?>
                </div>
                <div class="card card-vr p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Persona de contacto</h2>
                    <div class="row">
                        <?php campo(['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'requerido' => true, 'valor' => old('nombre', $u['nombre']), 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="100"']); ?>
                        <?php campo(['nombre' => 'apellido', 'etiqueta' => 'Apellido', 'requerido' => true, 'valor' => old('apellido', $u['apellido']), 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="100"']); ?>
                        <?php campo(['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'tel', 'requerido' => true, 'valor' => old('telefono', $u['telefono']), 'col' => 'col-md-4 mb-3', 'atributos' => 'maxlength="15"']); ?>
                    </div>
                    <p class="small text-body-secondary mb-0">Correo de acceso: <strong><?= e($u['email']) ?></strong></p>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('/panel') ?>" class="btn btn-light">Cancelar</a>
                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Guardar cambios</button>
                </div>
            </div>
        </div>
    </form>
</div>
