<?php
include_once __DIR__ . "/conexao.php";

$database = new Database();
$conn = $database->conn;

echo "<h2>Debug - Exercícios de Listening</h2>";

// Buscar exercícios de listening
$sql = "SELECT id, pergunta, categoria, conteudo FROM exercicios WHERE categoria = 'audicao' OR conteudo LIKE '%listening%' OR conteudo LIKE '%opcoes%' LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h3>Exercício ID: " . $row['id'] . "</h3>";
        echo "<p><strong>Pergunta:</strong> " . htmlspecialchars($row['pergunta']) . "</p>";
        echo "<p><strong>Categoria:</strong> " . htmlspecialchars($row['categoria']) . "</p>";
        echo "<p><strong>Conteúdo JSON:</strong></p>";
        echo "<pre>" . htmlspecialchars($row['conteudo']) . "</pre>";
        
        // Tentar decodificar JSON
        $conteudo = json_decode($row['conteudo'], true);
        if ($conteudo) {
            echo "<p><strong>Conteúdo Decodificado:</strong></p>";
            echo "<pre>" . print_r($conteudo, true) . "</pre>";
            
            if (isset($conteudo['opcoes'])) {
                echo "<p><strong>Opções encontradas:</strong> " . count($conteudo['opcoes']) . "</p>";
                foreach ($conteudo['opcoes'] as $i => $opcao) {
                    echo "<p>Opção $i: " . htmlspecialchars($opcao) . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>Erro ao decodificar JSON!</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p>Nenhum exercício de listening encontrado.</p>";
}

$database->closeConnection();
?>