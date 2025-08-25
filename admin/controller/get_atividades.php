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
    $sql_unidade = "SELECT idioma, nivel FROM unidades WHERE id = ?";
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

    // Buscar caminhos de aprendizagem para esta unidade
    $sql_caminhos = "
        SELECT 
            c.id,
            c.nome_caminho as nome,
            CONCAT('Atividade de ', c.nome_caminho) as descricao,
            CASE 
                WHEN c.nome_caminho LIKE '%Comida%' THEN 'fa-utensils'
                WHEN c.nome_caminho LIKE '%Sauda%' THEN 'fa-hand-wave'
                WHEN c.nome_caminho LIKE '%Rotina%' THEN 'fa-clock'
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
        WHERE c.idioma = ? AND c.nivel = ?
        GROUP BY c.id, c.nome_caminho
        ORDER BY c.id
    ";

    $stmt_caminhos = $conn->prepare($sql_caminhos);
    $stmt_caminhos->bind_param("ss", $unidade_info['idioma'], $unidade_info['nivel']);
    $stmt_caminhos->execute();
    $result_caminhos = $stmt_caminhos->get_result();
    
    $atividades = [];
    while ($row = $result_caminhos->fetch_assoc()) {
        $atividades[] = $row;
    }
    
    $stmt_caminhos->close();
    
    // Se não encontrou atividades, criar atividades padrão
    if (empty($atividades)) {
        $atividades = [
            [
                'id' => 1,
                'nome' => 'Vocabulário Básico',
                'descricao' => 'Aprenda palavras essenciais',
                'icone' => 'fa-book',
                'tipo' => 'vocabulario',
                'ordem' => 1,
                'explicacao_teorica' => '',
                'total_exercicios' => 5,
                'exercicios_concluidos' => 0,
                'progresso' => 0
            ],
            [
                'id' => 2,
                'nome' => 'Conversação',
                'descricao' => 'Pratique diálogos básicos',
                'icone' => 'fa-comments',
                'tipo' => 'conversacao',
                'ordem' => 2,
                'explicacao_teorica' => '',
                'total_exercicios' => 3,
                'exercicios_concluidos' => 0,
                'progresso' => 0
            ],
            [
                'id' => 3,
                'nome' => 'Gramática',
                'descricao' => 'Estude regras básicas',
                'icone' => 'fa-graduation-cap',
                'tipo' => 'gramatica',
                'ordem' => 3,
                'explicacao_teorica' => '',
                'total_exercicios' => 4,
                'exercicios_concluidos' => 0,
                'progresso' => 0
            ]
        ];
    }
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'atividades' => $atividades
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>