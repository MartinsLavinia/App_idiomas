<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['exercicio_id']) || !isset($data['resposta'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$exercicio_id = $data['exercicio_id'];
$resposta_usuario = $data['resposta'];
$tipo_exercicio = $data['tipo_exercicio'] ?? 'multipla_escolha';
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar exercício
    $sql = "SELECT * FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $exercicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    $conteudo = json_decode($exercicio['conteudo'], true);
    
    // Determinar tipo real baseado na categoria
    $tipo_real = $exercicio['categoria'] ?? 'gramatica';
    if ($tipo_real === 'audicao' || isset($conteudo['opcoes'])) {
        $tipo_real = 'listening';
    }

    $resultado = processarResposta($resposta_usuario, $conteudo, $tipo_real);

    // Registrar resposta
    $sql_resposta = "INSERT INTO respostas_exercicios (id_usuario, exercicio_id, tipo_exercicio, resposta_usuario, acertou, pontuacao, data_resposta) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt_resposta = $conn->prepare($sql_resposta);
    $acertou = $resultado['correto'] ? 1 : 0;
    $stmt_resposta->bind_param("iissii", $id_usuario, $exercicio_id, $tipo_real, $resposta_usuario, $acertou, $resultado['pontuacao']);
    $stmt_resposta->execute();
    $stmt_resposta->close();

    $database->closeConnection();
    echo json_encode($resultado);

} catch (Exception $e) {
    if (isset($database)) {
        $database->closeConnection();
    }
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

function processarResposta($resposta_usuario, $conteudo, $tipo_exercicio) {
    switch ($tipo_exercicio) {
        case 'listening':
        case 'audicao':
            return processarListening($resposta_usuario, $conteudo);
        case 'multipla_escolha':
        case 'gramatica':
            return processarMultiplaEscolha($resposta_usuario, $conteudo);
        default:
            return processarMultiplaEscolha($resposta_usuario, $conteudo);
    }
}

function processarListening($resposta_usuario, $conteudo) {
    // Verificar estrutura de listening corrigida
    if (isset($conteudo['opcoes']) && isset($conteudo['resposta_correta'])) {
        $opcoes = $conteudo['opcoes'];
        $resposta_correta_index = intval($conteudo['resposta_correta']);
        $resposta_usuario_index = intval($resposta_usuario);
        
        $correto = ($resposta_usuario_index === $resposta_correta_index);
        
        $resposta_correta_texto = $opcoes[$resposta_correta_index] ?? 'N/A';
        $resposta_selecionada_texto = $opcoes[$resposta_usuario_index] ?? 'N/A';
        
        // Gerar explicação detalhada
        if ($correto) {
            $explicacao = '✅ Correto! Você compreendeu o áudio perfeitamente!';
            if (!empty($conteudo['explicacao'])) {
                $explicacao .= ' ' . $conteudo['explicacao'];
            }
        } else {
            $explicacao = '❌ Incorreto. A resposta correta é: "' . $resposta_correta_texto . '".';
            if (!empty($conteudo['transcricao'])) {
                $explicacao .= ' Transcrição: "' . $conteudo['transcricao'] . '".';
            }
            if (!empty($conteudo['explicacao'])) {
                $explicacao .= ' ' . $conteudo['explicacao'];
            }
        }
        
        return [
            'success' => true,
            'correto' => $correto,
            'explicacao' => $explicacao,
            'resposta_correta' => $resposta_correta_texto,
            'resposta_selecionada' => $resposta_selecionada_texto,
            'alternativa_correta_id' => $resposta_correta_index,
            'pontuacao' => $correto ? 100 : 0,
            'frase_original' => $conteudo['frase_original'] ?? '',
            'transcricao' => $conteudo['transcricao'] ?? '',
            'dicas_compreensao' => $conteudo['dicas_compreensao'] ?? '',
            'audio_url' => $conteudo['audio_url'] ?? ''
        ];
    }
    
    return ['success' => false, 'message' => 'Exercício mal configurado'];
}

function processarMultiplaEscolha($resposta_usuario, $conteudo) {
    if (!isset($conteudo['alternativas'])) {
        return ['success' => false, 'message' => 'Exercício mal configurado'];
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
    
    $resposta_usuario_index = intval($resposta_usuario);
    $correto = ($resposta_usuario_index === $alternativa_correta_index);
    $resposta_selecionada_texto = $conteudo['alternativas'][$resposta_usuario_index]['texto'] ?? 'N/A';

    return [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? '✅ Correto! ' . ($conteudo['explicacao'] ?? '') : '❌ Incorreto. A resposta correta é: ' . $resposta_correta_texto,
        'resposta_correta' => $resposta_correta_texto,
        'resposta_selecionada' => $resposta_selecionada_texto,
        'pontuacao' => $correto ? 100 : 0,
        'alternativa_correta_id' => $alternativa_correta_index
    ];
}
?>