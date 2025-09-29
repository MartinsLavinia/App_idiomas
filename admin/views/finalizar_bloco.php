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
$bloco_id = isset($input['bloco_id']) ? (int)$input['bloco_id'] : null;
$resultados = isset($input['resultados']) ? $input['resultados'] : [];
$tempo_total = isset($input['tempo_total']) ? (int)$input['tempo_total'] : 0;

if (!$bloco_id || empty($resultados)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Calcular estatísticas
    $total_atividades = count($resultados);
    $atividades_concluidas = 0;
    $pontos_obtidos = 0;
    $total_pontos = $total_atividades * 10; // 10 pontos por atividade
    $acertos_por_categoria = [
        'gramatica' => 0,
        'fala' => 0,
        'dificil' => 0
    ];
    $total_por_categoria = [
        'gramatica' => 0,
        'fala' => 0,
        'dificil' => 0
    ];

    foreach ($resultados as $resultado) {
        if ($resultado['concluido']) {
            $atividades_concluidas++;
            $pontos_obtidos += $resultado['pontos'];
            
            // Estatísticas por categoria
            $categoria = $resultado['categoria'];
            if (isset($acertos_por_categoria[$categoria])) {
                $acertos_por_categoria[$categoria]++;
            }
        }
        
        // Contar totais por categoria
        $categoria = $resultado['categoria'];
        if (isset($total_por_categoria[$categoria])) {
            $total_por_categoria[$categoria]++;
        }
    }

    $progresso_percentual = ($atividades_concluidas / $total_atividades) * 100;
    $concluido = $atividades_concluidas >= $total_atividades;

    // Atualizar progresso do bloco
    $sql_progresso = "
        INSERT INTO progresso_bloco 
        (id_usuario, id_bloco, total_atividades, atividades_concluidas, total_pontos, pontos_obtidos, progresso_percentual, concluido, tempo_total, data_conclusao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        atividades_concluidas = VALUES(atividades_concluidas),
        pontos_obtidos = VALUES(pontos_obtidos),
        progresso_percentual = VALUES(progresso_percentual),
        concluido = VALUES(concluido),
        tempo_total = VALUES(tempo_total),
        data_conclusao = NOW()
    ";

    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param(
        "iiiiidiii", 
        $id_usuario, $bloco_id, $total_atividades, $atividades_concluidas, 
        $total_pontos, $pontos_obtidos, $progresso_percentual, $concluido, $tempo_total
    );
    $stmt_progresso->execute();
    $id_progresso_bloco = $stmt_progresso->insert_id;
    $stmt_progresso->close();

    // Salvar resultados individuais das atividades
    foreach ($resultados as $resultado) {
        if (isset($resultado['id_exercicio']) && $resultado['id_exercicio'] != 'demo') {
            $sql_resultado = "
                INSERT INTO resultado_atividade_bloco 
                (id_progresso_bloco, id_exercicio, acertou, pontos_obtidos, tempo_gasto, tentativas)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt_resultado = $conn->prepare($sql_resultado);
            $acertou = $resultado['pontos'] >= 5 ? 1 : 0; // Considera acerto se fez pelo menos 5 pontos
            $stmt_resultado->bind_param(
                "iiiidi", 
                $id_progresso_bloco, $resultado['id_exercicio'], $acertou, 
                $resultado['pontos'], $resultado['tempo_gasto'], $resultado['tentativas']
            );
            $stmt_resultado->execute();
            $stmt_resultado->close();
        }
    }

    // Calcular porcentagem de acerto por categoria
    $porcentagem_por_categoria = [];
    foreach ($acertos_por_categoria as $categoria => $acertos) {
        $total = $total_por_categoria[$categoria] ?: 1;
        $porcentagem_por_categoria[$categoria] = ($acertos / $total) * 100;
    }

    // Buscar informações do bloco para a resposta
    $sql_bloco = "SELECT * FROM blocos_atividades WHERE id = ?";
    $stmt_bloco = $conn->prepare($sql_bloco);
    $stmt_bloco->bind_param("i", $bloco_id);
    $stmt_bloco->execute();
    $bloco_info = $stmt_bloco->get_result()->fetch_assoc();
    $stmt_bloco->close();

    $database->closeConnection();

    // Preparar resposta com resultados detalhados
    $resultado_final = [
        'bloco' => $bloco_info,
        'estatisticas' => [
            'total_atividades' => $total_atividades,
            'atividades_concluidas' => $atividades_concluidas,
            'progresso_percentual' => round($progresso_percentual, 2),
            'pontos_obtidos' => $pontos_obtidos,
            'total_pontos' => $total_pontos,
            'porcentagem_acerto' => round(($pontos_obtidos / $total_pontos) * 100, 2),
            'tempo_total' => $tempo_total,
            'concluido' => $concluido
        ],
        'desempenho_por_categoria' => [
            'gramatica' => [
                'acertos' => $acertos_por_categoria['gramatica'],
                'total' => $total_por_categoria['gramatica'],
                'porcentagem' => round($porcentagem_por_categoria['gramatica'], 2)
            ],
            'fala' => [
                'acertos' => $acertos_por_categoria['fala'],
                'total' => $total_por_categoria['fala'],
                'porcentagem' => round($porcentagem_por_categoria['fala'], 2)
            ],
            'dificil' => [
                'acertos' => $acertos_por_categoria['dificil'],
                'total' => $total_por_categoria['dificil'],
                'porcentagem' => round($porcentagem_por_categoria['dificil'], 2)
            ]
        ],
        'feedback' => gerarFeedback($progresso_percentual, $porcentagem_por_categoria)
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Bloco finalizado com sucesso!',
        'resultado' => $resultado_final
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao finalizar bloco: ' . $e->getMessage()
    ]);
}

function gerarFeedback($progresso, $porcentagens) {
    if ($progresso >= 90) {
        return [
            'titulo' => 'Excelente!',
            'mensagem' => 'Você dominou este bloco completamente!',
            'icone' => 'fa-trophy',
            'cor' => 'success'
        ];
    } elseif ($progresso >= 70) {
        return [
            'titulo' => 'Muito Bom!',
            'mensagem' => 'Ótimo desempenho, continue assim!',
            'icone' => 'fa-star',
            'cor' => 'primary'
        ];
    } elseif ($progresso >= 50) {
        return [
            'titulo' => 'Bom Trabalho',
            'mensagem' => 'Você está no caminho certo, pratique um pouco mais.',
            'icone' => 'fa-check-circle',
            'cor' => 'warning'
        ];
    } else {
        return [
            'titulo' => 'Continue Praticando',
            'mensagem' => 'Revise o conteúdo e tente novamente.',
            'icone' => 'fa-redo',
            'cor' => 'info'
        ];
    }
}
?>