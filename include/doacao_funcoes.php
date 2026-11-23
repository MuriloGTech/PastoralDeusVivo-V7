<?php

function salvarDoador(PDO $pdo, array $post): array
{
    $id = (int) ($post['idDoador'] ?? 0);
    $nome = trim($post['nome'] ?? '');
    $telefone = trim($post['telefone'] ?? '');

    if ($nome === '' || mb_strlen($nome) > 255) {
        return ['ok' => false, 'erro' => 'Informe o nome de quem doou.'];
    }
    if ($telefone === '' || mb_strlen($telefone) > 45) {
        return ['ok' => false, 'erro' => 'Informe o telefone de quem doou.'];
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE tblDoador SET doaNome = :nome, doaTelefone = :telefone WHERE idDoador = :id');
        $stmt->execute([':nome' => $nome, ':telefone' => $telefone, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            $existe = $pdo->prepare('SELECT idDoador FROM tblDoador WHERE idDoador = :id');
            $existe->execute([':id' => $id]);
            if (!$existe->fetch()) {
                return ['ok' => false, 'erro' => 'Doador não encontrado.'];
            }
        }
        return ['ok' => true, 'erro' => '', 'texto' => 'Doador atualizado.'];
    }

    $stmt = $pdo->prepare('INSERT INTO tblDoador (doaNome, doaTelefone) VALUES (:nome, :telefone)');
    $stmt->execute([':nome' => $nome, ':telefone' => $telefone]);
    return ['ok' => true, 'erro' => '', 'texto' => 'Doador cadastrado.'];
}

function salvarDoacaoRecebida(PDO $pdo, array $post): array
{
    $idItem = (int) ($post['idItem'] ?? 0);
    $idDoador = (int) ($post['idDoador'] ?? 0);
    $quantidade = filter_var($post['quantidade'] ?? null, FILTER_VALIDATE_INT);

    if ($quantidade === false || $quantidade < 1 || $quantidade > 99999) {
        return ['ok' => false, 'erro' => 'Informe a quantidade recebida (1 a 99999).'];
    }

    $item = $pdo->prepare('SELECT ci.idCardapioSemana_Ingrediente
        FROM tblCardapioSemana_Ingrediente ci
        JOIN tblCardapioSemana c ON c.idCardapioSemana = ci.idCardapioSemana
        WHERE ci.idCardapioSemana_Ingrediente = :id AND c.carVigente = 1');
    $item->execute([':id' => $idItem]);
    if (!$item->fetch()) {
        return ['ok' => false, 'erro' => 'Escolha um ingrediente do cardápio da semana.'];
    }

    $doador = $pdo->prepare('SELECT idDoador FROM tblDoador WHERE idDoador = :id');
    $doador->execute([':id' => $idDoador]);
    if (!$doador->fetch()) {
        return ['ok' => false, 'erro' => 'Escolha a pessoa responsável pela doação.'];
    }

    $stmt = $pdo->prepare('INSERT INTO tblDoacao
        (idCardapioSemana_Ingrediente, idDoador, doaQuantidadeDoada, doaDataDoacao)
        VALUES (:item, :doador, :quantidade, CURDATE())');
    $stmt->execute([
        ':item' => $idItem,
        ':doador' => $idDoador,
        ':quantidade' => $quantidade,
    ]);

    return ['ok' => true, 'erro' => '', 'texto' => 'Doação registrada.'];
}
