<?php
/** Oportunidad en formato de evento: bloque de fecha, causa, título, fundación y datos clave. @var array $o */
$paraTi = !empty($mostrarAfinidad) && (int) ($o['afinidad'] ?? 0) >= 3;
$cupos = (int) $o['cuposDisponibles'];
?>
<article class="oport">
    <?= bloque_fecha($o['fechaInicio']) ?>
    <div class="oport-cuerpo">
        <p class="oport-cat">
            <span><?= e($o['categoria']) ?></span>
            <?php if ($paraTi): ?><span class="para-ti">Afín a tu perfil</span><?php endif; ?>
        </p>
        <h3><a class="stretched-link" href="<?= url('/oportunidades/' . $o['idOportunidad']) ?>"><?= e($o['titulo']) ?></a></h3>
        <p class="oport-org"><?= e($o['razonSocial'] ?? '') ?></p>
        <p class="oport-meta">
            <span><?= e($o['ciudad']) ?></span>
            <span><?= e(etiqueta('modalidad', $o['modalidad'])) ?></span>
            <span><?= e(hora($o['horaInicio'])) ?></span>
            <span class="cupos"><?= $cupos ?> cupo<?= $cupos === 1 ? '' : 's' ?></span>
        </p>
    </div>
</article>
