<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Mis postulaciones', 'subtitulo' => 'Consulta el estado de cada solicitud que enviaste.']); ?>
    <ul class="nav nav-pills mb-3 flex-wrap gap-1">
        <li class="nav-item"><a class="nav-link<?= !$estado ? ' active' : '' ?>" href="<?= url('/postulaciones') ?>">Todas (<?= array_sum($conteo) ?>)</a></li>
        <?php foreach (['pendiente' => 'Pendientes', 'aceptada' => 'Aceptadas', 'rechazada' => 'Rechazadas', 'cancelada' => 'Canceladas'] as $k => $t): ?>
            <li class="nav-item"><a class="nav-link<?= $estado === $k ? ' active' : '' ?>" href="<?= url('/postulaciones', ['estado' => $k]) ?>"><?= $t ?> (<?= (int) $conteo[$k] ?>)</a></li>
        <?php endforeach; ?>
    </ul>
    <div class="card card-vr">
        <?php if (!$postulaciones): ?>
            <div class="vacio"><i class="bi bi-inbox" aria-hidden="true"></i>No tienes postulaciones<?= $estado ? ' en este estado' : '' ?>.<br><a href="<?= url('/oportunidades') ?>">Explorar oportunidades</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vr table-hover mb-0">
                    <caption class="visually-hidden">Listado de mis postulaciones</caption>
                    <thead><tr><th scope="col">Actividad</th><th scope="col">Fecha de la actividad</th><th scope="col">Postulación</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($postulaciones as $p): ?>
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="<?= url('/oportunidades/' . $p['Oportunidad_idOportunidad']) ?>"><?= e($p['titulo']) ?></a>
                                <div class="small text-body-secondary"><?= e($p['razonSocial']) ?> · <?= e($p['ciudad']) ?></div>
                            </td>
                            <td class="small"><?= e(fecha_corta($p['fechaInicio'])) ?><br><span class="text-body-secondary"><?= e(hora($p['horaInicio'])) ?> – <?= e(hora($p['horaFin'])) ?></span></td>
                            <td class="small"><?= e(fecha_corta($p['fechaPostulacion'])) ?></td>
                            <td>
                                <?= badge_estado('estado_postulacion', $p['estado']) ?>
                                <?php if (in_array($p['estadoOportunidad'], ['cancelada', 'finalizada'], true)): ?>
                                    <div class="small text-body-secondary mt-1">Actividad <?= e(mb_strtolower(etiqueta('estado_oportunidad', $p['estadoOportunidad']))) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if (in_array($p['estado'], ['pendiente', 'aceptada'], true) && in_array($p['estadoOportunidad'], ['abierta', 'cerrada'], true)): ?>
                                    <form class="d-inline" method="post" action="<?= url('/postulaciones/' . $p['idPostulacion'] . '/cancelar') ?>"
                                          data-confirmar="¿Cancelar tu postulación a «<?= e($p['titulo']) ?>»?<?= $p['estado'] === 'aceptada' ? ' Liberarás tu cupo para otra persona.' : '' ?>" data-confirmar-boton="Sí, cancelar">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($p['idParticipacion'] && !$p['evaluada']): ?>
                                    <a class="btn btn-sm btn-naranja" href="<?= url('/evaluar/' . $p['idParticipacion']) ?>"><i class="bi bi-star me-1" aria-hidden="true"></i>Evaluar</a>
                                <?php endif; ?>
                                <form class="d-inline" method="post" action="<?= url('/mensajes/iniciar') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="oportunidad" value="<?= (int) $p['Oportunidad_idOportunidad'] ?>">
                                    <button class="btn btn-sm btn-light border" title="Escribir a la fundación"><i class="bi bi-chat-dots" aria-hidden="true"></i><span class="visually-hidden">Escribir a la fundación</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
