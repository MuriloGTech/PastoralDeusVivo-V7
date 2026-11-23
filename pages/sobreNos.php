<?php
require_once __DIR__ . '/../config/connection.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós | Pastoral de Rua Deus Vivo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/index.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/sobreNos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/users/site.css?">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>

    <main class="sobre-nos">
        <section class="sobre-container">
            <div class="sobre-imagem">
                <img src="<?= BASE_URL ?>img/foto-tcc.jpeg" alt="Equipe e trabalho da Pastoral de Rua Deus Vivo">
            </div>
            <div class="sobre-texto">
                <h1>Sobre Nós</h1>
                <p>
                    A Pastoral de Rua Deus Vivo é uma organização social que realiza trabalho voluntário de distribuição de marmitas para pessoas em situação de rua. Para facilitar a organização, este projeto busca gerenciar as etapas dessa obra, desde a arrecadação dos alimentos até sua distribuição, contribuindo para que tudo seja realizado da melhor maneira possível.
                </p>
                <p>
                    Este projeto é um Trabalho de Conclusão de Curso (TCC) desenvolvido pelos alunos Maria Clara Borges, Murilo Gonçalves e Pedro Cassinha, do curso de Desenvolvimento de Sistemas da Etec Profº Alfredo de Barros Santos, com a mentoria do Professor Mestre Jonhson de Tarso Silva, para colocar em prática os conhecimentos adquiridos durante o curso.
                </p>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
