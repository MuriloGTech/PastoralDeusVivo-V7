<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';
require_once __DIR__ . '/../../include/doacao_funcoes.php';

$mes = $_REQUEST['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    $mes = date('Y-m');
}
$semana = (int) ($_REQUEST['semana'] ?? 0);
$totalSemanas = semanasDoMes($mes);
if ($semana < 0 || $semana > $totalSemanas) {
    $semana = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = ($_POST['acao'] ?? '') === 'doador'
        ? salvarDoador($pdo, $_POST)
        : salvarDoacaoRecebida($pdo, $_POST);
    flashDefinir($resultado['ok'] ? 'success' : 'error', $resultado['ok'] ? $resultado['texto'] : $resultado['erro']);
    header('Location: ' . BASE_URL . 'admin/doadores/doadores.php?mes=' . urlencode($mes) . '&semana=' . $semana);
    exit;
}

$flash = flashLer();
[$inicio, $fim] = periodoDoMes($mes, $semana);

$vigente = $pdo->query('SELECT c.carTitulo, ci.idCardapioSemana_Ingrediente AS idItem, i.ingNome, i.ingMedida
    FROM tblCardapioSemana c
    JOIN tblCardapioSemana_Ingrediente ci ON ci.idCardapioSemana = c.idCardapioSemana
    JOIN tblIngrediente i ON i.idIngrediente = ci.idIngrediente
    WHERE c.carVigente = 1
    ORDER BY i.ingNome')->fetchAll();

$doadores = $pdo->query('SELECT idDoador, doaNome, doaTelefone FROM tblDoador ORDER BY doaNome')->fetchAll();

$consulta = $pdo->prepare('SELECT d.doaQuantidadeDoada, d.doaDataDoacao, dor.doaNome, i.ingNome, i.ingMedida, c.idCardapioSemana, c.carTitulo
    FROM tblDoacao d
    JOIN tblDoador dor ON dor.idDoador = d.idDoador
    JOIN tblCardapioSemana_Ingrediente ci ON ci.idCardapioSemana_Ingrediente = d.idCardapioSemana_Ingrediente
    JOIN tblIngrediente i ON i.idIngrediente = ci.idIngrediente
    JOIN tblCardapioSemana c ON c.idCardapioSemana = ci.idCardapioSemana
    WHERE d.doaDataDoacao BETWEEN :ini AND :fim
    ORDER BY c.carTitulo, d.doaDataDoacao DESC, d.idDoacao DESC');
$consulta->execute([':ini' => $inicio->format('Y-m-d'), ':fim' => $fim->format('Y-m-d')]);

$grupos = [];
$totalDoacoes = 0;
foreach ($consulta as $doacao) {
    $totalDoacoes++;
    $chave = (int) $doacao['idCardapioSemana'];
    if (!isset($grupos[$chave])) {
        $grupos[$chave] = ['titulo' => $doacao['carTitulo'], 'itens' => [], 'totais' => []];
    }
    $grupos[$chave]['itens'][] = $doacao;
    $nomeIngrediente = $doacao['ingNome'];
    $anterior = $grupos[$chave]['totais'][$nomeIngrediente]['total'] ?? 0;
    $grupos[$chave]['totais'][$nomeIngrediente] = [
        'medida' => $doacao['ingMedida'],
        'total' => $anterior + (int) $doacao['doaQuantidadeDoada'],
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doações - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/doacoes.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>
    <div class="conteudo-admin">
        <h1>Doações</h1>
        <p class="intro">As doações novas entram no cardápio da semana e aparecem na página pública.</p>

        <div class="barra-filtro">
        <form class="filtros" method="get">
            <label>MÊS<input type="month" name="mes" value="<?= h($mes) ?>"></label>
            <label>SEMANA
                <select name="semana">
                    <option value="0">Todas as semanas</option>
                    <?php for ($numero = 1; $numero <= $totalSemanas; $numero++): ?>
                        <?php [$iniSemana, $fimSemana] = periodoDoMes($mes, $numero); ?>
                        <option value="<?= $numero ?>" <?= $semana === $numero ? 'selected' : '' ?>>
                            Semana <?= $numero ?> (<?= $iniSemana->format('d/m') ?> a <?= $fimSemana->format('d/m') ?>)
                        </option>
                    <?php endfor; ?>
                </select>
            </label>
            <button type="submit" class="btn-acao">Filtrar</button>
        </form>
        <p class="periodo-doacoes"><?= $totalDoacoes ?> doação(ões) de <?= formatoData($inicio->format('Y-m-d')) ?> a <?= formatoData($fim->format('Y-m-d')) ?></p>
        </div>

        <?php if (!$grupos): ?>
            <p class="vazio">Nenhuma doação neste período.</p>
        <?php endif; ?>
        <?php foreach ($grupos as $grupo): ?>
            <section class="grupo-doacao">
                <h2><?= h($grupo['titulo']) ?></h2>
                <p class="totais">
                    <?php
                        $partes = [];
                        foreach ($grupo['totais'] as $nome => $total) {
                            $partes[] = $nome . ': ' . $total['total'] . ' ' . $total['medida'];
                        }
                        echo h(implode(' · ', $partes));
                    ?>
                </p>
                <table class="tabela">
                    <thead><tr><th>INGREDIENTE</th><th>QUANTIDADE</th><th>DOADOR</th></tr></thead>
                    <tbody>
                        <?php foreach ($grupo['itens'] as $item): ?>
                            <tr>
                                <td><?= h($item['ingNome']) ?></td>
                                <td><?= (int) $item['doaQuantidadeDoada'] ?> <?= h($item['ingMedida']) ?></td>
                                <td><?= h($item['doaNome']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>

        <div class="grade-cadastro">
            <section class="caixa">
                <h2>Registrar doação</h2>
                <?php if ($vigente): ?><p class="aviso aviso-ok">Cardápio da semana: <strong><?= h($vigente[0]['carTitulo']) ?></strong></p><?php endif; ?>
                <?php if (!$vigente): ?>
                    <p class="aviso">Defina o cardápio da semana para vincular a doação aos ingredientes.</p>
                <?php elseif (!$doadores): ?>
                    <p class="aviso">Cadastre um doador antes de registrar a doação.</p>
                <?php else: ?>
                    <form method="post" class="form-grade">
                        <input type="hidden" name="acao" value="doacao">
                        <input type="hidden" name="mes" value="<?= h($mes) ?>">
                        <input type="hidden" name="semana" value="<?= $semana ?>">
                        <label class="campo campo-cheio">INGREDIENTE
                            <select name="idItem" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($vigente as $item): ?>
                                    <option value="<?= (int) $item['idItem'] ?>"><?= h($item['ingNome']) ?> (<?= h($item['ingMedida']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="campo">QUANTIDADE<input type="number" name="quantidade" min="1" max="99999" required></label>
                        <label class="campo">RESPONSÁVEL
                            <select name="idDoador" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($doadores as $doador): ?>
                                    <option value="<?= (int) $doador['idDoador'] ?>"><?= h($doador['doaNome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="btn-acao">Salvar</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="caixa">
                <h2 id="tituloDoador">Cadastrar doador</h2>
                <form method="post" id="formDoador" class="form-grade">
                    <input type="hidden" name="acao" value="doador">
                    <input type="hidden" name="idDoador" id="idDoador" value="0">
                    <input type="hidden" name="mes" value="<?= h($mes) ?>">
                    <input type="hidden" name="semana" value="<?= $semana ?>">
                    <label class="campo">NOME<input type="text" name="nome" id="nomeDoador" maxlength="255" required></label>
                    <label class="campo">TELEFONE<input type="text" name="telefone" id="telefoneDoador" maxlength="45" required></label>
                    <button type="submit" class="btn-acao" id="botaoDoador">Cadastrar</button>
                </form>
                <h3 class="subtitulo">DOADORES CADASTRADOS</h3>
                <ul class="lista-doadores">
                    <?php foreach ($doadores as $doador): ?>
                        <li>
                            <span class="doador-info"><strong><?= h($doador['doaNome']) ?></strong><small><?= h(formatarTelefone($doador['doaTelefone'])) ?></small></span>
                            <button type="button" class="btn-editar" data-id="<?= (int) $doador['idDoador'] ?>" data-nome="<?= h($doador['doaNome']) ?>" data-telefone="<?= h($doador['doaTelefone']) ?>">Editar</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </div>
    <script>
        document.querySelectorAll('.btn-editar').forEach(function (botao) {
            botao.addEventListener('click', function () {
                document.getElementById('idDoador').value = botao.getAttribute('data-id');
                document.getElementById('nomeDoador').value = botao.getAttribute('data-nome');
                document.getElementById('telefoneDoador').value = botao.getAttribute('data-telefone');
                document.getElementById('tituloDoador').textContent = 'Editar doador';
                document.getElementById('botaoDoador').textContent = 'Salvar';
                document.getElementById('nomeDoador').focus();
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>