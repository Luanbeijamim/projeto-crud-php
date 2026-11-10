<?php

declare(strict_types=1);

namespace App\Models;

final class Carrinho
{
    public const ABERTO = 'aberto';
    public const FINALIZADO = 'finalizado';
    public const CANCELADO = 'cancelado';

    public function __construct(
        public ?int $id = null,
        public ?int $clienteId = null,
        public ?string $clienteNome = null,
        public string $status = self::ABERTO,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
        public array $itens = [],
        public int $quantidadeItens = 0,
        public float $valorTotal = 0.0
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            clienteId: isset($linha['cliente_id']) ? (int) $linha['cliente_id'] : null,
            clienteNome: $linha['cliente_nome'] ?? null,
            status: (string) ($linha['status'] ?? self::ABERTO),
            criadoEm: $linha['criado_em'] ?? null,
            atualizadoEm: $linha['atualizado_em'] ?? null,
            quantidadeItens: (int) ($linha['quantidade_itens'] ?? 0),
            valorTotal: (float) ($linha['valor_total'] ?? 0),
        );
    }

    public function estaAberto(): bool
    {
        return $this->status === self::ABERTO;
    }

    public function total(): float
    {
        if (!$this->itens) {
            return $this->valorTotal;
        }
        return round(array_sum(array_map(static fn (ItemCarrinho $i) => $i->subtotal(), $this->itens)), 2);
    }

    public function vazio(): bool
    {
        return $this->itens === [];
    }
}
