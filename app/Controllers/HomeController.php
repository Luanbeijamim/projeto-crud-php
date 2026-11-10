<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Repositories\CarrinhoRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;

final class HomeController extends Controller
{
    public function index(): void
    {
        $vendas = new VendaRepository();
        $produtos = new ProdutoRepository();
        $hoje = date('Y-m-d');

        $this->render('home/index', [
            'titulo'        => 'Início',
            'menuAtivo'     => 'home',
            'hoje'          => $vendas->resumo(['data_inicio' => $hoje, 'data_fim' => $hoje]),
            'mes'           => $vendas->resumo(['data_inicio' => date('Y-m-01'), 'data_fim' => $hoje]),
            'estoqueBaixo'  => $produtos->estoqueBaixo((int) Config::get('estoque_baixo')),
            'vencendo'      => $produtos->vencendo((int) Config::get('dias_validade')),
            'abertos'       => count((new CarrinhoRepository())->listarAbertos()),
            'limiteEstoque' => (int) Config::get('estoque_baixo'),
            'diasValidade'  => (int) Config::get('dias_validade'),
        ]);
    }
}
