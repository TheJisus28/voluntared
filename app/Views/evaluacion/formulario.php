<div class="container">
    <div class="auth-card card card-vr p-4 p-md-5">
        <h1><?= e($titulo) ?></h1>
        <p class="text-body-secondary">¿Cómo fue tu experiencia con <strong><?= e($evaluado) ?></strong> en «<?= e($p['titulo']) ?>»?</p>
        <form method="post" action="<?= url('/evaluar/' . $p['idParticipacion']) ?>" novalidate>
            <?= csrf_field() ?>
            <fieldset class="mb-3 text-center">
                <legend class="form-label fw-semibold fs-6">Calificación <span class="text-danger" aria-hidden="true">*</span></legend>
                <div class="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="estrella-<?= $i ?>" name="calificacion" value="<?= $i ?>"<?= (int) old('calificacion') === $i ? ' checked' : '' ?> required>
                        <label for="estrella-<?= $i ?>" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>"><i class="bi bi-star-fill" aria-hidden="true"></i><span class="visually-hidden"><?= $i ?> de 5 estrellas</span></label>
                    <?php endfor; ?>
                </div>
                <?php if (error_campo('calificacion')): ?><div class="text-danger small"><?= e(error_campo('calificacion')) ?></div><?php endif; ?>
            </fieldset>
            <div class="mb-3">
                <label for="comentario" class="form-label fw-semibold">Comentario</label>
                <textarea id="comentario" name="comentario" class="form-control<?= clase_invalida('comentario') ?>" rows="4" maxlength="500" data-contar="contador-comentario"
                          placeholder="<?= $esVoluntario ? 'Cuéntales a otros voluntarios cómo fue la organización, el trato y la experiencia.' : 'Describe el compromiso, puntualidad y aporte del voluntario.' ?>"><?= e(old('comentario')) ?></textarea>
                <div class="form-text text-end" id="contador-comentario"></div>
            </div>
            <p class="small text-body-secondary"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Solo puedes evaluar una vez cada participación.</p>
            <div class="d-flex gap-2">
                <a class="btn btn-light flex-fill" href="<?= url($esVoluntario ? '/historial' : '/fundacion/oportunidades/' . $p['Oportunidad_idOportunidad'] . '/participacion') ?>">Cancelar</a>
                <button class="btn btn-primary flex-fill" type="submit">Enviar evaluación</button>
            </div>
        </form>
    </div>
</div>
