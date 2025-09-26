<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Apenas admin logado pode acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do exercício foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

$exercicio_id = $_GET['id'];

// Pega o caminho_id do exercício para redirecionar de volta
$database = new Database();
$conn = $database->conn;

$sql_caminho_id = "SELECT caminho_id FROM exercicios WHERE id = ?";
$stmt_caminho_id = $conn->prepare($sql_caminho_id);
$stmt_caminho_id->bind_param("i", $exercicio_id);
$stmt_caminho_id->execute();
$result = $stmt_caminho_id->get_result();

if ($result->num_rows === 0) {
    header("Location: gerenciar_caminhos.php"); // Redireciona se o exercício não existe
    exit();
}
$caminho_id = $result->fetch_assoc()['caminho_id'];
$stmt_caminho_id->close();

// Executa a remoção do exercício
$sql_delete = "DELETE FROM exercicios WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $exercicio_id);
$stmt_delete->execute();
$stmt_delete->close();
$database->closeConnection();

// Redireciona de volta para a página de exercícios do caminho
header("Location: gerenciar_exercicios.php?caminho_id=" . $caminho_id);
exit();
?>