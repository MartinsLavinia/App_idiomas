<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;

    $sql = "SELECT id, titulo, conteudo FROM exercicios_especiais ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exercicios_especiais = [];
    
    while ($row = $result->fetch_assoc()) {
        $conteudo = json_decode($row['conteudo'], true);
        
        $exercicio = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'tipo_exercicio' => $conteudo['tipo_exercicio'] ?? 'observar',
            'link_video' => $conteudo['link_video'] ?? '',
            'letra_musica' => $conteudo['letra_musica'] ?? '',
            'conteudo_completo' => $conteudo
        ];
        
        $exercicios_especiais[] = $exercicio;
    }
    
    $stmt->close();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'total' => count($exercicios_especiais),
        'exercicios' => $exercicios_especiais
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>