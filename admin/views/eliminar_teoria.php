<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_teorias.php?status=erro_exclusao");
    exit();
}

$teoria_id = (int)$_GET['id'];
$database = new Database();
$conn = $database->conn;

$sql_delete = "DELETE FROM teorias WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $teoria_id);

if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
    $stmt_delete->close();
    $database->closeConnection();
    header("Location: gerenciar_teorias.php?status=sucesso_exclusao");
} else {
    $stmt_delete->close();
    $database->closeConnection();
    header("Location: gerenciar_teorias.php?status=erro_exclusao");
}
exit();
?>