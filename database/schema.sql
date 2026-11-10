SET TIME ZONE 'America/Cuiaba';

CREATE TABLE IF NOT EXISTS produtos (
    id             SERIAL PRIMARY KEY,
    cod            VARCHAR(50)    NOT NULL,
    nome           VARCHAR(150)   NOT NULL,
    categoria      VARCHAR(100),
    validade       DATE NULL,
    quantidade     INT            NOT NULL DEFAULT 0,
    preco_unitario NUMERIC(10, 2) NOT NULL DEFAULT 0,
    preco_pacote   NUMERIC(10, 2) NOT NULL DEFAULT 0,
    imagem         VARCHAR(255)
);

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'produtos_cod_unique') THEN
        ALTER TABLE produtos ADD CONSTRAINT produtos_cod_unique UNIQUE (cod);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'produtos_quantidade_check') THEN
        ALTER TABLE produtos ADD CONSTRAINT produtos_quantidade_check CHECK (quantidade >= 0);
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS clientes (
    id              SERIAL PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    cpf             CHAR(11)     NOT NULL UNIQUE,
    email           VARCHAR(150),
    telefone        VARCHAR(20),
    endereco        VARCHAR(255),
    data_nascimento DATE NULL,
    ativo           BOOLEAN      NOT NULL DEFAULT TRUE,
    criado_em       TIMESTAMPTZ  NOT NULL DEFAULT now(),
    atualizado_em   TIMESTAMPTZ  NULL
);

CREATE TABLE IF NOT EXISTS carrinhos (
    id            SERIAL PRIMARY KEY,
    cliente_id    INT NULL REFERENCES clientes (id) ON DELETE SET NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'aberto'
                  CHECK (status IN ('aberto', 'finalizado', 'cancelado')),
    criado_em     TIMESTAMPTZ NOT NULL DEFAULT now(),
    atualizado_em TIMESTAMPTZ NULL
);

CREATE TABLE IF NOT EXISTS carrinho_itens (
    id             SERIAL PRIMARY KEY,
    carrinho_id    INT            NOT NULL REFERENCES carrinhos (id) ON DELETE CASCADE,
    produto_id     INT            NOT NULL REFERENCES produtos (id) ON DELETE CASCADE,
    quantidade     INT            NOT NULL CHECK (quantidade > 0),
    preco_unitario NUMERIC(10, 2) NOT NULL,
    CONSTRAINT carrinho_itens_unico UNIQUE (carrinho_id, produto_id)
);

CREATE TABLE IF NOT EXISTS vendas (
    id              SERIAL PRIMARY KEY,
    carrinho_id     INT NULL REFERENCES carrinhos (id) ON DELETE SET NULL,
    cliente_id      INT NULL REFERENCES clientes (id) ON DELETE SET NULL,
    forma_pagamento VARCHAR(20)    NOT NULL
                    CHECK (forma_pagamento IN ('dinheiro', 'pix', 'debito', 'credito')),
    subtotal        NUMERIC(10, 2) NOT NULL,
    desconto        NUMERIC(10, 2) NOT NULL DEFAULT 0,
    acrescimo       NUMERIC(10, 2) NOT NULL DEFAULT 0,
    total           NUMERIC(10, 2) NOT NULL,
    valor_recebido  NUMERIC(10, 2) NOT NULL DEFAULT 0,
    troco           NUMERIC(10, 2) NOT NULL DEFAULT 0,
    status          VARCHAR(20)    NOT NULL DEFAULT 'concluida'
                    CHECK (status IN ('concluida', 'cancelada')),
    observacao      TEXT NULL,
    criado_em       TIMESTAMPTZ    NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS venda_itens (
    id             SERIAL PRIMARY KEY,
    venda_id       INT            NOT NULL REFERENCES vendas (id) ON DELETE CASCADE,
    produto_id     INT            NOT NULL REFERENCES produtos (id),
    quantidade     INT            NOT NULL CHECK (quantidade > 0),
    preco_unitario NUMERIC(10, 2) NOT NULL,
    subtotal       NUMERIC(10, 2) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_vendas_criado_em      ON vendas (criado_em);
CREATE INDEX IF NOT EXISTS idx_vendas_cliente_id     ON vendas (cliente_id);
CREATE INDEX IF NOT EXISTS idx_venda_itens_produto   ON venda_itens (produto_id);
CREATE INDEX IF NOT EXISTS idx_venda_itens_venda     ON venda_itens (venda_id);
CREATE INDEX IF NOT EXISTS idx_carrinhos_status      ON carrinhos (status);
CREATE INDEX IF NOT EXISTS idx_carrinho_itens_carrinho ON carrinho_itens (carrinho_id);
