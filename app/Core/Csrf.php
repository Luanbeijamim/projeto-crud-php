<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const CHAVE = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::CHAVE);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::CHAVE, $token);
        }
        return $token;
    }

    public static function validar(?string $token): bool
    {
        $esperado = Session::get(self::CHAVE);
        return is_string($esperado) && $esperado !== '' && is_string($token) && hash_equals($esperado, $token);
    }
}
