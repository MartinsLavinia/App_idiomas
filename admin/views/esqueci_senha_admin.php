<?php
include_once __DIR__ . 
'/../../conexao.php';

$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST['nome_usuario'];

    $database = new Database();
    $conn = $database->conn;

    // Verifica se o nome de usuário existe no banco de dados
    $stmt = $conn->prepare("SELECT id FROM administradores WHERE nome_usuario = ?");
    $stmt->bind_param("s", $nome_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $id_admin = $admin['id'];

        // Gera um token único
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token válido por 1 hora

        // Armazena o token no banco de dados
        $stmt_token = $conn->prepare("UPDATE administradores SET reset_token = ?, reset_expiracao = ? WHERE id = ?");
        $stmt_token->bind_param("ssi", $token, $expiracao, $id_admin);
        $stmt_token->execute();
        $stmt_token->close();

        // Envia o email com o link de recuperação
        $link_recuperacao = "http://localhost:8000/admin/views/redefinir_senha_admin.php?token=" . $token; // Substituir por URL real
        $assunto = "Recuperação de Senha - Administração";
        $corpo_email = "Olá,\n\nVocê solicitou a recuperação de senha. Clique no link abaixo para redefinir sua senha:\n\n" . $link_recuperacao . "\n\nEste link expirará em 1 hora.\n\nAtenciosamente,\nEquipe de Administração";

        // Simulação de envio de email
        $mensagem = "Um link de recuperação de senha foi enviado para o email associado a esta conta.";
    } else {
        $mensagem = "Nome de usuário não encontrado.";
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
    <title>Esqueci a Senha - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Esqueci a Senha (Admin)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-info"><?php echo $mensagem; ?></div>
                        <?php endif; ?>
                        <form action="esqueci_senha_admin.php" method="POST">
                            <div class="mb-3">
                                <label for="nome_usuario" class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" id="nome_usuario" name="nome_usuario" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enviar Link de Recuperação</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p><a href="login_admin.php">Voltar para o Login</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
