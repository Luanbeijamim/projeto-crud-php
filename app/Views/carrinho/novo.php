<?php
/**
 * @var \App\Models\Cliente[] $clientes
 */
?>
<h1 class="h3 mb-3"><i class="bi bi-cart-plus"></i> Novo carrinho</h1>

<form method="post" action="<?= e(url('carrinho/criar')) ?>" class="card shadow-sm" style="max-width: 560px">
    <div class="card-body">
        <?= csrf_field() ?>
        <label for="cliente_id" class="form-label">Cliente (opcional)</label>
        <select name="cliente_id" id="cliente_id" class="form-select">
            <option value="">Consumidor final (sem cadastro)</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= (int) $c->id ?>"><?= e($c->nome) ?> — <?= e(cpf_formatado($c->cpf)) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Cliente não cadastrado? <a href="<?= e(url('clientes/novo')) ?>">Cadastre aqui</a>.</div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= e(url('pdv')) ?>" class="btn btn-outline-secondary">Voltar</a>
        <button class="btn btn-success"><i class="bi bi-check-lg"></i> Abrir carrinho</button>
    </div>
</form>
