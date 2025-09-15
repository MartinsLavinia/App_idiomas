<?php
include_once __DIR__ . '/../models/AdminManager.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST['nome_usuario'];
    $senha = $_POST['senha'];

    $database = new Database();
    $adminManager = new AdminManager($database);

    if ($adminManager->loginAdmin($nome_usuario, $senha)) {
        header("Location: gerenciar_caminho.php");
        exit();
    } else {
        echo "Nome de usuário ou senha incorretos.";
    }

    $database->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Variáveis de cor para o tema */
        :root {
            --purple-main: #581c87;
            --purple-light: #7e22ce;
            --pink-wave: #db2777;
            --yellow-accent: #fcd34d;
            --white-text: #fff;
            --dark-text: #333;
            --light-background: #8a4eb8;
        }

        body {
            background: var(--purple-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: var(--white-text);
            position: relative;
            overflow: hidden;
            flex-direction: column;
            padding: 20px;
        }

        .background-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--purple-main) 0%, var(--purple-light) 100%);
            z-index: 0;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .waves {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 15vh;
            min-height: 100px;
            z-index: 2;
        }

        .parallax > use {
            animation: move-wave 25s cubic-bezier(.55, .5, .45, .5) infinite;
        }
        .parallax > use:nth-child(1) {
            animation-delay: -2s;
            animation-duration: 7s;
        }
        .parallax > use:nth-child(2) {
            animation-delay: -3s;
            animation-duration: 10s;
        }
        .parallax > use:nth-child(3) {
            animation-delay: -4s;
            animation-duration: 13s;
        }

        @keyframes move-wave {
            0% { transform: translate3d(-90px, 0, 0); }
            100% { transform: translate3d(85px, 0, 0); }
        }

        /* Título da área do administrador */
        .admin-title {
            z-index: 2;
            position: relative;
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            padding-top: 50px;
            color: var(--white-text);
            right: 300px;
        }

        .form-container {
    position: relative;
    z-index: 3;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    text-align: center;
    margin: auto;
    /* Sobe o container sem afetar o cabeçalho */
    transform: translateY(-40px);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
}

        .form-container h2 {
            color: var(--white-text);
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .form-container p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            font-size: 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white-text);
        }
        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--yellow-accent);
            box-shadow: 0 0 0 2px rgba(252, 211, 77, 0.4);
        }

        .btn-login {
            background: var(--yellow-accent);
            color: var(--dark-text);
            border: none;
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-weight: bold;
            transition: background 0.2s, transform 0.2s;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #fde047;
            transform: translateY(-2px);
        }

        .links-container {
            margin-top: 1rem;
        }

        .links-container a {
            color: var(--yellow-accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .links-container a:hover {
            color: #fde047;
        }

        .social-login {
            margin-top: 1.5rem;
        }

        .social-login button {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white-text);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s, transform 0.2s;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            cursor: pointer;
        }

        .social-login button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="background-container"></div>

    <!-- Título no topo da página -->
    <h1 style="color: #fff; font-size: 2rem; font-weight: 800; margin-top: 50px; text-align: center; z-index: 3; position: relative;">
        Área do Administrador
    </h1>

    <div class="form-container">
        <!-- Logo dentro do form, centralizada -->
        <img src="../../imagens/logo-idiomas.png" alt="Logo" style="width: 150px; display: block; margin: 0 auto 20px auto;">

        <h2>Bem-vindo(a) Administrador(a)</h2>

        <form action="login_admin.php" method="POST" id="formLogin">
            <div class="input-group">
                <input type="text" id="nome_usuario" name="nome_usuario" placeholder="Nome de Usuário" required>
            </div>
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
            </div>
            <div class="links-container text-end">
                <a href="esqueci_senha_admin.php">Esqueci a senha?</a>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <span id="btnText">Entrar</span>
                <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="social-login">
            <button type="button" onclick="window.location.href='google_oauth_admin.php?action=login'">
                <img src="https://img.icons8.com/color/16/000000/google-logo.png" alt="Google logo"> Entrar com Google
            </button>
        </div>

        <div class="links-container text-center mt-3">
            <p>Não tem uma conta? <a href="registrar_admin.php">Cadastre-se aqui</a></p>
        </div>
    </div>

    <!-- Onda animada -->
    <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
        <defs>
            <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 
            58-18 88-18 58 18 88 18v44h-352z" />
        </defs>
        <g class="parallax">
            <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.3" />
            <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.2)" />
            <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.1)" />
        </g>
    </svg>
</body>

</html>
