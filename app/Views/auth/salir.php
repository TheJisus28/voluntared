<div class="container">
    <div class="auth-card card card-vr p-4 p-md-5 text-center">
        <i class="bi bi-box-arrow-right text-primary" style="font-size: 3rem;" aria-hidden="true"></i>
        <h1 class="h3 fw-bold mt-2">¿Deseas cerrar sesión?</h1>
        <p class="text-body-secondary">Tu sesión se cerrará de forma segura en este dispositivo.</p>
        <form method="post" action="<?= url('/salir') ?>" class="d-flex gap-2 justify-content-center">
            <?= csrf_field() ?>
            <a class="btn btn-light" href="<?= url('/panel') ?>">Cancelar</a>
            <button class="btn btn-danger" type="submit">Cerrar sesión</button>
        </form>
    </div>
</div>
