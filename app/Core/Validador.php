<?php

declare(strict_types=1);

namespace App\Core;

final class Validador
{
    public static function cpf(string $cpf): bool
    {
        $cpf = self::somenteDigitos($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function data(string $data): bool
    {
        $objeto = \DateTime::createFromFormat('!Y-m-d', $data);
        return $objeto !== false && $objeto->format('Y-m-d') === $data;
    }

    public static function somenteDigitos(string $valor): string
    {
        return (string) preg_replace('/\D/', '', $valor);
    }

    public static function paraDecimal(string $valor): ?float
    {
        $valor = trim(str_replace(['R$', ' '], '', $valor));
        if ($valor === '') {
            return null;
        }
        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }
        return is_numeric($valor) ? round((float) $valor, 2) : null;
    }

    public static function tamanho(string $valor): int
    {
        return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : (int) preg_match_all('/./us', $valor);
    }
}
