<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';
require_once __DIR__ . '/../../include/cardapio_funcoes.php';

$linhasForm = [];
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'vigente') {
        $resultado = tornarCardapioDaSemana($pdo, (int) ($_POST['id'] ?? 0));
        flashDefinir($resultado['ok'] ? 'success' : 'error', $resultado['ok'] ? 'Este passou a ser o cardápio da semana.' : $resultado['erro']);
        header('Location: ' . BASE_URL . 'admin/cardapio/cardapio.php');
        exit;
    }

    $resultado = salvarNovoCardapio($pdo, $_POST, $_FILES);
    if ($resultado['ok']) {
        flashDefinir('success', 'Cardápio salvo.');
        header('Location: ' . BASE_URL . 'admin/cardapio/cardapio.php');
        exit;
    }

    $flash = ['tipo' => 'error', 'texto' => $resultado['erro']];
    $ids = $_POST['ingrediente'] ?? [];
    $quantidades = $_POST['qtdIngrediente'] ?? [];
    foreach ($ids as $indice => $idIngrediente) {
        $linhasForm[] = ['id' => (int) $idIngrediente, 'qtd' => (int) ($quantidades[$indice] ?? 0)];
    }
}

if (!$flash) {
    $flash = flashLer();
}

$mapa = ingredientesPorId($pdo);
$ingredientesJson = [];
foreach ($mapa as $ingrediente) {
    $ingredientesJson[] = [
        'id' => (int) $ingrediente['idIngrediente'],
        'nome' => $ingrediente['ingNome'],
        'preco' => (float) $ingrediente['ingPreco'],
        'quantidade' => (float) $ingrediente['ingQuantidade'],
        'medida' => $ingrediente['ingMedida'],
    ];
}

$cardapios = $pdo->query('SELECT c.idCardapioSemana, c.carTitulo, c.carFotoCardapio, c.carPreco, c.carVigente, c.carDataUso,
        (SELECT MAX(r.relData) FROM tblRelatorio r WHERE r.idCardapioSemana = c.idCardapioSemana) AS ultimaRelatorio
    FROM tblCardapioSemana c
    ORDER BY c.carVigente DESC, c.idCardapioSemana DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápios da semana - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/cardapio.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>

    <div class="conteudo-admin">
        <div class="topo-pagina">
            <h1>Cardápios da semana</h1>
            <button type="button" class="btn-acao" id="abrirCardapio">Adicionar cardápio</button>
        </div>

        <?php if (!$cardapios): ?>
            <p class="vazio">Nenhum cardápio cadastrado ainda.</p>
        <?php else: ?>
            <div class="galeria">
                <?php foreach ($cardapios as $cardapio): ?>
                    <?php
                        $datas = array_filter([$cardapio['carDataUso'], $cardapio['ultimaRelatorio']]);
                        $ultima = $datas ? max($datas) : null;
                    ?>
                    <article class="card-menu">
                        <?php if ($cardapio['carFotoCardapio']): ?>
                            <img src="<?= BASE_URL . h($cardapio['carFotoCardapio']) ?>" alt="<?= h($cardapio['carTitulo']) ?>">
                        <?php else: ?>
                            <div class="sem-foto">Sem fotografia</div>
                        <?php endif; ?>
                        <div class="corpo">
                            <?php if ((int) $cardapio['carVigente'] === 1): ?>
                                <span class="selo">Cardápio da semana</span>
                            <?php endif; ?>
                            <h2><?= h($cardapio['carTitulo']) ?></h2>
                            <p class="preco"><?= $cardapio['carPreco'] !== null ? moeda($cardapio['carPreco']) : '—' ?></p>
                            <form method="post">
                                <input type="hidden" name="acao" value="vigente">
                                <input type="hidden" name="id" value="<?= (int) $cardapio['idCardapioSemana'] ?>">
                                <button type="submit" class="<?= (int) $cardapio['carVigente'] === 1 ? 'btn-secundario' : 'btn-acao' ?>" <?= (int) $cardapio['carVigente'] === 1 ? 'disabled' : '' ?>>
                                    <?= (int) $cardapio['carVigente'] === 1 ? 'Cardápio da semana' : 'Tornar Cardápio da Semana' ?>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-form <?= $linhasForm ? 'aberto' : '' ?>" id="modalCardapio">
        <form class="caixa-form" method="post" enctype="multipart/form-data">
            <button type="button" class="fechar-modal" id="fecharCardapio" aria-label="Fechar">&times;</button>
            <h2>Novo cardápio</h2>
            <p class="ajuda">O custo usa a regra de três: preço do ingrediente ÷ quantidade cadastrada × quantidade usada neste preparo.</p>

            <?php if (!$ingredientesJson): ?>
                <p class="vazio">Cadastre ingredientes antes de criar um cardápio.</p>
            <?php else: ?>
                <div class="grade-form">
                    <label class="campo campo-largo">
                        Título do cardápio
                        <input type="text" name="titulo" maxlength="45" required value="<?= h($_POST['titulo'] ?? '') ?>">
                    </label>
                    <label class="campo">
                        Fotografia
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                    <label class="campo">
                        Marmitas estimadas
                        <input type="number" id="marmitas" name="marmitas" min="1" max="999" required value="<?= h((string) ($_POST['marmitas'] ?? '')) ?>">
                    </label>
                    <div class="campo campo-largo">
                        <label>Ingredientes e quantidades</label>
                        <div id="linhasIngrediente"></div>
                        <button type="button" class="btn-linha" id="adicionarIngrediente">Adicionar ingrediente</button>
                    </div>
                    <div class="resumo-calculo campo-largo" id="resumoCalculo"></div>
                </div>
                <button type="submit" class="btn-acao" name="acao" value="salvar">Salvar</button>
            <?php endif; ?>
        </form>
    </div>

    <script type="application/json" id="dados-cardapio"><?= json_encode([
        'ingredientes' => $ingredientesJson,
        'linhas' => $linhasForm,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= BASE_URL ?>js/admin/cardapio.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>
