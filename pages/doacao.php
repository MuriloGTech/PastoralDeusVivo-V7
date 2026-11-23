<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../include/funcoes.php';

$whatsNumero = '5512997911364';
$whatsExibir = '(12) 99791-1364';
$whatsMsg    = 'Olá! Gostaria de doar ingredientes para a Pastoral Deus Vivo.';
$linkWhats   = 'https://wa.me/' . $whatsNumero . '?text=' . urlencode($whatsMsg);

$cardapio = $pdo->query('SELECT idCardapioSemana, carTitulo, carFotoCardapio FROM tblCardapioSemana WHERE carVigente = 1 LIMIT 1')->fetch();
$itens = [];
$media = 0;

if ($cardapio) {
    $consulta = $pdo->prepare('SELECT i.ingNome, i.ingMedida, ci.caiQuantidadeTotal AS necessario,
            COALESCE(SUM(d.doaQuantidadeDoada), 0) AS doado
        FROM tblCardapioSemana_Ingrediente ci
        JOIN tblIngrediente i ON i.idIngrediente = ci.idIngrediente
        LEFT JOIN tblDoacao d ON d.idCardapioSemana_Ingrediente = ci.idCardapioSemana_Ingrediente
        WHERE ci.idCardapioSemana = :id
        GROUP BY ci.idCardapioSemana_Ingrediente, i.ingNome, i.ingMedida, ci.caiQuantidadeTotal
        ORDER BY i.ingNome');
    $consulta->execute([':id' => $cardapio['idCardapioSemana']]);
    $soma = 0;
    foreach ($consulta as $linha) {
        $necessario = (int) $linha['necessario'];
        $doado = (int) $linha['doado'];
        $percentual = $necessario > 0 ? (int) round(min(100, ($doado / $necessario) * 100)) : 0;
        $soma += $percentual;
        $itens[] = [
            'nome' => $linha['ingNome'],
            'medida' => $linha['ingMedida'],
            'necessario' => $necessario,
            'doado' => $doado,
            'falta' => max(0, $necessario - $doado),
            'percentual' => $percentual,
        ];
    }
    $media = $itens ? (int) round($soma / count($itens)) : 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doação</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/index.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/doacao.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/site.css">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>

    <section class="doacao">
        <div class="doacao-header">
            <span class="doacao-line"></span>
            <h2>Ajude a montar a marmita</h2>
        </div>

        <?php if (!$cardapio): ?>
            <div class="doacao-vazia">
                <p>O cardápio desta semana ainda não foi definido. Volte em breve para ver o que a pastoral precisa.</p>
            </div>
        <?php else: ?>
            <div class="doacao-content">
                <div class="doacao-marmita">
                    <?php if ($cardapio['carFotoCardapio']): ?>
                        <img src="<?= BASE_URL . h($cardapio['carFotoCardapio']) ?>" alt="<?= h($cardapio['carTitulo']) ?>">
                    <?php endif; ?>
                    <strong class="marmita-titulo"><?= h($cardapio['carTitulo']) ?></strong>
                    <div class="progress-info">
                        <strong>Marmita completa</strong>
                        <span><?= $media ?>%</span>
                    </div>
                    <div class="progress progress-grande">
                        <div class="progress-fill" style="width: <?= $media ?>%"></div>
                    </div>
                </div>

                <div class="doacao-ingredientes">
                    <h3>O que ainda precisamos</h3>
                    <?php if (!$itens): ?>
                        <p>Este cardápio ainda não tem ingredientes.</p>
                    <?php endif; ?>
                    <?php foreach ($itens as $item): ?>
                        <div class="ingrediente">
                            <div class="progress-info">
                                <strong><?= h($item['nome']) ?></strong>
                                <span><?= $item['percentual'] ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-fill" style="width: <?= $item['percentual'] ?>%"></div>
                            </div>
                            <small>
                                <?= $item['falta'] === 0
                                    ? 'Meta concluída'
                                    : 'Faltam ' . $item['falta'] . ' ' . h($item['medida']) . ' (' . $item['doado'] . ' de ' . $item['necessario'] . ' ' . h($item['medida']) . ')' ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="doacao-botao">
                <button type="button" class="btn-doar" id="abrirDoar">DOAR</button>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($cardapio): ?>
        <div class="modal-doar" id="modalDoar">
            <div class="modal-doar-caixa">
                <button type="button" class="modal-doar-fechar" id="fecharDoar" aria-label="Fechar">&times;</button>
                <h3>Fazer uma doação</h3>
                <p class="modal-doar-texto">Escolha o ingrediente que você quer ajudar a completar.</p>
                <label for="doarIngrediente">INGREDIENTE</label>
                <select id="doarIngrediente">
                    <option value="">Selecione...</option>
                    <?php foreach ($itens as $item): ?>
                        <?php if ($item['falta'] === 0): ?>
                            <option value="" disabled><?= h($item['nome']) ?> (meta concluída)</option>
                        <?php else: ?>
                            <option value="<?= h($item['nome']) ?>" data-falta="<?= $item['falta'] ?>" data-unidade="<?= h($item['medida']) ?>"><?= h($item['nome']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <label for="doarQuantidade">QUANTIDADE</label>
                <input type="number" id="doarQuantidade" min="1" step="1" placeholder="Escolha um ingrediente" disabled>
                <small class="modal-doar-dica" id="doarDica">Disponível para doar: —</small>
                <button type="button" class="btn-pix">Gerar Pix</button>
            </div>
        </div>
    <?php endif; ?>

    <section class="doacao-contato">
        <h2>Quer doar ingredientes?</h2>
        <p>Podemos buscar na sua casa ou você pode entregar para nós. Fale com a gente para combinar:</p>
        <a href="<?= $linkWhats ?>" target="_blank" rel="noopener noreferrer" class="doacao-numero"><?= $whatsExibir ?></a>
        <a href="<?= $linkWhats ?>" target="_blank" rel="noopener noreferrer" class="doacao-whatsapp">Chamar no WhatsApp</a>
    </section>

    <?php include __DIR__ . '/../include/footer.php'; ?>

    <?php if ($cardapio): ?>
    <script>
        var modal = document.getElementById('modalDoar');
        var selectIngrediente = document.getElementById('doarIngrediente');
        var inputQuantidade = document.getElementById('doarQuantidade');
        var dica = document.getElementById('doarDica');

        document.getElementById('abrirDoar').addEventListener('click', function () {
            modal.classList.add('aberto');
        });
        document.getElementById('fecharDoar').addEventListener('click', function () {
            modal.classList.remove('aberto');
        });
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) modal.classList.remove('aberto');
        });
        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') modal.classList.remove('aberto');
        });
        selectIngrediente.addEventListener('change', function () {
            var opcao = selectIngrediente.options[selectIngrediente.selectedIndex];
            if (opcao.value === '') {
                inputQuantidade.disabled = true;
                inputQuantidade.value = '';
                inputQuantidade.placeholder = 'Escolha um ingrediente';
                dica.textContent = 'Disponível para doar: —';
                return;
            }
            inputQuantidade.disabled = false;
            inputQuantidade.value = '';
            inputQuantidade.max = opcao.dataset.falta;
            inputQuantidade.placeholder = 'Em ' + opcao.dataset.unidade;
            dica.textContent = 'Disponível para doar: até ' + opcao.dataset.falta.replace('.', ',') + ' ' + opcao.dataset.unidade;
        });
    </script>
    <?php endif; ?>
</body>
</html>
