<?php
/**
 * API para geração de áudio no admin
 * Corrige problemas de geração de áudio
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

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
    
    // Validar idioma
    $idiomasPermitidos = ['en-us', 'en-gb', 'pt-br', 'es-es', 'fr-fr', 'de-de'];
    if (!in_array($idioma, $idiomasPermitidos)) {
        $idioma = 'en-us'; // Fallback
    }
    
    // Verificar se a classe existe
    if (!class_exists('\App\Services\AudioService')) {
        throw new Exception('Serviço de áudio não disponível');
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
    // Log error but don't output it to prevent HTML in JSON
    error_log('Erro na geração de áudio: ' . $e->getMessage());
    
    http_response_code(200); // Keep 200 to prevent browser errors
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar áudio. Tente novamente.',
        'audio_url' => null
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Handle fatal errors
    error_log('Erro fatal na geração de áudio: ' . $e->getMessage());
    
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno. Tente novamente.',
        'audio_url' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>
<?php
// Ensure no output after this point
exit();
?>