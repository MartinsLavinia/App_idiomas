<?php
// Script para verificar se há exercícios nos blocos
include_once __DIR__ . "/conexao.php";

$database = new Database();
$conn = $database->conn;

echo "<h2>Verificação de Exercícios nos Blocos</h2>";

// Buscar todos os blocos
$sql = "SELECT b.id, b.nome_bloco, b.caminho_id, 
               u.nome_unidade, u.idioma, u.nivel,
               (SELECT COUNT(*) FROM exercicios e WHERE e.bloco_id = b.id) as total_exercicios
        FROM blocos b
        JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
        JOIN unidades u ON c.id_unidade = u.id
        ORDER BY b.id";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Bloco ID</th><th>Nome do Bloco</th><th>Unidade</th><th>Idioma/Nível</th><th>Total Exercícios</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['total_exercicios'] > 0 ? "✅ OK" : "❌ SEM EXERCÍCIOS";
        $color = $row['total_exercicios'] > 0 ? "background-color: #d4edda;" : "background-color: #f8d7da;";
        
        echo "<tr style='$color'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nome_bloco']}</td>";
        echo "<td>{$row['nome_unidade']}</td>";
        echo "<td>{$row['idioma']} - {$row['nivel']}</td>";
        echo "<td>{$row['total_exercicios']}</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhum bloco encontrado.</p>";
}

// Verificar se há exercícios sem bloco_id
echo "<h3>Exercícios sem bloco_id:</h3>";
$sql_sem_bloco = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id IS NULL";
$result_sem_bloco = $conn->query($sql_sem_bloco);
$sem_bloco = $result_sem_bloco->fetch_assoc();
echo "<p>Total de exercícios sem bloco_id: {$sem_bloco['total']}</p>";

// Verificar estrutura da tabela exercicios
echo "<h3>Estrutura da tabela exercicios:</h3>";
$sql_estrutura = "DESCRIBE exercicios";
$result_estrutura = $conn->query($sql_estrutura);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($campo = $result_estrutura->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$campo['Field']}</td>";
    echo "<td>{$campo['Type']}</td>";
    echo "<td>{$campo['Null']}</td>";
    echo "<td>{$campo['Key']}</td>";
    echo "<td>{$campo['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

$database->closeConnection();
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>