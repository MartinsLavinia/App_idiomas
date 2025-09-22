<?php
session_start();

// Incluindo a conexão
include_once __DIR__ . 
'/../config/conexao.php';
// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

$unidades = [];
$sql_unidades = "SELECT u.id, u.nome_unidade, i.nome_idioma FROM unidades u JOIN idiomas i ON u.id_idioma = i.id ORDER BY i.nome_idioma, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);
if ($result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_unidade = $_POST['id_unidade'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $nome_caminho = $_POST['nome_caminho'] ?? '';

    if (empty($id_unidade) || empty($nivel) || empty($nome_caminho)) {
        $_SESSION['error'] = "Erro: Todos os campos são obrigatórios.";
        header("Location: gerenciar_caminho.php");
        exit();
    }

    // Obter o idioma da unidade selecionada
    $sql_get_idioma = "SELECT i.nome_idioma FROM unidades u JOIN idiomas i ON u.id_idioma = i.id WHERE u.id = ?";
    $stmt_get_idioma = $conn->prepare($sql_get_idioma);
    $stmt_get_idioma->bind_param("i", $id_unidade);
    $stmt_get_idioma->execute();
    $result_get_idioma = $stmt_get_idioma->get_result();
    $idioma_unidade = $result_get_idioma->fetch_assoc()['nome_idioma'];
    $stmt_get_idioma->close();

    // Prepara a inserção
    $sql = "INSERT INTO caminhos_aprendizagem (idioma, nivel, nome_caminho, id_unidade) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $_SESSION['error'] = "Erro ao preparar a declaração: " . $conn->error;
        header("Location: gerenciar_caminho.php");
        exit();
    }

    $stmt->bind_param("sssi", $idioma_unidade, $nivel, $nome_caminho, $id_unidade);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Caminho adicionado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar caminho: " . $stmt->error;
    }

    $stmt->close();
    $database->closeConnection();
    header("Location: gerenciar_caminho.php");
    exit();
} else {
    // Se não for POST, exibe o formulário
    $database->closeConnection();
}

$niveis_db = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Caminho</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Novo Caminho de Aprendizagem</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <form action="adicionar_caminho.php" method="POST">
            <label for="id_unidade">Unidade:</label>
            <select id="id_unidade" name="id_unidade" required>
                <option value="">Selecione uma unidade</option>
                <?php foreach ($unidades as $unidade): ?>
                    <option value="<?= $unidade['id']; ?>"><?= $unidade['nome_unidade']; ?> (<?= $unidade['nome_idioma']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <label for="nivel">Nível:</label>
            <select id="nivel" name="nivel" required>
                <option value="">Selecione um nível</option>
                <?php foreach ($niveis_db as $nivel): ?>
                    <option value="<?= $nivel; ?>"><?= $nivel; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="nome_caminho">Nome do Caminho:</label>
            <input type="text" id="nome_caminho" name="nome_caminho" required>

            <button type="submit">Adicionar Caminho</button>
        </form>
        <p><a href="gerenciar_caminho.php">Voltar para Gerenciar Caminhos</a></p>
    </div>
</body>
</html>