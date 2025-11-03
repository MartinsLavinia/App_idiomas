<?php
session_start();
include_once __DIR__ . "/../../conexao.php";

// 1. Verificação de Segurança: Garante que o usuário está logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: /../../index.php");
    exit();
}

// 2. Garante que a requisição é um POST para evitar exclusão via URL
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: perfil.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

$database = new Database();
$conn = $database->conn;

try {
    // 3. Inicia uma transação para garantir a integridade dos dados
    $conn->begin_transaction();

    // 4. Prepara e executa a exclusão na tabela 'usuarios'
    // Graças ao 'ON DELETE CASCADE' no seu banco de dados, ao excluir o usuário,
    // todos os registros relacionados em outras tabelas (progresso_usuario, quiz_resultados, etc.)
    // serão automaticamente removidos.
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    // 5. Confirma a transação
    $conn->commit();

    // 6. Limpa a sessão e redireciona o usuário para a página inicial
    session_unset();
    session_destroy();
    header("Location: /../../index.php?status=conta_excluida");
    exit();

} catch (Exception $e) {
    // 7. Em caso de erro, desfaz a transação e redireciona com uma mensagem de erro
    $conn->rollback();
    header("Location: perfil.php?status=erro_exclusao");
    exit();
} finally {
    if (isset($stmt)) $stmt->close();
    $database->closeConnection();
}
?>
