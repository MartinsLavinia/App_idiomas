<?php
// Debug para verificar blocos e exercícios
session_start();
include_once __DIR__ . "/conexao.php";

$database = new Database();
$conn = $database->conn;

echo "<h2>Debug: Blocos e Exercícios</h2>";

// Buscar blocos
echo "<h3>Blocos disponíveis:</h3>";
$sql_blocos = "SELECT b.*, u.nome_unidade, u.idioma, u.nivel 
               FROM blocos b 
               JOIN caminhos_aprendizagem c ON b.caminho_id = c.id 
               JOIN unidades u ON c.id_unidade = u.id 
               ORDER BY b.id";
$result_blocos = $conn->query($sql_blocos);

if ($result_blocos && $result_blocos->num_rows > 0) {
    while ($bloco = $result_blocos->fetch_assoc()) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>Bloco ID:</strong> {$bloco['id']}<br>";
        echo "<strong>Nome:</strong> {$bloco['nome_bloco']}<br>";
        echo "<strong>Unidade:</strong> {$bloco['nome_unidade']} ({$bloco['idioma']} - {$bloco['nivel']})<br>";
        echo "<strong>Caminho ID:</strong> {$bloco['caminho_id']}<br>";
        
        // Buscar exercícios deste bloco
        $sql_exercicios = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
        $stmt_ex = $conn->prepare($sql_exercicios);
        $stmt_ex->bind_param("i", $bloco['id']);
        $stmt_ex->execute();
        $result_ex = $stmt_ex->get_result()->fetch_assoc();
        $stmt_ex->close();
        
        echo "<strong>Total de exercícios:</strong> {$result_ex['total']}<br>";
        
        if ($result_ex['total'] > 0) {
            // Mostrar alguns exercícios
            $sql_ex_detalhes = "SELECT id, pergunta, tipo, categoria FROM exercicios WHERE bloco_id = ? LIMIT 3";
            $stmt_det = $conn->prepare($sql_ex_detalhes);
            $stmt_det->bind_param("i", $bloco['id']);
            $stmt_det->execute();
            $result_det = $stmt_det->get_result();
            
            echo "<strong>Exercícios:</strong><ul>";
            while ($ex = $result_det->fetch_assoc()) {
                echo "<li>ID: {$ex['id']} - {$ex['pergunta']} (Tipo: {$ex['tipo']}, Categoria: {$ex['categoria']})</li>";
            }
            echo "</ul>";
            $stmt_det->close();
        }
        
        echo "</div>";
    }
} else {
    echo "Nenhum bloco encontrado.";
}

// Buscar teorias
echo "<h3>Teorias disponíveis:</h3>";
$sql_teorias = "SELECT * FROM teorias ORDER BY id LIMIT 5";
$result_teorias = $conn->query($sql_teorias);

if ($result_teorias && $result_teorias->num_rows > 0) {
    while ($teoria = $result_teorias->fetch_assoc()) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>Teoria ID:</strong> {$teoria['id']}<br>";
        echo "<strong>Título:</strong> {$teoria['titulo']}<br>";
        echo "<strong>Idioma:</strong> {$teoria['idioma']}<br>";
        echo "<strong>Nível:</strong> {$teoria['nivel']}<br>";
        echo "</div>";
    }
} else {
    echo "Nenhuma teoria encontrada.";
}

$database->closeConnection();
?>