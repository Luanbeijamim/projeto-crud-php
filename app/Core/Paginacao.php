<?php

declare(strict_types=1);

namespace App\Core;

final class Paginacao
{
    public readonly int $pagina;
    public readonly int $totalPaginas;

    public function __construct(
        public readonly int $total,
        int $pagina,
        public readonly int $porPagina = 10
    ) {
        $this->totalPaginas = max(1, (int) ceil($total / max(1, $porPagina)));
        $this->pagina = min(max(1, $pagina), $this->totalPaginas);
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
