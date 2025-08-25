<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar se os dados necessários foram enviados
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['exercicio_id']) || !isset($input['resposta_usuario']) || !isset($input['resposta_esperada'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$exercicio_id = (int)$input['exercicio_id'];
$resposta_usuario = trim($input['resposta_usuario']);
$resposta_esperada = trim($input['resposta_esperada']);
$alternativas_aceitas = $input['alternativas_aceitas'] ?? [$resposta_esperada];
$tipo_exercicio = $input['tipo_exercicio'] ?? 'texto_livre';
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Analisar resposta escrita
    $resultado_analise = analisarResposta($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $tipo_exercicio);
    
    // Atualizar progresso do usuário na tabela progresso_usuario
    atualizarProgressoEscrita($conn, $exercicio_id, $id_usuario, $resultado_analise);
    
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'resultado' => $resultado_analise
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function analisarResposta($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $tipo_exercicio) {
    $resposta_usuario = strtolower(trim($resposta_usuario));
    $resposta_esperada = strtolower(trim($resposta_esperada));
    
    // Normalizar alternativas aceitas
    $alternativas_normalizadas = array_map(function($alt) {
        return strtolower(trim($alt));
    }, $alternativas_aceitas);
    
    // Verificar se a resposta está correta
    $correto = false;
    $pontuacao = 0;
    $erros_encontrados = [];
    $pontos_positivos = [];
    $sugestoes_melhoria = [];
    
    // Verificar correspondência exata
    if (in_array($resposta_usuario, $alternativas_normalizadas)) {
        $correto = true;
        $pontuacao = 1.0;
        $pontos_positivos[] = 'Resposta completamente correta!';
    } else {
        // Verificar correspondência parcial
        $melhor_similaridade = 0;
        foreach ($alternativas_normalizadas as $alternativa) {
            $similaridade = calcularSimilaridade($resposta_usuario, $alternativa);
            if ($similaridade > $melhor_similaridade) {
                $melhor_similaridade = $similaridade;
            }
        }
        
        $pontuacao = $melhor_similaridade;
        
        if ($melhor_similaridade >= 0.8) {
            $correto = true;
            $pontos_positivos[] = 'Resposta quase perfeita!';
            if ($melhor_similaridade < 1.0) {
                $sugestoes_melhoria[] = 'Verifique a ortografia de algumas palavras.';
            }
        } elseif ($melhor_similaridade >= 0.6) {
            $pontos_positivos[] = 'Você está no caminho certo!';
            $erros_encontrados[] = [
                'descricao' => 'Resposta parcialmente correta',
                'sugestao' => 'Revise a estrutura da frase e a ortografia.'
            ];
            $sugestoes_melhoria[] = 'Tente usar palavras mais precisas.';
        } else {
            $erros_encontrados[] = [
                'descricao' => 'Resposta incorreta',
                'sugestao' => 'Releia a pergunta e tente novamente.'
            ];
            $sugestoes_melhoria[] = 'Pense no contexto da pergunta.';
            $sugestoes_melhoria[] = 'Use palavras simples que você conhece bem.';
        }
    }
    
    // Determinar status
    $status = 'errado';
    if ($pontuacao >= 0.9) {
        $status = 'correto';
    } elseif ($pontuacao >= 0.8) {
        $status = 'quase_correto';
    } elseif ($pontuacao >= 0.6) {
        $status = 'meio_correto';
    }
    
    return [
        'status' => $status,
        'pontuacao' => $pontuacao,
        'correto' => $correto,
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'erros_encontrados' => $erros_encontrados,
        'pontos_positivos' => $pontos_positivos,
        'sugestoes_melhoria' => $sugestoes_melhoria
    ];
}

function calcularSimilaridade($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    if ($str1 === $str2) {
        return 1.0;
    }
    
    // Calcular similaridade usando Levenshtein distance
    $distancia = levenshtein($str1, $str2);
    $max_length = max(strlen($str1), strlen($str2));
    
    if ($max_length == 0) {
        return 1.0;
    }
    
    $similaridade = 1 - ($distancia / $max_length);
    
    // Verificar se contém palavras-chave
    $palavras_str2 = explode(' ', $str2);
    $palavras_encontradas = 0;
    foreach ($palavras_str2 as $palavra) {
        if (strpos($str1, $palavra) !== false) {
            $palavras_encontradas++;
        }
    }
    
    if (count($palavras_str2) > 0) {
        $bonus_palavras = ($palavras_encontradas / count($palavras_str2)) * 0.3;
        $similaridade = min(1.0, $similaridade + $bonus_palavras);
    }
    
    return round($similaridade, 2);
}

function atualizarProgressoEscrita($conn, $exercicio_id, $id_usuario, $resultado_analise) {
    $pontuacao = $resultado_analise['pontuacao'];
    $correto = $resultado_analise['correto'];
    $feedback_json = json_encode($resultado_analise);
    
    // Verificar se já existe progresso para este exercício na tabela progresso_usuario
    $sql_check = "SELECT id, tentativas, acertos FROM progresso_usuario WHERE exercicio_id = ? AND id_usuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $exercicio_id, $id_usuario);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $progresso_existente = $result->fetch_assoc();
    $stmt_check->close();
    
    if ($progresso_existente) {
        // Atualizar progresso existente
        $novas_tentativas = $progresso_existente['tentativas'] + 1;
        $novos_acertos = $progresso_existente['acertos'] + ($correto ? 1 : 0);
        $concluido = $correto ? 1 : 0;
        
        $sql_update = "
            UPDATE progresso_usuario 
            SET tentativas = ?, acertos = ?, concluido = ?, pontuacao = ?, data_conclusao = ?, feedback_escrita = ?
            WHERE id = ?
        ";
        
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iiidssi", $novas_tentativas, $novos_acertos, $concluido, $pontuacao, $data_conclusao, $feedback_json, $progresso_existente['id']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Buscar informações do exercício para obter caminho_id e idioma/nível
        $sql_exercicio = "
            SELECT e.caminho_id, c.idioma, c.nivel 
            FROM exercicios e 
            LEFT JOIN caminhos_aprendizagem c ON e.caminho_id = c.id 
            WHERE e.id = ?
        ";
        $stmt_exercicio = $conn->prepare($sql_exercicio);
        $stmt_exercicio->bind_param("i", $exercicio_id);
        $stmt_exercicio->execute();
        $exercicio_info = $stmt_exercicio->get_result()->fetch_assoc();
        $stmt_exercicio->close();
        
        // Criar novo progresso
        $tentativas = 1;
        $acertos = $correto ? 1 : 0;
        $concluido = $correto ? 1 : 0;
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        
        $idioma = $exercicio_info['idioma'] ?? 'Ingles';
        $nivel = $exercicio_info['nivel'] ?? 'A1';
        $caminho_id = $exercicio_info['caminho_id'] ?? null;
        
        $sql_insert = "
            INSERT INTO progresso_usuario 
            (id_usuario, idioma, nivel, caminho_id, exercicio_id, exercicio_atual, concluido, tentativas, acertos, pontuacao, data_conclusao, feedback_escrita) 
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("issiiiidss", $id_usuario, $idioma, $nivel, $caminho_id, $exercicio_id, $concluido, $tentativas, $acertos, $pontuacao, $data_conclusao, $feedback_json);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>
