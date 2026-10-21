<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";

// REGISTRAR EMPRÉSTIMO
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"] ?? "";

    try {
        if ($acao === "emprestar") {
            $idUsuario = filter_input(INPUT_POST, "id_usuario", FILTER_VALIDATE_INT);
            $idLivro = filter_input(INPUT_POST, "id_livro", FILTER_VALIDATE_INT);
            $dataDevolucao = $_POST["data_devolucao"] ?? "";

            if (!$idUsuario || !$idLivro || $dataDevolucao === "") {
                throw new Exception("Preencha todos os campos.");
            }

            $hoje = date("Y-m-d");

            if ($dataDevolucao < $hoje) {
                throw new Exception("A data prevista de devolução não pode ser anterior a hoje.");
            }

            $conexao->beginTransaction();

            // Verifica a disponibilidade do livro e bloqueia sua linha
            $consulta = $conexao->prepare(
                "SELECT quantidade, disponivel
                 FROM Livro
                 WHERE id_livro = :id
                 FOR UPDATE"
            );

            $consulta->execute(["id" => $idLivro]);
            $livro = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$livro) {
                throw new Exception("Livro não encontrado.");
            }

            if ((int)$livro["disponivel"] < 1) {
                throw new Exception("Não há exemplares disponíveis desse livro.");
            }

            // Verifica se o usuário existe
            $consulta = $conexao->prepare(
                "SELECT id_usuario FROM Usuario WHERE id_usuario = :id"
            );

            $consulta->execute(["id" => $idUsuario]);

            if (!$consulta->fetch()) {
                throw new Exception("Usuário não encontrado.");
            }

            // Registra o empréstimo
            $consulta = $conexao->prepare(
                "INSERT INTO Emprestimo
                (data_emprestimo, data_devolucao, data_devolucao_real, id_usuario, id_livro)
                VALUES (:data_emprestimo, :data_devolucao, NULL, :id_usuario, :id_livro)"
            );

            $consulta->execute([
                "data_emprestimo" => $hoje,
                "data_devolucao" => $dataDevolucao,
                "id_usuario" => $idUsuario,
                "id_livro" => $idLivro
            ]);

            // Atualiza a quantidade disponível
            $consulta = $conexao->prepare(
                "UPDATE Livro
                 SET disponivel = disponivel - 1
                 WHERE id_livro = :id"
            );

            $consulta->execute(["id" => $idLivro]);

            $conexao->commit();

            header("Location: emprestimos.php?sucesso=emprestimo");
            exit;
        }

        // REGISTRAR DEVOLUÇÃO
        if ($acao === "devolver") {
            $idEmprestimo = filter_input(
                INPUT_POST,
                "id_emprestimo",
                FILTER_VALIDATE_INT
            );

            if (!$idEmprestimo) {
                throw new Exception("Empréstimo inválido.");
            }

            $conexao->beginTransaction();

            // Bloqueia o empréstimo para evitar devolução duplicada
            $consulta = $conexao->prepare(
                "SELECT id_livro
                FROM Emprestimo
                WHERE id_emprestimo = :id
                AND data_devolucao_real IS NULL
                FOR UPDATE"
            );

            $consulta->execute(["id" => $idEmprestimo]);
            $emprestimo = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$emprestimo) {
                throw new Exception(
                    "Empréstimo não encontrado ou já devolvido."
                );
            }

            // Registra a devolução de hoje.
            // A data prevista será substituída pela data real da devolução.
            $consulta = $conexao->prepare(
                "UPDATE Emprestimo
                SET data_devolucao_real = :data
                WHERE id_emprestimo = :id"
            );

            $consulta->execute([
                "data" => date("Y-m-d"),
                "id" => $idEmprestimo
            ]);

            $consulta = $conexao->prepare(
                "UPDATE Livro
                 SET disponivel = disponivel + 1
                 WHERE id_livro = :id"
            );

            $consulta->execute(["id" => $emprestimo["id_livro"]]);

            $conexao->commit();

            header("Location: emprestimos.php?sucesso=devolucao");
            exit;
        }

        throw new Exception("Ação inválida.");

    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }

        $mensagem = $erro instanceof PDOException
            ? "Não foi possível concluir a operação. Verifique os dados e tente novamente."
            : $erro->getMessage();

        $tipoMensagem = "erro";
    }
}

if (($_GET["sucesso"] ?? "") === "emprestimo") {
    $mensagem = "Empréstimo registrado com sucesso!";
    $tipoMensagem = "sucesso";
}

if (($_GET["sucesso"] ?? "") === "devolucao") {
    $mensagem = "Devolução registrada com sucesso!";
    $tipoMensagem = "sucesso";
}

