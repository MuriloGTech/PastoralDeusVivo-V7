(function () {
    document.querySelectorAll('.nivel').forEach(function (barra) {
        barra.querySelectorAll('button').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var valor = botao.getAttribute('data-valor');
                barra.setAttribute('data-nivel', valor);
                barra.querySelector('input').value = valor;
            });
        });
    });

    var abrir = document.getElementById('abrirRelatorio');
    var caixa = document.getElementById('caixaRelatorio');
    if (abrir && caixa) {
        abrir.addEventListener('click', function () {
            caixa.classList.remove('oculto');
            abrir.classList.add('oculto');
        });
    }

    var origem = document.getElementById('dados-graficos');
    if (!origem || typeof Chart === 'undefined') {
        return;
    }
    var dados = JSON.parse(origem.textContent);

    var pizza = document.getElementById('graficoCidades');
    var avisoPizza = document.getElementById('avisoPizza');
    if (pizza && (dados.guara > 0 || dados.aparecida > 0)) {
        new Chart(pizza, {
            type: 'pie',
            data: {
                labels: ['Guaratinguetá', 'Aparecida'],
                datasets: [{
                    data: [dados.guara, dados.aparecida],
                    backgroundColor: ['#2980b9', '#c0392b']
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    } else if (pizza && avisoPizza) {
        pizza.hidden = true;
        avisoPizza.hidden = false;
    }

    var linhas = document.getElementById('graficoSemanas');
    if (linhas) {
        new Chart(linhas, {
            type: 'line',
            data: {
                labels: dados.labels,
                datasets: [
                    {
                        label: 'Guaratinguetá',
                        data: dados.guaraSemanas,
                        borderColor: '#2980b9',
                        backgroundColor: '#2980b9',
                        tension: 0.25
                    },
                    {
                        label: 'Aparecida',
                        data: dados.aparecidaSemanas,
                        borderColor: '#c0392b',
                        backgroundColor: '#c0392b',
                        tension: 0.25
                    }
                ]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
