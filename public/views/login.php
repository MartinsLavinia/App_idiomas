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
    <title>Sign Up</title>
    <link href="cadastro-login.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
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
    <h2>Bem-vindo!</h2>
    <p>Faça login na sua conta</p>

    <?php if (isset($erro_login)): ?>
        <div style="background-color: rgba(255, 0, 0, 0.2); border: 1px solid rgba(255, 0, 0, 0.4); color: white; padding: 10px; border-radius: 8px; margin-bottom: 1rem;"><?php echo $erro_login; ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <div class="input-group">
            <input type="email" id="email" name="email" placeholder="E-mail" required>
        </div>
        <div class="input-group">
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
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
        <p style="font-size: 0.9rem; margin-bottom: 1rem;">Ou entre com:</p>
        <button onclick="window.location.href='google_oauth.php?action=login'">
            <img src="https://img.icons8.com/color/16/000000/google-logo.png" alt="Google logo"> Google
        </button>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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

// Buscando login no BD
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


//fundo com ondas
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
</script>

</body>
</html>