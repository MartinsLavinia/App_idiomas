<?php
include_once __DIR__ . '/../../conexao.php';
require_once "config_esquecisenhau.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $database = new Database();
    $conn = $database->conn;

    $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        // Gera token e expiração
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Salva no banco
        $stmt_token = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expiracao = ? WHERE id = ?");
        $stmt_token->bind_param("ssi", $token, $expiracao, $usuario['id']);
        $stmt_token->execute();

        // Link de redefinição
        $reset_link = "http://localhost/public/views/redefinir_senha.php?token=$token";

        // Configura envio
        ini_set("SMTP", "smtp.seuservidor.com"); // ajuste conforme seu servidor
        $assunto = "Redefinição de Senha";
        $mensagem = "Olá {$usuario['nome']},\n\nClique no link abaixo para redefinir sua senha:\n$reset_link\n\nEste link expira em 1 hora.";

        mail($email, $assunto, $mensagem, "From: suporte@seudominio.com");

        echo "
        <script>
            alert('Um link para redefinir sua senha foi enviado para seu e-mail!');
            window.location.href = 'login.php';
        </script>";
        exit;
    } else {
        echo "
        <script>
            alert('E-mail não encontrado!');
            window.location.href = 'esqueci_senha.php';
        </script>";
        exit;
    }

    $stmt->close();
    $database->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0px 6px 20px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            box-sizing: border-box;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #2575fc;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #1b5edc;
        }
        .voltar {
            margin-top: 15px;
            display: block;
            color: #2575fc;
            text-decoration: none;
            font-size: 14px;
        }
        .voltar:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperar Senha</h2>
        <form method="POST">
            <label for="email">Digite seu e-mail cadastrado:</label>
            <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" required>
            <button type="submit">Enviar link</button>
        </form>
        <a class="voltar" href="login.php">Voltar ao login</a>
    </div>
</body>
</html>
