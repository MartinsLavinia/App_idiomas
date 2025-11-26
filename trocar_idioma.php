<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

// Verificar se o idioma foi enviado
if (!isset($_POST['idioma']) || empty($_POST['idioma'])) {
    echo json_encode(['success' => false, 'message' => 'Idioma não especificado']);
    exit();
}

$idioma = $_POST['idioma'];
$id_usuario = $_SESSION["id_usuario"];

// Incluir conexão
include_once __DIR__ . "/conexao.php";

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Verificar se o usuário já tem progresso neste idioma
    $sql_verificar = "SELECT id FROM progresso_usuario WHERE id_usuario = ? AND idioma = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("is", $id_usuario, $idioma);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();
    $stmt_verificar->close();
    
    if ($resultado->num_rows > 0) {
        // Usuário já tem progresso neste idioma, apenas trocar
        $_SESSION['idioma_escolhido'] = $idioma;
        echo json_encode(['success' => true, 'message' => 'Idioma trocado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Você ainda não começou a estudar este idioma']);
    }
    
    $database->closeConnection();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>