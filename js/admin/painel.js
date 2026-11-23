(function () {
    var origem = document.getElementById('dados-painel');
    if (!origem || typeof Chart === 'undefined') {
        return;
    }
    var dados = JSON.parse(origem.textContent);
    var pizza = document.getElementById('graficoCidades');
    var aviso = document.getElementById('avisoPizza');

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
    } else if (pizza && aviso) {
        pizza.hidden = true;
        aviso.hidden = false;
    }

    var barras = document.getElementById('graficoQuantidades');
    if (barras) {
        new Chart(barras, {
            type: 'bar',
            data: {
                labels: ['Produzidas', 'Entregues'],
                datasets: [{
                    data: [dados.feita, dados.entregue],
                    backgroundColor: ['#5d6d7e', '#2980b9']
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
})();
