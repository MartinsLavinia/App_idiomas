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
            border-radius: 8px;
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
</body>
</html>