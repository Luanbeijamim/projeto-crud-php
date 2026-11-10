<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

use App\Repositories\Contracts\VendaRepositoryInterface;

final class RelatorioPorCliente implements Relatorio
{
    public function __construct(private VendaRepositoryInterface $vendas)
    {
    }

    public function titulo(): string
    {
        return 'Vendas por cliente';
    }

    public function colunas(): array
    {
        return [
            'cliente'      => ['Cliente', 'texto'],
            'cpf'          => ['CPF', 'cpf'],
            'quantidade'   => ['Nº de vendas', 'inteiro'],
            'total'        => ['Total comprado', 'moeda'],
            'ticket_medio' => ['Ticket médio', 'moeda'],
        ];
    }

    public function linhas(array $filtros): array
    {
        return array_map(static fn (array $l) => [
            'cliente'      => $l['cliente_nome'] ?? 'Consumidor final',
            'cpf'          => $l['cliente_cpf'],
            'quantidade'   => $l['quantidade'],
            'total'        => $l['total'],
            'ticket_medio' => $l['quantidade'] > 0 ? round($l['total'] / $l['quantidade'], 2) : 0,
        ], $this->vendas->porCliente($filtros));
    }
}
