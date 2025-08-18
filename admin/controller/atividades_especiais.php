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
            handleGetAtividadesEspeciais($conn);
            break;
        case 'POST':
            handlePostAtividadeEspecial($conn);
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

function handleGetAtividadesEspeciais($conn) {
    $unidade_id = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : null;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;

    if (!$unidade_id) {
        echo json_encode(['success' => false, 'message' => 'ID da unidade é obrigatório']);
        return;
    }

    // Buscar atividades especiais para a unidade
    $sql = "
        SELECT 
            id,
            nome,
            tipo,
            conteudo_url,
            conteudo_texto,
            exercicios,
            ordem
        FROM atividades_especiais 
        WHERE unidade_id = ?
    ";
    
    $params = [$unidade_id];
    
    if ($tipo) {
        $sql .= " AND tipo = ?";
        $params[] = $tipo;
    }
    
    $sql .= " ORDER BY ordem ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
        return;
    }

    $stmt->bind_param(str_repeat('i', count($params) - 1) . 's', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $atividades = [];
    while ($row = $result->fetch_assoc()) {
        // Decodificar exercícios JSON
        if ($row['exercicios']) {
            $row['exercicios'] = json_decode($row['exercicios'], true);
        }
        $atividades[] = $row;
    }
    
    $stmt->close();

    // Se não há atividades, criar exemplos
    if (empty($atividades)) {
        $atividades = criarAtividadesExemplo($unidade_id, $tipo);
    }

    echo json_encode([
        'success' => true,
        'atividades' => $atividades
    ]);
}

function handlePostAtividadeEspecial($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['acao'])) {
        echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
        return;
    }

    switch ($input['acao']) {
        case 'escolher_opcao':
            escolherOpcaoAtividade($conn, $input);
            break;
        case 'responder_exercicio':
            responderExercicioEspecial($conn, $input);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
}

function escolherOpcaoAtividade($conn, $input) {
    $unidade_id = $input['unidade_id'] ?? null;
    $tipo_escolhido = $input['tipo_escolhido'] ?? null; // 'musica' ou 'filme_serie'
    $id_usuario = $_SESSION['id_usuario'];

    if (!$unidade_id || !$tipo_escolhido) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        return;
    }

    // Buscar atividade do tipo escolhido
    $sql = "
        SELECT id, nome, conteudo_texto, conteudo_url, exercicios 
        FROM atividades_especiais 
        WHERE unidade_id = ? AND tipo = ? 
        ORDER BY RAND() 
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $unidade_id, $tipo_escolhido);
    $stmt->execute();
    $result = $stmt->get_result();
    $atividade = $result->fetch_assoc();
    $stmt->close();

    if (!$atividade) {
        // Criar atividade exemplo se não existir
        $atividade = criarAtividadeExemplo($unidade_id, $tipo_escolhido);
    } else {
        // Decodificar exercícios
        if ($atividade['exercicios']) {
            $atividade['exercicios'] = json_decode($atividade['exercicios'], true);
        }
    }

    // Registrar escolha do usuário
    registrarEscolhaUsuario($conn, $id_usuario, $unidade_id, $tipo_escolhido);

    echo json_encode([
        'success' => true,
        'atividade' => $atividade,
        'tipo_escolhido' => $tipo_escolhido
    ]);
}

function responderExercicioEspecial($conn, $input) {
    $atividade_id = $input['atividade_id'] ?? null;
    $pergunta_id = $input['pergunta_id'] ?? null;
    $resposta_usuario = $input['resposta_usuario'] ?? null;
    $id_usuario = $_SESSION['id_usuario'];

    if (!$atividade_id || $pergunta_id === null || $resposta_usuario === null) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        return;
    }

    // Buscar atividade e exercícios
    $sql = "SELECT exercicios FROM atividades_especiais WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $atividade_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $atividade = $result->fetch_assoc();
    $stmt->close();

    if (!$atividade) {
        echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
        return;
    }

    $exercicios = json_decode($atividade['exercicios'], true);
    
    if (!isset($exercicios['perguntas'][$pergunta_id])) {
        echo json_encode(['success' => false, 'message' => 'Pergunta não encontrada']);
        return;
    }

    $pergunta = $exercicios['perguntas'][$pergunta_id];
    $resposta_correta = $pergunta['correta'];
    $correto = ($resposta_usuario == $resposta_correta);

    // Salvar resposta do usuário
    salvarRespostaEspecial($conn, $id_usuario, $atividade_id, $pergunta_id, $resposta_usuario, $correto);

    // Preparar feedback
    $feedback = [
        'correto' => $correto,
        'resposta_correta' => $resposta_correta,
        'explicacao' => $pergunta['explicacao'] ?? '',
        'alternativa_correta' => $pergunta['alternativas'][$resposta_correta] ?? ''
    ];

    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
}

