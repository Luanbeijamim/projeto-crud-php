<?php
/**
 * @var \App\Models\Cliente $cliente
 * @var \App\Models\Venda[] $historico
 * @var float $totalComprado
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-person"></i> <?= e($cliente->nome) ?>
        <?= $cliente->ativo ? '' : '<span class="badge text-bg-secondary fs-6 align-middle">Inativo</span>' ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= e(url('clientes')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
        <a href="<?= e(url('clientes/editar', ['id' => $cliente->id])) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
        <?= View::partial('partials/confirmar_post', ['acao' => 'clientes/excluir', 'id' => $cliente->id, 'rotulo' => 'Excluir', 'icone' => 'bi-trash', 'classe' => 'btn btn-outline-danger', 'mensagem' => 'Excluir este cliente? Se ele tiver vendas, será apenas inativado.']) ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">Dados cadastrais</div>
            <dl class="card-body mb-0">
                <dt>CPF</dt><dd><?= e(cpf_formatado($cliente->cpf)) ?></dd>
                <dt>E-mail</dt><dd><?= e($cliente->email ?? '—') ?></dd>
                <dt>Telefone</dt><dd><?= e($cliente->telefone ?? '—') ?></dd>
                <dt>Endereço</dt><dd><?= e($cliente->endereco ?? '—') ?></dd>
                <dt>Nascimento</dt><dd><?= data_br($cliente->dataNascimento) ?></dd>
                <dt>Cadastrado em</dt><dd><?= data_br($cliente->criadoEm, true) ?></dd>
                <dt>Atualizado em</dt><dd class="mb-0"><?= data_br($cliente->atualizadoEm, true) ?></dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Histórico de compras</span>
                <span><?= count($historico) ?> venda(s) · total <strong><?= moeda($totalComprado) ?></strong></span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>#</th><th>Data</th><th>Pagamento</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php if (!$historico): ?>
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">Nenhuma compra registrada.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($historico as $v): ?>
                        <tr>
                            <td><a href="<?= e(url('relatorios/venda', ['id' => $v->id])) ?>">#<?= (int) $v->id ?></a></td>
                            <td><?= data_br($v->criadoEm, true) ?></td>
                            <td><?= e(rotulo_pagamento($v->formaPagamento)) ?></td>
                            <td><?= badge_status($v->status) ?></td>
                            <td class="text-end"><?= moeda($v->total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
