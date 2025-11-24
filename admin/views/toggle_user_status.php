<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$database = new Database();
$conn = $database->conn;

$user_id = $_POST['user_id'];
$new_status = $_POST['status'];

$sql = "UPDATE usuarios SET ativo = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $new_status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
}

$stmt->close();
$database->closeConnection();
?>