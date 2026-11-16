<?php
/**
 * Campo de formulario con etiqueta, ayuda y mensaje de error accesibles.
 * Parámetros: nombre, etiqueta, tipo (text, email, password, date, time, number, textarea, select),
 * valor, requerido, ayuda, opciones (para select), atributos (string extra), col (clase de columna).
 */
$tipo = $tipo ?? 'text';
$id = $id ?? $nombre;
$error = error_campo($nombre);
$valor = $valor ?? old($nombre, '');
$requerido = $requerido ?? false;
$describe = trim(($ayuda ?? null ? "ayuda-$id " : '') . ($error ? "error-$id" : ''));
$attrs = ($requerido ? ' required aria-required="true"' : '') . ($describe ? ' aria-describedby="' . $describe . '"' : '')
    . ($error ? ' aria-invalid="true"' : '') . ' ' . ($atributos ?? '');
?>
<div class="<?= $col ?? 'mb-3' ?>">
    <label for="<?= e($id) ?>" class="form-label fw-semibold"><?= e($etiqueta) ?><?= $requerido ? ' <span class="text-danger" aria-hidden="true">*</span>' : '' ?></label>
    <?php if ($tipo === 'textarea'): ?>
        <textarea id="<?= e($id) ?>" name="<?= e($nombre) ?>" class="form-control<?= $error ? ' is-invalid' : '' ?>" rows="<?= $filas ?? 4 ?>"<?= $attrs ?>><?= e($valor) ?></textarea>
    <?php elseif ($tipo === 'select'): ?>
        <select id="<?= e($id) ?>" name="<?= e($nombre) ?>" class="form-select<?= $error ? ' is-invalid' : '' ?>"<?= $attrs ?>>
            <option value="">Selecciona…</option>
            <?php foreach ($opciones as $v => $t): ?>
                <option value="<?= e($v) ?>"<?= (string) $valor === (string) $v ? ' selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($tipo === 'password'): ?>
        <div class="input-group has-validation">
            <input id="<?= e($id) ?>" name="<?= e($nombre) ?>" type="password" class="form-control<?= $error ? ' is-invalid' : '' ?>"<?= $attrs ?>>
            <button class="btn btn-outline-secondary" type="button" data-ver-contrasena="<?= e($id) ?>" aria-pressed="false" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button>
            <?php if ($error): ?><div class="invalid-feedback" id="error-<?= e($id) ?>"><?= e($error) ?></div><?php endif; ?>
        </div>
    <?php else: ?>
        <input id="<?= e($id) ?>" name="<?= e($nombre) ?>" type="<?= e($tipo) ?>" value="<?= e($valor) ?>" class="form-control<?= $error ? ' is-invalid' : '' ?>"<?= $attrs ?>>
    <?php endif; ?>
    <?php if (!empty($ayuda)): ?><div class="form-text" id="ayuda-<?= e($id) ?>"><?= e($ayuda) ?></div><?php endif; ?>
    <?php if ($error && $tipo !== 'password'): ?><div class="invalid-feedback d-block" id="error-<?= e($id) ?>"><?= e($error) ?></div><?php endif; ?>
</div>
