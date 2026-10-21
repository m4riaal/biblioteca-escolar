<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "conexao.php";

$consulta = $conexao->query(
    "SELECT id_usuario, nome, email
     FROM Usuario
     ORDER BY nome"
);

$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | Biblioteca Escolar</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

<header class="cabecalho">
    <h1>📚 Biblioteca Escolar</h1>

    <nav>
        <a class="botao-sair" href="index.php">Painel</a>
        <a class="botao-sair" href="livros.php">Livros</a>
        <a class="botao-sair" href="emprestimos.php">Empréstimos</a>
        <a class="botao-sair" href="sair.php">Sair</a>
    </nav>
</header>

<main class="painel">
    <h2>Usuários cadastrados</h2>
    <p>Consulte as contas registradas na Biblioteca Escolar.</p>

    <section class="tabela-livros">
        <?php if (count($usuarios) > 0): ?>

            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <?= (int) $usuario["id_usuario"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $usuario["nome"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $usuario["email"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>Nenhum usuário cadastrado.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
