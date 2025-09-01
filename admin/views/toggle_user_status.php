<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$new_status = (int)$_POST['status'];

if ($new_status !== 0 && $new_status !== 1) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Verificar se o usuário existe
    $sql_check = "SELECT id, nome FROM usuarios WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $user = $result->fetch_assoc();
    $stmt_check->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit();
    }
    
    // Atualizar o status do usuário
    $sql_update = "UPDATE usuarios SET ativo = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $new_status, $user_id);
    
    if ($stmt_update->execute()) {
        $action = $new_status ? 'ativado' : 'desativado';
        echo json_encode([
            'success' => true, 
            'message' => "Usuário '{$user['nome']}' foi {$action} com sucesso"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status do usuário']);
    }
    
    $stmt_update->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}

$database->closeConnection();
?>
