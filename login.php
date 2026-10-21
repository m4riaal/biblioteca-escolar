<?php
session_start();
require_once "conexao.php";

$erro = "";
$cadastroSucesso = isset($_GET["cadastro"]) &&
                   $_GET["cadastro"] === "sucesso";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    $sql = "SELECT id_usuario, nome, email, senha
            FROM Usuario
            WHERE email = :email
            LIMIT 1";

    $consulta = $conexao->prepare($sql);
    $consulta->execute(["email" => $email]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario["senha"])) {
        session_regenerate_id(true);

        $_SESSION["id_usuario"] = $usuario["id_usuario"];
        $_SESSION["nome"] = $usuario["nome"];
        $_SESSION["email"] = $usuario["email"];

        header("Location: index.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Biblioteca Escolar</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body class="pagina-login">

    <main class="caixa-login">
        <h1>📚 Biblioteca Escolar</h1>
        <p>Entre para acessar o sistema.</p>

        <?php if ($erro !== ""): ?>
            <p class="mensagem-erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <?php if ($cadastroSucesso): ?>
            <p style="color: #067647;">
               Cadastro realizado com sucesso! Agora entre com seu e-mail e senha.
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Digite seu e-mail"
                required
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                required
            >

            <button type="submit">Entrar</button>
        </form>
        <p style="margin-top: 20px; margin-bottom: 0;">
            Ainda não tem uma conta?
            <a href="cadastro.php">Cadastre-se aqui</a>
        </p>
    </main>

</body>
</html>