<?php
try {
    session_start();
    include_once __DIR__ . "/../../../conexao.php";
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION["id_usuario"])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
        exit();
    }
    
    $database = new Database();
    $conn = $database->conn;
    
    $nivel = $_GET['nivel'] ?? 'A1';
    $idioma = $_GET['idioma'] ?? 'ingles';
    
    // Buscar teorias do banco
    $sql = "SELECT id, titulo, conteudo, nivel FROM teorias WHERE nivel = ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nivel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teorias = [];
    while ($row = $result->fetch_assoc()) {
        $teorias[] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'resumo' => substr(strip_tags($row['conteudo']), 0, 100) . '...',
            'conteudo' => $row['conteudo'],
            'nivel' => $row['nivel']
        ];
    }
    
    $stmt->close();
    $database->closeConnection();
    
    echo json_encode([
        'success' => true,
        'teorias' => $teorias,
        'debug' => [
            'nivel' => $nivel,
            'idioma' => $idioma,
            'total' => count($teorias)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage(),
        'debug' => [
            'file' => __FILE__,
            'line' => $e->getLine()
        ]
    ]);
}
?>