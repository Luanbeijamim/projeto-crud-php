<?php

declare(strict_types=1);

namespace App\Patterns\Observer;

use App\Models\Venda;

/** Observer: reage aos eventos de venda emitidos pelo VendaService. */
interface VendaObserver
{
    public const FINALIZADA = 'venda.finalizada';
    public const CANCELADA = 'venda.cancelada';

    public function atualizar(string $evento, Venda $venda): void;
}
