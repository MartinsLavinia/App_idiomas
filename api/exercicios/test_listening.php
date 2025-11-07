<?php
// Teste simples da API de listening
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API de listening funcionando',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>