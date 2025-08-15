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

    // Buscar atividades da unidade com progresso do usuário
    $sql = "
        SELECT 
            a.id,
            a.nome,
            a.descricao,
            a.icone,
            a.tipo,
            a.ordem,
            a.explicacao_teorica,
            COUNT(e.id) as total_exercicios,
            COUNT(pd.id) as exercicios_concluidos,
            CASE 
                WHEN COUNT(e.id) > 0 THEN ROUND((COUNT(pd.id) / COUNT(e.id)) * 100, 2)
                ELSE 0 
            END as progresso
        FROM atividades a
        LEFT JOIN exercicios e ON a.id = e.atividade_id
        LEFT JOIN progresso_detalhado pd ON e.id = pd.exercicio_id AND pd.id_usuario = ?
        WHERE a.unidade_id = ?
        GROUP BY a.id, a.nome, a.descricao, a.icone, a.tipo, a.ordem, a.explicacao_teorica
        ORDER BY a.ordem ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $unidade_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $atividades = [];
    while ($row = $result->fetch_assoc()) {
        $atividades[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();

    // Se não encontrou atividades na nova estrutura, buscar nos caminhos antigos
    if (empty($atividades)) {
        $database = new Database();
        $conn = $database->conn;

        // Buscar informações da unidade
        $sql_unidade = "SELECT idioma, nivel FROM unidades WHERE id = ?";
        $stmt_unidade = $conn->prepare($sql_unidade);
        $stmt_unidade->bind_param("i", $unidade_id);
        $stmt_unidade->execute();
        $unidade_info = $stmt_unidade->get_result()->fetch_assoc();
        $stmt_unidade->close();

        if ($unidade_info) {
            // Buscar caminhos de aprendizagem compatíveis
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
            
            while ($row = $result_caminhos->fetch_assoc()) {
                // Usar ID negativo para diferenciar de atividades reais
                $row['id'] = -$row['id'];
                $atividades[] = $row;
            }
            
            $stmt_caminhos->close();
        }
        
        $database->closeConnection();
    }

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
