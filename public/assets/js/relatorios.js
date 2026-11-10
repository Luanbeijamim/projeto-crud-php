(function () {
    var elemento = document.getElementById('dados-graficos');
    if (!elemento || typeof Chart === 'undefined') {
        return;
    }

    var dados = JSON.parse(elemento.textContent);
    var moeda = new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'});
    var cores = ['#2a78d6', '#1baf7a', '#eda73a', '#d65b5b', '#7b61c4', '#4bb3c6'];

    var formas = document.getElementById('grafico-formas');
    if (formas && dados.formas.valores.length) {
        new Chart(formas, {
            type: 'doughnut',
            data: {
                labels: dados.formas.rotulos,
                datasets: [{data: dados.formas.valores, backgroundColor: cores, borderWidth: 2}]
            },
            options: {
                plugins: {
                    legend: {position: 'bottom'},
                    tooltip: {callbacks: {label: function (ctx) { return ctx.label + ': ' + moeda.format(ctx.parsed); }}}
                }
            }
        });
    }

    var produtos = document.getElementById('grafico-produtos');
    if (produtos && dados.produtos.valores.length) {
        new Chart(produtos, {
            type: 'bar',
            data: {
                labels: dados.produtos.rotulos,
                datasets: [{label: 'Quantidade vendida', data: dados.produtos.valores, backgroundColor: cores[0], borderRadius: 4}]
            },
            options: {
                indexAxis: 'y',
                plugins: {legend: {display: false}},
                scales: {x: {beginAtZero: true, ticks: {precision: 0}}}
            }
        });
    }
})();
