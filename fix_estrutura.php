<?php
include_once __DIR__ . '/conexao.php';

$database = new Database();
$conn = $database->conn;

echo "<h2>Estrutura das Tabelas</h2>";

echo "<h3>Tabela idiomas:</h3>";
$result = $conn->query("SHOW COLUMNS FROM idiomas");
$colunas_idiomas = [];
while ($row = $result->fetch_assoc()) {
    $colunas_idiomas[] = $row['Field'];
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

echo "<h3>Tabela teorias:</h3>";
$result = $conn->query("SHOW COLUMNS FROM teorias");
$colunas_teorias = [];
while ($row = $result->fetch_assoc()) {
    $colunas_teorias[] = $row['Field'];
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

echo "<h3>Dados de exemplo - idiomas:</h3>";
$result = $conn->query("SELECT * FROM idiomas LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

echo "<h3>Dados de exemplo - teorias:</h3>";
$result = $conn->query("SELECT * FROM teorias LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// Sugerir correções
echo "<h3>Sugestões de correção:</h3>";

if (in_array('idioma', $colunas_idiomas)) {
    echo "✅ Usar coluna 'idioma' na tabela idiomas<br>";
} elseif (in_array('nome', $colunas_idiomas)) {
    echo "✅ Usar coluna 'nome' na tabela idiomas<br>";
} else {
    echo "❌ Nenhuma coluna padrão encontrada na tabela idiomas<br>";
}

if (in_array('idioma_id', $colunas_teorias)) {
    echo "✅ Coluna 'idioma_id' existe na tabela teorias<br>";
} else {
    echo "❌ Coluna 'idioma_id' NÃO existe na tabela teorias<br>";
}

if (in_array('caminho_id', $colunas_teorias)) {
    echo "✅ Coluna 'caminho_id' existe na tabela teorias<br>";
} else {
    echo "❌ Coluna 'caminho_id' NÃO existe na tabela teorias<br>";
}

$database->closeConnection();
?>