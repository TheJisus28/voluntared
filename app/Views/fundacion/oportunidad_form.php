<?php
use App\Core\Flash;
use App\Core\View;

$v = static fn (string $campo, $defecto = '') => old($campo, $o[$campo] ?? $defecto);
$sel = Flash::hayOld() ? array_map('intval', (array) old('habilidades', [])) : array_map('intval', $seleccionadas);
$accion = $o ? url('/fundacion/oportunidades/' . $o['idOportunidad']) : url('/fundacion/oportunidades');
?>
<div class="container" style="max-width: 980px;">
    <?php View::parcial('encabezado', [
        'titulo' => $titulo,
        'subtitulo' => $o ? 'Si cambias la fecha, el horario o el lugar avisaremos a los voluntarios postulados.' : 'Al publicarla notificaremos a los voluntarios interesados en la categoría.',
        'migas' => ['Mis oportunidades' => '/fundacion/oportunidades'],
    ]); ?>
    <form method="post" action="<?= $accion ?>" novalidate class="card card-vr p-4">
        <?= csrf_field() ?>
        <h2 class="h6 text-uppercase text-primary fw-bold mb-3">Información general</h2>
        <?php campo(['nombre' => 'titulo', 'etiqueta' => 'Título de la actividad', 'requerido' => true, 'valor' => $v('titulo'), 'atributos' => 'maxlength="150" placeholder="Ej.: Jornada de lectura con niños del barrio La Granja"']); ?>
        <?php campo(['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'requerido' => true, 'filas' => 5, 'valor' => $v('descripcion'),
            'ayuda' => 'Explica qué harán los voluntarios, a quién beneficia y cómo se organizará la jornada.', 'atributos' => 'maxlength="5000"']); ?>
        <?php campo(['nombre' => 'requisitos', 'etiqueta' => 'Requisitos', 'tipo' => 'textarea', 'filas' => 2, 'valor' => $v('requisitos'),
            'ayuda' => 'Edad mínima, experiencia, documentos, materiales que deben llevar, etc.', 'atributos' => 'maxlength="500"']); ?>
        <div class="row">
            <?php campo(['nombre' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'select', 'requerido' => true, 'col' => 'col-md-6 mb-3',
                'valor' => old('categoria', $o['Categoria_idCategoria'] ?? ''), 'opciones' => array_column($categorias, 'nombre', 'idCategoria')]); ?>
            <?php campo(['nombre' => 'cuposTotales', 'etiqueta' => 'Cupos (número de voluntarios)', 'tipo' => 'number', 'requerido' => true, 'col' => 'col-md-6 mb-3',
                'valor' => $v('cuposTotales'), 'atributos' => 'min="1" max="1000"',
                'ayuda' => $o ? 'No puede ser menor que el número de voluntarios ya aceptados.' : null]); ?>
        </div>

        <h2 class="h6 text-uppercase text-primary fw-bold mt-3 mb-3">Lugar</h2>
        <div class="row">
            <?php campo(['nombre' => 'modalidad', 'etiqueta' => 'Modalidad', 'tipo' => 'select', 'requerido' => true, 'col' => 'col-md-4 mb-3', 'valor' => $v('modalidad'),
                'opciones' => ['presencial' => 'Presencial', 'virtual' => 'Virtual', 'mixta' => 'Mixta']]); ?>
            <?php campo(['nombre' => 'ciudad', 'etiqueta' => 'Ciudad', 'requerido' => true, 'col' => 'col-md-4 mb-3', 'valor' => $v('ciudad', $ciudadFundacion), 'atributos' => 'maxlength="80"']); ?>
            <div class="col-md-4 mb-3">
                <label for="ubicacion" class="form-label fw-semibold">Dirección o lugar <span class="text-danger d-none" id="ubicacion-obligatoria" aria-hidden="true">*</span></label>
                <input id="ubicacion" name="ubicacion" class="form-control<?= clase_invalida('ubicacion') ?>" maxlength="200" value="<?= e($v('ubicacion')) ?>" aria-describedby="ayuda-ubicacion">
                <div class="form-text" id="ayuda-ubicacion">Obligatoria si es presencial o mixta.</div>
                <?php if (error_campo('ubicacion')): ?><div class="invalid-feedback d-block"><?= e(error_campo('ubicacion')) ?></div><?php endif; ?>
            </div>
        </div>

        <h2 class="h6 text-uppercase text-primary fw-bold mt-3 mb-3">Fechas y horario</h2>
        <div class="row">
            <?php campo(['nombre' => 'fechaInicio', 'etiqueta' => 'Fecha de inicio', 'tipo' => 'date', 'requerido' => true, 'col' => 'col-md-3 col-6 mb-3', 'valor' => $v('fechaInicio'),
                'atributos' => $o ? '' : 'min="' . date('Y-m-d') . '"']); ?>
            <?php campo(['nombre' => 'fechaFin', 'etiqueta' => 'Fecha de fin', 'tipo' => 'date', 'requerido' => true, 'col' => 'col-md-3 col-6 mb-3', 'valor' => $v('fechaFin')]); ?>
            <?php campo(['nombre' => 'horaInicio', 'etiqueta' => 'Hora de inicio', 'tipo' => 'time', 'requerido' => true, 'col' => 'col-md-3 col-6 mb-3', 'valor' => substr((string) $v('horaInicio'), 0, 5)]); ?>
            <?php campo(['nombre' => 'horaFin', 'etiqueta' => 'Hora de fin', 'tipo' => 'time', 'requerido' => true, 'col' => 'col-md-3 col-6 mb-3', 'valor' => substr((string) $v('horaFin'), 0, 5)]); ?>
        </div>

        <fieldset class="mt-2">
            <legend class="h6 text-uppercase text-primary fw-bold mb-1">Habilidades deseables</legend>
            <p class="small text-body-secondary">Ayudan a recomendar la oportunidad a los voluntarios adecuados.</p>
            <div class="row">
                <?php foreach ($habilidades as $h): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="habilidades[]" id="hab-<?= $h['idHabilidad'] ?>" value="<?= $h['idHabilidad'] ?>"<?= in_array((int) $h['idHabilidad'], $sel, true) ? ' checked' : '' ?>>
                            <label class="form-check-label" for="hab-<?= $h['idHabilidad'] ?>"><?= e($h['nombre']) ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a class="btn btn-light" href="<?= url('/fundacion/oportunidades') ?>">Cancelar</a>
            <button class="btn btn-primary px-4" type="submit"><i class="bi bi-<?= $o ? 'check2' : 'send' ?> me-1" aria-hidden="true"></i><?= $o ? 'Guardar cambios' : 'Publicar oportunidad' ?></button>
        </div>
    </form>
</div>
