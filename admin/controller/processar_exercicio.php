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
// Assumindo que a classe Database está em um caminho acessível
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

    // Buscar dados do exercício - INCLUINDO CATEGORIA
    $sql = "SELECT conteudo, tipo, categoria, bloco_id FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exercicio = $result->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    // Processar resposta baseado no tipo - USAR CATEGORIA SE DISPONÍVEL
    $conteudo = json_decode($exercicio['conteudo'], true);
    
    // Determinar o tipo real do exercício
    $tipo_real = $tipo_exercicio;
    if (!empty($exercicio['categoria'])) {
        $tipo_real = normalizarTipoExercicio($exercicio['categoria']);
    } elseif ($exercicio['tipo'] !== 'normal') {
        $tipo_real = normalizarTipoExercicio($exercicio['tipo']);
    }
    
    // Log para debug
    error_log("Processando exercício ID: $exercicio_id, Tipo: $tipo_real, Categoria: " . ($exercicio['categoria'] ?? 'N/A'));
    error_log("Conteúdo do exercício: " . json_encode($conteudo));
    
    // Adicionar exercicio_id aos dados POST para acesso nas funções
    $_POST['exercicio_id'] = $exercicio_id;
    
    $resultado = processarResposta($resposta_usuario, $conteudo, $tipo_real);
    
    // Garantir que sempre retorne success
    if (!isset($resultado['success'])) {
        $resultado['success'] = true;
    }

    // Registrar resposta do usuário
    registrarRespostaUsuario($conn, $id_usuario, $exercicio_id, $resultado['correto'], $resposta_usuario);

    // Registrar progresso do usuário
    registrarProgresso($conn, $id_usuario, $exercicio_id, $resultado['correto'], $resultado['pontuacao'] ?? 0);

    $database->closeConnection();

    echo json_encode($resultado);

} catch (Exception $e) {
    // Fechar conexão em caso de erro
    if (isset($database) && isset($database->conn)) {
        $database->closeConnection();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// =================================================================================
// Funções de Processamento
// =================================================================================

function processarResposta($resposta_usuario, $conteudo, $tipo_exercicio) {
    global $conn;
    
    // Normalizar tipo para garantir compatibilidade
    $tipo_normalizado = normalizarTipoExercicio($tipo_exercicio);
    
    switch ($tipo_normalizado) {
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
        case 'audicao':
            return processarListening($resposta_usuario, $conteudo);
        default:
            // Fallback para texto_livre para tipos não reconhecidos
            return processarTextoLivre($resposta_usuario, $conteudo);
    }
}

function normalizarTipoExercicio($tipo) {
    $tipo = strtolower(trim($tipo));
    
    $mapeamento = [
        'normal' => 'multipla_escolha', // padrão para exercícios normais
        'texto' => 'texto_livre',
        'text_input' => 'texto_livre',
        'input' => 'texto_livre',
        'multiple_choice' => 'multipla_escolha',
        'multipla escolha' => 'multipla_escolha',
        'escolha_multipla' => 'multipla_escolha',
        'gramatica' => 'multipla_escolha',
        'drag_drop' => 'arrastar_soltar',
        'drag' => 'arrastar_soltar',
        'arrastar' => 'arrastar_soltar',
        'speech' => 'fala',
        'pronuncia' => 'fala',
        'fala' => 'fala',
        'complete' => 'completar',
        'completion' => 'completar',
        'completar' => 'completar',
        'audio' => 'listening',
        'audicao' => 'listening',
        'listening' => 'listening',
        'escuta' => 'listening',
        'escrita' => 'texto_livre',
        'leitura' => 'texto_livre'
    ];
    
    return $mapeamento[$tipo] ?? $tipo;
}

function processarListening($resposta_usuario, $conteudo) {
    error_log("Processando listening - Conteúdo: " . json_encode($conteudo));
    
    // Verificar se é um exercício de listening com estrutura de opções
    if (isset($conteudo['opcoes']) && is_array($conteudo['opcoes']) && isset($conteudo['resposta_correta'])) {
        error_log("Usando processarListeningOpcoes");
        return processarListeningOpcoes($resposta_usuario, $conteudo);
    }
    
    // Se não tem opções, verificar se tem alternativas (Multipla Escolha padrão)
    if (isset($conteudo['alternativas']) && is_array($conteudo['alternativas'])) {
        error_log("Usando processarMultiplaEscolha para listening");
        return processarMultiplaEscolha($resposta_usuario, $conteudo);
    }
    
    // Se não tem nenhuma estrutura válida, retornar erro
    error_log("Listening mal configurado - sem opções ou alternativas");
    return [
        'success' => false,
        'correto' => false,
        'explicacao' => '❌ Incorreto! Exercício mal configurado. Verifique as opções ou alternativas.',
        'pontuacao' => 0,
        'alternativa_correta_id' => null // Adicionado para evitar erro de variável indefinida no frontend
    ];
}

// FUNÇÃO CORRIGIDA para processar listening com opções
function processarListeningOpcoes($resposta_usuario, $conteudo) {
    $opcoes = $conteudo['opcoes'] ?? [];
    $resposta_correta_index = $conteudo['resposta_correta'] ?? null; // O índice da resposta correta (0-based)
    
    if (empty($opcoes) || $resposta_correta_index === null) {
        return [
            'success' => false,
            'correto' => false,
            'explicacao' => '❌ Incorreto! Exercício mal configurado.',
            'pontuacao' => 0,
            'alternativa_correta_id' => null
        ];
    }
    
    // O frontend envia o índice da opção selecionada (0, 1, 2, 3...)
    $resposta_usuario_index = intval($resposta_usuario);
    
    // Garante que o índice da resposta correta é um inteiro
    $resposta_correta_index = intval($resposta_correta_index);
    
    // Verifica se a resposta do usuário está dentro do array de opções
    if (!isset($opcoes[$resposta_usuario_index])) {
        return [
            'success' => false,
            'correto' => false,
            'explicacao' => 'Resposta inválida.',
            'pontuacao' => 0,
            'alternativa_correta_id' => $resposta_correta_index
        ];
    }
    
    $correto = ($resposta_usuario_index === $resposta_correta_index);
    
    $resposta_correta_texto = $opcoes[$resposta_correta_index] ?? 'N/A';
    $resposta_selecionada_texto = $opcoes[$resposta_usuario_index] ?? 'N/A';
    
    // Gerar explicação mais detalhada
    $explicacao_base = $conteudo['explicacao'] ?? '';
    $frase_original = $conteudo['frase_original'] ?? $conteudo['frase'] ?? '';
    
    if ($correto) {
        $explicacao = '✅ Correto! ';
        if (!empty($explicacao_base)) {
            $explicacao .= $explicacao_base;
        } else {
            $explicacao .= 'Você compreendeu o áudio perfeitamente!';
        }
    } else {
        $explicacao = '❌ Incorreto. ';
        $explicacao .= 'A resposta correta é: "' . $resposta_correta_texto . '". ';
        
        if (!empty($frase_original)) {
            $explicacao .= 'Frase original: "' . $frase_original . '". ';
        }
        
        if (!empty($explicacao_base)) {
            $explicacao .= 'Explicação: ' . $explicacao_base;
        } else {
            $explicacao .= 'Ouça o áudio novamente com atenção.';
        }
    }
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $explicacao,
        'resposta_correta' => $resposta_correta_texto,
        'resposta_selecionada' => $resposta_selecionada_texto,
        'pontuacao' => $correto ? 100 : 0,
        'alternativa_correta_id' => $resposta_correta_index, // Adiciona o índice correto para o frontend
        'frase_original' => $frase_original,
        'audio_url' => $conteudo['audio_url'] ?? ''
    ];
}

function processarMultiplaEscolha($resposta_usuario, $conteudo) {
    global $conn;
    
    if (!isset($conteudo['alternativas']) || empty($conteudo['alternativas'])) {
        $exercicio_id = $_POST['exercicio_id'] ?? null;
        if ($exercicio_id) {
            $sql = "SELECT conteudo FROM exercicios WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $exercicio_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                $conteudo_db = json_decode($row['conteudo'], true);
                if ($conteudo_db && isset($conteudo_db['alternativas'])) {
                    $conteudo['alternativas'] = $conteudo_db['alternativas'];
                }
            }
        }
    }
    
    if (!isset($conteudo['alternativas']) || empty($conteudo['alternativas'])) {
        return [
            'success' => true,
            'correto' => false,
            'explicacao' => '❌ Incorreto! Exercício mal configurado.',
            'pontuacao' => 0,
            'alternativa_correta_id' => null
        ];
    }

    $alternativa_correta_index = null;
    $resposta_correta_texto = '';
    
    foreach ($conteudo['alternativas'] as $index => $alt) {
        if (isset($alt['correta']) && $alt['correta'] == true) {
            $alternativa_correta_index = $index;
            $resposta_correta_texto = $alt['texto'];
            break;
        }
    }
    
    if ($alternativa_correta_index === null) {
        // Se não houver 'correta: true', assume-se que o primeiro é o correto (pode ser um erro de configuração)
        $alternativa_correta_index = 0;
        $resposta_correta_texto = $conteudo['alternativas'][0]['texto'];
    }

    // A resposta do usuário pode ser o índice (0, 1, 2...) ou o ID ('a', 'b', 'c'...)
    $resposta_usuario_index = null;
    
    // Tenta encontrar o índice pelo ID (se for 'a', 'b', 'c'...)
    if (is_string($resposta_usuario)) {
        $resposta_usuario_id = strtolower($resposta_usuario);
        foreach ($conteudo['alternativas'] as $index => $alt) {
            if (isset($alt['id']) && strtolower($alt['id']) === $resposta_usuario_id) {
                $resposta_usuario_index = $index;
                break;
            }
        }
    }
    
    // Se não encontrou pelo ID, assume que é o índice numérico
    if ($resposta_usuario_index === null) {
        $resposta_usuario_index = intval($resposta_usuario);
    }

    $correto = ($resposta_usuario_index === $alternativa_correta_index);
    $resposta_selecionada_texto = $conteudo['alternativas'][$resposta_usuario_index]['texto'] ?? 'N/A';

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto! ' . ($conteudo['explicacao'] ?? 'Excelente!') : '❌ Incorreto. A resposta correta é: ' . $resposta_correta_texto . '. Explicação: ' . ($conteudo['explicacao'] ?? 'Sem explicação disponível.'),
        'resposta_correta' => $resposta_correta_texto,
        'resposta_selecionada' => $resposta_selecionada_texto,
        'pontuacao' => $correto ? 100 : 0,
        'alternativa_correta_id' => $alternativa_correta_index // Adiciona o índice correto para o frontend
    ];
}

