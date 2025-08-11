<?php
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Inicia a sessão no topo do arquivo para uso posterior
session_start();

// Lógica de cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Crie uma instância da classe Database para obter a conexão
    $database = new Database();
    $conn = $database->conn;
    
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $idioma = $_POST['idioma'];

    // Criptografe a senha
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
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Crie sua conta</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($erro_cadastro)): ?>
                            <div class="alert alert-danger"><?php echo $erro_cadastro; ?></div>
                        <?php endif; ?>
                        <form action="cadastro.php" method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            <div class="mb-3">
                                <label for="idioma" class="form-label">Escolha seu primeiro idioma</label>
                                <select class="form-select" id="idioma" name="idioma" required>
                                    <option value="" disabled selected>Selecione um idioma</option>
                                    <option value="Ingles">Inglês</option>
                                    <option value="Japones">Japonês</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Cadastrar e Começar Quiz</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Já tem uma conta? <a href="index.php">Faça login aqui</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>