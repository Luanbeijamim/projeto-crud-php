<?php
/**
 * @var string $acao
 * @var int $id
 * @var string $rotulo
 * @var string $classe
 * @var string $mensagem
 */
$classe = $classe ?? 'btn btn-sm btn-outline-danger';
$mensagem = $mensagem ?? 'Tem certeza?';
$icone = $icone ?? '';
?>
<form method="post" action="<?= e(url($acao)) ?>" class="d-inline" data-confirmar="<?= e($mensagem) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <button type="submit" class="<?= e($classe) ?>"><?php if ($icone): ?><i class="bi <?= e($icone) ?>"></i> <?php endif; ?><?= e($rotulo) ?></button>
</form>
