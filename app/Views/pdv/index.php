<?php
/**
 * @var string $busca
 * @var \App\Models\Produto[] $resultados
 * @var \App\Models\Carrinho|null $carrinhoAtual
 * @var \App\Models\Carrinho[] $abertos
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-upc-scan"></i> PDV</h1>
    <div class="d-flex gap-2">
        <a href="<?= e(url('carrinho')) ?>" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Carrinhos abertos (<?= count($abertos) ?>)</a>
        <a href="<?= e(url('carrinho/novo')) ?>" class="btn btn-success"><i class="bi bi-cart-plus"></i> Novo carrinho</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Buscar produto</h2>
                <?= View::partial('partials/busca_produtos', [
                    'busca'         => $busca,
                    'resultados'    => $resultados,
                    'rotaBusca'     => 'pdv',
                    'rotaAdicionar' => 'pdv/adicionar',
                ]) ?>
                <?php if ($busca === ''): ?>
                    <p class="text-body-secondary mb-0">Digite parte do nome ou o código do produto. Ao adicionar, ele vai para o carrinho atual
                        (um carrinho novo é aberto automaticamente se não houver).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-cart3"></i> Carrinho atual</div>
            <?php if ($carrinhoAtual === null): ?>
                <div class="card-body text-body-secondary">Nenhum carrinho em andamento.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($carrinhoAtual->itens as $item): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= (int) $item->quantidade ?>x <?= e($item->produtoNome) ?></span>
                            <span><?= moeda($item->subtotal()) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($carrinhoAtual->vazio()): ?>
                        <li class="list-group-item text-body-secondary">Carrinho vazio.</li>
                    <?php endif; ?>
                </ul>
                <div class="card-body">
                    <div class="d-flex justify-content-between fs-5 mb-2">
                        <span>Total</span><strong><?= moeda($carrinhoAtual->total()) ?></strong>
                    </div>
                    <small class="text-body-secondary d-block mb-2">Carrinho #<?= (int) $carrinhoAtual->id ?> · <?= e($carrinhoAtual->clienteNome ?? 'Consumidor final') ?></small>
                    <a href="<?= e(url('carrinho/ver', ['id' => $carrinhoAtual->id])) ?>" class="btn btn-primary w-100"><i class="bi bi-bag-check"></i> Ir para o carrinho / finalizar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
