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

// Lista de idiomas válidos
$idiomas_validos = ['Ingles', 'Japones', 'Espanhol', 'Frances', 'Alemao', 'Italiano', 'Portugues', 'Chines', 'Coreano', 'Russo'];

if (!in_array($idioma, $idiomas_validos)) {
    echo json_encode(['success' => false, 'message' => 'Idioma inválido']);
    exit();
}

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
        // Usuário já tem progresso neste idioma
        $_SESSION['idioma_escolhido'] = $idioma;
        echo json_encode(['success' => true, 'message' => 'Idioma já existe, trocado com sucesso']);
    } else {
        // Criar novo progresso para o idioma
        $sql_inserir = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, bloco_atual, topico_atual, progresso_bloco, data_inicio) VALUES (?, ?, 'A1', 1, 1, 0, NOW())";
        $stmt_inserir = $conn->prepare($sql_inserir);
        $stmt_inserir->bind_param("is", $id_usuario, $idioma);
        
        if ($stmt_inserir->execute()) {
            $_SESSION['idioma_escolhido'] = $idioma;
            echo json_encode(['success' => true, 'message' => 'Novo idioma iniciado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao iniciar novo idioma']);
        }
        $stmt_inserir->close();
    }
    
    $database->closeConnection();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>