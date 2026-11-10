<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\RegraNegocioException;
use App\Core\Session;
use App\Patterns\Strategy\FormaPagamentoFactory;
use App\Repositories\ClienteRepository;
use App\Repositories\Contracts\ClienteRepositoryInterface;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\ProdutoRepository;
use App\Services\CarrinhoService;
use App\Services\VendaService;

final class CarrinhoController extends Controller
{
    private CarrinhoService $carrinhos;
    private ClienteRepositoryInterface $clientes;
    private ProdutoRepositoryInterface $produtos;

    public function __construct()
    {
        $this->carrinhos = new CarrinhoService();
        $this->clientes = new ClienteRepository();
        $this->produtos = new ProdutoRepository();
    }

    public function index(): void
    {
        $this->render('carrinho/index', [
            'titulo'    => 'Carrinhos abertos',
            'menuAtivo' => 'pdv',
            'abertos'   => $this->carrinhos->abertos(),
        ]);
    }

    public function novo(): void
    {
        $this->render('carrinho/novo', [
            'titulo'    => 'Novo carrinho',
            'menuAtivo' => 'pdv',
            'clientes'  => $this->clientes->listarAtivos(),
        ]);
    }

    public function criar(): void
    {
        try {
            $clienteId = $this->request->inteiro('cliente_id');
            $id = $this->carrinhos->criar($clienteId > 0 ? $clienteId : null);
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
            $this->redirecionar('carrinho/novo');
        }

        Session::set('carrinho_atual', $id);
        Flash::sucesso("Carrinho #{$id} aberto.");
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function ver(): void
    {
        try {
            $carrinho = $this->carrinhos->obter($this->id());
        } catch (RegraNegocioException) {
            $this->naoEncontrado('Carrinho não encontrado.');
        }

        if ($carrinho->estaAberto()) {
            Session::set('carrinho_atual', $carrinho->id);
        }

        $busca = $this->request->texto('busca');

        $this->render('carrinho/ver', [
            'titulo'     => "Carrinho #{$carrinho->id}",
            'menuAtivo'  => 'pdv',
            'carrinho'   => $carrinho,
            'busca'      => $busca,
            'resultados' => ($busca !== '' && $carrinho->estaAberto()) ? $this->produtos->buscar($busca, 20, 0, true) : [],
            'clientes'   => $carrinho->estaAberto() ? $this->clientes->listarAtivos() : [],
            'formas'     => FormaPagamentoFactory::todas(),
        ]);
    }

    public function adicionar(): void
    {
        $id = $this->id();
        $quantidade = max(1, $this->request->inteiro('quantidade', 1));
        $codigo = $this->request->texto('codigo');

        $this->executar(function () use ($id, $quantidade, $codigo): void {
            $nome = $codigo !== ''
                ? $this->carrinhos->adicionarPorCodigo($id, $codigo, $quantidade)
                : $this->carrinhos->adicionarProduto($id, $this->request->inteiro('produto_id'), $quantidade);
            Flash::sucesso("{$quantidade}x \"{$nome}\" adicionado.");
        });

        $this->redirecionar('carrinho/ver', array_filter(['id' => $id, 'busca' => $this->request->texto('busca')]));
    }

    public function atualizarItem(): void
    {
        $id = $this->id();
        $this->executar(fn () => $this->carrinhos->atualizarItem($id, $this->request->inteiro('item_id'), $this->request->inteiro('quantidade')));
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function definirCliente(): void
    {
        $id = $this->id();
        $clienteId = $this->request->inteiro('cliente_id');
        $this->executar(function () use ($id, $clienteId): void {
            $this->carrinhos->definirCliente($id, $clienteId > 0 ? $clienteId : null);
            Flash::sucesso('Cliente do carrinho atualizado.');
        });
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function removerItem(): void
    {
        $id = $this->id();
        $this->executar(function () use ($id): void {
            $this->carrinhos->removerItem($id, $this->request->inteiro('item_id'));
            Flash::sucesso('Item removido do carrinho.');
        });
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function esvaziar(): void
    {
        $id = $this->id();
        $this->executar(function () use ($id): void {
            $this->carrinhos->esvaziar($id);
            Flash::sucesso('Carrinho esvaziado.');
        });
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function cancelar(): void
    {
        $id = $this->id();
        $ok = $this->executar(function () use ($id): void {
            $this->carrinhos->cancelar($id);
            Flash::sucesso("Carrinho #{$id} cancelado.");
        });
        if ($ok) {
            $this->esquecerCarrinhoAtual($id);
            $this->redirecionar('carrinho');
        }
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        $id = $this->id();
        $ok = $this->executar(function () use ($id): void {
            $this->carrinhos->excluir($id);
            Flash::sucesso("Carrinho #{$id} excluído.");
        });
        if ($ok) {
            $this->esquecerCarrinhoAtual($id);
            $this->redirecionar('carrinho');
        }
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }

    public function finalizar(): void
    {
        $id = $this->id();
        $forma = $this->request->texto('forma_pagamento');
        $recebido = $this->request->decimal('valor_recebido');
        $observacao = $this->request->texto('observacao');

        try {
            $venda = VendaService::padrao()->finalizar($id, $forma, $recebido, $observacao);
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
            $this->redirecionar('carrinho/ver', ['id' => $id]);
        }

        $this->esquecerCarrinhoAtual($id);
        Flash::sucesso(sprintf('Venda #%d finalizada! Total %s%s.', $venda->id, moeda($venda->total), $venda->troco > 0 ? ' · troco ' . moeda($venda->troco) : ''));
        $this->redirecionar('relatorios/venda', ['id' => $venda->id]);
    }

    private function executar(callable $acao): bool
    {
        try {
            $acao();
            return true;
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
            return false;
        }
    }

    private function esquecerCarrinhoAtual(int $id): void
    {
        if ((int) Session::get('carrinho_atual', 0) === $id) {
            Session::remover('carrinho_atual');
        }
    }
}
