<?php
require_once __DIR__ . 
'/google_oauth_config.php';

// Verifica o contexto (usuário ou admin) para definir a URL de redirecionamento correta.
$context = $_GET['context'] ?? 'user'; // Padrão é 'user'

if ($context === 'admin') {
    $redirect_uri = GOOGLE_REDIRECT_URI_ADMIN;
} else {
    $redirect_uri = GOOGLE_REDIRECT_URI_USER;
}

$auth_url = GOOGLE_AUTH_URL . 
'?scope=' . urlencode(GOOGLE_SCOPE) .
'&redirect_uri=' . urlencode($redirect_uri) . // Usa a URI dinâmica
'&response_type=code' .
'&client_id=' . GOOGLE_CLIENT_ID .
'&access_type=offline';

header('Location: ' . $auth_url);
exit();
?>
