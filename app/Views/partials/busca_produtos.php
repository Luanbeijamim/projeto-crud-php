<?php
/**
 * @var string $busca
 * @var \App\Models\Produto[] $resultados
 * @var string $rotaBusca
 * @var array $paramsBusca
 * @var string $rotaAdicionar
 * @var int|null $carrinhoId
 */
$paramsBusca = $paramsBusca ?? [];
$carrinhoId = $carrinhoId ?? null;
?>
<form method="get" action="<?= e(url()) ?>" class="input-group mb-3">
    <input type="hidden" name="rota" value="<?= e($rotaBusca) ?>">
    <?php foreach ($paramsBusca as $nome => $valor): ?>
        <input type="hidden" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
    <?php endforeach; ?>
    <input type="search" name="busca" value="<?= e($busca) ?>" class="form-control" placeholder="Nome ou código do produto" autofocus>
    <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
</form>

<?php if ($busca !== ''): ?>
    <?php if (!$resultados): ?>
        <p class="text-body-secondary">Nenhum produto ativo encontrado para "<?= e($busca) ?>".</p>
    <?php else: ?>
        <div class="list-group mb-3">
            <?php foreach ($resultados as $p): ?>
                <div class="list-group-item d-flex flex-wrap align-items-center gap-3">
                    <img src="<?= e(imagem_produto($p->imagem)) ?>" alt="" class="thumb-produto">
                    <div class="flex-grow-1">
                        <div class="fw-semibold"><?= e($p->nome) ?></div>
                        <small class="text-body-secondary">Cód. <?= e($p->cod) ?> · <?= e($p->categoria) ?> ·
                            estoque <span class="<?= $p->estoqueBaixo() ? 'text-danger fw-semibold' : '' ?>"><?= (int) $p->quantidade ?></span></small>
                    </div>
                    <div class="fw-semibold"><?= moeda($p->precoUnitario) ?></div>
                    <?php if ($p->quantidade > 0): ?>
                        <form method="post" action="<?= e(url($rotaAdicionar)) ?>" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <?php if ($carrinhoId): ?><input type="hidden" name="id" value="<?= (int) $carrinhoId ?>"><?php endif; ?>
                            <input type="hidden" name="produto_id" value="<?= (int) $p->id ?>">
                            <input type="hidden" name="busca" value="<?= e($busca) ?>">
                            <input type="number" name="quantidade" value="1" min="1" max="<?= (int) $p->quantidade ?>" class="form-control form-control-sm qtd-input" aria-label="Quantidade">
                            <button class="btn btn-sm btn-success text-nowrap"><i class="bi bi-cart-plus"></i> Adicionar</button>
                        </form>
                    <?php else: ?>
                        <span class="badge text-bg-danger">Sem estoque</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
