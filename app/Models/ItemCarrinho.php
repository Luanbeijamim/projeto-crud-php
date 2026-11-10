<?php

declare(strict_types=1);

namespace App\Models;

final class ItemCarrinho
{
    public function __construct(
        public ?int $id = null,
        public int $carrinhoId = 0,
        public int $produtoId = 0,
        public int $quantidade = 1,
        public float $precoUnitario = 0.0,
        public string $produtoNome = '',
        public string $produtoCod = '',
        public int $estoqueDisponivel = 0,
        public ?string $produtoImagem = null
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            carrinhoId: (int) ($linha['carrinho_id'] ?? 0),
            produtoId: (int) ($linha['produto_id'] ?? 0),
            quantidade: (int) ($linha['quantidade'] ?? 0),
            precoUnitario: (float) ($linha['preco_unitario'] ?? 0),
            produtoNome: (string) ($linha['produto_nome'] ?? ''),
            produtoCod: (string) ($linha['produto_cod'] ?? ''),
            estoqueDisponivel: (int) ($linha['estoque'] ?? 0),
            produtoImagem: $linha['produto_imagem'] ?? null,
        );
    }

    public function subtotal(): float
    {
        return round($this->quantidade * $this->precoUnitario, 2);
    }

    public function semEstoque(): bool
    {
        return $this->quantidade > $this->estoqueDisponivel;
    }
}
