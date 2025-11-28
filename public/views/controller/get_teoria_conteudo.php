<?php
session_start();
include_once __DIR__ . "/../../../conexao.php";

header('Content-Type: application/json');

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

$database = new Database();
$conn = $database->conn;

$id = $_GET['id'] ?? 0;

$sql = "SELECT id, titulo, conteudo, nivel FROM teorias WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'teoria' => $row
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Teoria não encontrada']);
}

$stmt->close();
$database->closeConnection();
?>