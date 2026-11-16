<?php
/** @var int $pagina @var int $paginas @var array $query */
if ($paginas <= 1) {
    return;
}
$ruta = strtok($_SERVER['REQUEST_URI'], '?');
$ruta = substr($ruta, strlen(base_path_url())) ?: '/';
$enlace = static fn (int $p) => url($ruta, array_merge($_GET, ['pagina' => $p]));
$inicio = max(1, $pagina - 2);
$fin = min($paginas, $pagina + 2);
?>
<nav aria-label="Paginación" class="mt-4">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= $pagina > 1 ? e($enlace($pagina - 1)) : '#' ?>" aria-label="Página anterior">&laquo;</a>
        </li>
        <?php for ($p = $inicio; $p <= $fin; $p++): ?>
            <li class="page-item<?= $p === $pagina ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($enlace($p)) ?>"<?= $p === $pagina ? ' aria-current="page"' : '' ?>><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?= $pagina >= $paginas ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= $pagina < $paginas ? e($enlace($pagina + 1)) : '#' ?>" aria-label="Página siguiente">&raquo;</a>
        </li>
    </ul>
</nav>
