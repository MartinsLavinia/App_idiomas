<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->conn;

    $id_pergunta = $_POST['id'];
    $idioma = $_POST['idioma'];
    $pergunta = $_POST['pergunta'];
    $opcao_a = $_POST['opcao_a'];
    $opcao_b = $_POST['opcao_b'];
    $opcao_c = $_POST['opcao_c'];
    $resposta_correta = $_POST['resposta_correta'];

    $sql_update = "UPDATE quiz_nivelamento SET pergunta = ?, opcao_a = ?, opcao_b = ?, opcao_c = ?, resposta_correta = ? WHERE id = ? AND idioma = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('sssssis', $pergunta, $opcao_a, $opcao_b, $opcao_c, $resposta_correta, $id_pergunta, $idioma);

    if ($stmt_update->execute()) {
        $msg = "Pergunta atualizada com sucesso!";
    } else {
        $msg = "Erro ao atualizar a pergunta: " . $stmt_update->error;
    }
    $stmt_update->close();
    $database->closeConnection();

    // Redireciona de volta para a página de gerenciamento do quiz
    header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma) . "&msg=" . urlencode($msg));
    exit();
} else {
    // Redireciona se a requisição não for POST
    header("Location: gerenciar_caminho.php");
    exit();
}
?>