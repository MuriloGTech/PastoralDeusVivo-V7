<button type="button" class="btn-menu" id="btnMenu" aria-label="Abrir ou fechar o menu">
    <svg width="24" height="24" viewBox="0 0 24 24" stroke="#ffffff" stroke-width="3" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<aside class="menu-lateral">
    <nav>
        <?php $rota = $_SERVER['SCRIPT_NAME'] ?? ''; ?>
        <a href="<?= BASE_URL ?>admin/index.php"<?= strpos($rota, 'admin/index.php') !== false ? ' class="ativo"' : '' ?>>INÍCIO</a>
        <a href="<?= BASE_URL ?>admin/cardapio/cardapio.php"<?= strpos($rota, '/cardapio/') !== false ? ' class="ativo"' : '' ?>>CARDÁPIOS DA SEMANA</a>
        <a href="<?= BASE_URL ?>admin/ingredientes/ingredientes.php"<?= strpos($rota, '/ingredientes/') !== false ? ' class="ativo"' : '' ?>>INGREDIENTES</a>
        <a href="<?= BASE_URL ?>admin/controleMarmitas/controleMarmitas.php"<?= strpos($rota, '/controleMarmitas/') !== false ? ' class="ativo"' : '' ?>>RELATÓRIOS</a>
        <a href="<?= BASE_URL ?>admin/doadores/doadores.php"<?= strpos($rota, '/doadores/') !== false ? ' class="ativo"' : '' ?>>DOAÇÕES</a>
        <a href="<?= BASE_URL ?>admin/reabilitados/listagem.php"<?= strpos($rota, '/reabilitados/') !== false ? ' class="ativo"' : '' ?>>REABILITADOS</a>
    </nav>

    <a href="<?= BASE_URL ?>admin/logout.php" class="btn-sair">SAIR</a>
</aside>

<script>

    var corpo = document.body;
    var botao = document.getElementById('btnMenu');
    var salvo = null;

    try {
        salvo = localStorage.getItem('menuFechado');
    } catch (erro) {}

    if (salvo === '1' || (salvo === null && window.innerWidth <= 800)) {
        corpo.classList.add('menu-fechado');
    }

    botao.addEventListener('click', function () {
        var fechado = corpo.classList.toggle('menu-fechado');
        try {
            localStorage.setItem('menuFechado', fechado ? '1' : '0');
        } catch (erro) {}
    });

</script>
