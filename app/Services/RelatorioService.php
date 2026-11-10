<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validador;
use App\Patterns\Factory\Relatorio;
use App\Patterns\Factory\RelatorioFactory;
use App\Patterns\Strategy\FormaPagamentoFactory;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\VendaRepository;

final class RelatorioService
{
    public function __construct(private VendaRepositoryInterface $vendas = new VendaRepository())
    {
    }

    public function normalizarFiltros(array $entrada): array
    {
        $texto = static fn (string $chave): string => is_string($entrada[$chave] ?? null) ? trim($entrada[$chave]) : '';

        $inicio = $texto('data_inicio');
        $fim = $texto('data_fim');
        $inicio = Validador::data($inicio) ? $inicio : date('Y-m-01');
        $fim = Validador::data($fim) ? $fim : date('Y-m-d');
        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $forma = $texto('forma_pagamento');
        $status = $texto('status');
        $tipo = $texto('tipo');

        return [
            'data_inicio'     => $inicio,
            'data_fim'        => $fim,
            'cliente_id'      => max(0, (int) filter_var($texto('cliente_id'), FILTER_VALIDATE_INT)),
            'forma_pagamento' => array_key_exists($forma, FormaPagamentoFactory::rotulos()) ? $forma : '',
            'status'          => in_array($status, ['concluida', 'cancelada'], true) ? $status : '',
            'tipo'            => RelatorioFactory::existe($tipo) ? $tipo : 'periodo',
        ];
    }

    public function painel(array $filtros): array
    {
        return [
            'resumo'       => $this->vendas->resumo($filtros),
            'maisVendidos' => $this->vendas->maisVendidos($filtros, 5),
            'porForma'     => $this->vendas->porFormaPagamento($filtros),
        ];
    }

    public function relatorio(string $tipo): Relatorio
    {
        return RelatorioFactory::criar($tipo, $this->vendas);
    }

    public function exportarCsv(Relatorio $relatorio, array $filtros, $saida): void
    {
        fwrite($saida, "\xEF\xBB\xBF");

        $colunas = $relatorio->colunas();
        fputcsv($saida, array_map(static fn (array $c) => $c[0], $colunas), ';', '"', '');

        foreach ($relatorio->linhas($filtros) as $linha) {
            $valores = [];
            foreach ($colunas as $chave => [, $tipo]) {
                $valores[] = self::formatarCsv($linha[$chave] ?? null, $tipo);
            }
            fputcsv($saida, $valores, ';', '"', '');
        }
    }

    private static function formatarCsv(mixed $valor, string $tipo): string
    {
        return match ($tipo) {
            'moeda'     => number_format((float) $valor, 2, ',', ''),
            'data'      => data_br((string) $valor, true),
            'cpf'       => $valor ? cpf_formatado((string) $valor) : '',
            'pagamento' => rotulo_pagamento((string) $valor),
            'status'    => $valor === 'concluida' ? 'Concluída' : 'Cancelada',
            default     => (string) $valor,
        };
    }
}
