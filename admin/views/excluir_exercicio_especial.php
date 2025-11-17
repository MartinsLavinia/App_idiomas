<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    $sql_delete = "DELETE FROM exercicios WHERE id = ? AND categoria = 'especial'";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Exercício especial excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir exercício']);
    }
    
    $stmt_delete->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

$database->closeConnection();
?>