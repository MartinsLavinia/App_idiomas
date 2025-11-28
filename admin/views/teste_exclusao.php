<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    die("Acesso negado");
}

if (!isset($_GET['id'])) {
    die("ID não fornecido");
}

$teoria_id = $_GET['id'];
echo "Tentando excluir teoria ID: $teoria_id<br>";

$database = new Database();
$conn = $database->conn;

// Verificar se existe
$sql = "SELECT * FROM teorias WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teoria_id);
$stmt->execute();
$result = $stmt->get_result();
$teoria = $result->fetch_assoc();

if ($teoria) {
    echo "Teoria encontrada: " . $teoria['titulo'] . "<br>";
    
    // Tentar excluir
    $sql_delete = "DELETE FROM teorias WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $teoria_id);
    
    if ($stmt_delete->execute()) {
        echo "Comando executado. Linhas afetadas: " . $stmt_delete->affected_rows . "<br>";
        if ($stmt_delete->affected_rows > 0) {
            echo "✅ SUCESSO: Teoria excluída!<br>";
        } else {
            echo "❌ ERRO: Nenhuma linha foi excluída<br>";
        }
    } else {
        echo "❌ ERRO SQL: " . $conn->error . "<br>";
    }
} else {
    echo "❌ Teoria não encontrada<br>";
}

$database->closeConnection();
?>
<a href="gerenciar_teorias.php">Voltar</a>