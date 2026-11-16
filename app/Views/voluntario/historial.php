<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', [
        'titulo' => 'Mi historial de voluntariado',
        'subtitulo' => 'Actividades en las que participaste, horas acumuladas y evaluaciones.',
        'acciones' => '<a class="btn btn-primary" href="' . url('/historial/certificado') . '"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Descargar certificado</a>',
    ]); ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat d-flex gap-3 align-items-center"><span class="icono"><i class="bi bi-clock-history" aria-hidden="true"></i></span><div><div class="valor"><?= e(horas($horas)) ?></div><div class="etiqueta">Total de horas de voluntariado</div></div></div></div>
        <div class="col-md-4"><div class="stat d-flex gap-3 align-items-center"><span class="icono"><i class="bi bi-journal-check" aria-hidden="true"></i></span><div><div class="valor"><?= count(array_filter($actividades, static fn ($a) => $a['asistio'])) ?></div><div class="etiqueta">Actividades realizadas</div></div></div></div>
        <div class="col-md-4"><div class="stat d-flex gap-3 align-items-center"><span class="icono naranja"><i class="bi bi-star" aria-hidden="true"></i></span><div><div class="valor"><?= $calificacion['promedio'] !== null ? number_format($calificacion['promedio'], 1, ',', '') : '—' ?></div><div class="etiqueta">Calificación recibida (<?= $calificacion['total'] ?>)</div></div></div></div>
    </div>

    <div class="card card-vr mb-4">
        <?php if (!$actividades): ?>
            <div class="vacio"><i class="bi bi-journal" aria-hidden="true"></i>Aún no tienes participaciones registradas. Cuando una fundación registre tu asistencia aparecerá aquí.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vr mb-0">
                    <caption class="visually-hidden">Historial de participaciones</caption>
                    <thead><tr><th scope="col">Actividad</th><th scope="col">Fecha</th><th scope="col">Asistencia</th><th scope="col">Horas</th><th scope="col">Calificación recibida</th><th scope="col" class="text-end">Mi evaluación</th></tr></thead>
                    <tbody>
                    <?php foreach ($actividades as $a): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold"><?= e($a['titulo']) ?></span>
                                <div class="small text-body-secondary"><a href="<?= url('/fundaciones/' . $a['idFundacion']) ?>"><?= e($a['razonSocial']) ?></a> · <?= e($a['categoria']) ?></div>
                                <?php if ($a['observacion']): ?><div class="small fst-italic mt-1">«<?= e($a['observacion']) ?>»</div><?php endif; ?>
                            </td>
                            <td class="small"><?= e(fecha_corta($a['fechaInicio'])) ?></td>
                            <td><?= $a['asistio'] ? '<span class="estado estado-aceptada">Asistió</span>' : '<span class="estado estado-cerrada">No asistió</span>' ?></td>
                            <td><?= e(horas($a['horasCumplidas'])) ?></td>
                            <td><?= $a['calificacionRecibida'] ? estrellas($a['calificacionRecibida'], false) : '<span class="small text-body-secondary">Pendiente</span>' ?></td>
                            <td class="text-end">
                                <?php if ($a['miCalificacion']): ?>
                                    <?= estrellas($a['miCalificacion'], false) ?>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-naranja" href="<?= url('/evaluar/' . $a['idParticipacion']) ?>"><i class="bi bi-star me-1" aria-hidden="true"></i>Evaluar fundación</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($evaluaciones): ?>
        <h2 class="h5 fw-bold mb-3">Comentarios de las fundaciones</h2>
        <div class="row g-3">
            <?php foreach ($evaluaciones as $ev): ?>
                <div class="col-md-6">
                    <div class="card card-vr p-3 h-100">
                        <div class="d-flex justify-content-between"><strong><?= e($ev['razonSocial'] ?? $ev['nombre']) ?></strong><?= estrellas($ev['calificacion'], false) ?></div>
                        <?php if ($ev['comentario']): ?><p class="mb-1 mt-2"><?= e($ev['comentario']) ?></p><?php endif; ?>
                        <div class="small text-body-secondary"><?= e($ev['titulo']) ?> · <?= e(fecha_corta($ev['fecha'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
