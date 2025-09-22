<?php
session_start();

// Incluir o arquivo de conexão simulado
include_once __DIR__ . 
'/../config/conexao.php';

// Incluir o arquivo com as funções de CRUD de unidade
include_once __DIR__ . 
'/unidade_functions.php';

// Simular um admin logado
$_SESSION['id_admin'] = 1;
$_SESSION['IS_TESTING'] = true;

$database = new Database();
$conn = $database->conn;

// --- Teste de Adição de Unidade ---
echo "\n--- Teste de Adição de Unidade ---\n";
$result_add = adicionarUnidade($conn, 'Nova Unidade', 'Descrição da Nova Unidade', 1); // Idioma ID 1 (Português)
if ($result_add['status'] === 'success') {
    echo "SUCESSO: " . $result_add['message'] . "\n";
} else {
    echo "FALHA: " . $result_add['message'] . "\n";
}

// --- Teste de Adição de Unidade Duplicada ---
echo "\n--- Teste de Adição de Unidade Duplicada ---\n";
$result_add_duplicate = adicionarUnidade($conn, 'Nova Unidade', 'Outra descrição', 1); // Mesmo nome e idioma
if ($result_add_duplicate['status'] === 'success') {
    echo "SUCESSO: " . $result_add_duplicate['message'] . "\n";
} else {
    echo "FALHA: " . $result_add_duplicate['message'] . "\n";
}

// --- Teste de Edição de Unidade ---
echo "\n--- Teste de Edição de Unidade ---\n";
// Assumindo que 'Nova Unidade' foi adicionada e tem ID 103 (mocked)
$result_edit = editarUnidade($conn, 103, 'Unidade Editada', 'Descrição Editada', 1);
if ($result_edit['status'] === 'success') {
    echo "SUCESSO: " . $result_edit['message'] . "\n";
} else {
    echo "FALHA: " . $result_edit['message'] . "\n";
}

// --- Teste de Edição de Unidade para um nome já existente no mesmo idioma ---
echo "\n--- Teste de Edição de Unidade para nome existente ---\n";
// Tentar mudar 'Unidade Editada' (ID 103) para 'Unidade 1' (ID 101), que já existe para o idioma 1
$result_edit_existing = editarUnidade($conn, 103, 'Unidade 1', 'Descrição Editada', 1);
if ($result_edit_existing['status'] === 'success') {
    echo "SUCESSO: " . $result_edit_existing['message'] . "\n";
} else {
    echo "FALHA: " . $result_edit_existing['message'] . "\n";
}

// --- Teste de Exclusão de Unidade ---
echo "\n--- Teste de Exclusão de Unidade ---\n";
// Assumindo que 'Unidade Editada' tem ID 103
$result_delete = eliminarUnidade($conn, 103);
if ($result_delete['status'] === 'success') {
    echo "SUCESSO: " . $result_delete['message'] . "\n";
} else {
    echo "FALHA: " . $result_delete['message'] . "\n";
}

// --- Teste de Exclusão de Unidade com Caminhos Associados ---
echo "\n--- Teste de Exclusão de Unidade com Caminhos Associados ---\n";
// Tentar excluir a unidade 'Unidade 1' (ID 101), que tem caminhos associados no mock
$result_delete_with_paths = eliminarUnidade($conn, 101);
if ($result_delete_with_paths['status'] === 'success') {
    echo "SUCESSO: " . $result_delete_with_paths['message'] . "\n";
} else {
    echo "FALHA: " . $result_delete_with_paths['message'] . "\n";
}

$database->closeConnection();

// Limpar a sessão
session_destroy();
?>