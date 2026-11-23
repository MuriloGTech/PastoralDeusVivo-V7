(function () {
    var origem = document.getElementById('dados-cardapio');
    var modal = document.getElementById('modalCardapio');
    if (!origem || !modal) {
        return;
    }

    var config = JSON.parse(origem.textContent);
    var lista = document.getElementById('linhasIngrediente');
    var resumo = document.getElementById('resumoCalculo');
    var marmitas = document.getElementById('marmitas');

    function moeda(valor) {
        return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function numero(valor) {
        return valor.toLocaleString('pt-BR', { maximumFractionDigits: 3 });
    }

    function achar(id) {
        return config.ingredientes.find(function (item) {
            return String(item.id) === String(id);
        }) || null;
    }

    function criarSelect(selecionado) {
        var select = document.createElement('select');
        select.name = 'ingrediente[]';
        select.required = true;
        var vazio = document.createElement('option');
        vazio.value = '';
        vazio.textContent = 'Selecione...';
        select.appendChild(vazio);
        config.ingredientes.forEach(function (item) {
            var opcao = document.createElement('option');
            opcao.value = item.id;
            opcao.textContent = item.nome + ' (' + moeda(item.preco) + ' / ' + numero(item.quantidade) + ' ' + item.medida + ')';
            opcao.selected = String(selecionado) === String(item.id);
            select.appendChild(opcao);
        });
        select.addEventListener('change', calcular);
        return select;
    }

    function criarLinha(id, quantidade) {
        var linha = document.createElement('div');
        linha.className = 'linha-ingrediente';

        var quantidadeInput = document.createElement('input');
        quantidadeInput.type = 'number';
        quantidadeInput.name = 'qtdIngrediente[]';
        quantidadeInput.min = '1';
        quantidadeInput.max = '99999';
        quantidadeInput.step = '1';
        quantidadeInput.placeholder = 'Quantidade';
        quantidadeInput.required = true;
        if (quantidade) {
            quantidadeInput.value = quantidade;
        }
        quantidadeInput.addEventListener('input', calcular);

        var remover = document.createElement('button');
        remover.type = 'button';
        remover.className = 'btn-remover';
        remover.textContent = 'Remover';
        remover.addEventListener('click', function () {
            if (lista.children.length === 1) {
                return;
            }
            linha.remove();
            calcular();
        });

        linha.appendChild(criarSelect(id));
        linha.appendChild(quantidadeInput);
        linha.appendChild(remover);
        lista.appendChild(linha);
    }

    function linhaResumo(rotulo, valor) {
        var paragrafo = document.createElement('p');
        var texto = document.createElement('span');
        var forte = document.createElement('strong');
        texto.textContent = rotulo;
        forte.textContent = valor;
        paragrafo.appendChild(texto);
        paragrafo.appendChild(forte);
        return paragrafo;
    }

    function calcular() {
        var totalMarmitas = parseInt(marmitas.value, 10);
        if (!totalMarmitas || totalMarmitas < 1) {
            totalMarmitas = 0;
        }

        var custo = 0;
        var porcoes = [];
        lista.querySelectorAll('.linha-ingrediente').forEach(function (linha) {
            var item = achar(linha.querySelector('select').value);
            var usada = parseInt(linha.querySelector('input').value, 10) || 0;
            if (!item || usada < 1) {
                return;
            }
            var custoItem = item.quantidade > 0 ? (usada / item.quantidade) * item.preco : 0;
            custo += custoItem;
            porcoes.push({
                nome: item.nome,
                texto: numero(totalMarmitas > 0 ? usada / totalMarmitas : 0) + ' ' + item.medida
            });
        });

        resumo.textContent = '';
        resumo.appendChild(linhaResumo('Custo total dos ingredientes', moeda(custo)));
        resumo.appendChild(linhaResumo('Custo por marmita', moeda(totalMarmitas > 0 ? custo / totalMarmitas : 0)));

        if (!porcoes.length) {
            var aviso = document.createElement('p');
            aviso.className = 'aviso-calculo';
            aviso.textContent = 'Informe os ingredientes e as quantidades para calcular.';
            resumo.appendChild(aviso);
            return;
        }

        var titulo = document.createElement('h3');
        titulo.textContent = 'Quantidade em cada marmita';
        var ul = document.createElement('ul');
        porcoes.forEach(function (porcao) {
            var li = document.createElement('li');
            var nome = document.createElement('span');
            var forte = document.createElement('strong');
            nome.textContent = porcao.nome;
            forte.textContent = porcao.texto;
            li.appendChild(nome);
            li.appendChild(forte);
            ul.appendChild(li);
        });
        resumo.appendChild(titulo);
        resumo.appendChild(ul);
    }

    if (lista && config.ingredientes.length) {
        var linhas = config.linhas.length ? config.linhas : [{ id: '', qtd: '' }];
        linhas.forEach(function (linha) {
            criarLinha(linha.id, linha.qtd);
        });
        document.getElementById('adicionarIngrediente').addEventListener('click', function () {
            criarLinha('', '');
        });
        marmitas.addEventListener('input', calcular);
        calcular();
    }

    var abrir = document.getElementById('abrirCardapio');
    if (abrir) {
        abrir.addEventListener('click', function () {
            modal.classList.add('aberto');
        });
    }
    document.getElementById('fecharCardapio').addEventListener('click', function () {
        modal.classList.remove('aberto');
    });
    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            modal.classList.remove('aberto');
        }
    });
})();
