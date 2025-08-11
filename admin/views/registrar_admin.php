<?php

include_once __DIR__ . '/../models/AdminManager.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST['nome_usuario'];
    $senha = $_POST['senha'];

    $database = new Database();
    $adminManager = new AdminManager($database);

    if ($adminManager->registerAdmin($nome_usuario, $senha)) {
        echo "Administrador cadastrado com sucesso! Agora você pode fazer o login.";
    } else {
        echo "Erro ao cadastrar administrador. Tente novamente.";
    }

    $database->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow-lg" style="width: 25rem;">
            <h2 class="card-title text-center mb-4">Cadastro de Novo Administrador</h2>
            <form action="registrar_admin.php" method="POST">
                <div class="mb-3">
                    <label for="nome_usuario" class="form-label">Nome de Usuário:</label>
                    <input type="text" class="form-control" id="nome_usuario" name="nome_usuario" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
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
                            <p>Já tem uma conta? <a href="login_admin.php">Faça o Login aqui</a></p>
                        </div>

        </div>
    </div>
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