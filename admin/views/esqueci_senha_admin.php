<?php
include_once __DIR__ . '/../../conexao.php';
// Removido require_once pois o arquivo config_esquecisenhau.php não existe

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_usuario = $_POST["nome_usuario"];

    $database = new Database();
    $conn = $database->conn;

    $stmt = $conn->prepare("SELECT id, nome_usuario FROM administradores WHERE nome_usuario = ?");
    $stmt->bind_param("s", $nome_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        // Gera token e expiração
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Salva no banco
        $stmt_token = $conn->prepare("UPDATE administradores SET reset_token = ?, reset_expiracao = ? WHERE id = ?");
        $stmt_token->bind_param("ssi", $token, $expiracao, $usuario['id']);
        
        if ($stmt_token->execute()) {
            // Link de redefinição (ajuste a URL conforme necessário)
            $reset_link = "http://localhost/public/views/admin/redefinir_senha_admin.php?token=$token";

            // Configuração de email (ajuste conforme seu servidor)
            $assunto = "Redefinição de Senha - Área do Administrador";
            $mensagem = "
            Olá Administrador,

            Foi solicitada a redefinição de senha para o usuário: {$usuario['nome_usuario']}

            Clique no link abaixo para redefinir sua senha:
            $reset_link

            Este link expira em 1 hora.

            Se você não solicitou esta redefinição, ignore este email.

            Atenciosamente,
            Sistema de Administração
            ";

            $headers = "From: suporte@seudominio.com\r\n";
            $headers .= "Reply-To: suporte@seudominio.com\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

            // NOTA: Você precisa configurar um email real para o administrador
            // Por enquanto, vamos apenas mostrar o token para testes
            $sucesso = "Link de redefinição gerado com sucesso! Token: $token";
            
        } else {
            $erro = "Erro ao gerar token de redefinição.";
        }
        
        $stmt_token->close();
    } else {
        $erro = "Nome de usuário não encontrado!";
    }

    $stmt->close();
    $database->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Administrador</title>
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
            max-width: 600px;
            width: 95%;
            text-align: center;
            margin: auto;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        .form-left {
            text-align: left;
        }

        .form-right {
            text-align: left;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-left, .form-right {
                text-align: center;
            }
            
            .form-container {
                max-width: 450px;
                padding: 1.5rem;
            }
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
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
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

        .btn-submit {
            background: var(--yellow-accent);
            color: var(--dark-text);
            border: none;
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-weight: bold;
            transition: background 0.2s, transform 0.2s;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .btn-submit:hover {
            background: #fde047;
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.4);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #f8d7da;
        }

        .info-text {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .info-text i {
            color: var(--yellow-accent);
            margin-right: 8px;
        }

        .admin-warning {
            background-color: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.4);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .admin-warning i {
            color: var(--yellow-accent);
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="background-container">
        <canvas id="particles-js"></canvas>
        <!-- Onda animada -->
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
            </defs>
            <g class="parallax">
                <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7)" />
                <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
            </g>
        </svg>
    </div>

    <div class="form-container">
        <img src="../../imagens/logo-idiomas.png" alt="Logo" style="width: 150px; display: block; margin: 0 auto 20px auto;">
        <h2>Recuperar Senha - Admin</h2>
        <p>Digite seu nome de usuário para receber o link de redefinição</p>

        <!-- Mensagens de alerta -->
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <!-- Aviso administrativo -->
        <div class="admin-warning">
            <i class="fas fa-shield-alt"></i>
            Esta é uma área restrita para administradores. O token será exibido na tela para fins de desenvolvimento.
        </div>

        <form method="POST" id="formRecuperacao">
            <div class="form-grid">
                <div class="form-left">
                    <div class="input-group">
                        <input type="text" id="nome_usuario" name="nome_usuario" placeholder="Nome de Usuário" required>
                    </div>
                </div>
                <div class="form-right">
                    <div class="info-text">
                        <i class="fas fa-info-circle"></i>
                        O token de redefinição será gerado e exibido na tela.
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit" id="btnSubmit">
                <span id="btnText">Gerar Token de Recuperação</span>
                <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="links-container">
            <a href="login_admin.php">
                <i class="fas fa-arrow-left"></i> Voltar para o Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formRecuperacao = document.getElementById('formRecuperacao');
            const btnSubmit = document.getElementById('btnSubmit');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            // Loading state no formulário
            formRecuperacao.addEventListener('submit', function() {
                btnText.textContent = "Gerando...";
                btnSpinner.classList.remove('d-none');
                btnSubmit.disabled = true;
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

            init();
            drawParticles();
        });
    </script>
</body>
</html>