<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

final class PagamentoCartaoCredito extends PagamentoBase
{
    public function codigo(): string
    {
        return 'credito';
    }

    public function nome(): string
    {
        return 'Cartão de crédito';
    }

    public function percentualAjuste(): float
    {
        return 3.0;
    }
}
