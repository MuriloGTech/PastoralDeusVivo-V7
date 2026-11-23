<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';
require_once __DIR__ . '/../../include/relatorio_funcoes.php';

$reabrir = false;
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = salvarRelatorio($pdo, $_POST, (int) $_SESSION['idCliente']);
    if ($resultado['ok']) {
        flashDefinir('success', 'Relatório registrado.');
        header('Location: ' . BASE_URL . 'admin/controleMarmitas/controleMarmitas.php');
        exit;
    }
    $flash = ['tipo' => 'error', 'texto' => $resultado['erro']];
    $reabrir = true;
} else {
    $flash = flashLer();
}

$ordens = [
    'data' => 'rel.relData',
    'produzidas' => 'rel.relQuantidadeFeita',
    'entregues' => 'rel.relQuantidadeEntregue',
];
$ordem = isset($ordens[$_GET['ordem'] ?? '']) ? $_GET['ordem'] : 'data';
$direcao = ($_GET['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
$mes = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    $mes = date('Y-m');
}

$vigente = $pdo->query('SELECT carTitulo FROM tblCardapioSemana WHERE carVigente = 1 LIMIT 1')->fetch();
$locais = $pdo->query('SELECT l.idLocal, l.locNome, b.barNome, c.cidNome
    FROM tblLocal l
    JOIN tblBairro b ON b.idBairro = l.idBairro
    JOIN tblCidade c ON c.idCidade = b.idCidade
    ORDER BY c.cidNome, b.barNome, l.locNome')->fetchAll();

$grupos = [];
foreach ($locais as $local) {
    $grupos[$local['cidNome']][] = $local;
}

$relatorios = $pdo->query('SELECT rel.idRelatorio, rel.relData, rel.relQuantidadeFeita, rel.relQuantidadeEntregue,
        rel.relQuantidadeGuara, rel.relQuantidadeAparecida, c.carTitulo
    FROM tblRelatorio rel
    JOIN tblCardapioSemana c ON c.idCardapioSemana = rel.idCardapioSemana
    ORDER BY ' . $ordens[$ordem] . ' ' . ($direcao === 'asc' ? 'ASC' : 'DESC') . ', rel.idRelatorio DESC')->fetchAll();

$porRelatorio = [];
if ($relatorios) {
    $ids = implode(',', array_map(fn($relatorio) => (int) $relatorio['idRelatorio'], $relatorios));
    $entregas = $pdo->query('SELECT re.idRelatorio, l.locNome, e.entNumeroAvaliativo
        FROM tblRelatorio_Entrega re
        JOIN tblEntrega e ON e.idEntrega = re.idEntrega
        JOIN tblLocal l ON l.idLocal = e.idLocal
        WHERE re.idRelatorio IN (' . $ids . ')
        ORDER BY e.entNumeroAvaliativo DESC, l.locNome')->fetchAll();
    foreach ($entregas as $entrega) {
        $porRelatorio[$entrega['idRelatorio']][] = $entrega;
    }
}

[$inicioMes, $fimMes] = periodoDoMes($mes, 0);
$consultaMes = $pdo->prepare('SELECT relData, relQuantidadeGuara, relQuantidadeAparecida FROM tblRelatorio WHERE relData BETWEEN :ini AND :fim');
$consultaMes->execute([':ini' => $inicioMes->format('Y-m-d'), ':fim' => $fimMes->format('Y-m-d')]);
$totalSemanas = semanasDoMes($mes);
$grafico = ['guara' => 0, 'aparecida' => 0, 'labels' => [], 'guaraSemanas' => [], 'aparecidaSemanas' => []];
for ($semana = 1; $semana <= $totalSemanas; $semana++) {
    $grafico['labels'][] = 'Semana ' . $semana;
    $grafico['guaraSemanas'][] = 0;
    $grafico['aparecidaSemanas'][] = 0;
}
foreach ($consultaMes as $linha) {
    $grafico['guara'] += (int) $linha['relQuantidadeGuara'];
    $grafico['aparecida'] += (int) $linha['relQuantidadeAparecida'];
    $indice = min($totalSemanas, (int) ceil(((int) (new DateTime($linha['relData']))->format('j')) / 7)) - 1;
    $grafico['guaraSemanas'][$indice] += (int) $linha['relQuantidadeGuara'];
    $grafico['aparecidaSemanas'][$indice] += (int) $linha['relQuantidadeAparecida'];
}

function campoRelatorio(string $nome): string
{
    return h((string) ($_POST[$nome] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/relatorios.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>
    <div class="conteudo-admin">
        <div class="topo-pagina">
            <h1>Relatórios</h1>
            <button type="button" class="btn-acao <?= $reabrir ? 'oculto' : '' ?>" id="abrirRelatorio">Novo relatório</button>
        </div>

        <section class="caixa <?= $reabrir ? '' : 'oculto' ?>" id="caixaRelatorio">
            <h2>Registrar atividade</h2>
            <?php if ($vigente): ?>
                <p class="cardapio-lido">Cardápio da semana: <strong><?= h($vigente['carTitulo']) ?></strong></p>
            <?php else: ?>
                <p class="cardapio-lido">Defina o cardápio da semana antes de registrar. <a href="<?= BASE_URL ?>admin/cardapio/cardapio.php">Ir para cardápios</a></p>
            <?php endif; ?>
            <form method="post">
                <div class="grade-form">
                    <label class="campo">DATA<input type="date" name="data" required value="<?= campoRelatorio('data') ?>"></label>
                    <label class="campo">MARMITAS PRODUZIDAS<input type="number" name="produzidas" min="0" max="999" required value="<?= campoRelatorio('produzidas') ?>"></label>
                    <label class="campo">MARMITAS ENTREGUES<input type="number" name="entregues" min="0" max="999" required value="<?= campoRelatorio('entregues') ?>"></label>
                    <label class="campo">GUARATINGUETÁ<input type="number" name="guara" min="0" max="999" required value="<?= campoRelatorio('guara') ?>"></label>
                    <label class="campo">APARECIDA<input type="number" name="aparecida" min="0" max="999" required value="<?= campoRelatorio('aparecida') ?>"></label>
                </div>
                <p class="legenda-nivel">Classifique cada local: 1 pouca distribuição, 2 média, 3 muita.</p>
                <?php foreach ($grupos as $cidade => $lista): ?>
                    <div class="grupo-cidade">
                        <h3><?= h($cidade) ?></h3>
                        <?php foreach ($lista as $local): ?>
                            <?php $nota = (string) ($_POST['nota'][$local['idLocal']] ?? ''); ?>
                            <div class="local-linha">
                                <div>
                                    <strong><?= h($local['locNome']) ?></strong>
                                    <small><?= h($local['barNome']) ?></small>
                                </div>
                                <div class="nivel" data-nivel="<?= h($nota) ?>">
                                    <button type="button" data-valor="1">1</button>
                                    <button type="button" data-valor="2">2</button>
                                    <button type="button" data-valor="3">3</button>
                                    <input type="hidden" name="nota[<?= (int) $local['idLocal'] ?>]" value="<?= h($nota) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn-acao" <?= $vigente ? '' : 'disabled' ?>>Salvar relatório</button>
            </form>
        </section>

        <form class="filtros" method="get">
            <label>ORGANIZAR POR
                <select name="ordem">
                    <option value="data" <?= $ordem === 'data' ? 'selected' : '' ?>>Data do relatório</option>
                    <option value="produzidas" <?= $ordem === 'produzidas' ? 'selected' : '' ?>>Marmitas produzidas</option>
                    <option value="entregues" <?= $ordem === 'entregues' ? 'selected' : '' ?>>Marmitas entregues</option>
                </select>
            </label>
            <label>ORDEM
                <select name="dir">
                    <option value="desc" <?= $direcao === 'desc' ? 'selected' : '' ?>>Maior para o menor</option>
                    <option value="asc" <?= $direcao === 'asc' ? 'selected' : '' ?>>Menor para o maior</option>
                </select>
            </label>
            <label>MÊS DOS GRÁFICOS
                <input type="month" name="mes" value="<?= h($mes) ?>">
            </label>
            <button type="submit" class="btn-acao">Aplicar</button>
        </form>

        <?php if (!$relatorios): ?>
            <p class="vazio">Nenhum relatório cadastrado ainda.</p>
        <?php else: ?>
            <div class="cards-relatorio">
                <?php foreach ($relatorios as $relatorio): ?>
                    <article class="card-relatorio">
                        <header>
                            <strong><?= formatoData($relatorio['relData']) ?></strong>
                            <span><?= h($relatorio['carTitulo']) ?></span>
                        </header>
                        <div class="numeros-relatorio">
                            <div><b><?= (int) $relatorio['relQuantidadeFeita'] ?></b><span>produzidas</span></div>
                            <div><b><?= (int) $relatorio['relQuantidadeEntregue'] ?></b><span>entregues</span></div>
                            <div><b><?= (int) $relatorio['relQuantidadeGuara'] ?></b><span>Guaratinguetá</span></div>
                            <div><b><?= (int) $relatorio['relQuantidadeAparecida'] ?></b><span>Aparecida</span></div>
                        </div>
                        <?php $entregasRelatorio = $porRelatorio[$relatorio['idRelatorio']] ?? []; ?>
                        <?php if ($entregasRelatorio): ?>
                            <button type="button" class="btn-info" aria-expanded="false">Mais informação</button>
                            <div class="detalhes oculto">
                                <?php foreach ($entregasRelatorio as $entrega): ?>
                                    <div class="mini-local">
                                        <span><?= h($entrega['locNome']) ?></span>
                                        <span class="medidor" data-nivel="<?= h($entrega['entNumeroAvaliativo']) ?>" title="<?= h(textoNivel($entrega['entNumeroAvaliativo'])) ?>"><i></i><i></i><i></i></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="grade-graficos">
            <section class="grafico-caixa">
                <h2>Distribuição por cidade</h2>
                <canvas id="graficoCidades"></canvas>
                <p class="grafico-vazio" id="avisoPizza" hidden>Sem entregas neste mês.</p>
            </section>
            <section class="grafico-caixa">
                <h2>Distribuição ao longo do mês</h2>
                <canvas id="graficoSemanas"></canvas>
            </section>
        </div>
    </div>

    <script type="application/json" id="dados-graficos"><?= json_encode($grafico, JSON_UNESCAPED_UNICODE) ?></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_URL ?>js/admin/relatorios.js"></script>
    <script>
        document.querySelectorAll('.btn-info').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var detalhes = botao.nextElementSibling;
                var aberto = detalhes.classList.toggle('oculto') === false;
                botao.textContent = aberto ? 'Menos informação' : 'Mais informação';
                botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>