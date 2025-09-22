<?php
session_start();

// Incluir o arquivo de conexão simulado
include_once __DIR__ . 
'/config/conexao.php';

// Incluir o arquivo com as funções de CRUD de idioma
include_once __DIR__ . 
'/idioma_functions.php';


// Simular um admin logado
$_SESSION['id_admin'] = 1;
$_SESSION['IS_TESTING'] = true;

$database = new Database();
$conn = $database->conn;

// --- Teste de Adição de Idioma ---
echo "\n--- Teste de Adição de Idioma ---\n";
$result_add = adicionarIdioma($conn, 'Espanhol');
if ($result_add['status'] === 'success') {
    echo "SUCESSO: " . $result_add['message'] . "\n";
} else {
    echo "FALHA: " . $result_add['message'] . "\n";
}

// --- Teste de Adição de Idioma Duplicado ---
echo "\n--- Teste de Adição de Idioma Duplicado ---\n";
$result_add_duplicate = adicionarIdioma($conn, 'Espanhol');
if ($result_add_duplicate['status'] === 'success') {
    echo "SUCESSO: " . $result_add_duplicate['message'] . "\n";
} else {
    echo "FALHA: " . $result_add_duplicate['message'] . "\n";
}

// --- Teste de Edição de Idioma ---
echo "\n--- Teste de Edição de Idioma ---\n";
// Assumindo que 'Espanhol' foi adicionado e tem ID 3 (mocked)
// Para um teste real, você precisaria buscar o ID do idioma recém-adicionado
$id_espanhol = null;
$sql_get_id = "SELECT id FROM idiomas WHERE nome_idioma = 'Espanhol'";
$result_get_id = $conn->query($sql_get_id);
if ($result_get_id && $result_get_id->num_rows > 0) {
    $id_espanhol = $result_get_id->fetch_assoc()['id'];
}

if ($id_espanhol) {
    $result_edit = editarIdioma($conn, $id_espanhol, 'Espanhol (Atualizado)');
    if ($result_edit['status'] === 'success') {
        echo "SUCESSO: " . $result_edit['message'] . "\n";
    } else {
        echo "FALHA: " . $result_edit['message'] . "\n";
    }
} else {
    echo "FALHA: Não foi possível encontrar o ID do idioma 'Espanhol' para edição.\n";
}

// --- Teste de Edição de Idioma para um nome já existente ---
echo "\n--- Teste de Edição de Idioma para nome existente ---\n";
// Tentar mudar 'Espanhol (Atualizado)' para 'Português' (que já existe)
if ($id_espanhol) {
    $result_edit_existing = editarIdioma($conn, $id_espanhol, 'Português');
    if ($result_edit_existing['status'] === 'success') {
        echo "SUCESSO: " . $result_edit_existing['message'] . "\n";
    } else {
        echo "FALHA: " . $result_edit_existing['message'] . "\n";
    }
} else {
    echo "FALHA: Não foi possível encontrar o ID do idioma 'Espanhol' para teste de edição com nome existente.\n";
}

// --- Teste de Exclusão de Idioma ---
echo "\n--- Teste de Exclusão de Idioma ---\n";
// Assumindo que 'Espanhol (Atualizado)' tem ID $id_espanhol
if ($id_espanhol) {
    $result_delete = eliminarIdioma($conn, $id_espanhol);
    if ($result_delete['status'] === 'success') {
        echo "SUCESSO: " . $result_delete['message'] . "\n";
    } else {
        echo "FALHA: " . $result_delete['message'] . "\n";
    }
} else {
    echo "FALHA: Não foi possível encontrar o ID do idioma 'Espanhol' para exclusão.\n";
}

// --- Teste de Exclusão de Idioma com Unidades Associadas ---
echo "\n--- Teste de Exclusão de Idioma com Unidades Associadas ---\n";
// Tentar excluir o idioma 'Inglês' (ID 2), que tem unidades associadas no mock
$result_delete_with_units = eliminarIdioma($conn, 2);
if ($result_delete_with_units['status'] === 'success') {
    echo "SUCESSO: " . $result_delete_with_units['message'] . "\n";
} else {
    echo "FALHA: " . $result_delete_with_units['message'] . "\n";
}

$database->closeConnection();

// Limpar a sessão
session_destroy();
?>
