<?php
include_once __DIR__ . 
'/../../conexao.php';

$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    $database = new Database();
    $conn = $database->conn;

    // Verifica se o email existe no banco de dados
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $id_usuario = $usuario['id'];

        // Gera um token único
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token válido por 1 hora

        // Armazena o token no banco de dados
        $stmt_token = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expiracao = ? WHERE id = ?");
        $stmt_token->bind_param("ssi", $token, $expiracao, $id_usuario);
        $stmt_token->execute();
        $stmt_token->close();

        // Envia o email com o link de recuperação
        $link_recuperacao = "http://localhost:8000/public/views/redefinir_senha.php?token=" . $token; // Substituir por URL real
        $assunto = "Recuperação de Senha - Site de Idiomas";
        $corpo_email = "Olá,\n\nVocê solicitou a recuperação de senha. Clique no link abaixo para redefinir sua senha:\n\n" . $link_recuperacao . "\n\nEste link expirará em 1 hora.\n\nAtenciosamente,\nEquipe Site de Idiomas";

        // Para enviar email, você precisaria de uma biblioteca ou configuração de SMTP
        // Exemplo (usando a função mail do PHP, que pode precisar de configuração no servidor):
        // mail($email, $assunto, $corpo_email, "From: noreply@seusite.com");
        
        $mensagem = "Um link de recuperação de senha foi enviado para o seu email.";
    } else {
        $mensagem = "Email não encontrado.";
    }

    $stmt->close();
    $database->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Esqueci a Senha</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-info"><?php echo $mensagem; ?></div>
                        <?php endif; ?>
                        <form action="esqueci_senha.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enviar Link de Recuperação</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p><a href="index.php">Voltar para o Login</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>