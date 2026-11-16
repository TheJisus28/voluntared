<?php
$u = usuario_actual();
$esVoluntario = $u['rol'] === 'voluntario';
$otro = static fn (array $c) => $esVoluntario
    ? ['nombre' => $c['razonSocial'], 'apellido' => null, 'foto' => $c['fundacionFoto']]
    : ['nombre' => trim($c['voluntarioNombre'] . ' ' . $c['voluntarioApellido']), 'apellido' => null, 'foto' => $c['voluntarioFoto']];
?>
<div class="container">
    <div class="encabezado-pagina"><h1>Mensajes</h1><p>Conversa con <?= $esVoluntario ? 'las fundaciones' : 'los voluntarios' ?> sobre las actividades.</p></div>
    <div class="card card-vr overflow-hidden">
        <div class="row g-0 chat-layout">
            <div class="col-md-4 border-end chat-lista h-100 <?= $actual ? 'd-none d-md-block' : '' ?>">
                <?php if (!$conversaciones): ?>
                    <div class="vacio"><i class="bi bi-chat-square-text" aria-hidden="true"></i>No tienes conversaciones.<?php if ($esVoluntario): ?><br><span class="small">Escribe a una fundación desde el detalle de una oportunidad.</span><?php endif; ?></div>
                <?php else: ?>
                    <div class="list-group list-group-flush" role="list">
                        <?php foreach ($conversaciones as $c): $o = $otro($c); $esActual = $actual && (int) $actual['idConversacion'] === (int) $c['idConversacion']; ?>
                            <a role="listitem" class="list-group-item list-group-item-action py-3<?= $esActual ? ' active' : '' ?>" href="<?= url('/mensajes/' . $c['idConversacion']) ?>"<?= $esActual ? ' aria-current="true"' : '' ?>>
                                <div class="d-flex gap-2 align-items-start">
                                    <?= avatar($o['foto'], $o['nombre'], null, 'sm') ?>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong class="text-truncate small"><?= e($o['nombre']) ?></strong>
                                            <span class="small text-body-secondary text-nowrap"><?= e(hace($c['ultimaFecha'] ?? $c['fechaCreacion'])) ?></span>
                                        </div>
                                        <?php if ($c['oportunidad']): ?><div class="small text-primary text-truncate"><?= e($c['oportunidad']) ?></div><?php endif; ?>
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="small text-body-secondary text-truncate"><?= e($c['ultimoMensaje'] ?? 'Sin mensajes aún') ?></span>
                                            <?php if ((int) $c['noLeidos'] > 0): ?><span class="badge rounded-pill text-bg-danger"><?= (int) $c['noLeidos'] ?><span class="visually-hidden"> sin leer</span></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-8 h-100 d-flex flex-column">
                <?php if (!$actual): ?>
                    <div class="vacio my-auto"><i class="bi bi-chat-heart" aria-hidden="true"></i>Selecciona una conversación para ver los mensajes.</div>
                <?php else: $o = $otro($actual); ?>
                    <div class="d-flex align-items-center gap-2 p-3 border-bottom bg-white">
                        <a class="btn btn-sm btn-light d-md-none" href="<?= url('/mensajes') ?>" aria-label="Volver a las conversaciones"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                        <?= avatar($o['foto'], $o['nombre'], null, 'sm') ?>
                        <div>
                            <h2 class="h6 fw-bold mb-0"><?= e($o['nombre']) ?></h2>
                            <?php if ($actual['oportunidad']): ?><div class="small text-body-secondary">Sobre: <a href="<?= url('/oportunidades/' . $actual['Oportunidad_idOportunidad']) ?>"><?= e($actual['oportunidad']) ?></a></div><?php endif; ?>
                        </div>
                    </div>
                    <div id="chat" class="d-flex flex-column flex-grow-1 overflow-hidden" data-conversacion="<?= (int) $actual['idConversacion'] ?>" data-ultimo="<?= $mensajes ? (int) end($mensajes)['idMensaje'] : 0 ?>">
                        <div id="chat-mensajes" class="chat-mensajes flex-grow-1 p-3 d-flex flex-column" aria-live="polite" aria-label="Mensajes de la conversación">
                            <?php if (!$mensajes): ?>
                                <div class="vacio my-auto small">Escribe el primer mensaje.</div>
                            <?php endif; ?>
                            <?php $diaAnterior = null; foreach ($mensajes as $m): $propio = (int) $m['emisor_id'] === (int) $u['idUsuario']; $dia = substr($m['fechaEnvio'], 0, 10); ?>
                                <?php if ($dia !== $diaAnterior): $diaAnterior = $dia; ?>
                                    <div class="text-center small text-body-secondary my-2"><span class="bg-white rounded-pill px-3 py-1 border"><?= e(fecha($dia)) ?></span></div>
                                <?php endif; ?>
                                <div class="burbuja <?= $propio ? 'propia' : 'ajena' ?>" data-mensaje="<?= (int) $m['idMensaje'] ?>"><?= e($m['contenido']) ?><div class="meta"><?= e(hora($m['fechaEnvio'])) ?><?php if ($propio): ?> <span class="estado-leido"><?php if ($m['leido']): ?>· <i class="bi bi-check2-all" aria-hidden="true"></i><span class="visually-hidden">Leído</span><?php endif; ?></span><?php endif; ?></div></div>
                            <?php endforeach; ?>
                        </div>
                        <form id="chat-form" method="post" action="<?= url('/mensajes/' . $actual['idConversacion']) ?>" class="border-top p-2 bg-white">
                            <?= csrf_field() ?>
                            <div class="d-flex gap-2 align-items-end">
                                <label for="chat-texto" class="visually-hidden">Escribe un mensaje</label>
                                <textarea id="chat-texto" name="contenido" class="form-control" rows="1" maxlength="2000" placeholder="Escribe un mensaje… (Enter para enviar)" required></textarea>
                                <button class="btn btn-primary" type="submit" aria-label="Enviar mensaje"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
                            </div>
                            <div id="chat-estado" class="small text-danger px-1" aria-live="assertive"></div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
