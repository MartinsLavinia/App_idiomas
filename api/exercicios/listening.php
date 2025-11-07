<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . "/../../conexao.php";

// Log para debug
error_log('API Listening chamada - ' . date('Y-m-d H:i:s'));

try {
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Usuário não autenticado');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = file_get_contents('php://input');
    error_log('Input recebido: ' . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados JSON inválidos');
    }
    
    $exercicio_id = $data['exercicio_id'] ?? null;
    $resposta = $data['resposta'] ?? null;
    
    if (!$exercicio_id || $resposta === null) {
        throw new Exception('Exercício ID e resposta são obrigatórios');
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
    
    // Processar conteúdo
    $conteudo = json_decode($exercicio['conteudo'], true) ?? [];
    error_log('Conteúdo do exercício: ' . json_encode($conteudo));
    
    // Verificar resposta
    $resposta_correta = $conteudo['resposta_correta'] ?? 0;
    $correto = (int)$resposta === (int)$resposta_correta;
    
    // Obter textos das opções
    $opcoes = $conteudo['opcoes'] ?? [];
    $resposta_texto = isset($opcoes[(int)$resposta]) ? $opcoes[(int)$resposta] : 'Opção inválida';
    $correta_texto = isset($opcoes[$resposta_correta]) ? $opcoes[$resposta_correta] : 'Opção não encontrada';
    
    // Preparar resposta
    $response = [
        'success' => true,
        'correto' => $correto,
        'explicacao' => $correto ? 
            '✅ Excelente! Você entendeu corretamente o áudio.' : 
            '❌ Não foi dessa vez. A resposta correta é: "' . $correta_texto . '"',
        'transcricao' => $conteudo['transcricao'] ?? $conteudo['frase_original'] ?? '',
        'dicas_compreensao' => $conteudo['dicas_compreensao'] ?? 'Concentre-se nas palavras-chave e tente identificar o contexto geral da conversa.',
        'alternativa_correta_id' => $resposta_correta,
        'resposta_selecionada' => $resposta_texto,
        'resposta_correta' => $correta_texto
    ];
    
    error_log('Resposta enviada: ' . json_encode($response));
    
    $database->closeConnection();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Erro na API Listening: ' . $e->getMessage());
    
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
?>