<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$nivel = $_GET['nivel'] ?? 'A1';
$idioma = $_SESSION['idioma_atual'] ?? null;

// Se não há idioma na sessão, tentar obter do progresso do usuário
if (!$idioma && isset($_SESSION['id_usuario'])) {
    $database = new Database();
    $conn = $database->conn;
    
    $sql_idioma = "SELECT idioma FROM progresso_usuario WHERE id_usuario = ? ORDER BY ultima_atividade DESC LIMIT 1";
    $stmt_idioma = $conn->prepare($sql_idioma);
    $stmt_idioma->bind_param("i", $_SESSION['id_usuario']);
    $stmt_idioma->execute();
    $result_idioma = $stmt_idioma->get_result();
    if ($row_idioma = $result_idioma->fetch_assoc()) {
        $idioma = $row_idioma['idioma'];
    }
    $stmt_idioma->close();
    $database->closeConnection();
}

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Filtrar teorias por nível e idioma
    if ($idioma) {
        $sql = "SELECT id, titulo, nivel, ordem, resumo FROM teorias WHERE nivel = ? AND idioma = ? ORDER BY ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nivel, $idioma);
    } else {
        // Fallback para quando não há idioma definido
        $sql = "SELECT id, titulo, nivel, ordem, resumo FROM teorias WHERE nivel = ? ORDER BY ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nivel);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teorias = [];
    while ($row = $result->fetch_assoc()) {
        $teorias[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();
    
    echo json_encode([
        'success' => true,
        'teorias' => $teorias
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teorias: ' . $e->getMessage()
    ]);
}
?>