<?php

function ingredientesPorId(PDO $pdo): array
{
    $lista = $pdo->query('SELECT idIngrediente, ingNome, ingPreco, ingQuantidade, ingMedida FROM tblIngrediente ORDER BY ingNome')->fetchAll();
    $mapa = [];
    foreach ($lista as $item) {
        $mapa[(int) $item['idIngrediente']] = $item;
    }
    return $mapa;
}

function salvarNovoCardapio(PDO $pdo, array $post, array $files): array
{
    $titulo = trim($post['titulo'] ?? '');
    $marmitas = (int) ($post['marmitas'] ?? 0);
    $ids = $post['ingrediente'] ?? [];
    $quantidades = $post['qtdIngrediente'] ?? [];

    if ($titulo === '' || mb_strlen($titulo) > 45) {
        return ['ok' => false, 'erro' => 'Informe o título do cardápio (até 45 letras).'];
    }
    if ($marmitas < 1 || $marmitas > 999) {
        return ['ok' => false, 'erro' => 'A quantidade estimada de marmitas deve ficar entre 1 e 999.'];
    }
    if (!is_array($ids) || !is_array($quantidades)) {
        return ['ok' => false, 'erro' => 'Inclua pelo menos um ingrediente.'];
    }

    $mapa = ingredientesPorId($pdo);
    $itens = [];
    $vistos = [];
    $custoTotal = 0.0;

    foreach ($ids as $indice => $idBruto) {
        $id = (int) $idBruto;
        $qtd = (int) ($quantidades[$indice] ?? 0);
        if ($id < 1 && $qtd < 1) {
            continue;
        }
        if (!isset($mapa[$id])) {
            return ['ok' => false, 'erro' => 'Escolha um ingrediente cadastrado.'];
        }
        if ($qtd < 1 || $qtd > 99999) {
            return ['ok' => false, 'erro' => 'Informe a quantidade usada de cada ingrediente (1 a 99999).'];
        }
        if (isset($vistos[$id])) {
            return ['ok' => false, 'erro' => 'Cada ingrediente pode aparecer uma vez no cardápio.'];
        }
        $vistos[$id] = true;
        $ing = $mapa[$id];
        $custoTotal += custoLinha((float) $ing['ingPreco'], (float) $ing['ingQuantidade'], (float) $qtd);
        $itens[] = ['id' => $id, 'qtd' => $qtd];
    }

    if (!$itens) {
        return ['ok' => false, 'erro' => 'Inclua pelo menos um ingrediente.'];
    }

    $foto = salvarImagem($files['foto'] ?? [], 'img/cardapios', true);
    if (!$foto['ok']) {
        return ['ok' => false, 'erro' => $foto['erro']];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO tblCardapioSemana
            (carTitulo, carFotoCardapio, carQuantiadeMarmitaEsperada, carPreco)
            VALUES (:titulo, :foto, :marmitas, :preco)');
        $stmt->execute([
            ':titulo' => $titulo,
            ':foto' => $foto['caminho'],
            ':marmitas' => $marmitas,
            ':preco' => round($custoTotal / $marmitas, 2),
        ]);
        $idCardapio = (int) $pdo->lastInsertId();

        $item = $pdo->prepare('INSERT INTO tblCardapioSemana_Ingrediente
            (idCardapioSemana, idIngrediente, caiQuantidadeTotal)
            VALUES (:cardapio, :ingrediente, :quantidade)');
        foreach ($itens as $linha) {
            $item->execute([
                ':cardapio' => $idCardapio,
                ':ingrediente' => $linha['id'],
                ':quantidade' => $linha['qtd'],
            ]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        apagarArquivoProjeto($foto['caminho']);
        return ['ok' => false, 'erro' => 'Não foi possível salvar o cardápio.'];
    }

    return ['ok' => true, 'erro' => ''];
}

function tornarCardapioDaSemana(PDO $pdo, int $id): array
{
    $busca = $pdo->prepare('SELECT idCardapioSemana FROM tblCardapioSemana WHERE idCardapioSemana = :id');
    $busca->execute([':id' => $id]);
    if (!$busca->fetch()) {
        return ['ok' => false, 'erro' => 'Cardápio não encontrado.'];
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec('UPDATE tblCardapioSemana SET carVigente = 0');
        $stmt = $pdo->prepare('UPDATE tblCardapioSemana SET carVigente = 1, carDataUso = CURDATE() WHERE idCardapioSemana = :id');
        $stmt->execute([':id' => $id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'erro' => 'Não foi possível atualizar o cardápio da semana.'];
    }

    return ['ok' => true, 'erro' => ''];
}
