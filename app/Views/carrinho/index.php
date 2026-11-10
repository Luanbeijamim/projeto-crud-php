<?php
/**
 * @var \App\Models\Carrinho[] $abertos
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-cart3"></i> Carrinhos abertos</h1>
    <div class="d-flex gap-2">
        <a href="<?= e(url('pdv')) ?>" class="btn btn-outline-secondary"><i class="bi bi-upc-scan"></i> PDV</a>
        <a href="<?= e(url('carrinho/novo')) ?>" class="btn btn-success"><i class="bi bi-cart-plus"></i> Novo carrinho</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr><th>#</th><th>Cliente</th><th>Aberto em</th><th>Última alteração</th><th class="text-end">Itens</th><th class="text-end">Total</th><th class="text-end">Ações</th></tr>
            </thead>
            <tbody>
            <?php if (!$abertos): ?>
                <tr><td colspan="7" class="text-center text-body-secondary py-4">Nenhum carrinho aberto.</td></tr>
            <?php endif; ?>
            <?php foreach ($abertos as $c): ?>
                <tr>
                    <td><a href="<?= e(url('carrinho/ver', ['id' => $c->id])) ?>">#<?= (int) $c->id ?></a></td>
                    <td><?= e($c->clienteNome ?? 'Consumidor final') ?></td>
                    <td><?= data_br($c->criadoEm, true) ?></td>
                    <td><?= data_br($c->atualizadoEm, true) ?></td>
                    <td class="text-end"><?= (int) $c->quantidadeItens ?></td>
                    <td class="text-end"><?= moeda($c->valorTotal) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= e(url('carrinho/ver', ['id' => $c->id])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-play-fill"></i> Retomar</a>
                        <?= View::partial('partials/confirmar_post', ['acao' => 'carrinho/cancelar', 'id' => $c->id, 'rotulo' => '', 'icone' => 'bi-x-lg', 'mensagem' => 'Cancelar o carrinho #' . $c->id . '?']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
