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
$exercicio_id = isset($input['exercicio_id']) ? (int)$input['exercicio_id'] : null;
$resposta_usuario = isset($input['resposta']) ? $input['resposta'] : null;
$tempo_gasto = isset($input['tempo_gasto']) ? (int)$input['tempo_gasto'] : 0;

if (!$exercicio_id || $resposta_usuario === null) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar exercício especial
    $sql = "SELECT * FROM exercicios_especiais WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $exercicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    // Verificar resposta
    $acertou = false;
    $pontos_obtidos = 0;
    $resposta_correta = json_decode($exercicio['resposta_correta'], true);

    switch ($exercicio['tipo_exercicio']) {
        case 'multipla_escolha':
            $acertou = ($resposta_usuario === $resposta_correta[0]);
            break;
            
        case 'preencher_lacunas':
            $acertou = (count(array_intersect($resposta_usuario, $resposta_correta)) === count($resposta_correta));
            break;
            
        case 'ordenar':
            $acertou = ($resposta_usuario === $resposta_correta);
            break;
            
        case 'arrastar_soltar':
            $acertou = ($resposta_usuario === $resposta_correta);
            break;
    }

    if ($acertou) {
        $pontos_obtidos = $exercicio['pontos'];
    }

    // Atualizar progresso do bloco com pontos do exercício especial
    $sql_progresso = "
        UPDATE progresso_bloco 
        SET pontos_obtidos = pontos_obtidos + ?, 
            atividades_concluidas = atividades_concluidas + 1,
            progresso_percentual = ((atividades_concluidas + 1) / (total_atividades + 1)) * 100
        WHERE id_bloco = ? AND id_usuario = ?
    ";
    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param("iii", $pontos_obtidos, $exercicio['id_bloco'], $id_usuario);
    $stmt_progresso->execute();
    $stmt_progresso->close();

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'acertou' => $acertou,
        'pontos_obtidos' => $pontos_obtidos,
        'explicacao' => $exercicio['explicacao'],
        'resposta_correta' => $resposta_correta,
        'feedback' => $acertou ? 
            'Parabéns! Você acertou o exercício especial!' : 
            'Não foi dessa vez, mas continue praticando!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao finalizar exercício especial: ' . $e->getMessage()
    ]);
}
?>