// BUSCAR USUÁRIOS
$consulta = $conexao->query(
    "SELECT id_usuario, nome, email
     FROM Usuario
     ORDER BY nome"
);
$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

// BUSCAR LIVROS
$consulta = $conexao->query(
    "SELECT id_livro, titulo, autor, disponivel
     FROM Livro
     WHERE disponivel > 0
     ORDER BY titulo"
);
$livros = $consulta->fetchAll(PDO::FETCH_ASSOC);

// BUSCAR EMPRÉSTIMOS
$consulta = $conexao->query(
    "SELECT e.id_emprestimo,
            e.data_emprestimo,
            e.data_devolucao,
            e.data_devolucao_real,
            u.nome AS nome_usuario,
            l.titulo AS titulo_livro
     FROM Emprestimo e
     INNER JOIN Usuario u ON e.id_usuario = u.id_usuario
     INNER JOIN Livro l ON e.id_livro = l.id_livro
     ORDER BY e.id_emprestimo DESC"
    );
$emprestimos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos | Biblioteca Escolar</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

<header class="cabecalho">
    <h1>📚 Biblioteca Escolar</h1>

    <nav>
        <a class="botao-sair" href="index.php">Painel</a>
        <a class="botao-sair" href="livros.php">Livros</a>
        <a class="botao-sair" href="sair.php">Sair</a>
    </nav>
</header>

<main class="painel">
    <h2>Gerenciamento de empréstimos</h2>
    <p>Registre empréstimos e acompanhe as devoluções.</p>

    <?php if ($mensagem !== ""): ?>
        <div class="aviso <?= htmlspecialchars($tipoMensagem) ?>">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="formulario-livro">
        <h3>Novo empréstimo</h3>

        <?php if (count($usuarios) > 0 && count($livros) > 0): ?>

            <form method="POST" action="emprestimos.php">
                <input type="hidden" name="acao" value="emprestar">

                <label for="id_usuario">Usuário</label>
                <select id="id_usuario" name="id_usuario" required>
                    <option value="">Selecione um usuário</option>

                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= (int)$usuario["id_usuario"] ?>">
                            <?= htmlspecialchars($usuario["nome"]) ?>
                            — <?= htmlspecialchars($usuario["email"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="id_livro">Livro</label>
                <select id="id_livro" name="id_livro" required>
                    <option value="">Selecione um livro</option>

                    <?php foreach ($livros as $livro): ?>
                        <option value="<?= (int)$livro["id_livro"] ?>">
                            <?= htmlspecialchars($livro["titulo"]) ?>
                            — Disponíveis: <?= (int)$livro["disponivel"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="data_devolucao">Data prevista de devolução</label>
                <input
                    type="date"
                    id="data_devolucao"
                    name="data_devolucao"
                    min="<?= date("Y-m-d") ?>"
                    value="<?= date("Y-m-d", strtotime("+7 days")) ?>"
                    required
                >

                <button type="submit">Registrar empréstimo</button>
            </form>

        <?php else: ?>
            <p>
                Para registrar um empréstimo, cadastre pelo menos um usuário
                e um livro disponível.
            </p>
        <?php endif; ?>
    </section>

    <section class="tabela-livros">
        <h3>Empréstimos registrados</h3>

        <?php if (count($emprestimos) > 0): ?>

            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Livro</th>
                            <th>Empréstimo</th>
                            <th>Devolução</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <tr>
                                <td>
                                    <?= (int)$emprestimo["id_emprestimo"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($emprestimo["nome_usuario"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($emprestimo["titulo_livro"]) ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($emprestimo["data_emprestimo"])) ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($emprestimo["data_devolucao"])) ?>
                                </td>

                                <td>
                                    <td>
                                        <?php if ($emprestimo["data_devolucao_real"] !== null): ?>
                                            <span class="status-ativo">Devolvido</span>
                                        <?php elseif ($emprestimo["data_devolucao"] < date("Y-m-d")): ?>
                                            <span class="status-atrasado">Atrasado</span>
                                        <?php else: ?>
                                            <span class="status-ativo">Emprestado</span>
                                        <?php endif; ?>
                                    </td>
                                </td>

                                <td>
                                    <form method="POST" action="emprestimos.php"
                                          onsubmit="return confirm('Confirmar a devolução deste livro?');">
                                        <input type="hidden" name="acao" value="devolver">
                                        <input
                                            type="hidden"
                                            name="id_emprestimo"
                                            value="<?= (int)$emprestimo["id_emprestimo"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="botao-devolver"
                                            <?= $emprestimo["data_devolucao_real"] !== null ? "disabled" : "" ?>
                                        >
                                            Devolver
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>Nenhum empréstimo registrado.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
