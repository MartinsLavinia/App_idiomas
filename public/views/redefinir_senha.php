<?php
include_once __DIR__ . 
'/../../conexao.php';

$mensagem = '';
$token_valido = false;

if (isset($_GET["token"])) {
    $token = $_GET["token"];

    $database = new Database();
    $conn = $database->conn;

    // Verifica se o token é válido e não expirou
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expiracao > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valido = true;
    } else {
        $mensagem = "Token inválido ou expirado.";
    }

    $stmt->close();
    $database->closeConnection();
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST["token"];
    $nova_senha = $_POST["nova_senha"];
    $confirmar_senha = $_POST["confirmar_senha"];

    if ($nova_senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem.";
    } else {
        $database = new Database();
        $conn = $database->conn;

        // Verifica o token novamente antes de redefinir a senha
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expiracao > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            $id_usuario = $usuario["id"];

            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Atualiza a senha e limpa o token
            $stmt_update = $conn->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expiracao = NULL WHERE id = ?");
            $stmt_update->bind_param("si", $senha_hash, $id_usuario);
            $stmt_update->execute();
            $stmt_update->close();

            $mensagem = "Sua senha foi redefinida com sucesso.";
        } else {
            $mensagem = "Token inválido ou expirado.";
        }

        $stmt->close();
        $database->closeConnection();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Redefinir Senha</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-info"><?php echo $mensagem; ?></div>
                        <?php endif; ?>

                        <?php if ($token_valido): ?>
                            <form action="redefinir_senha.php" method="POST">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center">
                                <p><a href="esqueci_senha.php">Solicitar nova recuperação de senha</a></p>
                            </div>
                        <?php endif; ?>
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
