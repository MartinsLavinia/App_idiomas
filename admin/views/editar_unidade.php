<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar dados da unidade
if (isset($_GET['id'])) {
    $id_unidade = $_GET['id'];
    
    $sql = "SELECT u.*, i.nome_idioma FROM unidades u 
            JOIN idiomas i ON u.id_idioma = i.id 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_unidade);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Unidade não encontrada.";
        header("Location: gerenciar_unidades.php");
        exit();
    }
    
    $unidade = $result->fetch_assoc();
    $stmt->close();
} else {
    header("Location: gerenciar_unidades.php");
    exit();
}

// Buscar idiomas disponíveis
$sql_idiomas = "SELECT id, nome_idioma FROM idiomas ORDER BY nome_idioma";
$result_idiomas = $conn->query($sql_idiomas);
$idiomas = $result_idiomas->fetch_all(MYSQLI_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_unidade = trim($_POST['nome_unidade']);
    $descricao = trim($_POST['descricao']);
    $nivel = trim($_POST['nivel']);
    $numero_unidade = trim($_POST['numero_unidade']);
    $id_idioma = $_POST['id_idioma'];
    
    if (empty($nome_unidade) || empty($id_idioma)) {
        $_SESSION['error'] = "Nome da unidade e idioma são obrigatórios.";
    } else {
        $sql_update = "UPDATE unidades SET nome_unidade = ?, descricao = ?, nivel = ?, numero_unidade = ?, id_idioma = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssii", $nome_unidade, $descricao, $nivel, $numero_unidade, $id_idioma, $id_unidade);
        
        if ($stmt_update->execute()) {
            $_SESSION['success'] = "Unidade atualizada com sucesso!";
            header("Location: gerenciar_unidades.php");
            exit();
        } else {
            $_SESSION['error'] = "Erro ao atualizar unidade: " . $conn->error;
        }
        $stmt_update->close();
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Unidade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
        :root {
            --roxo-principal: #6a0dad;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --cinza-claro: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
        }
        
        .btn-warning {
            background-color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Editar Unidade
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome_unidade" class="form-label">Nome da Unidade</label>
                                <input type="text" class="form-control" id="nome_unidade" name="nome_unidade" 
                                       value="<?= htmlspecialchars($unidade['nome_unidade']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="id_idioma" class="form-label">Idioma</label>
                                <select class="form-select" id="id_idioma" name="id_idioma" required>
                                    <?php foreach ($idiomas as $idioma): ?>
                                        <option value="<?= $idioma['id']; ?>" 
                                                <?= $idioma['id'] == $unidade['id_idioma'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($idioma['nome_idioma']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nivel" class="form-label">Nível</label>
                                        <input type="text" class="form-control" id="nivel" name="nivel" 
                                               value="<?= htmlspecialchars($unidade['nivel']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="numero_unidade" class="form-label">Número da Unidade</label>
                                        <input type="number" class="form-control" id="numero_unidade" name="numero_unidade" 
                                               value="<?= htmlspecialchars($unidade['numero_unidade']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($unidade['descricao']); ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="gerenciar_unidades.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>