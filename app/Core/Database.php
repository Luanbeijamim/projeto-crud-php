<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/** Singleton: uma única conexão PDO por requisição, compartilhada por todos os repositórios e pela transação da venda. */
final class Database
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Não é permitido desserializar o Singleton Database.');
    }

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            [$dsn, $usuario, $senha] = self::montarDsn((string) Config::get('db_url'), (string) Config::get('timezone'));

            self::$instancia = new PDO($dsn, $usuario, $senha, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instancia;
    }

    public static function montarDsn(string $url, string $timezone = 'America/Cuiaba'): array
    {
        if ($url === '') {
            throw new RuntimeException('Variável SRVDB não definida. Copie o .env.example para .env e preencha a conexão do Neon.');
        }

        $partes = parse_url($url);
        if ($partes === false || empty($partes['host'])) {
            throw new RuntimeException('SRVDB inválida. Formato esperado: postgresql://usuario:senha@host/banco');
        }

        $host     = $partes['host'];
        $porta    = $partes['port'] ?? 5432;
        $banco    = ltrim($partes['path'] ?? '/neondb', '/');
        $usuario  = urldecode($partes['user'] ?? '');
        $senha    = urldecode($partes['pass'] ?? '');
        $endpoint = explode('.', $host)[0];

        $query = [];
        parse_str($partes['query'] ?? '', $query);
        $sslmode = is_string($query['sslmode'] ?? null) ? $query['sslmode'] : 'require';

        // "endpoint=" evita o erro "Endpoint ID is not specified" do Neon em libpq sem SNI; um PostgreSQL comum rejeitaria a opção.
        $opcoes = str_ends_with($host, '.neon.tech') ? "endpoint={$endpoint} --timezone={$timezone}" : "--timezone={$timezone}";

        $dsn = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;options='%s'",
            $host,
            (int) $porta,
            $banco,
            $sslmode,
            $opcoes
        );

        return [$dsn, $usuario, $senha];
    }
}
