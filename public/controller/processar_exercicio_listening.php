<?php
// Processador específico para exercícios de listening
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar exercício de listening
    $sql = "SELECT * FROM exercicios_listening WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $exercicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    // Processar resposta
    $opcoes = json_decode($exercicio['opcoes'], true);
    $resposta_correta_index = intval($exercicio['resposta_correta']);
    $resposta_usuario_index = intval($resposta_usuario);

    if (!is_array($opcoes) || !isset($opcoes[$resposta_correta_index])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Exercício mal configurado'
        ]);
        exit();
    }

    $correto = ($resposta_usuario_index === $resposta_correta_index);
    $resposta_correta_texto = $opcoes[$resposta_correta_index];
    $resposta_selecionada_texto = $opcoes[$resposta_usuario_index] ?? 'N/A';

    // Gerar explicação detalhada
    $explicacao = '';
    if ($correto) {
        $explicacao = '✅ Correto! Você compreendeu o áudio perfeitamente!';
        if (!empty($exercicio['frase'])) {
            $explicacao .= ' A frase original era: "' . $exercicio['frase'] . '".';
        }
    } else {
        $explicacao = '❌ Incorreto. A resposta correta é: "' . $resposta_correta_texto . '".';
        if (!empty($exercicio['frase'])) {
            $explicacao .= ' A frase original era: "' . $exercicio['frase'] . '".';
        }
        $explicacao .= ' Ouça o áudio novamente com atenção.';
    }

    // Registrar resposta
    $sql_resposta = "INSERT INTO respostas_usuario (id_usuario, exercicio_id, resposta, correto, data_resposta, tipo_exercicio) 
                     VALUES (?, ?, ?, ?, NOW(), 'listening')";
    $stmt_resposta = $conn->prepare($sql_resposta);
    $correto_db = $correto ? 1 : 0;
    $stmt_resposta->bind_param("iisi", $id_usuario, $exercicio_id, $resposta_usuario, $correto_db);
    $stmt_resposta->execute();
    $stmt_resposta->close();

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'correto' => $correto,
        'explicacao' => $explicacao,
        'resposta_correta' => $resposta_correta_texto,
        'resposta_selecionada' => $resposta_selecionada_texto,
        'alternativa_correta_id' => $resposta_correta_index,
        'pontuacao' => $correto ? 100 : 0,
        'frase_original' => $exercicio['frase'] ?? '',
        'audio_url' => $exercicio['audio_url'] ?? ''
    ]);

} catch (Exception $e) {
    if (isset($database)) {
        $database->closeConnection();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>