# projeto-crud-php-faculdade — PDV em PHP com MVC

Este repositório é a minha versão do PDV (Ponto de Venda) que usamos em aula. O sistema original era um conjunto de arquivos PHP que faziam tudo ao mesmo tempo: montavam o HTML, rodavam o SQL e aplicavam as regras. Reorganizei tudo em **MVC**, apliquei alguns **padrões de projeto** onde eles resolviam problemas de verdade e criei os três CRUDs pedidos: **clientes**, **carrinho de vendas** e **relatórios de vendas**.

Não usei framework. A ideia foi justamente deixar à mostra cada peça que o Laravel, por exemplo, esconde.

**Stack:** PHP 8.1+ · PDO · PostgreSQL (Neon) · Bootstrap 5.3 · Chart.js 4

> **Atalho para a correção**
> - MVC → [Organização do código](#organização-do-código-mvc)
> - Design Patterns → [Padrões que usei](#padrões-que-usei)
> - CRUDs → [O que o sistema faz](#o-que-o-sistema-faz)
> - POP → [POP de implantação](#pop-de-implantação)

---

## O que o sistema faz

| Tela | Operações |
|---|---|
| **Início** | Vendas e faturamento do dia, faturamento do mês, carrinhos abertos, produtos com estoque ≤ 5 e produtos vencidos ou vencendo em até 30 dias |
| **Produtos** | Listar (busca por nome **ou** código, paginação), cadastrar e editar com foto, excluir. Se o produto já foi vendido, não pode ser apagado: só inativado |
| **Clientes** *(CRUD 1)* | Listar (busca por nome ou CPF), cadastrar, ver ficha com histórico de compras, editar, excluir. Cliente com compras é inativado em vez de apagado. O CPF é validado pelos dígitos verificadores |
| **PDV** | Busca rápida que manda o produto direto para o carrinho em andamento |
| **Carrinho** *(CRUD 2)* | Abrir (com ou sem cliente), adicionar item pela busca ou digitando o código, mudar quantidade, remover item, esvaziar, cancelar, excluir, retomar carrinhos abertos e **finalizar** a venda |
| **Relatórios** *(CRUD 3)* | Filtros (período, cliente, pagamento, status), totais e ticket médio, gráficos, relatório por período/cliente/produto, cupom para imprimir, editar pagamento/observação, cancelar (devolve o estoque), excluir venda cancelada e exportar CSV para o Excel |

Formas de pagamento e suas regras:

| Forma | Regra |
|---|---|
| Dinheiro | 5% de desconto; exige valor recebido ≥ total e calcula o troco |
| PIX | 2% de desconto |
| Débito | sem ajuste |
| Crédito | 3% de acréscimo |

---

## Rodando em 3 minutos

```bash
git clone https://github.com/RobertGean/projeto-crud-php-faculdade.git
cd projeto-crud-php-faculdade
cp .env.example .env              # coloque a connection string do Neon em SRVDB
php database/migrate.php --seed   # cria as tabelas e insere dados de teste
php -S localhost:8000 -t public
```

Depois é só abrir <http://localhost:8000>. O passo a passo completo, incluindo a hospedagem, está no [POP](#pop-de-implantação).

---

## Organização do código (MVC)

O MVC é a **arquitetura** do projeto, ou seja, a regra de onde cada coisa mora:

- **Model** (`app/Models` + `app/Repositories`): as entidades (`Produto`, `Cliente`, `Carrinho`, `ItemCarrinho`, `Venda`, `ItemVenda`) e os repositórios, que são **os únicos arquivos com SQL**, sempre com prepared statements.
- **View** (`app/Views`): só HTML. Toda variável sai pela função `e()`, que escapa o conteúdo, e nenhuma view consulta o banco.
- **Controller** (`app/Controllers`): lê a requisição, valida o que veio do formulário, chama o model ou o service e escolhe a view. Não tem SQL nem HTML.

Para as regras que envolvem várias tabelas de uma vez (fechar venda, cancelar venda) criei uma camada de **services** (`app/Services`). A infraestrutura (roteador, sessão, CSRF, log, erros) fica em `app/Core`.

### Caminho de uma requisição

```mermaid
flowchart TD
    A([Navegador]) --> B[public/index.php<br/>Front Controller]
    B --> C[Router<br/>confere CSRF nos POST]
    C --> D[Controller]
    D --> E[Service]
    D --> F[Repository]
    E --> F
    F --> G[(PostgreSQL / Neon)]
    D --> H[View + layout]
    H --> A
```

As rotas aceitam dois formatos, porque nem toda hospedagem compartilhada tem `mod_rewrite`:

- `index.php?rota=clientes/editar&id=5`: o padrão, funciona em qualquer servidor.
- `/clientes/editar/5`: ativado com `APP_URL_AMIGAVEL=true` no `.env`.

Tudo que altera dados (salvar, excluir, finalizar, cancelar) só aceita **POST**.

### Pastas

```text
public/            ← única pasta acessível pelo navegador
  index.php        ← entrada de todas as requisições
  assets/          ← CSS e JS
  uploads/produtos ← fotos enviadas
app/
  Core/            ← Router, Request, View, Database, Csrf, Flash, Session, Logger, ErrorHandler...
  Controllers/     ← Home, Produto, Cliente, Pdv, Carrinho, Relatorio
  Models/          ← entidades
  Repositories/    ← SQL (interfaces em Contracts/)
  Services/        ← VendaService, CarrinhoService, RelatorioService, UploadService
  Patterns/        ← Strategy/, Observer/, Factory/
  Views/           ← layouts/, partials/ e uma pasta por tela
config/            ← config.php (lê o .env) e rotas.php
database/          ← schema.sql, seed.sql, migrate.php
storage/logs/      ← app.log e vendas.log
legado/            ← código original, só para comparação
```

### Exemplo: o que acontece ao clicar em "Finalizar venda"

1. O formulário envia um POST para `carrinho/finalizar` com a forma de pagamento, o valor recebido e o token CSRF.
2. O `Router` confere o token e chama `CarrinhoController::finalizar()`.
3. O controller repassa os dados para `VendaService::finalizar()`, sem fazer nenhuma conta.
4. O service abre uma transação, trava o carrinho e cada produto com `SELECT ... FOR UPDATE` e confere o estoque.
5. A estratégia da forma de pagamento calcula desconto, acréscimo e troco.
6. A venda e os itens são gravados; os observers baixam o estoque e escrevem no `vendas.log`.
7. `COMMIT`. Se qualquer passo falhar, `ROLLBACK`, e o usuário vê a mensagem de erro no carrinho.
8. O navegador é redirecionado para o cupom da venda.

```php
// CarrinhoController.php — o controller só coordena
try {
    $venda = VendaService::padrao()->finalizar($id, $forma, $recebido, $observacao);
} catch (RegraNegocioException $e) {
    Flash::erro($e->getMessage());
    $this->redirecionar('carrinho/ver', ['id' => $id]);
}
$this->redirecionar('relatorios/venda', ['id' => $venda->id]);
```

---

## Padrões que usei

Um ponto que ficou claro em aula: **design pattern não é arquitetura**. A arquitetura aqui é o MVC, a divisão geral do sistema. Os padrões são soluções pequenas para problemas específicos que aparecem dentro dessa divisão. Por isso só usei os que resolviam algo concreto no PDV, para não cair em overengineering.

Também foi por isso que não usei Laravel: em um framework, metade dessas peças vem pronta e fica invisível. Mesmo sem ele, a estrutura é a mesma dos frameworks MVC (entrada única, arquivo de rotas, controllers, views com layout).

| # | Padrão | Tipo | Arquivo(s) |
|---|---|---|---|
| 1 | Singleton | Criacional | `app/Core/Database.php` |
| 2 | Factory Method | Criacional | `app/Patterns/Factory/` |
| 3 | Repository | Estrutural / acesso a dados | `app/Repositories/` |
| 4 | Front Controller | Estrutural (arquitetural) | `public/index.php`, `app/Core/Router.php` |
| 5 | Strategy | Comportamental | `app/Patterns/Strategy/` |
| 6 | Observer | Comportamental | `app/Patterns/Observer/`, `app/Services/VendaService.php` |
| + | Service Layer | Camada de aplicação | `app/Services/` |

### 1. Singleton

**O que é:** garante que exista uma única instância de uma classe, acessível de qualquer lugar.

**Por que precisei:** no código antigo, todo arquivo fazia `include 'db.php'` e abria uma conexão nova. No Neon, cada conexão custa um handshake TLS e entra no limite do plano. Além disso, a transação da venda só funciona se todos os repositórios usarem **a mesma** conexão.

```php
final class Database
{
    private static ?PDO $instancia = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            [$dsn, $usuario, $senha] = self::montarDsn((string) Config::get('db_url'), (string) Config::get('timezone'));
            self::$instancia = new PDO($dsn, $usuario, $senha, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, /* ... */]);
        }
        return self::$instancia;
    }
}
```

### 2. Factory Method

**O que é:** um método fica responsável por decidir qual classe concreta instanciar, e quem chama só conhece a interface.

**Por que precisei:** a tela de relatórios e a exportação para CSV geram três relatórios diferentes (por período, por cliente, por produto), escolhidos pelo parâmetro `tipo`. Sem a fábrica, o mesmo `switch` estaria repetido nos dois lugares. Para criar um relatório novo, basta uma classe e uma linha na fábrica.

```php
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
}
```

Na prática, o CSV não sabe qual relatório está exportando:

```php
foreach ($relatorio->linhas($filtros) as $linha) { /* ... */ }
```

### 3. Repository

**O que é:** uma classe que faz a ponte entre o domínio e o banco, devolvendo objetos em vez de linhas de tabela e escondendo o SQL.

**Por que precisei:** o legado tinha SQL espalhado por todas as páginas, alguns montados concatenando `$_POST` (porta aberta para SQL injection), e a busca do PDV usava a coluna `codigo`, que não existe (a certa é `cod`). Hoje cada tabela tem uma interface em `Contracts/` e uma implementação PostgreSQL, e é ali que mora todo o SQL.

```php
public function baixarEstoque(int $id, int $quantidade): void
{
    $stmt = $this->db()->prepare(
        'UPDATE produtos SET quantidade = quantidade - :qtd WHERE id = :id AND quantidade >= :minimo'
    );
    $stmt->execute([':qtd' => $quantidade, ':minimo' => $quantidade, ':id' => $id]);

    if ($stmt->rowCount() === 0) {
        throw new RegraNegocioException("Estoque insuficiente para o produto #{$id}.");
    }
}
```

### 4. Front Controller

**O que é:** um único ponto de entrada recebe todas as requisições e decide para onde mandar cada uma.

**Por que precisei:** antes, cada `.php` era aberto direto pelo navegador e ninguém conferia token CSRF nem tratava erro de forma igual. Agora `public/index.php` carrega o ambiente, abre a sessão e entrega a requisição ao `Router`, que verifica o CSRF e chama o controller certo. E só a pasta `public/` fica exposta na web.

```php
require dirname(__DIR__) . '/app/bootstrap.php';
Session::iniciar();
$router = new Router();
require BASE_PATH . '/config/rotas.php';
$router->despachar(Request::capturar());
```

### 5. Strategy

**O que é:** cada variação de um algoritmo vira uma classe com a mesma interface, e elas podem ser trocadas entre si.

**Por que precisei:** cada forma de pagamento tem a sua regra (veja a tabela no início). Com `if/else`, toda forma nova obrigaria a mexer na finalização da venda. Com Strategy, o `VendaService` só chama `calcular()`.

```php
$pagamento = FormaPagamentoFactory::criar($forma)->calcular($carrinho->total(), $valorRecebido);
```

```mermaid
classDiagram
    direction LR
    class FormaPagamentoStrategy {
        <<interface>>
        +codigo() string
        +nome() string
        +percentualAjuste() float
        +aceitaTroco() bool
        +calcular(subtotal, valorRecebido) ResultadoPagamento
    }
    class PagamentoBase {
        <<abstract>>
        +calcular(subtotal, valorRecebido) ResultadoPagamento
        #fecharPagamento() ResultadoPagamento
    }
    FormaPagamentoStrategy <|.. PagamentoBase
    PagamentoBase <|-- PagamentoDinheiro
    PagamentoBase <|-- PagamentoPix
    PagamentoBase <|-- PagamentoCartaoDebito
    PagamentoBase <|-- PagamentoCartaoCredito
    FormaPagamentoFactory ..> FormaPagamentoStrategy : cria
    VendaService --> FormaPagamentoStrategy : usa
    FormaPagamentoStrategy ..> ResultadoPagamento : devolve
```

### 6. Observer

**O que é:** quando um objeto muda de estado, ele avisa uma lista de interessados, sem conhecer cada um.

**Por que precisei:** finalizar ou cancelar uma venda tem consequências: mexer no estoque e registrar a venda num log. Se tudo isso ficasse dentro do `VendaService`, ele cresceria a cada nova necessidade. Com Observer, o service só anuncia `venda.finalizada` ou `venda.cancelada`:

- `BaixaEstoqueObserver` tira do estoque na finalização e devolve no cancelamento. Ele roda **dentro da mesma transação** da venda: se a baixa falhar, nada é gravado.
- `LogVendaObserver` escreve uma linha em `storage/logs/vendas.log`.

```php
$servico->anexar(new BaixaEstoqueObserver($produtos));
$servico->anexar(new LogVendaObserver());
// ...
$this->notificar(VendaObserver::FINALIZADA, $venda);
```

```mermaid
classDiagram
    direction LR
    class VendaSubject {
        <<interface>>
        +anexar(VendaObserver)
        +desanexar(VendaObserver)
        +notificar(evento, Venda)
    }
    class VendaObserver {
        <<interface>>
        +atualizar(evento, Venda)
    }
    VendaSubject <|.. VendaService
    VendaObserver <|.. BaixaEstoqueObserver
    VendaObserver <|.. LogVendaObserver
    VendaService o--> VendaObserver : avisa
    BaixaEstoqueObserver --> ProdutoRepositoryInterface : baixa/estorna
```

### Extra: Service Layer

O `VendaService` concentra os casos de uso de venda (finalizar, cancelar, editar, excluir), cada um dentro de `beginTransaction()` / `commit()` / `rollBack()`. Com o `FOR UPDATE` no estoque e o `CHECK (quantidade >= 0)` no banco, **duas vendas ao mesmo tempo do último item não deixam o estoque negativo**: a segunda recebe "Estoque insuficiente".

---

## Banco de dados

```mermaid
erDiagram
    clientes ||--o{ carrinhos : abre
    clientes ||--o{ vendas : compra
    carrinhos ||--|{ carrinho_itens : tem
    produtos ||--o{ carrinho_itens : ""
    carrinhos ||--o| vendas : vira
    vendas ||--|{ venda_itens : tem
    produtos ||--o{ venda_itens : ""

    produtos {
        serial id PK
        varchar cod UK
        varchar nome
        varchar categoria
        date validade
        int quantidade "CHECK >= 0"
        numeric preco_unitario
        numeric preco_pacote
        varchar imagem
        boolean ativo
    }
    clientes {
        serial id PK
        varchar nome
        char cpf UK
        varchar email
        varchar telefone
        varchar endereco
        date data_nascimento
        boolean ativo
        timestamptz criado_em
        timestamptz atualizado_em
    }
    carrinhos {
        serial id PK
        int cliente_id FK
        varchar status "aberto, finalizado, cancelado"
        timestamptz criado_em
        timestamptz atualizado_em
    }
    carrinho_itens {
        serial id PK
        int carrinho_id FK
        int produto_id FK
        int quantidade
        numeric preco_unitario
    }
    vendas {
        serial id PK
        int carrinho_id FK
        int cliente_id FK
        varchar forma_pagamento
        numeric subtotal
        numeric desconto
        numeric acrescimo
        numeric total
        numeric valor_recebido
        numeric troco
        varchar status "concluida, cancelada"
        text observacao
        timestamptz criado_em
    }
    venda_itens {
        serial id PK
        int venda_id FK
        int produto_id FK
        int quantidade
        numeric preco_unitario
        numeric subtotal
    }
```

A tabela `produtos` já existia no Neon. Por isso o `schema.sql` pode rodar quantas vezes for preciso: ele só cria o que falta (`IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, constraints testadas antes). O `seed.sql` insere 10 produtos, 5 clientes e 8 vendas, e não duplica nada se rodar de novo.

---

## O que mudou em relação ao legado

| Antes | Agora |
|---|---|
| HTML, SQL e regra no mesmo arquivo | Camadas separadas (MVC + services + repositories) |
| Cada `.php` aberto direto pelo navegador | Uma entrada só (`public/index.php`); o resto do projeto fica fora da web |
| Credenciais do banco escritas no código | Conexão só no `.env`, que não vai para o Git |
| `INSERT` concatenando `$_POST` | Prepared statements em 100% das consultas |
| `editar_produto.php` sem `?id` gerava dezenas de *Undefined variable $produto* no `error_log` | Registro inexistente responde com página 404; as views sempre recebem as variáveis; qualquer warning vira exceção e vai para o log |
| Busca no PDV pela coluna errada (`codigo`) | Busca por nome ou por `cod` |
| "Vender" só diminuía o número e aceitava estoque negativo | Carrinho → venda registrada com itens e pagamento, em transação e sem estoque negativo |
| Upload sem nenhuma checagem | Tipo conferido pelo conteúdo do arquivo, limite de 2 MB, nome aleatório e tratamento de falha |
| Erros do banco aparecendo na tela | Mensagem amigável na tela, detalhe no `storage/logs/app.log` |
| Sem CSRF; exclusão por link | Token CSRF em todos os POST; exclusão só por POST e com confirmação |
| `echo "Produto atualizado!"` antes do `<html>` | Mensagens flash exibidas pelo layout |

---

## POP de implantação

**POP-01 — Implantação do PDV MVC** · revisão 1.0

| | |
|---|---|
| **Objetivo** | Deixar registrado, passo a passo, como colocar o sistema no ar (local e hospedagem) de forma segura e repetível |
| **Escopo** | Máquina de desenvolvimento, banco no Neon, hospedagem cPanel com Apache e publicação no GitHub |
| **Responsável** | [Seu nome] |
| **Quando aplicar** | Na primeira instalação e a cada nova versão |

**Pré-requisitos:** conta no Neon e no GitHub; acesso ao cPanel (só para a etapa 4).

**Materiais e ferramentas:**

- PHP 8.1 ou superior, com as extensões `pdo_pgsql`, `fileinfo` e `mbstring`;
- Git;
- navegador;
- opcionais: Composer, `psql` e FileZilla.

### Etapa 1 — Preparar a máquina

1. Instale o PHP e o Git:
   - **Linux (Debian/Ubuntu):** `sudo apt install php-cli php-pgsql php-mbstring git`
   - **Windows:** baixe o PHP em <https://windows.php.net/download> e, no `php.ini`, tire o `;` das linhas `extension=pdo_pgsql`, `extension=pgsql`, `extension=fileinfo` e `extension=mbstring`.
   - **macOS:** `brew install php`
2. Confira: `php -v` deve mostrar 8.1 ou mais, e `php -m` precisa listar `pdo_pgsql`.
3. Clone o projeto e crie o `.env`:
   ```bash
   git clone https://github.com/RobertGean/projeto-crud-php-faculdade.git
   cd projeto-crud-php-faculdade
   cp .env.example .env
   ```
4. *(Opcional)* `composer dump-autoload`. Sem Composer, o projeto usa o autoload próprio do `app/bootstrap.php`.

### Etapa 2 — Criar o banco no Neon

5. No Neon, crie um projeto: PostgreSQL 16, região **São Paulo (sa-east-1)**.
6. Em **Connect**, deixe marcado **Pooled connection** e copie a connection string (o host tem `-pooler`).
7. No `.env`, preencha:
   ```dotenv
   SRVDB="postgresql://usuario:senha@ep-xxxx-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require"
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   ```
8. Crie as tabelas e os dados de teste, de **um** destes jeitos:
   - `php database/migrate.php --seed`;
   - no **SQL Editor** do Neon, rodando primeiro `database/schema.sql` e depois `database/seed.sql`;
   - `psql "<connection string>" -f database/schema.sql`, e o mesmo para o `seed.sql`.
9. Confira com `SELECT COUNT(*) FROM vendas;`. O resultado deve ser 8 ou mais.

### Etapa 3 — Rodar e testar localmente

10. Suba o servidor: `php -S localhost:8000 -t public`.
11. Abra <http://localhost:8000> e passe pelo roteiro:

| # | Ação | Esperado |
|---|---|---|
| 1 | Abrir o Início | Cards e alertas aparecem |
| 2 | Buscar produto pelo nome e pelo código | Encontra nos dois casos |
| 3 | Cadastrar produto com um `.txt` renomeado para `.jpg` | Recusa a imagem e mantém os campos |
| 4 | Abrir `index.php?rota=produtos/editar` sem id | Página "Produto não encontrado", sem warning |
| 5 | Tentar excluir produto já vendido | Bloqueia e oferece "Inativar" |
| 6 | Cadastrar cliente com CPF `111.111.111-11` | "CPF inválido", campos preservados |
| 7 | Cadastrar, abrir e editar um cliente válido | Ficha com histórico de compras |
| 8 | Excluir cliente que tem compras | Fica inativo, não some |
| 9 | No PDV, adicionar um produto | Carrinho criado com o item |
| 10 | Pedir mais unidades do que o estoque | "Estoque insuficiente" |
| 11 | Alterar quantidade, remover item, esvaziar | Totais atualizam |
| 12 | Finalizar em dinheiro com valor menor que o total | "Valor recebido insuficiente" |
| 13 | Finalizar em dinheiro com valor maior | Cupom com 5% de desconto e troco; estoque baixa; linha nova no `vendas.log` |
| 14 | Filtrar relatórios e trocar o tipo | Totais, gráficos e tabela mudam |
| 15 | Exportar CSV e abrir no Excel | Acentos e colunas corretos |
| 16 | Editar a forma de pagamento de uma venda | Total recalculado |
| 17 | Cancelar a venda | Status "Cancelada" e estoque devolvido |
| 18 | Excluir a venda cancelada | Some da lista |

### Etapa 4 — Publicar na hospedagem (cPanel)

12. Envie o projeto **sem** `.env`, `vendor/` e `.git/`, de um destes jeitos:
    - **Git Version Control** do cPanel;
    - FTP;
    - **Gerenciador de Arquivos**, enviando um `.zip` e usando *Extract*.
13. Aponte o domínio para a pasta `public/`, em *Domínios → Raiz do documento*. Se o painel não deixar mudar isso, coloque o projeto dentro de `public_html/`: o `.htaccess` da raiz redireciona tudo para `public/` e bloqueia o `.env`.
14. Crie o `.env` no servidor, com `APP_DEBUG=false`. Ative *Mostrar arquivos ocultos* para enxergá-lo.
15. Ajuste as permissões:
    - `public/uploads/produtos` e `storage/logs`: **755** (ou **775**, se der erro de gravação);
    - demais arquivos: **644**;
    - `.env`: **600**.
16. Em *Select PHP Version* (ou *MultiPHP Manager*), escolha 8.1+ e marque as extensões `pdo_pgsql`, `pgsql`, `fileinfo` e `mbstring`.
17. Se a hospedagem bloquear a porta 5432, peça ao suporte a liberação para `*.neon.tech`.
18. Repita os itens 1, 2, 9, 13 e 14 do roteiro no endereço publicado.

### Etapa 5 — Publicar no GitHub

19. Confira se o `.gitignore` tem `.env`, `vendor/`, `storage/logs/*` e `public/uploads/produtos/*`.
20. Envie o código:
    ```bash
    git status    # o .env não pode aparecer
    git remote add origin https://github.com/RobertGean/projeto-crud-php-faculdade.git
    git push -u origin main
    ```
    No push, o usuário é o do GitHub e a senha é um **Personal Access Token** com escopo `repo`.
21. Rode `git ls-files | grep -x .env`, que não pode retornar nada.
22. Se uma senha for enviada sem querer, **troque-a no Neon na hora**: o histórico do Git guarda o arquivo mesmo depois de apagado.

### Problemas comuns

| Problema | Por quê | Como resolver |
|---|---|---|
| `Endpoint ID is not specified` | A libpq do servidor é antiga e não manda SNI, então o Neon não sabe qual banco é | O `Database::montarDsn()` já envia `options='endpoint=<id>'` para hosts `*.neon.tech`. Confira a `SRVDB`; se continuar, teste o endpoint sem `-pooler` ou a conexão direta |
| `could not find driver` | `pdo_pgsql` desligado | Instale ou ative a extensão (no cPanel: *Select PHP Version → Extensions*) |
| Erro de SSL | `sslmode` faltando ou libpq sem SSL | Mantenha `?sslmode=require`; se for a libpq, só o suporte da hospedagem resolve |
| `password authentication failed` | Senha errada ou com caractere especial | Gere outra no Neon; `@` vira `%40` e `#` vira `%23` na URL |
| Horário errado | Fuso diferente | PHP e banco já usam `America/Cuiaba`; não troque para UTC |
| Foto não salva | Pasta sem permissão | `chmod 775 public/uploads/produtos`; o detalhe aparece no `storage/logs/app.log` |
| Foto grande recusada pelo servidor | `upload_max_filesize` baixo | Aumente para `4M` (e `post_max_size` para `8M`) no *MultiPHP INI Editor* |
| `Undefined variable` (era o erro do legado) | Página aberta sem o registro | No novo código, o controller responde 404 e todo warning vira exceção registrada no log |
| Tela branca ou erro 500 | Erro de PHP em produção | Veja o `storage/logs/app.log`; para ver na tela, `APP_DEBUG=true` (só em desenvolvimento) |
| URLs amigáveis dando 404 | Sem `mod_rewrite` | Use `APP_URL_AMIGAVEL=false`, que é o padrão |
| `schema.sql` reclama de `produtos_cod_unique` | Códigos repetidos vindos do legado | Ache-os com `SELECT cod FROM produtos GROUP BY cod HAVING COUNT(*) > 1;`, corrija e rode de novo |
| `schema.sql` reclama de `produtos_quantidade_check` | Estoque negativo vindo do legado | `UPDATE produtos SET quantidade = 0 WHERE quantidade < 0;` e rode de novo |
| Fotos antigas não aparecem | O legado guardava em `imagens/` | Copie os arquivos para `public/uploads/produtos/` |

### Critérios de aceite

- [ ] `pdo_pgsql` ativo na máquina e no servidor
- [ ] `schema.sql` e `seed.sql` executados sem erro
- [ ] Roteiro da etapa 3 aprovado por completo
- [ ] Uma venda finalizada com sucesso na hospedagem
- [ ] `/.env`, `/app/` e `/legado/` inacessíveis pelo navegador
- [ ] `APP_DEBUG=false` em produção
- [ ] `.env` fora do GitHub

### Histórico

| Revisão | Data | Responsável | Mudança |
|---|---|---|---|
| 1.0 | 09/11/2026 | [Seu nome] | Primeira versão |

---

## Telas

Os prints ficam em `docs/prints/`.

| Tela | Print |
|---|---|
| Início | ![Início](docs/prints/01-dashboard.png) |
| Produtos | ![Produtos](docs/prints/02-produtos.png) |
| Clientes | ![Clientes](docs/prints/03-clientes.png) |
| Carrinho | ![Carrinho](docs/prints/04-carrinho.png) |
| Cupom | ![Cupom](docs/prints/05-cupom.png) |
| Relatórios | ![Relatórios](docs/prints/06-relatorios.png) |

Roteiro de uso:

1. **Cadastre** produtos e clientes, ou use os dados do seed.
2. Abra o **PDV**, busque o produto e clique em **Adicionar**.
3. No **carrinho**, ajuste as quantidades e escolha o cliente e a forma de pagamento. A prévia mostra o total e o troco.
4. Clique em **Finalizar venda** e imprima o cupom.
5. Em **Relatórios**, acompanhe o faturamento, exporte o CSV e cancele ou corrija vendas.

---

## Sobre

Trabalho da disciplina **[Disciplina]**, com o professor **[Professor]**, em [Instituição], [Semestre/Ano].

Desenvolvido por **[Seu nome]**.
