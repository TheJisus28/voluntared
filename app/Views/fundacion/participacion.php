<?php
use App\Core\View;

$finalizada = $o['estado'] === 'finalizada';
?>
<div class="container">
    <?php View::parcial('encabezado', [
        'titulo' => 'Registrar participación',
        'subtitulo' => $o['titulo'] . ' · ' . fecha($o['fechaInicio']) . ($o['fechaFin'] !== $o['fechaInicio'] ? ' al ' . fecha($o['fechaFin']) : ''),
        'migas' => ['Mis oportunidades' => '/fundacion/oportunidades'],
    ]); ?>

    <?php if (!$finalizada): ?>
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Para registrar la asistencia, primero marca la actividad como finalizada.</span>
            <?php if (in_array($o['estado'], ['abierta', 'cerrada'], true) && $o['fechaInicio'] <= date('Y-m-d')): ?>
                <form method="post" action="<?= url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/estado') ?>" data-confirmar="¿Marcar la actividad como finalizada?" data-confirmar-boton="Finalizar" data-confirmar-estilo="btn-primary">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="finalizar">
                    <button class="btn btn-sm btn-warning">Marcar como finalizada</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$voluntarios): ?>
        <div class="card card-vr"><div class="vacio"><i class="bi bi-people" aria-hidden="true"></i>Esta oportunidad no tiene voluntarios aceptados.</div></div>
    <?php else: ?>
        <form method="post" action="<?= url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/participacion') ?>" class="card card-vr">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table table-vr mb-0 align-middle">
                    <caption class="px-3 small">Marca la asistencia y las horas cumplidas de cada voluntario aceptado. Horas sugeridas por jornada: <?= e(horas($horasSugeridas)) ?>.</caption>
                    <thead><tr><th scope="col">Voluntario</th><th scope="col">Asistió</th><th scope="col" style="width:130px">Horas</th><th scope="col">Observación</th><th scope="col" class="text-end">Evaluación</th></tr></thead>
                    <tbody>
                    <?php foreach ($voluntarios as $v): $idp = (int) $v['idPostulacion']; ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?= avatar($v['foto'], $v['nombre'], $v['apellido'], 'sm') ?>
                                    <div>
                                        <span class="fw-semibold"><?= e($v['nombre'] . ' ' . $v['apellido']) ?></span>
                                        <?php if ($v['idParticipacion']): ?><div class="small text-success"><i class="bi bi-check-circle" aria-hidden="true"></i> Registrado</div><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="asistio-<?= $idp ?>" name="registro[<?= $idp ?>][asistio]" value="1"
                                        <?= $v['idParticipacion'] ? ($v['asistio'] ? 'checked' : '') : 'checked' ?><?= $finalizada ? '' : ' disabled' ?>>
                                    <label class="form-check-label visually-hidden" for="asistio-<?= $idp ?>">Asistió <?= e($v['nombre']) ?></label>
                                </div>
                            </td>
                            <td>
                                <label class="visually-hidden" for="horas-<?= $idp ?>">Horas de <?= e($v['nombre']) ?></label>
                                <input class="form-control form-control-sm" type="number" step="0.5" min="0" max="999" id="horas-<?= $idp ?>" name="registro[<?= $idp ?>][horas]"
                                       value="<?= e($v['idParticipacion'] ? (float) $v['horasCumplidas'] : $horasSugeridas) ?>"<?= $finalizada ? '' : ' disabled' ?>>
                            </td>
                            <td>
                                <label class="visually-hidden" for="obs-<?= $idp ?>">Observación sobre <?= e($v['nombre']) ?></label>
                                <input class="form-control form-control-sm" maxlength="300" id="obs-<?= $idp ?>" name="registro[<?= $idp ?>][observacion]" value="<?= e($v['observacion']) ?>"
                                       placeholder="Opcional"<?= $finalizada ? '' : ' disabled' ?>>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($v['calificacionDada']): ?>
                                    <?= estrellas($v['calificacionDada'], false) ?>
                                <?php elseif ($v['idParticipacion']): ?>
                                    <a class="btn btn-sm btn-naranja" href="<?= url('/evaluar/' . $v['idParticipacion']) ?>"><i class="bi bi-star me-1" aria-hidden="true"></i>Evaluar</a>
                                <?php else: ?>
                                    <span class="small text-body-secondary">Tras registrar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($finalizada): ?>
                <div class="p-3 d-flex justify-content-end border-top">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar participación</button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
