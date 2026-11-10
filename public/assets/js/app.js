document.addEventListener('submit', function (evento) {
    var form = evento.target;
    var mensagem = form.getAttribute('data-confirmar');
    if (mensagem && !window.confirm(mensagem)) {
        evento.preventDefault();
    }
});

// Prévia apenas visual: o cálculo oficial é feito no servidor pelas classes Strategy.
(function () {
    var form = document.getElementById('form-finalizar');
    if (!form) {
        return;
    }

    var subtotal = parseFloat(form.getAttribute('data-subtotal')) || 0;
    var select = form.querySelector('[name="forma_pagamento"]');
    var recebido = form.querySelector('[name="valor_recebido"]');
    var grupoRecebido = document.getElementById('grupo-valor-recebido');
    var saida = document.getElementById('previa-pagamento');
    var moeda = new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'});

    function atualizar() {
        var opcao = select.options[select.selectedIndex];
        var percentual = parseFloat(opcao.getAttribute('data-percentual')) || 0;
        var troco = opcao.getAttribute('data-troco') === '1';
        var ajuste = Math.round(subtotal * Math.abs(percentual)) / 100;
        var total = percentual < 0 ? subtotal - ajuste : subtotal + ajuste;

        grupoRecebido.style.display = troco ? '' : 'none';
        recebido.required = troco;

        var texto = 'Subtotal ' + moeda.format(subtotal);
        if (percentual < 0) {
            texto += ' · Desconto ' + moeda.format(ajuste);
        } else if (percentual > 0) {
            texto += ' · Acréscimo ' + moeda.format(ajuste);
        }
        texto += ' · <strong>Total ' + moeda.format(total) + '</strong>';

        if (troco) {
            var valor = parseFloat(String(recebido.value).replace(',', '.')) || 0;
            texto += valor >= total
                ? ' · Troco ' + moeda.format(valor - total)
                : ' · <span class="text-danger">Faltam ' + moeda.format(total - valor) + '</span>';
        }
        saida.innerHTML = texto;
    }

    select.addEventListener('change', atualizar);
    recebido.addEventListener('input', atualizar);
    atualizar();
})();
