<?php
require_once __DIR__ . 
'/google_oauth_config.php';

$auth_url = GOOGLE_AUTH_URL . 
'?scope=' . urlencode(GOOGLE_SCOPE) .
'&redirect_uri=' . urlencode(GOOGLE_REDIRECT_URI_USER) .
'&response_type=code' .
'&client_id=' . GOOGLE_CLIENT_ID .
'&access_type=offline';

header('Location: ' . $auth_url);
exit();
?>
