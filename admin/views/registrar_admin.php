<?php
include_once __DIR__ . '/../models/AdminManager.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST['nome_usuario'];
    $senha = $_POST['senha'];

    $database = new Database();
    $adminManager = new AdminManager($database);

    if ($adminManager->registerAdmin($nome_usuario, $senha)) {
        header("Location: gerenciar_caminho.php");
        exit();
    } else {
        echo "<script>
            alert('Nome de usuário ou senha incorretos.');
            window.location.href = 'login_admin.php';
        </script>";
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

    <style>
    :root {
        --purple-main: #581c87;
        --purple-light: #7e22ce;
        --yellow-accent: #fcd34d;
        --white-text: #fff;
        --dark-text: #333;
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

    .waves {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 15vh;
        min-height: 100px;
        z-index: 2;
    }

    .parallax>use {
        animation: move-wave 25s cubic-bezier(.55, .5, .45, .5) infinite;
    }

    .parallax>use:nth-child(1) {
        animation-delay: -2s;
        animation-duration: 7s;
    }

    .parallax>use:nth-child(2) {
        animation-delay: -3s;
        animation-duration: 10s;
    }

    .parallax>use:nth-child(3) {
        animation-delay: -4s;
        animation-duration: 13s;
    }

    @keyframes move-wave {
        0% {
            transform: translate3d(-90px, 0, 0);
        }

        100% {
            transform: translate3d(85px, 0, 0);
        }
    }

    .form-container {
        position: relative;
        z-index: 3;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        text-align: center;
        margin: 20px auto;
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
        margin-bottom: 1rem;
        font-size: 2rem;
        font-weight: 700;
    }

    .input-group,
    .mb-3 {
        margin-bottom: 1rem;
    }

    input.form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 8px;
        font-size: 1rem;
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--white-text);
    }

    input.form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    input.form-control:focus {
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

    a {
        color: var(--yellow-accent);
        text-decoration: none;
    }

    a:hover {
        color: #fde047;
    }
    </style>
</head>

<body>
    <div class="background-container"></div>

    <h1
        style="color: #fff; font-size: 2rem; font-weight: 800; margin-bottom: 100px; text-align: center; z-index: 3; position: relative;">
        Área do Administrador
    </h1>

    <div class="form-container">
        <img src="../../imagens/logo-idiomas.png" alt="Logo"
            style="width: 150px; display: block; margin: 0 auto 20px auto;">
        <h2>Cadastro de Novo Administrador(a)</h2>

        <form action="registrar_admin.php" method="POST">
            <div class="mb-3">
                <input type="text" class="form-control" id="nome_usuario" name="nome_usuario"
                    placeholder="Nome de Usuário" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <span id="btnText">Cadastrar</span>
                <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status"
                    aria-hidden="true"></span>
            </button>
        </form>

        <div class="text-center mt-3">
            <p>Já tem uma conta? <a href="login_admin.php" style="font-weight: bold;">Faça o Login aqui</a></p>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector("form").addEventListener("submit", function() {
        const btnLogin = document.getElementById("btnLogin");
        const btnText = document.getElementById("btnText");
        const btnSpinner = document.getElementById("btnSpinner");

        btnText.textContent = "Cadastrando...";
        btnSpinner.classList.remove("d-none");
        btnLogin.disabled = true;
    });
    </script>
</body>

</html>