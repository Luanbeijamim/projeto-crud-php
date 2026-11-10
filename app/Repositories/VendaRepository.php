<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ItemVenda;
use App\Models\Venda;
use App\Repositories\Contracts\VendaRepositoryInterface;
use PDO;

final class VendaRepository extends Repository implements VendaRepositoryInterface
{
    private const SQL_VENDA = 'SELECT v.*, c.nome AS cliente_nome, c.cpf AS cliente_cpf
                               FROM vendas v
                               LEFT JOIN clientes c ON c.id = v.cliente_id';

    public function inserir(Venda $venda): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO vendas (carrinho_id, cliente_id, forma_pagamento, subtotal, desconto, acrescimo, total,
                                 valor_recebido, troco, status, observacao)
             VALUES (:carrinho, :cliente, :forma, :subtotal, :desconto, :acrescimo, :total,
                     :recebido, :troco, :status, :observacao)
             RETURNING id, criado_em'
        );
        $stmt->bindValue(':carrinho', $venda->carrinhoId, $venda->carrinhoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':cliente', $venda->clienteId, $venda->clienteId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':forma', $venda->formaPagamento);
        $stmt->bindValue(':subtotal', $venda->subtotal);
        $stmt->bindValue(':desconto', $venda->desconto);
        $stmt->bindValue(':acrescimo', $venda->acrescimo);
        $stmt->bindValue(':total', $venda->total);
        $stmt->bindValue(':recebido', $venda->valorRecebido);
        $stmt->bindValue(':troco', $venda->troco);
        $stmt->bindValue(':status', $venda->status);
        $stmt->bindValue(':observacao', $venda->observacao, $venda->observacao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        $linha = $stmt->fetch();
        $venda->criadoEm = $linha['criado_em'];
        return (int) $linha['id'];
    }

    public function inserirItem(int $vendaId, ItemVenda $item): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO venda_itens (venda_id, produto_id, quantidade, preco_unitario, subtotal)
             VALUES (:venda, :produto, :quantidade, :preco, :subtotal)'
        );
        $stmt->execute([
            ':venda'      => $vendaId,
            ':produto'    => $item->produtoId,
            ':quantidade' => $item->quantidade,
            ':preco'      => $item->precoUnitario,
            ':subtotal'   => $item->subtotal,
        ]);
    }

    public function buscarPorId(int $id): ?Venda
    {
        $stmt = $this->db()->prepare(self::SQL_VENDA . ' WHERE v.id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        if (!$linha) {
            return null;
        }
        $venda = Venda::deLinha($linha);
        $venda->itens = $this->itens($venda->id);
        return $venda;
    }

    public function bloquear(int $id): ?Venda
    {
        $stmt = $this->db()->prepare('SELECT id FROM vendas WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() === false ? null : $this->buscarPorId($id);
    }

    public function listar(array $filtros, int $limite, int $offset): array
    {
        [$where, $params] = $this->filtros($filtros);
        $stmt = $this->db()->prepare(self::SQL_VENDA . " {$where} ORDER BY v.criado_em DESC, v.id DESC LIMIT :limite OFFSET :offset");
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([Venda::class, 'deLinha'], $stmt->fetchAll());
    }

    public function contar(array $filtros): int
    {
        [$where, $params] = $this->filtros($filtros);
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM vendas v {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function resumo(array $filtros): array
    {
        [$where, $params] = $this->filtros($filtros);
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) AS quantidade,
                    COUNT(*) FILTER (WHERE v.status = 'concluida') AS concluidas,
                    COUNT(*) FILTER (WHERE v.status = 'cancelada') AS canceladas,
                    COALESCE(SUM(v.total) FILTER (WHERE v.status = 'concluida'), 0) AS faturamento
             FROM vendas v {$where}"
        );
        $stmt->execute($params);
        $linha = $stmt->fetch() ?: [];

        $concluidas = (int) ($linha['concluidas'] ?? 0);
        $faturamento = (float) ($linha['faturamento'] ?? 0);

        return [
            'quantidade'   => (int) ($linha['quantidade'] ?? 0),
            'concluidas'   => $concluidas,
            'canceladas'   => (int) ($linha['canceladas'] ?? 0),
            'faturamento'  => $faturamento,
            'ticket_medio' => $concluidas > 0 ? round($faturamento / $concluidas, 2) : 0.0,
        ];
    }

    public function maisVendidos(array $filtros, ?int $limite = 10): array
    {
        [$where, $params] = $this->filtros($filtros, somenteConcluidas: true);
        $sql = "SELECT p.id AS produto_id, p.cod, p.nome,
                       SUM(vi.quantidade) AS quantidade, SUM(vi.subtotal) AS faturamento
                FROM venda_itens vi
                JOIN vendas v ON v.id = vi.venda_id
                JOIN produtos p ON p.id = vi.produto_id
                {$where}
                GROUP BY p.id, p.cod, p.nome
                ORDER BY quantidade DESC, faturamento DESC";
        if ($limite !== null) {
            $sql .= ' LIMIT ' . max(1, $limite);
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return array_map(static fn (array $l) => [
            'produto_id'  => (int) $l['produto_id'],
            'cod'         => (string) $l['cod'],
            'nome'        => (string) $l['nome'],
            'quantidade'  => (int) $l['quantidade'],
            'faturamento' => (float) $l['faturamento'],
        ], $stmt->fetchAll());
    }

    public function porFormaPagamento(array $filtros): array
    {
        [$where, $params] = $this->filtros($filtros, somenteConcluidas: true);
        $stmt = $this->db()->prepare(
            "SELECT v.forma_pagamento, COUNT(*) AS quantidade, SUM(v.total) AS total
             FROM vendas v {$where}
             GROUP BY v.forma_pagamento
             ORDER BY total DESC"
        );
        $stmt->execute($params);

        return array_map(static fn (array $l) => [
            'forma_pagamento' => (string) $l['forma_pagamento'],
            'quantidade'      => (int) $l['quantidade'],
            'total'           => (float) $l['total'],
        ], $stmt->fetchAll());
    }

    public function porCliente(array $filtros): array
    {
        [$where, $params] = $this->filtros($filtros, somenteConcluidas: true);
        $stmt = $this->db()->prepare(
            "SELECT v.cliente_id, c.nome AS cliente_nome, c.cpf AS cliente_cpf,
                    COUNT(*) AS quantidade, SUM(v.total) AS total
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             {$where}
             GROUP BY v.cliente_id, c.nome, c.cpf
             ORDER BY total DESC"
        );
        $stmt->execute($params);

        return array_map(static fn (array $l) => [
            'cliente_id'   => $l['cliente_id'] !== null ? (int) $l['cliente_id'] : null,
            'cliente_nome' => $l['cliente_nome'],
            'cliente_cpf'  => $l['cliente_cpf'] !== null ? trim((string) $l['cliente_cpf']) : null,
            'quantidade'   => (int) $l['quantidade'],
            'total'        => (float) $l['total'],
        ], $stmt->fetchAll());
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->db()->prepare(self::SQL_VENDA . ' WHERE v.cliente_id = :cliente ORDER BY v.criado_em DESC');
        $stmt->execute([':cliente' => $clienteId]);
        return array_map([Venda::class, 'deLinha'], $stmt->fetchAll());
    }

    public function atualizarPagamento(Venda $venda): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE vendas SET forma_pagamento = :forma, desconto = :desconto, acrescimo = :acrescimo, total = :total,
                               valor_recebido = :recebido, troco = :troco, observacao = :observacao
             WHERE id = :id'
        );
        $stmt->bindValue(':forma', $venda->formaPagamento);
        $stmt->bindValue(':desconto', $venda->desconto);
        $stmt->bindValue(':acrescimo', $venda->acrescimo);
        $stmt->bindValue(':total', $venda->total);
        $stmt->bindValue(':recebido', $venda->valorRecebido);
        $stmt->bindValue(':troco', $venda->troco);
        $stmt->bindValue(':observacao', $venda->observacao, $venda->observacao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $venda->id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function definirStatus(int $id, string $status, ?string $observacao): void
    {
        $stmt = $this->db()->prepare('UPDATE vendas SET status = :status, observacao = :observacao WHERE id = :id');
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':observacao', $observacao, $observacao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM vendas WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function itens(int $vendaId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT vi.*, p.nome AS produto_nome, p.cod AS produto_cod
             FROM venda_itens vi
             JOIN produtos p ON p.id = vi.produto_id
             WHERE vi.venda_id = :venda
             ORDER BY vi.id'
        );
        $stmt->execute([':venda' => $vendaId]);
        return array_map([ItemVenda::class, 'deLinha'], $stmt->fetchAll());
    }

    private function filtros(array $filtros, bool $somenteConcluidas = false): array
    {
        $condicoes = [];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'v.criado_em >= CAST(:data_inicio AS date)';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $condicoes[] = "v.criado_em < CAST(:data_fim AS date) + INTERVAL '1 day'";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        if (!empty($filtros['cliente_id'])) {
            $condicoes[] = 'v.cliente_id = :cliente_id';
            $params[':cliente_id'] = (int) $filtros['cliente_id'];
        }
        if (!empty($filtros['forma_pagamento'])) {
            $condicoes[] = 'v.forma_pagamento = :forma_pagamento';
            $params[':forma_pagamento'] = $filtros['forma_pagamento'];
        }
        if (!empty($filtros['status'])) {
            $condicoes[] = 'v.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if ($somenteConcluidas) {
            $condicoes[] = "v.status = 'concluida'";
        }

        return [$condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '', $params];
    }
}
