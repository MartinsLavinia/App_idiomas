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

if (!isset($_GET['unidade_id']) || !is_numeric($_GET['unidade_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da unidade inválido']);
    exit();
}

$unidade_id = (int)$_GET['unidade_id'];
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar informações da unidade
    $sql_unidade = "SELECT id, nome_unidade, idioma, nivel FROM unidades WHERE id = ?";
    $stmt_unidade = $conn->prepare($sql_unidade);
    $stmt_unidade->bind_param("i", $unidade_id);
    $stmt_unidade->execute();
    $unidade_info = $stmt_unidade->get_result()->fetch_assoc();
    $stmt_unidade->close();

    if (!$unidade_info) {
        echo json_encode(['success' => false, 'message' => 'Unidade não encontrada']);
        exit();
    }

    // Buscar ou criar blocos para esta unidade
    $blocos = criarOuBuscarBlocos($conn, $unidade_id, $unidade_info);

    // Buscar progresso do usuário nos blocos
    $blocos_com_progresso = [];
    foreach ($blocos as $bloco) {
        $sql_progresso = "SELECT * FROM progresso_bloco WHERE id_usuario = ? AND id_bloco = ?";
        $stmt_progresso = $conn->prepare($sql_progresso);
        $stmt_progresso->bind_param("ii", $id_usuario, $bloco['id']);
        $stmt_progresso->execute();
        $progresso = $stmt_progresso->get_result()->fetch_assoc();
        $stmt_progresso->close();

        $bloco['progresso'] = $progresso ?: [
            'atividades_concluidas' => 0,
            'total_atividades' => $bloco['total_atividades'],
            'progresso_percentual' => 0,
            'concluido' => false,
            'pontos_obtidos' => 0,
            'total_pontos' => $bloco['total_atividades'] * 10
        ];

        $blocos_com_progresso[] = $bloco;
    }

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'unidade' => $unidade_info,
        'blocos' => $blocos_com_progresso
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}

function criarOuBuscarBlocos($conn, $unidade_id, $unidade_info) {
    // Verificar se já existem blocos para esta unidade
    $sql = "SELECT * FROM blocos_atividades WHERE id_unidade = ? ORDER BY ordem";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidade_id);
    $stmt->execute();
    $blocos_existentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Se já existem blocos, retorná-los
    if (!empty($blocos_existentes)) {
        return $blocos_existentes;
    }

    // Criar 10 blocos padrão
    $nomes_blocos = [
        "Fundamentos Básicos",
        "Construindo Frases", 
        "Conversação Inicial",
        "Gramática Essencial",
        "Pronúncia e Fala",
        "Compreensão Auditiva",
        "Vocabulário Expandido",
        "Estruturas Complexas",
        "Fluência e Velocidade",
        "Domínio Completo"
    ];

    $blocos = [];
    for ($i = 1; $i <= 10; $i++) {
        $nome = $nomes_blocos[$i-1] ?? "Bloco " . $i;
        $descricao = "Bloco de atividades " . $i . " - " . $unidade_info['nome_unidade'];
        
        $sql_inserir = "INSERT INTO blocos_atividades (id_unidade, numero_bloco, nome_bloco, descricao, ordem) VALUES (?, ?, ?, ?, ?)";
        $stmt_inserir = $conn->prepare($sql_inserir);
        $stmt_inserir->bind_param("iissi", $unidade_id, $i, $nome, $descricao, $i);
        $stmt_inserir->execute();
        
        $bloco_id = $stmt_inserir->insert_id;
        $stmt_inserir->close();

        $blocos[] = [
            'id' => $bloco_id,
            'id_unidade' => $unidade_id,
            'numero_bloco' => $i,
            'nome_bloco' => $nome,
            'descricao' => $descricao,
            'total_atividades' => 12,
            'atividades_gramatica' => 6,
            'atividades_fala' => 3,
            'atividades_dificeis' => 3,
            'ordem' => $i
        ];
    }

    return $blocos;
}
?>