<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    private const CHAVE = '_flash';

    public static function sucesso(string $mensagem): void
    {
        self::adicionar('success', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::adicionar('danger', $mensagem);
    }

    public static function aviso(string $mensagem): void
    {
        self::adicionar('warning', $mensagem);
    }

    public static function consumir(): array
    {
        $mensagens = $_SESSION[self::CHAVE] ?? [];
        unset($_SESSION[self::CHAVE]);
        return is_array($mensagens) ? $mensagens : [];
    }

    private static function adicionar(string $tipo, string $mensagem): void
    {
        $_SESSION[self::CHAVE][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }
}
