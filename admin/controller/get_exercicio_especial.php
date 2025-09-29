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

if (!isset($_GET['bloco_id']) || !is_numeric($_GET['bloco_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do bloco inválido']);
    exit();
}

$bloco_id = (int)$_GET['bloco_id'];
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Buscar exercício especial do bloco
    $sql = "SELECT * FROM exercicios_especiais WHERE id_bloco = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bloco_id);
    $stmt->execute();
    $exercicio_especial = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Se não existe, criar um padrão baseado no tipo de bloco
    if (!$exercicio_especial) {
        $exercicio_especial = criarExercicioEspecialPadrao($conn, $bloco_id);
    }

    // Decodificar JSON fields
    if ($exercicio_especial['opcoes_resposta']) {
        $exercicio_especial['opcoes_resposta'] = json_decode($exercicio_especial['opcoes_resposta'], true);
    }
    if ($exercicio_especial['resposta_correta']) {
        $exercicio_especial['resposta_correta'] = json_decode($exercicio_especial['resposta_correta'], true);
    }

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'exercicio_especial' => $exercicio_especial
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}

function criarExercicioEspecialPadrao($conn, $bloco_id) {
    // Buscar informações do bloco
    $sql_bloco = "SELECT b.*, u.nome_unidade, u.idioma FROM blocos_atividades b 
                  JOIN unidades u ON b.id_unidade = u.id 
                  WHERE b.id = ?";
    $stmt = $conn->prepare($sql_bloco);
    $stmt->bind_param("i", $bloco_id);
    $stmt->execute();
    $bloco_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $tipos_media = ['musica', 'filme', 'anime', 'video'];
    $tipo = $tipos_media[array_rand($tipos_media)];
    
    $exercicios_por_tipo = [
        'musica' => [
            'titulo' => 'Exercício com Música - ' . $bloco_info['nome_unidade'],
            'descricao' => 'Ouça a música e responda às perguntas sobre a letra e vocabulário.',
            'url_media' => 'https://exemplo.com/musica_' . $bloco_info['numero_bloco'] . '.mp3',
            'transcricao' => "Letra da música exemplo...\nPalavras-chave: hello, world, love, time",
            'pergunta' => 'Qual é o tema principal desta música?',
            'tipo_exercicio' => 'multipla_escolha',
            'opcoes_resposta' => json_encode([
                ['id' => 'a', 'texto' => 'Amor e relacionamentos'],
                ['id' => 'b', 'texto' => 'Aventura e viagens'],
                ['id' => 'c', 'texto' => 'Trabalho e carreira'],
                ['id' => 'd', 'texto' => 'Natureza e meio ambiente']
            ]),
            'resposta_correta' => json_encode(['a']),
            'explicacao' => 'A música fala sobre relacionamentos e sentimentos amorosos.'
        ],
        'filme' => [
            'titulo' => 'Cena de Filme - ' . $bloco_info['nome_unidade'],
            'descricao' => 'Assista à cena do filme e complete as falas.',
            'url_media' => 'https://exemplo.com/filme_' . $bloco_info['numero_bloco'] . '.mp4',
            'transcricao' => "Personagem A: Hello, how ___ you?\nPersonagem B: I ___ fine, thank you.",
            'pergunta' => 'Complete as falas da cena:',
            'tipo_exercicio' => 'preencher_lacunas',
            'opcoes_resposta' => json_encode(['are', 'am']),
            'resposta_correta' => json_encode(['are', 'am']),
            'explicacao' => 'A forma correta é "How are you?" e "I am fine".'
        ],
        'anime' => [
            'titulo' => 'Cena de Anime - ' . $bloco_info['nome_unidade'],
            'descricao' => 'Assista à cena do anime e ordene as frases na sequência correta.',
            'url_media' => 'https://exemplo.com/anime_' . $bloco_info['numero_bloco'] . '.mp4',
            'transcricao' => "Olá, meu nome é...\nPrazer em conhecê-lo!\nComo você está?\nEstou bem, obrigado!",
            'pergunta' => 'Ordene as frases na sequência da conversa:',
            'tipo_exercicio' => 'ordenar',
            'opcoes_resposta' => json_encode([
                "Olá, meu nome é...",
                "Prazer em conhecê-lo!",
                "Como você está?",
                "Estou bem, obrigado!"
            ]),
            'resposta_correta' => json_encode([0, 2, 3, 1]),
            'explicacao' => 'A sequência lógica de uma conversa é: apresentação, pergunta sobre bem-estar, resposta, cumprimento final.'
        ]
    ];

    $exercicio = $exercicios_por_tipo[$tipo];
    $exercicio['tipo'] = $tipo;
    $exercicio['id_bloco'] = $bloco_id;
    $exercicio['pontos'] = 20;

    // Inserir no banco
    $sql_inserir = "INSERT INTO exercicios_especiais 
                   (id_bloco, tipo, titulo, descricao, url_media, transcricao, pergunta, tipo_exercicio, opcoes_resposta, resposta_correta, explicacao, pontos) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_inserir = $conn->prepare($sql_inserir);
    $stmt_inserir->bind_param(
        "issssssssssi", 
        $exercicio['id_bloco'], $exercicio['tipo'], $exercicio['titulo'], $exercicio['descricao'],
        $exercicio['url_media'], $exercicio['transcricao'], $exercicio['pergunta'], $exercicio['tipo_exercicio'],
        $exercicio['opcoes_resposta'], $exercicio['resposta_correta'], $exercicio['explicacao'], $exercicio['pontos']
    );
    $stmt_inserir->execute();
    $exercicio['id'] = $stmt_inserir->insert_id;
    $stmt_inserir->close();

    return $exercicio;
}
?>