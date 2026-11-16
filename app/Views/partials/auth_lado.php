<?php
/** Panel derecho de las pantallas de acceso: un testimonio real y las horas registradas. */
$t = \App\Models\Evaluacion::testimonio();
$horas = (float) \App\Core\Database::valor('SELECT COALESCE(SUM(horasCumplidas), 0) FROM participacion WHERE asistio = 1');
?>
<aside class="auth-lado">
    <a class="marca" href="<?= url('/') ?>" style="color:#fff"><img src="<?= asset('img/logo.svg') ?>" alt=""><span>Volunta<em style="color:#f0c56a">Red</em></span></a>
    <?php if ($t): ?>
        <figure class="my-5">
            <blockquote class="cita mb-0">«<?= e($t['comentario']) ?>»</blockquote>
            <figcaption class="cita-autor"><?= e($t['nombre'] . ' ' . mb_substr((string) $t['apellido'], 0, 1) . '.') ?> · <?= e($t['razonSocial']) ?></figcaption>
        </figure>
    <?php else: ?>
        <p class="cita my-5">Tu tiempo, donde más se necesita.</p>
    <?php endif; ?>
    <div>
        <div class="dato"><?= number_format($horas, 0, ',', '.') ?> h</div>
        <div class="dato-texto">de voluntariado registradas por fundaciones verificadas de Córdoba.</div>
    </div>
</aside>
