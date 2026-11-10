<?php
/**
 * @var string $busca
 * @var \App\Models\Produto[] $produtos
 * @var \App\Core\Paginacao $paginacao
 */
use App\Core\View;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-box-seam"></i> Produtos</h1>
    <a href="<?= e(url('produtos/novo')) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Novo produto</a>
</div>

<form method="get" action="<?= e(url()) ?>" class="row g-2 mb-3">
    <input type="hidden" name="rota" value="produtos">
    <div class="col-sm-9 col-md-10">
        <input type="search" name="busca" value="<?= e($busca) ?>" class="form-control" placeholder="Buscar por nome ou código">
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
                <th></th>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Validade</th>
                <th class="text-end">Estoque</th>
                <th class="text-end">Preço un.</th>
                <th class="text-end">Preço pct.</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$produtos): ?>
                <tr><td colspan="9" class="text-center text-body-secondary py-4">Nenhum produto encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($produtos as $p): ?>
                <tr class="<?= $p->ativo ? '' : 'table-secondary text-body-secondary' ?>">
                    <td><img src="<?= e(imagem_produto($p->imagem)) ?>" alt="" class="thumb-produto"></td>
                    <td><code><?= e($p->cod) ?></code></td>
                    <td>
                        <?= e($p->nome) ?>
                        <?php if (!$p->ativo): ?><span class="badge text-bg-secondary">Inativo</span><?php endif; ?>
                    </td>
                    <td><?= e($p->categoria) ?></td>
                    <td>
                        <?= data_br($p->validade) ?>
                        <?php if ($p->vencido()): ?><span class="badge text-bg-danger">Vencido</span><?php endif; ?>
                    </td>
                    <td class="text-end">
                        <span class="<?= $p->estoqueBaixo() ? 'badge text-bg-warning' : '' ?>"><?= (int) $p->quantidade ?></span>
                    </td>
                    <td class="text-end"><?= moeda($p->precoUnitario) ?></td>
                    <td class="text-end"><?= moeda($p->precoPacote) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= e(url('produtos/editar', ['id' => $p->id])) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <?php if ($p->ativo): ?>
                            <?= View::partial('partials/confirmar_post', ['acao' => 'produtos/inativar', 'id' => $p->id, 'rotulo' => '', 'icone' => 'bi-eye-slash', 'classe' => 'btn btn-sm btn-outline-secondary', 'mensagem' => 'Inativar este produto? Ele deixará de aparecer no PDV.']) ?>
                        <?php else: ?>
                            <?= View::partial('partials/confirmar_post', ['acao' => 'produtos/ativar', 'id' => $p->id, 'rotulo' => '', 'icone' => 'bi-eye', 'classe' => 'btn btn-sm btn-outline-success', 'mensagem' => 'Reativar este produto?']) ?>
                        <?php endif; ?>
                        <?= View::partial('partials/confirmar_post', ['acao' => 'produtos/excluir', 'id' => $p->id, 'rotulo' => '', 'icone' => 'bi-trash', 'mensagem' => 'Excluir definitivamente o produto "' . $p->nome . '"?']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?= View::partial('partials/paginacao', ['paginacao' => $paginacao, 'rota' => 'produtos', 'params' => array_filter(['busca' => $busca])]) ?>
</div>
