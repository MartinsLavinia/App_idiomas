<?php
// admin/controller/gerar_audio_api.php

header('Content-Type: application/json');

// Incluir o controller que tem a lógica de geração
include_once __DIR__ . '/listening_controller.php';

// Ativar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validação básica da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

$frase = $_POST['frase'] ?? '';
$idioma = $_POST['idioma'] ?? 'en-us';

if (empty($frase)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'A frase não pode estar vazia.']);
    exit();
}

try {
    $listeningController = new ListeningController();
    // O controller agora retorna um caminho relativo a partir da raiz do projeto
    $caminhoRelativo = $listeningController->gerarAudio($frase, $idioma);
    
    // Adiciona um timestamp para evitar problemas de cache do navegador
    $url_com_cache_bust = $caminhoRelativo . '?t=' . time();
    
    echo json_encode(['success' => true, 'audio_url' => $url_com_cache_bust]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}