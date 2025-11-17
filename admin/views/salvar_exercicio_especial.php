<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}

$caminho_id = $_POST['caminho_id'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$pergunta = $_POST['pergunta'] ?? '';
$ordem = $_POST['ordem'] ?? 1;

if (!$caminho_id || !$titulo || !$tipo || !$pergunta) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Check if path has less than 5 special exercises
    $sql_count = "SELECT COUNT(*) as total FROM exercicios WHERE categoria = 'especial' AND bloco_id IN (SELECT id FROM blocos WHERE caminho_id = ?)";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $caminho_id);
    $stmt_count->execute();
    $count = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
    
    if ($count >= 5) {
        echo json_encode(['success' => false, 'message' => 'Limite de 5 exercícios especiais atingido']);
        exit();
    }
    
    // Get first block of the path to associate the exercise
    $sql_bloco = "SELECT id FROM blocos WHERE caminho_id = ? LIMIT 1";
    $stmt_bloco = $conn->prepare($sql_bloco);
    $stmt_bloco->bind_param("i", $caminho_id);
    $stmt_bloco->execute();
    $bloco_result = $stmt_bloco->get_result()->fetch_assoc();
    $stmt_bloco->close();
    
    if (!$bloco_result) {
        echo json_encode(['success' => false, 'message' => 'Nenhum bloco encontrado para este caminho']);
        exit();
    }
    
    $bloco_id = $bloco_result['id'];
    
    // Create basic content structure
    $conteudo = json_encode([
        'tipo_exercicio' => $tipo,
        'resposta_correta' => '',
        'alternativas' => [],
        'dica' => ''
    ]);
    
    $sql_insert = "INSERT INTO exercicios (bloco_id, pergunta, conteudo, tipo, categoria) VALUES (?, ?, ?, ?, 'especial')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isss", $bloco_id, $pergunta, $conteudo, $tipo);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Exercício especial criado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar exercício']);
    }
    
    $stmt_insert->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

$database->closeConnection();
?>