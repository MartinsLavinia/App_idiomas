<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID da teoria foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_teorias.php");
    exit();
}

$teoria_id = $_GET['id'];

$database = new Database();
$conn = $database->conn;

// Verifica se a teoria existe antes de tentar excluir
$sql_verificar = "SELECT id FROM teorias WHERE id = ?";
$stmt_verificar = $conn->prepare($sql_verificar);
$stmt_verificar->bind_param("i", $teoria_id);
$stmt_verificar->execute();
$resultado = $stmt_verificar->get_result();
$stmt_verificar->close();

if ($resultado->num_rows == 0) {
    // Teoria não encontrada
    header("Location: gerenciar_teorias.php");
    exit();
}

// Exclui a teoria
$sql_excluir = "DELETE FROM teorias WHERE id = ?";
$stmt_excluir = $conn->prepare($sql_excluir);
$stmt_excluir->bind_param("i", $teoria_id);

if ($stmt_excluir->execute()) {
    // Sucesso na exclusão
    header("Location: gerenciar_teorias.php?status=sucesso_exclusao");
} else {
    // Erro na exclusão
    header("Location: gerenciar_teorias.php?status=erro_exclusao");
}

$stmt_excluir->close();
$database->closeConnection();
exit();
?>
