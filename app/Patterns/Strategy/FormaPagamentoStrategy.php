<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

/** Strategy: cada forma de pagamento calcula desconto, acréscimo e troco à sua maneira. */
interface FormaPagamentoStrategy
{
    public function codigo(): string;

    public function nome(): string;

    public function percentualAjuste(): float;

    public function aceitaTroco(): bool;

    public function calcular(float $subtotal, ?float $valorRecebido = null): ResultadoPagamento;
}
