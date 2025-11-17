<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}

$user_id = $_SESSION['id_usuario'];
$caminho_id = $_POST['caminho_id'] ?? null;
$tipo = $_POST['tipo'] ?? null; // 'bloco' ou 'especial'

if (!$caminho_id || !$tipo) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Verificar progresso atual
    $sql_check = "SELECT progresso, exercicio_atual FROM progresso_usuario WHERE id_usuario = ? AND caminho_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $caminho_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        // Criar novo progresso
        $sql_insert = "INSERT INTO progresso_usuario (id_usuario, caminho_id, progresso, exercicio_atual) VALUES (?, ?, 0, 1)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $user_id, $caminho_id);
        $stmt_insert->execute();
        $stmt_insert->close();
        $progresso_atual = 0;
    } else {
        $row = $result->fetch_assoc();
        $progresso_atual = $row['progresso'];
    }
    $stmt_check->close();
    
    // Calcular novo progresso
    // Cada caminho tem 5 blocos + 5 exercícios especiais = 10 itens total
    // Cada item vale 10% do progresso
    $incremento = 10.0;
    $novo_progresso = min(100, $progresso_atual + $incremento);
    
    // Atualizar progresso
    $sql_update = "UPDATE progresso_usuario SET progresso = ?, ultima_atividade = NOW() WHERE id_usuario = ? AND caminho_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dii", $novo_progresso, $user_id, $caminho_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    // Verificar se completou o caminho
    $concluido = $novo_progresso >= 100;
    if ($concluido) {
        $sql_complete = "UPDATE progresso_usuario SET concluido = 1 WHERE id_usuario = ? AND caminho_id = ?";
        $stmt_complete = $conn->prepare($sql_complete);
        $stmt_complete->bind_param("ii", $user_id, $caminho_id);
        $stmt_complete->execute();
        $stmt_complete->close();
    }
    
    echo json_encode([
        'success' => true,
        'progresso' => $novo_progresso,
        'concluido' => $concluido,
        'message' => $concluido ? 'Caminho concluído!' : 'Progresso atualizado!'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

$database->closeConnection();
?>