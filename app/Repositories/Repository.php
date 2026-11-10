<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Repository: todo o SQL do sistema fica nas subclasses desta base. */
abstract class Repository
{
    public function __construct(private ?PDO $conexao = null)
    {
    }

    protected function db(): PDO
    {
        return $this->conexao ??= Database::getInstance();
    }

    protected function termoLike(string $termo): string
    {
        return '%' . addcslashes($termo, '%_\\') . '%';
    }
}
