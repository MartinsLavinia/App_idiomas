<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_GET['unidade_id']) && !isset($_GET['caminho_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da unidade ou caminho não fornecido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    
    if (isset($_GET['unidade_id'])) {
        // Buscar blocos por unidade
        $unidade_id = $_GET['unidade_id'];
        
        $sql = "SELECT b.*, c.nome_caminho 
                FROM blocos b 
                LEFT JOIN caminhos_aprendizagem c ON b.caminho_id = c.id 
                WHERE c.id_unidade = ? 
                ORDER BY b.ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $unidade_id);
    } else {
        // Buscar blocos por caminho
        $caminho_id = $_GET['caminho_id'];
        
        $sql = "SELECT b.*, c.nome_caminho 
                FROM blocos b 
                LEFT JOIN caminhos_aprendizagem c ON b.caminho_id = c.id 
                WHERE b.caminho_id = ? 
                ORDER BY b.ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $caminho_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $blocos = [];
    
    while ($row = $result->fetch_assoc()) {
        $bloco = $row;
        
        // Buscar progresso do usuário se logado
        if ($id_usuario) {
            $sql_progresso = "SELECT progresso_percentual, atividades_concluidas, total_atividades, concluido 
                             FROM progresso_bloco 
                             WHERE usuario_id = ? AND bloco_id = ?";
            $stmt_progresso = $conn->prepare($sql_progresso);
            $stmt_progresso->bind_param("ii", $id_usuario, $row['id']);
            $stmt_progresso->execute();
            $progresso_result = $stmt_progresso->get_result();
            
            if ($progresso_data = $progresso_result->fetch_assoc()) {
                $bloco['progresso'] = $progresso_data;
            } else {
                // Se não há progresso, calcular baseado nos exercícios
                $sql_total = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
                $stmt_total = $conn->prepare($sql_total);
                $stmt_total->bind_param("i", $row['id']);
                $stmt_total->execute();
                $total_exercicios = $stmt_total->get_result()->fetch_assoc()['total'];
                $stmt_total->close();
                
                $bloco['progresso'] = [
                    'progresso_percentual' => 0,
                    'atividades_concluidas' => 0,
                    'total_atividades' => $total_exercicios,
                    'concluido' => false
                ];
            }
            $stmt_progresso->close();
        } else {
            $bloco['progresso'] = [
                'progresso_percentual' => 0,
                'atividades_concluidas' => 0,
                'total_atividades' => 0,
                'concluido' => false
            ];
        }
        
        $blocos[] = $bloco;
    }
    
    $stmt->close();
    $database->closeConnection();
    
    echo json_encode(['success' => true, 'blocos' => $blocos]);
    
} catch (Exception $e) {
    $database->closeConnection();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>