<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Aceitar tanto atividade_id quanto bloco_id
$id_busca = null;
$tipo_busca = '';

if (isset($_GET['bloco_id']) && is_numeric($_GET['bloco_id'])) {
    $id_busca = (int)$_GET['bloco_id'];
    $tipo_busca = 'bloco';
} elseif (isset($_GET['atividade_id']) && is_numeric($_GET['atividade_id'])) {
    $id_busca = (int)$_GET['atividade_id'];
    $tipo_busca = 'atividade';
} else {
    echo json_encode(['success' => false, 'message' => 'ID do bloco ou atividade inválido']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    $exercicios = [];
    
    if ($tipo_busca === 'bloco') {
        // Buscar exercícios por bloco_id
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo,

                e.caminho_id,
                e.bloco_id
            FROM exercicios e
            WHERE e.bloco_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    } else {
        // Buscar exercícios por caminho_id (atividade)
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo,

                e.caminho_id,
                e.bloco_id
            FROM exercicios e
            WHERE e.caminho_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['conteudo'] && is_string($row['conteudo']) && $row['conteudo'][0] === '{') {
            try {
                $conteudo_decodificado = json_decode($row['conteudo'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($conteudo_decodificado['alternativas'])) {
                        $row['tipo_exercicio'] = 'multipla_escolha';
                    } elseif (isset($conteudo_decodificado['frase_completar'])) {
                        $row['tipo_exercicio'] = 'completar';
                    } else {
                        $row['tipo_exercicio'] = 'texto_livre';
                    }
                    $row['conteudo'] = $conteudo_decodificado;
                }
            } catch (Exception $e) {
                error_log("Erro ao decodificar JSON: " . $e->getMessage());
            }
        }
        $exercicios[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'tipo_busca' => $tipo_busca,
        'id_busca' => $id_busca,
        'total_exercicios' => count($exercicios),
        'exercicios' => $exercicios
    ]);

} catch (Exception $e) {
    error_log("Erro em get_exercicio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>