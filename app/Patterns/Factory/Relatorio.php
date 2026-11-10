<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

interface Relatorio
{
    public function titulo(): string;

    public function colunas(): array;

    public function linhas(array $filtros): array;
}
