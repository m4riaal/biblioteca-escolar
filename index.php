<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION["nome"] ?? "Usuário";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Painel | Biblioteca Escolar</title>

    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

    <header class="cabecalho">
        <h1>📚 Biblioteca Escolar</h1>

        <div>
            Olá, <?= htmlspecialchars($nome, ENT_QUOTES, "UTF-8") ?>!
            <a class="botao-sair" href="sair.php">Sair</a>
        </div>
    </header>

    <main class="painel">
        <h2>Painel principal</h2>

        <p>Bem-vindo ao sistema de gerenciamento da biblioteca.</p>

        <section class="cartoes">

            <a href="livros.php" class="cartao">
                <span>📖</span>
                <h3>Livros</h3>
                <p>Cadastrar e consultar livros.</p>
            </a>

            <a href="emprestimos.php" class="cartao">
                <span>📝</span>
                <h3>Empréstimos</h3>
                <p>Registrar e consultar empréstimos.</p>
            </a>

            <a href="usuarios.php" class="cartao">
                <span>👥</span>
                <h3>Usuários</h3>
                <p>Consultar usuários cadastrados.</p>
            </a>

        </section>
    </main>

</body>
</html>