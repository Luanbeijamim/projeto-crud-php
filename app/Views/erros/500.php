<?php
/**
 * @var string $mensagem
 * @var string|null $detalhe
 */
$detalhe = $detalhe ?? null;
?>
<div class="text-center py-5">
    <i class="bi bi-exclamation-octagon display-1 text-danger"></i>
    <h1 class="h3 mt-3"><?= e($titulo ?? 'Erro no sistema') ?></h1>
    <p class="text-body-secondary"><?= e($mensagem ?? 'Ocorreu um erro inesperado.') ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Voltar ao início</a>
</div>
<?php if ($detalhe !== null): ?>
    <div class="alert alert-warning">
        <strong>APP_DEBUG=true</strong> — detalhes visíveis apenas em desenvolvimento:
        <pre class="mb-0 mt-2 small text-wrap"><?= e($detalhe) ?></pre>
    </div>
<?php endif; ?>
