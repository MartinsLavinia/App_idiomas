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

    // Buscar informações do bloco
    $sql_bloco = "SELECT b.*, u.nome_unidade, u.idioma, u.nivel 
                  FROM blocos_atividades b
                  JOIN unidades u ON b.id_unidade = u.id
                  WHERE b.id = ?";
    $stmt_bloco = $conn->prepare($sql_bloco);
    $stmt_bloco->bind_param("i", $bloco_id);
    $stmt_bloco->execute();
    $bloco_info = $stmt_bloco->get_result()->fetch_assoc();
    $stmt_bloco->close();

    if (!$bloco_info) {
        echo json_encode(['success' => false, 'message' => 'Bloco não encontrado']);
        exit();
    }

    // Buscar atividades para este bloco (usando caminhos existentes)
    $sql_atividades = "
        SELECT 
            e.id,
            e.ordem,
            e.tipo,
            e.pergunta,
            e.conteudo,
            CASE 
                WHEN e.ordem <= 6 THEN 'gramatica'
                WHEN e.ordem <= 9 THEN 'fala' 
                ELSE 'dificil'
            END as categoria,
            CASE 
                WHEN e.ordem <= 6 THEN 'Gramática'
                WHEN e.ordem <= 9 THEN 'Fala'
                ELSE 'Desafio'
            END as categoria_nome,
            c.nome_caminho
        FROM exercicios e
        JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
        WHERE c.id_unidade = ?
        ORDER BY e.ordem
        LIMIT 12
    ";

    $stmt_atividades = $conn->prepare($sql_atividades);
    $stmt_atividades->bind_param("i", $bloco_info['id_unidade']);
    $stmt_atividades->execute();
    $atividades = $stmt_atividades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_atividades->close();

    // Se não há atividades suficientes, criar algumas padrão
    if (count($atividades) < 12) {
        $atividades = gerarAtividadesPadrao($bloco_info);
    }

    // Buscar progresso atual do usuário neste bloco
    $sql_progresso = "SELECT * FROM progresso_bloco WHERE id_usuario = ? AND id_bloco = ?";
    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param("ii", $id_usuario, $bloco_id);
    $stmt_progresso->execute();
    $progresso = $stmt_progresso->get_result()->fetch_assoc();
    $stmt_progresso->close();

    // Se não existe progresso, criar
    if (!$progresso) {
        $sql_novo_progresso = "INSERT INTO progresso_bloco (id_usuario, id_bloco, total_atividades) VALUES (?, ?, ?)";
        $stmt_novo = $conn->prepare($sql_novo_progresso);
        $stmt_novo->bind_param("iii", $id_usuario, $bloco_id, count($atividades));
        $stmt_novo->execute();
        $stmt_novo->close();
        
        $progresso = [
            'atividades_concluidas' => 0,
            'total_atividades' => count($atividades),
            'progresso_percentual' => 0,
            'concluido' => false
        ];
    }

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'bloco' => $bloco_info,
        'atividades' => $atividades,
        'progresso' => $progresso,
        'distribuicao' => [
            'gramatica' => 6,
            'fala' => 3,
            'dificeis' => 3
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}

function gerarAtividadesPadrao($bloco_info) {
    $atividades = [];
    $tipos_gramatica = ['multipla_escolha', 'texto_livre', 'completar'];
    $tipos_fala = ['fala'];
    $tipos_dificeis = ['audicao', 'texto_livre_complexo'];
    
    // 6 atividades de gramática
    for ($i = 1; $i <= 6; $i++) {
        $atividades[] = [
            'id' => 'gramatica_' . $i,
            'ordem' => $i,
            'tipo' => $tipos_gramatica[array_rand($tipos_gramatica)],
            'categoria' => 'gramatica',
            'categoria_nome' => 'Gramática',
            'pergunta' => 'Atividade de Gramática ' . $i . ' - ' . $bloco_info['nome_unidade'],
            'conteudo' => json_encode(['tipo' => 'gramatica', 'dificuldade' => 'medio']),
            'nome_caminho' => $bloco_info['nome_unidade']
        ];
    }
    
    // 3 atividades de fala
    for ($i = 7; $i <= 9; $i++) {
        $atividades[] = [
            'id' => 'fala_' . ($i-6),
            'ordem' => $i,
            'tipo' => 'fala',
            'categoria' => 'fala',
            'categoria_nome' => 'Fala',
            'pergunta' => 'Atividade de Fala ' . ($i-6) . ' - ' . $bloco_info['nome_unidade'],
            'conteudo' => json_encode(['tipo' => 'fala', 'dificuldade' => 'medio']),
            'nome_caminho' => $bloco_info['nome_unidade']
        ];
    }
    
    // 3 atividades difíceis
    for ($i = 10; $i <= 12; $i++) {
        $atividades[] = [
            'id' => 'dificil_' . ($i-9),
            'ordem' => $i,
            'tipo' => $tipos_dificeis[array_rand($tipos_dificeis)],
            'categoria' => 'dificil',
            'categoria_nome' => 'Desafio',
            'pergunta' => 'Atividade Desafio ' . ($i-9) . ' - ' . $bloco_info['nome_unidade'],
            'conteudo' => json_encode(['tipo' => 'desafio', 'dificuldade' => 'dificil']),
            'nome_caminho' => $bloco_info['nome_unidade']
        ];
    }
    
    return $atividades;
}
?>