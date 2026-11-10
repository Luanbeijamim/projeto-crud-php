<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\RegraNegocioException;
use App\Models\Carrinho;
use App\Models\ItemVenda;
use App\Models\Venda;
use App\Patterns\Observer\BaixaEstoqueObserver;
use App\Patterns\Observer\LogVendaObserver;
use App\Patterns\Observer\VendaObserver;
use App\Patterns\Observer\VendaSubject;
use App\Patterns\Strategy\FormaPagamentoFactory;
use App\Repositories\CarrinhoRepository;
use App\Repositories\Contracts\CarrinhoRepositoryInterface;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use PDO;
use Throwable;

/** Service Layer e Subject do Observer: cada caso de uso de venda roda em uma transação. */
final class VendaService implements VendaSubject
{
    private array $observers = [];

    public function __construct(
        private CarrinhoRepositoryInterface $carrinhos,
        private VendaRepositoryInterface $vendas,
        private ProdutoRepositoryInterface $produtos,
        private ?PDO $conexao = null
    ) {
    }

    public static function padrao(): self
    {
        $produtos = new ProdutoRepository();
        $servico = new self(new CarrinhoRepository(), new VendaRepository(), $produtos);
        $servico->anexar(new BaixaEstoqueObserver($produtos));
        $servico->anexar(new LogVendaObserver());
        return $servico;
    }

    public function anexar(VendaObserver $observer): void
    {
        $this->observers[spl_object_id($observer)] = $observer;
    }

    public function desanexar(VendaObserver $observer): void
    {
        unset($this->observers[spl_object_id($observer)]);
    }

    public function notificar(string $evento, Venda $venda): void
    {
        foreach ($this->observers as $observer) {
            $observer->atualizar($evento, $venda);
        }
    }

    public function finalizar(int $carrinhoId, string $formaPagamento, ?float $valorRecebido, ?string $observacao = null): Venda
    {
        $estrategia = FormaPagamentoFactory::criar($formaPagamento);

        return $this->emTransacao(function () use ($carrinhoId, $estrategia, $valorRecebido, $observacao): Venda {
            $status = $this->carrinhos->bloquear($carrinhoId);
            if ($status === null) {
                throw new RegraNegocioException('Carrinho não encontrado.');
            }
            if ($status !== Carrinho::ABERTO) {
                throw new RegraNegocioException("Este carrinho já está {$status}.");
            }

            $carrinho = $this->carrinhos->buscarPorId($carrinhoId);
            if ($carrinho === null || $carrinho->vazio()) {
                throw new RegraNegocioException('Adicione pelo menos um produto antes de finalizar.');
            }

            foreach ($carrinho->itens as $item) {
                $estoque = $this->produtos->estoqueComBloqueio($item->produtoId) ?? 0;
                if ($item->quantidade > $estoque) {
                    throw new RegraNegocioException("Estoque insuficiente para \"{$item->produtoNome}\": disponível {$estoque}, no carrinho {$item->quantidade}.");
                }
            }

            $pagamento = $estrategia->calcular($carrinho->total(), $valorRecebido);

            $venda = new Venda(
                carrinhoId: $carrinho->id,
                clienteId: $carrinho->clienteId,
                clienteNome: $carrinho->clienteNome,
                formaPagamento: $estrategia->codigo(),
                subtotal: $pagamento->subtotal,
                desconto: $pagamento->desconto,
                acrescimo: $pagamento->acrescimo,
                total: $pagamento->total,
                valorRecebido: $pagamento->valorRecebido,
                troco: $pagamento->troco,
                observacao: $observacao !== '' ? $observacao : null,
                itens: array_map([ItemVenda::class, 'deItemCarrinho'], $carrinho->itens),
            );

            $venda->id = $this->vendas->inserir($venda);
            foreach ($venda->itens as $item) {
                $item->vendaId = $venda->id;
                $this->vendas->inserirItem($venda->id, $item);
            }

            $this->notificar(VendaObserver::FINALIZADA, $venda);
            $this->carrinhos->definirStatus($carrinho->id, Carrinho::FINALIZADO);

            return $venda;
        });
    }

    public function cancelar(int $vendaId, string $motivo = ''): Venda
    {
        return $this->emTransacao(function () use ($vendaId, $motivo): Venda {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if (!$venda->estaConcluida()) {
                throw new RegraNegocioException("A venda #{$vendaId} já está cancelada.");
            }

            $observacao = trim(($venda->observacao ?? '') . "\nCancelada em " . date('d/m/Y H:i') . ($motivo !== '' ? ": {$motivo}" : '.'));
            $this->vendas->definirStatus($venda->id, Venda::CANCELADA, $observacao);
            $venda->status = Venda::CANCELADA;
            $venda->observacao = $observacao;

            $this->notificar(VendaObserver::CANCELADA, $venda);
            return $venda;
        });
    }

    public function atualizar(int $vendaId, string $formaPagamento, ?float $valorRecebido, ?string $observacao): Venda
    {
        $estrategia = FormaPagamentoFactory::criar($formaPagamento);

        return $this->emTransacao(function () use ($vendaId, $estrategia, $valorRecebido, $observacao): Venda {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if (!$venda->estaConcluida()) {
                throw new RegraNegocioException('Vendas canceladas não podem ser editadas.');
            }

            if ($estrategia->aceitaTroco() && $valorRecebido === null) {
                $valorRecebido = $estrategia->calcular($venda->subtotal, PHP_FLOAT_MAX)->total;
            }
            $pagamento = $estrategia->calcular($venda->subtotal, $valorRecebido);

            $venda->formaPagamento = $estrategia->codigo();
            $venda->desconto = $pagamento->desconto;
            $venda->acrescimo = $pagamento->acrescimo;
            $venda->total = $pagamento->total;
            $venda->valorRecebido = $pagamento->valorRecebido;
            $venda->troco = $pagamento->troco;
            $venda->observacao = ($observacao ?? '') !== '' ? $observacao : null;

            $this->vendas->atualizarPagamento($venda);
            return $venda;
        });
    }

    public function excluir(int $vendaId): void
    {
        $this->emTransacao(function () use ($vendaId): void {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if ($venda->estaConcluida()) {
                throw new RegraNegocioException('Somente vendas canceladas podem ser excluídas. Cancele a venda primeiro.');
            }
            $this->vendas->excluir($venda->id);
        });
    }

    private function emTransacao(callable $operacao): mixed
    {
        $pdo = $this->conexao ??= Database::getInstance();
        $pdo->beginTransaction();
        try {
            $resultado = $operacao();
            $pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $erro;
        }
    }
}
