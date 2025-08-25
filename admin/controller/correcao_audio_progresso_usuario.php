<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar se os dados necessários foram enviados
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['exercicio_id']) || !isset($input['audio_data']) || !isset($input['frase_esperada'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$exercicio_id = (int)$input['exercicio_id'];
$audio_data = $input['audio_data']; // Base64 encoded audio
$frase_esperada = trim($input['frase_esperada']);
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    // Analisar áudio com sistema melhorado
    $resultado_analise = analisarAudioMelhorado($audio_data, $frase_esperada);
    
    // Salvar resultado na tabela de feedback de áudio
    salvarFeedbackAudio($conn, $exercicio_id, $id_usuario, $resultado_analise);
    
    // Atualizar progresso do usuário
    atualizarProgressoAudio($conn, $exercicio_id, $id_usuario, $resultado_analise);
    
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'resultado' => $resultado_analise
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function analisarAudioMelhorado($audio_data, $frase_esperada) {
    // Sistema melhorado de análise de áudio
    $frase_esperada_limpa = limparTexto($frase_esperada);
    
    // Simular transcrição mais realista
    $transcricao_simulada = simularTranscricaoRealista($frase_esperada_limpa);
    
    // Calcular pontuação com múltiplos critérios
    $analise_detalhada = analisarPronunciaDetalhada($frase_esperada_limpa, $transcricao_simulada);
    
    // Determinar status baseado na análise
    $status = determinarStatusMelhorado($analise_detalhada['pontuacao_geral']);
    
    // Gerar feedback completo
    $feedback_detalhado = gerarFeedbackCompleto($frase_esperada_limpa, $transcricao_simulada, $analise_detalhada, $status);
    
    return [
        'transcricao' => $transcricao_simulada,
        'pontuacao' => $analise_detalhada['pontuacao_geral'],
        'pontuacao_percentual' => round($analise_detalhada['pontuacao_geral'] * 100),
        'status' => $status,
        'feedback_detalhado' => $feedback_detalhado,
        'analise_detalhada' => $analise_detalhada
    ];
}

function limparTexto($texto) {
    // Remover pontuação e normalizar
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^\w\s]/', '', $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

function simularTranscricaoRealista($frase_esperada) {
    // Base de dados de pronúncias comuns e erros típicos
    $padroes_pronuncia = [
        // Palavras básicas
        'hello' => ['hello', 'helo', 'hallo', 'ello'],
        'good' => ['good', 'gud', 'goot', 'goud'],
        'morning' => ['morning', 'mornin', 'morming', 'moning'],
        'afternoon' => ['afternoon', 'afternun', 'aftenoon', 'afternoon'],
        'evening' => ['evening', 'evenin', 'ivening', 'evning'],
        'night' => ['night', 'nite', 'naight', 'nigt'],
        'how' => ['how', 'ow', 'hau', 'hou'],
        'are' => ['are', 'ar', 'ere', 'aire'],
        'you' => ['you', 'yu', 'u', 'yoo'],
        'fine' => ['fine', 'fain', 'fin', 'faine'],
        'thank' => ['thank', 'tank', 'dank', 'thenk'],
        'thanks' => ['thanks', 'tanks', 'danks', 'thenks'],
        'please' => ['please', 'pleas', 'plis', 'plese'],
        'sorry' => ['sorry', 'sory', 'sari', 'sorey'],
        'excuse' => ['excuse', 'excus', 'eskuse', 'excyuse'],
        'name' => ['name', 'naim', 'nem', 'neim'],
        'nice' => ['nice', 'nais', 'nis', 'naice'],
        'meet' => ['meet', 'mit', 'meit', 'met'],
        'where' => ['where', 'wer', 'were', 'whare'],
        'what' => ['what', 'wat', 'wot', 'whot'],
        'when' => ['when', 'wen', 'whan', 'whene'],
        'why' => ['why', 'wai', 'wi', 'whay'],
        'who' => ['who', 'hu', 'wo', 'whoo'],
        'yes' => ['yes', 'yas', 'yis', 'yess'],
        'no' => ['no', 'nou', 'noo', 'nau'],
        'okay' => ['okay', 'okey', 'ok', 'oke'],
        'welcome' => ['welcome', 'welcom', 'welkome', 'welcum'],
        'goodbye' => ['goodbye', 'goodbai', 'gudbai', 'goodby'],
        'see' => ['see', 'si', 'se', 'sii'],
        'later' => ['later', 'leter', 'laiter', 'latur'],
        'today' => ['today', 'tudei', 'todei', 'tuday'],
        'tomorrow' => ['tomorrow', 'tomorow', 'tumoro', 'tomoro'],
        'yesterday' => ['yesterday', 'yestardei', 'yesterdei', 'yasterday']
    ];
    
    // Simular diferentes níveis de qualidade de pronúncia
    $qualidade_pronuncia = rand(1, 100);
    
    $palavras = explode(' ', $frase_esperada);
    $palavras_transcritas = [];
    
    foreach ($palavras as $palavra) {
        if (isset($padroes_pronuncia[$palavra])) {
            $variacoes = $padroes_pronuncia[$palavra];
            
            if ($qualidade_pronuncia >= 85) {
                // Pronúncia excelente - 85% chance de acerto
                $palavras_transcritas[] = rand(1, 100) <= 85 ? $variacoes[0] : $variacoes[1];
            } elseif ($qualidade_pronuncia >= 65) {
                // Pronúncia boa - 65% chance de acerto
                $palavras_transcritas[] = rand(1, 100) <= 65 ? $variacoes[0] : $variacoes[rand(1, 2)];
            } elseif ($qualidade_pronuncia >= 40) {
                // Pronúncia média - 40% chance de acerto
                $palavras_transcritas[] = rand(1, 100) <= 40 ? $variacoes[0] : $variacoes[rand(1, 3)];
            } else {
                // Pronúncia ruim - 20% chance de acerto
                $palavras_transcritas[] = rand(1, 100) <= 20 ? $variacoes[0] : $variacoes[rand(2, 3)];
            }
        } else {
            // Para palavras não mapeadas, simular erros baseados em padrões comuns
            $palavras_transcritas[] = simularErrosPalavra($palavra, $qualidade_pronuncia);
        }
    }
    
    return implode(' ', $palavras_transcritas);
}

function simularErrosPalavra($palavra, $qualidade) {
    $erros_comuns = [
        'th' => ['d', 'z', 't'],
        'r' => ['w', 'l'],
        'v' => ['b', 'f'],
        'w' => ['v', 'u'],
        'ing' => ['in', 'eng'],
        'ed' => ['d', 'id', 't'],
        'ch' => ['sh', 'tch'],
        'sh' => ['ch', 's'],
        'oo' => ['u', 'o'],
        'ee' => ['i', 'e'],
        'ai' => ['ei', 'a'],
        'ou' => ['au', 'o']
    ];
    
    if ($qualidade >= 80) {
        return $palavra; // Sem erros
    }
    
    $palavra_com_erros = $palavra;
    
    foreach ($erros_comuns as $som_correto => $erros_possiveis) {
        if (strpos($palavra_com_erros, $som_correto) !== false) {
            $chance_erro = 100 - $qualidade;
            if (rand(1, 100) <= $chance_erro) {
                $erro_escolhido = $erros_possiveis[array_rand($erros_possiveis)];
                $palavra_com_erros = str_replace($som_correto, $erro_escolhido, $palavra_com_erros);
            }
        }
    }
    
    return $palavra_com_erros;
}

function analisarPronunciaDetalhada($esperada, $transcrita) {
    $palavras_esperadas = explode(' ', $esperada);
    $palavras_transcritas = explode(' ', $transcrita);
    
    $analise = [
        'palavras_corretas' => 0,
        'palavras_incorretas' => 0,
        'palavras_total' => count($palavras_esperadas),
        'similaridade_geral' => 0,
        'pontuacao_palavras' => 0,
        'pontuacao_similaridade' => 0,
        'pontuacao_geral' => 0,
        'detalhes_palavras' => []
    ];
    
    // Analisar cada palavra
    for ($i = 0; $i < count($palavras_esperadas); $i++) {
        $palavra_esperada = $palavras_esperadas[$i];
        $palavra_transcrita = isset($palavras_transcritas[$i]) ? $palavras_transcritas[$i] : '';
        
        $similaridade_palavra = calcularSimilaridadePalavra($palavra_esperada, $palavra_transcrita);
        
        $analise['detalhes_palavras'][] = [
            'esperada' => $palavra_esperada,
            'transcrita' => $palavra_transcrita,
            'similaridade' => $similaridade_palavra,
            'correta' => $similaridade_palavra >= 0.8
        ];
        
        if ($similaridade_palavra >= 0.8) {
            $analise['palavras_corretas']++;
        } else {
            $analise['palavras_incorretas']++;
        }
    }
    
    // Calcular pontuações
    $analise['pontuacao_palavras'] = $analise['palavras_corretas'] / $analise['palavras_total'];
    $analise['similaridade_geral'] = calcularSimilaridadeTexto($esperada, $transcrita);
    $analise['pontuacao_similaridade'] = $analise['similaridade_geral'];
    
    // Pontuação geral (média ponderada)
    $analise['pontuacao_geral'] = ($analise['pontuacao_palavras'] * 0.6) + ($analise['pontuacao_similaridade'] * 0.4);
    
    return $analise;
}

function calcularSimilaridadePalavra($esperada, $transcrita) {
    if (empty($esperada) && empty($transcrita)) {
        return 1.0;
    }
    
    if (empty($esperada) || empty($transcrita)) {
        return 0.0;
    }
    
    // Usar algoritmo de Levenshtein normalizado
    $distancia = levenshtein($esperada, $transcrita);
    $max_length = max(strlen($esperada), strlen($transcrita));
    
    return 1 - ($distancia / $max_length);
}

function calcularSimilaridadeTexto($esperada, $transcrita) {
    // Calcular similaridade usando múltiplos métodos
    $levenshtein = calcularSimilaridadePalavra($esperada, $transcrita);
    
    // Calcular similaridade de palavras em comum
    $palavras_esperadas = explode(' ', $esperada);
    $palavras_transcritas = explode(' ', $transcrita);
    
    $palavras_comuns = array_intersect($palavras_esperadas, $palavras_transcritas);
    $similaridade_palavras = count($palavras_comuns) / max(count($palavras_esperadas), count($palavras_transcritas));
    
    // Média ponderada
    return ($levenshtein * 0.7) + ($similaridade_palavras * 0.3);
}

function determinarStatusMelhorado($pontuacao) {
    if ($pontuacao >= 0.85) {
        return 'correto';
    } elseif ($pontuacao >= 0.65) {
        return 'meio_correto';
    } else {
        return 'errado';
    }
}

function gerarFeedbackCompleto($esperada, $transcrita, $analise, $status) {
    $feedback = [
        'status' => $status,
        'pontuacao_percentual' => round($analise['pontuacao_geral'] * 100),
        'frase_esperada' => $esperada,
        'frase_transcrita' => $transcrita,
        'palavras_corretas' => [],
        'palavras_incorretas' => [],
        'sugestoes' => [],
        'pontos_melhoria' => [],
        'estatisticas' => [
            'palavras_corretas' => $analise['palavras_corretas'],
            'palavras_incorretas' => $analise['palavras_incorretas'],
            'total_palavras' => $analise['palavras_total'],
            'percentual_acerto' => round(($analise['palavras_corretas'] / $analise['palavras_total']) * 100)
        ]
    ];
    
    // Processar detalhes das palavras
    foreach ($analise['detalhes_palavras'] as $detalhe) {
        if ($detalhe['correta']) {
            $feedback['palavras_corretas'][] = $detalhe['esperada'];
        } else {
            $feedback['palavras_incorretas'][] = [
                'esperada' => $detalhe['esperada'],
                'transcrita' => $detalhe['transcrita'],
                'sugestao' => gerarSugestaoPalavraDetalhada($detalhe['esperada'], $detalhe['transcrita'])
            ];
        }
    }
    
    // Gerar sugestões baseadas no status e análise
    $feedback['sugestoes'] = gerarSugestoesPorStatus($status, $analise);
    $feedback['pontos_melhoria'] = gerarPontosMelhoria($status, $analise);
    
    return $feedback;
}

function gerarSugestaoPalavraDetalhada($esperada, $transcrita) {
    $sugestoes_especificas = [
        'hello' => 'Pronuncie "HE-lou" com ênfase no "lou". O "H" deve ser aspirado.',
        'good' => 'Pronuncie "gud" com som de "u" fechado, como em "livro".',
        'morning' => 'Pronuncie "MOR-ning". O "R" deve ser bem marcado.',
        'how' => 'Pronuncie "rau". Comece com som de "R" suave.',
        'are' => 'Pronuncie "ar" com "R" no final, mas não muito forte.',
        'you' => 'Pronuncie "iu". Som de "i" seguido rapidamente de "u".',
        'thank' => 'O "TH" deve ser pronunciado colocando a língua entre os dentes.',
        'thanks' => 'Igual a "thank" + "s". Pratique o som "TH".',
        'name' => 'Pronuncie "neim" com som de "ei" como em "lei".',
        'nice' => 'Pronuncie "nais" com som de "ai" como em "pai".',
        'meet' => 'Pronuncie "mit" com "i" longo, como em "vida".',
        'where' => 'Pronuncie "uer" com "R" suave no final.',
        'what' => 'Pronuncie "uót" com "W" como "u" rápido.',
        'when' => 'Pronuncie "uen" com "W" como "u" rápido.',
        'welcome' => 'Pronuncie "UEL-cam". Divida em duas sílabas.',
        'please' => 'Pronuncie "plis" com "i" longo.',
        'sorry' => 'Pronuncie "SO-ri" com "O" aberto na primeira sílaba.'
    ];
    
    if (isset($sugestoes_especificas[$esperada])) {
        return $sugestoes_especificas[$esperada];
    }
    
    // Sugestão genérica baseada na diferença
    if (strlen($transcrita) < strlen($esperada)) {
        return "Tente pronunciar todas as sílabas de '{$esperada}' de forma mais clara.";
    } elseif (strlen($transcrita) > strlen($esperada)) {
        return "Evite adicionar sons extras. A palavra '{$esperada}' deve ser pronunciada de forma mais concisa.";
    } else {
        return "Pratique a pronúncia de '{$esperada}' prestando atenção aos sons individuais.";
    }
}

function gerarSugestoesPorStatus($status, $analise) {
    $sugestoes = [];
    
    switch ($status) {
        case 'correto':
            $sugestoes[] = 'Excelente pronúncia! Sua fala está muito clara e precisa.';
            $sugestoes[] = 'Continue praticando para manter essa qualidade.';
            if ($analise['pontuacao_geral'] < 0.95) {
                $sugestoes[] = 'Pequenos ajustes podem tornar sua pronúncia ainda mais perfeita.';
            }
            break;
            
        case 'meio_correto':
            $sugestoes[] = 'Boa pronúncia! Você está no caminho certo.';
            $sugestoes[] = 'Tente falar um pouco mais devagar para melhorar a clareza.';
            $sugestoes[] = 'Pratique as palavras mais difíceis separadamente.';
            if ($analise['palavras_incorretas'] > 0) {
                $sugestoes[] = 'Foque especialmente nas palavras que ainda precisam de ajuste.';
            }
            break;
            
        case 'errado':
            $sugestoes[] = 'Continue praticando! A pronúncia melhora com o tempo e dedicação.';
            $sugestoes[] = 'Tente ouvir a pronúncia correta várias vezes antes de repetir.';
            $sugestoes[] = 'Fale mais devagar e articule bem cada palavra.';
            $sugestoes[] = 'Use um espelho para observar o movimento da boca ao falar.';
            $sugestoes[] = 'Pratique uma palavra de cada vez antes de tentar a frase completa.';
            break;
    }
    
    return $sugestoes;
}

function gerarPontosMelhoria($status, $analise) {
    $pontos = [];
    
    if ($status === 'correto') {
        if ($analise['pontuacao_geral'] < 0.95) {
            $pontos[] = 'Pequenos ajustes na entonação podem aperfeiçoar ainda mais sua pronúncia';
        }
        return $pontos;
    }
    
    if ($analise['palavras_incorretas'] > $analise['palavras_corretas']) {
        $pontos[] = 'Trabalhe na pronúncia individual de cada palavra';
        $pontos[] = 'Pratique os sons mais difíceis separadamente';
    }
    
    if ($analise['similaridade_geral'] < 0.5) {
        $pontos[] = 'Foque na articulação clara das consoantes';
        $pontos[] = 'Pratique a abertura correta das vogais';
        $pontos[] = 'Use exercícios de aquecimento vocal antes de praticar';
    }
    
    if ($status === 'errado') {
        $pontos[] = 'Comece praticando palavras isoladas';
        $pontos[] = 'Grave sua própria voz e compare com exemplos';
        $pontos[] = 'Pratique exercícios de respiração para melhorar a fluência';
    }
    
    return $pontos;
}

function salvarFeedbackAudio($conn, $exercicio_id, $id_usuario, $resultado) {
    $sql = "
        INSERT INTO feedback_audio 
        (exercicio_id, usuario_id, transcricao, pontuacao_audio, feedback_detalhado, data_resposta) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ";
    
    $transcricao = $resultado['transcricao'];
    $pontuacao = $resultado['pontuacao'];
    $feedback_json = json_encode($resultado['feedback_detalhado']);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisds", $exercicio_id, $id_usuario, $transcricao, $pontuacao, $feedback_json);
    $stmt->execute();
    $stmt->close();
}

function atualizarProgressoAudio($conn, $exercicio_id, $id_usuario, $resultado_analise) {
    $pontuacao = $resultado_analise['pontuacao'];
    $correto = $resultado_analise['status'] === 'correto';
    
    // Verificar se já existe progresso para este exercício
    $sql_check = "SELECT id, tentativas, acertos FROM progresso_detalhado WHERE exercicio_id = ? AND id_usuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $exercicio_id, $id_usuario);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $progresso_existente = $result->fetch_assoc();
    $stmt_check->close();
    
    if ($progresso_existente) {
        // Atualizar progresso existente
        $novas_tentativas = $progresso_existente['tentativas'] + 1;
        $novos_acertos = $progresso_existente['acertos'] + ($correto ? 1 : 0);
        $concluido = $correto ? 1 : 0;
        
        $sql_update = "
            UPDATE progresso_detalhado 
            SET tentativas = ?, acertos = ?, concluido = ?, pontuacao = ?, data_conclusao = ?
            WHERE id = ?
        ";
        
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iiidsi", $novas_tentativas, $novos_acertos, $concluido, $pontuacao, $data_conclusao, $progresso_existente['id']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Criar novo progresso
        $tentativas = 1;
        $acertos = $correto ? 1 : 0;
        $concluido = $correto ? 1 : 0;
        $data_conclusao = $concluido ? date('Y-m-d H:i:s') : null;
        
        $sql_insert = "
            INSERT INTO progresso_detalhado 
            (id_usuario, exercicio_id, concluido, tentativas, acertos, pontuacao, data_conclusao) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiiids", $id_usuario, $exercicio_id, $concluido, $tentativas, $acertos, $pontuacao, $data_conclusao);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
?>
