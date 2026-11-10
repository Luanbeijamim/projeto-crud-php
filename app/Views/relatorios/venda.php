<?php
/**
 * @var \App\Models\Venda $venda
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 d-print-none">
    <h1 class="h3 mb-0"><i class="bi bi-receipt"></i> Venda #<?= (int) $venda->id ?> <?= badge_status($venda->status) ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('relatorios')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Relatórios</a>
        <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <?php if ($venda->estaConcluida()): ?>
            <a href="<?= e(url('relatorios/editar', ['id' => $venda->id])) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
        <?php else: ?>
            <?= View::partial('partials/confirmar_post', ['acao' => 'relatorios/excluir', 'id' => $venda->id, 'rotulo' => 'Excluir', 'icone' => 'bi-trash', 'classe' => 'btn btn-outline-danger', 'mensagem' => 'Excluir definitivamente esta venda cancelada?']) ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="cupom card shadow-sm p-3">
            <div class="text-center">
                <strong><?= e(\App\Core\Config::get('nome')) ?></strong><br>
                <small>CUPOM NÃO FISCAL</small>
            </div>
            <hr>
            <div class="small">
                Venda: #<?= (int) $venda->id ?><br>
                Data: <?= data_br($venda->criadoEm, true) ?><br>
                Cliente: <?= e($venda->clienteNome ?? 'Consumidor final') ?>
                <?php if ($venda->clienteCpf): ?><br>CPF: <?= e(cpf_formatado($venda->clienteCpf)) ?><?php endif; ?>
            </div>
            <hr>
            <table class="table table-sm table-borderless small mb-0 bg-transparent">
                <thead><tr><th>Item</th><th class="text-end">Qtd x Preço</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                <?php foreach ($venda->itens as $item): ?>
                    <tr>
                        <td><?= e($item->produtoCod) ?> <?= e($item->produtoNome) ?></td>
                        <td class="text-end text-nowrap"><?= (int) $item->quantidade ?> x <?= moeda($item->precoUnitario) ?></td>
                        <td class="text-end text-nowrap"><?= moeda($item->subtotal) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <div class="small">
                <div class="d-flex justify-content-between"><span>Subtotal (<?= $venda->quantidadeItens() ?> itens)</span><span><?= moeda($venda->subtotal) ?></span></div>
                <?php if ($venda->desconto > 0): ?>
                    <div class="d-flex justify-content-between"><span>Desconto</span><span>- <?= moeda($venda->desconto) ?></span></div>
                <?php endif; ?>
                <?php if ($venda->acrescimo > 0): ?>
                    <div class="d-flex justify-content-between"><span>Acréscimo</span><span>+ <?= moeda($venda->acrescimo) ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between fs-5 fw-bold"><span>TOTAL</span><span><?= moeda($venda->total) ?></span></div>
                <div class="d-flex justify-content-between"><span>Pagamento</span><span><?= e(rotulo_pagamento($venda->formaPagamento)) ?></span></div>
                <div class="d-flex justify-content-between"><span>Valor recebido</span><span><?= moeda($venda->valorRecebido) ?></span></div>
                <div class="d-flex justify-content-between"><span>Troco</span><span><?= moeda($venda->troco) ?></span></div>
            </div>
            <?php if (!$venda->estaConcluida()): ?>
                <hr><div class="text-center fw-bold">*** VENDA CANCELADA ***</div>
            <?php endif; ?>
            <hr>
            <div class="text-center small">Obrigado pela preferência!</div>
        </div>
    </div>

    <div class="col-lg-6 d-print-none">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Observações</div>
            <div class="card-body"><?= $venda->observacao ? nl2br(e($venda->observacao)) : '<span class="text-body-secondary">Nenhuma.</span>' ?></div>
        </div>

        <?php if ($venda->estaConcluida()): ?>
            <form method="post" action="<?= e(url('relatorios/cancelar')) ?>" class="card shadow-sm border-warning"
                  data-confirmar="Cancelar esta venda? Os itens voltarão ao estoque.">
                <div class="card-header text-bg-warning">Cancelar venda</div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $venda->id ?>">
                    <label for="motivo" class="form-label">Motivo (opcional)</label>
                    <input type="text" id="motivo" name="motivo" maxlength="200" class="form-control mb-3">
                    <button class="btn btn-warning"><i class="bi bi-x-circle"></i> Cancelar venda e estornar estoque</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
