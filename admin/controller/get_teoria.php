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

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $database = new Database();
    $conn = $database->conn;

    switch ($method) {
        case 'GET':
            handleGetTeorias($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }

    $database->closeConnection();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function handleGetTeorias($conn) {
    $nivel = isset($_GET['nivel']) ? $_GET['nivel'] : null;
    $busca = isset($_GET['busca']) ? $_GET['busca'] : null;
    $idioma = isset($_GET['idioma']) ? $_GET['idioma'] : null;
    $caminho_id = isset($_GET['caminho_id']) ? $_GET['caminho_id'] : null;

    // Construir consulta SQL
    $sql = "SELECT t.id, t.titulo, t.nivel, t.idioma_id, t.caminho_id, t.ordem, t.resumo, t.palavras_chave, t.data_criacao, 
                   i.nome as idioma_nome, c.nome as caminho_nome 
            FROM teorias t 
            LEFT JOIN idiomas i ON t.idioma_id = i.id 
            LEFT JOIN caminhos_aprendizagem c ON t.caminho_id = c.id 
            WHERE 1=1";
    $params = [];
    $types = "";

    if ($nivel) {
        $sql .= " AND t.nivel = ?";
        $params[] = $nivel;
        $types .= "s";
    }

    if ($idioma) {
        $sql .= " AND i.nome = ?";
        $params[] = $idioma;
        $types .= "s";
    }

    if ($caminho_id) {
        $sql .= " AND t.caminho_id = ?";
        $params[] = $caminho_id;
        $types .= "i";
    }

    if ($busca) {
        $sql .= " AND (titulo LIKE ? OR resumo LIKE ? OR palavras_chave LIKE ?)";
        $busca_param = "%$busca%";
        $params[] = $busca_param;
        $params[] = $busca_param;
        $params[] = $busca_param;
        $types .= "sss";
    }

    $sql .= " ORDER BY i.nome, c.nome, t.nivel, t.ordem";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
        return;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $teorias = [];
    while ($row = $result->fetch_assoc()) {
        $teorias[] = $row;
    }
    
    $stmt->close();

    echo json_encode([
        'success' => true,
        'teorias' => $teorias
    ]);
}
?>