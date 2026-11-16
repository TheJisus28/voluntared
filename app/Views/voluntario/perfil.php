<?php
use App\Core\Flash;
use App\Core\View;

// Si el formulario volvió con errores se respetan las selecciones enviadas.
$selHab = Flash::hayOld() ? array_map('intval', (array) old('habilidades', [])) : array_map('intval', $misHabilidades);
$selCat = Flash::hayOld() ? array_map('intval', (array) old('categorias', [])) : array_map('intval', $misCategorias);
$p = $perfil ?? [];
?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Mi perfil', 'subtitulo' => 'Esta información la usan las fundaciones para conocerte y el sistema para recomendarte oportunidades.']); ?>
    <form method="post" action="<?= url('/perfil') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card card-vr p-4 text-center">
                    <div class="mx-auto mb-2">
                        <?php if ($u['foto']): ?>
                            <img id="previa-foto" src="<?= e(upload_url($u['foto'])) ?>" alt="Tu foto de perfil" class="avatar avatar-lg">
                        <?php else: ?>
                            <span class="avatar avatar-lg avatar-iniciales" aria-hidden="true"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></span>
                            <img id="previa-foto" src="" alt="Vista previa de tu foto" class="avatar avatar-lg d-none">
                        <?php endif; ?>
                    </div>
                    <h2 class="h5 fw-bold mb-0"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></h2>
                    <p class="small text-body-secondary"><?= e($u['email']) ?></p>
                    <label for="foto" class="btn btn-outline-primary btn-sm">Cambiar foto</label>
                    <input class="visually-hidden" type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" data-vista-previa="previa-foto">
                    <?php if (error_campo('foto')): ?><div class="text-danger small mt-1"><?= e(error_campo('foto')) ?></div><?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-around">
                        <div><div class="fw-bold fs-5"><?= e(horas($horas)) ?></div><div class="small text-body-secondary">Voluntariado</div></div>
                        <div><div class="fw-bold fs-5"><?= $calificacion['promedio'] !== null ? number_format($calificacion['promedio'], 1, ',', '') : '—' ?></div><div class="small text-body-secondary">Calificación</div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-vr p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Datos personales</h2>
                    <div class="row">
                        <?php campo(['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'requerido' => true, 'valor' => old('nombre', $u['nombre']), 'col' => 'col-md-6 mb-3', 'atributos' => 'maxlength="100"']); ?>
                        <?php campo(['nombre' => 'apellido', 'etiqueta' => 'Apellido', 'requerido' => true, 'valor' => old('apellido', $u['apellido']), 'col' => 'col-md-6 mb-3', 'atributos' => 'maxlength="100"']); ?>
                        <?php campo(['nombre' => 'fechaNacimiento', 'etiqueta' => 'Fecha de nacimiento', 'tipo' => 'date', 'requerido' => true, 'valor' => old('fechaNacimiento', $p['fechaNacimiento'] ?? ''), 'col' => 'col-md-6 mb-3', 'atributos' => 'max="' . date('Y-m-d') . '"']); ?>
                        <?php campo(['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'tel', 'valor' => old('telefono', $u['telefono']), 'col' => 'col-md-6 mb-3', 'atributos' => 'maxlength="15"']); ?>
                        <?php campo(['nombre' => 'ciudad', 'etiqueta' => 'Ciudad', 'requerido' => true, 'valor' => old('ciudad', $p['ciudad'] ?? ''), 'col' => 'col-md-6 mb-3', 'atributos' => 'maxlength="80"']); ?>
                        <?php campo(['nombre' => 'departamento', 'etiqueta' => 'Departamento', 'tipo' => 'select', 'requerido' => true, 'valor' => old('departamento', $p['departamento'] ?? 'Córdoba'),
                            'opciones' => array_combine(DEPARTAMENTOS, DEPARTAMENTOS), 'col' => 'col-md-6 mb-3']); ?>
                        <?php campo(['nombre' => 'disponibilidad', 'etiqueta' => 'Disponibilidad', 'tipo' => 'select', 'requerido' => true, 'valor' => old('disponibilidad', $p['disponibilidad'] ?? ''), 'col' => 'col-md-6 mb-3',
                            'opciones' => ['mananas' => 'Mañanas', 'tardes' => 'Tardes', 'noches' => 'Noches', 'fines_de_semana' => 'Fines de semana', 'flexible' => 'Flexible']]); ?>
                    </div>
                    <?php campo(['nombre' => 'biografia', 'etiqueta' => 'Sobre mí', 'tipo' => 'textarea', 'filas' => 3, 'valor' => old('biografia', $p['biografia'] ?? ''),
                        'ayuda' => 'Una breve presentación: qué te motiva, tu experiencia, etc.', 'atributos' => 'maxlength="500"']); ?>
                </div>

                <div class="card card-vr p-4 mb-4">
                    <fieldset>
                        <legend class="h5 fw-bold mb-1">Causas que te interesan <span class="text-danger" aria-hidden="true">*</span></legend>
                        <p class="small text-body-secondary">Te notificaremos cuando se publiquen oportunidades en estas categorías.</p>
                        <div class="row">
                            <?php foreach ($categorias as $c): ?>
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" id="cat-<?= $c['idCategoria'] ?>" value="<?= $c['idCategoria'] ?>"<?= in_array((int) $c['idCategoria'], $selCat, true) ? ' checked' : '' ?>>
                                        <label class="form-check-label" for="cat-<?= $c['idCategoria'] ?>"><?= e($c['nombre']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (error_campo('categorias')): ?><div class="text-danger small mt-2"><?= e(error_campo('categorias')) ?></div><?php endif; ?>
                    </fieldset>
                </div>

                <div class="card card-vr p-4 mb-4">
                    <fieldset>
                        <legend class="h5 fw-bold mb-1">Mis habilidades</legend>
                        <p class="small text-body-secondary">Las fundaciones las ven al revisar tu postulación.</p>
                        <div class="row">
                            <?php foreach ($habilidades as $h): ?>
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="habilidades[]" id="hab-<?= $h['idHabilidad'] ?>" value="<?= $h['idHabilidad'] ?>"<?= in_array((int) $h['idHabilidad'], $selHab, true) ? ' checked' : '' ?>>
                                        <label class="form-check-label" for="hab-<?= $h['idHabilidad'] ?>"><?= e($h['nombre']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (error_campo('habilidades')): ?><div class="text-danger small mt-2"><?= e(error_campo('habilidades')) ?></div><?php endif; ?>
                    </fieldset>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('/panel') ?>" class="btn btn-light">Cancelar</a>
                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Guardar cambios</button>
                </div>
            </div>
        </div>
    </form>
</div>
