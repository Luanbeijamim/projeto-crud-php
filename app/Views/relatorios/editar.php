<?php
/**
 * @var \App\Models\Venda $venda
 * @var \App\Patterns\Strategy\FormaPagamentoStrategy[] $formas
 */
?>
<h1 class="h3 mb-3"><i class="bi bi-pencil"></i> Editar venda #<?= (int) $venda->id ?></h1>

<form method="post" action="<?= e(url('relatorios/atualizar')) ?>" id="form-finalizar" class="card shadow-sm" style="max-width: 640px"
      data-subtotal="<?= e(number_format($venda->subtotal, 2, '.', '')) ?>">
    <div class="card-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $venda->id ?>">

        <p class="mb-3">Subtotal dos itens: <strong><?= moeda($venda->subtotal) ?></strong>.
            Ao trocar a forma de pagamento, desconto/acréscimo e total são recalculados pela estratégia escolhida.</p>

        <label for="forma_pagamento" class="form-label">Forma de pagamento</label>
        <select name="forma_pagamento" id="forma_pagamento" class="form-select mb-3">
            <?php foreach ($formas as $forma): ?>
                <option value="<?= e($forma->codigo()) ?>" data-percentual="<?= e($forma->percentualAjuste()) ?>"
                        data-troco="<?= $forma->aceitaTroco() ? '1' : '0' ?>" <?= selected($forma->codigo(), $venda->formaPagamento) ?>>
                    <?= e($forma->nome()) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="grupo-valor-recebido" class="mb-3">
            <label for="valor_recebido" class="form-label">Valor recebido (R$)</label>
            <input type="number" step="0.01" min="0" name="valor_recebido" id="valor_recebido" class="form-control"
                   value="<?= e(number_format($venda->valorRecebido, 2, '.', '')) ?>">
        </div>

        <label for="observacao" class="form-label">Observação</label>
        <textarea name="observacao" id="observacao" rows="3" maxlength="500" class="form-control mb-3"><?= e($venda->observacao) ?></textarea>

        <div id="previa-pagamento" class="alert alert-light border small mb-0"></div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= e(url('relatorios/venda', ['id' => $venda->id])) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-success"><i class="bi bi-check-lg"></i> Salvar alterações</button>
    </div>
</form>
