<?php
/**
 * @var \App\Models\Carrinho $carrinho
 * @var string $busca
 * @var \App\Models\Produto[] $resultados
 * @var \App\Models\Cliente[] $clientes
 * @var \App\Patterns\Strategy\FormaPagamentoStrategy[] $formas
 */
use App\Core\View;

$aberto = $carrinho->estaAberto();
$total = $carrinho->total();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-cart3"></i> Carrinho #<?= (int) $carrinho->id ?> <?= badge_status($carrinho->status) ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(url('carrinho')) ?>" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Carrinhos abertos</a>
        <?php if ($aberto): ?>
            <?php if (!$carrinho->vazio()): ?>
                <?= View::partial('partials/confirmar_post', ['acao' => 'carrinho/esvaziar', 'id' => $carrinho->id, 'rotulo' => 'Esvaziar', 'icone' => 'bi-trash', 'classe' => 'btn btn-outline-warning', 'mensagem' => 'Remover todos os itens do carrinho?']) ?>
            <?php endif; ?>
            <?= View::partial('partials/confirmar_post', ['acao' => 'carrinho/cancelar', 'id' => $carrinho->id, 'rotulo' => 'Cancelar carrinho', 'icone' => 'bi-x-lg', 'classe' => 'btn btn-outline-danger', 'mensagem' => 'Cancelar este carrinho?']) ?>
        <?php elseif ($carrinho->status === 'cancelado'): ?>
            <?= View::partial('partials/confirmar_post', ['acao' => 'carrinho/excluir', 'id' => $carrinho->id, 'rotulo' => 'Excluir', 'icone' => 'bi-trash', 'classe' => 'btn btn-outline-danger', 'mensagem' => 'Excluir definitivamente este carrinho cancelado?']) ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!$aberto): ?>
    <div class="alert alert-info">Este carrinho está <strong><?= e($carrinho->status) ?></strong> e não pode mais ser alterado.
        <?php if ($carrinho->status === 'finalizado'): ?>Consulte a venda em <a href="<?= e(url('relatorios')) ?>">Relatórios</a>.<?php endif; ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <?php if ($aberto): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h5">Adicionar produto</h2>
                    <form method="post" action="<?= e(url('carrinho/adicionar')) ?>" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $carrinho->id ?>">
                        <div class="col-7 col-md-8">
                            <input type="text" name="codigo" class="form-control" placeholder="Código exato (leitor de código de barras)" aria-label="Código do produto">
                        </div>
                        <div class="col-2">
                            <input type="number" name="quantidade" value="1" min="1" class="form-control" aria-label="Quantidade">
                        </div>
                        <div class="col-3 col-md-2 d-grid">
                            <button class="btn btn-success"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                    <?= View::partial('partials/busca_produtos', [
                        'busca'         => $busca,
                        'resultados'    => $resultados,
                        'rotaBusca'     => 'carrinho/ver',
                        'paramsBusca'   => ['id' => $carrinho->id],
                        'rotaAdicionar' => 'carrinho/adicionar',
                        'carrinhoId'    => $carrinho->id,
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header">Itens do carrinho</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Produto</th><th class="text-end">Preço</th><th>Qtd.</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($carrinho->vazio()): ?>
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">Nenhum item. Busque um produto acima.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($carrinho->itens as $item): ?>
                        <tr class="<?= $item->semEstoque() ? 'table-danger' : '' ?>">
                            <td>
                                <?= e($item->produtoNome) ?><br>
                                <small class="text-body-secondary">Cód. <?= e($item->produtoCod) ?> · estoque <?= (int) $item->estoqueDisponivel ?></small>
                                <?php if ($aberto && $item->semEstoque()): ?><br><small class="text-danger">Quantidade maior que o estoque atual!</small><?php endif; ?>
                            </td>
                            <td class="text-end"><?= moeda($item->precoUnitario) ?></td>
                            <td>
                                <?php if ($aberto): ?>
                                    <form method="post" action="<?= e(url('carrinho/atualizar-item')) ?>" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $carrinho->id ?>">
                                        <input type="hidden" name="item_id" value="<?= (int) $item->id ?>">
                                        <input type="number" name="quantidade" value="<?= (int) $item->quantidade ?>" min="0" max="<?= (int) $item->estoqueDisponivel ?>" class="form-control form-control-sm qtd-input" aria-label="Quantidade">
                                        <button class="btn btn-sm btn-outline-primary" title="Atualizar quantidade"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                <?php else: ?>
                                    <?= (int) $item->quantidade ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= moeda($item->subtotal()) ?></td>
                            <td class="text-end">
                                <?php if ($aberto): ?>
                                    <form method="post" action="<?= e(url('carrinho/remover-item')) ?>" data-confirmar="Remover este item?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $carrinho->id ?>">
                                        <input type="hidden" name="item_id" value="<?= (int) $item->id ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Remover"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="fs-5"><th colspan="3" class="text-end">Total</th><th class="text-end"><?= moeda($total) ?></th><th></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Cliente</div>
            <div class="card-body">
                <?php if ($aberto): ?>
                    <form method="post" action="<?= e(url('carrinho/cliente')) ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $carrinho->id ?>">
                        <select name="cliente_id" class="form-select" aria-label="Cliente">
                            <option value="">Consumidor final</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= (int) $c->id ?>" <?= selected($c->id, $carrinho->clienteId) ?>><?= e($c->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary">Salvar</button>
                    </form>
                <?php else: ?>
                    <?= e($carrinho->clienteNome ?? 'Consumidor final') ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($aberto): ?>
            <form method="post" action="<?= e(url('carrinho/finalizar')) ?>" id="form-finalizar" class="card shadow-sm border-success"
                  data-subtotal="<?= e(number_format($total, 2, '.', '')) ?>">
                <div class="card-header text-bg-success">Finalizar venda</div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $carrinho->id ?>">

                    <label for="forma_pagamento" class="form-label">Forma de pagamento</label>
                    <select name="forma_pagamento" id="forma_pagamento" class="form-select mb-3" required>
                        <?php foreach ($formas as $forma): ?>
                            <?php
                            $percentual = $forma->percentualAjuste();
                            $sufixo = $percentual < 0 ? ' (' . abs($percentual) . '% de desconto)' : ($percentual > 0 ? " (+{$percentual}%)" : '');
                            ?>
                            <option value="<?= e($forma->codigo()) ?>" data-percentual="<?= e($percentual) ?>" data-troco="<?= $forma->aceitaTroco() ? '1' : '0' ?>">
                                <?= e($forma->nome() . $sufixo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="grupo-valor-recebido" class="mb-3">
                        <label for="valor_recebido" class="form-label">Valor recebido (R$)</label>
                        <input type="number" step="0.01" min="0" name="valor_recebido" id="valor_recebido" class="form-control" placeholder="0,00">
                    </div>

                    <label for="observacao" class="form-label">Observação</label>
                    <textarea name="observacao" id="observacao" rows="2" maxlength="500" class="form-control mb-3"></textarea>

                    <div id="previa-pagamento" class="alert alert-light border small mb-3">Total <?= moeda($total) ?></div>

                    <button class="btn btn-success w-100 btn-lg" <?= $carrinho->vazio() ? 'disabled' : '' ?>>
                        <i class="bi bi-check2-circle"></i> Finalizar venda
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
