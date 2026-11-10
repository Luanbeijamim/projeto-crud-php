<?php
/**
 * @var string $busca
 * @var \App\Models\Cliente[] $clientes
 * @var \App\Core\Paginacao $paginacao
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-people"></i> Clientes</h1>
    <a href="<?= e(url('clientes/novo')) ?>" class="btn btn-success"><i class="bi bi-person-plus"></i> Novo cliente</a>
</div>

<form method="get" action="<?= e(url()) ?>" class="row g-2 mb-3">
    <input type="hidden" name="rota" value="clientes">
    <div class="col-sm-9 col-md-10">
        <input type="search" name="busca" value="<?= e($busca) ?>" class="form-control" placeholder="Buscar por nome ou CPF">
    </div>
    <div class="col-sm-3 col-md-2 d-grid">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Situação</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$clientes): ?>
                <tr><td colspan="6" class="text-center text-body-secondary py-4">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><a href="<?= e(url('clientes/ver', ['id' => $c->id])) ?>"><?= e($c->nome) ?></a></td>
                    <td class="text-nowrap"><?= e(cpf_formatado($c->cpf)) ?></td>
                    <td><?= e($c->email ?? '—') ?></td>
                    <td class="text-nowrap"><?= e($c->telefone ?? '—') ?></td>
                    <td><?= $c->ativo ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= e(url('clientes/ver', ['id' => $c->id])) ?>" class="btn btn-sm btn-outline-secondary" title="Visualizar"><i class="bi bi-eye"></i></a>
                        <a href="<?= e(url('clientes/editar', ['id' => $c->id])) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <?= View::partial('partials/confirmar_post', ['acao' => 'clientes/excluir', 'id' => $c->id, 'rotulo' => '', 'icone' => 'bi-trash', 'mensagem' => 'Excluir o cliente "' . $c->nome . '"? Se ele tiver vendas, será apenas inativado.']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?= View::partial('partials/paginacao', ['paginacao' => $paginacao, 'rota' => 'clientes', 'params' => array_filter(['busca' => $busca])]) ?>
</div>
