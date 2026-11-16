<?php use App\Core\View; ?>
<div class="container">
    <?php View::parcial('encabezado', ['titulo' => 'Usuarios', 'subtitulo' => "$total cuenta(s) encontradas."]); ?>
    <form class="card card-vr p-3 mb-3" method="get" action="<?= url('/admin/usuarios') ?>" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-md-5"><label for="q" class="form-label small fw-semibold">Buscar</label><input class="form-control" id="q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, correo o fundación"></div>
            <div class="col-md-3"><label for="rol" class="form-label small fw-semibold">Rol</label>
                <select class="form-select" id="rol" name="rol"><option value="">Todos</option>
                    <?php foreach (['voluntario' => 'Voluntario', 'fundacion' => 'Fundación', 'administrador' => 'Administrador'] as $k => $t): ?><option value="<?= $k ?>"<?= $filtros['rol'] === $k ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2"><label for="estado" class="form-label small fw-semibold">Estado</label>
                <select class="form-select" id="estado" name="estado"><option value="">Todos</option>
                    <option value="activo"<?= $filtros['estado'] === 'activo' ? ' selected' : '' ?>>Activos</option>
                    <option value="suspendido"<?= $filtros['estado'] === 'suspendido' ? ' selected' : '' ?>>Suspendidos</option>
                </select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar</button></div>
        </div>
    </form>
    <div class="card card-vr">
        <?php if (!$usuarios): ?>
            <div class="vacio"><i class="bi bi-person-x" aria-hidden="true"></i>No se encontraron usuarios.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vr table-hover mb-0">
                    <caption class="visually-hidden">Listado de usuarios</caption>
                    <thead><tr><th scope="col">Usuario</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Registro</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $usr): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?= avatar($usr['foto'], $usr['nombre'], $usr['apellido'], 'sm') ?>
                                    <div><span class="fw-semibold"><?= e($usr['razonSocial'] ?: $usr['nombre'] . ' ' . $usr['apellido']) ?></span>
                                        <div class="small text-body-secondary"><?= e($usr['email']) ?><?= $usr['razonSocial'] ? ' · ' . e($usr['nombre'] . ' ' . $usr['apellido']) : '' ?></div></div>
                                </div>
                            </td>
                            <td><?= e(etiqueta('rol', $usr['rol'])) ?><?php if ($usr['estadoVerificacion']): ?><div><?= badge_estado('verificacion', $usr['estadoVerificacion']) ?></div><?php endif; ?></td>
                            <td>
                                <?= $usr['activo'] ? '<span class="estado estado-activo">Activo</span>' : '<span class="estado estado-suspendido">Suspendido</span>' ?>
                                <?php if (!$usr['emailVerificado']): ?><div class="small text-body-secondary">Correo sin confirmar</div><?php endif; ?>
                            </td>
                            <td class="small"><?= e(fecha_corta($usr['fechaCreacion'])) ?></td>
                            <td class="text-end">
                                <?php if ($usr['rol'] !== 'administrador'): ?>
                                    <?php if ($usr['activo']): ?>
                                        <form method="post" action="<?= url('/admin/usuarios/' . $usr['idUsuario'] . '/suspender') ?>" data-confirmar="¿Suspender la cuenta de <?= e($usr['email']) ?>? No podrá iniciar sesión." data-confirmar-boton="Suspender" data-motivo="1">
                                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i>Suspender</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('/admin/usuarios/' . $usr['idUsuario'] . '/reactivar') ?>" data-confirmar="¿Reactivar la cuenta de <?= e($usr['email']) ?>?" data-confirmar-boton="Reactivar" data-confirmar-estilo="btn-primary">
                                            <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Reactivar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php View::parcial('paginacion', ['pagina' => $pagina, 'paginas' => $paginas]); ?>
</div>
