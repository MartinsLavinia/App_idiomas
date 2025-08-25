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
$frase_completa = $input['frase_completa'] ?? null;
$palavra_faltante = $input['palavra_faltante'] ?? null;
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Analisar resposta com sistema melhorado
    $resultado_analise = analisarRespostaMelhorada($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $tipo_exercicio, $frase_completa, $palavra_faltante);
    
    // Salvar resultado na tabela de feedback de escrita
    salvarFeedbackEscrita($conn, $exercicio_id, $id_usuario, $resultado_analise);
    
    // Atualizar progresso do usuário
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

function analisarRespostaMelhorada($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $tipo_exercicio, $frase_completa = null, $palavra_faltante = null) {
    // Normalizar respostas
    $resposta_usuario_limpa = limparTextoEscrita($resposta_usuario);
    $resposta_esperada_limpa = limparTextoEscrita($resposta_esperada);
    $alternativas_limpas = array_map('limparTextoEscrita', $alternativas_aceitas);
    
    // Análise específica por tipo de exercício
    switch ($tipo_exercicio) {
        case 'completar':
            return analisarExercicioCompletar($resposta_usuario_limpa, $palavra_faltante, $alternativas_limpas, $frase_completa);
        case 'traducao':
            return analisarExercicioTraducao($resposta_usuario_limpa, $resposta_esperada_limpa, $alternativas_limpas);
        case 'gramatica':
            return analisarExercicioGramatica($resposta_usuario_limpa, $resposta_esperada_limpa, $alternativas_limpas);
        case 'texto_livre':
        default:
            return analisarExercicioTextoLivre($resposta_usuario_limpa, $resposta_esperada_limpa, $alternativas_limpas);
    }
}

