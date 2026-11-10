<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Core\RegraNegocioException;
use App\Patterns\Factory\RelatorioFactory;
use App\Patterns\Strategy\FormaPagamentoFactory;
use App\Repositories\ClienteRepository;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\VendaRepository;
use App\Services\RelatorioService;
use App\Services\VendaService;

final class RelatorioController extends Controller
{
    private RelatorioService $relatorios;
    private VendaRepositoryInterface $vendas;

    public function __construct()
    {
        $this->vendas = new VendaRepository();
        $this->relatorios = new RelatorioService($this->vendas);
    }

    public function index(): void
    {
        $filtros = $this->relatorios->normalizarFiltros($this->request->consulta());
        $relatorio = $this->relatorios->relatorio($filtros['tipo']);

        $dados = [
            'titulo'    => 'Relatórios de vendas',
            'menuAtivo' => 'relatorios',
            'filtros'   => $filtros,
            'tipos'     => RelatorioFactory::tipos(),
            'formas'    => FormaPagamentoFactory::rotulos(),
            'clientes'  => (new ClienteRepository())->listarTodos(),
            'relatorio' => $relatorio,
            'linhas'    => [],
            'vendas'    => [],
            'paginacao' => null,
            'scripts'   => ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js', asset('assets/js/relatorios.js')],
        ] + $this->relatorios->painel($filtros);

        if ($filtros['tipo'] === 'periodo') {
            $paginacao = new Paginacao($this->vendas->contar($filtros), $this->pagina(), (int) Config::get('por_pagina'));
            $dados['paginacao'] = $paginacao;
            $dados['vendas'] = $this->vendas->listar($filtros, $paginacao->porPagina, $paginacao->offset());
        } else {
            $dados['linhas'] = $relatorio->linhas($filtros);
        }

        $this->render('relatorios/index', $dados);
    }

    public function venda(): void
    {
        $venda = $this->vendas->buscarPorId($this->id()) ?? $this->naoEncontrado('Venda não encontrada.');
        $this->render('relatorios/venda', [
            'titulo'    => "Venda #{$venda->id}",
            'menuAtivo' => 'relatorios',
            'venda'     => $venda,
        ]);
    }

    public function editar(): void
    {
        $venda = $this->vendas->buscarPorId($this->id()) ?? $this->naoEncontrado('Venda não encontrada.');
        if (!$venda->estaConcluida()) {
            Flash::aviso('Vendas canceladas não podem ser editadas.');
            $this->redirecionar('relatorios/venda', ['id' => $venda->id]);
        }

        $this->render('relatorios/editar', [
            'titulo'    => "Editar venda #{$venda->id}",
            'menuAtivo' => 'relatorios',
            'venda'     => $venda,
            'formas'    => FormaPagamentoFactory::todas(),
        ]);
    }

    public function atualizar(): void
    {
        $id = $this->id();
        try {
            VendaService::padrao()->atualizar(
                $id,
                $this->request->texto('forma_pagamento'),
                $this->request->decimal('valor_recebido'),
                $this->request->texto('observacao')
            );
            Flash::sucesso("Venda #{$id} atualizada.");
            $this->redirecionar('relatorios/venda', ['id' => $id]);
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
            $this->redirecionar('relatorios/editar', ['id' => $id]);
        }
    }

    public function cancelar(): void
    {
        $id = $this->id();
        try {
            VendaService::padrao()->cancelar($id, $this->request->texto('motivo'));
            Flash::sucesso("Venda #{$id} cancelada e estoque estornado.");
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
        }
        $this->redirecionar('relatorios/venda', ['id' => $id]);
    }

    public function excluir(): void
    {
        $id = $this->id();
        try {
            VendaService::padrao()->excluir($id);
            Flash::sucesso("Venda #{$id} excluída definitivamente.");
            $this->redirecionar('relatorios');
        } catch (RegraNegocioException $e) {
            Flash::erro($e->getMessage());
            $this->redirecionar('relatorios/venda', ['id' => $id]);
        }
    }

    public function exportar(): void
    {
        $filtros = $this->relatorios->normalizarFiltros($this->request->consulta());
        $relatorio = $this->relatorios->relatorio($filtros['tipo']);

        $arquivo = sprintf('relatorio-%s-%s-a-%s.csv', $filtros['tipo'], $filtros['data_inicio'], $filtros['data_fim']);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $arquivo . '"');
        header('Cache-Control: no-store');

        $saida = fopen('php://output', 'wb');
        $this->relatorios->exportarCsv($relatorio, $filtros, $saida);
        fclose($saida);
    }
}
