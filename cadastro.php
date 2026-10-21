<?php
session_start();
require_once "conexao.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    if ($nome === "" || $email === "" || $senha === "") {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha precisa ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $erro = "As senhas não são iguais.";
    } else {
        try {
            // Verifica se o e-mail já está cadastrado
            $consulta = $conexao->prepare(
                "SELECT id_usuario FROM Usuario WHERE email = :email"
            );

            $consulta->execute(["email" => $email]);

            if ($consulta->fetch()) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                // Protege a senha antes de armazená-la
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $consulta = $conexao->prepare(
                    "INSERT INTO Usuario (nome, email, senha)
                     VALUES (:nome, :email, :senha)"
                );

                $consulta->execute([
                    "nome" => $nome,
                    "email" => $email,
                    "senha" => $senhaHash
                ]);

                header("Location: login.php?cadastro=sucesso");
                exit;
            }
        } catch (PDOException $e) {
            $erro = "Não foi possível realizar o cadastro. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro | Biblioteca Escolar</title>

    <link rel="stylesheet" href="css/estilo.css">
</head>

<body class="pagina-login">

    <main class="caixa-login">
        <h1>📚 Criar conta</h1>
        <p>Cadastre-se para acessar a Biblioteca Escolar.</p>

        <?php if ($erro !== ""): ?>
            <p class="mensagem-erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">

            <label for="nome">Nome completo</label>
            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="100"
                required
                value="<?= htmlspecialchars($_POST["nome"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >

            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
                value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="6"
                required
            >

            <label for="confirmar_senha">Confirmar senha</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="6"
                required
            >

            <button type="submit">Cadastrar</button>

        </form>

        <p style="margin-top: 20px; margin-bottom: 0;">
            Já tem uma conta?
            <a href="login.php">Entrar</a>
        </p>
    </main>

</body>
</html>