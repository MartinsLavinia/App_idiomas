<?php
session_start();
ob_start(); // Garante que o header() funcione corretamente

include_once __DIR__ . '/../../conexao.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->conn;

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $nome_usuario, $senha_hash);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['nome_usuario'] = $nome_usuario;
            header("Location: painel.php");
            exit();
        } else {
            $erro_login = "Email ou senha incorretos.";
        }
    } else {
        $erro_login = "Email ou senha incorretos.";
    }

    $stmt->close();
    $database->closeConnection();
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Entrar</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($erro_login)): ?>
                            <div class="alert alert-danger"><?php echo $erro_login; ?></div>
                        <?php endif; ?>
                        <form action="index.php" method="POST" id="formLogin">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="btnLogin">
                                    <span id="btnText">Entrar</span>
                                    <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Bootstrap + JS de carregamento -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById("formLogin").addEventListener("submit", function () {
            const btnLogin = document.getElementById("btnLogin");
            const btnText = document.getElementById("btnText");
            const btnSpinner = document.getElementById("btnSpinner");

            // Mostra o spinner e atualiza o texto
            btnText.textContent = "Entrando...";
            btnSpinner.classList.remove("d-none");

            // Desativa o botão para evitar cliques duplos
            btnLogin.disabled = true;
        });
    </script>
</body>
</html>
