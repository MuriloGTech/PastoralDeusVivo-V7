<?php

function salvarRelatorio(PDO $pdo, array $post, int $idCliente): array
{
    $data = $post['data'] ?? '';
    $feita = filter_var($post['produzidas'] ?? null, FILTER_VALIDATE_INT);
    $entregue = filter_var($post['entregues'] ?? null, FILTER_VALIDATE_INT);
    $guara = filter_var($post['guara'] ?? null, FILTER_VALIDATE_INT);
    $aparecida = filter_var($post['aparecida'] ?? null, FILTER_VALIDATE_INT);
    $notas = $post['nota'] ?? [];

    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    if (!$dt || $dt->format('Y-m-d') !== $data) {
        return ['ok' => false, 'erro' => 'Informe a data da atividade.'];
    }
    foreach (['produzidas' => $feita, 'entregues' => $entregue, 'Guaratinguetá' => $guara, 'Aparecida' => $aparecida] as $nome => $valor) {
        if ($valor === false || $valor < 0 || $valor > 999) {
            return ['ok' => false, 'erro' => 'As quantidades de ' . $nome . ' devem ser números de 0 a 999.'];
        }
    }
    if ($entregue > $feita) {
        return ['ok' => false, 'erro' => 'A quantidade entregue não pode ser maior que a produzida.'];
    }
    if (($guara + $aparecida) > $entregue) {
        return ['ok' => false, 'erro' => 'A soma de Guaratinguetá e Aparecida não pode passar das marmitas entregues.'];
    }

    $vigente = $pdo->query('SELECT idCardapioSemana FROM tblCardapioSemana WHERE carVigente = 1 LIMIT 1')->fetch();
    if (!$vigente) {
        return ['ok' => false, 'erro' => 'Defina o cardápio da semana antes de registrar o relatório.'];
    }

    $locais = $pdo->query('SELECT idLocal FROM tblLocal ORDER BY idLocal')->fetchAll();
    if (!$locais) {
        return ['ok' => false, 'erro' => 'Não há locais cadastrados para classificar.'];
    }

    $classificacoes = [];
    foreach ($locais as $local) {
        $idLocal = (int) $local['idLocal'];
        $nota = (string) ($notas[$idLocal] ?? '');
        if (!in_array($nota, ['1', '2', '3'], true)) {
            return ['ok' => false, 'erro' => 'Classifique todos os locais de entrega.'];
        }
        $classificacoes[$idLocal] = $nota;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO tblRelatorio
            (idCliente, idCardapioSemana, relQuantidadeFeita, relQuantidadeEntregue, relQuantidadeGuara, relQuantidadeAparecida, relData)
            VALUES (:cliente, :cardapio, :feita, :entregue, :guara, :aparecida, :data)');
        $stmt->execute([
            ':cliente' => $idCliente,
            ':cardapio' => (int) $vigente['idCardapioSemana'],
            ':feita' => $feita,
            ':entregue' => $entregue,
            ':guara' => $guara,
            ':aparecida' => $aparecida,
            ':data' => $data,
        ]);
        $idRelatorio = (int) $pdo->lastInsertId();

        $entrega = $pdo->prepare('INSERT INTO tblEntrega (idLocal, entNumeroAvaliativo) VALUES (:local, :nota)');
        $liga = $pdo->prepare('INSERT INTO tblRelatorio_Entrega (idRelatorio, idEntrega) VALUES (:relatorio, :entrega)');
        foreach ($classificacoes as $idLocal => $nota) {
            $entrega->execute([':local' => $idLocal, ':nota' => $nota]);
            $liga->execute([':relatorio' => $idRelatorio, ':entrega' => (int) $pdo->lastInsertId()]);
        }

        $uso = $pdo->prepare('UPDATE tblCardapioSemana
            SET carDataUso = CASE WHEN carDataUso IS NULL OR carDataUso < :data THEN :data ELSE carDataUso END
            WHERE idCardapioSemana = :id');
        $uso->execute([':data' => $data, ':id' => (int) $vigente['idCardapioSemana']]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'erro' => 'Não foi possível salvar o relatório.'];
    }

    return ['ok' => true, 'erro' => ''];
}
