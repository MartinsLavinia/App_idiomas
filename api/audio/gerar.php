<?php
/**
 * API para geração de áudio
 * Corrige problemas de geração de áudio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Autoload simplificado
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../../src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $frase = $_POST['frase'] ?? '';
    $idioma = $_POST['idioma'] ?? 'en-us';
    
    if (empty($frase)) {
        throw new Exception('Frase é obrigatória');
    }
    
    if (strlen($frase) > 300) {
        throw new Exception('Frase muito longa. Máximo 300 caracteres');
    }
    
    // Criar serviço de áudio
    $audioService = new \App\Services\AudioService();
    
    // Gerar áudio
    $audioUrl = $audioService->gerarAudio($frase, $idioma);
    
    echo json_encode([
        'success' => true,
        'audio_url' => $audioUrl,
        'frase' => $frase,
        'idioma' => $idioma,
        'message' => 'Áudio gerado com sucesso'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'audio_url' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>