// Funções de processamento de outros tipos (manter como estavam, mas com o prefixo '❌ Incorreto!')
function processarTextoLivre($resposta_usuario, $conteudo) {
    // Lógica de correção de texto livre (ex: comparação de strings, NLP, etc.)
    // Exemplo simplificado:
    $resposta_esperada = $conteudo['resposta_esperada'] ?? '';
    $correto = (strtolower(trim($resposta_usuario)) === strtolower(trim($resposta_esperada)));
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto!' : '❌ Incorreto. Resposta esperada: ' . $resposta_esperada,
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarFala($resposta_usuario, $conteudo) {
    // Esta função é um placeholder. A correção de fala real deve ocorrer em um endpoint separado
    // que recebe a transcrição e a frase esperada (como em correcao_audio.php)
    
    // Aqui, apenas simula a validação para o processar_exercicio.php
    $frase_esperada = $conteudo['frase_esperada'] ?? $conteudo['texto_para_falar'] ?? '';
    
    // Simulação de correção de fala (deve ser mais complexa na prática)
    $correto = (strtolower(trim($resposta_usuario)) === strtolower(trim($frase_esperada)));
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto! Sua pronúncia foi excelente.' : '❌ Incorreto. Tente novamente. Frase esperada: ' . $frase_esperada,
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarArrastarSoltar($resposta_usuario, $conteudo) {
    // Lógica de correção de arrastar e soltar
    $ordem_correta = $conteudo['ordem_correta'] ?? [];
    $correto = ($resposta_usuario === $ordem_correta); // Comparação de arrays
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto!' : '❌ Incorreto. A ordem correta é: ' . implode(', ', $ordem_correta),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarCompletar($resposta_usuario, $conteudo) {
    // Lógica de correção de completar lacunas
    $respostas_corretas = $conteudo['respostas_corretas'] ?? [];
    
    // Assumindo que $resposta_usuario é um array de respostas
    $correto = (count(array_diff($respostas_corretas, $resposta_usuario)) === 0 && count(array_diff($resposta_usuario, $respostas_corretas)) === 0);
    
    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto!' : '❌ Incorreto. As respostas corretas são: ' . implode(', ', $respostas_corretas),
        'pontuacao' => $correto ? 100 : 0
    ];
}

// =================================================================================
// Funções de Registro (Manter como estavam)
// =================================================================================

function registrarRespostaUsuario($conn, $id_usuario, $exercicio_id, $correto, $resposta_usuario) {
    // Lógica para registrar a resposta do usuário no banco de dados
    // Exemplo:
    $sql = "INSERT INTO respostas_usuario (id_usuario, exercicio_id, resposta, correto, data_resposta) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $correto_db = $correto ? 1 : 0;
    $resposta_json = is_array($resposta_usuario) ? json_encode($resposta_usuario) : $resposta_usuario;
    $stmt->bind_param("iisi", $id_usuario, $exercicio_id, $resposta_json, $correto_db);
    $stmt->execute();
    $stmt->close();
}

function registrarProgresso($conn, $id_usuario, $exercicio_id, $correto, $pontuacao) {
    // Lógica para atualizar o progresso do usuário
    // Exemplo:
    $sql = "
        INSERT INTO progresso_usuario (id_usuario, exercicio_id, concluido, pontuacao, data_conclusao)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            concluido = VALUES(concluido), 
            pontuacao = VALUES(pontuacao), 
            data_conclusao = VALUES(data_conclusao)
    ";
    $stmt = $conn->prepare($sql);
    $concluido = $correto ? 1 : 0;
    $stmt->bind_param("iiii", $id_usuario, $exercicio_id, $concluido, $pontuacao);
    $stmt->execute();
    $stmt->close();
}

?>
