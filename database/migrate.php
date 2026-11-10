<?php

declare(strict_types=1);

// Uso: php database/migrate.php [--seed]
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$arquivos = [__DIR__ . '/schema.sql'];
if (in_array('--seed', $argv, true)) {
    $arquivos[] = __DIR__ . '/seed.sql';
}

$pdo = Database::getInstance();
echo 'Conectado: ', $pdo->query('SELECT version()')->fetchColumn(), PHP_EOL;

foreach ($arquivos as $arquivo) {
    echo 'Executando ', basename($arquivo), '... ';
    $pdo->exec((string) file_get_contents($arquivo));
    echo 'OK', PHP_EOL;
}

$contagem = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM produtos) AS produtos,
            (SELECT COUNT(*) FROM clientes) AS clientes,
            (SELECT COUNT(*) FROM vendas) AS vendas"
)->fetch();

printf("Registros: %d produtos, %d clientes, %d vendas\n", $contagem['produtos'], $contagem['clientes'], $contagem['vendas']);
