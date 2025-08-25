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
    <style>
     * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url('fundo-idiomas.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            font-family: Arial, sans-serif;
            color: #333;
        }

        /* Barra superior */
        header {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 20px 30px;
            position: relative;
            z-index: 10;
        }

        header button {
            padding: 12px 24px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-entrar {
            background-color: rgba(227, 213, 247, 0.8);
            color: #6b46c1;
        }

        .btn-entrar:hover {
            background-color: rgba(227, 213, 247, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-cadastrar {
            background-color: rgba(194, 145, 243, 0.8);
            color: white;
        }

        .btn-cadastrar:hover {
            background-color: rgba(194, 145, 243, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Área principal */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Logo */
        .logo-section {
            transform: translateX(100px);
            margin-bottom: 0px;
            animation: slideInFromTop 1.2s ease-out;
        }

        .logo img {
            max-width: 600px;
            //* aumentei de 500px para 600px *//
            transform: translate(100px, 80px);
            transition: transform 1s ease, opacity 1s ease;
            opacity: 1;
        }

        /* Seção inferior */
        .bottom-section {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            margin-top: 40px;
        }

        /* Mascote */
        .mascote-container {
            flex: 0 0 300px;
        }

        .mascote {
            max-width: 500px;
            /* aumentei de 300px para 400px */
            width: 100%;
            height: auto;
        }

        .welcome-section {
            flex: 1;
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            /* mantém bordas arredondadas */
            margin-left: 40px;
            box-shadow: 0 0 20px rgba(107, 70, 193, 0.6), 0 0 40px rgba(107, 70, 193, 0.4);
            backdrop-filter: blur(10px);
            border: 3px solid #6b46c1;
            max-width: 750px;
            /* largura aumentada */
            min-width: 450px;
            /* garante que não fique pequena */

            /* Animação */
            opacity: 0;
            transform: translateX(100px);
            animation: slideInFull 1.5s ease-out forwards;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: bold;
            color: #000000;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(107, 70, 193, 0.7),
                0 0 20px rgba(107, 70, 193, 0.7),
                0 0 30px rgba(107, 70, 193, 0.3);

        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: #666;
            line-height: 1.6;
        }

        @keyframes slideInFull {
            0% {
                opacity: 0;
                transform: translateX(100px) scale(0.95);
            }

            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }


        @keyframes fadeZoom {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInFromLeft {
            0% {
                opacity: 0;
                transform: translateX(-100px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInFromRight {
            0% {
                opacity: 0;
                transform: translateX(100px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes mascoteBounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes welcomePulse {

            0%,
            100% {
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }

            50% {
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            }
        }

        @keyframes titleTypewriter {
            0% {
                opacity: 0;
                width: 0;
            }

            100% {
                opacity: 1;
                width: 100%;
            }
        }

        @keyframes subtitleFadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 2.5rem;
            }

            .bottom-section {
                flex-direction: column;
                align-items: center;
                gap: 30px;
            }

            .welcome-section {
                margin-left: 0;
                margin-top: 20px;
            }

            .welcome-title {
                font-size: 1.8rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
            }

            header {
                padding: 15px 20px;
            }

            header button {
                padding: 10px 20px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
     <header>
            <button class="btn-entrar">Entrar</button>
            <button class="btn-cadastrar">Cadastre-se</button>
        </header>
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
                            <div class="mb-3 text-end">
                                <a href="esqueci_senha.php">Esqueci a senha?</a>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="btnLogin">
                                    <span id="btnText">Entrar</span>
                                    <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
<div class="d-grid gap-2 mt-3">
                                <button type="button" class="btn btn-danger" onclick="window.location.href=\'google_oauth.php?action=login\'">
                                    <img src="https://img.icons8.com/color/16/000000/google-logo.png" alt="Google logo"> Entrar com Google
                                </button>
                            </div>
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