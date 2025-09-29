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
$respostas = isset($input['respostas']) ? $input['respostas'] : [];
$tempo_gasto = isset($input['tempo_gasto']) ? (int)$input['tempo_gasto'] : 0;

if (!$prova_id || empty($respostas)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar informações da prova
    $sql_prova = "SELECT * FROM provas_finais WHERE id = ?";
    $stmt_prova = $conn->prepare($sql_prova);
    $stmt_prova->bind_param("i", $prova_id);
    $stmt_prova->execute();
    $prova = $stmt_prova->get_result()->fetch_assoc();
    $stmt_prova->close();

    // Buscar questões da prova
    $sql_questoes = "SELECT * FROM questao_prova WHERE id_prova = ?";
    $stmt_questoes = $conn->prepare($sql_questoes);
    $stmt_questoes->bind_param("i", $prova_id);
    $stmt_questoes->execute();
    $questoes = $stmt_questoes->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_questoes->close();

    // Calcular pontuação
    $pontuacao_total = 0;
    $acertos = 0;
    $resultados_questoes = [];

    foreach ($respostas as $resposta) {
        $id_questao = $resposta['id_questao'];
        $resposta_usuario = $resposta['resposta'];

        // Encontrar a questão correspondente
        $questao = null;
        foreach ($questoes as $q) {
            if ($q['id'] == $id_questao) {
                $questao = $q;
                break;
            }
        }

        if (!$questao) continue;

        // Verificar se acertou
        $acertou = false;
        $resposta_correta = json_decode($questao['resposta_correta'], true);

        switch ($questao['tipo']) {
            case 'multipla_escolha':
                $acertou = ($resposta_usuario === $resposta_correta[0]);
                break;
                
            case 'preencher_lacunas':
                $acertou = (count(array_intersect($resposta_usuario, $resposta_correta)) === count($resposta_correta));
                break;
                
            case 'ordenar':
            case 'arrastar_soltar':
                $acertou = ($resposta_usuario === $resposta_correta);
                break;
        }

        $pontos_questao = $acertou ? $questao['pontos'] : 0;
        $pontuacao_total += $pontos_questao;
        
        if ($acertou) {
            $acertos++;
        }

        $resultados_questoes[] = [
            'id_questao' => $id_questao,
            'acertou' => $acertou,
            'pontos' => $pontos_questao,
            'resposta_correta' => $resposta_correta,
            'explicacao' => $questao['explicacao']
        ];
    }

    // Calcular percentual
    $percentual_acerto = ($acertos / count($questoes)) * 100;
    $aprovado = ($percentual_acerto >= $prova['pontuacao_minima']);

    // Salvar resultado
    $sql_resultado = "INSERT INTO resultado_prova 
                     (id_usuario, id_prova, pontuacao_total, percentual_acerto, tempo_gasto, data_conclusao, aprovado) 
                     VALUES (?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_resultado = $conn->prepare($sql_resultado);
    $stmt_resultado->bind_param("iiidii", $id_usuario, $prova_id, $pontuacao_total, $percentual_acerto, $tempo_gasto, $aprovado);
    $stmt_resultado->execute();
    $resultado_id = $stmt_resultado->insert_id;
    $stmt_resultado->close();

    // Se aprovado, liberar próxima unidade
    if ($aprovado) {
        liberarProximaUnidade($conn, $id_usuario, $prova['id_unidade']);
    }

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'aprovado' => $aprovado,
        'resultado' => [
            'pontuacao_total' => $pontuacao_total,
            'percentual_acerto' => round($percentual_acerto, 2),
            'acertos' => $acertos,
            'total_questoes' => count($questoes),
            'pontuacao_minima' => $prova['pontuacao_minima'],
            'tempo_gasto' => $tempo_gasto,
            'resultados_questoes' => $resultados_questoes
        ],
        'feedback' => gerarFeedbackProva($percentual_acerto, $prova['pontuacao_minima'])
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao finalizar prova: ' . $e->getMessage()
    ]);
}

function liberarProximaUnidade($conn, $id_usuario, $id_unidade_atual) {
    // Buscar próxima unidade
    $sql_proxima = "SELECT id FROM unidades WHERE id > ? ORDER BY id LIMIT 1";
    $stmt = $conn->prepare($sql_proxima);
    $stmt->bind_param("i", $id_unidade_atual);
    $stmt->execute();
    $proxima_unidade = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($proxima_unidade) {
        // Atualizar progresso do usuário para a próxima unidade
        $sql_progresso = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, caminho_id, exercicio_atual) 
                         SELECT ?, idioma, nivel, NULL, 1 FROM unidades WHERE id = ?
                         ON DUPLICATE KEY UPDATE exercicio_atual = 1";
        $stmt_progresso = $conn->prepare($sql_progresso);
        $stmt_progresso->bind_param("ii", $id_usuario, $proxima_unidade['id']);
        $stmt_progresso->execute();
        $stmt_progresso->close();
    }
}

function gerarFeedbackProva($percentual, $minimo) {
    if ($percentual >= 90) {
        return [
            'titulo' => 'Excelente!',
            'mensagem' => 'Você dominou completamente esta unidade!',
            'icone' => 'fa-trophy',
            'cor' => 'success'
        ];
    } elseif ($percentual >= $minimo) {
        return [
            'titulo' => 'Parabéns!',
            'mensagem' => 'Você foi aprovado e pode avançar para a próxima unidade!',
            'icone' => 'fa-check-circle',
            'cor' => 'primary'
        ];
    } else {
        return [
            'titulo' => 'Continue Estudando',
            'mensagem' => 'Revise o conteúdo e tente novamente. Você consegue!',
            'icone' => 'fa-redo',
            'cor' => 'warning'
        ];
    }
}
?>