<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido";
    exit();
}

$teoria_id = $_GET['id'];
$database = new Database();
$conn = $database->conn;

echo "<h3>Debug da Exclusão - Teoria ID: $teoria_id</h3>";

// 1. Verificar se a teoria existe
$sql_verificar = "SELECT * FROM teorias WHERE id = ?";
$stmt_verificar = $conn->prepare($sql_verificar);
$stmt_verificar->bind_param("i", $teoria_id);
$stmt_verificar->execute();
$resultado = $stmt_verificar->get_result();
$teoria = $resultado->fetch_assoc();
$stmt_verificar->close();

if (!$teoria) {
    echo "<p style='color: red;'>❌ Teoria não encontrada!</p>";
    exit();
}

echo "<p style='color: green;'>✅ Teoria encontrada:</p>";
echo "<pre>" . print_r($teoria, true) . "</pre>";

// 2. Verificar restrições de chave estrangeira
echo "<h4>Verificando dependências:</h4>";

// Verificar se há unidades que referenciam esta teoria
$sql_unidades = "SELECT COUNT(*) as total FROM unidades WHERE teoria_id = ?";
$stmt_unidades = $conn->prepare($sql_unidades);
$stmt_unidades->bind_param("i", $teoria_id);
$stmt_unidades->execute();
$result_unidades = $stmt_unidades->get_result();
$unidades_count = $result_unidades->fetch_assoc()['total'];
$stmt_unidades->close();

echo "<p>Unidades que referenciam esta teoria: $unidades_count</p>";

// 3. Tentar a exclusão com debug
echo "<h4>Tentando exclusão:</h4>";

if ($unidades_count > 0) {
    echo "<p style='color: orange;'>⚠️ Existem $unidades_count unidades que referenciam esta teoria. Isso pode impedir a exclusão.</p>";
    
    // Mostrar as unidades
    $sql_mostrar_unidades = "SELECT id, titulo FROM unidades WHERE teoria_id = ?";
    $stmt_mostrar = $conn->prepare($sql_mostrar_unidades);
    $stmt_mostrar->bind_param("i", $teoria_id);
    $stmt_mostrar->execute();
    $unidades_result = $stmt_mostrar->get_result();
    
    echo "<p>Unidades que impedem a exclusão:</p><ul>";
    while ($unidade = $unidades_result->fetch_assoc()) {
        echo "<li>ID: {$unidade['id']} - {$unidade['titulo']}</li>";
    }
    echo "</ul>";
    $stmt_mostrar->close();
    
    echo "<p><strong>Solução:</strong> Você precisa excluir ou mover essas unidades primeiro.</p>";
} else {
    // Tentar excluir
    $sql_excluir = "DELETE FROM teorias WHERE id = ?";
    $stmt_excluir = $conn->prepare($sql_excluir);
    $stmt_excluir->bind_param("i", $teoria_id);
    
    if ($stmt_excluir->execute()) {
        if ($stmt_excluir->affected_rows > 0) {
            echo "<p style='color: green;'>✅ Teoria excluída com sucesso!</p>";
            echo "<p><a href='gerenciar_teorias.php'>Voltar para gerenciar teorias</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Nenhuma linha foi afetada. A teoria pode não existir.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Erro na exclusão: " . $conn->error . "</p>";
    }
    $stmt_excluir->close();
}

$database->closeConnection();
?>