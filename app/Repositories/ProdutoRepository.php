<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\RegraNegocioException;
use App\Models\Produto;
use App\Repositories\Contracts\ProdutoRepositoryInterface;

final class ProdutoRepository extends Repository implements ProdutoRepositoryInterface
{
    public function buscar(string $termo, int $limite, int $offset, bool $somenteAtivos = false): array
    {
        [$where, $params] = $this->filtro($termo, $somenteAtivos);
        $stmt = $this->db()->prepare("SELECT * FROM produtos {$where} ORDER BY nome LIMIT :limite OFFSET :offset");
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map([Produto::class, 'deLinha'], $stmt->fetchAll());
    }

    public function contar(string $termo, bool $somenteAtivos = false): int
    {
        [$where, $params] = $this->filtro($termo, $somenteAtivos);
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM produtos {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?Produto
    {
        $stmt = $this->db()->prepare('SELECT * FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? Produto::deLinha($linha) : null;
    }

    public function buscarPorCodigo(string $cod): ?Produto
    {
        $stmt = $this->db()->prepare('SELECT * FROM produtos WHERE cod = :cod');
        $stmt->execute([':cod' => $cod]);
        $linha = $stmt->fetch();
        return $linha ? Produto::deLinha($linha) : null;
    }

    public function estoqueComBloqueio(int $id): ?int
    {
        $stmt = $this->db()->prepare('SELECT quantidade FROM produtos WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : (int) $valor;
    }

    public function existeCodigo(string $cod, ?int $ignorarId = null): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM produtos WHERE cod = :cod AND id <> :id');
        $stmt->execute([':cod' => $cod, ':id' => $ignorarId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public function inserir(Produto $produto): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO produtos (cod, nome, categoria, validade, quantidade, preco_unitario, preco_pacote, imagem, ativo)
             VALUES (:cod, :nome, :categoria, :validade, :quantidade, :preco_unitario, :preco_pacote, :imagem, :ativo)
             RETURNING id'
        );
        $stmt->execute($this->parametros($produto));
        return (int) $stmt->fetchColumn();
    }

    public function atualizar(Produto $produto): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE produtos SET cod = :cod, nome = :nome, categoria = :categoria, validade = :validade,
                    quantidade = :quantidade, preco_unitario = :preco_unitario, preco_pacote = :preco_pacote,
                    imagem = :imagem, ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($this->parametros($produto) + [':id' => $produto->id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function definirAtivo(int $id, bool $ativo): void
    {
        $stmt = $this->db()->prepare('UPDATE produtos SET ativo = :ativo WHERE id = :id');
        $stmt->bindValue(':ativo', $ativo, \PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    public function possuiVendas(int $id): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM venda_itens WHERE produto_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function baixarEstoque(int $id, int $quantidade): void
    {
        // "quantidade >= :minimo" impede estoque negativo mesmo com vendas simultâneas.
        $stmt = $this->db()->prepare(
            'UPDATE produtos SET quantidade = quantidade - :qtd WHERE id = :id AND quantidade >= :minimo'
        );
        $stmt->execute([':qtd' => $quantidade, ':minimo' => $quantidade, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new RegraNegocioException("Estoque insuficiente para o produto #{$id}.");
        }
    }

    public function estornarEstoque(int $id, int $quantidade): void
    {
        $stmt = $this->db()->prepare('UPDATE produtos SET quantidade = quantidade + :qtd WHERE id = :id');
        $stmt->execute([':qtd' => $quantidade, ':id' => $id]);
    }

    public function estoqueBaixo(int $limite): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM produtos WHERE ativo AND quantidade <= :limite ORDER BY quantidade, nome LIMIT 20');
        $stmt->execute([':limite' => $limite]);
        return array_map([Produto::class, 'deLinha'], $stmt->fetchAll());
    }

    public function vencendo(int $dias): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM produtos
             WHERE ativo AND validade IS NOT NULL AND validade <= CURRENT_DATE + make_interval(days => :dias)
             ORDER BY validade LIMIT 20"
        );
        $stmt->execute([':dias' => $dias]);
        return array_map([Produto::class, 'deLinha'], $stmt->fetchAll());
    }

    private function filtro(string $termo, bool $somenteAtivos): array
    {
        $condicoes = [];
        $params = [];
        if ($termo !== '') {
            // Nomes diferentes porque, com prepares nativos, o PDO não aceita repetir o mesmo parâmetro.
            $condicoes[] = '(nome ILIKE :termo_nome OR cod ILIKE :termo_cod)';
            $params[':termo_nome'] = $this->termoLike($termo);
            $params[':termo_cod'] = $this->termoLike($termo);
        }
        if ($somenteAtivos) {
            $condicoes[] = 'ativo';
        }
        return [$condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '', $params];
    }

    private function parametros(Produto $p): array
    {
        return [
            ':cod'            => $p->cod,
            ':nome'           => $p->nome,
            ':categoria'      => $p->categoria,
            ':validade'       => $p->validade,
            ':quantidade'     => $p->quantidade,
            ':preco_unitario' => $p->precoUnitario,
            ':preco_pacote'   => $p->precoPacote,
            ':imagem'         => $p->imagem,
            ':ativo'          => $p->ativo ? 'true' : 'false',
        ];
    }
}
