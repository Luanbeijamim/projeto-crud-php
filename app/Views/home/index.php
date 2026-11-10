<?php
/**
 * @var array $hoje
 * @var array $mes
 * @var \App\Models\Produto[] $estoqueBaixo
 * @var \App\Models\Produto[] $vencendo
 * @var int $abertos
 * @var int $limiteEstoque
 * @var int $diasValidade
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-speedometer2"></i> Painel</h1>
    <a href="<?= e(url('pdv')) ?>" class="btn btn-success btn-lg"><i class="bi bi-upc-scan"></i> Abrir PDV</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100 border-start border-4 border-primary"><div class="card-body">
            <div class="text-body-secondary small">Vendas hoje</div>
            <div class="valor"><?= (int) $hoje['concluidas'] ?></div>
            <small class="text-body-secondary"><?= moeda($hoje['faturamento']) ?></small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100 border-start border-4 border-success"><div class="card-body">
            <div class="text-body-secondary small">Faturamento do mês</div>
            <div class="valor text-success"><?= moeda($mes['faturamento']) ?></div>
            <small class="text-body-secondary"><?= (int) $mes['concluidas'] ?> venda(s) · ticket <?= moeda($mes['ticket_medio']) ?></small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100 border-start border-4 border-warning"><div class="card-body">
            <div class="text-body-secondary small">Estoque baixo (≤ <?= $limiteEstoque ?>)</div>
            <div class="valor"><?= count($estoqueBaixo) ?></div>
            <small class="text-body-secondary">produto(s) ativos</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100 border-start border-4 border-info"><div class="card-body">
            <div class="text-body-secondary small">Carrinhos abertos</div>
            <div class="valor"><?= (int) $abertos ?></div>
            <a href="<?= e(url('carrinho')) ?>" class="small">ver carrinhos</a>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-exclamation-triangle text-warning"></i> Produtos com estoque baixo</div>
            <ul class="list-group list-group-flush">
                <?php if (!$estoqueBaixo): ?>
                    <li class="list-group-item text-body-secondary">Nenhum produto com estoque baixo. 👍</li>
                <?php endif; ?>
                <?php foreach ($estoqueBaixo as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= e(url('produtos/editar', ['id' => $p->id])) ?>"><?= e($p->nome) ?></a>
                        <span class="badge <?= $p->quantidade === 0 ? 'text-bg-danger' : 'text-bg-warning' ?>"><?= (int) $p->quantidade ?> un.</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-calendar-x text-danger"></i> Validade vencida ou vencendo em <?= $diasValidade ?> dias</div>
            <ul class="list-group list-group-flush">
                <?php if (!$vencendo): ?>
                    <li class="list-group-item text-body-secondary">Nenhum produto próximo do vencimento.</li>
                <?php endif; ?>
                <?php foreach ($vencendo as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= e(url('produtos/editar', ['id' => $p->id])) ?>"><?= e($p->nome) ?></a>
                        <span class="badge <?= $p->vencido() ? 'text-bg-danger' : 'text-bg-light border' ?>">
                            <?= $p->vencido() ? 'Venceu em ' : 'Vence em ' ?><?= data_br($p->validade) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
