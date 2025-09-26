<?php
include_once __DIR__ . '/../../conexao.php';
require "config_esquicisenha.ini";
ini_set("SMTP", "smtp.eb.mil.br");
$nome = $_POST["nome"];
$email = $_POST["email"];
$assunto = "Contato pelo Site";
$mensagem = '';
$tipo_mensagem = '';
$token_valido = false;
$token = '';

// Verificar se o token foi fornecido
if ($certo == "1") {

    // Função mail para enviar o e-mail
    mail("$emaildest", "$assunto", "Nome: $nome\n\nEmail: $email\n\nMensagem:\n$mensagem\n\n...::: Recebido do site :::...", "From: $nome<$email>");
}
// Processar formulário de redefinição
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valido) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if (empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = 'Por favor, preencha todos os campos.';
        $tipo_mensagem = 'danger';
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = 'A senha deve ter pelo menos 6 caracteres.';
        $tipo_mensagem = 'danger';
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = 'As senhas não coincidem.';
        $tipo_mensagem = 'danger';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Criptografar a nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualizar a senha e limpar o token
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expiracao = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $senha_hash, $token);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $mensagem = 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
            $tipo_mensagem = 'success';
            $token_valido = false; // Impedir nova tentativa
        } else {
            $mensagem = 'Erro ao redefinir senha. Tente novamente.';
            $tipo_mensagem = 'danger';
        }
        
        $stmt->close();
        $database->closeConnection();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Site de Idiomas</title>
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
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background: #e9ecef;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        .password-requirements {
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .requirement {
            color: #6c757d;
            transition: color 0.3s ease;
        }
        
        .requirement.met {
            color: #28a745;
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
                <div class="icon">🔑</div>
                <h2>Redefinir Senha</h2>
                <p>Crie uma nova senha segura</p>
            </div>
            <div class="card-body">
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> mb-4">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($token_valido): ?>
                    <div class="mb-4 p-3 bg-light rounded">
                        <h6><strong>👤 Redefinindo senha para:</strong></h6>
                        <p class="mb-0"><?php echo htmlspecialchars($usuario['nome']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)</p>
                    </div>
                    
                    <form action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" id="formRedefinirSenha">
                        <div class="mb-4">
                            <label for="nova_senha" class="form-label">
                                <strong>🔒 Nova Senha</strong>
                            </label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                   placeholder="Digite sua nova senha" required minlength="6">
                            
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <small id="strengthText" class="text-muted">Digite uma senha</small>
                            </div>
                            
                            <div class="password-requirements">
                                <small>
                                    <div class="requirement" id="req-length">• Pelo menos 6 caracteres</div>
                                    <div class="requirement" id="req-letter">• Pelo menos uma letra</div>
                                    <div class="requirement" id="req-number">• Pelo menos um número</div>
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmar_senha" class="form-label">
                                <strong>🔒 Confirmar Nova Senha</strong>
                            </label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                   placeholder="Digite novamente sua nova senha" required>
                            <div class="form-text" id="passwordMatch"></div>
                        </div>
                        
                        <div class="d-grid gap-2 mb-4">
                            <button type="submit" class="btn btn-primary" id="btnRedefinir" disabled>
                                ✅ Redefinir Senha
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <div class="icon">❌</div>
                        <h4>Link Inválido</h4>
                        <p>O link de recuperação é inválido ou expirou.</p>
                    </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <p>
                        <a href="login.php" class="back-link">
                            ← Voltar para o Login
                        </a>
                    </p>
                    
                    <?php if (!$token_valido): ?>
                    <p class="mt-3">
                        <a href="esqueci_senha.php" class="btn btn-outline-primary">
                            Solicitar Novo Link
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($tipo_mensagem === 'success'): ?>
                    <p class="mt-3">
                        <a href="login.php" class="btn btn-success">
                            Fazer Login
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <?php if ($token_valido): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><strong>🛡️ Dicas de segurança:</strong></h6>
                    <ul class="mb-0 small">
                        <li>Use uma senha única que você não usa em outros sites</li>
                        <li>Combine letras, números e símbolos</li>
                        <li>Evite informações pessoais óbvias</li>
                        <li>Considere usar um gerenciador de senhas</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const novaSenha = document.getElementById('nova_senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            const btnRedefinir = document.getElementById('btnRedefinir');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            
            if (!novaSenha) return; // Se não há formulário, sair
            
            // Verificar força da senha
            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 6,
                    letter: /[a-zA-Z]/.test(password),
                    number: /\d/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };
                
                // Atualizar indicadores visuais dos requisitos
                document.getElementById('req-length').classList.toggle('met', requirements.length);
                document.getElementById('req-letter').classList.toggle('met', requirements.letter);
                document.getElementById('req-number').classList.toggle('met', requirements.number);
                
                // Calcular força
                if (requirements.length) strength += 25;
                if (requirements.letter) strength += 25;
                if (requirements.number) strength += 25;
                if (requirements.special) strength += 25;
                
                // Atualizar barra de força
                strengthBar.style.width = strength + '%';
                strengthBar.className = 'strength-fill';
                
                if (strength < 50) {
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = 'Senha fraca';
                    strengthText.className = 'text-danger';
                } else if (strength < 75) {
                    strengthBar.classList.add('strength-medium');
                    strengthText.textContent = 'Senha média';
                    strengthText.className = 'text-warning';
                } else {
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = 'Senha forte';
                    strengthText.className = 'text-success';
                }
                
                return requirements.length && requirements.letter && requirements.number;
            }
            
            // Verificar se as senhas coincidem
            function checkPasswordMatch() {
                const senha1 = novaSenha.value;
                const senha2 = confirmarSenha.value;
                
                if (senha2.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'form-text';
                    return false;
                } else if (senha1 === senha2) {
                    passwordMatch.textContent = '✅ Senhas coincidem';
                    passwordMatch.className = 'form-text text-success';
                    return true;
                } else {
                    passwordMatch.textContent = '❌ Senhas não coincidem';
                    passwordMatch.className = 'form-text text-danger';
                    return false;
                }
            }
            
            // Habilitar/desabilitar botão
            function updateSubmitButton() {
                const strongPassword = checkPasswordStrength(novaSenha.value);
                const passwordsMatch = checkPasswordMatch();
                
                btnRedefinir.disabled = !(strongPassword && passwordsMatch);
            }
            
            // Event listeners
            novaSenha.addEventListener('input', updateSubmitButton);
            confirmarSenha.addEventListener('input', updateSubmitButton);
            
            // Validação no envio
            document.getElementById('formRedefinirSenha').addEventListener('submit', function(e) {
                const senha1 = novaSenha.value;
                const senha2 = confirmarSenha.value;
                
                if (senha1.length < 6) {
                    e.preventDefault();
                    alert('A senha deve ter pelo menos 6 caracteres.');
                    return;
                }
                
                if (senha1 !== senha2) {
                    e.preventDefault();
                    alert('As senhas não coincidem.');
                    return;
                }
                
                // Desabilitar botão para evitar múltiplos envios
                btnRedefinir.disabled = true;
                btnRedefinir.innerHTML = '⏳ Redefinindo...';
            });
            
            // Auto-focus no primeiro campo
            novaSenha.focus();
        });
    </script>
</body>
</html>