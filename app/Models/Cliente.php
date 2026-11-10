<?php

declare(strict_types=1);

namespace App\Models;

final class Cliente
{
    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public string $cpf = '',
        public ?string $email = null,
        public ?string $telefone = null,
        public ?string $endereco = null,
        public ?string $dataNascimento = null,
        public bool $ativo = true,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: isset($linha['id']) ? (int) $linha['id'] : null,
            nome: (string) ($linha['nome'] ?? ''),
            cpf: trim((string) ($linha['cpf'] ?? '')),
            email: $linha['email'] ?? null,
            telefone: $linha['telefone'] ?? null,
            endereco: $linha['endereco'] ?? null,
            dataNascimento: $linha['data_nascimento'] ?? null,
            ativo: !isset($linha['ativo']) || filter_var($linha['ativo'], FILTER_VALIDATE_BOOLEAN),
            criadoEm: $linha['criado_em'] ?? null,
            atualizadoEm: $linha['atualizado_em'] ?? null,
        );
    }

    public function paraFormulario(): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'cpf'             => cpf_formatado($this->cpf),
            'email'           => (string) $this->email,
            'telefone'        => (string) $this->telefone,
            'endereco'        => (string) $this->endereco,
            'data_nascimento' => $this->dataNascimento ? substr($this->dataNascimento, 0, 10) : '',
            'ativo'           => $this->ativo,
        ];
    }
}
