<?php
/**
 * @var \App\Core\Paginacao $paginacao
 * @var string $rota
 * @var array $params
 */
$params = $params ?? [];
if ($paginacao->totalPaginas <= 1) {
    return;
}
$inicio = max(1, $paginacao->pagina - 2);
$fim = min($paginacao->totalPaginas, $paginacao->pagina + 2);
?>
<nav aria-label="Paginação" class="d-print-none">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item<?= $paginacao->pagina <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(url($rota, ['pagina' => $paginacao->pagina - 1] + $params)) ?>">Anterior</a>
        </li>
        <?php for ($p = $inicio; $p <= $fim; $p++): ?>
            <li class="page-item<?= $p === $paginacao->pagina ? ' active' : '' ?>">
                <a class="page-link" href="<?= e(url($rota, ['pagina' => $p] + $params)) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?= $paginacao->pagina >= $paginacao->totalPaginas ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(url($rota, ['pagina' => $paginacao->pagina + 1] + $params)) ?>">Próxima</a>
        </li>
    </ul>
    <p class="text-center small text-body-secondary"><?= $paginacao->total ?> registro(s) · página <?= $paginacao->pagina ?> de <?= $paginacao->totalPaginas ?></p>
</nav>