function limparTextoEscrita($texto) {
    // Normalizar texto mantendo pontuação importante
    $texto = trim($texto);
    $texto = strtolower($texto);
    // Remover pontuação extra mas manter apostrofes
    $texto = preg_replace('/[^\w\s\']/', '', $texto);
    // Normalizar espaços
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

function analisarExercicioCompletar($resposta_usuario, $palavra_faltante, $alternativas_aceitas, $frase_completa) {
    $palavra_faltante_limpa = limparTextoEscrita($palavra_faltante);
    
    // Verificar se a resposta está nas alternativas aceitas
    $correto = false;
    $melhor_match = '';
    $pontuacao_maxima = 0;
    
    foreach ($alternativas_aceitas as $alternativa) {
        $similaridade = calcularSimilaridadeTextoEscrita($resposta_usuario, $alternativa);
        if ($similaridade > $pontuacao_maxima) {
            $pontuacao_maxima = $similaridade;
            $melhor_match = $alternativa;
        }
        
        if ($resposta_usuario === $alternativa || $similaridade >= 0.9) {
            $correto = true;
            break;
        }
    }
    
    // Se não encontrou match exato, verificar similaridade com palavra principal
    if (!$correto) {
        $similaridade_principal = calcularSimilaridadeTextoEscrita($resposta_usuario, $palavra_faltante_limpa);
        if ($similaridade_principal > $pontuacao_maxima) {
            $pontuacao_maxima = $similaridade_principal;
            $melhor_match = $palavra_faltante_limpa;
        }
        $correto = $similaridade_principal >= 0.8;
    }
    
    $status = determinarStatusEscrita($pontuacao_maxima, $correto);
    
    return [
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $palavra_faltante_limpa,
        'correto' => $correto,
        'pontuacao' => $pontuacao_maxima,
        'status' => $status,
        'tipo_exercicio' => 'completar',
        'feedback_detalhado' => gerarFeedbackCompletar($resposta_usuario, $palavra_faltante_limpa, $alternativas_aceitas, $pontuacao_maxima, $status, $frase_completa)
    ];
}

function analisarExercicioTraducao($resposta_usuario, $resposta_esperada, $alternativas_aceitas) {
    // Para traduções, ser mais flexível com sinônimos e estruturas diferentes
    $correto = false;
    $pontuacao_maxima = 0;
    $melhor_match = '';
    
    // Verificar alternativas aceitas
    foreach ($alternativas_aceitas as $alternativa) {
        $similaridade = calcularSimilaridadeTextoEscrita($resposta_usuario, $alternativa);
        if ($similaridade > $pontuacao_maxima) {
            $pontuacao_maxima = $similaridade;
            $melhor_match = $alternativa;
        }
        
        if ($similaridade >= 0.8) {
            $correto = true;
            break;
        }
    }
    
    // Análise de palavras-chave para traduções
    $palavras_chave_encontradas = analisarPalavrasChave($resposta_usuario, $resposta_esperada);
    
    // Ajustar pontuação baseada em palavras-chave
    if ($palavras_chave_encontradas['percentual'] >= 0.7) {
        $pontuacao_maxima = max($pontuacao_maxima, $palavras_chave_encontradas['percentual']);
        if ($palavras_chave_encontradas['percentual'] >= 0.8) {
            $correto = true;
        }
    }
    
    $status = determinarStatusEscrita($pontuacao_maxima, $correto);
    
    return [
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'correto' => $correto,
        'pontuacao' => $pontuacao_maxima,
        'status' => $status,
        'tipo_exercicio' => 'traducao',
        'feedback_detalhado' => gerarFeedbackTraducao($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao_maxima, $status, $palavras_chave_encontradas)
    ];
}

function analisarExercicioGramatica($resposta_usuario, $resposta_esperada, $alternativas_aceitas) {
    // Para gramática, ser mais rigoroso com a estrutura
    $correto = false;
    $pontuacao_maxima = 0;
    
    foreach ($alternativas_aceitas as $alternativa) {
        $similaridade = calcularSimilaridadeTextoEscrita($resposta_usuario, $alternativa);
        if ($similaridade > $pontuacao_maxima) {
            $pontuacao_maxima = $similaridade;
        }
        
        if ($resposta_usuario === $alternativa || $similaridade >= 0.95) {
            $correto = true;
            break;
        }
    }
    
    // Análise gramatical específica
    $analise_gramatical = analisarEstruturasGramaticais($resposta_usuario, $resposta_esperada);
    
    $status = determinarStatusEscrita($pontuacao_maxima, $correto);
    
    return [
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'correto' => $correto,
        'pontuacao' => $pontuacao_maxima,
        'status' => $status,
        'tipo_exercicio' => 'gramatica',
        'feedback_detalhado' => gerarFeedbackGramatica($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao_maxima, $status, $analise_gramatical)
    ];
}

function analisarExercicioTextoLivre($resposta_usuario, $resposta_esperada, $alternativas_aceitas) {
    $correto = false;
    $pontuacao_maxima = 0;
    
    foreach ($alternativas_aceitas as $alternativa) {
        $similaridade = calcularSimilaridadeTextoEscrita($resposta_usuario, $alternativa);
        if ($similaridade > $pontuacao_maxima) {
            $pontuacao_maxima = $similaridade;
        }
        
        if ($similaridade >= 0.8) {
            $correto = true;
            break;
        }
    }
    
    $status = determinarStatusEscrita($pontuacao_maxima, $correto);
    
    return [
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'correto' => $correto,
        'pontuacao' => $pontuacao_maxima,
        'status' => $status,
        'tipo_exercicio' => 'texto_livre',
        'feedback_detalhado' => gerarFeedbackTextoLivre($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao_maxima, $status)
    ];
}

function calcularSimilaridadeTextoEscrita($texto1, $texto2) {
    if (empty($texto1) && empty($texto2)) {
        return 1.0;
    }
    
    if (empty($texto1) || empty($texto2)) {
        return 0.0;
    }
    
    // Usar múltiplos algoritmos para melhor precisão
    $levenshtein = calcularLevenshteinNormalizado($texto1, $texto2);
    $palavras_comuns = calcularSimilaridadePalavras($texto1, $texto2);
    
    // Média ponderada
    return ($levenshtein * 0.6) + ($palavras_comuns * 0.4);
}

function calcularLevenshteinNormalizado($texto1, $texto2) {
    $distancia = levenshtein($texto1, $texto2);
    $max_length = max(strlen($texto1), strlen($texto2));
    
    if ($max_length == 0) {
        return 1.0;
    }
    
    return 1 - ($distancia / $max_length);
}

function calcularSimilaridadePalavras($texto1, $texto2) {
    $palavras1 = explode(' ', $texto1);
    $palavras2 = explode(' ', $texto2);
    
    $palavras_comuns = array_intersect($palavras1, $palavras2);
    $total_palavras = max(count($palavras1), count($palavras2));
    
    if ($total_palavras == 0) {
        return 1.0;
    }
    
    return count($palavras_comuns) / $total_palavras;
}

function analisarPalavrasChave($resposta_usuario, $resposta_esperada) {
    $palavras_esperadas = explode(' ', $resposta_esperada);
    $palavras_usuario = explode(' ', $resposta_usuario);
    
    // Filtrar palavras importantes (não artigos, preposições, etc.)
    $palavras_irrelevantes = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for', 'of', 'with'];
    
    $palavras_importantes = array_filter($palavras_esperadas, function($palavra) use ($palavras_irrelevantes) {
        return !in_array($palavra, $palavras_irrelevantes) && strlen($palavra) > 2;
    });
    
    $palavras_encontradas = array_intersect($palavras_importantes, $palavras_usuario);
    
    return [
        'palavras_importantes' => $palavras_importantes,
        'palavras_encontradas' => $palavras_encontradas,
        'total_importantes' => count($palavras_importantes),
        'total_encontradas' => count($palavras_encontradas),
        'percentual' => count($palavras_importantes) > 0 ? count($palavras_encontradas) / count($palavras_importantes) : 0
    ];
}

function analisarEstruturasGramaticais($resposta_usuario, $resposta_esperada) {
    // Análise básica de estruturas gramaticais
    $analise = [
        'ordem_palavras' => analisarOrdemPalavras($resposta_usuario, $resposta_esperada),
        'tempos_verbais' => analisarTemposVerbais($resposta_usuario, $resposta_esperada),
        'concordancia' => analisarConcordancia($resposta_usuario)
    ];
    
    return $analise;
}

function analisarOrdemPalavras($resposta_usuario, $resposta_esperada) {
    $palavras_usuario = explode(' ', $resposta_usuario);
    $palavras_esperadas = explode(' ', $resposta_esperada);
    
    // Verificar se as palavras principais estão na ordem correta
    $ordem_correta = true;
    $posicoes_usuario = [];
    $posicoes_esperadas = [];
    
    foreach ($palavras_esperadas as $index => $palavra) {
        $pos_usuario = array_search($palavra, $palavras_usuario);
        if ($pos_usuario !== false) {
            $posicoes_usuario[] = $pos_usuario;
            $posicoes_esperadas[] = $index;
        }
    }
    
    // Verificar se as posições relativas são mantidas
    for ($i = 1; $i < count($posicoes_usuario); $i++) {
        if ($posicoes_usuario[$i] < $posicoes_usuario[$i-1]) {
            $ordem_correta = false;
            break;
        }
    }
    
    return $ordem_correta;
}

function analisarTemposVerbais($resposta_usuario, $resposta_esperada) {
    // Lista básica de verbos e suas formas
    $verbos_regulares = ['work', 'play', 'study', 'live', 'like', 'want', 'need', 'help'];
    $verbos_irregulares = ['go' => 'went', 'come' => 'came', 'see' => 'saw', 'do' => 'did', 'have' => 'had'];
    
    // Verificar se os tempos verbais coincidem
    $palavras_usuario = explode(' ', $resposta_usuario);
    $palavras_esperadas = explode(' ', $resposta_esperada);
    
    $tempos_corretos = true;
    
    // Análise simplificada - verificar se verbos principais coincidem
    foreach ($palavras_esperadas as $palavra) {
        if (in_array($palavra, $verbos_regulares) || array_key_exists($palavra, $verbos_irregulares) || array_search($palavra, $verbos_irregulares)) {
            if (!in_array($palavra, $palavras_usuario)) {
                $tempos_corretos = false;
                break;
            }
        }
    }
    
    return $tempos_corretos;
}

function analisarConcordancia($resposta_usuario) {
    // Análise básica de concordância
    $palavras = explode(' ', $resposta_usuario);
    $concordancia_correta = true;
    
    // Verificar algumas regras básicas
    for ($i = 0; $i < count($palavras) - 1; $i++) {
        $palavra_atual = $palavras[$i];
        $proxima_palavra = $palavras[$i + 1];
        
        // Verificar concordância básica (he/she/it + verbo)
        if (in_array($palavra_atual, ['he', 'she', 'it'])) {
            if (in_array($proxima_palavra, ['work', 'play', 'study', 'live', 'like', 'want', 'need', 'help'])) {
                $concordancia_correta = false; // Deveria ter 's' no final
                break;
            }
        }
    }
    
    return $concordancia_correta;
}

function determinarStatusEscrita($pontuacao, $correto) {
    if ($correto && $pontuacao >= 0.9) {
        return 'correto';
    } elseif ($pontuacao >= 0.7) {
        return 'quase_correto';
    } elseif ($pontuacao >= 0.4) {
        return 'meio_correto';
    } else {
        return 'errado';
    }
}

function gerarFeedbackCompletar($resposta_usuario, $palavra_esperada, $alternativas_aceitas, $pontuacao, $status, $frase_completa) {
    $feedback = [
        'status' => $status,
        'pontuacao' => $pontuacao,
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $palavra_esperada,
        'pontos_positivos' => [],
        'erros_encontrados' => [],
        'sugestoes_melhoria' => []
    ];
    
    switch ($status) {
        case 'correto':
            $feedback['pontos_positivos'][] = 'Excelente! Você completou a frase corretamente.';
            $feedback['pontos_positivos'][] = 'Sua compreensão do contexto está muito boa.';
            break;
            
        case 'quase_correto':
            $feedback['pontos_positivos'][] = 'Muito bem! Sua resposta está quase correta.';
            $feedback['erros_encontrados'][] = [
                'descricao' => 'Pequenos ajustes na palavra escolhida',
                'sugestao' => "A palavra mais adequada seria '{$palavra_esperada}'"
            ];
            break;
            
        case 'meio_correto':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'A palavra escolhida não se encaixa perfeitamente no contexto',
                'sugestao' => "Tente '{$palavra_esperada}' - observe o contexto da frase"
            ];
            $feedback['sugestoes_melhoria'][] = 'Leia a frase completa para entender melhor o contexto';
            break;
            
        case 'errado':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'A palavra não se encaixa no contexto da frase',
                'sugestao' => "A resposta correta é '{$palavra_esperada}'"
            ];
            $feedback['sugestoes_melhoria'][] = 'Analise o tipo de palavra que falta (verbo, substantivo, adjetivo)';
            $feedback['sugestoes_melhoria'][] = 'Considere o tempo verbal e o contexto da frase';
            break;
    }
    
    if (count($alternativas_aceitas) > 1) {
        $feedback['sugestoes_melhoria'][] = 'Outras respostas aceitas: ' . implode(', ', $alternativas_aceitas);
    }
    
    return $feedback;
}

