<?php
session_start();

// Incluir o arquivo de conexão simulado
include_once __DIR__ . 
'/../config/conexao.php';

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
$result_edit = editarIdioma($conn, 3, 'Espanhol (Atualizado)');
if ($result_edit['status'] === 'success') {
    echo "SUCESSO: " . $result_edit['message'] . "\n";
} else {
    echo "FALHA: " . $result_edit['message'] . "\n";
}

// --- Teste de Edição de Idioma para um nome já existente ---
echo "\n--- Teste de Edição de Idioma para nome existente ---\n";
// Tentar mudar 'Espanhol (Atualizado)' para 'Português' (que já existe)
$result_edit_existing = editarIdioma($conn, 3, 'Português');
if ($result_edit_existing['status'] === 'success') {
    echo "SUCESSO: " . $result_edit_existing['message'] . "\n";
} else {
    echo "FALHA: " . $result_edit_existing['message'] . "\n";
}

// --- Teste de Exclusão de Idioma ---
echo "\n--- Teste de Exclusão de Idioma ---\n";
// Assumindo que 'Espanhol (Atualizado)' tem ID 3
$result_delete = eliminarIdioma($conn, 3);
if ($result_delete['status'] === 'success') {
    echo "SUCESSO: " . $result_delete['message'] . "\n";
} else {
    echo "FALHA: " . $result_delete['message'] . "\n";
}

// --- Teste de Exclusão de Idioma com Unidades Associadas ---
echo "\n--- Teste de Exclusão de Idioma com Unidades Associadas ---\n";
// Tentar excluir o idioma 'Português' (ID 1), que tem unidades associadas no mock
$result_delete_with_units = eliminarIdioma($conn, 1);
if ($result_delete_with_units['status'] === 'success') {
    echo "SUCESSO: " . $result_delete_with_units['message'] . "\n";
} else {
    echo "FALHA: " . $result_delete_with_units['message'] . "\n";
}

$database->closeConnection();

// Limpar a sessão
session_destroy();
?>