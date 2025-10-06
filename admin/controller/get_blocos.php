<?php
// get_blocos.php - Coloque na pasta controller
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

    // Buscar caminhos relacionados a esta unidade
    $sql_caminhos = "SELECT id, nome_caminho FROM caminhos_aprendizagem WHERE id_unidade = ?";
    $stmt_caminhos = $conn->prepare($sql_caminhos);
    $stmt_caminhos->bind_param("i", $unidade_id);
    $stmt_caminhos->execute();
    $caminhos = $stmt_caminhos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_caminhos->close();

    $blocos_com_progresso = [];

    // Para cada caminho, buscar os blocos
    foreach ($caminhos as $caminho) {
        // Buscar blocos do caminho
        $sql_blocos = "SELECT id, nome_bloco, ordem FROM blocos WHERE caminho_id = ? ORDER BY ordem ASC";
        $stmt_blocos = $conn->prepare($sql_blocos);
        $stmt_blocos->bind_param("i", $caminho['id']);
        $stmt_blocos->execute();
        $blocos = $stmt_blocos->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_blocos->close();

        // Processar cada bloco
        foreach ($blocos as $bloco) {
            // Buscar exercícios do bloco
            $sql_exercicios = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
            $stmt_exercicios = $conn->prepare($sql_exercicios);
            $stmt_exercicios->bind_param("i", $bloco['id']);
            $stmt_exercicios->execute();
            $result_exercicios = $stmt_exercicios->get_result()->fetch_assoc();
            $stmt_exercicios->close();

            $total_exercicios = $result_exercicios['total'] ?? 0;

            // Buscar progresso do usuário na tabela progresso_bloco
            $sql_progresso = "SELECT * FROM progresso_bloco WHERE id_usuario = ? AND id_bloco = ?";
            $stmt_progresso = $conn->prepare($sql_progresso);
            $stmt_progresso->bind_param("ii", $id_usuario, $bloco['id']);
            $stmt_progresso->execute();
            $progresso = $stmt_progresso->get_result()->fetch_assoc();
            $stmt_progresso->close();

            // Se não existe registro de progresso, criar um padrão
            if (!$progresso) {
                $progresso = [
                    'atividades_concluidas' => 0,
                    'total_atividades' => $total_exercicios,
                    'progresso_percentual' => 0,
                    'concluido' => false,
                    'pontos_obtidos' => 0,
                    'total_pontos' => $total_exercicios * 10
                ];
            } else {
                // Usar dados existentes da tabela progresso_bloco
                $progresso_percentual = $progresso['total_atividades'] > 0 ? 
                    round(($progresso['atividades_concluidas'] / $progresso['total_atividades']) * 100) : 0;
                
                $progresso['progresso_percentual'] = $progresso_percentual;
                $progresso['concluido'] = ($progresso['atividades_concluidas'] >= $progresso['total_atividades']) && ($progresso['total_atividades'] > 0);
            }

            // Adicionar informações adicionais ao bloco
            $bloco_completo = [
                'id' => $bloco['id'],
                'nome_bloco' => $bloco['nome_bloco'],
                'ordem' => $bloco['ordem'],
                'caminho_nome' => $caminho['nome_caminho'],
                'descricao' => "Bloco " . $bloco['ordem'] . " - " . $caminho['nome_caminho'],
                'progresso' => $progresso
            ];

            $blocos_com_progresso[] = $bloco_completo;
        }
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
?>