<?php

// Substitua pelos seus dados do Google Cloud Console
define("GOOGLE_CLIENT_ID", "YOUR_CLIENT_ID");
define("GOOGLE_CLIENT_SECRET", "YOUR_CLIENT_SECRET");
define("GOOGLE_REDIRECT_URI_USER", "http://localhost:8000/public/views/google_oauth_callback.php"); // URL de callback para usuários
define("GOOGLE_REDIRECT_URI_ADMIN", "http://localhost:8000/admin/views/google_oauth_callback_admin.php"); // URL de callback para administradores

// Escopos de acesso que você está solicitando
define("GOOGLE_SCOPE", "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile");

// URL de autorização do Google
define("GOOGLE_AUTH_URL", "https://accounts.google.com/o/oauth2/auth");

// URL para obter o token de acesso
define("GOOGLE_TOKEN_URL", "https://oauth2.googleapis.com/token");

// URL para obter informações do usuário
define("GOOGLE_USERINFO_URL", "https://www.googleapis.com/oauth2/v1/userinfo");

?>