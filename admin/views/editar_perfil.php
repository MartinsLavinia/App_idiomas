<?php
session_start();
include __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

$id_admin = $_SESSION['id_admin'];

// Buscar dados do administrador logado
$sql = "SELECT nome_usuario FROM administradores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_usuario = $_POST['nome_usuario'];
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql_update = "UPDATE administradores SET nome_usuario = ?, senhaadm = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $nome_usuario, $senha_hash, $id_admin);
    } else {
        $sql_update = "UPDATE administradores SET nome_usuario = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nome_usuario, $id_admin);
    }

    if ($stmt_update->execute()) {
        $mensagem = "Perfil atualizado com sucesso!";
        $admin['nome_usuario'] = $nome_usuario;

        // Redirecionamento após 2 segundos
        echo "<script>
            setTimeout(function() {
                window.location.href = 'gerenciar_caminho.php';
            }, 2000);
        </script>";
    } else {
        $mensagem = "Erro ao atualizar o perfil: " . $conn->error;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --roxo-principal: #6a0dad;
        --roxo-escuro: #4c087c;
        --amarelo-detalhe: #ffd700;
        --branco: #ffffff;
        --cinza-claro: #f4f6f9;
        --cinza-medio: #dee2e6;
        --preto-texto: #212529;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--cinza-claro);
        color: var(--preto-texto);
    }

    .navbar {
        background: var(--roxo-principal) !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
    }

    .navbar-brand img {
        height: 65px;
        width: auto;
    }

    .profile-container {
        max-width: 600px;
        margin: 50px auto;
    }

    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: var(--roxo-principal);
        color: var(--branco);
        font-size: 1.3rem;
        font-weight: 600;
        border-radius: 1rem 1rem 0 0;
        text-align: center;
        padding: 20px;
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 6px;
    }

    .form-control {
        padding: 12px;
        border-radius: 0.7rem;
        border: 1px solid var(--cinza-medio);
    }

    .form-control:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
    }

    .btn-primary {
        background: var(--roxo-principal);
        border: none;
        padding: 12px;
        font-weight: 600;
        border-radius: 0.7rem;
        width: 100%;
        transition: all 0.3s ease-in-out;
    }

    .btn-primary:hover {
        background: var(--roxo-escuro);
        transform: scale(1.02);
    }

    .alert {
        border-radius: 0.7rem;
        font-weight: 500;
        text-align: center;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../../imagens/logo-idiomas.png" alt="Logo do Site">
            </a>
        </div>
    </nav>

    <div class="profile-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-cog me-2"></i> Editar Perfil
            </div>
            <div class="card-body p-4">
                <?php if (isset($mensagem)): ?>
                <div class="alert alert-info"><?= $mensagem ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="nome_usuario" class="form-label">Nome de Usuário</label>
                        <input type="text" class="form-control" id="nome_usuario" name="nome_usuario"
                            value="<?= htmlspecialchars($admin['nome_usuario'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha"
                            placeholder="Deixe em branco para manter a atual">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Atualizar Perfil
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>