function gerarFeedbackTraducao($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao, $status, $palavras_chave) {
    $feedback = [
        'status' => $status,
        'pontuacao' => $pontuacao,
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'pontos_positivos' => [],
        'erros_encontrados' => [],
        'sugestoes_melhoria' => []
    ];
    
    if ($palavras_chave['total_encontradas'] > 0) {
        $feedback['pontos_positivos'][] = "Você acertou {$palavras_chave['total_encontradas']} de {$palavras_chave['total_importantes']} palavras-chave importantes";
    }
    
    switch ($status) {
        case 'correto':
            $feedback['pontos_positivos'][] = 'Tradução excelente! Muito precisa.';
            $feedback['pontos_positivos'][] = 'Você captou bem o sentido da frase.';
            break;
            
        case 'quase_correto':
            $feedback['pontos_positivos'][] = 'Boa tradução! O sentido geral está correto.';
            $feedback['sugestoes_melhoria'][] = 'Pequenos ajustes podem tornar a tradução mais natural';
            break;
            
        case 'meio_correto':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'Algumas palavras ou estruturas podem ser melhoradas',
                'sugestao' => 'Compare sua resposta com a tradução esperada'
            ];
            $feedback['sugestoes_melhoria'][] = 'Foque no significado geral, não traduza palavra por palavra';
            break;
            
        case 'errado':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'A tradução precisa ser revista',
                'sugestao' => 'Tente entender o contexto antes de traduzir'
            ];
            $feedback['sugestoes_melhoria'][] = 'Identifique as palavras-chave principais primeiro';
            $feedback['sugestoes_melhoria'][] = 'Considere expressões idiomáticas que podem ter traduções específicas';
            break;
    }
    
    return $feedback;
}

