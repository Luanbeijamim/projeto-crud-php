<?php

declare(strict_types=1);

if (!function_exists('carregar_env')) {
    function carregar_env(string $arquivo): array
    {
        if (!is_file($arquivo) || !is_readable($arquivo)) {
            return [];
        }

        $valores = [];
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor);

            $tamanho = strlen($valor);
            if ($tamanho >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[$tamanho - 1] === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }

            $valores[$chave] = $valor;
        }

        return $valores;
    }
}

$env = carregar_env(dirname(__DIR__) . '/.env');

$ler = static function (string $chave, ?string $padrao = null) use ($env): ?string {
    if (array_key_exists($chave, $env)) {
        return $env[$chave];
    }
    $valor = getenv($chave);
    return $valor === false ? $padrao : $valor;
};

$booleano = static fn (?string $valor): bool => filter_var($valor, FILTER_VALIDATE_BOOLEAN);

return [
    'nome'          => 'PDV MVC',
    'db_url'        => (string) $ler('SRVDB', ''),
    'debug'         => $booleano($ler('APP_DEBUG', 'false')),
    'app_url'       => rtrim((string) $ler('APP_URL', ''), '/'),
    'url_amigavel'  => $booleano($ler('APP_URL_AMIGAVEL', 'false')),
    'timezone'      => 'America/Cuiaba',
    'por_pagina'    => 10,
    'estoque_baixo' => 5,
    'dias_validade' => 30,
    'upload' => [
        'diretorio'  => dirname(__DIR__) . '/public/uploads/produtos',
        'url'        => 'uploads/produtos',
        'tamanho_max' => 2 * 1024 * 1024,
        'tipos'      => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
    'logs' => dirname(__DIR__) . '/storage/logs',
];
