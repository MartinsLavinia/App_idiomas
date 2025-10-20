<?php
// processar_exercicio.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
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

// Ler dados JSON da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

// Validar dados obrigatórios
if (!isset($data['exercicio_id']) || !isset($data['resposta']) || !isset($data['tipo_exercicio'])) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios ausentes']);
    exit();
}

$exercicio_id = $data['exercicio_id'];
$resposta_usuario = $data['resposta'];
$tipo_exercicio = $data['tipo_exercicio'];
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar dados do exercício
    $sql = "SELECT conteudo, tipo_exercicio FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $exercicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    // Processar resposta baseado no tipo
    $conteudo = json_decode($exercicio['conteudo'], true);
    $resultado = processarResposta($resposta_usuario, $conteudo, $tipo_exercicio);

    // Registrar progresso do usuário
    registrarProgresso($conn, $id_usuario, $exercicio_id, $resultado['correto'], $resultado['pontuacao'] ?? 0);

    $database->closeConnection();

    echo json_encode($resultado);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function processarResposta($resposta_usuario, $conteudo, $tipo_exercicio) {
    switch ($tipo_exercicio) {
        case 'multipla_escolha':
            return processarMultiplaEscolha($resposta_usuario, $conteudo);
        case 'texto_livre':
            return processarTextoLivre($resposta_usuario, $conteudo);
        case 'fala':
            return processarFala($resposta_usuario, $conteudo);
        case 'arrastar_soltar':
            return processarArrastarSoltar($resposta_usuario, $conteudo);
        case 'completar':
            return processarCompletar($resposta_usuario, $conteudo);
        case 'listening':
            return processarListening($resposta_usuario, $conteudo);
        default:
            return ['success' => false, 'correto' => false, 'explicacao' => 'Tipo de exercício não suportado.'];
    }
}

// FUNÇÃO CORRIGIDA: Processar exercício de listening
function processarListening($resposta_usuario, $conteudo) {
    if (!isset($conteudo['resposta_correta']) || !isset($conteudo['opcoes'])) {
        return [
            'success' => false,
            'correto' => false,
            'explicacao' => 'Exercício de listening mal configurado.'
        ];
    }

    $resposta_correta_index = $conteudo['resposta_correta_index']; // Agora espera o índice
    $resposta_correta_texto = $conteudo['opcoes'][$resposta_correta_index] ?? '';
    
    // Comparar o índice da resposta do usuário com o índice correto
    $correto = (intval($resposta_usuario) === $resposta_correta_index);

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Excelente! Você acertou!' : 'Resposta incorreta. Tente novamente.'),
        'mensagem' => $correto ? '🎉 Parabéns! Resposta correta!' : '❌ Resposta incorreta.',
        'audio_url' => $conteudo['audio_url'] ?? '',
        'frase_original' => $conteudo['frase_original'] ?? '',
        'resposta_correta' => $resposta_correta_texto,
        'resposta_correta_index' => $resposta_correta_index,
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarMultiplaEscolha($resposta_usuario, $conteudo) {
    if (!isset($conteudo['alternativas'])) {
        return ['success' => false, 'correto' => false, 'explicacao' => 'Exercício mal configurado.'];
    }

    $resposta_correta = null;
    foreach ($conteudo['alternativas'] as $alt) {
        if (isset($alt['correta']) && $alt['correta']) {
            $resposta_correta = $alt['id'] ?? $alt['texto'];
            break;
        }
    }

    if (!$resposta_correta && isset($conteudo['resposta_correta'])) {
        $resposta_correta = $conteudo['resposta_correta'];
    }

    $correto = strtolower($resposta_usuario) === strtolower($resposta_correta);

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : 'Resposta incorreta.'),
        'dica' => $correto ? null : ($conteudo['dica'] ?? null),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarTextoLivre($resposta_usuario, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? '';
    $alternativas_aceitas = $conteudo['alternativas_aceitas'] ?? [$resposta_correta];

    // Limpar e normalizar a resposta do usuário
    $resposta_limpa = trim(strtolower($resposta_usuario));
    
    $correto = false;
    $melhor_similaridade = 0;
    $resposta_encontrada = '';
    
    foreach ($alternativas_aceitas as $alternativa) {
        // Limpar e normalizar alternativa
        $alt_limpa = trim(strtolower($alternativa));
        
        // Verificar correspondência exata
        if ($resposta_limpa === $alt_limpa) {
            $correto = true;
            $melhor_similaridade = 1.0;
            $resposta_encontrada = $alternativa;
            break;
        }
        
        // Calcular similaridade
        $similaridade = calcularSimilaridade($resposta_limpa, $alt_limpa);
        if ($similaridade > $melhor_similaridade) {
            $melhor_similaridade = $similaridade;
            $resposta_encontrada = $alternativa;
        }
    }
    
    // Considerar correto se similaridade >= 80%
    if (!$correto && $melhor_similaridade >= 0.8) {
        $correto = true;
    }

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : "Resposta incorreta. Esperado: '$resposta_encontrada'"),
        'dica' => $correto ? null : ($conteudo['dica'] ?? "Tente novamente. Resposta esperada: '$resposta_encontrada'"),
        'pontuacao' => $correto ? 100 : ($melhor_similaridade * 100),
        'similaridade' => $melhor_similaridade,
        'resposta_correta' => $resposta_encontrada
    ];
}

