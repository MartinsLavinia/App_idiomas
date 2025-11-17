<?php
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Inicia a sessão no topo do arquivo para uso posterior
session_start();

// Variável para armazenar a mensagem de erro, se houver.
$erro_cadastro = "";

// --- BUSCAR IDIOMAS PARA O DROPDOWN ---
$database_idiomas = new Database();
$conn_idiomas = $database_idiomas->conn;

$idiomas = [];
$sql_idiomas = "SELECT nome_idioma FROM idiomas ORDER BY nome_idioma ASC";
$result_idiomas = $conn_idiomas->query($sql_idiomas);
if ($result_idiomas && $result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas[] = $row;
    }
}
$database_idiomas->closeConnection();
// --- FIM DA BUSCA DE IDIOMAS ---

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

// Lógica de cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Crie uma instância da classe Database para obter a conexão
    $database = new Database();
    $conn = $database->conn;
    
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $idioma = $_POST['idioma'];

    // 1. VERIFICAÇÃO DA FORÇA DA SENHA
    $forca_senha = verificarForcaSenha($senha);
    
    if ($forca_senha <= 2) {
        $erro_cadastro = "Senha muito fraca. Sua senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.";
    } else {
        // 2. VERIFICAÇÃO: Verifica se o e-mail já existe no banco de dados.
        $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check_email);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Se o e-mail já existe, define a mensagem de erro.
            $erro_cadastro = "Este e-mail já está cadastrado. Por favor, tente fazer login.";
        } else {
            // 3. CADASTRO: Se o e-mail não existe e senha é forte, procede com a inserção do usuário.
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Prepare e execute a inserção na tabela 'usuarios'
            $sql_usuario = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
            $stmt_usuario = $conn->prepare($sql_usuario);
            $stmt_usuario->bind_param("sss", $nome, $email, $senha_hash);

            if ($stmt_usuario->execute()) {
                // Se o cadastro do usuário for bem-sucedido, pegue o ID dele
                $id_usuario = $conn->insert_id;
                
                // Prepare e execute a inserção na tabela 'progresso_usuario'
                $sql_progresso = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel) VALUES (?, ?, ?)";
                $stmt_progresso = $conn->prepare($sql_progresso);
                $nivel_inicial = "A1";
                $stmt_progresso->bind_param("iss", $id_usuario, $idioma, $nivel_inicial);

                if ($stmt_progresso->execute()) {
                    // Sucesso: inicia a sessão e redireciona para o quiz
                    $_SESSION['id_usuario'] = $id_usuario;
                    // Salva o nome do usuário na sessão para personalização no painel
                    $_SESSION['nome_usuario'] = $nome; 
                    header("Location: quiz.php?idioma=$idioma");
                    exit(); 
                } else {
                    $erro_cadastro = "Erro ao registrar o progresso: " . $stmt_progresso->error;
                }
                $stmt_progresso->close();
            } else {
                $erro_cadastro = "Erro no cadastro do usuário: " . $stmt_usuario->error;
            }
            $stmt_usuario->close();
        }
    }
    
    // Feche a conexão usando o método da classe
    $database->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 45px 12px 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            font-size: 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white-text);
            box-sizing: border-box;
        }

        .input-group input::placeholder,
        .input-group select option:first-child {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group input:focus,
        .input-group select:focus {
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

        /* Estilos para o medidor de força da senha */
        .password-strength-meter {
            margin-top: -0.5rem;
            margin-bottom: 1rem;
        }
        .strength-bar {
            height: 6px;
            background-color: #e0e0e0;
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
        }
        /* Cores da barra */
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #198754; }
        /* Cores do texto */
        .text-weak { color: #dc3545; }
        .text-medium { color: #ffc107; }
        .text-strong { color: #198754; }
        
        /* Estilo para botão desabilitado */
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
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

        /* Estilo para o select */
        .form-select {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: var(--white-text) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
        }

        .form-select:focus {
            border-color: var(--yellow-accent) !important;
            box-shadow: 0 0 0 2px rgba(252, 211, 77, 0.4) !important;
        }

        .form-select option {
            background-color: var(--purple-main);
            color: var(--white-text);
        }
    </style>
</head>

<body>
    <div class="background-container">
        <canvas id="particles-js"></canvas>
        <!-- Ondas SVG -->
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none"
            shape-rendering="auto">
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
        <h2>Crie sua conta</h2>
        <p>Preencha os dados para começar</p>

        <?php if (!empty($erro_cadastro)): ?>
        <div style="background-color: rgba(255, 0, 0, 0.2); border: 1px solid rgba(255, 0, 0, 0.4); color: white; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $erro_cadastro; ?>
        </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST" id="form-cadastro">
            <div class="input-group">
                <input type="text" id="nome" name="nome" placeholder="Nome Completo" required>
            </div>
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
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
            
            <div class="input-group">
                <select class="form-select" id="idioma" name="idioma" required>
                    <option value="" disabled selected>Selecione seu primeiro idioma</option>
                    <?php foreach ($idiomas as $idioma_item): ?>
                        <option value="<?php echo htmlspecialchars($idioma_item['nome_idioma']); ?>">
                            <?php echo htmlspecialchars($idioma_item['nome_idioma']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-login" id="btn-cadastrar">Cadastrar e Começar Quiz</button>
        </form>

        <div class="social-login">
            <button type="button" onclick="window.location.href='google_oauth.php?action=register'">
                <img src="https://img.icons8.com/color/16/000000/google-logo.png" alt="Google logo"> Cadastrar com Google
            </button>
        </div>

        <div class="links-container">
            <p style="font-size: 0.9rem; margin-top: 1rem; color: rgba(255, 255, 255, 0.6);">
                Já tem uma conta? <a href="login.php" style="color: var(--yellow-accent);">Faça login aqui</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // --- LÓGICA DO MEDIDOR DE FORÇA DA SENHA ---
    document.addEventListener('DOMContentLoaded', function() {
        const senhaInput = document.getElementById('senha');
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        const btnCadastrar = document.getElementById('btn-cadastrar');
        const formCadastro = document.getElementById('form-cadastro');
        const weakPasswordMessage = document.getElementById('weak-password-message');
        const toggleSenha = document.getElementById('toggleSenha');
        const toggleIcon = toggleSenha.querySelector('i');

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

        let forcaSenhaAtual = 0;

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
                btnCadastrar.classList.add('btn-disabled');
                btnCadastrar.title = 'A senha é muito fraca. Melhore sua senha para continuar.';
            } else {
                btnCadastrar.disabled = false;
                btnCadastrar.classList.remove('btn-disabled');
                btnCadastrar.title = '';
            }
        }

        senhaInput.addEventListener('input', function() {
            atualizarForcaSenha(this.value);
        });

        // Validação no envio do formulário (segunda camada de segurança)
        formCadastro.addEventListener('submit', function(e) {
            const senha = senhaInput.value;
            if (forcaSenhaAtual <= 2) {
                e.preventDefault();
                alert('Por favor, escolha uma senha mais forte. Sua senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.');
                senhaInput.focus();
            }
        });

        // Inicializa o estado do botão
        atualizarEstadoBotao();
    });
    </script>
    <script>
    // Particle animation script
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

    window.onload = function() {
        init();
        drawParticles();
    }
    </script>
</body>
</html>