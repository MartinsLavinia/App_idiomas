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

    // Buscar prova final da unidade
    $sql = "SELECT * FROM provas_finais WHERE id_unidade = ? AND ativa = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidade_id);
    $stmt->execute();
    $prova = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Se não existe, criar uma prova padrão
    if (!$prova) {
        $prova = criarProvaPadrao($conn, $unidade_id);
    }

    // Buscar questões da prova
    $sql_questoes = "SELECT * FROM questao_prova WHERE id_prova = ? ORDER BY ordem";
    $stmt_questoes = $conn->prepare($sql_questoes);
    $stmt_questoes->bind_param("i", $prova['id']);
    $stmt_questoes->execute();
    $questoes = $stmt_questoes->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_questoes->close();

    // Decodificar JSON fields
    foreach ($questoes as &$questao) {
        if ($questao['opcoes_resposta']) {
            $questao['opcoes_resposta'] = json_decode($questao['opcoes_resposta'], true);
        }
        if ($questao['resposta_correta']) {
            $questao['resposta_correta'] = json_decode($questao['resposta_correta'], true);
        }
    }

    $prova['questoes'] = $questoes;

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'prova' => $prova
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}

function criarProvaPadrao($conn, $unidade_id) {
    // Buscar informações da unidade
    $sql_unidade = "SELECT * FROM unidades WHERE id = ?";
    $stmt = $conn->prepare($sql_unidade);
    $stmt->bind_param("i", $unidade_id);
    $stmt->execute();
    $unidade_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Criar prova
    $sql_inserir = "INSERT INTO provas_finais (id_unidade, titulo, descricao, total_questoes, pontuacao_minima, tempo_limite) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $titulo = "Prova Final - " . $unidade_info['nome_unidade'];
    $descricao = "Avaliação final da unidade " . $unidade_info['nome_unidade'] . ". Você precisa de 70% de acerto para passar.";
    $total_questoes = 20;
    $pontuacao_minima = 70;
    $tempo_limite = 1800; // 30 minutos
    
    $stmt_inserir = $conn->prepare($sql_inserir);
    $stmt_inserir->bind_param("issiii", $unidade_id, $titulo, $descricao, $total_questoes, $pontuacao_minima, $tempo_limite);
    $stmt_inserir->execute();
    $prova_id = $stmt_inserir->insert_id;
    $stmt_inserir->close();

    // Criar questões padrão
    criarQuestoesPadrao($conn, $prova_id, $unidade_info);

    return [
        'id' => $prova_id,
        'id_unidade' => $unidade_id,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'total_questoes' => $total_questoes,
        'pontuacao_minima' => $pontuacao_minima,
        'tempo_limite' => $tempo_limite
    ];
}

function criarQuestoesPadrao($conn, $prova_id, $unidade_info) {
    $questoes = [
        // Questões de múltipla escolha
        [
            'pergunta' => 'Qual é a forma correta do verbo "to be" para "I"?',
            'tipo' => 'multipla_escolha',
            'opcoes_resposta' => json_encode([
                ['id' => 'a', 'texto' => 'am'],
                ['id' => 'b', 'texto' => 'is'],
                ['id' => 'c', 'texto' => 'are'],
                ['id' => 'd', 'texto' => 'be']
            ]),
            'resposta_correta' => json_encode(['a']),
            'explicacao' => 'A forma correta é "I am".',
            'pontos' => 5,
            'ordem' => 1
        ],
        // Adicionar mais 19 questões similares...
    ];

    foreach ($questoes as $index => $questao) {
        $sql = "INSERT INTO questao_prova (id_prova, pergunta, tipo, opcoes_resposta, resposta_correta, explicacao, pontos, ordem) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssii", 
            $prova_id, $questao['pergunta'], $questao['tipo'], $questao['opcoes_resposta'],
            $questao['resposta_correta'], $questao['explicacao'], $questao['pontos'], $questao['ordem']
        );
        $stmt->execute();
        $stmt->close();
    }
}
?>