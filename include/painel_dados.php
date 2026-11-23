<?php

function carregarPainel(PDO $pdo, string $visao): array
{
    $visao = $visao === 'passada' ? 'passada' : 'atual';
    [$inicio, $fim] = $visao === 'passada' ? semanaPassada() : semanaAtual();
    $ini = $inicio->format('Y-m-d');
    $fimSql = $fim->format('Y-m-d');

    $dados = [
        'visao' => $visao,
        'inicio' => $inicio,
        'fim' => $fim,
        'cardapio' => null,
        'ingredientes' => [],
        'outrosCardapios' => [],
        'doacoes' => 0,
        'relatorios' => [],
        'somaFeita' => 0,
        'somaEntregue' => 0,
        'somaGuara' => 0,
        'somaAparecida' => 0,
        'locais' => [],
        'reabilitados' => [],
    ];

    if ($visao === 'atual') {
        $dados['cardapio'] = $pdo->query('SELECT idCardapioSemana, carTitulo, carFotoCardapio, carPreco, carQuantiadeMarmitaEsperada, carDataUso
            FROM tblCardapioSemana WHERE carVigente = 1 LIMIT 1')->fetch() ?: null;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM tblDoacao WHERE doaDataDoacao BETWEEN :ini AND :fim');
        $stmt->execute([':ini' => $ini, ':fim' => $fimSql]);
        $dados['doacoes'] = (int) $stmt->fetchColumn();
        return $dados;
    }

    $stmt = $pdo->prepare('SELECT idRelatorio, relData, relQuantidadeFeita, relQuantidadeEntregue, relQuantidadeGuara, relQuantidadeAparecida, idCardapioSemana
        FROM tblRelatorio WHERE relData BETWEEN :ini AND :fim ORDER BY relData DESC, idRelatorio DESC');
    $stmt->execute([':ini' => $ini, ':fim' => $fimSql]);
    $dados['relatorios'] = $stmt->fetchAll();

    $idsCardapio = [];
    foreach ($dados['relatorios'] as $relatorio) {
        $dados['somaFeita'] += (int) $relatorio['relQuantidadeFeita'];
        $dados['somaEntregue'] += (int) $relatorio['relQuantidadeEntregue'];
        $dados['somaGuara'] += (int) $relatorio['relQuantidadeGuara'];
        $dados['somaAparecida'] += (int) $relatorio['relQuantidadeAparecida'];
        $idsCardapio[(int) $relatorio['idCardapioSemana']] = true;
    }

    if (!$dados['relatorios']) {
        $card = $pdo->prepare('SELECT idCardapioSemana, carTitulo, carFotoCardapio, carPreco, carQuantiadeMarmitaEsperada, carDataUso
            FROM tblCardapioSemana
            WHERE carDataUso BETWEEN :ini AND :fim
            ORDER BY carDataUso DESC
            LIMIT 1');
        $card->execute([':ini' => $ini, ':fim' => $fimSql]);
        $dados['cardapio'] = $card->fetch() ?: null;
        if ($dados['cardapio']) {
            $dados['ingredientes'] = ingredientesDoCardapio($pdo, (int) $dados['cardapio']['idCardapioSemana']);
        }
        return $dados;
    }

    $idPrincipal = (int) $dados['relatorios'][0]['idCardapioSemana'];
    $card = $pdo->prepare('SELECT idCardapioSemana, carTitulo, carFotoCardapio, carPreco, carQuantiadeMarmitaEsperada, carDataUso
        FROM tblCardapioSemana WHERE idCardapioSemana = :id');
    $card->execute([':id' => $idPrincipal]);
    $dados['cardapio'] = $card->fetch() ?: null;
    $dados['ingredientes'] = ingredientesDoCardapio($pdo, $idPrincipal);

    if (count($idsCardapio) > 1) {
        unset($idsCardapio[$idPrincipal]);
        $ids = implode(',', array_map('intval', array_keys($idsCardapio)));
        $dados['outrosCardapios'] = $pdo->query("SELECT carTitulo FROM tblCardapioSemana WHERE idCardapioSemana IN ($ids)")->fetchAll();
    }

    $locais = $pdo->prepare('SELECT l.locNome, b.barNome, c.cidNome, e.entNumeroAvaliativo
        FROM tblRelatorio rel
        JOIN tblRelatorio_Entrega re ON re.idRelatorio = rel.idRelatorio
        JOIN tblEntrega e ON e.idEntrega = re.idEntrega
        JOIN tblLocal l ON l.idLocal = e.idLocal
        JOIN tblBairro b ON b.idBairro = l.idBairro
        JOIN tblCidade c ON c.idCidade = b.idCidade
        WHERE rel.relData BETWEEN :ini AND :fim');
    $locais->execute([':ini' => $ini, ':fim' => $fimSql]);
    $dados['locais'] = locaisComMaiorDistribuicao($locais->fetchAll());

    $reabilitados = $pdo->prepare('SELECT r.reaApelido, r.reaNome, r.reaIdade, l.locNome, c.cidNome
        FROM tblReabilitado r
        JOIN tblRelatorio rel ON rel.idRelatorio = r.idRelatorio
        JOIN tblLocal l ON l.idLocal = r.idLocal
        JOIN tblBairro b ON b.idBairro = l.idBairro
        JOIN tblCidade c ON c.idCidade = b.idCidade
        WHERE rel.relData BETWEEN :ini AND :fim
        ORDER BY r.reaApelido');
    $reabilitados->execute([':ini' => $ini, ':fim' => $fimSql]);
    $dados['reabilitados'] = $reabilitados->fetchAll();

    return $dados;
}

function ingredientesDoCardapio(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT i.ingNome, i.ingMedida, ci.caiQuantidadeTotal, c.carQuantiadeMarmitaEsperada
        FROM tblCardapioSemana_Ingrediente ci
        JOIN tblIngrediente i ON i.idIngrediente = ci.idIngrediente
        JOIN tblCardapioSemana c ON c.idCardapioSemana = ci.idCardapioSemana
        WHERE ci.idCardapioSemana = :id
        ORDER BY i.ingNome');
    $stmt->execute([':id' => $id]);
    return $stmt->fetchAll();
}

function locaisComMaiorDistribuicao(array $locais): array
{
    $unicos = [];
    foreach ($locais as $local) {
        $chave = $local['cidNome'] . '|' . $local['barNome'] . '|' . $local['locNome'];
        $nota = (int) $local['entNumeroAvaliativo'];
        if (!isset($unicos[$chave]) || $nota > (int) $unicos[$chave]['entNumeroAvaliativo']) {
            $unicos[$chave] = $local;
        }
    }

    $maior = 0;
    foreach ($unicos as $local) {
        $maior = max($maior, (int) $local['entNumeroAvaliativo']);
    }
    if ($maior < 1) {
        return [];
    }

    $destaques = [];
    foreach ($unicos as $local) {
        if ((int) $local['entNumeroAvaliativo'] === $maior) {
            $destaques[] = $local;
        }
    }
    usort($destaques, function ($a, $b) {
        return strcmp($a['locNome'], $b['locNome']);
    });
    return $destaques;
}
