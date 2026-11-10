<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private array $query,
        private array $post,
        private array $arquivos,
        private array $server
    ) {
    }

    public static function capturar(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER);
    }

    public function metodo(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->metodo() === 'POST';
    }

    public function texto(string $chave, string $padrao = ''): string
    {
        $valor = $this->post[$chave] ?? $this->query[$chave] ?? $padrao;
        return is_scalar($valor) ? trim((string) $valor) : $padrao;
    }

    public function inteiro(string $chave, int $padrao = 0): int
    {
        $valor = filter_var($this->texto($chave), FILTER_VALIDATE_INT);
        return $valor === false ? $padrao : $valor;
    }

    public function decimal(string $chave): ?float
    {
        return Validador::paraDecimal($this->texto($chave));
    }

    public function arquivo(string $chave): ?array
    {
        $arquivo = $this->arquivos[$chave] ?? null;
        if (!is_array($arquivo) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $arquivo;
    }

    public function consulta(): array
    {
        return $this->query;
    }

    public function definirParametro(string $chave, string $valor): void
    {
        $this->query[$chave] = $valor;
    }

    public function referer(): ?string
    {
        return $this->server['HTTP_REFERER'] ?? null;
    }

    public function rota(): string
    {
        if (isset($this->query['rota']) && is_string($this->query['rota'])) {
            $caminho = $this->query['rota'];
        } else {
            $caminho = (string) parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $caminho = rawurldecode($caminho);

            $base = rtrim(dirname(str_replace('\\', '/', $this->server['SCRIPT_NAME'] ?? '/index.php')), '/.');
            foreach ([$base, preg_replace('#/public$#', '', $base)] as $prefixo) {
                if ($prefixo !== '' && str_starts_with($caminho, $prefixo . '/')) {
                    $caminho = substr($caminho, strlen($prefixo));
                    break;
                }
                if ($prefixo !== '' && $caminho === $prefixo) {
                    $caminho = '/';
                    break;
                }
            }
            $caminho = preg_replace('#^/index\.php#', '', $caminho);
        }

        $caminho = strtolower(trim((string) $caminho, '/'));
        return preg_replace('#[^a-z0-9/_-]#', '', $caminho);
    }
}
