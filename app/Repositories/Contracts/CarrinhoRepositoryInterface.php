<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Carrinho;
use App\Models\ItemCarrinho;

interface CarrinhoRepositoryInterface
{
    public function criar(?int $clienteId): int;

    public function buscarPorId(int $id): ?Carrinho;

    public function bloquear(int $id): ?string;

    public function listarAbertos(): array;

    public function buscarItem(int $carrinhoId, int $produtoId): ?ItemCarrinho;

    public function buscarItemPorId(int $carrinhoId, int $itemId): ?ItemCarrinho;

    public function adicionarItem(int $carrinhoId, int $produtoId, int $quantidade, float $precoUnitario): void;

    public function atualizarQuantidade(int $carrinhoId, int $itemId, int $quantidade): void;

    public function removerItem(int $carrinhoId, int $itemId): void;

    public function esvaziar(int $carrinhoId): void;

    public function definirStatus(int $id, string $status): void;

    public function definirCliente(int $id, ?int $clienteId): void;

    public function excluir(int $id): void;
}
