(function () {
    var origem = document.getElementById('dados-locais');
    var cidade = document.getElementById('idCidade');
    var bairro = document.getElementById('idBairro');
    var local = document.getElementById('idLocal');
    if (!origem || !cidade || !bairro || !local) {
        return;
    }

    var dados = JSON.parse(origem.textContent);
    var cidadeInicial = cidade.value;
    var bairroInicial = bairro.value;
    var localInicial = local.value;

    function opcao(valor, texto, selecionado) {
        var item = document.createElement('option');
        item.value = valor;
        item.textContent = texto;
        item.selected = selecionado;
        return item;
    }

    function preencherBairros() {
        var escolhido = bairro.value || bairroInicial;
        bairro.textContent = '';
        bairro.appendChild(opcao('', 'Selecione...', escolhido === ''));
        var vistos = {};
        dados.bairros.forEach(function (item) {
            if (String(item.cidade) !== String(cidade.value) || vistos[item.id]) {
                return;
            }
            vistos[item.id] = true;
            bairro.appendChild(opcao(item.id, item.nome, String(item.id) === String(escolhido)));
        });
        bairroInicial = '';
        preencherLocais();
    }

    function preencherLocais() {
        var escolhido = local.value || localInicial;
        local.textContent = '';
        local.appendChild(opcao('', 'Selecione...', escolhido === ''));
        dados.locais.forEach(function (item) {
            if (String(item.bairro) !== String(bairro.value)) {
                return;
            }
            local.appendChild(opcao(item.id, item.nome, String(item.id) === String(escolhido)));
        });
        localInicial = '';
    }

    cidade.addEventListener('change', function () {
        bairroInicial = '';
        localInicial = '';
        bairro.value = '';
        local.value = '';
        preencherBairros();
    });
    bairro.addEventListener('change', function () {
        localInicial = '';
        preencherLocais();
    });

    if (cidadeInicial) {
        cidade.value = cidadeInicial;
    }
    preencherBairros();
})();
