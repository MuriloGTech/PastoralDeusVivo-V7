(function () {
    var modal = document.getElementById('modalFoto');
    var imagem = document.getElementById('modalImagem');
    var girar = document.getElementById('modalGirar');
    var rotacao = 0;

    function aplicarRotacao() {
        imagem.style.transform = 'rotate(' + rotacao + 'deg)';
        var lado = (rotacao / 90) % 2 !== 0;
        imagem.style.maxWidth = lado ? '75vh' : '';
        imagem.style.maxHeight = lado ? '85vw' : '';
    }

    document.querySelectorAll('.foto-mini').forEach(function (foto) {
        foto.addEventListener('click', function () {
            rotacao = 0;
            aplicarRotacao();
            imagem.src = foto.src;
            modal.classList.add('aberto');
        });
    });

    if (girar) {
        girar.addEventListener('click', function () {
            rotacao += 90;
            aplicarRotacao();
        });
    }

    function fechar(alvo) {
        if (alvo) {
            alvo.classList.remove('aberto');
        }
    }

    if (modal) {
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal || evento.target.id === 'modalFechar') {
                fechar(modal);
            }
        });
    }

    var modalExtra = document.getElementById('modalExtra');
    var formExtra = document.getElementById('formExtra');
    document.querySelectorAll('.btn-mais').forEach(function (botao) {
        botao.addEventListener('click', function () {
            document.getElementById('extraId').value = botao.getAttribute('data-id');
            document.getElementById('extraTitulo').textContent = botao.getAttribute('data-apelido');
            var campos = {
                escolaridade: 'escolaridade',
                estadoCivil: 'estadocivil',
                raca: 'raca',
                profissao: 'profissao',
                naturalidade: 'naturalidade'
            };
            Object.keys(campos).forEach(function (campo) {
                formExtra.elements[campo].value = botao.getAttribute('data-' + campos[campo]) || '';
            });
            modalExtra.classList.add('aberto');
        });
    });

    if (modalExtra) {
        modalExtra.addEventListener('click', function (evento) {
            if (evento.target === modalExtra || evento.target.id === 'fecharExtra') {
                fechar(modalExtra);
            }
        });
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            fechar(modal);
            fechar(modalExtra);
        }
    });

    var formExcluir = document.getElementById('formExcluir');
    document.querySelectorAll('.btn-excluir').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var apelido = botao.getAttribute('data-apelido');
            var enviar = function () {
                document.getElementById('idExcluir').value = botao.getAttribute('data-id');
                formExcluir.submit();
            };
            if (!window.Swal) {
                if (window.confirm('Excluir o cadastro de ' + apelido + '?')) {
                    enviar();
                }
                return;
            }
            Swal.fire({
                icon: 'warning',
                title: 'Excluir cadastro?',
                text: 'O cadastro de ' + apelido + ' será removido.',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#c0392b'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    enviar();
                }
            });
        });
    });
})();
