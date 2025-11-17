<?php
// get_exercicio.php - VERSÃO CORRIGIDA PARA EXERCÍCIOS DE FALA
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
                e.categoria,
                e.dificuldade,
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
                e.categoria,
                e.dificuldade,
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

// FUNÇÃO CORRIGIDA PARA DETERMINAR TIPO DE EXERCÍCIO
function determinarTipoExercicio($row, $conteudo) {
    // PRIORIDADE 1: Verificar se existe 'tipo_exercicio' no conteúdo JSON
    if (is_array($conteudo) && isset($conteudo['tipo_exercicio'])) {
        $tipo_conteudo = strtolower(trim($conteudo['tipo_exercicio']));
        
        // Mapear tipos do conteúdo
        $mapeamento_conteudo = [
            'listening' => 'listening',
            'fala' => 'fala', 
            'speech' => 'fala',
            'multipla_escolha' => 'multipla_escolha',
            'multiple_choice' => 'multipla_escolha',
            'texto_livre' => 'texto_livre',
            'completar' => 'completar'
        ];
        
        if (isset($mapeamento_conteudo[$tipo_conteudo])) {
            return $mapeamento_conteudo[$tipo_conteudo];
        }
    }
    
    // PRIORIDADE 2: Usar o campo 'categoria' se não for 'gramatica'
    if (!empty($row['categoria']) && $row['categoria'] !== 'gramatica') {
        $mapeamento_categoria = [
            'fala' => 'fala',
            'audicao' => 'listening', 
            'escrita' => 'texto_livre',
            'leitura' => 'texto_livre'
        ];
        
        if (isset($mapeamento_categoria[$row['categoria']])) {
            return $mapeamento_categoria[$row['categoria']];
        }
    }
    
    // PRIORIDADE 3: Analisar o conteúdo para determinar o tipo
    if (is_array($conteudo)) {
        // Verificar se é exercício de completar PRIMEIRO
        if (isset($conteudo['frase_completar']) || 
            (isset($conteudo['tipo_exercicio']) && $conteudo['tipo_exercicio'] === 'completar')) {
            return 'completar';
        }
        // Verificar se é exercício de listening (apenas se tem áudio)
        elseif (isset($conteudo['audio_url']) || isset($conteudo['arquivo_audio'])) {
            return 'listening';
        }
        // Verificar se é exercício de fala
        elseif (isset($conteudo['frase_esperada']) || isset($conteudo['texto_para_falar']) ||
                isset($conteudo['frase'])) {
            return 'fala';
        }
        // Verificar se é múltipla escolha
        elseif (isset($conteudo['alternativas']) && is_array($conteudo['alternativas'])) {
            return 'multipla_escolha';
        }
        // Se tem opções mas não é listening, pode ser completar
        elseif (isset($conteudo['opcoes']) && isset($conteudo['resposta_correta'])) {
            // Verificar se tem contexto de completar
            if (isset($conteudo['pergunta']) && 
                (strpos(strtolower($conteudo['pergunta']), 'complete') !== false ||
                 strpos(strtolower($conteudo['pergunta']), 'completar') !== false)) {
                return 'completar';
            }
            return 'multipla_escolha'; // Default para opções
        }
    }
    
    // FALLBACK: padrão baseado na categoria ou tipo
    if ($row['categoria'] === 'gramatica' || $row['tipo'] === 'normal') {
        return 'multipla_escolha';
    }
    
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