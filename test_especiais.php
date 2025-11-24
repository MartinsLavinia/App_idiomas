<?php
include_once 'conexao.php';

$database = new Database();
$conn = $database->conn;

// Verificar se existem exercícios especiais
$sql = "SELECT COUNT(*) as total FROM exercicios_especiais";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "Total de exercícios especiais: " . $row['total'] . "<br>";

if ($row['total'] > 0) {
    $sql = "SELECT id, titulo FROM exercicios_especiais LIMIT 5";
    $result = $conn->query($sql);
    
    echo "<h3>Exercícios encontrados:</h3>";
    while ($exercicio = $result->fetch_assoc()) {
        echo "ID: " . $exercicio['id'] . " - Título: " . $exercicio['titulo'] . "<br>";
    }
} else {
    echo "Nenhum exercício especial encontrado no banco de dados.";
}

$database->closeConnection();
?>