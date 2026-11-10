<?php
/**
 * @var array $dados
 * @var bool $editando
 */
$acao = $editando ? url('produtos/atualizar') : url('produtos/salvar');
?>
<h1 class="h3 mb-3"><i class="bi bi-box-seam"></i> <?= $editando ? 'Editar produto' : 'Cadastrar produto' ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger">Corrija os campos destacados abaixo.</div>
<?php endif; ?>

<form method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" class="card shadow-sm" novalidate>
    <div class="card-body row g-3">
        <?= csrf_field() ?>
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= (int) $dados['id'] ?>">
        <?php endif; ?>

        <div class="col-md-3">
            <label for="cod" class="form-label">Código *</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'cod') ?>" id="cod" name="cod" maxlength="50" required value="<?= e($dados['cod']) ?>">
            <?= msg_erro($erros, 'cod') ?>
        </div>
        <div class="col-md-6">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'nome') ?>" id="nome" name="nome" maxlength="150" required value="<?= e($dados['nome']) ?>">
            <?= msg_erro($erros, 'nome') ?>
        </div>
        <div class="col-md-3">
            <label for="categoria" class="form-label">Categoria</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'categoria') ?>" id="categoria" name="categoria" maxlength="100" value="<?= e($dados['categoria']) ?>">
            <?= msg_erro($erros, 'categoria') ?>
        </div>
        <div class="col-md-3">
            <label for="validade" class="form-label">Validade</label>
            <input type="date" class="form-control<?= classe_erro($erros, 'validade') ?>" id="validade" name="validade" value="<?= e($dados['validade']) ?>">
            <?= msg_erro($erros, 'validade') ?>
        </div>
        <div class="col-md-3">
            <label for="quantidade" class="form-label">Quantidade em estoque *</label>
            <input type="number" min="0" step="1" class="form-control<?= classe_erro($erros, 'quantidade') ?>" id="quantidade" name="quantidade" required value="<?= e($dados['quantidade']) ?>">
            <?= msg_erro($erros, 'quantidade') ?>
        </div>
        <div class="col-md-3">
            <label for="preco_unitario" class="form-label">Preço unitário (R$) *</label>
            <input type="number" min="0" step="0.01" class="form-control<?= classe_erro($erros, 'preco_unitario') ?>" id="preco_unitario" name="preco_unitario" required value="<?= e($dados['preco_unitario']) ?>">
            <?= msg_erro($erros, 'preco_unitario') ?>
        </div>
        <div class="col-md-3">
            <label for="preco_pacote" class="form-label">Preço do pacote (R$) *</label>
            <input type="number" min="0" step="0.01" class="form-control<?= classe_erro($erros, 'preco_pacote') ?>" id="preco_pacote" name="preco_pacote" required value="<?= e($dados['preco_pacote']) ?>">
            <?= msg_erro($erros, 'preco_pacote') ?>
        </div>

        <div class="col-md-9">
            <label for="imagem" class="form-label">Imagem <?= $editando ? '(opcional — envie para trocar)' : '(opcional)' ?></label>
            <input type="file" class="form-control<?= classe_erro($erros, 'imagem') ?>" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">JPG, PNG ou WEBP, até 2 MB.</div>
            <?= msg_erro($erros, 'imagem') ?>
        </div>
        <div class="col-md-3 text-center">
            <img src="<?= e(imagem_produto($dados['imagem'] ?? null)) ?>" alt="Imagem atual" class="img-thumbnail" style="max-height: 120px">
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= e(url('produtos')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Salvar</button>
    </div>
</form>
