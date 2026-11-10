<?php
/**
 * @var string $conteudo
 * @var array $flashes
 */
$titulo = $titulo ?? 'PDV';
$menuAtivo = $menuAtivo ?? '';
$flashes = $flashes ?? [];
$menu = [
    'home'       => ['Início', url(), 'bi-speedometer2'],
    'pdv'        => ['PDV / Carrinho', url('pdv'), 'bi-cart3'],
    'produtos'   => ['Produtos', url('produtos'), 'bi-box-seam'],
    'clientes'   => ['Clientes', url('clientes'), 'bi-people'],
    'relatorios' => ['Relatórios', url('relatorios'), 'bi-graph-up'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(\App\Core\Config::get('nome')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark d-print-none">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url()) ?>"><i class="bi bi-shop"></i> <?= e(\App\Core\Config::get('nome')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($menu as $chave => [$rotulo, $link, $icone]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $menuAtivo === $chave ? ' active' : '' ?>" href="<?= e($link) ?>"
                            <?= $menuAtivo === $chave ? 'aria-current="page"' : '' ?>>
                            <i class="bi <?= e($icone) ?>"></i> <?= e($rotulo) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?> alert-dismissible fade show d-print-none" role="alert">
            <?= e($flash['mensagem']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endforeach; ?>

    <?= $conteudo ?>
</main>

<footer class="container pb-4 text-center text-body-secondary small d-print-none">
    PDV MVC · Projeto acadêmico · PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
<?php if (!empty($scripts)): foreach ($scripts as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
