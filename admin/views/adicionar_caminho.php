<?php
session_start();

// Incluindo a conexão
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idioma = $_POST['idioma'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $nome_caminho = $_POST['nome_caminho'] ?? '';

    if (empty($idioma) || empty($nivel) || empty($nome_caminho)) {
        header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro: Todos os campos são obrigatórios."));
        exit();
    }

    $database = new Database();
    $conn = $database->conn;

    // Prepara a inserção
    $sql = "INSERT INTO caminhos_aprendizagem (idioma, nivel, nome_caminho) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro ao preparar a declaração: " . $conn->error));
        exit();
    }

    $stmt->bind_param("sss", $idioma, $nivel, $nome_caminho);

    if ($stmt->execute()) {
        header("Location: gerenciar_caminho.php?msg=" . urlencode("Caminho adicionado com sucesso!"));
    } else {
        header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro ao adicionar caminho: " . $stmt->error));
    }

    $stmt->close();
    $database->closeConnection();
} else {
    header("Location: gerenciar_caminho.php?msg=" . urlencode("Método de requisição inválido."));
    exit();
}
?>