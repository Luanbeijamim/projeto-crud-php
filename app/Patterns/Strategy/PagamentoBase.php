<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

abstract class PagamentoBase implements FormaPagamentoStrategy
{
    public function aceitaTroco(): bool
    {
        return false;
    }

    public function calcular(float $subtotal, ?float $valorRecebido = null): ResultadoPagamento
    {
        $subtotal = round($subtotal, 2);
        $ajuste = round($subtotal * abs($this->percentualAjuste()) / 100, 2);
        $desconto = $this->percentualAjuste() < 0 ? $ajuste : 0.0;
        $acrescimo = $this->percentualAjuste() > 0 ? $ajuste : 0.0;
        $total = round($subtotal - $desconto + $acrescimo, 2);

        return $this->fecharPagamento($subtotal, $desconto, $acrescimo, $total, $valorRecebido);
    }

    protected function fecharPagamento(float $subtotal, float $desconto, float $acrescimo, float $total, ?float $valorRecebido): ResultadoPagamento
    {
        return new ResultadoPagamento($subtotal, $desconto, $acrescimo, $total, $total, 0.0);
    }
}
