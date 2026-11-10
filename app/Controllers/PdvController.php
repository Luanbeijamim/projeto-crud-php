<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\RegraNegocioException;
use App\Core\Session;
use App\Models\Carrinho;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\ProdutoRepository;
use App\Services\CarrinhoService;

final class PdvController extends Controller
{
    private ProdutoRepositoryInterface $produtos;
    private CarrinhoService $carrinhos;

    public function __construct()
    {
        $this->produtos = new ProdutoRepository();
        $this->carrinhos = new CarrinhoService();
    }

    public function index(): void
    {
        $busca = $this->request->texto('busca');

        $this->render('pdv/index', [
            'titulo'         => 'PDV',
            'menuAtivo'      => 'pdv',
            'busca'          => $busca,
            'resultados'     => $busca !== '' ? $this->produtos->buscar($busca, 30, 0, true) : [],
            'carrinhoAtual'  => $this->carrinhoAtual(),
            'abertos'        => $this->carrinhos->abertos(),
        ]);
    }

    public function adicionar(): void
    {
        $busca = $this->request->texto('busca');
        try {
            $carrinho = $this->carrinhoAtual();
            $carrinhoId = $carrinho?->id ?? $this->carrinhos->criar(null);
            Session::set('carrinho_atual', $carrinhoId);

            $nome = $this->carrinhos->adicionarProduto($carrinhoId, $this->request->inteiro('produto_id'), max(1, $this->request->inteiro('quantidade', 1)));
            Flash::sucesso("\"{$nome}\" adicionado ao carrinho #{$carrinhoId}.");
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
        }

        $this->redirecionar('pdv', array_filter(['busca' => $busca]));
    }

    private function carrinhoAtual(): ?Carrinho
    {
        $id = (int) Session::get('carrinho_atual', 0);
        if ($id <= 0) {
            return null;
        }
        try {
            $carrinho = $this->carrinhos->obter($id);
        } catch (RegraNegocioException) {
            $carrinho = null;
        }
        if ($carrinho === null || !$carrinho->estaAberto()) {
            Session::remover('carrinho_atual');
            return null;
        }
        return $carrinho;
    }
}
