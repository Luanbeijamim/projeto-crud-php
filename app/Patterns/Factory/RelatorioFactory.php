<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

use App\Core\RegraNegocioException;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\VendaRepository;

/** Factory Method: decide qual relatório concreto instanciar a partir do tipo. */
final class RelatorioFactory
{
    private const TIPOS = [
        'periodo' => RelatorioPorPeriodo::class,
        'cliente' => RelatorioPorCliente::class,
        'produto' => RelatorioPorProduto::class,
    ];

    public static function criar(string $tipo, ?VendaRepositoryInterface $vendas = null): Relatorio
    {
        $classe = self::TIPOS[$tipo] ?? throw new RegraNegocioException("Tipo de relatório inválido: {$tipo}");
        return new $classe($vendas ?? new VendaRepository());
    }

    public static function tipos(): array
    {
        return [
            'periodo' => 'Por período (lista de vendas)',
            'cliente' => 'Por cliente',
            'produto' => 'Por produto',
        ];
    }

    public static function existe(string $tipo): bool
    {
        return isset(self::TIPOS[$tipo]);
    }
}
