<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Core\RegraNegocioException;
use App\Core\Validador;
use App\Models\Produto;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\ProdutoRepository;
use App\Services\UploadService;

final class ProdutoController extends Controller
{
    private ProdutoRepositoryInterface $produtos;
    private UploadService $upload;

    public function __construct()
    {
        $this->produtos = new ProdutoRepository();
        $this->upload = new UploadService();
    }

    public function index(): void
    {
        $busca = $this->request->texto('busca');
        $paginacao = new Paginacao($this->produtos->contar($busca), $this->pagina(), (int) Config::get('por_pagina'));

        $this->render('produtos/index', [
            'titulo'    => 'Produtos',
            'menuAtivo' => 'produtos',
            'busca'     => $busca,
            'produtos'  => $this->produtos->buscar($busca, $paginacao->porPagina, $paginacao->offset()),
            'paginacao' => $paginacao,
        ]);
    }

    public function novo(): void
    {
        $this->formulario((new Produto())->paraFormulario(), []);
    }

    public function salvar(): void
    {
        [$dados, $erros] = $this->validar();
        if ($erros) {
            $this->formulario($dados, $erros);
            return;
        }

        $produto = $this->montar($dados);
        try {
            $arquivo = $this->request->arquivo('imagem');
            $produto->imagem = $arquivo ? $this->upload->salvarImagem($arquivo) : null;
        } catch (RegraNegocioException $e) {
            $this->formulario($dados, ['imagem' => $e->getMessage()]);
            return;
        }

        $id = $this->produtos->inserir($produto);
        Flash::sucesso("Produto \"{$produto->nome}\" cadastrado com sucesso (#{$id}).");
        $this->redirecionar('produtos');
    }

    public function editar(): void
    {
        $produto = $this->produtos->buscarPorId($this->id()) ?? $this->naoEncontrado('Produto não encontrado.');
        $this->formulario($produto->paraFormulario(), []);
    }

    public function atualizar(): void
    {
        $atual = $this->produtos->buscarPorId($this->id()) ?? $this->naoEncontrado('Produto não encontrado.');

        [$dados, $erros] = $this->validar($atual->id);
        $dados['id'] = $atual->id;
        $dados['imagem'] = $atual->imagem;
        if ($erros) {
            $this->formulario($dados, $erros);
            return;
        }

        $produto = $this->montar($dados);
        $produto->id = $atual->id;
        $produto->ativo = $atual->ativo;
        $produto->imagem = $atual->imagem;

        try {
            $arquivo = $this->request->arquivo('imagem');
            if ($arquivo) {
                $produto->imagem = $this->upload->salvarImagem($arquivo);
            }
        } catch (RegraNegocioException $e) {
            $this->formulario($dados, ['imagem' => $e->getMessage()]);
            return;
        }

        $this->produtos->atualizar($produto);
        if ($produto->imagem !== $atual->imagem) {
            $this->upload->remover($atual->imagem);
        }

        Flash::sucesso("Produto \"{$produto->nome}\" atualizado com sucesso.");
        $this->redirecionar('produtos');
    }

    public function excluir(): void
    {
        $produto = $this->produtos->buscarPorId($this->id()) ?? $this->naoEncontrado('Produto não encontrado.');

        if ($this->produtos->possuiVendas($produto->id)) {
            Flash::aviso("O produto \"{$produto->nome}\" já possui vendas e não pode ser excluído. Use \"Inativar\" para tirá-lo do PDV.");
            $this->redirecionar('produtos', ['busca' => $produto->cod]);
        }

        $this->produtos->excluir($produto->id);
        $this->upload->remover($produto->imagem);
        Flash::sucesso("Produto \"{$produto->nome}\" excluído.");
        $this->redirecionar('produtos');
    }

    public function inativar(): void
    {
        $this->alterarAtivo(false);
    }

    public function ativar(): void
    {
        $this->alterarAtivo(true);
    }

    private function alterarAtivo(bool $ativo): void
    {
        $produto = $this->produtos->buscarPorId($this->id()) ?? $this->naoEncontrado('Produto não encontrado.');
        $this->produtos->definirAtivo($produto->id, $ativo);
        Flash::sucesso("Produto \"{$produto->nome}\" " . ($ativo ? 'reativado' : 'inativado') . '.');
        $this->redirecionar('produtos');
    }

    private function formulario(array $dados, array $erros): void
    {
        $editando = !empty($dados['id']);
        $this->render('produtos/form', [
            'titulo'    => $editando ? 'Editar produto' : 'Novo produto',
            'menuAtivo' => 'produtos',
            'dados'     => $dados,
            'erros'     => $erros,
            'editando'  => $editando,
        ], $erros ? 422 : 200);
    }

    private function validar(?int $ignorarId = null): array
    {
        $dados = [
            'id'             => $ignorarId,
            'cod'            => $this->request->texto('cod'),
            'nome'           => $this->request->texto('nome'),
            'categoria'      => $this->request->texto('categoria'),
            'validade'       => $this->request->texto('validade'),
            'quantidade'     => $this->request->texto('quantidade'),
            'preco_unitario' => $this->request->texto('preco_unitario'),
            'preco_pacote'   => $this->request->texto('preco_pacote'),
            'imagem'         => null,
        ];
        $erros = [];

        if ($dados['cod'] === '') {
            $erros['cod'] = 'Informe o código do produto.';
        } elseif (Validador::tamanho($dados['cod']) > 50) {
            $erros['cod'] = 'O código deve ter até 50 caracteres.';
        } elseif ($this->produtos->existeCodigo($dados['cod'], $ignorarId)) {
            $erros['cod'] = 'Já existe um produto com este código.';
        }

        if ($dados['nome'] === '' || Validador::tamanho($dados['nome']) > 150) {
            $erros['nome'] = 'Informe o nome do produto (até 150 caracteres).';
        }

        if (Validador::tamanho($dados['categoria']) > 100) {
            $erros['categoria'] = 'A categoria deve ter até 100 caracteres.';
        }

        if ($dados['validade'] !== '' && !Validador::data($dados['validade'])) {
            $erros['validade'] = 'Data de validade inválida.';
        }

        if (filter_var($dados['quantidade'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            $erros['quantidade'] = 'A quantidade deve ser um número inteiro maior ou igual a zero.';
        }

        foreach (['preco_unitario' => 'preço unitário', 'preco_pacote' => 'preço do pacote'] as $campo => $rotulo) {
            $valor = Validador::paraDecimal($dados[$campo]);
            if ($valor === null || $valor < 0 || $valor > 99999999.99) {
                $erros[$campo] = "Informe um {$rotulo} válido.";
            }
        }

        return [$dados, $erros];
    }

    private function montar(array $dados): Produto
    {
        return new Produto(
            cod: $dados['cod'],
            nome: $dados['nome'],
            categoria: $dados['categoria'],
            validade: $dados['validade'] !== '' ? $dados['validade'] : null,
            quantidade: (int) $dados['quantidade'],
            precoUnitario: (float) Validador::paraDecimal($dados['preco_unitario']),
            precoPacote: (float) Validador::paraDecimal($dados['preco_pacote']),
        );
    }
}
