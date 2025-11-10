<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../conexao.php';

try {
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Usuário não autenticado');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados inválidos');
    }
    
    $exercicio_id = $data['exercicio_id'] ?? null;
    $resposta = $data['resposta'] ?? null;
    $tipo = $data['tipo_exercicio'] ?? 'multipla_escolha';
    
    if (!$exercicio_id || $resposta === null) {
        throw new Exception('Dados obrigatórios ausentes');
    }
    
    $database = new Database();
    $conn = $database->conn;
    
    // Buscar exercício
    $sql = "SELECT conteudo, categoria FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exercicio = $result->fetch_assoc();
    $stmt->close();
    
    if (!$exercicio) {
        throw new Exception('Exercício não encontrado');
    }
    
    $conteudo = json_decode($exercicio['conteudo'], true) ?: [];
    $categoria = $exercicio['categoria'] ?? 'gramatica';
    
    // Log para debug
    error_log('Processando exercício ID: ' . $exercicio_id . ', Categoria: ' . $categoria . ', Tipo: ' . $tipo);
    
    // Processar resposta baseado no tipo
    if ($categoria === 'audicao' || $tipo === 'listening' || (isset($conteudo['tipo_exercicio']) && $conteudo['tipo_exercicio'] === 'listening') || isset($conteudo['opcoes'])) {
        $resultado = processarListening($resposta, $conteudo);
    } else if ($categoria === 'fala') {
        $resultado = processarFala($resposta, $conteudo);
    } else if (isset($conteudo['resposta_correta']) && !isset($conteudo['alternativas'])) {
        $resultado = processarTextoLivre($resposta, $conteudo);
    } else if (isset($conteudo['frase_completar'])) {
        $resultado = processarCompletar($resposta, $conteudo);
    } else {
        $resultado = processarMultiplaEscolha($resposta, $conteudo);
    }
    
    // Log do resultado
    error_log('Resultado: ' . json_encode($resultado));
    
    $database->closeConnection();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro na API processar_exercicio: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    if (isset($database)) {
        $database->closeConnection();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function processarListening($resposta, $conteudo) {
    error_log('Processando listening - Resposta: ' . $resposta);
    error_log('Conteúdo: ' . json_encode($conteudo));
    
    // Verificar estrutura de opções (formato listening)
    if (isset($conteudo['opcoes']) && is_array($conteudo['opcoes'])) {
        $resposta_index = intval($resposta);
        $opcoes = array_values(array_filter($conteudo['opcoes'])); // Remove opções vazias
        
        // Determinar resposta correta
        $correto_index = isset($conteudo['resposta_correta']) ? intval($conteudo['resposta_correta']) : 0;
        
        // Validar se o índice da resposta é válido
        if ($resposta_index < 0 || $resposta_index >= count($opcoes)) {
            return [
                'success' => true,
                'correto' => false,
                'explicacao' => '❌ Resposta inválida selecionada.',
                'alternativa_correta_id' => $correto_index
            ];
        }
        
        $correto = ($resposta_index === $correto_index);
        $resposta_texto = $opcoes[$resposta_index];
        $correta_texto = isset($opcoes[$correto_index]) ? $opcoes[$correto_index] : 'Opção não encontrada';
        
        // Obter texto original do áudio
        $texto_audio = $conteudo['frase_original'] ?? $conteudo['transcricao'] ?? '';
        
        return [
            'success' => true,
            'correto' => $correto,
            'explicacao' => $correto ? 
                '✅ Correto! Você entendeu perfeitamente o áudio.' : 
                '❌ Incorreto. A resposta correta é: "' . $correta_texto . '"',
            'transcricao' => $texto_audio,
            'dicas_compreensao' => $conteudo['dicas_compreensao'] ?? 'Ouça novamente com atenção.',
            'alternativa_correta_id' => $correto_index,
            'resposta_selecionada' => $resposta_texto,
            'resposta_correta' => $correta_texto,
            'audio_texto' => $texto_audio
        ];
    }
    
    // Se não tem estrutura de opções, tentar alternativas
    if (isset($conteudo['alternativas']) && is_array($conteudo['alternativas'])) {
        return processarMultiplaEscolha($resposta, $conteudo);
    }
    
    // Fallback - exercício mal configurado
    return [
        'success' => true,
        'correto' => false,
        'explicacao' => '❌ Exercício de listening mal configurado - estrutura de opções não encontrada.',
        'alternativa_correta_id' => 0
    ];
}

function processarFala($resposta, $conteudo) {
    $frase_esperada = $conteudo['frase_esperada'] ?? $conteudo['texto_para_falar'] ?? '';
    $similaridade = calcularSimilaridade($resposta, $frase_esperada);
    $correto = $similaridade >= 0.6;
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Boa pronúncia!' : '❌ Tente novamente.',
        'similaridade' => round($similaridade * 100)
    ];
}

