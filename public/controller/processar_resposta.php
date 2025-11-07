<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit();
}

$exercicioId = $_POST['exercicio_id'] ?? null;
$respostaUsuario = $_POST['resposta'] ?? '';

if (!$exercicioId) {
    echo json_encode(['success' => false, 'error' => 'ID do exercício não fornecido']);
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar exercício
$sql = "SELECT * FROM exercicios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exercicioId);
$stmt->execute();
$exercicio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exercicio) {
    echo json_encode(['success' => false, 'error' => 'Exercício não encontrado']);
    $database->closeConnection();
    exit();
}

// Processar resposta baseada no tipo de exercício
$tipoExercicio = $exercicio['tipo'];
$conteudo = json_decode($exercicio['conteudo'], true);
$resultado = ['correto' => false, 'feedback' => '', 'pontuacao' => 0];

switch ($tipoExercicio) {
    case 'fala':
        // Para exercícios de fala, validamos a similaridade da frase
        $fraseEsperada = $conteudo['frase_esperada'] ?? '';
        $similaridade = calcularSimilaridade($respostaUsuario, $fraseEsperada);
        $tolerancia = $conteudo['tolerancia_erro'] ?? 0.7;
        
        $resultado['correto'] = ($similaridade >= $tolerancia);
        $resultado['feedback'] = $resultado['correto'] ? 
            'Pronúncia correta! Similaridade: ' . round($similaridade * 100, 1) . '%' :
            'Pronúncia precisa melhorar. Similaridade: ' . round($similaridade * 100, 1) . '%';
        $resultado['pontuacao'] = $resultado['correto'] ? 10 : 0;
        $resultado['similaridade'] = $similaridade;
        break;
        
    default:
        $resultado['feedback'] = 'Tipo de exercício não suportado: ' . $tipoExercicio;
        break;
}

// Salvar no banco de dados
$sql = "INSERT INTO respostas_exercicios (usuario_id, exercicio_id, resposta, correta, pontuacao, dados_adicionais) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$correta = $resultado['correto'] ? 1 : 0;
$dadosAdicionais = json_encode(['similaridade' => $resultado['similaridade'] ?? 0]);
$stmt->bind_param("iisiis", $_SESSION['id_usuario'], $exercicioId, $respostaUsuario, $correta, $resultado['pontuacao'], $dadosAdicionais);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'correto' => $resultado['correto'],
    'feedback' => $resultado['feedback'],
    'pontuacao' => $resultado['pontuacao']
]);

$database->closeConnection();

// Função para calcular similaridade entre strings
function calcularSimilaridade($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    if ($str1 === $str2) return 1.0;
    
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    $maxLen = max($len1, $len2);
    
    if ($maxLen === 0) return 1.0;
    
    $distance = levenshtein($str1, $str2);
    return 1 - ($distance / $maxLen);
}
?>