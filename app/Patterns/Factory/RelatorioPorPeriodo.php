<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

use App\Repositories\Contracts\VendaRepositoryInterface;

final class RelatorioPorPeriodo implements Relatorio
{
    private const LIMITE = 5000;

    public function __construct(private VendaRepositoryInterface $vendas)
    {
    }

    public function titulo(): string
    {
        return 'Vendas por período';
    }

    public function colunas(): array
    {
        return [
            'id'              => ['Venda', 'inteiro'],
            'criado_em'       => ['Data', 'data'],
            'cliente'         => ['Cliente', 'texto'],
            'forma_pagamento' => ['Pagamento', 'pagamento'],
            'status'          => ['Status', 'status'],
            'subtotal'        => ['Subtotal', 'moeda'],
            'desconto'        => ['Desconto', 'moeda'],
            'acrescimo'       => ['Acréscimo', 'moeda'],
            'total'           => ['Total', 'moeda'],
        ];
    }

    public function linhas(array $filtros): array
    {
        $linhas = [];
        foreach ($this->vendas->listar($filtros, self::LIMITE, 0) as $v) {
            $linhas[] = [
                'id'              => $v->id,
                'criado_em'       => $v->criadoEm,
                'cliente'         => $v->clienteNome ?? 'Consumidor final',
                'forma_pagamento' => $v->formaPagamento,
                'status'          => $v->status,
                'subtotal'        => $v->subtotal,
                'desconto'        => $v->desconto,
                'acrescimo'       => $v->acrescimo,
                'total'           => $v->total,
            ];
        }
        return $linhas;
    }
}
