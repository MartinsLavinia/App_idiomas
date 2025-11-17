<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$teoria_id = $_GET['id'] ?? null;

if (!$teoria_id) {
    echo json_encode(['success' => false, 'message' => 'ID da teoria não fornecido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;
    
    $sql = "SELECT id, titulo, conteudo, nivel FROM teorias WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teoria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($teoria = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'teoria' => $teoria
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Teoria não encontrada'
        ]);
    }
    
    $stmt->close();
    $database->closeConnection();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teoria: ' . $e->getMessage()
    ]);
}
?>