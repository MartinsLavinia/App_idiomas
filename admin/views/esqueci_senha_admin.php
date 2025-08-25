<?php
include_once __DIR__ . '/../../conexao.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $mensagem = 'Por favor, digite seu e-mail.';
        $tipo_mensagem = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Por favor, digite um e-mail válido.';
        $tipo_mensagem = 'danger';
    } else {
        $database = new Database();
        $conn = $database->conn;

        // Verifica se o email existe no banco de dados
        $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            $id_usuario = $usuario['id'];
            $nome_usuario = $usuario['nome'];

            // Gera um token único
            $token = bin2hex(random_bytes(32));
            $expiracao = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token válido por 1 hora

            // Armazena o token no banco de dados
            $stmt_token = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expiracao = ? WHERE id = ?");
            $stmt_token->bind_param("ssi", $token, $expiracao, $id_usuario);
            
            if ($stmt_token->execute()) {
                // Tentar enviar o email
                $email_enviado = enviarEmailRecuperacao($email, $nome_usuario, $token);
                
                if ($email_enviado) {
                    $mensagem = "Um link de recuperação de senha foi enviado para o seu email. Verifique sua caixa de entrada e spam.";
                    $tipo_mensagem = 'success';
                } else {
                    // Se não conseguir enviar email, mostrar o link diretamente (para desenvolvimento)
                    $link_recuperacao = "http://" . $_SERVER['HTTP_HOST'] . "/public/views/redefinir_senha.php?token=" . $token;
                    $mensagem = "Não foi possível enviar o email automaticamente. Use este link para redefinir sua senha: <br><a href='$link_recuperacao' target='_blank'>$link_recuperacao</a><br><small>Este link expirará em 1 hora.</small>";
                    $tipo_mensagem = 'warning';
                }
            } else {
                $mensagem = "Erro interno. Tente novamente mais tarde.";
                $tipo_mensagem = 'danger';
            }
            
            $stmt_token->close();
        } else {
            // Por segurança, não revelar se o email existe ou não
            $mensagem = "Se este email estiver cadastrado, você receberá um link de recuperação.";
            $tipo_mensagem = 'info';
        }

        $stmt->close();
        $database->closeConnection();
    }
}

function enviarEmailRecuperacao($email, $nome, $token) {
    // Configurações de email
    $host_atual = $_SERVER['HTTP_HOST'];
    $link_recuperacao = "http://$host_atual/public/views/redefinir_senha.php?token=" . $token;
    
    $assunto = "Recuperação de Senha - Site de Idiomas";
    
    $corpo_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Recuperação de Senha</h1>
                <p>Site de Idiomas</p>
            </div>
            <div class='content'>
                <p>Olá, <strong>$nome</strong>!</p>
                <p>Você solicitou a recuperação de senha para sua conta no Site de Idiomas.</p>
                <p>Clique no botão abaixo para redefinir sua senha:</p>
                <p style='text-align: center;'>
                    <a href='$link_recuperacao' class='button'>Redefinir Senha</a>
                </p>
                <p>Ou copie e cole este link no seu navegador:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;'>$link_recuperacao</p>
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Este link expirará em <strong>1 hora</strong></li>
                    <li>Se você não solicitou esta recuperação, ignore este email</li>
                    <li>Por segurança, não compartilhe este link com ninguém</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Este é um email automático, não responda.</p>
                <p>&copy; " . date('Y') . " Site de Idiomas. Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $corpo_texto = "
    Olá, $nome!
    
    Você solicitou a recuperação de senha para sua conta no Site de Idiomas.
    
    Acesse este link para redefinir sua senha:
    $link_recuperacao
    
    IMPORTANTE:
    - Este link expirará em 1 hora
    - Se você não solicitou esta recuperação, ignore este email
    - Por segurança, não compartilhe este link com ninguém
    
    Atenciosamente,
    Equipe Site de Idiomas
    ";
    
    // Headers para email HTML
    $headers = array(
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: Site de Idiomas <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Tentar enviar email usando a função mail() do PHP
    $email_enviado = @mail($email, $assunto, $corpo_html, implode("\r\n", $headers));
    
    // Se não conseguir enviar com HTML, tentar com texto simples
    if (!$email_enviado) {
        $headers_texto = array(
            'From: Site de Idiomas <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'X-Mailer: PHP/' . phpversion()
        );
        
        $email_enviado = @mail($email, $assunto, $corpo_texto, implode("\r\n", $headers_texto));
    }
    
    return $email_enviado;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border: none;
        }
        
        .card-header h2 {
            margin: 0;
            font-weight: bold;
        }
        
        .card-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 20px;
        }
        
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .card-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="icon">🔐</div>
                <h2>Esqueci a Senha</h2>
                <p>Recupere o acesso à sua conta</p>
            </div>
            <div class="card-body">
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> mb-4">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($tipo_mensagem !== 'success'): ?>
                <form action="esqueci_senha.php" method="POST" id="formEsqueciSenha">
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <strong>📧 E-mail cadastrado</strong>
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Digite seu e-mail" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <div class="form-text">
                            Digite o e-mail que você usou para criar sua conta
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary" id="btnEnviar">
                            📤 Enviar Link de Recuperação
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center">
                    <p>
                        <a href="index.php" class="back-link">
                            ← Voltar para o Login
                        </a>
                    </p>
                    
                    <?php if ($tipo_mensagem === 'success'): ?>
                    <p class="mt-3">
                        <a href="index.php" class="btn btn-outline-primary">
                            Ir para Login
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><strong>💡 Dicas importantes:</strong></h6>
                    <ul class="mb-0 small">
                        <li>Verifique sua caixa de entrada e pasta de spam</li>
                        <li>O link de recuperação expira em 1 hora</li>
                        <li>Se não receber o email, tente novamente</li>
                        <li>Entre em contato conosco se o problema persistir</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formEsqueciSenha');
            const btnEnviar = document.getElementById('btnEnviar');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Desabilitar botão para evitar múltiplos envios
                    btnEnviar.disabled = true;
                    btnEnviar.innerHTML = '⏳ Enviando...';
                    
                    // Reabilitar após 5 segundos
                    setTimeout(() => {
                        btnEnviar.disabled = false;
                        btnEnviar.innerHTML = '📤 Enviar Link de Recuperação';
                    }, 5000);
                });
            }
            
            // Auto-focus no campo de email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>