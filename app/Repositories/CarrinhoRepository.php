<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Carrinho;
use App\Models\ItemCarrinho;
use App\Repositories\Contracts\CarrinhoRepositoryInterface;
use PDO;

final class CarrinhoRepository extends Repository implements CarrinhoRepositoryInterface
{
    private const SQL_ITENS = 'SELECT ci.*, p.nome AS produto_nome, p.cod AS produto_cod, p.quantidade AS estoque, p.imagem AS produto_imagem
                               FROM carrinho_itens ci
                               JOIN produtos p ON p.id = ci.produto_id';

    public function criar(?int $clienteId): int
    {
        $stmt = $this->db()->prepare("INSERT INTO carrinhos (cliente_id, status, atualizado_em) VALUES (:cliente, 'aberto', now()) RETURNING id");
        $stmt->bindValue(':cliente', $clienteId, $clienteId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?Carrinho
    {
        $stmt = $this->db()->prepare(
            'SELECT c.*, cl.nome AS cliente_nome
             FROM carrinhos c
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        if (!$linha) {
            return null;
        }

        $carrinho = Carrinho::deLinha($linha);

        $stmt = $this->db()->prepare(self::SQL_ITENS . ' WHERE ci.carrinho_id = :id ORDER BY ci.id');
        $stmt->execute([':id' => $id]);
        $carrinho->itens = array_map([ItemCarrinho::class, 'deLinha'], $stmt->fetchAll());

        return $carrinho;
    }

    public function bloquear(int $id): ?string
    {
        $stmt = $this->db()->prepare('SELECT status FROM carrinhos WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $status = $stmt->fetchColumn();
        return $status === false ? null : (string) $status;
    }

    public function listarAbertos(): array
    {
        $stmt = $this->db()->query(
            "SELECT c.*, cl.nome AS cliente_nome,
                    COALESCE(SUM(ci.quantidade), 0) AS quantidade_itens,
                    COALESCE(SUM(ci.quantidade * ci.preco_unitario), 0) AS valor_total
             FROM carrinhos c
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             LEFT JOIN carrinho_itens ci ON ci.carrinho_id = c.id
             WHERE c.status = 'aberto'
             GROUP BY c.id, cl.nome
             ORDER BY COALESCE(c.atualizado_em, c.criado_em) DESC"
        );
        return array_map([Carrinho::class, 'deLinha'], $stmt->fetchAll());
    }

    public function buscarItem(int $carrinhoId, int $produtoId): ?ItemCarrinho
    {
        $stmt = $this->db()->prepare(self::SQL_ITENS . ' WHERE ci.carrinho_id = :carrinho AND ci.produto_id = :produto');
        $stmt->execute([':carrinho' => $carrinhoId, ':produto' => $produtoId]);
        $linha = $stmt->fetch();
        return $linha ? ItemCarrinho::deLinha($linha) : null;
    }

    public function buscarItemPorId(int $carrinhoId, int $itemId): ?ItemCarrinho
    {
        $stmt = $this->db()->prepare(self::SQL_ITENS . ' WHERE ci.carrinho_id = :carrinho AND ci.id = :item');
        $stmt->execute([':carrinho' => $carrinhoId, ':item' => $itemId]);
        $linha = $stmt->fetch();
        return $linha ? ItemCarrinho::deLinha($linha) : null;
    }

    public function adicionarItem(int $carrinhoId, int $produtoId, int $quantidade, float $precoUnitario): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO carrinho_itens (carrinho_id, produto_id, quantidade, preco_unitario)
             VALUES (:carrinho, :produto, :quantidade, :preco)
             ON CONFLICT (carrinho_id, produto_id)
             DO UPDATE SET quantidade = carrinho_itens.quantidade + EXCLUDED.quantidade,
                           preco_unitario = EXCLUDED.preco_unitario'
        );
        $stmt->execute([':carrinho' => $carrinhoId, ':produto' => $produtoId, ':quantidade' => $quantidade, ':preco' => $precoUnitario]);
        $this->tocar($carrinhoId);
    }

    public function atualizarQuantidade(int $carrinhoId, int $itemId, int $quantidade): void
    {
        $stmt = $this->db()->prepare('UPDATE carrinho_itens SET quantidade = :quantidade WHERE id = :item AND carrinho_id = :carrinho');
        $stmt->execute([':quantidade' => $quantidade, ':item' => $itemId, ':carrinho' => $carrinhoId]);
        $this->tocar($carrinhoId);
    }

    public function removerItem(int $carrinhoId, int $itemId): void
    {
        $stmt = $this->db()->prepare('DELETE FROM carrinho_itens WHERE id = :item AND carrinho_id = :carrinho');
        $stmt->execute([':item' => $itemId, ':carrinho' => $carrinhoId]);
        $this->tocar($carrinhoId);
    }

    public function esvaziar(int $carrinhoId): void
    {
        $stmt = $this->db()->prepare('DELETE FROM carrinho_itens WHERE carrinho_id = :carrinho');
        $stmt->execute([':carrinho' => $carrinhoId]);
        $this->tocar($carrinhoId);
    }

    public function definirStatus(int $id, string $status): void
    {
        $stmt = $this->db()->prepare('UPDATE carrinhos SET status = :status, atualizado_em = now() WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function definirCliente(int $id, ?int $clienteId): void
    {
        $stmt = $this->db()->prepare('UPDATE carrinhos SET cliente_id = :cliente, atualizado_em = now() WHERE id = :id');
        $stmt->bindValue(':cliente', $clienteId, $clienteId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM carrinhos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function tocar(int $carrinhoId): void
    {
        $stmt = $this->db()->prepare('UPDATE carrinhos SET atualizado_em = now() WHERE id = :id');
        $stmt->execute([':id' => $carrinhoId]);
    }
}
