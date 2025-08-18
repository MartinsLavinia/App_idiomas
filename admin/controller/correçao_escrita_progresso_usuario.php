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

if (!isset($input['exercicio_id']) || !isset($input['audio_data']) || !isset($input['frase_esperada'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$exercicio_id = (int)$input['exercicio_id'];
$audio_data = $input['audio_data']; // Base64 encoded audio
$frase_esperada = trim(strtolower($input['frase_esperada']));
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Simular análise de áudio (em produção, usaria APIs como Google Speech-to-Text)
    $resultado_analise = analisarAudio($audio_data, $frase_esperada);
    
    // Atualizar progresso do usuário na tabela progresso_usuario
    atualizarProgressoAudio($conn, $exercicio_id, $id_usuario, $resultado_analise);
    
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

function analisarAudio($audio_data, $frase_esperada) {
    // Simular transcrição do áudio (em produção, usaria API real)
    $transcricoes_simuladas = [
        'hello' => ['hello', 'helo', 'hallo', 'hellow'],
        'good morning' => ['good morning', 'gud morning', 'good mornin', 'goo morning'],
        'how are you' => ['how are you', 'how r you', 'how are u', 'ow are you'],
        'my name is' => ['my name is', 'mai name is', 'my nam is', 'mi name is'],
        'thank you' => ['thank you', 'tank you', 'thank u', 'tanku'],
        'nice to meet you' => ['nice to meet you', 'nice to met you', 'nic to meet you']
    ];
    
    // Encontrar a transcrição mais próxima
    $transcricao_simulada = simularTranscricao($frase_esperada, $transcricoes_simuladas);
    
    // Calcular pontuação baseada na similaridade
    $pontuacao = calcularSimilaridade($frase_esperada, $transcricao_simulada);
    
    // Determinar status baseado na pontuação
    $status = determinarStatus($pontuacao);
    
    // Gerar feedback detalhado
    $feedback_detalhado = gerarFeedbackDetalhado($frase_esperada, $transcricao_simulada, $pontuacao, $status);
    
    return [
        'transcricao' => $transcricao_simulada,
        'pontuacao' => $pontuacao,
        'status' => $status,
        'feedback_detalhado' => $feedback_detalhado
    ];
}

function simularTranscricao($frase_esperada, $transcricoes_simuladas) {
    // Buscar transcrição simulada baseada na frase esperada
    foreach ($transcricoes_simuladas as $frase => $variacoes) {
        if (strpos($frase_esperada, $frase) !== false) {
            // Retornar uma variação aleatória para simular diferentes níveis de pronúncia
            $rand = rand(0, 100);
            if ($rand < 30) {
                return $variacoes[0]; // Pronúncia perfeita
            } elseif ($rand < 60) {
                return $variacoes[1]; // Pronúncia boa
            } elseif ($rand < 80) {
                return $variacoes[2]; // Pronúncia média
            } else {
                return $variacoes[3]; // Pronúncia ruim
            }
        }
    }
    
    // Se não encontrar, simular uma transcrição com base na frase esperada
    return simularErrosPronuncia($frase_esperada);
}

function simularErrosPronuncia($frase) {
    $erros_comuns = [
        'th' => 'd',
        'r' => 'w',
        'v' => 'b',
        'ing' => 'in',
        'ed' => 'd'
    ];
    
    $frase_com_erros = $frase;
    $rand = rand(0, 100);
    
    if ($rand < 20) {
        // 20% chance de pronúncia perfeita
        return $frase;
    } elseif ($rand < 50) {
        // 30% chance de um erro pequeno
        foreach ($erros_comuns as $correto => $erro) {
            if (strpos($frase_com_erros, $correto) !== false) {
                $frase_com_erros = str_replace($correto, $erro, $frase_com_erros);
                break;
            }
        }
    } else {
        // 50% chance de múltiplos erros
        foreach ($erros_comuns as $correto => $erro) {
            if (strpos($frase_com_erros, $correto) !== false && rand(0, 1)) {
                $frase_com_erros = str_replace($correto, $erro, $frase_com_erros);
            }
        }
    }
    
    return $frase_com_erros;
}

function calcularSimilaridade($esperada, $transcrita) {
    $esperada = strtolower(trim($esperada));
    $transcrita = strtolower(trim($transcrita));
    
    // Calcular similaridade usando Levenshtein distance
    $distancia = levenshtein($esperada, $transcrita);
    $max_length = max(strlen($esperada), strlen($transcrita));
    
    if ($max_length == 0) {
        return 1.0;
    }
    
    $similaridade = 1 - ($distancia / $max_length);
    return round($similaridade, 2);
}

function determinarStatus($pontuacao) {
    if ($pontuacao >= 0.9) {
        return 'correto';
    } elseif ($pontuacao >= 0.7) {
        return 'meio_correto';
    } else {
        return 'errado';
    }
}

function gerarFeedbackDetalhado($esperada, $transcrita, $pontuacao, $status) {
    $feedback = [
        'status' => $status,
        'pontuacao_percentual' => round($pontuacao * 100),
        'frase_esperada' => $esperada,
        'frase_transcrita' => $transcrita,
        'palavras_corretas' => [],
        'palavras_incorretas' => [],
        'sugestoes' => [],
        'pontos_melhoria' => []
    ];
    
    // Analisar palavra por palavra
    $palavras_esperadas = explode(' ', $esperada);
    $palavras_transcritas = explode(' ', $transcrita);
    
    for ($i = 0; $i < count($palavras_esperadas); $i++) {
        $palavra_esperada = $palavras_esperadas[$i];
        $palavra_transcrita = isset($palavras_transcritas[$i]) ? $palavras_transcritas[$i] : '';
        
        if ($palavra_esperada === $palavra_transcrita) {
            $feedback['palavras_corretas'][] = $palavra_esperada;
        } else {
            $feedback['palavras_incorretas'][] = [
                'esperada' => $palavra_esperada,
                'transcrita' => $palavra_transcrita,
                'sugestao' => gerarSugestaoPalavra($palavra_esperada, $palavra_transcrita)
            ];
        }
    }
    
    // Gerar sugestões baseadas no status
    switch ($status) {
        case 'correto':
            $feedback['sugestoes'][] = 'Excelente pronúncia! Continue praticando para manter a qualidade.';
            $feedback['sugestoes'][] = 'Sua pronúncia está muito clara e precisa.';
            break;
            
        case 'meio_correto':
            $feedback['sugestoes'][] = 'Boa pronúncia! Há alguns pontos que podem ser melhorados.';
            $feedback['sugestoes'][] = 'Tente falar um pouco mais devagar para melhorar a clareza.';
            $feedback['pontos_melhoria'][] = 'Foque na articulação das consoantes';
            $feedback['pontos_melhoria'][] = 'Pratique a entonação das palavras';
            break;
            
        case 'errado':
            $feedback['sugestoes'][] = 'Continue praticando! A pronúncia melhora com o tempo.';
            $feedback['sugestoes'][] = 'Tente ouvir a pronúncia correta várias vezes antes de repetir.';
            $feedback['sugestoes'][] = 'Fale mais devagar e articule bem cada palavra.';
            $feedback['pontos_melhoria'][] = 'Trabalhe na pronúncia individual de cada palavra';
            $feedback['pontos_melhoria'][] = 'Pratique os sons mais difíceis separadamente';
            $feedback['pontos_melhoria'][] = 'Use um espelho para observar o movimento da boca';
            break;
    }
    
    return $feedback;
}

function gerarSugestaoPalavra($esperada, $transcrita) {
    $sugestoes = [
        'hello' => 'Pronuncie como "hê-LÔU", com ênfase na segunda sílaba',
        'good' => 'Pronuncie como "GUD", com som de U fechado',
        'morning' => 'Pronuncie como "MÓR-ning", com R bem marcado',
        'how' => 'Pronuncie como "RÁU", começando com som de R',
        'are' => 'Pronuncie como "ÁR", com R no final',
        'you' => 'Pronuncie como "IÚ", som de I seguido de U',
        'name' => 'Pronuncie como "NÊIM", com som de EI',
        'thank' => 'Pronuncie como "ZÊNK", com TH como Z',
        'nice' => 'Pronuncie como "NÁIS", com som de AI',
        'meet' => 'Pronuncie como "MÍT", com I longo'
    ];
    
    return isset($sugestoes[$esperada]) ? $sugestoes[$esperada] : "Pratique a pronúncia de '{$esperada}' várias vezes";
}

function atualizarProgressoAudio($conn, $exercicio_id, $id_usuario, $resultado_analise) {
    $pontuacao = $resultado_analise['pontuacao'];
    $feedback_json = json_encode($resultado_analise['feedback_detalhado']);
    
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
        $novos_acertos = $progresso_existente['acertos'] + ($pontuacao >= 0.7 ? 1 : 0);
        $concluido = $pontuacao >= 0.7 ? 1 : 0;
        
        $sql_update = "
            UPDATE progresso_usuario 
            SET tentativas = ?, acertos = ?, concluido = ?, pontuacao = ?, data_conclusao = ?, feedback_audio = ?
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
        $acertos = $pontuacao >= 0.7 ? 1 : 0;
        $concluido = $pontuacao >= 0.7 ? 1 : 0;
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        
        $idioma = $exercicio_info['idioma'] ?? 'Ingles';
        $nivel = $exercicio_info['nivel'] ?? 'A1';
        $caminho_id = $exercicio_info['caminho_id'] ?? null;
        
        $sql_insert = "
            INSERT INTO progresso_usuario 
            (id_usuario, idioma, nivel, caminho_id, exercicio_id, exercicio_atual, concluido, tentativas, acertos, pontuacao, data_conclusao, feedback_audio) 
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("issiiiidss", $id_usuario, $idioma, $nivel, $caminho_id, $exercicio_id, $concluido, $tentativas, $acertos, $pontuacao, $data_conclusao, $feedback_json);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>

