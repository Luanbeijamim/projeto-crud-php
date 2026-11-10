<?php

declare(strict_types=1);

namespace App\Models;

final class Produto
{
    public function __construct(
        public ?int $id = null,
        public string $cod = '',
        public string $nome = '',
        public string $categoria = '',
        public ?string $validade = null,
        public int $quantidade = 0,
        public float $precoUnitario = 0.0,
        public float $precoPacote = 0.0,
        public ?string $imagem = null,
        public bool $ativo = true
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            cod: (string) ($linha['cod'] ?? ''),
            nome: (string) ($linha['nome'] ?? ''),
            categoria: (string) ($linha['categoria'] ?? ''),
            validade: !empty($linha['validade']) ? (string) $linha['validade'] : null,
            quantidade: (int) ($linha['quantidade'] ?? 0),
            precoUnitario: (float) ($linha['preco_unitario'] ?? 0),
            precoPacote: (float) ($linha['preco_pacote'] ?? 0),
            imagem: !empty($linha['imagem']) ? (string) $linha['imagem'] : null,
            ativo: !isset($linha['ativo']) || filter_var($linha['ativo'], FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function paraFormulario(): array
    {
        return [
            'id'             => $this->id,
            'cod'            => $this->cod,
            'nome'           => $this->nome,
            'categoria'      => $this->categoria,
            'validade'       => $this->validade ? substr($this->validade, 0, 10) : '',
            'quantidade'     => (string) $this->quantidade,
            'preco_unitario' => number_format($this->precoUnitario, 2, '.', ''),
            'preco_pacote'   => number_format($this->precoPacote, 2, '.', ''),
            'imagem'         => $this->imagem,
            'ativo'          => $this->ativo,
        ];
    }

    public function estoqueBaixo(int $limite = 5): bool
    {
        return $this->quantidade <= $limite;
    }

    public function vencido(): bool
    {
        return $this->validade !== null && $this->validade < date('Y-m-d');
    }
}
