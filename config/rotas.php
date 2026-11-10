<?php

declare(strict_types=1);

/**
 * @var App\Core\Router $router
 */

use App\Controllers\CarrinhoController;
use App\Controllers\ClienteController;
use App\Controllers\HomeController;
use App\Controllers\PdvController;
use App\Controllers\ProdutoController;
use App\Controllers\RelatorioController;

$router->get('', [HomeController::class, 'index']);
$router->get('home', [HomeController::class, 'index']);

$router->get('produtos', [ProdutoController::class, 'index']);
$router->get('produtos/novo', [ProdutoController::class, 'novo']);
$router->post('produtos/salvar', [ProdutoController::class, 'salvar']);
$router->get('produtos/editar', [ProdutoController::class, 'editar']);
$router->post('produtos/atualizar', [ProdutoController::class, 'atualizar']);
$router->post('produtos/excluir', [ProdutoController::class, 'excluir']);
$router->post('produtos/inativar', [ProdutoController::class, 'inativar']);
$router->post('produtos/ativar', [ProdutoController::class, 'ativar']);

$router->get('clientes', [ClienteController::class, 'index']);
$router->get('clientes/novo', [ClienteController::class, 'novo']);
$router->post('clientes/salvar', [ClienteController::class, 'salvar']);
$router->get('clientes/ver', [ClienteController::class, 'ver']);
$router->get('clientes/editar', [ClienteController::class, 'editar']);
$router->post('clientes/atualizar', [ClienteController::class, 'atualizar']);
$router->post('clientes/excluir', [ClienteController::class, 'excluir']);

$router->get('pdv', [PdvController::class, 'index']);
$router->post('pdv/adicionar', [PdvController::class, 'adicionar']);

$router->get('carrinho', [CarrinhoController::class, 'index']);
$router->get('carrinho/novo', [CarrinhoController::class, 'novo']);
$router->post('carrinho/criar', [CarrinhoController::class, 'criar']);
$router->get('carrinho/ver', [CarrinhoController::class, 'ver']);
$router->post('carrinho/adicionar', [CarrinhoController::class, 'adicionar']);
$router->post('carrinho/atualizar-item', [CarrinhoController::class, 'atualizarItem']);
$router->post('carrinho/remover-item', [CarrinhoController::class, 'removerItem']);
$router->post('carrinho/cliente', [CarrinhoController::class, 'definirCliente']);
$router->post('carrinho/esvaziar', [CarrinhoController::class, 'esvaziar']);
$router->post('carrinho/cancelar', [CarrinhoController::class, 'cancelar']);
$router->post('carrinho/excluir', [CarrinhoController::class, 'excluir']);
$router->post('carrinho/finalizar', [CarrinhoController::class, 'finalizar']);

$router->get('relatorios', [RelatorioController::class, 'index']);
$router->get('relatorios/venda', [RelatorioController::class, 'venda']);
$router->get('relatorios/editar', [RelatorioController::class, 'editar']);
$router->post('relatorios/atualizar', [RelatorioController::class, 'atualizar']);
$router->post('relatorios/cancelar', [RelatorioController::class, 'cancelar']);
$router->post('relatorios/excluir', [RelatorioController::class, 'excluir']);
$router->get('relatorios/exportar', [RelatorioController::class, 'exportar']);
