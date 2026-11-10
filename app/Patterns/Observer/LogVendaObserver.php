<?php

declare(strict_types=1);

namespace App\Patterns\Observer;

use App\Core\Logger;
use App\Models\Venda;

final class LogVendaObserver implements VendaObserver
{
    public function atualizar(string $evento, Venda $venda): void
    {
        Logger::escrever('vendas.log', strtoupper(str_replace('venda.', '', $evento)), "Venda #{$venda->id}", [
            'cliente' => $venda->clienteNome ?? 'consumidor final',
            'forma'   => $venda->formaPagamento,
            'total'   => number_format($venda->total, 2, '.', ''),
            'itens'   => array_map(static fn ($i) => "{$i->quantidade}x {$i->produtoCod}", $venda->itens),
        ]);
    }
}