function processarMultiplaEscolha($resposta, $conteudo) {
    // Log para debug
    error_log('Processando múltipla escolha - Resposta: ' . $resposta);
    error_log('Alternativas: ' . json_encode($conteudo['alternativas'] ?? 'Não encontradas'));
    
    if (!isset($conteudo['alternativas']) || !is_array($conteudo['alternativas'])) {
        return [
            'success' => true,
            'correto' => false,
            'explicacao' => '❌ Exercício mal configurado - alternativas não encontradas.',
            'alternativa_correta_id' => 0
        ];
    }
    
    $correto_index = null;
    $alternativas = $conteudo['alternativas'];
    
    // Procurar alternativa correta
    foreach ($alternativas as $index => $alt) {
        if (isset($alt['correta']) && $alt['correta']) {
            $correto_index = $index;
            break;
        }
    }
    
    // Se não encontrou, assumir primeira como correta
    if ($correto_index === null) {
        $correto_index = 0;
    }
    
    $resposta_index = intval($resposta);
    $correto = ($resposta_index === $correto_index);
    
    $resposta_texto = isset($alternativas[$resposta_index]['texto']) ? 
        $alternativas[$resposta_index]['texto'] : 'Alternativa inválida';
    $correta_texto = isset($alternativas[$correto_index]['texto']) ? 
        $alternativas[$correto_index]['texto'] : 'Alternativa não encontrada';
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? 
            '✅ Correto! ' . ($conteudo['explicacao'] ?? 'Excelente!') : 
            '❌ Incorreto. A resposta correta é: "' . $correta_texto . '"',
        'alternativa_correta_id' => $correto_index,
        'resposta_selecionada' => $resposta_texto,
        'resposta_correta' => $correta_texto
    ];
}

function processarTextoLivre($resposta, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? '';
    $alternativas_aceitas = $conteudo['alternativas_aceitas'] ?? [$resposta_correta];
    
    $resposta_normalizada = strtolower(trim($resposta));
    $correto = false;
    
    foreach ($alternativas_aceitas as $alternativa) {
        if (strtolower(trim($alternativa)) === $resposta_normalizada) {
            $correto = true;
            break;
        }
    }
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? 
            '✅ Correto! Sua resposta está correta.' : 
            '❌ Incorreto. A resposta esperada é: "' . $resposta_correta . '"',
        'resposta_correta' => $resposta_correta,
        'resposta_usuario' => $resposta
    ];
}

function processarCompletar($resposta, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? '';
    $alternativas_aceitas = $conteudo['alternativas_aceitas'] ?? [$resposta_correta];
    
    $resposta_normalizada = strtolower(trim($resposta));
    $correto = false;
    
    foreach ($alternativas_aceitas as $alternativa) {
        if (strtolower(trim($alternativa)) === $resposta_normalizada) {
            $correto = true;
            break;
        }
    }
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? 
            '✅ Correto! Você completou a frase corretamente.' : 
            '❌ Incorreto. A resposta correta é: "' . $resposta_correta . '"',
        'resposta_correta' => $resposta_correta,
        'resposta_usuario' => $resposta
    ];
}

function calcularSimilaridade($texto1, $texto2) {
    $palavras1 = explode(' ', strtolower(trim($texto1)));
    $palavras2 = explode(' ', strtolower(trim($texto2)));
    
    $corretas = 0;
    foreach ($palavras2 as $palavra) {
        if (in_array($palavra, $palavras1)) {
            $corretas++;
        }
    }
    
    return count($palavras2) > 0 ? $corretas / count($palavras2) : 0;
}
?>