<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $idioma = trim($_POST['idioma'] ?? '');
    $unidade_id = trim($_POST['unidade_id'] ?? '');

    if ($titulo === '' || $descricao === '' || $nivel === '' || $idioma === '' || $unidade_id === '') {
        $_SESSION['error'] = "⚠️ Todos os campos são obrigatórios!";
    } else {
        // Inserir no banco
        $sql_insert = "INSERT INTO caminhos_aprendizagem (titulo, descricao, nivel, idioma, id_unidade) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("ssssi", $titulo, $descricao, $nivel, $idioma, $unidade_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Caminho adicionado com sucesso!";
        } else {
            $_SESSION['error'] = "❌ Erro ao adicionar caminho: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: adicionar_caminho.php");
    exit();
}

// Buscar unidades existentes no banco para exibir no formulário
$sql_unidades = "SELECT u.id, u.nome_unidade, u.nivel, u.numero_unidade, i.nome_idioma
                 FROM unidades u
                 JOIN idiomas i ON u.id_idioma = i.id
                 ORDER BY i.nome_idioma, u.nivel, u.numero_unidade, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);

$unidades = [];
if ($result_unidades && $result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Caminho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">➕ Adicionar Caminho</h1>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="card shadow">
            <div class="card-body">
                <form method="post" action="adicionar_caminho.php">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título do Caminho</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="">-- Selecione --</option>
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="B1">B1</option>
                            <option value="B2">B2</option>
                            <option value="C1">C1</option>
                            <option value="C2">C2</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="idioma" class="form-label">Idioma</label>
                        <input type="text" class="form-control" id="idioma" name="idioma" required>
                    </div>

                    <div class="mb-3">
                        <label for="unidade_id" class="form-label">Unidade</label>
                        <select class="form-select" id="unidade_id" name="unidade_id" required>
                            <option value="">-- Selecione a unidade --</option>
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= $u['id']; ?>">
                                    <?= htmlspecialchars($u['nome_idioma'] . " - Nível " . $u['nivel'] . " - Unidade " . $u['numero_unidade'] . " - " . $u['nome_unidade']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Caminho</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>