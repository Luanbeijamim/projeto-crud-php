<?php
/**
 * @var array $dados
 * @var bool $editando
 */
$acao = $editando ? url('clientes/atualizar') : url('clientes/salvar');
?>
<h1 class="h3 mb-3"><i class="bi bi-person-vcard"></i> <?= $editando ? 'Editar cliente' : 'Cadastrar cliente' ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger">Corrija os campos destacados abaixo.</div>
<?php endif; ?>

<form method="post" action="<?= e($acao) ?>" class="card shadow-sm" novalidate>
    <div class="card-body row g-3">
        <?= csrf_field() ?>
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= (int) $dados['id'] ?>">
        <?php endif; ?>

        <div class="col-md-8">
            <label for="nome" class="form-label">Nome completo *</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'nome') ?>" id="nome" name="nome" maxlength="150" required value="<?= e($dados['nome']) ?>">
            <?= msg_erro($erros, 'nome') ?>
        </div>
        <div class="col-md-4">
            <label for="cpf" class="form-label">CPF *</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'cpf') ?>" id="cpf" name="cpf" maxlength="14" required placeholder="000.000.000-00" inputmode="numeric" value="<?= e($dados['cpf']) ?>">
            <?= msg_erro($erros, 'cpf') ?>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control<?= classe_erro($erros, 'email') ?>" id="email" name="email" maxlength="150" value="<?= e($dados['email']) ?>">
            <?= msg_erro($erros, 'email') ?>
        </div>
        <div class="col-md-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="tel" class="form-control<?= classe_erro($erros, 'telefone') ?>" id="telefone" name="telefone" maxlength="20" placeholder="(65) 99999-9999" value="<?= e($dados['telefone']) ?>">
            <?= msg_erro($erros, 'telefone') ?>
        </div>
        <div class="col-md-3">
            <label for="data_nascimento" class="form-label">Data de nascimento</label>
            <input type="date" class="form-control<?= classe_erro($erros, 'data_nascimento') ?>" id="data_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" value="<?= e($dados['data_nascimento']) ?>">
            <?= msg_erro($erros, 'data_nascimento') ?>
        </div>
        <div class="col-12">
            <label for="endereco" class="form-label">Endereço</label>
            <input type="text" class="form-control<?= classe_erro($erros, 'endereco') ?>" id="endereco" name="endereco" maxlength="255" value="<?= e($dados['endereco']) ?>">
            <?= msg_erro($erros, 'endereco') ?>
        </div>
        <?php if ($editando): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" <?= checked($dados['ativo']) ?>>
                    <label class="form-check-label" for="ativo">Cliente ativo</label>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= e($editando ? url('clientes/ver', ['id' => $dados['id']]) : url('clientes')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Salvar</button>
    </div>
</form>
