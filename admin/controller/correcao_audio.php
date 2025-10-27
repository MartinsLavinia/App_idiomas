<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include_once __DIR__ . '/../../conexao.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Ler dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

// Validar dados obrigatórios
$required_fields = ['exercicio_id', 'frase_esperada', 'frase_transcrita', 'idioma'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Campo obrigatório faltando: $field"]);
        exit();
    }
}

$exercicio_id = $data['exercicio_id'];
$frase_esperada = $data['frase_esperada'];
$frase_transcrita = $data['frase_transcrita'];
$idioma = $data['idioma'];
$id_usuario = $_SESSION['id_usuario'];

try {
    // Analisar a pronúncia
    $resultado = analisarPronuncia($frase_esperada, $frase_transcrita, $idioma);
    
    // Registrar a resposta do usuário
    registrarRespostaFala($exercicio_id, $id_usuario, $frase_transcrita, $resultado);
    
    echo json_encode([
        'success' => true,
        'resultado' => $resultado
    ]);

} catch (Exception $e) {
    error_log("Erro em correcao_audio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function analisarPronuncia($frase_esperada, $frase_transcrita, $idioma) {
    // Normalizar as frases para comparação
    $esperada_limpa = trim(strtolower($frase_esperada));
    $transcrita_limpa = trim(strtolower($frase_transcrita));
    
    // Calcular similaridade
    $similaridade = calcularSimilaridadeTexto($esperada_limpa, $transcrita_limpa);
    
    // Determinar status baseado na similaridade
    if ($similaridade >= 0.9) {
        $status = 'correto';
        $pontuacao = 100;
    } elseif ($similaridade >= 0.7) {
        $status = 'meio_correto';
        $pontuacao = round($similaridade * 100);
    } else {
        $status = 'errado';
        $pontuacao = round($similaridade * 100);
    }
    
    // Analisar palavras individuais
    $palavras_analise = analisarPalavrasIndividuais($esperada_limpa, $transcrita_limpa);
    
    // Gerar feedback detalhado
    $feedback_detalhado = gerarFeedbackDetalhado(
        $frase_esperada, 
        $frase_transcrita, 
        $palavras_analise,
        $similaridade
    );
    
    return [
        'status' => $status,
        'pontuacao_percentual' => $pontuacao,
        'similaridade' => $similaridade,
        'feedback_detalhado' => $feedback_detalhado
    ];
}

function calcularSimilaridadeTexto($str1, $str2) {
    if ($str1 === $str2) {
        return 1.0;
    }
    
    // Remover pontuação
    $str1 = preg_replace('/[^\w\s]/', '', $str1);
    $str2 = preg_replace('/[^\w\s]/', '', $str2);
    
    // Se uma string estiver vazia após limpeza
    if (empty($str1) || empty($str2)) {
        return 0.0;
    }
    
    // Calcular usando Levenshtein
    $distancia = levenshtein($str1, $str2);
    $max_length = max(strlen($str1), strlen($str2));
    
    return 1 - ($distancia / $max_length);
}

function analisarPalavrasIndividuais($esperada, $transcrita) {
    $palavras_esperadas = preg_split('/\s+/', $esperada);
    $palavras_transcritas = preg_split('/\s+/', $transcrita);
    
    $palavras_corretas = [];
    $palavras_incorretas = [];
    
    foreach ($palavras_esperadas as $index => $palavra_esperada) {
        $palavra_transcrita = $palavras_transcritas[$index] ?? '';
        
        if (!empty($palavra_transcrita)) {
            $similaridade = calcularSimilaridadeTexto($palavra_esperada, $palavra_transcrita);
            
            if ($similaridade >= 0.8) {
                $palavras_corretas[] = $palavra_esperada;
            } else {
                $palavras_incorretas[] = [
                    'esperada' => $palavra_esperada,
                    'transcrita' => $palavra_transcrita,
                    'similaridade' => $similaridade,
                    'sugestao' => gerarSugestaoPronuncia($palavra_esperada, $palavra_transcrita)
                ];
            }
        }
    }
    
    return [
        'corretas' => $palavras_corretas,
        'incorretas' => $palavras_incorretas
    ];
}

function gerarSugestaoPronuncia($esperada, $transcrita) {
    $sugestoes = [
        "Pratique a pronúncia de '$esperada'",
        "Preste atenção aos sons das vogais e consoantes",
        "Tente falar mais devagar e claramente",
        "Ouça a pronúncia correta e repita"
    ];
    
    return $sugestoes[array_rand($sugestoes)];
}

function gerarFeedbackDetalhado($frase_esperada, $frase_transcrita, $palavras_analise, $similaridade) {
    $sugestoes = [];
    
    if ($similaridade < 0.9) {
        $sugestoes[] = "Tente falar mais claramente e em um ritmo constante";
        $sugestoes[] = "Preste atenção na pronúncia das palavras individuais";
    }
    
    if (count($palavras_analise['incorretas']) > 0) {
        $sugestoes[] = "Foque nas palavras que precisam de melhor pronúncia";
    }
    
    // Explicação baseada no desempenho
    if ($similaridade >= 0.9) {
        $explicacao = "Excelente pronúncia! Você falou a frase corretamente.";
    } elseif ($similaridade >= 0.7) {
        $explicacao = "Boa pronúncia! Com um pouco mais de prática você melhora.";
    } else {
        $explicacao = "Continue praticando! Foque na pronúncia das palavras-chave.";
    }
    
    return [
        'frase_esperada' => $frase_esperada,
        'frase_transcrita' => $frase_transcrita,
        'palavras_corretas' => $palavras_analise['corretas'],
        'palavras_incorretas' => $palavras_analise['incorretas'],
        'sugestoes' => $sugestoes,
        'explicacao' => $explicacao
    ];
}

function registrarRespostaFala($exercicio_id, $usuario_id, $transcricao, $resultado) {
    try {
        $database = new Database();
        $conn = $database->conn;
        
        $acertou = ($resultado['status'] === 'correto') ? 1 : 0;
        $pontuacao = $resultado['pontuacao_percentual'];
        
        $sql = "INSERT INTO site_idiomas_respostas_exercicios 
                (id_usuario, exercicio_id, acertou, resposta_usuario, pontuacao, data_resposta) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisi", $usuario_id, $exercicio_id, $acertou, $transcricao, $pontuacao);
        $stmt->execute();
        $stmt->close();
        
        $database->closeConnection();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao registrar resposta de fala: " . $e->getMessage());
        return false;
    }
}
?>