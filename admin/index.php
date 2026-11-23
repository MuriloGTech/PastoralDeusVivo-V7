<?php
require_once __DIR__ . '/../include/auth_admin.php';
require_once __DIR__ . '/../include/funcoes.php';
require_once __DIR__ . '/../include/painel_dados.php';

$visao = ($_GET['visao'] ?? '') === 'passada' ? 'passada' : 'atual';
$dados = carregarPainel($pdo, $visao);
$cardapio = $dados['cardapio'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/painel.css">
</head>
<body>
    <?php include __DIR__ . '/../include/navbar.php'; ?>

    <div class="conteudo-admin">
        <div class="topo-pagina">
            <h1>Painel de Controle da Pastoral Deus Vivo</h1>
            <div class="abas">
                <a href="?visao=passada" class="<?= $visao === 'passada' ? 'ativa' : '' ?>">Semana Passada</a>
                <a href="?visao=atual" class="<?= $visao === 'atual' ? 'ativa' : '' ?>">Semana Atual</a>
            </div>
        </div>

        <?php if ($visao === 'atual'): ?>
            <div class="grade-atual">
                <article class="cartao">
                    <?php if ($cardapio && $cardapio['carFotoCardapio']): ?>
                        <img src="<?= BASE_URL . h($cardapio['carFotoCardapio']) ?>" alt="<?= h($cardapio['carTitulo']) ?>">
                    <?php else: ?>
                        <div class="foto-vazia">Sem fotografia</div>
                    <?php endif; ?>
                    <div class="cartao-corpo">
                        <?php if ($cardapio): ?>
                            <h2><?= h($cardapio['carTitulo']) ?></h2>
                            <p><?= (int) $cardapio['carQuantiadeMarmitaEsperada'] ?> marmitas estimadas</p>
                            <p class="preco-destaque"><?= $cardapio['carPreco'] !== null ? moeda($cardapio['carPreco']) . ' por marmita' : 'Preço ainda não calculado' ?></p>
                        <?php else: ?>
                            <h2>Nenhum cardápio definido</h2>
                            <p>Escolha o cardápio da semana para acompanhar a produção.</p>
                            <a class="link-acao" href="<?= BASE_URL ?>admin/cardapio/cardapio.php">Definir cardápio</a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php else: ?>
            <div class="resumo-numeros">
                <article><strong><?= count($dados['relatorios']) ?></strong><span>Relatórios</span></article>
                <article><strong><?= $dados['somaEntregue'] ?></strong><span>Marmitas entregues</span></article>
                <article><strong><?= count($dados['reabilitados']) ?></strong><span>Reabilitados</span></article>
            </div>

            <div class="grade-graficos">
                <article class="cartao">
                    <?php if ($cardapio && $cardapio['carFotoCardapio']): ?>
                        <img src="<?= BASE_URL . h($cardapio['carFotoCardapio']) ?>" alt="<?= h($cardapio['carTitulo']) ?>">
                    <?php else: ?>
                        <div class="foto-vazia">Sem cardápio nesta semana</div>
                    <?php endif; ?>
                    <div class="cartao-corpo">
                        <h2><?= $cardapio ? h($cardapio['carTitulo']) : 'Nenhum cardápio utilizado' ?></h2>
                        <?php if ($cardapio): ?>
                            <p><?= (int) $cardapio['carQuantiadeMarmitaEsperada'] ?> marmitas previstas</p>
                            <p class="preco-destaque"><?= $cardapio['carPreco'] !== null ? moeda($cardapio['carPreco']) . ' por marmita' : '' ?></p>
                        <?php endif; ?>
                        <?php if ($dados['outrosCardapios']): ?>
                            <p class="aviso">Também houve registro com <?= h(implode(', ', array_column($dados['outrosCardapios'], 'carTitulo'))) ?>.</p>
                        <?php endif; ?>
                    </div>
                </article>
                <section class="bloco">
                    <h2>Pré-visualização das marmitas</h2>
                    <?php if (!$dados['ingredientes']): ?>
                        <p>Não há ingredientes para mostrar nesta semana.</p>
                    <?php else: ?>
                        <ul class="lista-simples">
                            <?php foreach ($dados['ingredientes'] as $item): ?>
                                <?php $porMarmita = (int) $item['carQuantiadeMarmitaEsperada'] > 0 ? $item['caiQuantidadeTotal'] / $item['carQuantiadeMarmitaEsperada'] : 0; ?>
                                <li>
                                    <span><?= h($item['ingNome']) ?></span>
                                    <strong><?= numeroBr($porMarmita) ?> <?= h($item['ingMedida']) ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <div class="grade-graficos">
                <section class="grafico-caixa">
                    <h2>Distribuição por cidade</h2>
                    <canvas id="graficoCidades"></canvas>
                    <p class="grafico-vazio" id="avisoPizza" hidden>Sem entregas registradas nesta semana.</p>
                </section>
                <section class="grafico-caixa">
                    <h2>Produção da semana</h2>
                    <canvas id="graficoQuantidades"></canvas>
                </section>
            </div>

            <div class="grade-blocos">
                <section class="bloco">
                    <h2>Relatórios</h2>
                    <?php if (!$dados['relatorios']): ?>
                        <p>Nenhum relatório nesta semana.</p>
                    <?php else: ?>
                        <ul class="lista-simples">
                            <?php foreach ($dados['relatorios'] as $relatorio): ?>
                                <li>
                                    <span><?= formatoData($relatorio['relData']) ?></span>
                                    <strong><?= (int) $relatorio['relQuantidadeEntregue'] ?> entregues</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
                <section class="bloco">
                    <h2>Maiores distribuições</h2>
                    <?php if (!$dados['locais']): ?>
                        <p>Nenhuma distribuição registrada nesta semana.</p>
                    <?php else: ?>
                        <ul class="lista-simples">
                            <?php foreach ($dados['locais'] as $local): ?>
                                <li>
                                    <span><?= h($local['locNome']) ?><small><?= h($local['barNome']) ?> · <?= h($local['cidNome']) ?></small></span>
                                    <span class="medidor" data-nivel="<?= h($local['entNumeroAvaliativo']) ?>" title="<?= h(textoNivel($local['entNumeroAvaliativo'])) ?>"><i></i><i></i><i></i></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <section class="bloco">
                <h2>Moradores em reabilitação</h2>
                <?php if (!$dados['reabilitados']): ?>
                    <p>Nenhum morador cadastrado nesta semana.</p>
                <?php else: ?>
                    <ul class="lista-simples">
                        <?php foreach ($dados['reabilitados'] as $pessoa): ?>
                            <li>
                                <span><?= h($pessoa['reaNome'] ?: $pessoa['reaApelido']) ?> <small><?= h($pessoa['reaApelido']) ?> · <?= (int) $pessoa['reaIdade'] ?> anos</small></span>
                                <strong><?= h($pessoa['locNome']) ?> · <?= h($pessoa['cidNome']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <script type="application/json" id="dados-painel"><?= json_encode([
                'guara' => $dados['somaGuara'],
                'aparecida' => $dados['somaAparecida'],
                'feita' => $dados['somaFeita'],
                'entregue' => $dados['somaEntregue'],
            ], JSON_UNESCAPED_UNICODE) ?></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script src="<?= BASE_URL ?>js/admin/painel.js"></script>
        <?php endif; ?>
    </div>
</body>
</html>