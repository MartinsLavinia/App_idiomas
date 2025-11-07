<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../conexao.php';

try {
    $database = new Database();
    $conn = $database->conn;
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados inválidos');
    }
    
    $exercicioId = $data['exercicio_id'] ?? null;
    $fraseTranscrita = $data['frase_transcrita'] ?? '';
    
    if (!$exercicioId) {
        throw new Exception('ID do exercício é obrigatório');
    }
    
    // Buscar exercício
    $sql = "SELECT * FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exercicio = $result->fetch_assoc();
    $stmt->close();
    
    if (!$exercicio) {
        throw new Exception('Exercício não encontrado');
    }
    
    // Processar conteúdo
    $conteudo = json_decode($exercicio['conteudo'], true) ?: [];
    $fraseEsperada = $conteudo['frase_esperada'] ?? $conteudo['texto_para_falar'] ?? '';
    
    // Análise simples de similaridade
    $fraseTranscritaLower = strtolower(trim($fraseTranscrita));
    $fraseEsperadaLower = strtolower(trim($fraseEsperada));
    
    $palavrasEsperadas = explode(' ', $fraseEsperadaLower);
    $palavrasTranscritas = explode(' ', $fraseTranscritaLower);
    
    $palavrasCorretas = 0;
    foreach ($palavrasEsperadas as $palavra) {
        foreach ($palavrasTranscritas as $transcrita) {
            if (strpos($transcrita, $palavra) !== false || strpos($palavra, $transcrita) !== false) {
                $palavrasCorretas++;
                break;
            }
        }
    }
    
    $similaridade = count($palavrasEsperadas) > 0 ? $palavrasCorretas / count($palavrasEsperadas) : 0;
    $correto = $similaridade >= 0.6;
    
    // Feedback baseado na similaridade
    $feedback = '';
    if ($similaridade >= 0.9) {
        $feedback = 'Excelente pronúncia!';
    } elseif ($similaridade >= 0.7) {
        $feedback = 'Boa pronúncia, continue praticando!';
    } elseif ($similaridade >= 0.5) {
        $feedback = 'Pronúncia razoável, tente falar mais claramente.';
    } else {
        $feedback = 'Tente novamente, fale mais devagar e claramente.';
    }
    
    echo json_encode([
        'success' => true,
        'correto' => $correto,
        'similaridade' => round($similaridade * 100),
        'frase_esperada' => $fraseEsperada,
        'frase_transcrita' => $fraseTranscrita,
        'feedback' => $feedback,
        'explicacao' => $feedback
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($database)) {
        $database->closeConnection();
    }
}
?>