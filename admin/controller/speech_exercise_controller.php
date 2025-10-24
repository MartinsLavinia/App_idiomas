<?php

// Incluir a conexão com o banco de dados e o Model
include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/app/models/SpeechExerciseModel.php';

class SpeechExerciseController {

    private $model;
    private $conn;

    public function __construct() {
        // A classe Database deve estar acessível via conexao.php
        $database = new Database();
        $this->conn = $database->conn;
        $this->model = new SpeechExerciseModel($database);
    }

    /**
     * Endpoint para processar a fala do usuário e validar a pronúncia.
     * 
     * @param int $exercicioId ID do exercício
     * @param string $recognizedText Texto reconhecido pela Web Speech API
     * @return array Resultado da validação
     */
    public function validateSpeech($exercicioId, $recognizedText) {
        // Configura o cabeçalho para JSON
        header('Content-Type: application/json');

        if (empty($exercicioId) || empty($recognizedText)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'ID do exercício e texto reconhecido são obrigatórios.'];
        }

        $exercicio = $this->model->findById($exercicioId);

        if (!$exercicio) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Exercício de fala não encontrado.'];
        }

        $conteudo = json_decode($exercicio['conteudo'], true);
        $fraseEsperada = $conteudo['frase_esperada'] ?? '';
        $toleranciaErro = $conteudo['tolerancia_erro'] ?? 0.8;

        // --- Lógica de Validação da Fala (Simulação) ---

        // 1. Normalização do Texto
        $normalizedExpected = $this->normalizeText($fraseEsperada);
        $normalizedRecognized = $this->normalizeText($recognizedText);

        // 2. Cálculo de Similaridade (usando similar_text para simular a correção)
        // O ideal seria um serviço de IA para avaliação fonética, mas simulamos com similaridade de string.
        
        $similarity = 0;
        similar_text($normalizedExpected, $normalizedRecognized, $similarity);
        $similarity = $similarity / 100; // Converte para um valor entre 0 e 1

        $isCorrect = $similarity >= $toleranciaErro;

        // 3. Feedback Detalhado
        $feedback = [
            'expected' => $fraseEsperada,
            'recognized' => $recognizedText,
            'similarity_score' => round($similarity, 4),
            'required_score' => $toleranciaErro,
            'is_correct' => $isCorrect,
            'message' => $isCorrect ? 'Parabéns! Sua pronúncia está correta.' : 'Tente novamente. A frase esperada era: "' . $fraseEsperada . '".'
        ];

        return ['success' => true, 'result' => $feedback];
    }

    /**
     * Normaliza o texto para comparação (remove pontuação, converte para minúsculas).
     * @param string $text
     * @return string
     */
    private function normalizeText($text) {
        // Remove pontuação e converte para minúsculas
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text); 
        $text = preg_replace('/\s+/', ' ', $text); 
        return trim($text);
    }
}

// --- Lógica de Roteamento (API Endpoint) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Simulação de leitura de dados JSON
    $data = json_decode(file_get_contents("php://input"), true);

    // O arquivo speech_exercise_controller.php já existe no upload, então vamos usá-lo como endpoint.
    // O usuário deve ter um arquivo 'conexao.php' na raiz ou no mesmo nível para que este código funcione.
    
    $exercicioId = $data['exercicio_id'] ?? null;
    $recognizedText = $data['recognized_text'] ?? null;

    $controller = new SpeechExerciseController();
    $result = $controller->validateSpeech($exercicioId, $recognizedText);

    echo json_encode($result);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Exemplo de endpoint GET para buscar o exercício
    // Este endpoint será usado pelo JS para carregar a frase esperada
    
    $exercicioId = $_GET['exercicio_id'] ?? null;

    if (!$exercicioId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do exercício é obrigatório.']);
        exit;
    }

    $controller = new SpeechExerciseController();
    $exercicio = $controller->model->findById($exercicioId);

    if (!$exercicio) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado.']);
        exit;
    }

    $conteudo = json_decode($exercicio['conteudo'], true);

    echo json_encode([
        'success' => true,
        'exercicio_id' => $exercicioId,
        'pergunta' => $exercicio['pergunta'],
        'frase_esperada' => $conteudo['frase_esperada'] ?? '',
        'tolerancia_erro' => $conteudo['tolerancia_erro'] ?? 0.8
    ]);
    exit;
}
?>
