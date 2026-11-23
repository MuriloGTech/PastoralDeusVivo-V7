<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$consulta = $pdo->prepare('SELECT r.*, l.locNome, b.barNome, c.cidNome, rel.relData,
        e.infEscolaridade, e.infoEstadoCivil, e.`infoRaça` AS infoRaca, e.infoProfissao, e.infoNaturalidade
    FROM tblReabilitado r
    JOIN tblLocal l ON l.idLocal = r.idLocal
    JOIN tblBairro b ON b.idBairro = l.idBairro
    JOIN tblCidade c ON c.idCidade = b.idCidade
    JOIN tblRelatorio rel ON rel.idRelatorio = r.idRelatorio
    LEFT JOIN tblInfoExtraReabilitado e ON e.idReabilitado = r.idReabilitado
    WHERE r.idReabilitado = :id');
$consulta->execute([':id' => $id]);
$pessoa = $consulta->fetch();
$arquivo = $pessoa ? preg_replace('/[^a-z0-9]+/i', '-', $pessoa['reaApelido']) : 'reabilitado';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reabilitado <?= $pessoa ? h($pessoa['reaApelido']) : '' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <style>
        .barra-pdf { margin-left: 250px; padding: 72px 32px 0; display: flex; gap: 10px; }
        body.menu-fechado .barra-pdf, body.menu-fechado .documento { margin-left: 0; }
        
        .documento { 
            margin: 16px 32px 32px 282px; 
            background: #fff; 
            border-radius: 10px; 
            padding: 28px; 
            max-width: 800px; 
            color: #2c3e50; 
            box-sizing: border-box;
        }
        
        .documento h1 { font-size: 24px; margin-bottom: 4px; color: #1a252f; }
        .documento .subtitulo-ficha { font-size: 14px; color: #7f8c8d; margin-bottom: 16px; }
        .documento h2 { font-size: 15px; margin: 18px 0 6px; color: #2980b9; border-bottom: 1px solid #eaeaea; padding-bottom: 4px; }
        .documento p { margin: 4px 0; font-size: 13px; line-height: 1.4; }
        .documento img { max-width: 180px; height: auto; margin-top: 8px; border-radius: 6px; border: 1px solid #ddd; display: block; }
        
        .btn-pdf, .btn-voltar { border: none; border-radius: 6px; color: #fff; font-weight: 700; padding: 11px 16px; text-decoration: none; background: #2980b9; cursor: pointer; }
        .btn-voltar { background: #5d6d7e; }

        @media print { 
            .btn-menu, .menu-lateral, .barra-pdf { display: none !important; } 
            .documento { margin: 0; box-shadow: none; width: 100%; max-width: none; } 
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>
    <div class="barra-pdf">
        <a class="btn-voltar" href="<?= BASE_URL ?>admin/reabilitados/listagem.php">Voltar</a>
        <?php if ($pessoa): ?><button type="button" class="btn-pdf" id="baixarPdf">Gerar PDF</button><?php endif; ?>
    </div>
    
    <?php if (!$pessoa): ?>
        <article class="documento"><h1>Cadastro não encontrado</h1></article>
    <?php else: ?>
        <article class="documento" id="documento">
            <h1>Pastoral Deus Vivo</h1>
            <p class="subtitulo-ficha">Ficha do morador em reabilitação</p>
            
            <?php if ($pessoa['reaDocumento']): ?>
                <p><strong>Documento / RG:</strong></p>
                <img src="<?= BASE_URL . h($pessoa['reaDocumento']) ?>" alt="RG de <?= h($pessoa['reaApelido']) ?>" crossorigin="anonymous">
            <?php endif; ?>
            
            <h2>Dados principais</h2>
            <p><strong>Nome:</strong> <?= $pessoa['reaNome'] ? h($pessoa['reaNome']) : 'Não informado' ?></p>
            <p><strong>Apelido:</strong> <?= h($pessoa['reaApelido']) ?></p>
            <p><strong>Idade:</strong> <?= (int) $pessoa['reaIdade'] ?></p>
            <p><strong>CPF:</strong> <?= formatarCpf($pessoa['reaCpf']) ?></p>
            <p><strong>Telefone:</strong> <?= formatarTelefone($pessoa['reaTelefone']) ?></p>
            <p><strong>Relatório:</strong> Nº <?= (int) $pessoa['idRelatorio'] ?> - <?= formatoData($pessoa['relData']) ?></p>
            <p><strong>Cidade:</strong> <?= h($pessoa['cidNome']) ?></p>
            <p><strong>Bairro:</strong> <?= h($pessoa['barNome']) ?></p>
            <p><strong>Local encontrado:</strong> <?= h($pessoa['locNome']) ?></p>
            
            <h2>Informações adicionais</h2>
            <p><strong>Escolaridade:</strong> <?= h($pessoa['infEscolaridade'] ?: 'Não informado') ?></p>
            <p><strong>Estado civil:</strong> <?= h($pessoa['infoEstadoCivil'] ?: 'Não informado') ?></p>
            <p><strong>Raça:</strong> <?= h($pessoa['infoRaca'] ?: 'Não informado') ?></p>
            <p><strong>Profissão:</strong> <?= h($pessoa['infoProfissao'] ?: 'Não informado') ?></p>
            <p><strong>Naturalidade:</strong> <?= h($pessoa['infoNaturalidade'] ?: 'Não informado') ?></p>
        </article>
        
        <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
        <script>
            document.getElementById('baixarPdf').addEventListener('click', function () {
                if (!window.html2pdf) {
                    window.print();
                    return;
                }

                const elemento = document.getElementById('documento');

                const opcoes = {
                    margin:       [10, 10, 10, 10], // Margens em milímetros (Superior, Esquerda, Inferior, Direita)
                    filename:     <?= json_encode('reabilitado-' . strtolower($arquivo) . '.pdf') ?>,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { 
                        scale: 2,           // Melhora a nitidão do texto
                        useCORS: true,      // Permite carregar imagens hospedadas no mesmo domínio/servidor
                        letterRendering: true 
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opcoes).from(elemento).save();
            });
        </script>
    <?php endif; ?>
</body>
</html>