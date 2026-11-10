<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Produto;

interface ProdutoRepositoryInterface
{
    public function buscar(string $termo, int $limite, int $offset, bool $somenteAtivos = false): array;

    public function contar(string $termo, bool $somenteAtivos = false): int;

    public function buscarPorId(int $id): ?Produto;

    public function buscarPorCodigo(string $cod): ?Produto;

    public function estoqueComBloqueio(int $id): ?int;

    public function existeCodigo(string $cod, ?int $ignorarId = null): bool;

    public function inserir(Produto $produto): int;

    public function atualizar(Produto $produto): void;

    public function excluir(int $id): void;

    public function definirAtivo(int $id, bool $ativo): void;

    public function possuiVendas(int $id): bool;

    public function baixarEstoque(int $id, int $quantidade): void;

    public function estornarEstoque(int $id, int $quantidade): void;

    public function estoqueBaixo(int $limite): array;

    public function vencendo(int $dias): array;
}