function gerarFeedbackGramatica($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao, $status, $analise_gramatical) {
    $feedback = [
        'status' => $status,
        'pontuacao' => $pontuacao,
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'pontos_positivos' => [],
        'erros_encontrados' => [],
        'sugestoes_melhoria' => []
    ];
    
    if ($analise_gramatical['ordem_palavras']) {
        $feedback['pontos_positivos'][] = 'A ordem das palavras está correta';
    } else {
        $feedback['erros_encontrados'][] = [
            'descricao' => 'Ordem das palavras incorreta',
            'sugestao' => 'Revise a estrutura: Sujeito + Verbo + Complemento'
        ];
    }
    
    if ($analise_gramatical['tempos_verbais']) {
        $feedback['pontos_positivos'][] = 'Tempo verbal correto';
    } else {
        $feedback['erros_encontrados'][] = [
            'descricao' => 'Tempo verbal incorreto',
            'sugestao' => 'Verifique se o tempo verbal está adequado ao contexto'
        ];
    }
    
    if ($analise_gramatical['concordancia']) {
        $feedback['pontos_positivos'][] = 'Concordância verbal correta';
    } else {
        $feedback['erros_encontrados'][] = [
            'descricao' => 'Erro de concordância verbal',
            'sugestao' => 'Lembre-se: he/she/it + verbo com "s"'
        ];
    }
    
    switch ($status) {
        case 'correto':
            $feedback['pontos_positivos'][] = 'Gramática perfeita! Excelente domínio das regras.';
            break;
            
        case 'quase_correto':
            $feedback['sugestoes_melhoria'][] = 'Revise pequenos detalhes gramaticais';
            break;
            
        case 'meio_correto':
            $feedback['sugestoes_melhoria'][] = 'Pratique mais as estruturas gramaticais básicas';
            break;
            
        case 'errado':
            $feedback['sugestoes_melhoria'][] = 'Revise as regras gramaticais fundamentais';
            $feedback['sugestoes_melhoria'][] = 'Pratique com exercícios mais simples primeiro';
            break;
    }
    
    return $feedback;
}

