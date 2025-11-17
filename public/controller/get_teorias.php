<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$nivel = $_GET['nivel'] ?? 'A1';

try {
    $database = new Database();
    $conn = $database->conn;
    
    $sql = "SELECT id, titulo, nivel, ordem, resumo FROM teorias WHERE nivel = ? ORDER BY ordem ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nivel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teorias = [];
    while ($row = $result->fetch_assoc()) {
        $teorias[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();
    
    echo json_encode([
        'success' => true,
        'teorias' => $teorias
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teorias: ' . $e->getMessage()
    ]);
}
?>