<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Validador;
use App\Models\Cliente;
use App\Repositories\Contracts\ClienteRepositoryInterface;
use PDO;

final class ClienteRepository extends Repository implements ClienteRepositoryInterface
{
    public function buscar(string $termo, int $limite, int $offset): array
    {
        [$where, $params] = $this->filtro($termo);
        $stmt = $this->db()->prepare("SELECT * FROM clientes {$where} ORDER BY ativo DESC, nome LIMIT :limite OFFSET :offset");
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([Cliente::class, 'deLinha'], $stmt->fetchAll());
    }

    public function contar(string $termo): int
    {
        [$where, $params] = $this->filtro($termo);
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM clientes {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $stmt = $this->db()->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? Cliente::deLinha($linha) : null;
    }

    public function existeCpf(string $cpf, ?int $ignorarId = null): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM clientes WHERE cpf = :cpf AND id <> :id');
        $stmt->execute([':cpf' => Validador::somenteDigitos($cpf), ':id' => $ignorarId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public function inserir(Cliente $cliente): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO clientes (nome, cpf, email, telefone, endereco, data_nascimento, ativo)
             VALUES (:nome, :cpf, :email, :telefone, :endereco, :data_nascimento, :ativo)
             RETURNING id'
        );
        $stmt->execute($this->parametros($cliente));
        return (int) $stmt->fetchColumn();
    }

    public function atualizar(Cliente $cliente): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE clientes SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone,
                    endereco = :endereco, data_nascimento = :data_nascimento, ativo = :ativo, atualizado_em = now()
             WHERE id = :id'
        );
        $stmt->execute($this->parametros($cliente) + [':id' => $cliente->id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function definirAtivo(int $id, bool $ativo): void
    {
        $stmt = $this->db()->prepare('UPDATE clientes SET ativo = :ativo, atualizado_em = now() WHERE id = :id');
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function possuiVendas(int $id): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM vendas WHERE cliente_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function listarAtivos(): array
    {
        $stmt = $this->db()->query('SELECT * FROM clientes WHERE ativo ORDER BY nome');
        return array_map([Cliente::class, 'deLinha'], $stmt->fetchAll());
    }

    public function listarTodos(): array
    {
        $stmt = $this->db()->query('SELECT * FROM clientes ORDER BY nome');
        return array_map([Cliente::class, 'deLinha'], $stmt->fetchAll());
    }

    private function filtro(string $termo): array
    {
        if ($termo === '') {
            return ['', []];
        }
        $params = [':termo_nome' => $this->termoLike($termo)];
        $sql = 'WHERE (nome ILIKE :termo_nome';
        $digitos = Validador::somenteDigitos($termo);
        if ($digitos !== '') {
            $sql .= ' OR cpf LIKE :termo_cpf';
            $params[':termo_cpf'] = '%' . $digitos . '%';
        }
        return [$sql . ')', $params];
    }

    private function parametros(Cliente $c): array
    {
        return [
            ':nome'            => $c->nome,
            ':cpf'             => Validador::somenteDigitos($c->cpf),
            ':email'           => $c->email ?: null,
            ':telefone'        => $c->telefone ?: null,
            ':endereco'        => $c->endereco ?: null,
            ':data_nascimento' => $c->dataNascimento ?: null,
            ':ativo'           => $c->ativo ? 'true' : 'false',
        ];
    }
}
