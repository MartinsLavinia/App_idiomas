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

// Aceitar tanto atividade_id quanto bloco_id
$id_busca = null;
$tipo_busca = '';

if (isset($_GET['bloco_id']) && is_numeric($_GET['bloco_id'])) {
    $id_busca = (int)$_GET['bloco_id'];
    $tipo_busca = 'bloco';
} elseif (isset($_GET['atividade_id']) && is_numeric($_GET['atividade_id'])) {
    $id_busca = (int)$_GET['atividade_id'];
    $tipo_busca = 'atividade';
} else {
    echo json_encode(['success' => false, 'message' => 'ID do bloco ou atividade inválido']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    $exercicios = [];
    $idioma_exercicio = null; // Variável para armazenar o idioma

    if ($tipo_busca === 'bloco') {
        // Buscar exercícios por bloco_id
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo, 
                e.tipo AS tipo_exercicio,
                e.caminho_id,
                e.bloco_id,
                u.idioma AS idioma_exercicio
            FROM exercicios e
            JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
            JOIN unidades u ON c.id_unidade = u.id
            WHERE e.bloco_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    } else {
        // Buscar exercícios por caminho_id (atividade)
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo, 
                e.tipo AS tipo_exercicio,
                e.caminho_id,
                e.bloco_id,
                u.idioma AS idioma_exercicio
            FROM exercicios e
            JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
            JOIN unidades u ON c.id_unidade = u.id
            WHERE e.caminho_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Obter o idioma do primeiro exercício encontrado
        if ($idioma_exercicio === null) {
            $idioma_exercicio = $row['idioma_exercicio'];
        }
        unset($row['idioma_exercicio']); // Remove do array de exercício para não poluir
        
        // Processar o conteúdo se for JSON string
        if ($row['conteudo'] && is_string($row['conteudo']) && ($row['conteudo'][0] === '{' || $row['conteudo'][0] === '[')) {
            try {
                $conteudo_decodificado = json_decode($row['conteudo'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['conteudo'] = $conteudo_decodificado;
                    
                    // Lógica aprimorada para determinar tipo_exercicio
                    if ($row['tipo_exercicio'] === null || $row['tipo_exercicio'] === '') {
                        if (isset($conteudo_decodificado['alternativas'])) {
                            $row['tipo_exercicio'] = 'multipla_escolha';
                        } elseif (isset($conteudo_decodificado['frase_completar'])) {
                            $row['tipo_exercicio'] = 'completar';
                        } elseif (isset($conteudo_decodificado['frase_esperada']) || isset($conteudo_decodificado['texto_para_falar'])) {
                            $row['tipo_exercicio'] = 'fala';
                        } elseif (isset($conteudo_decodificado['audio_url']) || isset($conteudo_decodificado['arquivo_audio'])) {
                            $row['tipo_exercicio'] = 'audicao';
                        } elseif (isset($conteudo_decodificado['texto_traduzir'])) {
                            $row['tipo_exercicio'] = 'traducao';
                        } else {
                            $row['tipo_exercicio'] = 'texto_livre';
                        }
                    }
                    
                    // Adicionar metadados específicos para exercícios de fala
                    if ($row['tipo_exercicio'] === 'fala') {
                        // Garantir que os campos específicos de fala estejam presentes
                        if (!isset($row['conteudo']['texto_para_falar']) && isset($row['conteudo']['frase_esperada'])) {
                            $row['conteudo']['texto_para_falar'] = $row['conteudo']['frase_esperada'];
                        }
                        
                        // Adicionar configurações padrão para exercícios de fala
                        $row['conteudo']['config_fala'] = array_merge([
                            'dificuldade' => 'medio',
                            'tempo_maximo_gravacao' => 30,
                            'tentativas_permitidas' => 3,
                            'tolerancia_pronuncia' => 0.7,
                            'feedback_imediato' => true
                        ], $row['conteudo']['config_fala'] ?? []);
                        
                        // Adicionar idioma ao conteúdo do exercício de fala
                        $row['conteudo']['idioma'] = $idioma_exercicio;
                    }
                }
            } catch (Exception $e) {
                // Manter como string se der erro
                error_log("Erro ao decodificar JSON: " . $e->getMessage());
            }
        }
        
        $exercicios[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'tipo_busca' => $tipo_busca,
        'id_busca' => $id_busca,
        'total_exercicios' => count($exercicios),
        'idioma' => $idioma_exercicio, // Adiciona o idioma
        'exercicios' => $exercicios
    ]);

} catch (Exception $e) {
    error_log("Erro em get_exercicio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>