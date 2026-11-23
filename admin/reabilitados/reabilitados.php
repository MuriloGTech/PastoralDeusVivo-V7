<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';
require_once __DIR__ . '/../../include/reabilitado_funcoes.php';

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$valores = [
    'nome' => '', 'apelido' => '', 'idade' => '', 'cpf' => '', 'telefone' => '',
    'idRelatorio' => '', 'idCidade' => '', 'idBairro' => '', 'idLocal' => '',
];
$fotoAtual = '';
$flash = null;

if ($id > 0) {
    $busca = $pdo->prepare('SELECT r.*, b.idBairro, b.idCidade
        FROM tblReabilitado r
        JOIN tblLocal l ON l.idLocal = r.idLocal
        JOIN tblBairro b ON b.idBairro = l.idBairro
        WHERE r.idReabilitado = :id');
    $busca->execute([':id' => $id]);
    $registro = $busca->fetch();
    if (!$registro) {
        flashDefinir('error', 'Cadastro não encontrado.');
        header('Location: ' . BASE_URL . 'admin/reabilitados/listagem.php');
        exit;
    }
    $fotoAtual = (string) $registro['reaDocumento'];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $valores = [
            'nome' => (string) $registro['reaNome'],
            'apelido' => $registro['reaApelido'],
            'idade' => (string) $registro['reaIdade'],
            'cpf' => (string) $registro['reaCpf'],
            'telefone' => (string) $registro['reaTelefone'],
            'idRelatorio' => (string) $registro['idRelatorio'],
            'idCidade' => (string) $registro['idCidade'],
            'idBairro' => (string) $registro['idBairro'],
            'idLocal' => (string) $registro['idLocal'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = processarReabilitado($pdo, $_POST, $_FILES);
    if ($resultado['ok']) {
        flashDefinir('success', $id > 0 ? 'Cadastro atualizado.' : 'Reabilitado cadastrado com sucesso.');
        header('Location: ' . BASE_URL . 'admin/reabilitados/listagem.php');
        exit;
    }
    $flash = ['tipo' => 'error', 'texto' => $resultado['erro']];
    foreach ($valores as $campo => $ignorar) {
        $valores[$campo] = (string) ($_POST[$campo] ?? '');
    }
} else {
    $flash = flashLer();
}

$relatorios = $pdo->query('SELECT idRelatorio, relData FROM tblRelatorio ORDER BY relData DESC')->fetchAll();
$cidades = $pdo->query('SELECT idCidade, cidNome FROM tblCidade ORDER BY cidNome')->fetchAll();
$bairros = $pdo->query('SELECT idBairro, idCidade, barNome FROM tblBairro ORDER BY barNome')->fetchAll();
$locais = $pdo->query('SELECT idLocal, idBairro, locNome FROM tblLocal ORDER BY locNome')->fetchAll();
$dadosLocais = [
    'bairros' => array_map(fn($bairro) => ['id' => (int) $bairro['idBairro'], 'cidade' => (int) $bairro['idCidade'], 'nome' => $bairro['barNome']], $bairros),
    'locais' => array_map(fn($local) => ['id' => (int) $local['idLocal'], 'bairro' => (int) $local['idBairro'], 'nome' => $local['locNome']], $locais),
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'Editar' : 'Cadastrar' ?> reabilitado - Pastoral Deus Vivo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/reabilitados.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>
    <div class="conteudo-admin">
        <div class="topo-pagina">
            <h1><?= $id > 0 ? 'Editar reabilitado' : 'Cadastrar reabilitado' ?></h1>
            <a href="<?= BASE_URL ?>admin/reabilitados/listagem.php" class="btn-lista">Visualizar Lista</a>
        </div>

        <form class="form-cadastro" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="campo"><label for="nome">NOME</label><input type="text" id="nome" name="nome" maxlength="80" value="<?= h($valores['nome']) ?>"></div>
            <div class="campo"><label for="apelido">APELIDO *</label><input type="text" id="apelido" name="apelido" maxlength="15" required value="<?= h($valores['apelido']) ?>"></div>
            <div class="campo"><label for="idade">IDADE *</label><input type="number" id="idade" name="idade" min="1" max="99" required value="<?= h($valores['idade']) ?>"></div>
            <div class="campo"><label for="cpf">CPF</label><input type="text" id="cpf" name="cpf" maxlength="14" value="<?= h($valores['cpf']) ?>"></div>
            <div class="campo"><label for="telefone">TELEFONE</label><input type="text" id="telefone" name="telefone" maxlength="15" value="<?= h($valores['telefone']) ?>"></div>
            <div class="campo">
                <label for="idRelatorio">RELATÓRIO *</label>
                <select id="idRelatorio" name="idRelatorio" required>
                    <option value=""><?= $relatorios ? 'Selecione...' : 'Nenhum relatório cadastrado' ?></option>
                    <?php foreach ($relatorios as $relatorio): ?>
                        <option value="<?= (int) $relatorio['idRelatorio'] ?>" <?= (string) $valores['idRelatorio'] === (string) $relatorio['idRelatorio'] ? 'selected' : '' ?>>
                            Relatório <?= (int) $relatorio['idRelatorio'] ?> - <?= formatoData($relatorio['relData']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="idCidade">CIDADE *</label>
                <select id="idCidade" name="idCidade" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($cidades as $cidade): ?>
                        <option value="<?= (int) $cidade['idCidade'] ?>" <?= (string) $valores['idCidade'] === (string) $cidade['idCidade'] ? 'selected' : '' ?>><?= h($cidade['cidNome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="idBairro">BAIRRO *</label>
                <select id="idBairro" name="idBairro" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($bairros as $bairro): ?>
                        <option value="<?= (int) $bairro['idBairro'] ?>" <?= (string) $valores['idBairro'] === (string) $bairro['idBairro'] ? 'selected' : '' ?>><?= h($bairro['barNome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="idLocal">LOCAL ENCONTRADO *</label>
                <select id="idLocal" name="idLocal" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($locais as $local): ?>
                        <option value="<?= (int) $local['idLocal'] ?>" <?= (string) $valores['idLocal'] === (string) $local['idLocal'] ? 'selected' : '' ?>><?= h($local['locNome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="foto">FOTO DO RG</label>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                <?php if ($fotoAtual): ?>
                    <img class="foto-atual" src="<?= BASE_URL . h($fotoAtual) ?>" alt="RG atual">
                <?php endif; ?>
            </div>
            <button type="submit"><?= $id > 0 ? 'Salvar alterações' : 'Cadastrar' ?></button>
        </form>
    </div>

    <script type="application/json" id="dados-locais"><?= json_encode($dadosLocais, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= BASE_URL ?>js/admin/reabilitados.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>
