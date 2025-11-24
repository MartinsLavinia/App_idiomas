<?php
// Debug version of get_exercicio.php
header('Content-Type: application/json');
session_start();
include_once 'conexao.php';

// Simular parâmetros
$_GET['bloco_id'] = 1; // Usar um bloco existente
$_SESSION['id_usuario'] = 1; // Simular usuário logado

$id_busca = (int)$_GET['bloco_id'];
$tipo_busca = 'bloco';
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    $exercicios = [];
    $idioma_exercicio = null;

    // Buscar exercícios normais
    $sql = "
        SELECT 
            e.id,
            e.ordem,
            e.tipo,
            e.pergunta,
            e.conteudo, 
            e.categoria,
            e.dificuldade,
            e.caminho_id,
            e.bloco_id,
            u.idioma AS idioma_exercicio
        FROM exercicios e
        JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
        JOIN unidades u ON c.id_unidade = u.id
        WHERE e.bloco_id = ?
        ORDER BY e.ordem ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_busca);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Exercícios normais encontrados: " . $result->num_rows . "\n";
    
    while ($row = $result->fetch_assoc()) {
        if ($idioma_exercicio === null) {
            $idioma_exercicio = $row['idioma_exercicio'];
        }
        unset($row['idioma_exercicio']);
        $exercicios[] = $row;
    }
    
    $stmt->close();
    
    // ADICIONAR EXERCÍCIOS ESPECIAIS
    $sql_especiais = "SELECT id, titulo as pergunta, conteudo FROM exercicios_especiais ORDER BY id";
    $stmt_especiais = $conn->prepare($sql_especiais);
    $stmt_especiais->execute();
    $result_especiais = $stmt_especiais->get_result();
    
    echo "Exercícios especiais encontrados: " . $result_especiais->num_rows . "\n";
    
    while ($row_especial = $result_especiais->fetch_assoc()) {
        $exercicio_especial = [
            'id' => 'especial_' . $row_especial['id'],
            'ordem' => 999 + $row_especial['id'],
            'tipo' => 'especial',
            'pergunta' => $row_especial['pergunta'],
            'conteudo' => json_decode($row_especial['conteudo'], true),
            'categoria' => 'especial',
            'dificuldade' => 'medio',
            'caminho_id' => null,
            'bloco_id' => null
        ];
        
        $exercicios[] = $exercicio_especial;
        echo "Adicionado exercício especial: " . $row_especial['pergunta'] . "\n";
    }
    
    $stmt_especiais->close();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'tipo_busca' => $tipo_busca,
        'id_busca' => $id_busca,
        'total_exercicios' => count($exercicios),
        'total_especiais' => count(array_filter($exercicios, function($e) { return $e['tipo'] === 'especial'; })),
        'idioma' => $idioma_exercicio,
        'exercicios' => $exercicios
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>