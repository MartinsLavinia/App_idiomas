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
                             WHERE id_usuario = ? AND id_bloco = ?";
            $stmt_progresso = $conn->prepare($sql_progresso);
            $stmt_progresso->bind_param("ii", $id_usuario, $row['id']);
            $stmt_progresso->execute();
            $progresso_result = $stmt_progresso->get_result();
            
            if ($progresso_data = $progresso_result->fetch_assoc()) {
                $bloco['progresso'] = $progresso_data;
            } else {
                // Se não há progresso, calcular baseado nos exercícios (máximo 12)
                $sql_total = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ? LIMIT 12";
                $stmt_total = $conn->prepare($sql_total);
                if ($stmt_total) {
                    $stmt_total->bind_param("i", $row['id']);
                    $stmt_total->execute();
                    $total_exercicios = min(12, $stmt_total->get_result()->fetch_assoc()['total']);
                    $stmt_total->close();
                } else {
                    $total_exercicios = 0;
                }
                
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
    
    // Adicionar exercícios especiais como blocos - SEMPRE
    $sql_especiais = "SELECT id, titulo, descricao, url_media, transcricao, pergunta, tipo_exercicio, opcoes_resposta, resposta_correta, conteudo FROM exercicios_especiais WHERE id_bloco IS NOT NULL ORDER BY id";
    $stmt_especiais = $conn->prepare($sql_especiais);
    $stmt_especiais->execute();
    $result_especiais = $stmt_especiais->get_result();
    
    $count_especiais = 0;
    while ($row_especial = $result_especiais->fetch_assoc()) {
        // Tentar decodificar o campo conteudo se existir, senão criar estrutura
        $conteudo_json = null;
        if (!empty($row_especial['conteudo'])) {
            $conteudo_json = json_decode($row_especial['conteudo'], true);
        }
        
        // Criar estrutura de conteúdo baseada nos campos da tabela
        $conteudo_estruturado = [
            'tipo_exercicio' => $conteudo_json['tipo_exercicio'] ?? 'observar',
            'link_video' => $conteudo_json['link_video'] ?? $row_especial['url_media'] ?? '',
            'letra_musica' => $conteudo_json['letra_musica'] ?? $row_especial['transcricao'] ?? '',
            'pergunta' => $row_especial['pergunta'],
            'opcoes_resposta' => $row_especial['opcoes_resposta'] ? json_decode($row_especial['opcoes_resposta'], true) : null,
            'resposta_correta' => $row_especial['resposta_correta'] ? json_decode($row_especial['resposta_correta'], true) : null
        ];
        
        $bloco_especial = [
            'id' => 'especial_' . $row_especial['id'],
            'nome_bloco' => $row_especial['titulo'],
            'descricao' => $row_especial['descricao'] ?? 'Exercício especial com vídeo/música',
            'ordem' => 999 + $row_especial['id'],
            'caminho_id' => null,
            'nome_caminho' => 'Especial',
            'tipo' => 'especial',
            'exercicio_especial' => [
                'id' => $row_especial['id'],
                'titulo' => $row_especial['titulo'],
                'tipo_exercicio' => $conteudo_estruturado['tipo_exercicio'],
                'conteudo_completo' => $conteudo_estruturado
            ],
            'progresso' => [
                'progresso_percentual' => 100,
                'atividades_concluidas' => 1,
                'total_atividades' => 1,
                'concluido' => false
            ]
        ];
        
        $blocos[] = $bloco_especial;
        $count_especiais++;
    }
    
    $stmt_especiais->close();
    $database->closeConnection();
    
    echo json_encode([
        'success' => true, 
        'blocos' => $blocos, 
        'total_especiais' => $count_especiais, 
        'debug_info' => [
            'total_blocos' => count($blocos), 
            'especiais_adicionados' => $count_especiais,
            'blocos_normais' => count($blocos) - $count_especiais
        ]
    ]);
    
} catch (Exception $e) {
    $database->closeConnection();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>