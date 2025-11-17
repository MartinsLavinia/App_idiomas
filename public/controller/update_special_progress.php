<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}

$user_id = $_SESSION['id_usuario'];
$caminho_id = $_POST['caminho_id'] ?? null;

if (!$caminho_id) {
    echo json_encode(['success' => false, 'message' => 'Caminho não especificado']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Atualizar progresso para exercício especial (10% por exercício especial)
    $sql_update = "UPDATE progresso_usuario SET 
                   progresso = LEAST(100, progresso + 10), 
                   ultima_atividade = NOW() 
                   WHERE id_usuario = ? AND caminho_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $user_id, $caminho_id);
    $stmt_update->execute();
    
    // Verificar progresso atual
    $sql_check = "SELECT progresso FROM progresso_usuario WHERE id_usuario = ? AND caminho_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $caminho_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $row = $result->fetch_assoc();
    
    $progresso_atual = $row['progresso'] ?? 0;
    $concluido = $progresso_atual >= 100;
    
    if ($concluido) {
        $sql_complete = "UPDATE progresso_usuario SET concluido = 1 WHERE id_usuario = ? AND caminho_id = ?";
        $stmt_complete = $conn->prepare($sql_complete);
        $stmt_complete->bind_param("ii", $user_id, $caminho_id);
        $stmt_complete->execute();
        $stmt_complete->close();
    }
    
    $stmt_update->close();
    $stmt_check->close();
    
    echo json_encode([
        'success' => true,
        'progresso' => $progresso_atual,
        'concluido' => $concluido,
        'message' => $concluido ? 'Caminho concluído!' : 'Exercício especial completado!'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

$database->closeConnection();
?>