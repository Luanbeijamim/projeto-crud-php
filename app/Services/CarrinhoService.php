<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\RegraNegocioException;
use App\Models\Carrinho;
use App\Repositories\CarrinhoRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\Contracts\CarrinhoRepositoryInterface;
use App\Repositories\Contracts\ClienteRepositoryInterface;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\ProdutoRepository;

final class CarrinhoService
{
    public function __construct(
        private CarrinhoRepositoryInterface $carrinhos = new CarrinhoRepository(),
        private ProdutoRepositoryInterface $produtos = new ProdutoRepository(),
        private ClienteRepositoryInterface $clientes = new ClienteRepository()
    ) {
    }

    public function criar(?int $clienteId): int
    {
        return $this->carrinhos->criar($this->validarCliente($clienteId));
    }

    public function obter(int $id): Carrinho
    {
        return $this->carrinhos->buscarPorId($id) ?? throw new RegraNegocioException('Carrinho não encontrado.');
    }

    public function abertos(): array
    {
        return $this->carrinhos->listarAbertos();
    }

    public function adicionarProduto(int $carrinhoId, int $produtoId, int $quantidade): string
    {
        $this->obterAberto($carrinhoId);
        $produto = $this->produtos->buscarPorId($produtoId);
        if ($produto === null || !$produto->ativo) {
            throw new RegraNegocioException('Produto não encontrado ou inativo.');
        }

        return $this->adicionar($carrinhoId, $produto->id, $produto->nome, $produto->quantidade, $produto->precoUnitario, $quantidade);
    }

    public function adicionarPorCodigo(int $carrinhoId, string $cod, int $quantidade): string
    {
        $this->obterAberto($carrinhoId);
        $produto = $this->produtos->buscarPorCodigo($cod);
        if ($produto === null || !$produto->ativo) {
            throw new RegraNegocioException("Nenhum produto ativo com o código \"{$cod}\".");
        }

        return $this->adicionar($carrinhoId, $produto->id, $produto->nome, $produto->quantidade, $produto->precoUnitario, $quantidade);
    }

    public function atualizarItem(int $carrinhoId, int $itemId, int $quantidade): void
    {
        $this->obterAberto($carrinhoId);
        $item = $this->carrinhos->buscarItemPorId($carrinhoId, $itemId) ?? throw new RegraNegocioException('Item não encontrado no carrinho.');

        if ($quantidade <= 0) {
            $this->carrinhos->removerItem($carrinhoId, $itemId);
            return;
        }

        if ($quantidade > $item->estoqueDisponivel) {
            throw new RegraNegocioException("Estoque insuficiente para \"{$item->produtoNome}\": disponível {$item->estoqueDisponivel}.");
        }

        $this->carrinhos->atualizarQuantidade($carrinhoId, $itemId, $quantidade);
    }

    public function removerItem(int $carrinhoId, int $itemId): void
    {
        $this->obterAberto($carrinhoId);
        $this->carrinhos->removerItem($carrinhoId, $itemId);
    }

    public function esvaziar(int $carrinhoId): void
    {
        $this->obterAberto($carrinhoId);
        $this->carrinhos->esvaziar($carrinhoId);
    }

    public function cancelar(int $carrinhoId): void
    {
        $this->obterAberto($carrinhoId);
        $this->carrinhos->definirStatus($carrinhoId, Carrinho::CANCELADO);
    }

    public function excluir(int $carrinhoId): void
    {
        $carrinho = $this->obter($carrinhoId);
        if ($carrinho->status === Carrinho::FINALIZADO) {
            throw new RegraNegocioException('Carrinhos finalizados não podem ser excluídos (estão ligados a uma venda).');
        }
        if ($carrinho->estaAberto() && !$carrinho->vazio()) {
            throw new RegraNegocioException('Esvazie ou cancele o carrinho antes de excluí-lo.');
        }
        $this->carrinhos->excluir($carrinhoId);
    }

    public function definirCliente(int $carrinhoId, ?int $clienteId): void
    {
        $this->obterAberto($carrinhoId);
        $this->carrinhos->definirCliente($carrinhoId, $this->validarCliente($clienteId));
    }

    private function adicionar(int $carrinhoId, int $produtoId, string $nome, int $estoque, float $preco, int $quantidade): string
    {
        if ($quantidade < 1) {
            throw new RegraNegocioException('A quantidade deve ser pelo menos 1.');
        }

        $noCarrinho = $this->carrinhos->buscarItem($carrinhoId, $produtoId)?->quantidade ?? 0;
        if ($noCarrinho + $quantidade > $estoque) {
            throw new RegraNegocioException(
                "Estoque insuficiente para \"{$nome}\": disponível {$estoque}" . ($noCarrinho ? ", já há {$noCarrinho} no carrinho." : '.')
            );
        }

        $this->carrinhos->adicionarItem($carrinhoId, $produtoId, $quantidade, $preco);
        return $nome;
    }

    private function obterAberto(int $carrinhoId): Carrinho
    {
        $carrinho = $this->obter($carrinhoId);
        if (!$carrinho->estaAberto()) {
            throw new RegraNegocioException("O carrinho #{$carrinhoId} está {$carrinho->status} e não pode ser alterado.");
        }
        return $carrinho;
    }

    private function validarCliente(?int $clienteId): ?int
    {
        if ($clienteId === null || $clienteId <= 0) {
            return null;
        }
        $cliente = $this->clientes->buscarPorId($clienteId);
        if ($cliente === null || !$cliente->ativo) {
            throw new RegraNegocioException('Cliente não encontrado ou inativo.');
        }
        return $cliente->id;
    }
}