function processarCompletar($resposta_usuario, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? '';
    $alternativas_aceitas = $conteudo['alternativas_aceitas'] ?? [$resposta_correta];

    // Limpar e normalizar a resposta do usuário
    $resposta_limpa = trim(strtolower($resposta_usuario));
    
    $correto = false;
    $melhor_similaridade = 0;
    $resposta_encontrada = '';
    
    foreach ($alternativas_aceitas as $alternativa) {
        // Limpar e normalizar alternativa
        $alt_limpa = trim(strtolower($alternativa));
        
        // Verificar correspondência exata
        if ($resposta_limpa === $alt_limpa) {
            $correto = true;
            $melhor_similaridade = 1.0;
            $resposta_encontrada = $alternativa;
            break;
        }
        
        // Calcular similaridade
        $similaridade = calcularSimilaridade($resposta_limpa, $alt_limpa);
        if ($similaridade > $melhor_similaridade) {
            $melhor_similaridade = $similaridade;
            $resposta_encontrada = $alternativa;
        }
    }
    
    // Considerar correto se similaridade >= 80%
    if (!$correto && $melhor_similaridade >= 0.8) {
        $correto = true;
    }

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : "Resposta incorreta. Esperado: '$resposta_encontrada'"),
        'dica' => $correto ? null : ($conteudo['dica'] ?? "Tente novamente. Resposta esperada: '$resposta_encontrada'"),
        'pontuacao' => $correto ? 100 : ($melhor_similaridade * 100),
        'similaridade' => $melhor_similaridade,
        'resposta_correta' => $resposta_encontrada
    ];
}

function processarFala($resposta_usuario, $conteudo) {
    // Simulação de processamento de fala
    $correto = $resposta_usuario === 'fala_processada';

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? 'Excelente pronúncia!' : 'Tente novamente, prestando atenção à pronúncia.',
        'dica' => $correto ? null : 'Ouça o áudio de exemplo e pratique devagar.',
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarArrastarSoltar($resposta_usuario, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? [];
    
    // Comparar arrays de resposta
    $correto = json_encode($resposta_usuario) === json_encode($resposta_correta);

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : 'Verifique as posições dos elementos.'),
        'dica' => $correto ? null : ($conteudo['dica'] ?? 'Leia as categorias com atenção.'),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function calcularSimilaridade($str1, $str2) {
    $str1 = trim(strtolower($str1));
    $str2 = trim(strtolower($str2));
    
    if ($str1 === $str2) {
        return 1.0;
    }
    
    // Remover pontuação para melhor comparação
    $str1 = preg_replace('/[^\w\s]/', '', $str1);
    $str2 = preg_replace('/[^\w\s]/', '', $str2);
    
    // Calcular similaridade usando Levenshtein distance
    $distancia = levenshtein($str1, $str2);
    $max_length = max(strlen($str1), strlen($str2));
    
    if ($max_length == 0) {
        return 1.0;
    }
    
    return 1 - ($distancia / $max_length);
}

function registrarProgresso($conn, $id_usuario, $exercicio_id, $acertou, $pontuacao) {
    // Buscar informações do exercício para saber a qual bloco pertence
    $sql_exercicio = "SELECT bloco_id FROM exercicios WHERE id = ?";
    $stmt_exercicio = $conn->prepare($sql_exercicio);
    $stmt_exercicio->bind_param("i", $exercicio_id);
    $stmt_exercicio->execute();
    $exercicio_info = $stmt_exercicio->get_result()->fetch_assoc();
    $stmt_exercicio->close();
    
    $bloco_id = $exercicio_info['bloco_id'] ?? null;
    
    if (!$bloco_id) {
        return; // Se não tem bloco, não registra progresso
    }
    
    // Verificar se já existe progresso para este bloco
    $sql_check = "SELECT * FROM progresso_bloco WHERE id_usuario = ? AND id_bloco = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_usuario, $bloco_id);
    $stmt_check->execute();
    $progresso_existente = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    // Buscar total de exercícios no bloco
    $sql_total = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $bloco_id);
    $stmt_total->execute();
    $total_exercicios = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();

    if ($progresso_existente) {
        // Atualizar progresso existente
        $novas_concluidas = $progresso_existente['atividades_concluidas'] + 1;
        $novos_pontos = $progresso_existente['pontos_obtidos'] + ($acertou ? 10 : 0);
        $novo_progresso = $total_exercicios > 0 ? round(($novas_concluidas / $total_exercicios) * 100) : 0;
        $concluido = ($novas_concluidas >= $total_exercicios) && ($total_exercicios > 0);

        $sql_update = "UPDATE progresso_bloco SET 
                      atividades_concluidas = ?, 
                      pontos_obtidos = ?,
                      progresso_percentual = ?,
                      concluido = ?,
                      data_atualizacao = NOW()
                      WHERE id_usuario = ? AND id_bloco = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iiiiii", $novas_concluidas, $novos_pontos, $novo_progresso, $concluido, $id_usuario, $bloco_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Criar novo registro de progresso
        $atividades_concluidas = 1;
        $pontos_obtidos = $acertou ? 10 : 0;
        $progresso_percentual = $total_exercicios > 0 ? round(($atividades_concluidas / $total_exercicios) * 100) : 0;
        $concluido = false;

        $sql_insert = "INSERT INTO progresso_bloco 
                      (id_usuario, id_bloco, atividades_concluidas, total_atividades, pontos_obtidos, total_pontos, progresso_percentual, concluido, data_criacao, data_atualizacao) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $total_pontos = $total_exercicios * 10;
        $stmt_insert->bind_param("iiiiiiii", $id_usuario, $bloco_id, $atividades_concluidas, $total_exercicios, $pontos_obtidos, $total_pontos, $progresso_percentual, $concluido);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>