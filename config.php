<?php
// Configurações do sistema
define('BASE_URL', '/App_idiomas');
define('API_URL', BASE_URL . '/api');
define('ADMIN_URL', BASE_URL . '/admin');
define('PUBLIC_URL', BASE_URL . '/public');

// URLs das APIs
define('API_PROCESSAR_EXERCICIO', API_URL . '/processar_exercicio.php');
define('API_LISTENING', API_URL . '/exercicios/listening.php');
define('API_FALA', API_URL . '/fala.php');

// Função para obter URL completa
function getFullUrl($path) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . $path;
}
?>