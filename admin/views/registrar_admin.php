<?php
include_once __DIR__ . '/../models/AdminManager.php';

// Função para verificar força da senha
function verificarForcaSenha($senha) {
    $score = 0;
    
    // Critérios de pontuação
    if (strlen($senha) >= 8) $score++;       // Comprimento mínimo
    if (preg_match('/[a-z]/', $senha)) $score++;     // Letras minúsculas
    if (preg_match('/[A-Z]/', $senha)) $score++;     // Letras maiúsculas
    if (preg_match('/[0-9]/', $senha)) $score++;     // Números
    if (preg_match('/[^a-zA-Z0-9]/', $senha)) $score++; // Caracteres especiais
    
    return $score;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST['nome_usuario'];
    $senha = $_POST['senha'];

    // Verificação da força da senha
    $forca_senha = verificarForcaSenha($senha);
    
    if ($forca_senha <= 2) {
        $erro_cadastro = "Senha muito fraca. Sua senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.";
    } else {
        $database = new Database();
        $adminManager = new AdminManager($database);

        if ($adminManager->registerAdmin($nome_usuario, $senha)) {
            header("Location: gerenciar_caminho.php");
            exit();
        } else {
            $erro_cadastro = "Erro ao cadastrar administrador. Tente novamente.";
        }
        
        $database->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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
        position: relative;
    }

    input.form-control {
        width: 100%;
        padding: 12px 45px 12px 12px;
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

    .btn-login:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-login:disabled:hover {
        background: var(--yellow-accent);
        transform: none;
    }

    a {
        color: var(--yellow-accent);
        text-decoration: none;
    }

    a:hover {
        color: #fde047;
    }

    /* Estilos para o medidor de força da senha */
    .password-strength-meter {
        margin-top: -0.5rem;
        margin-bottom: 1rem;
    }
    .strength-bar {
        height: 6px;
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
        overflow: hidden;
    }
    .strength-fill {
        height: 100%;
        width: 0;
        border-radius: 3px;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    .strength-text {
        font-size: 0.8rem;
        margin-top: 4px;
        text-align: right;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.8);
    }
    /* Cores da barra */
    .strength-weak { background-color: #dc3545; } /* Vermelho */
    .strength-medium { background-color: #ffc107; } /* Amarelo */
    .strength-strong { background-color: #198754; } /* Verde */
    /* Cores do texto */
    .text-weak { color: #dc3545 !important; }
    .text-medium { color: #ffc107 !important; }
    .text-strong { color: #198754 !important; }
    
    /* Mensagem de senha fraca */
    .weak-password-message {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: -0.5rem;
        margin-bottom: 1rem;
        text-align: center;
        font-weight: 500;
        display: none;
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.4);
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    /* Estilo para o botão de mostrar/ocultar senha */
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s ease;
        z-index: 10;
    }

    .password-toggle:hover {
        color: var(--yellow-accent);
        background: rgba(255, 255, 255, 0.1);
    }

    .password-toggle:focus {
        outline: none;
        color: var(--yellow-accent);
        background: rgba(255, 255, 255, 0.1);
    }

    .password-toggle i {
        font-size: 1.1rem;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
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

        <?php if (!empty($erro_cadastro)): ?>
        <div class="alert-danger">
            <?php echo $erro_cadastro; ?>
        </div>
        <?php endif; ?>

        <form action="registrar_admin.php" method="POST" id="form-cadastro">
            <div class="mb-3">
                <input type="text" class="form-control" id="nome_usuario" name="nome_usuario"
                    placeholder="Nome de Usuário" required>
            </div>
            <div class="mb-3" style="position: relative;">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                <button type="button" class="password-toggle" id="toggleSenha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <!-- Medidor de Força da Senha -->
            <div class="password-strength-meter">
                <div class="strength-bar">
                    <div id="strength-fill" class="strength-fill"></div>
                </div>
                <div id="strength-text" class="strength-text"></div>
            </div>
            
            <!-- Mensagem de senha fraca -->
            <div id="weak-password-message" class="weak-password-message">
                Senha fraca. Crie uma senha mais forte.
            </div>

            <button type="submit" class="btn-login" id="btn-cadastrar">
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
    document.addEventListener('DOMContentLoaded', function() {
        const senhaInput = document.getElementById('senha');
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        const btnCadastrar = document.getElementById('btn-cadastrar');
        const formCadastro = document.getElementById('form-cadastro');
        const weakPasswordMessage = document.getElementById('weak-password-message');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const toggleSenha = document.getElementById('toggleSenha');
        const toggleIcon = toggleSenha.querySelector('i');

        let forcaSenhaAtual = 0;

        // Função para mostrar/ocultar senha
        function togglePasswordVisibility() {
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleSenha.setAttribute('aria-label', 'Ocultar senha');
            } else {
                senhaInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleSenha.setAttribute('aria-label', 'Mostrar senha');
            }
        }

        // Event listener para o botão de mostrar/ocultar senha
        toggleSenha.addEventListener('click', togglePasswordVisibility);

        // Permitir usar Enter no campo de senha para alternar a visibilidade (acessibilidade)
        toggleSenha.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePasswordVisibility();
            }
        });

        function atualizarForcaSenha(senha) {
            let score = 0;
            let feedback = '';

            if (senha.length === 0) {
                score = 0;
                feedback = '';
            } else {
                // Critérios de pontuação (mesmos do PHP)
                if (senha.length >= 8) score++;       // Comprimento
                if (/[a-z]/.test(senha)) score++;     // Letras minúsculas
                if (/[A-Z]/.test(senha)) score++;     // Letras maiúsculas
                if (/[0-9]/.test(senha)) score++;     // Números
                if (/[^a-zA-Z0-9]/.test(senha)) score++; // Caracteres especiais
            }

            // Atualiza a barra e o texto com base na pontuação
            let width = (score / 5) * 100;
            strengthFill.style.width = width + '%';
            strengthFill.className = 'strength-fill'; // Reseta as classes de cor

            if (senha.length === 0) {
                strengthText.textContent = '';
                weakPasswordMessage.style.display = 'none';
            } else if (score <= 2) {
                feedback = 'Senha fraca';
                strengthFill.classList.add('strength-weak');
                strengthText.className = 'strength-text text-weak';
                weakPasswordMessage.style.display = 'block';
            } else if (score <= 4) {
                feedback = 'Senha média';
                strengthFill.classList.add('strength-medium');
                strengthText.className = 'strength-text text-medium';
                weakPasswordMessage.style.display = 'none';
            } else {
                feedback = 'Senha forte';
                strengthFill.classList.add('strength-strong');
                strengthText.className = 'strength-text text-strong';
                weakPasswordMessage.style.display = 'none';
            }

            strengthText.textContent = feedback;
            forcaSenhaAtual = score;

            // Atualiza estado do botão
            atualizarEstadoBotao();
        }

        function atualizarEstadoBotao() {
            if (forcaSenhaAtual <= 2 && senhaInput.value.length > 0) {
                btnCadastrar.disabled = true;
                btnCadastrar.title = 'A senha é muito fraca. Melhore sua senha para continuar.';
            } else {
                btnCadastrar.disabled = false;
                btnCadastrar.title = '';
            }
        }

        senhaInput.addEventListener('input', function() {
            atualizarForcaSenha(this.value);
        });

        // Validação no envio do formulário (segunda camada de segurança)
        formCadastro.addEventListener('submit', function(e) {
            const senha = senhaInput.value;
            
            // Mostrar loading
            btnText.textContent = "Cadastrando...";
            btnSpinner.classList.remove("d-none");
            btnCadastrar.disabled = true;

            if (forcaSenhaAtual <= 2) {
                e.preventDefault();
                alert('Por favor, escolha uma senha mais forte. Sua senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.');
                senhaInput.focus();
                
                // Resetar loading
                btnText.textContent = "Cadastrar";
                btnSpinner.classList.add("d-none");
                atualizarEstadoBotao();
            }
        });

        // Inicializa o estado do botão
        atualizarEstadoBotao();
    });
    </script>
</body>

</html>