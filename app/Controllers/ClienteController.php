<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Core\Validador;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Repositories\Contracts\ClienteRepositoryInterface;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\VendaRepository;

final class ClienteController extends Controller
{
    private ClienteRepositoryInterface $clientes;
    private VendaRepositoryInterface $vendas;

    public function __construct()
    {
        $this->clientes = new ClienteRepository();
        $this->vendas = new VendaRepository();
    }

    public function index(): void
    {
        $busca = $this->request->texto('busca');
        $paginacao = new Paginacao($this->clientes->contar($busca), $this->pagina(), (int) Config::get('por_pagina'));

        $this->render('clientes/index', [
            'titulo'    => 'Clientes',
            'menuAtivo' => 'clientes',
            'busca'     => $busca,
            'clientes'  => $this->clientes->buscar($busca, $paginacao->porPagina, $paginacao->offset()),
            'paginacao' => $paginacao,
        ]);
    }

    public function ver(): void
    {
        $cliente = $this->clientes->buscarPorId($this->id()) ?? $this->naoEncontrado('Cliente não encontrado.');
        $historico = $this->vendas->listarPorCliente($cliente->id);

        $totalComprado = 0.0;
        foreach ($historico as $venda) {
            if ($venda->status === 'concluida') {
                $totalComprado += $venda->total;
            }
        }

        $this->render('clientes/ver', [
            'titulo'        => $cliente->nome,
            'menuAtivo'     => 'clientes',
            'cliente'       => $cliente,
            'historico'     => $historico,
            'totalComprado' => $totalComprado,
        ]);
    }

    public function novo(): void
    {
        $this->formulario((new Cliente())->paraFormulario(), []);
    }

    public function salvar(): void
    {
        [$dados, $erros] = $this->validar();
        if ($erros) {
            $this->formulario($dados, $erros);
            return;
        }

        $cliente = $this->montar($dados);
        $id = $this->clientes->inserir($cliente);
        Flash::sucesso("Cliente \"{$cliente->nome}\" cadastrado com sucesso.");
        $this->redirecionar('clientes/ver', ['id' => $id]);
    }

    public function editar(): void
    {
        $cliente = $this->clientes->buscarPorId($this->id()) ?? $this->naoEncontrado('Cliente não encontrado.');
        $this->formulario($cliente->paraFormulario(), []);
    }

    public function atualizar(): void
    {
        $atual = $this->clientes->buscarPorId($this->id()) ?? $this->naoEncontrado('Cliente não encontrado.');

        [$dados, $erros] = $this->validar($atual->id);
        if ($erros) {
            $this->formulario($dados, $erros);
            return;
        }

        $cliente = $this->montar($dados);
        $cliente->id = $atual->id;
        $this->clientes->atualizar($cliente);
        Flash::sucesso("Cliente \"{$cliente->nome}\" atualizado com sucesso.");
        $this->redirecionar('clientes/ver', ['id' => $cliente->id]);
    }

    public function excluir(): void
    {
        $cliente = $this->clientes->buscarPorId($this->id()) ?? $this->naoEncontrado('Cliente não encontrado.');

        if ($this->clientes->possuiVendas($cliente->id)) {
            $this->clientes->definirAtivo($cliente->id, false);
            Flash::aviso("O cliente \"{$cliente->nome}\" possui vendas registradas e foi apenas inativado.");
        } else {
            $this->clientes->excluir($cliente->id);
            Flash::sucesso("Cliente \"{$cliente->nome}\" excluído.");
        }

        $this->redirecionar('clientes');
    }

    private function formulario(array $dados, array $erros): void
    {
        $editando = !empty($dados['id']);
        $this->render('clientes/form', [
            'titulo'    => $editando ? 'Editar cliente' : 'Novo cliente',
            'menuAtivo' => 'clientes',
            'dados'     => $dados,
            'erros'     => $erros,
            'editando'  => $editando,
        ], $erros ? 422 : 200);
    }

    private function validar(?int $ignorarId = null): array
    {
        $dados = [
            'id'              => $ignorarId,
            'nome'            => $this->request->texto('nome'),
            'cpf'             => $this->request->texto('cpf'),
            'email'           => $this->request->texto('email'),
            'telefone'        => $this->request->texto('telefone'),
            'endereco'        => $this->request->texto('endereco'),
            'data_nascimento' => $this->request->texto('data_nascimento'),
            'ativo'           => $ignorarId === null || $this->request->texto('ativo') === '1',
        ];
        $erros = [];

        if (Validador::tamanho($dados['nome']) < 3 || Validador::tamanho($dados['nome']) > 150) {
            $erros['nome'] = 'Informe o nome completo (3 a 150 caracteres).';
        }

        if ($dados['cpf'] === '') {
            $erros['cpf'] = 'Informe o CPF.';
        } elseif (!Validador::cpf($dados['cpf'])) {
            $erros['cpf'] = 'CPF inválido (confira os dígitos).';
        } elseif ($this->clientes->existeCpf($dados['cpf'], $ignorarId)) {
            $erros['cpf'] = 'Já existe um cliente com este CPF.';
        }

        if ($dados['email'] !== '' && !Validador::email($dados['email'])) {
            $erros['email'] = 'E-mail inválido.';
        }

        $telefone = Validador::somenteDigitos($dados['telefone']);
        if ($dados['telefone'] !== '' && (strlen($telefone) < 10 || strlen($telefone) > 11)) {
            $erros['telefone'] = 'Telefone deve ter DDD + número (10 ou 11 dígitos).';
        }

        if (Validador::tamanho($dados['endereco']) > 255) {
            $erros['endereco'] = 'Endereço muito longo (máx. 255 caracteres).';
        }

        if ($dados['data_nascimento'] !== '') {
            if (!Validador::data($dados['data_nascimento'])) {
                $erros['data_nascimento'] = 'Data de nascimento inválida.';
            } elseif ($dados['data_nascimento'] > date('Y-m-d')) {
                $erros['data_nascimento'] = 'A data de nascimento não pode ser no futuro.';
            }
        }

        return [$dados, $erros];
    }

    private function montar(array $dados): Cliente
    {
        return new Cliente(
            nome: $dados['nome'],
            cpf: Validador::somenteDigitos($dados['cpf']),
            email: $dados['email'] !== '' ? strtolower($dados['email']) : null,
            telefone: $dados['telefone'] !== '' ? $dados['telefone'] : null,
            endereco: $dados['endereco'] !== '' ? $dados['endereco'] : null,
            dataNascimento: $dados['data_nascimento'] !== '' ? $dados['data_nascimento'] : null,
            ativo: (bool) $dados['ativo'],
        );
    }
}
