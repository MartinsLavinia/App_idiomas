<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o ID da unidade foi fornecido
if (!isset($_GET['unidade_id']) || !is_numeric($_GET['unidade_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da unidade inválido']);
    exit();
}

$unidade_id = (int)$_GET['unidade_id'];
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar informações da unidade
    $sql_unidade = "SELECT idioma, nivel FROM unidades WHERE id = ?";
    $stmt_unidade = $conn->prepare($sql_unidade);
    if (!$stmt_unidade) {
        throw new Exception("Erro ao preparar consulta da unidade: " . $conn->error);
    }
    $stmt_unidade->bind_param("i", $unidade_id);
    $stmt_unidade->execute();
    $unidade_info = $stmt_unidade->get_result()->fetch_assoc();
    $stmt_unidade->close();

    $atividades = [];
    
    if (!$unidade_info) {
        echo json_encode([
            'success' => false,
            'message' => 'Unidade não encontrada com ID: ' . $unidade_id
        ]);
        exit();
    }
    
    if ($unidade_info) {
        // Buscar caminhos de aprendizagem (atividades) para esta unidade
        $sql_caminhos = "
            SELECT 
                c.id,
                c.nome_caminho as nome,
                CONCAT('Atividade de ', c.nome_caminho) as descricao,
                CASE 
                    WHEN c.nome_caminho LIKE '%Conversação%' OR c.nome_caminho LIKE '%Conversa%' THEN 'fa-comments'
                    WHEN c.nome_caminho LIKE '%Vocabulário%' OR c.nome_caminho LIKE '%Vocab%' THEN 'fa-book'
                    WHEN c.nome_caminho LIKE '%Pronúncia%' OR c.nome_caminho LIKE '%Pronun%' THEN 'fa-microphone'
                    WHEN c.nome_caminho LIKE '%Escrita%' OR c.nome_caminho LIKE '%Escrev%' THEN 'fa-pen'
                    WHEN c.nome_caminho LIKE '%Audição%' OR c.nome_caminho LIKE '%Audio%' OR c.nome_caminho LIKE '%Escuta%' THEN 'fa-headphones'
                    WHEN c.nome_caminho LIKE '%Comida%' OR c.nome_caminho LIKE '%Aliment%' THEN 'fa-utensils'
                    WHEN c.nome_caminho LIKE '%Saudações%' OR c.nome_caminho LIKE '%Cumpriment%' THEN 'fa-hand-wave'
                    WHEN c.nome_caminho LIKE '%Rotina%' OR c.nome_caminho LIKE '%Diária%' THEN 'fa-clock'
                    ELSE 'fa-graduation-cap'
                END as icone,
                CASE 
                    WHEN c.nome_caminho LIKE '%Conversação%' OR c.nome_caminho LIKE '%Conversa%' THEN 'conversacao'
                    WHEN c.nome_caminho LIKE '%Vocabulário%' OR c.nome_caminho LIKE '%Vocab%' THEN 'vocabulario'
                    WHEN c.nome_caminho LIKE '%Pronúncia%' OR c.nome_caminho LIKE '%Pronun%' THEN 'pronuncia'
                    WHEN c.nome_caminho LIKE '%Escrita%' OR c.nome_caminho LIKE '%Escrev%' THEN 'escrita'
                    WHEN c.nome_caminho LIKE '%Audição%' OR c.nome_caminho LIKE '%Audio%' OR c.nome_caminho LIKE '%Escuta%' THEN 'audicao'
                    ELSE 'gramatica'
                END as tipo,
                ROW_NUMBER() OVER (ORDER BY c.id) as ordem,
                NULL as explicacao_teorica,
                COUNT(e.id) as total_exercicios,
                0 as exercicios_concluidos,
                CASE 
                    WHEN COUNT(e.id) > 0 THEN 0
                    ELSE 0 
                END as progresso
            FROM caminhos_aprendizagem c
            LEFT JOIN exercicios e ON c.id = e.caminho_id
            WHERE c.idioma = ? AND c.nivel = ?
            GROUP BY c.id, c.nome_caminho
            ORDER BY c.id
        ";

        $stmt_caminhos = $conn->prepare($sql_caminhos);
        if (!$stmt_caminhos) {
            throw new Exception("Erro ao preparar consulta dos caminhos: " . $conn->error);
        }
        $stmt_caminhos->bind_param("ss", $unidade_info['idioma'], $unidade_info['nivel']);
        $stmt_caminhos->execute();
        $result_caminhos = $stmt_caminhos->get_result();
        
        while ($row = $result_caminhos->fetch_assoc()) {
            $atividades[] = $row;
        }
        
        $stmt_caminhos->close();
        
        // Debug: verificar se encontrou atividades
        if (empty($atividades)) {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhuma atividade encontrada para idioma: ' . $unidade_info['idioma'] . ', nível: ' . $unidade_info['nivel'],
                'debug' => [
                    'unidade_id' => $unidade_id,
                    'idioma' => $unidade_info['idioma'],
                    'nivel' => $unidade_info['nivel']
                ]
            ]);
            exit();
        }
    }
    
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'atividades' => $atividades
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
