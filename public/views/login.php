<?php
session_start();
ob_start(); // Garante que o header() funcione corretamente

include_once __DIR__ . '/../../conexao.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->conn;

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha, ativo FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $nome_usuario, $senha_hash, $ativo);
        $stmt->fetch();
        
        if (!$ativo) {
            $erro_login = "Sua conta foi desativada. Entre em contato com o administrador.";
        } elseif (password_verify($senha, $senha_hash)) {
            // Update last login
            $sql_update_login = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_login);
            $stmt_update->bind_param("i", $id_usuario);
            $stmt_update->execute();
            $stmt_update->close();
            
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Site de Idiomas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    
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

        .form-container {
            position: relative;
            z-index: 3;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            margin: auto;
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

        /* Estilo para o grupo de input */
        .input-group {
            margin-bottom: 1rem;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 12px 45px 12px 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            font-size: 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white-text);
            box-sizing: border-box;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--yellow-accent);
            box-shadow: 0 0 0 2px rgba(252, 211, 77, 0.4);
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
            z-index: 10;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
        }

        .password-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--yellow-accent);
        }

        .password-toggle i {
            font-size: 1.1rem;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .links-container {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .links-container a {
            color: var(--yellow-accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            font-size: 0.9rem;
        }

        .links-container a:hover {
            color: #fde047;
        }

        .social-login {
            margin-top: 1.5rem;
        }

        .social-login p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            font-size: 0.9rem;
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

    <div class="background-container">
        <canvas id="particles-js"></canvas>
        <!-- Ondas SVG -->
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
            </defs>
            <g class="parallax">
                <use class="wave1" xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7)" />
                <use class="wave2" xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                <use class="wave3" xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
            </g>
        </svg>
    </div>

    <div class="form-container">
        <img src="../../imagens/logo-idiomas.png" alt="Logo" style="width: 150px; display: block; margin: 0 auto 20px auto;">
        <h2>Bem-vindo!</h2>
        <p>Faça login na sua conta</p>

        <?php if (isset($erro_login)): ?>
            <div style="background-color: rgba(255, 0, 0, 0.2); border: 1px solid rgba(255, 0, 0, 0.4); color: white; padding: 10px; border-radius: 8px; margin-bottom: 1rem;"><?php echo $erro_login; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="formLogin">
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
                <button type="button" class="password-toggle" id="toggleSenha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <span id="btnText">Entrar</span>
                <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="links-container">
            <a href="esqueci_senha.php" class="link-senha">Esqueci a senha?</a>
            <span style="color: rgba(255, 255, 255, 0.6);"> | </span>
            <a href="cadastro.php">Crie uma conta</a>
        </div>
        
        <div class="social-login">
            <p>Ou entre com:</p>
            <button type="button" onclick="window.location.href='google_oauth.php?action=login'">
                <img src="https://img.icons8.com/color/16/000000/google-logo.png" alt="Google logo"> Google
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSenha = document.getElementById('toggleSenha');
            const senhaInput = document.getElementById('senha');
            const toggleIcon = toggleSenha.querySelector('i');
            const formLogin = document.getElementById('formLogin');
            const btnLogin = document.getElementById('btnLogin');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            // Função para mostrar/ocultar senha
            function togglePasswordVisibility() {
                if (senhaInput.type === 'password') {
                    senhaInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    senhaInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            }
            toggleSenha.addEventListener('click', togglePasswordVisibility);

            // Loading state no formulário
            formLogin.addEventListener('submit', function() {
                btnText.textContent = "Entrando...";
                btnSpinner.classList.remove('d-none');
                btnLogin.disabled = true;
            });

            // Particle animation
            const canvas = document.getElementById('particles-js');
            const ctx = canvas.getContext('2d');
            let particles = [];
            let w, h;

            function resizeCanvas() {
                w = canvas.width = window.innerWidth;
                h = canvas.height = window.innerHeight;
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            function createParticle() {
                return {
                    x: Math.random() * w,
                    y: Math.random() * h,
                    radius: Math.random() * 2,
                    color: `rgba(255, 255, 255, ${Math.random() * 0.5 + 0.5})`,
                    velocity: {
                        x: (Math.random() - 0.5) * 0.5,
                        y: (Math.random() - 0.5) * 0.5,
                    }
                };
            }

            function init() {
                particles = [];
                for (let i = 0; i < 100; i++) {
                    particles.push(createParticle());
                }
            }

            function drawParticles() {
                ctx.clearRect(0, 0, w, h);
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = p.color;
                    ctx.fill();

                    p.x += p.velocity.x;
                    p.y += p.velocity.y;

                    if (p.x < 0 || p.x > w || p.y < 0 || p.y > h) {
                        particles[i] = createParticle();
                    }
                }
                requestAnimationFrame(drawParticles);
            }

            // Efeito parallax nas ondas
            const wave1 = document.querySelector('.wave1');
            const wave2 = document.querySelector('.wave2');
            const wave3 = document.querySelector('.wave3');

            document.addEventListener('mousemove', (e) => {
                const mouseX = e.clientX;
                const mouseY = e.clientY;

                const offsetX = (mouseX / window.innerWidth - 0.5) * 50; 
                const offsetY = (mouseY / window.innerHeight - 0.5) * 30;

                wave1.style.transform = `translate(${offsetX * 0.1}px, ${offsetY * 0.1}px)`;
                wave2.style.transform = `translate(${offsetX * 0.2}px, ${offsetY * 0.2}px)`;
                wave3.style.transform = `translate(${offsetX * 0.3}px, ${offsetY * 0.3}px)`;
            });

            // Inicializar animações
            init();
            drawParticles();
        });
    </script>

    
 
 <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
      <div class="vw-plugin-top-wrapper"></div>
    </div>
  </div>
  <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>


  
 <style>
        /* Botão de Acessibilidade */
        .accessibility-widget {
            position: fixed;
            bottom: 50px;
            right: 5px;
            z-index: 10000;
            font-family: 'arial';
        }

        .accessibility-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .accessibility-toggle:hover, .accessibility-toggle:focus-visible {
            outline: none;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(45, 62, 143, 1);
        }

        .accessibility-panel {
            position: absolute;
            bottom: 60px;
            right: 0;
            width: 320px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            padding: 10px 15px 15px 15px;
            font-size: 14px;
            z-index: 10001;
            color: #222;
        }

        .accessibility-header {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            padding: 12px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        .accessibility-header h3 {
            margin: 0;
            color: white;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover, .close-btn:focus-visible {
            background: rgba(255, 255, 255, 0.25);
            outline: none;
        }

        /* GRID DOS BOTÕES - TAMANHO CONSISTENTE */
        .accessibility-options {
            padding: 10px 5px 0 5px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: 95px;
            gap: 10px;
            justify-items: stretch;
        }

        .option-btn {
            background: white;
            border: 2px solid #d5d9db;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            padding: 8px 6px;
            font-size: 13px;
            color: #2d3e8f;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            user-select: none;
            box-shadow: 0 1px 1px rgb(0 0 0 / 0.05);
            font-weight: 600;
            height: 95px;
            min-height: 95px;
            max-height: 95px;
            width: 100%;
            box-sizing: border-box;
            gap: 0;
        }

        .option-btn i {
            font-size: 28px;
            margin-bottom: 0;
            color: #2d3e8f;
            flex-shrink: 0;
            line-height: 1;
        }

        .option-btn:hover, .option-btn:focus-visible {
            background: #e1e8f8;
            border-color: #1a2980;
            box-shadow: 0 2px 6px rgb(26 41 128 / 0.25);
            outline: none;
            transform: translateY(-2px);
        }

        .option-btn[aria-pressed="true"] {
            background: #3952a3;
            color: white;
            border-color: #1a2980;
        }

        .option-btn[aria-pressed="true"] i {
            color: white;
        }

        .reset-btn {
            background: #f5f5f7;
            border-color: #c9c9d7;
            color: #71717a;
        }

        .reset-btn:hover, .reset-btn:focus-visible {
            background: #d6d6e1;
            border-color: #71717a;
            color: #1a1a28;
        }

        /* CONTAINERS E SUBMENUS */
        .option-btn-container {
            position: relative;
            height: 95px;
        }

        /* SUBMENUS ESTILIZADOS */
        .submenu {
            display: none;
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            z-index: 10002;
            width: 280px;
            top: -150px;
            left: 0;
            border: 2px solid #e1e8f8;
        }

        .submenu.active {
            display: block;
        }

        .submenu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #2d3e8f;
            margin-bottom: 12px;
            font-size: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e1e8f8;
        }

        .submenu-close {
            background: none;
            border: none;
            color: #2d3e8f;
            font-size: 14px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .submenu-close:hover {
            background: #e1e8f8;
            color: #3952a3;
        }

        /* CONTROLES DESLIZANTES NOS SUBMENUS */
        .slider-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            margin: 15px 0;
        }

        .slider-btn {
            background: #e1e8f8;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #2d3e8f;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .slider-btn:hover {
            background: #3952a3;
            color: white;
            border-color: #2d3e8f;
        }

        .slider-wrapper {
            flex: 1;
            position: relative;
        }

        .slider-track {
            position: relative;
            height: 8px;
            background: #e1e8f8;
            border-radius: 4px;
            overflow: visible;
        }

        .slider-fill {
            position: absolute;
            height: 100%;
            background: linear-gradient(90deg, #2d3e8f, #3952a3);
            border-radius: 4px;
            width: 0%;
            transition: width 0.2s ease;
        }

        /* SLIDER COM BOLINHA VISÍVEL */
        .slider {
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
            opacity: 1;
            -webkit-appearance: none;
            background: transparent;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-webkit-slider-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-moz-range-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider-value {
            font-size: 12px;
            font-weight: 600;
            color: #2d3e8f;
            text-align: center;
            margin-top: 8px;
        }

        /* BOTÕES DO SUBMENU DE ALINHAMENTO */
        .submenu-btn {
            width: 100%;
            padding: 10px 12px;
            margin: 6px 0;
            background: white;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2d3e8f;
        }

        .submenu-btn:hover {
            background: #e1e8f8;
            border-color: #2d3e8f;
        }

        .submenu-btn i {
            font-size: 14px;
            width: 16px;
        }

        /* CLASSES PARA FUNCIONALIDADES */
        /* MODO DE ALTO CONTRASTE APENAS COM AMARELO/PRETO */
        .contrast-mode {
            background-color: #000000 !important;
            color: #ffff00 !important;
        }

        .contrast-mode * {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border-color: #ffff00 !important;
        }

        .contrast-mode a {
            color: #ffff00 !important;
            text-decoration: underline !important;
        }

        .contrast-mode button,
        .contrast-mode input,
        .contrast-mode select,
        .contrast-mode textarea {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode img {
            filter: grayscale(100%) contrast(150%) !important;
        }

        .contrast-mode .accessibility-panel {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn:hover,
        .contrast-mode .option-btn:focus-visible {
            background-color: #ffff00 !important;
            color: #000000 !important;
        }

        .highlight-links a, .highlight-links button {
            outline: 2px solid #00ffff !important;
            box-shadow: 0 0 8px #00ffff !important;
            position: relative;
        }

        .pause-animations * {
            animation-play-state: paused !important;
            transition: none !important;
        }

        @import url('https://fonts.googleapis.com/css2?family=Open+Dyslexic&display=swap');

        .dyslexia-friendly {
            font-family: 'Open Dyslexic', Arial, sans-serif !important;
            letter-spacing: 0.12em !important;
            word-spacing: 0.2em !important;
        }

        .text-spacing {
            letter-spacing: 0.12em !important;
            word-spacing: 0.3em !important;
        }

        .text-align-left * {
            text-align: left !important;
        }

        .text-align-center * {
            text-align: center !important;
        }

        .text-align-justify * {
            text-align: justify !important;
        }

        .tooltip-enabled a[title], .tooltip-enabled button[title] {
            position: relative;
            outline: none;
        }

        .tooltip-enabled a[title]:hover::after,
        .tooltip-enabled button[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3e8f;
            color: white;
            padding: 5px 8px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 11px;
            z-index: 2000;
            opacity: 0.95;
            pointer-events: none;
            font-weight: 600;
        }

        .accessibility-widget.moved {
            right: auto !important;
            left: 20px !important;
            top: 20px !important;
            bottom: auto !important;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 400px) {
            .accessibility-widget {
                right: 5px;
                width: 300px;
            }
            
            .accessibility-panel {
                width: 300px;
            }
            
            .submenu {
                width: 260px;
                left: -130px;
            }
        }

        /* Estilo para o botão de parar leitura */
        #stop-reading-btn {
            background: #dc3545 !important;
            color: white !important;
            border-color: #dc3545 !important;
        }

        #stop-reading-btn:hover {
            background: #c82333 !important;
            border-color: #bd2130 !important;
        }

        #stop-reading-btn i {
            color: white !important;
        }

        /* Feedback visual para leitura ativa */
        .reading-active {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
</head>
<body>
  

    <!-- Botão de Acessibilidade -->
    <div id="accessibility-widget" class="accessibility-widget" aria-label="Menu de acessibilidade">
        <button id="accessibility-toggle" class="accessibility-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="accessibility-panel" aria-label="Abrir menu de acessibilidade">
            <i class="fas fa-universal-access" aria-hidden="true"></i>
        </button>
        <div id="accessibility-panel" class="accessibility-panel" role="dialog" aria-modal="true" aria-labelledby="accessibility-title" tabindex="-1" hidden>
            <div class="accessibility-header">
                <h3 id="accessibility-title">Menu de Acessibilidade (CTRL+U)</h3>
                <button id="close-panel" class="close-btn" aria-label="Fechar menu de acessibilidade">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="accessibility-options grid">
                <button class="option-btn" data-action="contrast" aria-pressed="false" title="Contraste + (Alt+1)">
                    <i class="fas fa-adjust" aria-hidden="true"></i><br> Contraste +
                </button>
                <button class="option-btn" data-action="highlight-links" aria-pressed="false" title="Destacar links (Alt+2)">
                    <i class="fas fa-link" aria-hidden="true"></i><br> Destacar links
                </button>
                
                <!-- Botão de fonte com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="font-size-btn" title="Tamanho da fonte (Alt+3)">
                        <i class="fas fa-text-height" aria-hidden="true"></i><br> Tamanho da fonte
                    </button>
                    <div class="font-submenu submenu" id="font-submenu">
                        <div class="submenu-header">
                            <span>Tamanho da Fonte</span>
                            <button class="submenu-close" id="font-close" aria-label="Fechar menu de fonte">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-controls">
                            <button class="slider-btn" id="font-decrease" title="Diminuir fonte">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                            <div class="slider-wrapper">
                                <div class="slider-track">
                                    <div class="slider-fill" id="font-fill"></div>
                                    <input type="range" id="font-slider" class="slider" min="0" max="32" value="0" step="2">
                                </div>
                            </div>
                            <button class="slider-btn" id="font-increase" title="Aumentar fonte">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-value" id="font-value">Original</div>
                    </div>
                </div>
                
                <button class="option-btn" data-action="text-spacing" aria-pressed="false" title="Espaçamento texto (Alt+4)">
                    <i class="fas fa-arrows-alt-h" aria-hidden="true"></i><br> Espaçamento texto
                </button>
                <button class="option-btn" data-action="pause-animations" aria-pressed="false" title="Pausar animações (Alt+5)">
                    <i class="fas fa-pause-circle" aria-hidden="true"></i><br> Pausar animações
                </button>
                <button class="option-btn" data-action="dyslexia-friendly" aria-pressed="false" title="Modo dislexia (Alt+6)">
                    <i class="fas fa-font" aria-hidden="true"></i><br> Modo dislexia
                </button>
                
                <!-- Botão de leitura de página -->
                <button class="option-btn" id="read-page-btn" title="Ler página (Alt+7)">
                    <i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página
                </button>
                
                <button class="option-btn" data-action="tooltips" aria-pressed="false" title="Tooltips (Alt+8)">
                    <i class="fas fa-info-circle" aria-hidden="true"></i><br> Tooltips
                </button>
                
                <!-- Botão de alinhamento com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="align-btn" title="Alinhar texto (Alt+0)">
                        <i class="fas fa-align-left" aria-hidden="true"></i><br> Alinhar texto
                    </button>
                    <div class="align-submenu submenu" id="align-submenu">
                        <div class="submenu-header">
                            <span>Alinhar Texto</span>
                            <button class="submenu-close" id="align-close" aria-label="Fechar menu de alinhamento">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <button class="submenu-btn" data-action="text-align-original">
                            <i class="fas fa-undo"></i> Original
                        </button>
                        <button class="submenu-btn" data-action="text-align-left">
                            <i class="fas fa-align-left"></i> Alinhar à esquerda
                        </button>
                        <button class="submenu-btn" data-action="text-align-center">
                            <i class="fas fa-align-center"></i> Alinhar ao centro
                        </button>
                        <button class="submenu-btn" data-action="text-align-justify">
                            <i class="fas fa-align-justify"></i> Justificar
                        </button>
                    </div>
                </div>
                
                <button class="option-btn reset-btn" data-action="reset-all" title="Redefinir tudo">
                    <i class="fas fa-undo" aria-hidden="true"></i><br> Redefinir tudo
                </button>
                <button class="option-btn" data-action="move-hide" title="Mover/Ocultar menu">
                    <i class="fas fa-arrows-alt" aria-hidden="true"></i><br> Mover/Ocultar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const widget = document.getElementById('accessibility-widget');
            const toggleBtn = document.getElementById('accessibility-toggle');
            const panel = document.getElementById('accessibility-panel');
            const closeBtn = document.getElementById('close-panel');
            const optionBtns = document.querySelectorAll('.option-btn');
            const submenuBtns = document.querySelectorAll('.submenu-btn');
            
            // Elementos dos controles deslizantes
            const fontSlider = document.getElementById('font-slider');
            const fontFill = document.getElementById('font-fill');
            const fontValue = document.getElementById('font-value');
            const fontDecrease = document.getElementById('font-decrease');
            const fontIncrease = document.getElementById('font-increase');
            const fontBtn = document.getElementById('font-size-btn');
            const fontSubmenu = document.getElementById('font-submenu');
            const fontClose = document.getElementById('font-close');
            
            const alignBtn = document.getElementById('align-btn');
            const alignSubmenu = document.getElementById('align-submenu');
            const alignClose = document.getElementById('align-close');

            // Botões de leitura
            const readPageBtn = document.getElementById('read-page-btn');
            let speechSynthesis = window.speechSynthesis;
            let isReading = false;
            let currentUtterance = null;
            let userStopped = false;

            // Estado para fonte (0 = tamanho original)
            let fontSize = parseInt(localStorage.getItem('fontSize')) || 0;

            // Estado dos botões com toggle
            let states = {
                contrast: false,
                highlightLinks: false,
                textSpacing: false,
                pauseAnimations: false,
                dyslexiaFriendly: false,
                tooltips: false,
                textAlign: 'original'
            };

            // Função para atualizar o preenchimento do slider
            function updateSliderFill(slider, fill) {
                const value = slider.value;
                const min = slider.min;
                const max = slider.max;
                const percentage = ((value - min) / (max - min)) * 100;
                fill.style.width = percentage + '%';
            }

            // Inicializar sliders
            function initializeSliders() {
                updateSliderFill(fontSlider, fontFill);
                updateFontValue();
            }

            // Atualizar valor exibido da fonte
            function updateFontValue() {
                if (fontSize === 0) {
                    fontValue.textContent = 'Original';
                } else {
                    fontValue.textContent = fontSize + 'px';
                }
            }

            // Função para garantir tamanho consistente dos botões
            function enforceConsistentButtonSizes() {
                const optionBtns = document.querySelectorAll('.option-btn');
                const containers = document.querySelectorAll('.option-btn-container');
                
                optionBtns.forEach(btn => {
                    btn.style.height = '95px';
                    btn.style.minHeight = '95px';
                    btn.style.maxHeight = '95px';
                });
                
                containers.forEach(container => {
                    container.style.height = '95px';
                    container.style.minHeight = '95px';
                });
            }

            // Mostra ou esconde painel e atualiza aria-expanded
            function togglePanel(show) {
                if (show) {
                    panel.hidden = false;
                    panel.classList.add('active');
                    toggleBtn.setAttribute('aria-expanded', 'true');
                    panel.focus();
                    setTimeout(enforceConsistentButtonSizes, 10);
                } else {
                    panel.hidden = true;
                    panel.classList.remove('active');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    closeAllSubmenus();
                }
            }

            toggleBtn.addEventListener('click', () => {
                const isActive = !panel.hidden;
                togglePanel(!isActive);
            });
            
            closeBtn.addEventListener('click', () => togglePanel(false));

            // Fecha painel clicando fora
            document.addEventListener('click', e => {
                if (!widget.contains(e.target) && !panel.hidden) {
                    togglePanel(false);
                }
            });

            // Navegação pelo teclado no painel: ESC para fechar
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !panel.hidden) {
                    togglePanel(false);
                    toggleBtn.focus();
                }
            });

            // Eventos para os botões principais
            optionBtns.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const action = this.getAttribute('data-action');
                    
                    // Verificar se é um botão com submenu
                    if (this.id === 'font-size-btn') {
                        toggleSubmenu(fontSubmenu);
                    } else if (this.id === 'align-btn') {
                        toggleSubmenu(alignSubmenu);
                    } else {
                        handleAccessibilityAction(action, this);
                    }
                });
            });

            // Evento para o botão de ler página
            readPageBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!isReading) {
                    startReading();
                } else {
                    userStopped = true;
                    stopReading();
                }
            });

            // Função para iniciar leitura da página
            function startReading() {
                if (!speechSynthesis) {
                    console.log('Seu navegador não suporta leitura de texto.');
                    return;
                }

                // Parar qualquer leitura anterior
                stopReading();

                // Obter todo o texto da página
                const pageText = getPageText();
                
                if (!pageText.trim()) {
                    console.log('Nenhum texto encontrado para ler.');
                    return;
                }

                // Criar utterance
                currentUtterance = new SpeechSynthesisUtterance(pageText);
                currentUtterance.lang = 'pt-BR';
                currentUtterance.rate = 0.8;
                currentUtterance.pitch = 1;
                currentUtterance.volume = 1;

                // Resetar flag
                userStopped = false;

                // Atualizar interface
                isReading = true;
                readPageBtn.innerHTML = '<i class="fas fa-stop" aria-hidden="true"></i><br> Parar leitura';
                readPageBtn.id = 'stop-reading-btn';
                readPageBtn.classList.add('reading-active');

                // Evento quando a leitura terminar
                currentUtterance.onend = function() {
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Evento quando ocorrer erro - apenas log, sem alert
                currentUtterance.onerror = function(event) {
                    console.log('Erro na leitura:', event.error);
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Iniciar leitura
                speechSynthesis.speak(currentUtterance);
            }

            // Função para parar leitura
            function stopReading() {
                if (speechSynthesis && isReading) {
                    speechSynthesis.cancel();
                }
                
                isReading = false;
                currentUtterance = null;
                readPageBtn.innerHTML = '<i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página';
                readPageBtn.id = 'read-page-btn';
                readPageBtn.classList.remove('reading-active');
            }

            // Função para obter texto da página (excluindo elementos irrelevantes)
            function getPageText() {
                // Clonar o body para não modificar o DOM original
                const clone = document.body.cloneNode(true);
                
                // Remover elementos que não devem ser lidos
                const elementsToRemove = clone.querySelectorAll(
                    'script, style, nav, header, footer, .accessibility-widget, [aria-hidden="true"]'
                );
                elementsToRemove.forEach(el => el.remove());
                
                // Obter texto limpo
                return clone.textContent.replace(/\s+/g, ' ').trim();
            }

            // Eventos para os botões dos submenus
            submenuBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const action = this.getAttribute('data-action');
                    handleAccessibilityAction(action, this);
                    closeAllSubmenus();
                });
            });

            // Botões de fechar nos submenus
            fontClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            alignClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            // Funções para controlar submenus
            function toggleSubmenu(submenu) {
                closeAllSubmenus();
                submenu.classList.add('active');
            }

            function closeAllSubmenus() {
                fontSubmenu.classList.remove('active');
                alignSubmenu.classList.remove('active');
            }

            // Fechar submenus ao clicar fora deles
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.option-btn-container')) {
                    closeAllSubmenus();
                }
            });

            // Controle deslizante de fonte
            fontSlider.value = fontSize;
            
            fontSlider.addEventListener('input', function() {
                fontSize = parseInt(this.value);
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontDecrease.addEventListener('click', function() {
                fontSize = Math.max(parseInt(fontSlider.min), fontSize - 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontIncrease.addEventListener('click', function() {
                fontSize = Math.min(parseInt(fontSlider.max), fontSize + 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            function applyFontSize() {
                const elements = document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, a, span, li, label, button, div');
                
                if (fontSize === 0) {
                    // Volta ao tamanho original
                    elements.forEach(el => {
                        el.style.fontSize = '';
                    });
                } else {
                    // Aplica o tamanho personalizado
                    elements.forEach(el => {
                        el.style.fontSize = fontSize + 'px';
                    });
                }
                localStorage.setItem('fontSize', fontSize);
            }

            function applyTextAlign() {
                // Remove todas as classes de alinhamento
                document.body.classList.remove('text-align-left', 'text-align-center', 'text-align-justify');
                
                if (states.textAlign !== 'original') {
                    document.body.classList.add(states.textAlign);
                }
            }

            function handleAccessibilityAction(action, btn) {
                const body = document.body;
                switch (action) {
                    case 'contrast':
                        states.contrast = !states.contrast;
                        body.classList.toggle('contrast-mode', states.contrast);
                        btn.setAttribute('aria-pressed', states.contrast);
                        break;

                    case 'highlight-links':
                        states.highlightLinks = !states.highlightLinks;
                        body.classList.toggle('highlight-links', states.highlightLinks);
                        btn.setAttribute('aria-pressed', states.highlightLinks);
                        break;

                    case 'text-spacing':
                        states.textSpacing = !states.textSpacing;
                        body.classList.toggle('text-spacing', states.textSpacing);
                        btn.setAttribute('aria-pressed', states.textSpacing);
                        break;

                    case 'pause-animations':
                        states.pauseAnimations = !states.pauseAnimations;
                        body.classList.toggle('pause-animations', states.pauseAnimations);
                        btn.setAttribute('aria-pressed', states.pauseAnimations);
                        break;

                    case 'dyslexia-friendly':
                        states.dyslexiaFriendly = !states.dyslexiaFriendly;
                        body.classList.toggle('dyslexia-friendly', states.dyslexiaFriendly);
                        btn.setAttribute('aria-pressed', states.dyslexiaFriendly);
                        break;

                    case 'tooltips':
                        states.tooltips = !states.tooltips;
                        body.classList.toggle('tooltip-enabled', states.tooltips);
                        btn.setAttribute('aria-pressed', states.tooltips);
                        break;

                    case 'text-align-original':
                        states.textAlign = 'original';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-left':
                        states.textAlign = 'text-align-left';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-center':
                        states.textAlign = 'text-align-center';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-justify':
                        states.textAlign = 'text-align-justify';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;

                    case 'reset-all':
                        resetAll();
                        break;

                    case 'move-hide':
                        const moved = widget.classList.toggle('moved');
                        if (moved) {
                            btn.style.backgroundColor = '#fbbf24';
                        } else {
                            btn.style.backgroundColor = '';
                        }
                        break;
                }
            }

            function resetAll() {
                // Parar leitura se estiver ativa
                userStopped = true;
                stopReading();
                
                // Remove todas as classes de acessibilidade
                document.body.className = '';
                
                // Remove todos os estilos inline
                document.querySelectorAll('*').forEach(el => {
                    el.style.fontSize = '';
                    el.style.lineHeight = '';
                    el.style.letterSpacing = '';
                    el.style.wordSpacing = '';
                    el.style.textAlign = '';
                    el.style.fontFamily = '';
                });
                
                // Reseta estados
                fontSize = 0;
                fontSlider.value = fontSize;
                
                states = {
                    contrast: false,
                    highlightLinks: false,
                    textSpacing: false,
                    pauseAnimations: false,
                    dyslexiaFriendly: false,
                    tooltips: false,
                    textAlign: 'original'
                };

                initializeSliders();
                applyFontSize();

                // Reseta botões
                optionBtns.forEach(btn => {
                    btn.setAttribute('aria-pressed', false);
                    btn.style.backgroundColor = '';
                });

                // Limpa localStorage
                localStorage.removeItem('fontSize');
                closeAllSubmenus();
            }

            // Inicialização
            enforceConsistentButtonSizes();
            window.addEventListener('resize', enforceConsistentButtonSizes);
            initializeSliders();

            // Aplica configurações salvas ao carregar
            if (localStorage.getItem('fontSize')) {
                applyFontSize();
            }

            // Atalhos: Alt+1 até Alt+0 para facilitar uso rápido
            document.addEventListener('keydown', e => {
                if (e.altKey && !e.ctrlKey && !e.metaKey) {
                    switch (e.key) {
                        case '1': document.querySelector('[data-action="contrast"]').click(); break;
                        case '2': document.querySelector('[data-action="highlight-links"]').click(); break;
                        case '3': fontBtn.click(); break;
                        case '4': document.querySelector('[data-action="text-spacing"]').click(); break;
                        case '5': document.querySelector('[data-action="pause-animations"]').click(); break;
                        case '6': document.querySelector('[data-action="dyslexia-friendly"]').click(); break;
                        case '7': readPageBtn.click(); break;
                        case '8': document.querySelector('[data-action="tooltips"]').click(); break;
                        case '0': alignBtn.click(); break;
                        default: break;
                    }
                }

                // CTRL+U alterna painel
                if (e.ctrlKey && e.key.toLowerCase() === 'u') {
                    e.preventDefault();
                    togglePanel(panel.hidden);
                }

                // ESC para parar leitura
                if (e.key === 'Escape' && isReading) {
                    userStopped = true;
                    stopReading();
                }
            });

            // Parar leitura quando a página for fechada
            window.addEventListener('beforeunload', function() {
                userStopped = true;
                stopReading();
            });
        });
    </script>


</body>
</html>