<div class="auth-dividida">
    <div class="auth-form">
        <div>
            <h1>Crea tu cuenta</h1>
            <p class="intro">¿Cómo quieres participar en VoluntaRed?</p>
            <div class="d-grid gap-3">
                <a class="opcion-registro" href="<?= url('/registro/voluntario') ?>">
                    <h2>Quiero ser voluntario</h2>
                    <p>Encuentra actividades según tus causas, habilidades y tiempo libre, y certifica tus horas.</p>
                    <span class="ir">Registrarme como voluntario →</span>
                </a>
                <a class="opcion-registro" href="<?= url('/registro/fundacion') ?>">
                    <h2>Represento una fundación</h2>
                    <p>Publica convocatorias, recibe postulaciones y lleva el registro de tus voluntarios.</p>
                    <span class="ir">Registrar mi fundación →</span>
                </a>
            </div>
            <p class="mt-4 mb-0 text-body-secondary">¿Ya tienes cuenta? <a class="fw-semibold" href="<?= url('/login') ?>">Inicia sesión</a></p>
        </div>
    </div>
    <?php App\Core\View::parcial('auth_lado'); ?>
</div>
