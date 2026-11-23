<?php

function processarReabilitado(PDO $pdo, array $post, array $files): array
{
    $id = (int) ($post['id'] ?? 0);
    $nome = trim($post['nome'] ?? '');
    $apelido = trim($post['apelido'] ?? '');
    $idade = (int) ($post['idade'] ?? 0);
    $cpf = preg_replace('/\D/', '', $post['cpf'] ?? '');
    $telefone = preg_replace('/\D/', '', $post['telefone'] ?? '');
    $idRelatorio = (int) ($post['idRelatorio'] ?? 0);
    $idCidade = (int) ($post['idCidade'] ?? 0);
    $idBairro = (int) ($post['idBairro'] ?? 0);
    $idLocal = (int) ($post['idLocal'] ?? 0);

    if ($apelido === '' || mb_strlen($apelido) > 15) {
        return ['ok' => false, 'erro' => 'Informe o apelido (até 15 letras).'];
    }
    if (mb_strlen($nome) > 80) {
        return ['ok' => false, 'erro' => 'O nome pode ter no máximo 80 letras.'];
    }
    if ($idade < 1 || $idade > 99) {
        return ['ok' => false, 'erro' => 'A idade deve ter no máximo 2 dígitos.'];
    }
    if ($cpf !== '' && strlen($cpf) !== 11) {
        return ['ok' => false, 'erro' => 'O CPF deve ter 11 números.'];
    }
    if ($telefone !== '' && strlen($telefone) !== 11) {
        return ['ok' => false, 'erro' => 'O telefone deve ter 11 números (DDD + número).'];
    }

    $relatorio = $pdo->prepare('SELECT idRelatorio FROM tblRelatorio WHERE idRelatorio = :id');
    $relatorio->execute([':id' => $idRelatorio]);
    if (!$relatorio->fetch()) {
        return ['ok' => false, 'erro' => 'Escolha o relatório correspondente.'];
    }

    $local = $pdo->prepare('SELECT l.idLocal FROM tblLocal l
        JOIN tblBairro b ON b.idBairro = l.idBairro
        WHERE l.idLocal = :local AND b.idBairro = :bairro AND b.idCidade = :cidade');
    $local->execute([':local' => $idLocal, ':bairro' => $idBairro, ':cidade' => $idCidade]);
    if (!$local->fetch()) {
        return ['ok' => false, 'erro' => 'Escolha a cidade, o bairro e o local encontrado.'];
    }

    $atual = null;
    if ($id > 0) {
        $busca = $pdo->prepare('SELECT idReabilitado, reaDocumento FROM tblReabilitado WHERE idReabilitado = :id');
        $busca->execute([':id' => $id]);
        $atual = $busca->fetch();
        if (!$atual) {
            return ['ok' => false, 'erro' => 'Cadastro não encontrado.'];
        }
    }

    $foto = salvarImagem($files['foto'] ?? [], 'img/documentos', false);
    if (!$foto['ok']) {
        return ['ok' => false, 'erro' => $foto['erro']];
    }
    $caminho = $foto['caminho'] !== '' ? $foto['caminho'] : (string) ($atual['reaDocumento'] ?? '');

    try {
        $valores = [
            ':idRelatorio' => $idRelatorio,
            ':idLocal' => $idLocal,
            ':nome' => $nome !== '' ? $nome : null,
            ':apelido' => $apelido,
            ':idade' => $idade,
            ':cpf' => $cpf !== '' ? $cpf : null,
            ':telefone' => $telefone !== '' ? $telefone : null,
            ':documento' => $caminho,
        ];

        if ($id > 0) {
            $valores[':id'] = $id;
            $stmt = $pdo->prepare('UPDATE tblReabilitado SET
                idRelatorio = :idRelatorio, idLocal = :idLocal, reaNome = :nome, reaApelido = :apelido,
                reaIdade = :idade, reaCpf = :cpf, reaTelefone = :telefone, reaDocumento = :documento
                WHERE idReabilitado = :id');
            $stmt->execute($valores);
            if ($foto['caminho'] !== '' && $atual['reaDocumento'] !== '' && $atual['reaDocumento'] !== $foto['caminho']) {
                apagarArquivoProjeto($atual['reaDocumento']);
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO tblReabilitado
                (idRelatorio, idLocal, reaNome, reaApelido, reaIdade, reaCpf, reaTelefone, reaDocumento)
                VALUES (:idRelatorio, :idLocal, :nome, :apelido, :idade, :cpf, :telefone, :documento)');
            $stmt->execute($valores);
            $id = (int) $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        if ($foto['caminho'] !== '') {
            apagarArquivoProjeto($foto['caminho']);
        }
        return ['ok' => false, 'erro' => 'Não foi possível salvar. Confira o relatório e o local escolhidos.'];
    }

    return ['ok' => true, 'erro' => '', 'id' => $id];
}

function salvarInfoExtra(PDO $pdo, array $post): array
{
    $id = (int) ($post['id'] ?? 0);
    $existe = $pdo->prepare('SELECT idReabilitado FROM tblReabilitado WHERE idReabilitado = :id');
    $existe->execute([':id' => $id]);
    if (!$existe->fetch()) {
        return ['ok' => false, 'erro' => 'Cadastro não encontrado.'];
    }

    $campos = [
        'escolaridade' => trim($post['escolaridade'] ?? ''),
        'estadoCivil' => trim($post['estadoCivil'] ?? ''),
        'raca' => trim($post['raca'] ?? ''),
        'profissao' => trim($post['profissao'] ?? ''),
        'naturalidade' => trim($post['naturalidade'] ?? ''),
    ];
    foreach ($campos as $chave => $valor) {
        if (mb_strlen($valor) > 45) {
            return ['ok' => false, 'erro' => 'Cada informação adicional pode ter no máximo 45 letras.'];
        }
        $campos[$chave] = $valor === '' ? null : $valor;
    }

    $busca = $pdo->prepare('SELECT idInfoExtraReabilitados FROM tblInfoExtraReabilitado WHERE idReabilitado = :id LIMIT 1');
    $busca->execute([':id' => $id]);
    $extra = $busca->fetch();

    $valores = [
        ':escolaridade' => $campos['escolaridade'],
        ':estadoCivil' => $campos['estadoCivil'],
        ':raca' => $campos['raca'],
        ':profissao' => $campos['profissao'],
        ':naturalidade' => $campos['naturalidade'],
        ':id' => $id,
    ];

    if ($extra) {
        $stmt = $pdo->prepare('UPDATE tblInfoExtraReabilitado SET
            infEscolaridade = :escolaridade, infoEstadoCivil = :estadoCivil, `infoRaça` = :raca,
            infoProfissao = :profissao, infoNaturalidade = :naturalidade
            WHERE idReabilitado = :id');
    } else {
        $stmt = $pdo->prepare('INSERT INTO tblInfoExtraReabilitado
            (idReabilitado, infEscolaridade, infoEstadoCivil, `infoRaça`, infoProfissao, infoNaturalidade)
            VALUES (:id, :escolaridade, :estadoCivil, :raca, :profissao, :naturalidade)');
    }
    $stmt->execute($valores);

    return ['ok' => true, 'erro' => ''];
}

function excluirReabilitado(PDO $pdo, int $id): array
{
    $busca = $pdo->prepare('SELECT reaDocumento FROM tblReabilitado WHERE idReabilitado = :id');
    $busca->execute([':id' => $id]);
    $registro = $busca->fetch();
    if (!$registro) {
        return ['ok' => false, 'erro' => 'Cadastro não encontrado.'];
    }

    try {
        $pdo->beginTransaction();
        $extra = $pdo->prepare('DELETE FROM tblInfoExtraReabilitado WHERE idReabilitado = :id');
        $extra->execute([':id' => $id]);
        $stmt = $pdo->prepare('DELETE FROM tblReabilitado WHERE idReabilitado = :id');
        $stmt->execute([':id' => $id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'erro' => 'Não foi possível excluir o cadastro.'];
    }

    if ($registro['reaDocumento'] !== '') {
        apagarArquivoProjeto($registro['reaDocumento']);
    }
    return ['ok' => true, 'erro' => ''];
}
