<?php
/** Opciones de cambio de estado de una oportunidad (elementos de un dropdown). @var array $o */
$id = (int) $o['idOportunidad'];
$acciones = [];
if ($o['estado'] === 'abierta') {
    $acciones['cerrar'] = ['Cerrar convocatoria', 'bi-lock', '¿Cerrar la convocatoria? Ya no se recibirán nuevas postulaciones.', 'btn-warning'];
}
if ($o['estado'] === 'cerrada') {
    $acciones['reabrir'] = ['Reabrir convocatoria', 'bi-unlock', '¿Reabrir la convocatoria para recibir postulaciones?', 'btn-primary'];
}
if (in_array($o['estado'], ['abierta', 'cerrada'], true) && $o['fechaInicio'] <= date('Y-m-d')) {
    $acciones['finalizar'] = ['Marcar como finalizada', 'bi-flag', '¿La actividad ya terminó? Después podrás registrar la asistencia y las horas.', 'btn-primary'];
}
if (in_array($o['estado'], ['abierta', 'cerrada'], true)) {
    $acciones['cancelar'] = ['Cancelar oportunidad', 'bi-x-circle', '¿Cancelar la oportunidad? Se notificará a todos los voluntarios postulados. Esta acción no se puede deshacer.', 'btn-danger'];
}
foreach ($acciones as $accion => [$texto, $icono, $confirmacion, $estilo]): ?>
    <li>
        <form method="post" action="<?= url("/fundacion/oportunidades/$id/estado") ?>" data-confirmar="<?= e($confirmacion) ?>" data-confirmar-boton="<?= e($texto) ?>" data-confirmar-estilo="<?= $estilo ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="<?= $accion ?>">
            <button class="dropdown-item<?= $accion === 'cancelar' ? ' text-danger' : '' ?>"><i class="bi <?= $icono ?> me-2" aria-hidden="true"></i><?= e($texto) ?></button>
        </form>
    </li>
<?php endforeach; ?>
