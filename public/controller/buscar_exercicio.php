<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit();
}

$exercicioId = $_GET['exercicio_id'] ?? null;

if (!$exercicioId) {
    echo json_encode(['success' => false, 'error' => 'ID do exercício não fornecido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar exercício
$sql = "SELECT * FROM exercicios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exercicioId);
$stmt->execute();
$exercicio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exercicio) {
    echo json_encode([
        'success' => true,
        'exercicio' => $exercicio
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Exercício não encontrado'
    ]);
}

$database->closeConnection();
?>