<?php
require_once __DIR__ . 
'/../../public/views/google_oauth_config.php';
include_once __DIR__ . 
'/../../conexao.php';
session_start();

if (isset($_GET[
'code'
])) {
    $code = $_GET[
'code'
];

    // Troca o código de autorização por um token de acesso
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI_ADMIN,
        'grant_type' => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data[
'access_token'
])) {
        // Obtém informações do usuário
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token_data[
'access_token'
]
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_info_response = curl_exec($ch);
        curl_close($ch);

        $user_info = json_decode($user_info_response, true);

        if (isset($user_info[
'email'
])) {
            $database = new Database();
            $conn = $database->conn;

            // Verifica se o administrador já existe (usando email como nome de usuário)
            $stmt = $conn->prepare("SELECT id, nome_usuario FROM administradores WHERE nome_usuario = ?");
            $stmt->bind_param("s", $user_info[
'email'
]);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Administrador existe, faz login
                $admin = $result->fetch_assoc();
                $_SESSION[
'id_admin'
] = $admin[
'id'
];
                $_SESSION[
'nome_admin'
] = $admin[
'nome_usuario'
];
            } else {
                // Administrador não existe, cadastra (com senha aleatória)
                $nome_usuario = $user_info[
'email'
];
                $senha_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Senha aleatória

                $stmt_insert = $conn->prepare("INSERT INTO administradores (nome_usuario, senhaadm) VALUES (?, ?)");
                $stmt_insert->bind_param("ss", $nome_usuario, $senha_hash);
                $stmt_insert->execute();
                $id_admin = $conn->insert_id;
                $stmt_insert->close();

                $_SESSION[
'id_admin'
] = $id_admin;
                $_SESSION[
'nome_admin'
] = $nome_usuario;
            }

            $stmt->close();
            $database->closeConnection();

            header("Location: gerenciar_caminho.php"); // Redirecionar para o painel do admin
            exit();
        }
    }
}

header("Location: login_admin.php");
exit();
?>