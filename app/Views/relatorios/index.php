<?php
/**
 * @var array $filtros
 * @var \App\Models\Cliente[] $clientes
 * @var \App\Patterns\Factory\Relatorio $relatorio
 * @var array $resumo
 * @var array $maisVendidos
 * @var array $porForma
 * @var \App\Models\Venda[] $vendas
 * @var array $linhas
 * @var \App\Core\Paginacao|null $paginacao
 */
use App\Core\View;

$paramsFiltro = array_filter($filtros, static fn ($v) => $v !== '' && $v !== 0);

$formatar = static function (mixed $valor, string $tipo): string {
    return match ($tipo) {
        'moeda'     => moeda($valor),
        'inteiro'   => number_format((int) $valor, 0, ',', '.'),
        'data'      => data_br((string) $valor, true),
        'cpf'       => $valor ? e(cpf_formatado((string) $valor)) : '—',
        'pagamento' => e(rotulo_pagamento((string) $valor)),
        'status'    => badge_status((string) $valor),
        default     => e($valor),
    };
};

$dadosGrafico = [
    'formas' => [
        'rotulos' => array_map(static fn ($l) => rotulo_pagamento($l['forma_pagamento']), $porForma),
        'valores' => array_map(static fn ($l) => round($l['total'], 2), $porForma),
    ],
    'produtos' => [
        'rotulos' => array_map(static fn ($l) => $l['nome'], $maisVendidos),
        'valores' => array_map(static fn ($l) => $l['quantidade'], $maisVendidos),
    ],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-graph-up"></i> Relatórios de vendas</h1>
    <a href="<?= e(url('relatorios/exportar', $paramsFiltro)) ?>" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
</div>

<form method="get" action="<?= e(url()) ?>" class="card shadow-sm mb-3 d-print-none">
    <input type="hidden" name="rota" value="relatorios">
    <div class="card-body row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small" for="data_inicio">De</label>
            <input type="date" id="data_inicio" name="data_inicio" value="<?= e($filtros['data_inicio']) ?>" class="form-control">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="data_fim">Até</label>
            <input type="date" id="data_fim" name="data_fim" value="<?= e($filtros['data_fim']) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small" for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c->id ?>" <?= selected($c->id, $filtros['cliente_id']) ?>><?= e($c->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="forma_pagamento">Pagamento</label>
            <select id="forma_pagamento" name="forma_pagamento" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($formas as $codigo => $nome): ?>
                    <option value="<?= e($codigo) ?>" <?= selected($codigo, $filtros['forma_pagamento']) ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label small" for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">Todos</option>
                <option value="concluida" <?= selected('concluida', $filtros['status']) ?>>Concluída</option>
                <option value="cancelada" <?= selected('cancelada', $filtros['status']) ?>>Cancelada</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small" for="tipo">Relatório</label>
            <select id="tipo" name="tipo" class="form-select">
                <?php foreach ($tipos as $tipo => $rotulo): ?>
                    <option value="<?= e($tipo) ?>" <?= selected($tipo, $filtros['tipo']) ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= e(url('relatorios')) ?>" class="btn btn-outline-secondary">Limpar</a>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100"><div class="card-body">
            <div class="text-body-secondary small">Vendas no período</div>
            <div class="valor"><?= (int) $resumo['quantidade'] ?></div>
            <small class="text-body-secondary"><?= (int) $resumo['concluidas'] ?> concluída(s) · <?= (int) $resumo['canceladas'] ?> cancelada(s)</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100"><div class="card-body">
            <div class="text-body-secondary small">Faturamento</div>
            <div class="valor text-success"><?= moeda($resumo['faturamento']) ?></div>
            <small class="text-body-secondary">somente concluídas</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100"><div class="card-body">
            <div class="text-body-secondary small">Ticket médio</div>
            <div class="valor"><?= moeda($resumo['ticket_medio']) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador shadow-sm h-100"><div class="card-body">
            <div class="text-body-secondary small">Período</div>
            <div class="fw-semibold"><?= data_br($filtros['data_inicio']) ?> a <?= data_br($filtros['data_fim']) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header">Vendas por forma de pagamento</div>
            <div class="card-body">
                <?php if (!$porForma): ?>
                    <p class="text-body-secondary mb-0">Sem vendas concluídas no período.</p>
                <?php else: ?>
                    <canvas id="grafico-formas" height="220" aria-label="Gráfico de vendas por forma de pagamento" role="img"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <?php foreach ($porForma as $l): ?>
                            <tr><td><?= e(rotulo_pagamento($l['forma_pagamento'])) ?></td><td class="text-end"><?= (int) $l['quantidade'] ?> venda(s)</td><td class="text-end"><?= moeda($l['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header">Produtos mais vendidos (top 5)</div>
            <div class="card-body">
                <?php if (!$maisVendidos): ?>
                    <p class="text-body-secondary mb-0">Sem vendas concluídas no período.</p>
                <?php else: ?>
                    <canvas id="grafico-produtos" height="220" aria-label="Gráfico de produtos mais vendidos" role="img"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><?= e($relatorio->titulo()) ?></div>
    <div class="table-responsive">
        <?php if ($filtros['tipo'] === 'periodo'): ?>
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>Data</th><th>Cliente</th><th>Pagamento</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                <?php if (!$vendas): ?>
                    <tr><td colspan="7" class="text-center text-body-secondary py-4">Nenhuma venda encontrada com esses filtros.</td></tr>
                <?php endif; ?>
                <?php foreach ($vendas as $v): ?>
                    <tr>
                        <td><a href="<?= e(url('relatorios/venda', ['id' => $v->id])) ?>">#<?= (int) $v->id ?></a></td>
                        <td><?= data_br($v->criadoEm, true) ?></td>
                        <td><?= e($v->clienteNome ?? 'Consumidor final') ?></td>
                        <td><?= e(rotulo_pagamento($v->formaPagamento)) ?></td>
                        <td><?= badge_status($v->status) ?></td>
                        <td class="text-end"><?= moeda($v->total) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(url('relatorios/venda', ['id' => $v->id])) ?>" class="btn btn-sm btn-outline-secondary" title="Ver cupom"><i class="bi bi-receipt"></i></a>
                            <?php if ($v->estaConcluida()): ?>
                                <a href="<?= e(url('relatorios/editar', ['id' => $v->id])) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <?= View::partial('partials/confirmar_post', ['acao' => 'relatorios/cancelar', 'id' => $v->id, 'rotulo' => '', 'icone' => 'bi-x-circle', 'classe' => 'btn btn-sm btn-outline-warning', 'mensagem' => 'Cancelar a venda #' . $v->id . '? Os itens voltarão ao estoque.']) ?>
                            <?php else: ?>
                                <?= View::partial('partials/confirmar_post', ['acao' => 'relatorios/excluir', 'id' => $v->id, 'rotulo' => '', 'icone' => 'bi-trash', 'mensagem' => 'Excluir definitivamente a venda cancelada #' . $v->id . '?']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr>
                    <?php foreach ($relatorio->colunas() as [$rotulo, $tipo]): ?>
                        <th class="<?= in_array($tipo, ['moeda', 'inteiro'], true) ? 'text-end' : '' ?>"><?= e($rotulo) ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php if (!$linhas): ?>
                    <tr><td colspan="<?= count($relatorio->colunas()) ?>" class="text-center text-body-secondary py-4">Sem dados para o período.</td></tr>
                <?php endif; ?>
                <?php foreach ($linhas as $linha): ?>
                    <tr>
                        <?php foreach ($relatorio->colunas() as $chave => [, $tipo]): ?>
                            <td class="<?= in_array($tipo, ['moeda', 'inteiro'], true) ? 'text-end' : '' ?>"><?= $formatar($linha[$chave] ?? null, $tipo) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($paginacao !== null): ?>
    <div class="mt-3">
        <?= View::partial('partials/paginacao', ['paginacao' => $paginacao, 'rota' => 'relatorios', 'params' => $paramsFiltro]) ?>
    </div>
<?php endif; ?>

<script type="application/json" id="dados-graficos"><?= json_encode($dadosGrafico, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