function criarAtividadesExemplo($unidade_id, $tipo = null) {
    $atividades = [];
    
    if (!$tipo || $tipo === 'musica') {
        $atividades[] = [
            'id' => 'musica_exemplo_' . $unidade_id,
            'nome' => 'Música: Hello - Adele',
            'tipo' => 'musica',
            'conteudo_url' => null,
            'conteudo_texto' => "Hello, it's me\nI was wondering if after all these years\nYou'd like to meet, to go over everything\nThey say that time's supposed to heal ya\nBut I ain't done much healing",
            'exercicios' => [
                'perguntas' => [
                    [
                        'pergunta' => 'Qual palavra significa "olá" na música?',
                        'alternativas' => ['Hello', 'Meet', 'Time', 'Healing'],
                        'correta' => 0,
                        'explicacao' => 'Hello é a primeira palavra da música e significa olá em português.'
                    ],
                    [
                        'pergunta' => 'Complete: "Hello, it\'s ____"',
                        'alternativas' => ['you', 'me', 'us', 'them'],
                        'correta' => 1,
                        'explicacao' => 'A frase correta é "Hello, it\'s me" (Olá, sou eu).'
                    ],
                    [
                        'pergunta' => 'O que significa "after all these years"?',
                        'alternativas' => ['Depois de todos esses anos', 'Antes de todos esses anos', 'Durante todos esses anos', 'Sem todos esses anos'],
                        'correta' => 0,
                        'explicacao' => '"After all these years" significa "depois de todos esses anos".'
                    ]
                ]
            ],
            'ordem' => 1
        ];
    }
    
    if (!$tipo || $tipo === 'filme_serie') {
        $atividades[] = [
            'id' => 'filme_exemplo_' . $unidade_id,
            'nome' => 'Série: Friends - Apresentações',
            'tipo' => 'filme_serie',
            'conteudo_url' => null,
            'conteudo_texto' => "Ross: Hi, I'm Ross.\nRachel: Nice to meet you, I'm Rachel.\nRoss: So, what do you do?\nRachel: I work at a coffee shop. And you?\nRoss: I'm a paleontologist.",
            'exercicios' => [
                'perguntas' => [
                    [
                        'pergunta' => 'Como Ross se apresenta?',
                        'alternativas' => ['Hi, I\'m Ross', 'Hello Ross', 'My name Ross', 'I Ross'],
                        'correta' => 0,
                        'explicacao' => 'Ross se apresenta dizendo "Hi, I\'m Ross" (Oi, eu sou o Ross).'
                    ],
                    [
                        'pergunta' => 'Onde Rachel trabalha?',
                        'alternativas' => ['Restaurant', 'Coffee shop', 'Museum', 'School'],
                        'correta' => 1,
                        'explicacao' => 'Rachel diz "I work at a coffee shop" (Eu trabalho em uma cafeteria).'
                    ],
                    [
                        'pergunta' => 'Qual é a profissão de Ross?',
                        'alternativas' => ['Doctor', 'Teacher', 'Paleontologist', 'Engineer'],
                        'correta' => 2,
                        'explicacao' => 'Ross diz "I\'m a paleontologist" (Eu sou paleontólogo).'
                    ]
                ]
            ],
            'ordem' => 2
        ];
    }
    
    return $atividades;
}

function criarAtividadeExemplo($unidade_id, $tipo) {
    $atividades = criarAtividadesExemplo($unidade_id, $tipo);
    return $atividades[0] ?? null;
}

function registrarEscolhaUsuario($conn, $id_usuario, $unidade_id, $tipo_escolhido) {
    $sql = "
        INSERT INTO progresso_detalhado 
        (id_usuario, exercicio_id, concluido, data_conclusao) 
        VALUES (?, ?, 0, NOW())
        ON DUPLICATE KEY UPDATE data_conclusao = NOW()
    ";
    
    // Usar ID negativo para atividades especiais para não conflitar com exercícios normais
    $exercicio_especial_id = -($unidade_id * 100 + ($tipo_escolhido === 'musica' ? 1 : 2));
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $exercicio_especial_id);
    $stmt->execute();
    $stmt->close();
}

function salvarRespostaEspecial($conn, $id_usuario, $atividade_id, $pergunta_id, $resposta_usuario, $correto) {
    $sql = "
        INSERT INTO feedback_escrita 
        (exercicio_id, usuario_id, resposta_usuario, pontuacao_escrita, erros_encontrados, sugestoes_melhoria, data_resposta) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ";
    
    // Usar ID negativo baseado na atividade especial
    $exercicio_especial_id = -($atividade_id * 1000 + $pergunta_id);
    $pontuacao = $correto ? 1.0 : 0.0;
    $erros = $correto ? '[]' : '[{"tipo": "resposta_incorreta", "descricao": "Resposta incorreta"}]';
    $sugestoes = $correto ? 'Resposta correta!' : 'Revise o conteúdo e tente novamente.';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisdss", $exercicio_especial_id, $id_usuario, $resposta_usuario, $pontuacao, $erros, $sugestoes);
    $stmt->execute();
    $stmt->close();
}
?>