function gerarFeedbackTextoLivre($resposta_usuario, $resposta_esperada, $alternativas_aceitas, $pontuacao, $status) {
    $feedback = [
        'status' => $status,
        'pontuacao' => $pontuacao,
        'resposta_usuario' => $resposta_usuario,
        'resposta_esperada' => $resposta_esperada,
        'pontos_positivos' => [],
        'erros_encontrados' => [],
        'sugestoes_melhoria' => []
    ];
    
    switch ($status) {
        case 'correto':
            $feedback['pontos_positivos'][] = 'Resposta excelente! Muito bem escrita.';
            $feedback['pontos_positivos'][] = 'Sua expressão em inglês está muito boa.';
            break;
            
        case 'quase_correto':
            $feedback['pontos_positivos'][] = 'Boa resposta! O sentido está correto.';
            $feedback['sugestoes_melhoria'][] = 'Pequenos ajustes podem melhorar ainda mais sua resposta';
            break;
            
        case 'meio_correto':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'A resposta está parcialmente correta',
                'sugestao' => 'Revise a estrutura e o vocabulário usado'
            ];
            $feedback['sugestoes_melhoria'][] = 'Tente usar frases mais simples e diretas';
            break;
            
        case 'errado':
            $feedback['erros_encontrados'][] = [
                'descricao' => 'A resposta precisa ser reformulada',
                'sugestao' => 'Compare com a resposta esperada e identifique as diferenças'
            ];
            $feedback['sugestoes_melhoria'][] = 'Foque em usar vocabulário que você domina bem';
            $feedback['sugestoes_melhoria'][] = 'Pratique estruturas de frases mais básicas';
            break;
    }
    
    if (count($alternativas_aceitas) > 1) {
        $feedback['sugestoes_melhoria'][] = 'Outras respostas possíveis: ' . implode(', ', array_slice($alternativas_aceitas, 0, 3));
    }
    
    return $feedback;
}

function salvarFeedbackEscrita($conn, $exercicio_id, $id_usuario, $resultado) {
    $sql = "
        INSERT INTO feedback_escrita 
        (exercicio_id, usuario_id, resposta_usuario, resposta_esperada, pontuacao_escrita, feedback_detalhado, data_resposta) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $resposta_usuario = $resultado['resposta_usuario'];
    $resposta_esperada = $resultado['resposta_esperada'];
    $pontuacao = $resultado['pontuacao'];
    $feedback_json = json_encode($resultado['feedback_detalhado']);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssds", $exercicio_id, $id_usuario, $resposta_usuario, $resposta_esperada, $pontuacao, $feedback_json);
    $stmt->execute();
    $stmt->close();
}

function atualizarProgressoEscrita($conn, $exercicio_id, $id_usuario, $resultado_analise) {
    $pontuacao = $resultado_analise['pontuacao'];
    $correto = $resultado_analise['correto'];
    
    // Verificar se já existe progresso para este exercício
    $sql_check = "SELECT id, tentativas, acertos FROM progresso_detalhado WHERE exercicio_id = ? AND id_usuario = ?";
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
            UPDATE progresso_detalhado 
            SET tentativas = ?, acertos = ?, concluido = ?, pontuacao = ?, data_conclusao = ?
            WHERE id = ?
        ";
        
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iiidsi", $novas_tentativas, $novos_acertos, $concluido, $pontuacao, $data_conclusao, $progresso_existente['id']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Criar novo progresso
        $tentativas = 1;
        $acertos = $correto ? 1 : 0;
        $concluido = $correto ? 1 : 0;
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        
        $sql_insert = "
            INSERT INTO progresso_detalhado 
            (id_usuario, exercicio_id, concluido, tentativas, acertos, pontuacao, data_conclusao) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiiidss", $id_usuario, $exercicio_id, $concluido, $tentativas, $acertos, $pontuacao, $data_conclusao);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>
