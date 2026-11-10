<?php

declare(strict_types=1);

namespace App\Models;

final class ItemVenda
{
    public function __construct(
        public ?int $id = null,
        public int $vendaId = 0,
        public int $produtoId = 0,
        public int $quantidade = 0,
        public float $precoUnitario = 0.0,
        public float $subtotal = 0.0,
        public string $produtoNome = '',
        public string $produtoCod = ''
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            vendaId: (int) ($linha['venda_id'] ?? 0),
            produtoId: (int) ($linha['produto_id'] ?? 0),
            quantidade: (int) ($linha['quantidade'] ?? 0),
            precoUnitario: (float) ($linha['preco_unitario'] ?? 0),
            subtotal: (float) ($linha['subtotal'] ?? 0),
            produtoNome: (string) ($linha['produto_nome'] ?? ''),
            produtoCod: (string) ($linha['produto_cod'] ?? ''),
        );
    }

    public static function deItemCarrinho(ItemCarrinho $item): self
    {
        return new self(
            produtoId: $item->produtoId,
            quantidade: $item->quantidade,
            precoUnitario: $item->precoUnitario,
            subtotal: $item->subtotal(),
            produtoNome: $item->produtoNome,
            produtoCod: $item->produtoCod,
        );
    }
}
