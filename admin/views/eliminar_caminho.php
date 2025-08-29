<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Apenas administradores podem acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

// Verifica se o ID do caminho foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro: ID inválido ou não fornecido."));
    exit();
}

$caminho_id = $_GET['id'];
$database = new Database();
$conn = $database->conn;

// Inicia uma transação para garantir que tudo seja feito de uma vez
$conn->begin_transaction();

try {
    // 1. Exclui exercícios (usando o nome da coluna id_caminho)
    $sql_delete_exercicios = "DELETE FROM exercicios WHERE caminho_id = ?";
    $stmt_exercicios = $conn->prepare($sql_delete_exercicios);
    $stmt_exercicios->bind_param("i", $caminho_id);
    $stmt_exercicios->execute();
    $stmt_exercicios->close();

    // 2. Exclui teorias (usando o nome da coluna id_caminho)
    $sql_delete_teorias = "DELETE FROM teorias WHERE caminho_id = ?";
    $stmt_teorias = $conn->prepare($sql_delete_teorias);
    $stmt_teorias->bind_param("i", $caminho_id);
    $stmt_teorias->execute();
    $stmt_teorias->close();

    // 3. Exclui o caminho em si
    $sql_delete_caminho = "DELETE FROM caminhos_aprendizagem WHERE id = ?";
    $stmt_caminho = $conn->prepare($sql_delete_caminho);
    $stmt_caminho->bind_param("i", $caminho_id);
    $stmt_caminho->execute();

    if ($stmt_caminho->affected_rows === 0) {
        $conn->rollback();
        header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro: O caminho não foi encontrado para exclusão."));
        exit();
    }
    $stmt_caminho->close();

    // Se tudo deu certo, confirma a transação
    $conn->commit();
    header("Location: gerenciar_caminho.php?status=success&message=" . urlencode("Sucesso! O caminho e seus conteúdos foram excluídos com êxito."));
} catch (mysqli_sql_exception $e) {
    // Se algo deu errado, desfaz tudo e retorna uma mensagem de erro
    $conn->rollback();
    header("Location: gerenciar_caminho.php?status=error&message=" . urlencode("Erro ao excluir caminho: " . $e->getMessage()));
} finally {
    // Fecha a conexão com o banco de dados
    $database->closeConnection();
}
exit();
?>