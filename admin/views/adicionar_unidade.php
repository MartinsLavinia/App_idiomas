<?php
session_start();
include_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

$idiomas = [];
$sql_idiomas = "SELECT id, nome_idioma FROM idiomas ORDER BY nome_idioma";
$result_idiomas = $conn->query($sql_idiomas);
if ($result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_unidade = trim($_POST['nome_unidade']);
    $descricao = trim($_POST['descricao']);
    $id_idioma = $_POST['id_idioma'];

    if (empty($nome_unidade) || empty($id_idioma)) {
        $_SESSION['error'] = "Nome da unidade e idioma são obrigatórios.";
    } else {
        try {
            $sql_insert = "INSERT INTO unidades (nome_unidade, descricao, id_idioma) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssi", $nome_unidade, $descricao, $id_idioma);

            if ($stmt_insert->execute()) {
                $_SESSION['success'] = "Unidade '$nome_unidade' adicionada com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao adicionar a unidade: " . $conn->error;
            }
            $stmt_insert->close();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao processar a solicitação: " . $e->getMessage();
        }
    }
    header("Location: gerenciar_unidades.php");
    exit();
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Unidade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Nova Unidade</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <form action="adicionar_unidade.php" method="POST">
            <label for="nome_unidade">Nome da Unidade:</label>
            <input type="text" id="nome_unidade" name="nome_unidade" required>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao"></textarea>

            <label for="id_idioma">Idioma:</label>
            <select id="id_idioma" name="id_idioma" required>
                <option value="">Selecione um idioma</option>
                <?php foreach ($idiomas as $idioma): ?>
                    <option value="<?= $idioma['id']; ?>"><?= $idioma['nome_idioma']; ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Adicionar Unidade</button>
        </form>
        <p><a href="gerenciar_unidades.php">Voltar para Gerenciar Unidades</a></p>
    </div>
</body>
</html>