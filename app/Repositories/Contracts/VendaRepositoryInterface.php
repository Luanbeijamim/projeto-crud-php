<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ItemVenda;
use App\Models\Venda;

interface VendaRepositoryInterface
{
    public function inserir(Venda $venda): int;

    public function inserirItem(int $vendaId, ItemVenda $item): void;

    public function buscarPorId(int $id): ?Venda;

    public function bloquear(int $id): ?Venda;

    public function listar(array $filtros, int $limite, int $offset): array;

    public function contar(array $filtros): int;

    public function resumo(array $filtros): array;

    public function maisVendidos(array $filtros, ?int $limite = 10): array;

    public function porFormaPagamento(array $filtros): array;

    public function porCliente(array $filtros): array;

    public function listarPorCliente(int $clienteId): array;

    public function atualizarPagamento(Venda $venda): void;

    public function definirStatus(int $id, string $status, ?string $observacao): void;

    public function excluir(int $id): void;
}
