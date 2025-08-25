<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $database = new Database();
    $conn = $database->conn;

    switch ($method) {
        case 'GET':
            handleGetTeoriaDetalhes($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }

    $database->closeConnection();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function handleGetTeoriaDetalhes($conn) {
    $teoria_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $unidade_id = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : null;

    if (!$teoria_id && !$unidade_id) {
        echo json_encode(['success' => false, 'message' => 'ID da teoria ou ID da unidade é obrigatório']);
        return;
    }

    // Buscar detalhes completos da teoria
    if ($teoria_id) {
        $sql = "SELECT id, titulo, nivel, ordem, conteudo, resumo, palavras_chave, data_criacao FROM teorias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teoria_id);
    } else {
        // Buscar teoria pela unidade
        $sql = "SELECT t.id, t.titulo, t.nivel, t.ordem, t.conteudo, t.resumo, t.palavras_chave, t.data_criacao 
                FROM teorias t 
                INNER JOIN unidades u ON t.nivel = u.nivel 
                WHERE u.id = ? 
                ORDER BY t.ordem ASC 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $unidade_id);
    }
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
        return;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $teoria = $result->fetch_assoc();
    $stmt->close();

    if (!$teoria) {
        echo json_encode(['success' => false, 'message' => 'Teoria não encontrada']);
        return;
    }

    // Registrar visualização da teoria
    registrarVisualizacaoTeoria($conn, $_SESSION['id_usuario'], $teoria['id']);

    echo json_encode([
        'success' => true,
        'teoria' => $teoria
    ]);
}

function registrarVisualizacaoTeoria($conn, $id_usuario, $teoria_id) {
    $sql = "INSERT INTO visualizacoes_teoria (id_usuario, teoria_id, data_visualizacao) VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE data_visualizacao = NOW()";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $id_usuario, $teoria_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>