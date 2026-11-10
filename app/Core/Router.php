<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $rotas = ['GET' => [], 'POST' => []];

    public function get(string $rota, array $acao): void
    {
        $this->rotas['GET'][trim($rota, '/')] = $acao;
    }

    public function post(string $rota, array $acao): void
    {
        $this->rotas['POST'][trim($rota, '/')] = $acao;
    }

    public function despachar(Request $request): void
    {
        $metodo = $request->metodo();
        $rota = $request->rota();

        if (!$this->existe($rota) && preg_match('#^(.+)/(\d+)$#', $rota, $m)) {
            $rota = $m[1];
            $request->definirParametro('id', $m[2]);
        }

        $acao = $this->rotas[$metodo][$rota] ?? null;

        if ($acao === null) {
            if ($this->existe($rota)) {
                http_response_code(405);
                View::exibir('erros/404', ['titulo' => 'Método não permitido', 'mensagem' => 'Esta ação só pode ser executada pelo formulário correspondente.']);
                return;
            }
            http_response_code(404);
            View::exibir('erros/404', ['titulo' => 'Página não encontrada', 'mensagem' => 'A rota "' . $rota . '" não existe.']);
            return;
        }

        if ($metodo === 'POST' && !Csrf::validar($request->texto('_csrf'))) {
            Flash::erro('Sua sessão expirou ou o formulário é inválido (token CSRF). Tente novamente.');
            header('Location: ' . ($request->referer() ?: url()));
            return;
        }

        [$classe, $metodoController] = $acao;
        $controller = new $classe();
        $controller->definirRequest($request);
        $controller->$metodoController();
    }

    private function existe(string $rota): bool
    {
        return isset($this->rotas['GET'][$rota]) || isset($this->rotas['POST'][$rota]);
    }
}
