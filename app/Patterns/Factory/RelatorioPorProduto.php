<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

use App\Repositories\Contracts\VendaRepositoryInterface;

final class RelatorioPorProduto implements Relatorio
{
    public function __construct(private VendaRepositoryInterface $vendas)
    {
    }

    public function titulo(): string
    {
        return 'Vendas por produto';
    }

    public function colunas(): array
    {
        return [
            'cod'         => ['Código', 'texto'],
            'nome'        => ['Produto', 'texto'],
            'quantidade'  => ['Qtd. vendida', 'inteiro'],
            'faturamento' => ['Faturamento', 'moeda'],
        ];
    }

    public function linhas(array $filtros): array
    {
        return array_map(static fn (array $l) => [
            'cod'         => $l['cod'],
            'nome'        => $l['nome'],
            'quantidade'  => $l['quantidade'],
            'faturamento' => $l['faturamento'],
        ], $this->vendas->maisVendidos($filtros, null));
    }
}
