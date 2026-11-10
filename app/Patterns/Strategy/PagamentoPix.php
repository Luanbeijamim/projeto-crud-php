<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

final class PagamentoPix extends PagamentoBase
{
    public function codigo(): string
    {
        return 'pix';
    }

    public function nome(): string
    {
        return 'PIX';
    }

    public function percentualAjuste(): float
    {
        return -2.0;
    }
}
