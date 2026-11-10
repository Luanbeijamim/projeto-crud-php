SET TIME ZONE 'America/Cuiaba';

INSERT INTO produtos (cod, nome, categoria, validade, quantidade, preco_unitario, preco_pacote, imagem, ativo) VALUES
    ('7891000100103', 'Leite integral 1L',          'Laticínios', CURRENT_DATE + 12,  60,  5.49,  32.90, NULL, TRUE),
    ('7894900011517', 'Refrigerante cola 2L',       'Bebidas',    CURRENT_DATE + 180, 48,  9.99,  56.90, NULL, TRUE),
    ('7896004000015', 'Arroz tipo 1 5kg',           'Mercearia',  CURRENT_DATE + 300, 25, 27.90, 135.00, NULL, TRUE),
    ('7896005800010', 'Feijão carioca 1kg',         'Mercearia',  CURRENT_DATE + 240, 40,  8.79,  84.90, NULL, TRUE),
    ('7891910000197', 'Açúcar refinado 1kg',        'Mercearia',  CURRENT_DATE + 365, 35,  4.99,  47.90, NULL, TRUE),
    ('7896036090244', 'Café torrado e moído 500g',  'Mercearia',  CURRENT_DATE + 150, 4,  18.90,  89.90, NULL, TRUE),
    ('7891024134702', 'Creme dental 90g',           'Higiene',    CURRENT_DATE + 500, 30,  4.59,  25.90, NULL, TRUE),
    ('7891150037007', 'Sabonete 85g',               'Higiene',    CURRENT_DATE + 400, 3,   2.79,  15.90, NULL, TRUE),
    ('7896102000016', 'Pão de forma 500g',          'Padaria',    CURRENT_DATE + 5,   15,  8.49,  48.00, NULL, TRUE),
    ('7891000053508', 'Iogurte morango 170g',       'Laticínios', CURRENT_DATE - 2,   8,   3.29,  18.90, NULL, TRUE)
ON CONFLICT (cod) DO NOTHING;

INSERT INTO clientes (nome, cpf, email, telefone, endereco, data_nascimento, ativo) VALUES
    ('Ana Paula Souza',     '52998224725', 'ana.souza@example.com',   '(65) 99999-1001', 'Rua das Flores, 120 - Centro, Cuiabá/MT',    '1990-04-12', TRUE),
    ('Bruno Henrique Lima', '11144477735', 'bruno.lima@example.com',  '(65) 98888-2002', 'Av. Fernando Corrêa, 450 - Coxipó, Cuiabá/MT', '1985-09-30', TRUE),
    ('Carla Mendes',        '12345678909', 'carla.mendes@example.com','(65) 97777-3003', 'Rua 13 de Junho, 77 - Porto, Cuiabá/MT',      '1998-01-05', TRUE),
    ('Diego Fernandes',     '93541134780', NULL,                      '(65) 3333-4004',  'Rua Barão de Melgaço, 900 - Centro, Cuiabá/MT', NULL,       TRUE),
    ('Elaine Rodrigues',    '28625587887', 'elaine.r@example.com',    NULL,              NULL,                                            '1979-11-21', TRUE)
ON CONFLICT (cpf) DO NOTHING;

DO $$
DECLARE
    r          RECORD;
    v_cliente  INT;
    v_carrinho INT;
    v_venda    INT;
    v_subtotal NUMERIC(10, 2);
    v_desconto NUMERIC(10, 2);
    v_acrescimo NUMERIC(10, 2);
    v_total    NUMERIC(10, 2);
    v_recebido NUMERIC(10, 2);
BEGIN
    IF EXISTS (SELECT 1 FROM vendas) THEN
        RAISE NOTICE 'Tabela vendas já possui dados — vendas de exemplo não foram criadas.';
        RETURN;
    END IF;

    FOR r IN
        SELECT * FROM (VALUES
            ('52998224725', 'pix',      20, '[["7891000100103", 2], ["7894900011517", 1], ["7896102000016", 1]]'::jsonb),
            ('11144477735', 'dinheiro', 15, '[["7896004000015", 1], ["7896005800010", 2]]'::jsonb),
            (NULL,          'debito',   9,  '[["7891024134702", 2], ["7891150037007", 3]]'::jsonb),
            ('12345678909', 'credito',  6,  '[["7896036090244", 2], ["7891910000197", 2], ["7891000100103", 1]]'::jsonb),
            ('52998224725', 'dinheiro', 3,  '[["7894900011517", 2], ["7891000053508", 4]]'::jsonb),
            ('93541134780', 'pix',      1,  '[["7896005800010", 1], ["7896004000015", 1]]'::jsonb),
            (NULL,          'dinheiro', 0,  '[["7896102000016", 2], ["7891000100103", 3]]'::jsonb),
            ('28625587887', 'credito',  0,  '[["7891024134702", 1], ["7894900011517", 1]]'::jsonb)
        ) AS t(cpf, forma, dias_atras, itens)
    LOOP
        SELECT id INTO v_cliente FROM clientes WHERE cpf = r.cpf;

        INSERT INTO carrinhos (cliente_id, status, criado_em, atualizado_em)
        VALUES (v_cliente, 'finalizado', now() - make_interval(days => r.dias_atras), now() - make_interval(days => r.dias_atras))
        RETURNING id INTO v_carrinho;

        INSERT INTO carrinho_itens (carrinho_id, produto_id, quantidade, preco_unitario)
        SELECT v_carrinho, p.id, (e ->> 1)::int, p.preco_unitario
        FROM jsonb_array_elements(r.itens) AS e
        JOIN produtos p ON p.cod = e ->> 0;

        SELECT COALESCE(SUM(quantidade * preco_unitario), 0) INTO v_subtotal
        FROM carrinho_itens WHERE carrinho_id = v_carrinho;

        v_desconto  := CASE r.forma WHEN 'dinheiro' THEN round(v_subtotal * 0.05, 2)
                                    WHEN 'pix'      THEN round(v_subtotal * 0.02, 2)
                                    ELSE 0 END;
        v_acrescimo := CASE r.forma WHEN 'credito' THEN round(v_subtotal * 0.03, 2) ELSE 0 END;
        v_total     := v_subtotal - v_desconto + v_acrescimo;
        v_recebido  := CASE r.forma WHEN 'dinheiro' THEN ceil(v_total / 10) * 10 ELSE v_total END;

        INSERT INTO vendas (carrinho_id, cliente_id, forma_pagamento, subtotal, desconto, acrescimo, total,
                            valor_recebido, troco, status, observacao, criado_em)
        VALUES (v_carrinho, v_cliente, r.forma, v_subtotal, v_desconto, v_acrescimo, v_total,
                v_recebido, v_recebido - v_total, 'concluida', 'Venda de exemplo (seed)',
                now() - make_interval(days => r.dias_atras))
        RETURNING id INTO v_venda;

        INSERT INTO venda_itens (venda_id, produto_id, quantidade, preco_unitario, subtotal)
        SELECT v_venda, produto_id, quantidade, preco_unitario, quantidade * preco_unitario
        FROM carrinho_itens WHERE carrinho_id = v_carrinho;

        UPDATE produtos p SET quantidade = p.quantidade - ci.quantidade
        FROM carrinho_itens ci
        WHERE ci.carrinho_id = v_carrinho AND ci.produto_id = p.id;
    END LOOP;
END $$;
