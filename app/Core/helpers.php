<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Patterns\Strategy\FormaPagamentoFactory;

if (!function_exists('e')) {
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(dirname($script), '/.');
    }
    return $base;
}

function url(string $rota = '', array $params = []): string
{
    $rota = trim($rota, '/');

    if (Config::get('url_amigavel')) {
        if (isset($params['id']) && $rota !== '') {
            $rota .= '/' . (int) $params['id'];
            unset($params['id']);
        }
        $consulta = $params ? '?' . http_build_query($params) : '';
        return base_url() . '/' . $rota . $consulta;
    }

    if ($rota === '') {
        $consulta = $params ? '?' . http_build_query($params) : '';
        return base_url() . '/index.php' . $consulta;
    }

    $rotaCodificada = implode('/', array_map('rawurlencode', explode('/', $rota)));
    return base_url() . '/index.php?rota=' . $rotaCodificada . ($params ? '&' . http_build_query($params) : '');
}

function asset(string $caminho): string
{
    return base_url() . '/' . ltrim($caminho, '/');
}

function imagem_produto(?string $arquivo): string
{
    if ($arquivo) {
        $nome = basename($arquivo);
        if (is_file(Config::get('upload.diretorio') . '/' . $nome)) {
            return asset(Config::get('upload.url') . '/' . rawurlencode($nome));
        }
    }
    return asset('assets/img/sem-imagem.svg');
}

function moeda(mixed $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function data_br(?string $data, bool $comHora = false): string
{
    if ($data === null || $data === '') {
        return '—';
    }
    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return e($data);
    }
    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

function cpf_formatado(?string $cpf): string
{
    $digitos = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($digitos) !== 11) {
        return (string) $cpf;
    }
    return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? 'selected' : '';
}

function checked(mixed $valor): string
{
    return $valor ? 'checked' : '';
}

function rotulo_pagamento(?string $codigo): string
{
    return FormaPagamentoFactory::rotulos()[$codigo] ?? (string) $codigo;
}

function badge_status(string $status): string
{
    $cores = [
        'concluida'  => 'success',
        'aberto'     => 'primary',
        'finalizado' => 'success',
        'cancelada'  => 'danger',
        'cancelado'  => 'danger',
    ];
    $rotulos = ['concluida' => 'Concluída', 'cancelada' => 'Cancelada', 'aberto' => 'Aberto', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
    $cor = $cores[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $cor . '">' . e($rotulos[$status] ?? $status) . '</span>';
}

function classe_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function msg_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? '<div class="invalid-feedback">' . e($erros[$campo]) . '</div>' : '';
}
