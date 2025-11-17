<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$bloco_id = $_GET['bloco_id'] ?? null;

if (!$bloco_id) {
    echo json_encode(['success' => false, 'message' => 'ID do bloco não fornecido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Buscar teoria relacionada ao bloco (baseado na ordem do bloco)
    $sql = "SELECT b.ordem, b.caminho_id, c.nivel 
            FROM blocos b 
            JOIN caminhos_aprendizagem c ON b.caminho_id = c.id 
            WHERE b.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bloco_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($bloco = $result->fetch_assoc()) {
        // Buscar teoria correspondente à ordem do bloco e nível
        $sql_teoria = "SELECT id, titulo, conteudo FROM teorias WHERE nivel = ? AND ordem = ?";
        $stmt_teoria = $conn->prepare($sql_teoria);
        $stmt_teoria->bind_param("si", $bloco['nivel'], $bloco['ordem']);
        $stmt_teoria->execute();
        $result_teoria = $stmt_teoria->get_result();
        
        if ($teoria = $result_teoria->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'teoria' => $teoria
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhuma teoria encontrada para este bloco'
            ]);
        }
        
        $stmt_teoria->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Bloco não encontrado'
        ]);
    }
    
    $stmt->close();
    $database->closeConnection();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teoria do bloco: ' . $e->getMessage()
    ]);
}
?>