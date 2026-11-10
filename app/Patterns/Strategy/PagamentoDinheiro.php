<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

use App\Core\RegraNegocioException;

final class PagamentoDinheiro extends PagamentoBase
{
    public function codigo(): string
    {
        return 'dinheiro';
    }

    public function nome(): string
    {
        return 'Dinheiro';
    }

    public function percentualAjuste(): float
    {
        return -5.0;
    }

    public function aceitaTroco(): bool
    {
        return true;
    }

    protected function fecharPagamento(float $subtotal, float $desconto, float $acrescimo, float $total, ?float $valorRecebido): ResultadoPagamento
    {
        if ($valorRecebido === null || $valorRecebido < $total) {
            throw new RegraNegocioException(sprintf(
                'Valor recebido insuficiente. Total a pagar: R$ %s.',
                number_format($total, 2, ',', '.')
            ));
        }

        return new ResultadoPagamento($subtotal, $desconto, $acrescimo, $total, round($valorRecebido, 2), round($valorRecebido - $total, 2));
    }
}
