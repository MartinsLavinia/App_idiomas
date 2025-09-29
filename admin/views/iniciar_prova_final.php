<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$prova_id = isset($input['prova_id']) ? (int)$input['prova_id'] : null;

if (!$prova_id) {
    echo json_encode(['success' => false, 'message' => 'ID da prova inválido']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Verificar se já existe um resultado em andamento
    $sql = "SELECT * FROM resultado_prova WHERE id_usuario = ? AND id_prova = ? AND data_conclusao IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $prova_id);
    $stmt->execute();
    $resultado_existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resultado_existente) {
        // Retornar o resultado existente
        echo json_encode([
            'success' => true,
            'resultado_id' => $resultado_existente['id'],
            'message' => 'Prova já iniciada'
        ]);
        exit();
    }

    // Criar novo resultado
    $sql_insert = "INSERT INTO resultado_prova (id_usuario, id_prova, data_inicio) VALUES (?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ii", $id_usuario, $prova_id);
    $stmt_insert->execute();
    $resultado_id = $stmt_insert->insert_id;
    $stmt_insert->close();

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'resultado_id' => $resultado_id,
        'message' => 'Prova iniciada com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>