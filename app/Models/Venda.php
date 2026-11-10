<?php

declare(strict_types=1);

namespace App\Models;

final class Venda
{
    public const CONCLUIDA = 'concluida';
    public const CANCELADA = 'cancelada';

    public function __construct(
        public ?int $id = null,
        public ?int $carrinhoId = null,
        public ?int $clienteId = null,
        public ?string $clienteNome = null,
        public ?string $clienteCpf = null,
        public string $formaPagamento = 'dinheiro',
        public float $subtotal = 0.0,
        public float $desconto = 0.0,
        public float $acrescimo = 0.0,
        public float $total = 0.0,
        public float $valorRecebido = 0.0,
        public float $troco = 0.0,
        public string $status = self::CONCLUIDA,
        public ?string $observacao = null,
        public ?string $criadoEm = null,
        public array $itens = []
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            carrinhoId: isset($linha['carrinho_id']) ? (int) $linha['carrinho_id'] : null,
            clienteId: isset($linha['cliente_id']) ? (int) $linha['cliente_id'] : null,
            clienteNome: $linha['cliente_nome'] ?? null,
            clienteCpf: isset($linha['cliente_cpf']) ? trim((string) $linha['cliente_cpf']) : null,
            formaPagamento: (string) ($linha['forma_pagamento'] ?? 'dinheiro'),
            subtotal: (float) ($linha['subtotal'] ?? 0),
            desconto: (float) ($linha['desconto'] ?? 0),
            acrescimo: (float) ($linha['acrescimo'] ?? 0),
            total: (float) ($linha['total'] ?? 0),
            valorRecebido: (float) ($linha['valor_recebido'] ?? 0),
            troco: (float) ($linha['troco'] ?? 0),
            status: (string) ($linha['status'] ?? self::CONCLUIDA),
            observacao: $linha['observacao'] ?? null,
            criadoEm: $linha['criado_em'] ?? null,
        );
    }

    public function estaConcluida(): bool
    {
        return $this->status === self::CONCLUIDA;
    }

    public function quantidadeItens(): int
    {
        return array_sum(array_map(static fn (ItemVenda $i) => $i->quantidade, $this->itens));
    }
}
