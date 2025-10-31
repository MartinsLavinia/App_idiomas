<?php
// get_exercicio.php - VERSÃO CORRIGIDA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Aceitar tanto atividade_id quanto bloco_id
$id_busca = null;
$tipo_busca = '';

if (isset($_GET['bloco_id']) && is_numeric($_GET['bloco_id'])) {
    $id_busca = (int)$_GET['bloco_id'];
    $tipo_busca = 'bloco';
} elseif (isset($_GET['atividade_id']) && is_numeric($_GET['atividade_id'])) {
    $id_busca = (int)$_GET['atividade_id'];
    $tipo_busca = 'atividade';
} else {
    echo json_encode(['success' => false, 'message' => 'ID do bloco ou atividade inválido']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    $exercicios = [];
    $idioma_exercicio = null;

    if ($tipo_busca === 'bloco') {
        // Buscar exercícios por bloco_id - QUERY CORRIGIDA
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo, 
                e.tipo AS tipo_exercicio,
                e.caminho_id,
                e.bloco_id,
                u.idioma AS idioma_exercicio
            FROM exercicios e
            JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
            JOIN unidades u ON c.id_unidade = u.id
            WHERE e.bloco_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    } else {
        // Buscar exercícios por caminho_id (atividade)
        $sql = "
            SELECT 
                e.id,
                e.ordem,
                e.tipo,
                e.pergunta,
                e.conteudo, 
                e.tipo AS tipo_exercicio,
                e.caminho_id,
                e.bloco_id,
                u.idioma AS idioma_exercicio
            FROM exercicios e
            JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
            JOIN unidades u ON c.id_unidade = u.id
            WHERE e.caminho_id = ?
            ORDER BY e.ordem ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_busca);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Obter o idioma do primeiro exercício encontrado
        if ($idioma_exercicio === null) {
            $idioma_exercicio = $row['idioma_exercicio'];
        }
        unset($row['idioma_exercicio']);
        
        // CORREÇÃO PRINCIPAL: Processar o conteúdo de forma mais robusta
        $row = processarConteudoExercicio($row, $idioma_exercicio);
        
        $exercicios[] = $row;
    }
    
    $stmt->close();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'tipo_busca' => $tipo_busca,
        'id_busca' => $id_busca,
        'total_exercicios' => count($exercicios),
        'idioma' => $idioma_exercicio,
        'exercicios' => $exercicios
    ]);

} catch (Exception $e) {
    error_log("Erro em get_exercicio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// FUNÇÃO NOVA PARA PROCESSAR CONTEÚDO DOS EXERCÍCIOS
function processarConteudoExercicio($row, $idioma_exercicio) {
    $conteudo = $row['conteudo'];
    
    // Se o conteúdo for uma string JSON, decodificar
    if ($conteudo && is_string($conteudo)) {
        try {
            $conteudo_decodificado = json_decode($conteudo, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row['conteudo'] = $conteudo_decodificado;
                $conteudo = $conteudo_decodificado;
            }
        } catch (Exception $e) {
            error_log("Erro ao decodificar JSON do exercício {$row['id']}: " . $e->getMessage());
        }
    }
    
    // DETERMINAR TIPO DE EXERCÍCIO DE FORMA MAIS PRECISA
    $tipo_determinado = determinarTipoExercicio($row, $conteudo);
    $row['tipo_exercicio'] = $tipo_determinado;
    
    // CONFIGURAR EXERCÍCIOS DE FALA ESPECIFICAMENTE
    if ($tipo_determinado === 'fala') {
        $row['conteudo'] = configurarExercicioFala($row['conteudo'], $idioma_exercicio);
    }
    
    // CONFIGURAR EXERCÍCIOS DE LISTENING
    if ($tipo_determinado === 'listening') {
        $row['conteudo'] = configurarExercicioListening($row['conteudo']);
    }
    
    return $row;
}

// FUNÇÃO PARA DETERMINAR TIPO DE EXERCÍCIO
function determinarTipoExercicio($row, $conteudo) {
    // Primeiro, verificar se já tem um tipo definido
    if (!empty($row['tipo_exercicio'])) {
        return $row['tipo_exercicio'];
    }
    
    // Se não tem tipo definido, analisar o conteúdo
    if (is_array($conteudo)) {
        if (isset($conteudo['alternativas']) && is_array($conteudo['alternativas'])) {
            return 'multipla_escolha';
        } elseif (isset($conteudo['frase_completar'])) {
            return 'completar';
        } elseif (isset($conteudo['frase_esperada']) || isset($conteudo['texto_para_falar'])) {
            return 'fala';
        } elseif (isset($conteudo['audio_url']) || isset($conteudo['arquivo_audio'])) {
            return 'listening';
        } elseif (isset($conteudo['texto_traduzir'])) {
            return 'traducao';
        } elseif (isset($conteudo['arrastar_soltar'])) {
            return 'arrastar_soltar';
        }
    }
    
    // Fallback para o campo 'tipo' original ou padrão
    return $row['tipo'] ?? 'texto_livre';
}

// FUNÇÃO PARA CONFIGURAR EXERCÍCIOS DE FALA
function configurarExercicioFala($conteudo, $idioma) {
    if (!is_array($conteudo)) {
        $conteudo = [];
    }
    
    // Garantir campos essenciais para fala
    if (!isset($conteudo['texto_para_falar']) && isset($conteudo['frase_esperada'])) {
        $conteudo['texto_para_falar'] = $conteudo['frase_esperada'];
    }
    
    // Configurações padrão para exercícios de fala
    $conteudo['config_fala'] = array_merge([
        'dificuldade' => 'medio',
        'tempo_maximo_gravacao' => 30,
        'tentativas_permitidas' => 3,
        'tolerancia_pronuncia' => 0.7,
        'feedback_imediato' => true,
        'idioma' => $idioma
    ], $conteudo['config_fala'] ?? []);
    
    // Idioma específico para reconhecimento de voz
    $conteudo['idioma_reconhecimento'] = mapIdiomaParaReconhecimento($idioma);
    
    return $conteudo;
}

// FUNÇÃO PARA CONFIGURAR EXERCÍCIOS DE LISTENING
function configurarExercicioListening($conteudo) {
    if (!is_array($conteudo)) {
        $conteudo = [];
    }
    
    // Garantir que opções sejam um array
    if (isset($conteudo['opcoes']) && is_string($conteudo['opcoes'])) {
        $conteudo['opcoes'] = json_decode($conteudo['opcoes'], true) ?? [];
    }
    
    return $conteudo;
}

// MAPEAR IDIOMA PARA CÓDIGO DE RECONHECIMENTO
function mapIdiomaParaReconhecimento($idioma) {
    $mapa = [
        'Ingles' => 'en-US',
        'Inglês' => 'en-US',
        'English' => 'en-US',
        'Japones' => 'ja-JP',
        'Japonês' => 'ja-JP',
        'Japanese' => 'ja-JP',
        'Coreano' => 'ko-KR',
        'Korean' => 'ko-KR',
        'Portugues' => 'pt-BR',
        'Português' => 'pt-BR',
        'Portuguese' => 'pt-BR',
        'Espanhol' => 'es-ES',
        'Spanish' => 'es-ES'
    ];
    
    return $mapa[$idioma] ?? 'en-US';
}
?>