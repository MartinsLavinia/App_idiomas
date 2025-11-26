<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$teoria_id = $_GET['id'] ?? null;
$idioma = $_SESSION['idioma_atual'] ?? null;

if (!$teoria_id) {
    echo json_encode(['success' => false, 'message' => 'ID da teoria não fornecido']);
    exit();
}

// Se não há idioma na sessão, tentar obter do progresso do usuário
if (!$idioma && isset($_SESSION['id_usuario'])) {
    $database = new Database();
    $conn = $database->conn;
    
    $sql_idioma = "SELECT idioma FROM progresso_usuario WHERE id_usuario = ? ORDER BY ultima_atividade DESC LIMIT 1";
    $stmt_idioma = $conn->prepare($sql_idioma);
    $stmt_idioma->bind_param("i", $_SESSION['id_usuario']);
    $stmt_idioma->execute();
    $result_idioma = $stmt_idioma->get_result();
    if ($row_idioma = $result_idioma->fetch_assoc()) {
        $idioma = $row_idioma['idioma'];
    }
    $stmt_idioma->close();
    $database->closeConnection();
}

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Buscar teoria por ID e idioma (se disponível)
    if ($idioma) {
        $sql = "SELECT id, titulo, conteudo, nivel, idioma FROM teorias WHERE id = ? AND idioma = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $teoria_id, $idioma);
    } else {
        $sql = "SELECT id, titulo, conteudo, nivel, idioma FROM teorias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teoria_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($teoria = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'teoria' => $teoria
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Teoria não encontrada'
        ]);
    }
    
    $stmt->close();
    $database->closeConnection();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teoria: ' . $e->getMessage()
    ]);
}
?>