<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$bloco_id = $_POST['bloco_id'] ?? null;
$id_usuario = $_SESSION['id_usuario'];

if (!$bloco_id) {
    echo json_encode(['success' => false, 'message' => 'ID do bloco não fornecido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Contar total de exercícios no bloco (máximo 12)
    $sql_total = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $bloco_id);
    $stmt_total->execute();
    $total_exercicios = min(12, $stmt_total->get_result()->fetch_assoc()['total']);
    $stmt_total->close();

    // Contar exercícios respondidos pelo usuário (limitado aos primeiros 12)
    $sql_respondidos = "SELECT COUNT(DISTINCT exercicio_id) as respondidos 
                       FROM respostas_exercicios 
                       WHERE usuario_id = ? AND exercicio_id IN (
                           SELECT id FROM exercicios WHERE bloco_id = ? ORDER BY ordem ASC LIMIT 12
                       )";
    $stmt_respondidos = $conn->prepare($sql_respondidos);
    $stmt_respondidos->bind_param("ii", $id_usuario, $bloco_id);
    $stmt_respondidos->execute();
    $exercicios_respondidos = $stmt_respondidos->get_result()->fetch_assoc()['respondidos'];
    $stmt_respondidos->close();

    // Calcular progresso
    $progresso = $total_exercicios > 0 ? round(($exercicios_respondidos / $total_exercicios) * 100) : 0;
    $concluido = ($exercicios_respondidos >= $total_exercicios && $total_exercicios > 0);

    // Atualizar ou inserir progresso do bloco
    $sql_upsert = "INSERT INTO progresso_bloco (usuario_id, bloco_id, progresso_percentual, atividades_concluidas, total_atividades, concluido, data_atualizacao) 
                   VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                   ON DUPLICATE KEY UPDATE 
                   progresso_percentual = VALUES(progresso_percentual),
                   atividades_concluidas = VALUES(atividades_concluidas),
                   total_atividades = VALUES(total_atividades),
                   concluido = VALUES(concluido),
                   data_atualizacao = NOW()";
    
    $stmt_upsert = $conn->prepare($sql_upsert);
    $stmt_upsert->bind_param("iiiiis", $id_usuario, $bloco_id, $progresso, $exercicios_respondidos, $total_exercicios, $concluido);
    $stmt_upsert->execute();
    $stmt_upsert->close();

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'progresso' => $progresso,
        'concluido' => $concluido,
        'atividades_concluidas' => $exercicios_respondidos,
        'total_atividades' => $total_exercicios
    ]);

} catch (Exception $e) {
    $database->closeConnection();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>