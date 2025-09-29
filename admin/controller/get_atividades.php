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
    $sql_unidade = "SELECT u.id, u.nome_unidade, u.idioma, u.nivel, u.descricao 
                   FROM unidades u 
                   WHERE u.id = ?";
    $stmt_unidade = $conn->prepare($sql_unidade);
    $stmt_unidade->bind_param("i", $unidade_id);
    $stmt_unidade->execute();
    $unidade_info = $stmt_unidade->get_result()->fetch_assoc();
    $stmt_unidade->close();

    if (!$unidade_info) {
        echo json_encode([
            'success' => false,
            'message' => 'Unidade não encontrada'
        ]);
        exit();
    }

    // Buscar caminhos de aprendizagem APENAS para esta unidade específica
    $sql_caminhos = "
        SELECT 
            c.id,
            c.nome_caminho as nome,
            CONCAT('Atividade de ', c.nome_caminho) as descricao,
            CASE 
                WHEN c.nome_caminho LIKE '%Comida%' OR c.nome_caminho LIKE '%food%' THEN 'fa-utensils'
                WHEN c.nome_caminho LIKE '%Sauda%' OR c.nome_caminho LIKE '%greet%' THEN 'fa-hand-wave'
                WHEN c.nome_caminho LIKE '%Rotina%' OR c.nome_caminho LIKE '%routine%' THEN 'fa-clock'
                WHEN c.nome_caminho LIKE '%Viagem%' OR c.nome_caminho LIKE '%travel%' THEN 'fa-plane'
                WHEN c.nome_caminho LIKE '%Apresenta%' OR c.nome_caminho LIKE '%introduc%' THEN 'fa-user'
                ELSE 'fa-graduation-cap'
            END as icone,
            'geral' as tipo,
            c.id as ordem,
            '' as explicacao_teorica,
            COUNT(e.id) as total_exercicios,
            0 as exercicios_concluidos,
            0 as progresso
        FROM caminhos_aprendizagem c
        LEFT JOIN exercicios e ON c.id = e.caminho_id
        WHERE c.id_unidade = ?
        GROUP BY c.id, c.nome_caminho
        ORDER BY c.id
    ";

    $stmt_caminhos = $conn->prepare($sql_caminhos);
    $stmt_caminhos->bind_param("i", $unidade_id);
    $stmt_caminhos->execute();
    $result_caminhos = $stmt_caminhos->get_result();
    
    $atividades = [];
    while ($row = $result_caminhos->fetch_assoc()) {
        $atividades[] = $row;
    }
    
    $stmt_caminhos->close();
    
    // REMOVIDO: O bloco que criava atividades padrão foi completamente removido
    // Agora só retorna atividades que estão realmente no banco de dados

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'unidade' => [
            'id' => $unidade_info['id'],
            'nome' => $unidade_info['nome_unidade'],
            'idioma' => $unidade_info['idioma'],
            'nivel' => $unidade_info['nivel'],
            'descricao' => $unidade_info['descricao']
        ],
        'atividades' => $atividades,
        'total_atividades' => count($atividades)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>