<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use PDOException;
use Throwable;

final class ErrorHandler
{
    public static function registrar(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', Config::get('debug') ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $nivel, string $mensagem, string $arquivo, int $linha): bool {
            if (!(error_reporting() & $nivel)) {
                return false;
            }
            throw new ErrorException($mensagem, 0, $nivel, $arquivo, $linha);
        });

        set_exception_handler([self::class, 'tratar']);
    }

    public static function tratar(Throwable $erro): void
    {
        Logger::erro($erro->getMessage(), [
            'tipo'    => $erro::class,
            'arquivo' => $erro->getFile() . ':' . $erro->getLine(),
            'rota'    => $_SERVER['REQUEST_URI'] ?? 'cli',
        ]);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Erro: ' . $erro->getMessage() . PHP_EOL);
            exit(1);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        $mensagem = $erro instanceof PDOException
            ? 'Não foi possível acessar o banco de dados no momento. Tente novamente em instantes.'
            : 'Ocorreu um erro inesperado. A equipe técnica foi notificada pelo log do sistema.';

        $dados = [
            'titulo'   => 'Erro no sistema',
            'mensagem' => $mensagem,
            'detalhe'  => Config::get('debug') ? $erro::class . ': ' . $erro->getMessage() . "\n" . $erro->getFile() . ':' . $erro->getLine() . "\n\n" . $erro->getTraceAsString() : null,
        ];

        try {
            View::exibir('erros/500', $dados);
        } catch (Throwable) {
            echo '<h1>Erro no sistema</h1><p>' . htmlspecialchars($mensagem) . '</p>';
        }
    }
}
