<?php
/**
 * @var string $titulo
 * @var string $mensagem
 */
$mensagem = $mensagem ?? 'Página não encontrada.';
?>
<div class="text-center py-5">
    <i class="bi bi-signpost-split display-1 text-body-secondary"></i>
    <h1 class="h3 mt-3"><?= e($titulo ?? 'Não encontrado') ?></h1>
    <p class="text-body-secondary"><?= e($mensagem) ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Voltar ao início</a>
</div>
