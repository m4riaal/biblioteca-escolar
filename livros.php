<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";

// Cadastrar livro
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $quantidade = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);

    if ($titulo === "" || $autor === "" || $quantidade === false || $quantidade === null || $quantidade < 0) {
        $mensagem = "Preencha os campos corretamente.";
        $tipoMensagem = "erro";
    } else {
        try {
            $sql = "INSERT INTO Livro (titulo, autor, categoria, quantidade, disponivel)
                    VALUES (:titulo, :autor, :categoria, :quantidade, :disponivel)";

            $consulta = $conexao->prepare($sql);

            $consulta->execute([
                "titulo" => $titulo,
                "autor" => $autor,
                "categoria" => $categoria,
                "quantidade" => $quantidade,
                "disponivel" => $quantidade
            ]);

            header("Location: livros.php?sucesso=1");
            exit;
        } catch (PDOException $erro) {
            $mensagem = "Não foi possível cadastrar o livro.";
            $tipoMensagem = "erro";
        }
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Livro cadastrado com sucesso!";
    $tipoMensagem = "sucesso";
}

// Buscar livros
$consulta = $conexao->query(
    "SELECT id_livro, titulo, autor, categoria, quantidade, disponivel
     FROM Livro
     ORDER BY titulo"
);

$livros = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livros | Biblioteca Escolar</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

<header class="cabecalho">
    <h1>📚 Biblioteca Escolar</h1>
    <nav>
        <a class="botao-sair" href="index.php">Voltar ao painel</a>
        <a class="botao-sair" href="sair.php">Sair</a>
    </nav>
</header>

<main class="painel">
    <h2>Cadastro de livros</h2>
    <p>Cadastre novos livros e consulte o acervo da biblioteca.</p>

    <?php if ($mensagem !== ""): ?>
        <div class="aviso <?= htmlspecialchars($tipoMensagem) ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <section class="formulario-livro">
        <h3>Novo livro</h3>

        <form method="POST" action="livros.php">
            <label for="titulo">Título do livro</label>
            <input
                type="text"
                id="titulo"
                name="titulo"
                maxlength="150"
                required
            >

            <label for="autor">Autor</label>
            <input
                type="text"
                id="autor"
                name="autor"
                maxlength="100"
                required
            >

            <label for="categoria">Categoria</label>
            <input
                type="text"
                id="categoria"
                name="categoria"
                maxlength="100"
                placeholder="Ex.: Romance, aventura, ciência"
            >

            <label for="quantidade">Quantidade de exemplares</label>
            <input
                type="number"
                id="quantidade"
                name="quantidade"
                min="0"
                value="1"
                required
            >

            <button type="submit">Cadastrar livro</button>
        </form>
    </section>

    <section class="tabela-livros">
        <h3>Livros cadastrados</h3>

        <?php if (count($livros) > 0): ?>
            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Disponíveis</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($livros as $livro): ?>
                            <tr>
                                <td><?= (int) $livro["id_livro"] ?></td>
                                <td><?= htmlspecialchars($livro["titulo"]) ?></td>
                                <td><?= htmlspecialchars($livro["autor"]) ?></td>
                                <td><?= htmlspecialchars($livro["categoria"] ?? "") ?></td>
                                <td><?= (int) $livro["quantidade"] ?></td>
                                <td><?= (int) $livro["disponivel"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhum livro cadastrado ainda.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>