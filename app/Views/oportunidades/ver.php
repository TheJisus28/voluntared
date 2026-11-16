<?php
use App\Core\View;

$u = usuario_actual();
$ocupados = (int) $o['cuposTotales'] - (int) $o['cuposDisponibles'];
$porcentaje = $o['cuposTotales'] ? round($ocupados * 100 / $o['cuposTotales']) : 0;
$fechas = $o['fechaInicio'] === $o['fechaFin'] ? fecha($o['fechaInicio']) : 'Del ' . fecha($o['fechaInicio']) . ' al ' . fecha($o['fechaFin']);
?>
<div class="container">
    <div class="encabezado-pagina">
        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= url($esDuena ? '/fundacion/oportunidades' : '/oportunidades') ?>"><?= $esDuena ? 'Mis oportunidades' : 'Oportunidades' ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($o['categoria']) ?></li>
            </ol>
        </nav>
        <h1><?= e($o['titulo']) ?></h1>
        <p class="d-flex flex-wrap gap-3 align-items-center">
            <a class="text-body fw-semibold" href="<?= url('/fundaciones/' . $o['idFundacion']) ?>"><?= e($o['razonSocial']) ?></a>
            <?= badge_estado('estado_oportunidad', $o['estado']) ?>
            <span><?= e(etiqueta('modalidad', $o['modalidad'])) ?> · <?= e($o['ciudad']) ?></span>
        </p>
    </div>

    <?php if (!$o['visible']): ?>
        <div class="alert alert-warning">Esta oportunidad fue ocultada por moderación y no es visible para los voluntarios.</div>
    <?php endif; ?>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-7">
            <h2 class="titulo-seccion">Sobre la actividad</h2>
            <p class="prosa"><?= e($o['descripcion']) ?></p>

            <?php if ($o['requisitos']): ?>
                <h2 class="titulo-seccion mt-4">Requisitos</h2>
                <p class="prosa"><?= e($o['requisitos']) ?></p>
            <?php endif; ?>

            <?php if ($o['habilidades']): ?>
                <h2 class="titulo-seccion mt-4">Habilidades que ayudan</h2>
                <div><?php foreach ($o['habilidades'] as $h): ?><span class="chip"><?= e($h['nombre']) ?></span><?php endforeach; ?></div>
            <?php endif; ?>

            <div class="card-vr p-4 mt-4 d-flex gap-3 align-items-center">
                <?= avatar($o['fundacionFoto'], $o['razonSocial'], null, 'md') ?>
                <div class="flex-grow-1">
                    <div class="small text-body-secondary">Organiza</div>
                    <a class="fw-semibold text-body" href="<?= url('/fundaciones/' . $o['idFundacion']) ?>"><?= e($o['razonSocial']) ?></a>
                    <div class="small"><?= estrellas($calificacion['promedio']) ?> <span class="text-body-secondary">· <?= $calificacion['total'] ?> evaluaciones · fundación verificada</span></div>
                </div>
                <a class="btn btn-light btn-sm" href="<?= url('/fundaciones/' . $o['idFundacion']) ?>">Ver perfil</a>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-vr p-4 ficha-lateral">
                <dl class="datos mb-4">
                    <dt>Cuándo</dt><dd><?= e($fechas) ?></dd>
                    <dt>Horario</dt><dd><?= e(hora($o['horaInicio'])) ?> a <?= e(hora($o['horaFin'])) ?></dd>
                    <dt>Dónde</dt><dd><?= e($o['ubicacion'] ? $o['ubicacion'] . ', ' . $o['ciudad'] : $o['ciudad'] . ' (virtual)') ?></dd>
                    <dt>Cupos</dt>
                    <dd>
                        <?= (int) $o['cuposDisponibles'] ?> de <?= (int) $o['cuposTotales'] ?> disponibles
                        <div class="progress mt-2" role="progressbar" aria-label="Cupos ocupados" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100" style="height: .45rem;">
                            <div class="progress-bar" style="width: <?= $porcentaje ?>%"></div>
                        </div>
                    </dd>
                </dl>

                <?php if ($esDuena): ?>
                    <a class="btn btn-primary w-100" href="<?= url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/postulaciones') ?>">Ver postulaciones</a>
                    <?php if (in_array($o['estado'], ['abierta', 'cerrada'], true)): ?>
                        <a class="btn btn-light w-100 mt-2" href="<?= url('/fundacion/oportunidades/' . $o['idOportunidad'] . '/editar') ?>">Modificar</a>
                    <?php endif; ?>
                <?php elseif (!$u): ?>
                    <a class="btn btn-primary btn-lg w-100" href="<?= url('/login', ['volver' => url('/oportunidades/' . $o['idOportunidad'])]) ?>">Inicia sesión para postularte</a>
                    <p class="small text-center mt-2 mb-0">¿Nuevo en VoluntaRed? <a href="<?= url('/registro/voluntario') ?>">Crea tu cuenta</a></p>
                <?php elseif ($u['rol'] === 'voluntario'): ?>
                    <?php if ($postulacion && $postulacion['estado'] !== 'cancelada'): ?>
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Tu postulación</span><?= badge_estado('estado_postulacion', $postulacion['estado']) ?>
                            </div>
                            <div class="small text-body-secondary mb-3">Enviada el <?= e(fecha($postulacion['fechaPostulacion'], true)) ?></div>
                        </div>
                        <?php if (in_array($postulacion['estado'], ['pendiente', 'aceptada'], true) && in_array($o['estado'], ['abierta', 'cerrada'], true)): ?>
                            <form method="post" action="<?= url('/postulaciones/' . $postulacion['idPostulacion'] . '/cancelar') ?>"
                                  data-confirmar="¿Deseas cancelar tu postulación a «<?= e($o['titulo']) ?>»?<?= $postulacion['estado'] === 'aceptada' ? ' Liberarás tu cupo.' : '' ?>" data-confirmar-boton="Sí, cancelar">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger w-100">Cancelar postulación</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($o['estado'] !== 'abierta' || (int) $o['cuposDisponibles'] === 0): ?>
                        <div class="alert alert-secondary mb-0">Esta oportunidad ya no recibe postulaciones.</div>
                    <?php elseif (!$u['perfil']): ?>
                        <div class="alert alert-warning small">Completa tu perfil para poder postularte.</div>
                        <a class="btn btn-primary w-100" href="<?= url('/perfil') ?>">Completar mi perfil</a>
                    <?php else: ?>
                        <form method="post" action="<?= url('/oportunidades/' . $o['idOportunidad'] . '/postular') ?>" class="border-top pt-3">
                            <?= csrf_field() ?>
                            <label for="mensaje" class="form-label">Mensaje para la fundación <span class="fw-normal text-body-secondary">(opcional)</span></label>
                            <textarea id="mensaje" name="mensaje" class="form-control mb-1" rows="3" maxlength="500" data-contar="contador-mensaje"
                                      placeholder="¿Por qué te interesa participar?"><?= e(old('mensaje')) ?></textarea>
                            <div class="form-text text-end mb-3" id="contador-mensaje"></div>
                            <button class="btn btn-primary btn-lg w-100" type="submit">Postularme</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= url('/mensajes/iniciar') ?>" class="mt-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="oportunidad" value="<?= (int) $o['idOportunidad'] ?>">
                        <button class="btn btn-light w-100">Escribir a la fundación</button>
                    </form>
                <?php endif; ?>
                <p class="small text-body-secondary mt-3 mb-0">Publicada el <?= e(fecha($o['fechaPublicacion'])) ?></p>
            </div>
        </div>
    </div>
</div>
