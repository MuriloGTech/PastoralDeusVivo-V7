<?php
session_start();
require_once __DIR__ . '/../config/connection.php';

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $senha  = $_POST['senha'] ?? '';

    if ($codigo === '' || $senha === '') {
        $erro = 'Preencha o código e a senha.';
    } else {
        $stmt = $pdo->prepare('SELECT idCliente FROM tblCliente WHERE cliCodigo = :codigo AND cliSenha = :senha LIMIT 1');
        $stmt->execute([':codigo' => $codigo, ':senha' => $senha]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['idCliente'] = $usuario['idCliente'];
            $sucesso = true;
        } else {
            $erro = 'Código ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pastoral Deus Vivo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/login.css">
</head>
<body>

    <main class="login-container">
        <div class="login-card">

            <form class="login-form" method="POST" action="login.php">
                <h1>Área Administrativa</h1>
                <p class="subtitulo">Entre com seu código e senha</p>

                <label for="codigo">CÓDIGO</label>
                <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">

                <label for="senha">SENHA</label>
                <input type="password" id="senha" name="senha" required>

                <button type="submit">ENTRAR</button>

            </form>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($sucesso): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Login realizado!',
            text: 'Seja Bem-vindo',
            timer: 1800,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = '<?= BASE_URL ?>admin/index.php';
        });
    </script>
    <?php elseif ($erro): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: <?= json_encode($erro) ?>,
            confirmButtonText: 'Tentar novamente',
            confirmButtonColor: '#52B1FF'
        });
    </script>
    <?php endif; ?>

</body>
</html>
