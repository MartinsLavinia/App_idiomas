<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
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

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Ler dados JSON da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

// Validar dados obrigatórios
if (!isset($data['exercicio_id']) || !isset($data['resposta']) || !isset($data['tipo_exercicio'])) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios ausentes']);
    exit();
}

$exercicio_id = $data['exercicio_id'];
$resposta_usuario = $data['resposta'];
$tipo_exercicio = $data['tipo_exercicio'];
$id_usuario = $_SESSION['id_usuario'];

try {
    // Para exercícios demo (IDs string), simular processamento
    if (is_string($exercicio_id) && strpos($exercicio_id, 'demo_') === 0) {
        $resultado = processarExercicioDemo($exercicio_id, $resposta_usuario, $tipo_exercicio);
        echo json_encode($resultado);
        exit();
    }

    $database = new Database();
    $conn = $database->conn;

    // Buscar dados do exercício
    $sql = "SELECT conteudo, tipo_exercicio FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicio_id);
    $stmt->execute();
    $exercicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exercicio) {
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado']);
        exit();
    }

    // Processar resposta baseado no tipo
    $conteudo = json_decode($exercicio['conteudo'], true);
    $resultado = processarResposta($resposta_usuario, $conteudo, $tipo_exercicio);

    // Registrar progresso do usuário
    if ($resultado['correto']) {
        registrarProgresso($conn, $id_usuario, $exercicio_id, true, $resultado['pontuacao'] ?? 100);
    } else {
        registrarProgresso($conn, $id_usuario, $exercicio_id, false, $resultado['pontuacao'] ?? 0);
    }

    $database->closeConnection();

    echo json_encode($resultado);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function processarExercicioDemo($exercicio_id, $resposta_usuario, $tipo_exercicio) {
    // Respostas corretas para exercícios demo
    $respostas_demo = [
        'demo_1' => ['resposta' => 'A', 'explicacao' => 'Hello é a forma mais comum de cumprimentar alguém em inglês.', 'dica' => 'É a primeira palavra que aprendemos em qualquer idioma.'],
        'demo_2' => ['resposta' => 'is', 'explicacao' => 'Usamos o verbo to be (is) com a terceira pessoa do singular.', 'dica' => 'Lembre-se da conjugação do verbo to be.'],
        'demo_3' => ['resposta' => 'fala_processada', 'explicacao' => 'Excelente pronúncia!', 'dica' => 'Continue praticando para melhorar ainda mais.'],
        'demo_4' => ['resposta' => 'A', 'explicacao' => 'Good morning é usado para cumprimentar alguém pela manhã.', 'dica' => 'Morning significa manhã em inglês.'],
        'demo_5' => ['resposta' => 'My name is', 'explicacao' => 'Esta é a forma mais comum de se apresentar em inglês.', 'dica' => 'Comece com "My name is" seguido do seu nome.'],
        'demo_6' => ['resposta' => 'A', 'explicacao' => 'How are you? é a pergunta padrão para saber como alguém está.', 'dica' => 'How significa "como" em inglês.'],
        'demo_7' => ['resposta' => 'fala_processada', 'explicacao' => 'Muito bem! Sua pronúncia está melhorando.', 'dica' => 'Pratique diariamente para melhores resultados.'],
        'demo_8' => ['resposta' => 'am', 'explicacao' => 'Usamos "am" com o pronome "I" (eu).', 'dica' => 'I + am = I am (ou I\'m)'],
        'demo_9' => ['resposta' => 'A', 'explicacao' => 'You\'re welcome é a resposta padrão para "Thank you".', 'dica' => 'É uma expressão de cortesia para aceitar agradecimentos.'],
        'demo_10' => ['resposta' => 'fala_processada', 'explicacao' => 'Perfeito! Você conseguiu fazer uma apresentação completa.', 'dica' => 'Agora você pode se apresentar em inglês!'],
        'demo_11' => ['resposta' => 'Hello', 'explicacao' => 'Uma apresentação completa inclui saudação, nome, origem e pergunta sobre o outro.', 'dica' => 'Combine as frases que você aprendeu nos exercícios anteriores.'],
        'demo_12' => ['resposta' => 'A', 'explicacao' => 'A frase correta seria "My name IS John", não "My name AM John".', 'dica' => 'Lembre-se: I am, You are, He/She/It is, My name is.']
    ];

    $resposta_correta = $respostas_demo[$exercicio_id] ?? null;
    
    if (!$resposta_correta) {
        return ['correto' => false, 'explicacao' => 'Exercício não encontrado.'];
    }

    $correto = false;
    
    switch ($tipo_exercicio) {
        case 'multipla_escolha':
            $correto = strtoupper($resposta_usuario) === strtoupper($resposta_correta['resposta']);
            break;
        case 'texto_livre':
            $correto = stripos($resposta_usuario, $resposta_correta['resposta']) !== false;
            break;
        case 'fala':
            $correto = $resposta_usuario === 'fala_processada';
            break;
    }

    return [
        'correto' => $correto,
        'explicacao' => $resposta_correta['explicacao'],
        'dica' => $correto ? null : $resposta_correta['dica'],
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarResposta($resposta_usuario, $conteudo, $tipo_exercicio) {
    switch ($tipo_exercicio) {
        case 'multipla_escolha':
            return processarMultiplaEscolha($resposta_usuario, $conteudo);
        case 'texto_livre':
            return processarTextoLivre($resposta_usuario, $conteudo);
        case 'fala':
            return processarFala($resposta_usuario, $conteudo);
        case 'arrastar_soltar':
            return processarArrastarSoltar($resposta_usuario, $conteudo);
        default:
            return ['correto' => false, 'explicacao' => 'Tipo de exercício não suportado.'];
    }
}

function processarMultiplaEscolha($resposta_usuario, $conteudo) {
    if (!isset($conteudo['alternativas'])) {
        return ['correto' => false, 'explicacao' => 'Exercício mal configurado.'];
    }

    $resposta_correta = null;
    foreach ($conteudo['alternativas'] as $alt) {
        if (isset($alt['correta']) && $alt['correta']) {
            $resposta_correta = $alt['id'] ?? $alt['texto'];
            break;
        }
    }

    if (!$resposta_correta && isset($conteudo['resposta_correta'])) {
        $resposta_correta = $conteudo['resposta_correta'];
    }

    $correto = strtolower($resposta_usuario) === strtolower($resposta_correta);

    return [
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : 'Resposta incorreta.'),
        'dica' => $correto ? null : ($conteudo['dica'] ?? null),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarTextoLivre($resposta_usuario, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? '';
    $alternativas_aceitas = $conteudo['alternativas_aceitas'] ?? [$resposta_correta];

    $correto = false;
    foreach ($alternativas_aceitas as $alternativa) {
        if (stripos($resposta_usuario, $alternativa) !== false) {
            $correto = true;
            break;
        }
    }

    return [
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : 'Resposta incorreta.'),
        'dica' => $correto ? null : ($conteudo['dica'] ?? null),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarFala($resposta_usuario, $conteudo) {
    // Simulação de processamento de fala
    // Em uma implementação real, aqui seria feita a análise do áudio
    $correto = $resposta_usuario === 'fala_processada';

    return [
        'correto' => $correto,
        'explicacao' => $correto ? 'Excelente pronúncia!' : 'Tente novamente, prestando atenção à pronúncia.',
        'dica' => $correto ? null : 'Ouça o áudio de exemplo e pratique devagar.',
        'pontuacao' => $correto ? 100 : 0
    ];
}

function processarArrastarSoltar($resposta_usuario, $conteudo) {
    $resposta_correta = $conteudo['resposta_correta'] ?? [];
    
    // Comparar arrays de resposta
    $correto = json_encode($resposta_usuario) === json_encode($resposta_correta);

    return [
        'correto' => $correto,
        'explicacao' => $conteudo['explicacao'] ?? ($correto ? 'Correto!' : 'Verifique as posições dos elementos.'),
        'dica' => $correto ? null : ($conteudo['dica'] ?? 'Leia as categorias com atenção.'),
        'pontuacao' => $correto ? 100 : 0
    ];
}

function registrarProgresso($conn, $id_usuario, $exercicio_id, $acertou, $pontuacao) {
    // Verificar se já existe progresso para este exercício
    $sql_check = "SELECT id, tentativas, acertos FROM progresso_detalhado WHERE id_usuario = ? AND exercicio_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_usuario, $exercicio_id);
    $stmt_check->execute();
    $progresso_existente = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($progresso_existente) {
        // Atualizar progresso existente
        $novas_tentativas = $progresso_existente['tentativas'] + 1;
        $novos_acertos = $progresso_existente['acertos'] + ($acertou ? 1 : 0);
        $data_conclusao = $acertou ? 'NOW()' : 'NULL';

        $sql_update = "UPDATE progresso_detalhado SET tentativas = ?, acertos = ?, pontuacao = ?, data_conclusao = $data_conclusao WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iidi", $novas_tentativas, $novos_acertos, $pontuacao, $progresso_existente['id']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Criar novo registro de progresso
        $tentativas = 1;
        $acertos = $acertou ? 1 : 0;
        $data_conclusao = $acertou ? 'NOW()' : 'NULL';

        $sql_insert = "INSERT INTO progresso_detalhado (id_usuario, exercicio_id, tentativas, acertos, pontuacao, data_conclusao) VALUES (?, ?, ?, ?, ?, $data_conclusao)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiiid", $id_usuario, $exercicio_id, $tentativas, $acertos, $pontuacao);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>