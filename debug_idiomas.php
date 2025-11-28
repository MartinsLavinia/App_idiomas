<?php
include_once __DIR__ . '/conexao.php';

$database = new Database();
$conn = $database->conn;

echo "<h3>Estrutura da tabela idiomas:</h3>";
$result = $conn->query("DESCRIBE idiomas");
while ($row = $result->fetch_assoc()) {
    echo "Coluna: " . $row['Field'] . " - Tipo: " . $row['Type'] . "<br>";
}

echo "<h3>Dados da tabela idiomas:</h3>";
$result = $conn->query("SELECT * FROM idiomas LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

$database->closeConnection();
?>