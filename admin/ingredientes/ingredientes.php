<?php
require_once __DIR__ . '/../../include/auth_admin.php';
require_once __DIR__ . '/../../include/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'editar';
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $preco = precoParaDecimal($_POST['preco'] ?? '');
    $quantidade = trim($_POST['quantidade'] ?? '');
    $medida = trim($_POST['medida'] ?? '');

    if ($nome === '' || mb_strlen($nome) > 45) {
        $flash = ['tipo' => 'error', 'texto' => 'Informe o nome do ingrediente (até 45 letras).'];
    } elseif ($preco === null || $preco < 0 || $preco > 99999999.99) {
        $flash = ['tipo' => 'error', 'texto' => 'Informe um preço válido. Exemplo: 21,50'];
    } elseif (!ctype_digit($quantidade) || (int) $quantidade < 1 || (int) $quantidade > 99999) {
        $flash = ['tipo' => 'error', 'texto' => 'A quantidade deve ser um número inteiro de 1 a 99999.'];
    } elseif ($medida === '' || mb_strlen($medida) > 20) {
        $flash = ['tipo' => 'error', 'texto' => 'Informe a medida (até 20 letras). Exemplo: Kg'];
    } else {
        $duplicado = $pdo->prepare('SELECT idIngrediente FROM tblIngrediente WHERE ingNome = :nome AND idIngrediente <> :id LIMIT 1');
        $duplicado->execute([':nome' => $nome, ':id' => $id]);
        if ($duplicado->fetch()) {
            $flash = ['tipo' => 'error', 'texto' => 'Já existe um ingrediente com esse nome.'];
        } else {
            try {
                if ($acao === 'novo') {
                    $stmt = $pdo->prepare('INSERT INTO tblIngrediente (ingNome, ingPreco, ingQuantidade, ingMedida) VALUES (:nome, :preco, :quantidade, :medida)');
                    $stmt->execute([':nome' => $nome, ':preco' => $preco, ':quantidade' => (int) $quantidade, ':medida' => $medida]);
                    $flash = ['tipo' => 'success', 'texto' => 'Ingrediente cadastrado.'];
                } elseif ($id < 1) {
                    $flash = ['tipo' => 'error', 'texto' => 'Ingrediente inválido.'];
                } else {
                    $stmt = $pdo->prepare('UPDATE tblIngrediente SET ingNome = :nome, ingPreco = :preco, ingQuantidade = :quantidade, ingMedida = :medida WHERE idIngrediente = :id');
                    $stmt->execute([':nome' => $nome, ':preco' => $preco, ':quantidade' => (int) $quantidade, ':medida' => $medida, ':id' => $id]);
                    $flash = ['tipo' => 'success', 'texto' => 'Ingrediente atualizado com sucesso.'];
                }
            } catch (PDOException $e) {
                $flash = ['tipo' => 'error', 'texto' => 'Não foi possível salvar. Tente novamente.'];
            }
        }
    }

    $_SESSION['flash'] = $flash;
    header('Location: ' . BASE_URL . 'admin/ingredientes/ingredientes.php');
    exit;
}

$flash = flashLer();
$ingredientes = $pdo->query('SELECT idIngrediente, ingNome, ingPreco, ingQuantidade, ingMedida FROM tblIngrediente ORDER BY ingNome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingredientes - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin/ingredientes.css">
</head>
<body>
    <?php include __DIR__ . '/../../include/navbar.php'; ?>

    <div class="conteudo-admin">
        <h1>Ingredientes</h1>

        <section class="caixa-novo">
            <h2>Novo ingrediente</h2>
            <form class="form-novo" method="post">
                <input type="hidden" name="acao" value="novo">
                <label>NOME<input type="text" name="nome" maxlength="45" required></label>
                <label>PREÇO (R$)<input type="text" name="preco" inputmode="decimal" placeholder="21,50" required></label>
                <label>QUANTIDADE<input type="number" name="quantidade" min="1" max="99999" step="1" required></label>
                <label>MEDIDA<input type="text" name="medida" maxlength="20" placeholder="Kg" required></label>
                <button type="submit">Adicionar</button>
            </form>
        </section>

        <?php if (!$ingredientes): ?>
            <p class="vazio">Nenhum ingrediente cadastrado ainda.</p>
        <?php else: ?>
            <div class="busca">
                <input type="search" id="busca" placeholder="Buscar ingrediente..." autocomplete="off" aria-label="Buscar ingrediente">
            </div>
            <p class="vazio" id="semResultado" style="display: none;">Nenhum ingrediente encontrado.</p>
            <div class="lista">
                <div class="linha cabecalho">
                    <span>INGREDIENTE</span>
                    <span>PREÇO (R$)</span>
                    <span>QUANTIDADE</span>
                    <span>MEDIDA</span>
                    <span></span>
                </div>
                <?php foreach ($ingredientes as $ingrediente): ?>
                    <form class="linha" method="post">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" value="<?= (int) $ingrediente['idIngrediente'] ?>">
                        <label class="cel nome">
                            <small>INGREDIENTE</small>
                            <input type="text" name="nome" maxlength="45" value="<?= h($ingrediente['ingNome']) ?>" readonly>
                        </label>
                        <label class="cel">
                            <small>PREÇO (R$)</small>
                            <input type="text" name="preco" inputmode="decimal" value="<?= number_format($ingrediente['ingPreco'], 2, ',', '') ?>" readonly>
                        </label>
                        <label class="cel">
                            <small>QUANTIDADE</small>
                            <input type="number" name="quantidade" min="1" max="99999" step="1" value="<?= (int) $ingrediente['ingQuantidade'] ?>" readonly>
                        </label>
                        <label class="cel">
                            <small>MEDIDA</small>
                            <input type="text" name="medida" maxlength="20" value="<?= h($ingrediente['ingMedida']) ?>" readonly>
                        </label>
                        <div class="acoes">
                            <button type="button" class="btn-editar">Editar</button>
                            <button type="submit" class="btn-salvar" disabled>Salvar</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var busca = document.getElementById('busca');
        var semResultado = document.getElementById('semResultado');
        var listaCompleta = document.querySelector('.lista');

        function normalizar(texto) {
            return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        if (busca) {
            busca.addEventListener('input', function () {
                var termo = normalizar(busca.value.trim());
                var encontrados = 0;
                document.querySelectorAll('form.linha').forEach(function (linha) {
                    var nome = normalizar(linha.querySelector('input[name=nome]').value);
                    var mostrar = linha.classList.contains('editando') || nome.indexOf(termo) !== -1;
                    linha.style.display = mostrar ? '' : 'none';
                    if (mostrar) {
                        encontrados++;
                    }
                });
                semResultado.style.display = encontrados === 0 ? '' : 'none';
                listaCompleta.style.display = encontrados === 0 ? 'none' : '';
            });
        }

        document.querySelectorAll('form.linha').forEach(function (linha) {
            var campos = linha.querySelectorAll('input[type=text], input[type=number]');
            var editar = linha.querySelector('.btn-editar');
            var salvar = linha.querySelector('.btn-salvar');
            editar.addEventListener('click', function () {
                campos.forEach(function (campo) { campo.removeAttribute('readonly'); });
                salvar.disabled = false;
                editar.disabled = true;
                linha.classList.add('editando');
                campos[0].focus();
            });
        });
    </script>
    <?php include __DIR__ . '/../../include/flash.php'; ?>
</body>
</html>