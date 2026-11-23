<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';
require_once __DIR__ . '/../../include/reabilitado_funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['acao'] ?? '') === 'excluir') {
        $resultado = excluirReabilitado($pdo, (int) ($_POST['id'] ?? 0));
        flashDefinir($resultado['ok'] ? 'success' : 'error', $resultado['ok'] ? 'Cadastro excluído.' : $resultado['erro']);
    } else {
        $resultado = salvarInfoExtra($pdo, $_POST);
        flashDefinir($resultado['ok'] ? 'success' : 'error', $resultado['ok'] ? 'Informações salvas.' : $resultado['erro']);
    }
    header('Location: ' . BASE_URL . 'admin/reabilitados/listagem.php');
    exit;
}

$flash = flashLer();
$reabilitados = $pdo->query('SELECT r.idReabilitado, r.reaNome, r.reaApelido, r.reaIdade, r.reaCpf, r.reaTelefone, r.reaDocumento,
        l.locNome, c.cidNome, rel.idRelatorio, rel.relData,
        e.infEscolaridade, e.infoEstadoCivil, e.`infoRaça` AS infoRaca, e.infoProfissao, e.infoNaturalidade
    FROM tblReabilitado r
    JOIN tblLocal l ON l.idLocal = r.idLocal
    JOIN tblBairro b ON b.idBairro = l.idBairro
    JOIN tblCidade c ON c.idCidade = b.idCidade
    JOIN tblRelatorio rel ON rel.idRelatorio = r.idRelatorio
    LEFT JOIN tblInfoExtraReabilitado e ON e.idReabilitado = r.idReabilitado
    ORDER BY r.idReabilitado DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Reabilitados - Pastoral Deus Vivo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/listagem.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>
    <div class="conteudo-admin">
        <div class="topo-pagina">
            <h1>Reabilitados cadastrados</h1>
            <a href="<?= BASE_URL ?>admin/reabilitados/reabilitados.php" class="btn-novo">+ Novo cadastro</a>
        </div>

        <?php if (!$reabilitados): ?>
            <p class="vazio">Nenhum reabilitado cadastrado ainda.</p>
        <?php else: ?>
            <div class="tabela-caixa">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>FOTO</th><th>NOME</th><th>APELIDO</th><th>IDADE</th><th>CPF</th>
                            <th>TELEFONE</th><th>CIDADE</th><th>LOCAL</th><th>RELATÓRIO</th><th>AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reabilitados as $pessoa): ?>
                            <tr>
                                <td>
                                    <?php if ($pessoa['reaDocumento']): ?>
                                        <img src="<?= BASE_URL . h($pessoa['reaDocumento']) ?>" alt="RG de <?= h($pessoa['reaApelido']) ?>" class="foto-mini">
                                    <?php endif; ?>
                                </td>
                                <td><?= $pessoa['reaNome'] ? h($pessoa['reaNome']) : '—' ?></td>
                                <td><?= h($pessoa['reaApelido']) ?></td>
                                <td><?= (int) $pessoa['reaIdade'] ?></td>
                                <td class="sem-quebra"><?= formatarCpf($pessoa['reaCpf']) ?></td>
                                <td class="sem-quebra"><?= formatarTelefone($pessoa['reaTelefone']) ?></td>
                                <td><?= h($pessoa['cidNome']) ?></td>
                                <td><?= h($pessoa['locNome']) ?></td>
                                <td class="sem-quebra">Nº <?= (int) $pessoa['idRelatorio'] ?> - <?= formatoData($pessoa['relData']) ?></td>
                                <td>
                                    <div class="acoes-linha">
                                        <a class="btn-editar-linha" href="<?= BASE_URL ?>admin/reabilitados/reabilitados.php?id=<?= (int) $pessoa['idReabilitado'] ?>">Editar</a>
                                        <button type="button" class="btn-excluir" data-id="<?= (int) $pessoa['idReabilitado'] ?>" data-apelido="<?= h($pessoa['reaApelido']) ?>">Excluir</button>
                                        <a href="<?= BASE_URL ?>admin/reabilitados/pdf.php?id=<?= (int) $pessoa['idReabilitado'] ?>" target="_blank" rel="noopener">Gerar PDF</a>
                                        <button type="button" class="btn-mais"
                                            data-id="<?= (int) $pessoa['idReabilitado'] ?>"
                                            data-apelido="<?= h($pessoa['reaApelido']) ?>"
                                            data-escolaridade="<?= h($pessoa['infEscolaridade']) ?>"
                                            data-estadocivil="<?= h($pessoa['infoEstadoCivil']) ?>"
                                            data-raca="<?= h($pessoa['infoRaca']) ?>"
                                            data-profissao="<?= h($pessoa['infoProfissao']) ?>"
                                            data-naturalidade="<?= h($pessoa['infoNaturalidade']) ?>">Mais informações</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal" id="modalFoto">
        <button type="button" class="modal-fechar" id="modalFechar" aria-label="Fechar">&times;</button>
        <img src="" alt="Documento ampliado" id="modalImagem">
        <button type="button" class="modal-girar" id="modalGirar">&#8635; Girar</button>
    </div>

    <div class="modal-painel" id="modalExtra">
        <form class="caixa-extra" method="post" id="formExtra">
            <button type="button" class="fechar-extra" id="fecharExtra" aria-label="Fechar">&times;</button>
            <h2>Mais informações</h2>
            <p id="extraTitulo"></p>
            <input type="hidden" name="acao" value="extra">
            <input type="hidden" name="id" id="extraId">
            <div class="grade-extra">
                <label>ESCOLARIDADE<input type="text" name="escolaridade" maxlength="45" list="listaEscolaridade"></label>
                <label>ESTADO CIVIL<input type="text" name="estadoCivil" maxlength="45" list="listaEstadoCivil"></label>
                <label>RAÇA<input type="text" name="raca" maxlength="45" list="listaRaca"></label>
                <label>PROFISSÃO<input type="text" name="profissao" maxlength="45"></label>
                <label class="largo">NATURALIDADE / NATURALIZADO<input type="text" name="naturalidade" maxlength="45" placeholder="Cidade/UF ou naturalizado"></label>
            </div>
            <datalist id="listaEscolaridade">
                <option value="Ensino fundamental incompleto"></option>
                <option value="Ensino fundamental completo"></option>
                <option value="Ensino médio incompleto"></option>
                <option value="Ensino médio completo"></option>
                <option value="Ensino superior"></option>
            </datalist>
            <datalist id="listaEstadoCivil">
                <option value="Solteiro"></option>
                <option value="Casado"></option>
                <option value="União estável"></option>
                <option value="Divorciado"></option>
                <option value="Viúvo"></option>
            </datalist>
            <datalist id="listaRaca">
                <option value="Branca"></option>
                <option value="Preta"></option>
                <option value="Parda"></option>
                <option value="Amarela"></option>
                <option value="Indígena"></option>
            </datalist>
            <button type="submit">Salvar informações</button>
        </form>
    </div>

    <form method="post" id="formExcluir">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="idExcluir">
    </form>

    <script src="<?= BASE_URL ?>js/admin/listagem.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>
