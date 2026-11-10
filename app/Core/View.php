<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function exibir(string $view, array $dados = [], ?string $layout = 'layouts/main'): void
    {
        echo self::renderizar($view, $dados, $layout);
    }

    public static function renderizar(string $view, array $dados = [], ?string $layout = 'layouts/main'): string
    {
        $conteudo = self::incluir($view, $dados);

        if ($layout === null) {
            return $conteudo;
        }

        return self::incluir($layout, $dados + ['conteudo' => $conteudo, 'flashes' => Flash::consumir()]);
    }

    public static function partial(string $view, array $dados = []): string
    {
        return self::incluir($view, $dados);
    }

    private static function incluir(string $view, array $dados): string
    {
        $arquivo = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }

        $renderizar = static function (string $__arquivo, array $__dados): void {
            extract($__dados, EXTR_SKIP);
            require $__arquivo;
        };

        ob_start();
        try {
            $renderizar($arquivo, $dados);
        } catch (\Throwable $erro) {
            ob_end_clean();
            throw $erro;
        }
        return (string) ob_get_clean();
    }
}
