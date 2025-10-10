<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Ativar exibição de erros (apenas para desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID da UNIDADE foi passado via URL
if (!isset($_GET['unidade_id']) || !is_numeric($_GET['unidade_id'])) {
    header("Location: gerenciar_unidades.php");
    exit();
}

$unidade_id = $_GET['unidade_id'];
$mensagem = '';

// Instancia a conexão com o banco de dados
$database = new Database();
$conn = $database->conn;

// --- Funções de Acesso a Dados (simulando um Model) ---
function getUnidadeInfo($conn, $unidadeId) {
    $sql = "SELECT u.*, c.nome_caminho, c.nivel 
            FROM unidades u 
            LEFT JOIN caminhos_aprendizagem c ON u.id = c.id_unidade 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidadeId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

function getCaminhosByUnidade($conn, $unidadeId) {
    $sql = "SELECT id, nome_caminho FROM caminhos_aprendizagem WHERE id_unidade = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidadeId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

function getBlocosByCaminhos($conn, array $caminhoIds) {
    if (empty($caminhoIds)) {
        return [];
    }
    $placeholders = str_repeat('?,' , count($caminhoIds) - 1) . '?';
    $sql = "SELECT id, caminho_id, nome_bloco, ordem 
            FROM blocos 
            WHERE caminho_id IN ($placeholders) 
            ORDER BY caminho_id, ordem ASC";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($caminhoIds));
    $stmt->bind_param($types, ...$caminhoIds);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $blocosPorCaminho = [];
    foreach ($result as $bloco) {
        $blocosPorCaminho[$bloco["caminho_id"]][] = $bloco;
    }
    return $blocosPorCaminho;
}

function adicionarExercicio($conn, $caminhoId, $blocoId, $ordem, $tipo, $pergunta, $conteudo) {
    $sql = "INSERT INTO exercicios (caminho_id, bloco_id, ordem, tipo, pergunta, conteudo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiisss", $caminhoId, $blocoId, $ordem, $tipo, $pergunta, $conteudo);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Erro ao adicionar exercício: " . $stmt->error);
            $stmt->close();
            return false;
        }
    } else {
        error_log("Erro na preparação da consulta: " . $conn->error);
        return false;
    }
}

function getExercicioById($conn, $exercicioId) {
    $sql = "SELECT * FROM exercicios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercicioId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// --- Lógica de Processamento (simulando um Controller) ---

// Buscar informações da unidade, caminhos e blocos
$unidade_info = getUnidadeInfo($conn, $unidade_id);
$caminhos = getCaminhosByUnidade($conn, $unidade_id);

$blocos_por_caminho = [];
if (!empty($caminhos)) {
    $caminho_ids = array_column($caminhos, 'id');
    $blocos_por_caminho = getBlocosByCaminhos($conn, $caminho_ids);
}

// Lógica para lidar com a submissão do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caminho_id = $_POST["caminho_id"] ?? null;
    $bloco_id = $_POST["bloco_id"] ?? null;
    $ordem = $_POST["ordem"] ?? null;
    $tipo = $_POST["tipo"] ?? null;
    $pergunta = $_POST["pergunta"] ?? null;
    $tipo_exercicio = $_POST["tipo_exercicio"] ?? null;
    $conteudo = null;

    if (empty($caminho_id) || empty($bloco_id) || empty($ordem) || empty($pergunta)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        switch ($tipo) {
            case 'normal':
                if ($tipo_exercicio === 'multipla_escolha') {
                    if (empty($_POST['alt_texto']) || !isset($_POST['alt_correta'])) {
                        $mensagem = '<div class="alert alert-danger">Alternativas e resposta correta são obrigatórias.</div>';
                    } else {
                        $alternativas = [];
                        foreach ($_POST['alt_texto'] as $index => $texto) {
                            if (!empty($texto)) {
                                $alternativas[] = [
                                    'id' => chr(97 + $index),
                                    'texto' => $texto,
                                    'correta' => ($index == $_POST['alt_correta'])
                                ];
                            }
                        }
                        $conteudo = json_encode([
                            'alternativas' => $alternativas,
                            'explicacao' => $_POST['explicacao'] ?? ''
                        ], JSON_UNESCAPED_UNICODE);
                    }
                } elseif ($tipo_exercicio === 'texto_livre') {
                    if (empty($_POST['resposta_esperada'])) {
                        $mensagem = '<div class="alert alert-danger">Resposta esperada é obrigatória.</div>';
                    } else {
                        $alternativas_aceitas = !empty($_POST['alternativas_aceitas']) ? 
                            array_map('trim', explode(',', $_POST['alternativas_aceitas'])) : 
                            [$_POST['resposta_esperada']];
                        $conteudo = json_encode([
                            'resposta_correta' => $_POST['resposta_esperada'],
                            'alternativas_aceitas' => $alternativas_aceitas,
                            'dica' => $_POST['dica_texto'] ?? ''
                        ], JSON_UNESCAPED_UNICODE);
                    }
                } elseif ($tipo_exercicio === 'completar') {
                    if (empty($_POST['frase_completar']) || empty($_POST['resposta_completar'])) {
                        $mensagem = '<div class="alert alert-danger">Frase para completar e resposta são obrigatórias.</div>';
                    } else {
                        $alternativas_aceitas = !empty($_POST['alternativas_completar']) ? 
                            array_map('trim', explode(',', $_POST['alternativas_completar'])) : 
                            [$_POST['resposta_completar']];
                        $conteudo = json_encode([
                            'frase_completar' => $_POST['frase_completar'],
                            'resposta_correta' => $_POST['resposta_completar'],
                            'alternativas_aceitas' => $alternativas_aceitas,
                            'dica' => $_POST['dica_completar'] ?? '',
                            'placeholder' => $_POST['placeholder_completar'] ?? 'Digite sua resposta...'
                        ], JSON_UNESCAPED_UNICODE);
                    }
                } elseif ($tipo_exercicio === 'fala') {
                    if (empty($_POST['frase_esperada'])) {
                        $mensagem = '<div class="alert alert-danger">Frase esperada é obrigatória.</div>';
                    } else {
                        $palavras_chave = !empty($_POST['palavras_chave']) ? 
                            array_map('trim', explode(',', $_POST['palavras_chave'])) : 
                            [];
                        $conteudo = json_encode([
                            'frase_esperada' => $_POST['frase_esperada'],
                            'pronuncia_fonetica' => $_POST['pronuncia_fonetica'] ?? '',
                            'palavras_chave' => $palavras_chave,
                            'tolerancia_erro' => 0.8
                        ], JSON_UNESCAPED_UNICODE);
                    }
                } elseif ($tipo_exercicio === 'audicao') {
                    $conteudo = json_encode([
                        'audio_url' => $_POST['audio_url'] ?? '',
                        'transcricao' => $_POST['transcricao'] ?? '',
                        'resposta_correta' => $_POST['resposta_audio_correta'] ?? ''
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'especial':
                if (empty($_POST['link_video']) || empty($_POST['pergunta_extra'])) {
                    $mensagem = '<div class="alert alert-danger">O Link do Vídeo/Áudio e a Pergunta Extra são obrigatórios para este tipo de exercício.</div>';
                } else {
                    $conteudo = json_encode([
                        'link_video' => $_POST['link_video'],
                        'pergunta_extra' => $_POST['pergunta_extra']
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'quiz':
                if (empty($_POST['quiz_id'])) {
                    $mensagem = '<div class="alert alert-danger">O ID do Quiz é obrigatório para este tipo de exercício.</div>';
                } else {
                    $conteudo = json_encode([
                        'quiz_id' => $_POST['quiz_id']
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
        }

        if (empty($mensagem)) {
            if (adicionarExercicio($conn, $caminho_id, $bloco_id, $ordem, $tipo, $pergunta, $conteudo)) {
                $mensagem = '<div class="alert alert-success">Exercício adicionado com sucesso!</div>';
                $_POST = array(); // Limpar campos do formulário
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício.</div>';
            }
        }
    }
}

// --- Lógica de Correção de Áudio (para AJAX) ---
if (isset($_GET['action']) && $_GET['action'] === 'corrigir_audio') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $exercicioId = $data['exercicio_id'] ?? null;
    $transcricaoUsuario = $data['transcricao'] ?? '';

    if (!$exercicioId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do exercício não fornecido.']);
        exit();
    }

    $exercicio = getExercicioById($conn, $exercicioId);

    if (!$exercicio) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Exercício não encontrado.']);
        exit();
    }

    $conteudo = json_decode($exercicio['conteudo'], true);
    $fraseEsperada = $conteudo['resposta_correta'] ?? '';

    // Normalização básica dos textos para comparação
    $normalizeText = function($text) {
        $text = strtolower($text);
        $text = preg_replace('/[\p{P}\p{S}]/u', '', $text); // Remove pontuação e símbolos
        $text = trim($text);
        return $text;
    };

    $fraseEsperadaNormalizada = $normalizeText($fraseEsperada);
    $transcricaoUsuarioNormalizada = $normalizeText($transcricaoUsuario);

    // Cálculo da similaridade (usando similar_text)
    $similaridade = 0;
    similar_text($fraseEsperadaNormalizada, $transcricaoUsuarioNormalizada, $similaridade);

    $acertou = $similaridade >= 80; // Define um limiar de 80% de similaridade

    echo json_encode([
        'success' => true,
        'acertou' => $acertou,
        'similaridade' => round($similaridade, 2),
        'frase_correta' => $fraseEsperada
    ]);
    exit(); // Termina a execução após a resposta AJAX
}

$database->closeConnection();

// --- Variáveis para pré-preencher o formulário em caso de erro ou sucesso ---
$post_caminho_id = $_POST["caminho_id"] ?? '';
$post_bloco_id = $_POST["bloco_id"] ?? '';
$post_ordem = $_POST["ordem"] ?? '';
$post_tipo = $_POST["tipo"] ?? "normal";
$post_tipo_exercicio = $_POST["tipo_exercicio"] ?? "multipla_escolha";
$post_pergunta = $_POST["pergunta"] ?? '';

// Campos específicos para cada tipo de exercício
$post_alt_texto = $_POST["alt_texto"] ?? [];
$post_alt_correta = $_POST["alt_correta"] ?? null;
$post_explicacao = $_POST["explicacao"] ?? '';
$post_resposta_esperada = $_POST["resposta_esperada"] ?? '';
$post_alternativas_aceitas = $_POST["alternativas_aceitas"] ?? '';
$post_dica_texto = $_POST["dica_texto"] ?? '';
$post_frase_completar = $_POST["frase_completar"] ?? '';
$post_resposta_completar = $_POST["resposta_completar"] ?? '';
$post_alternativas_completar = $_POST["alternativas_completar"] ?? '';
$post_dica_completar = $_POST["dica_completar"] ?? '';
$post_placeholder_completar = $_POST["placeholder_completar"] ?? 'Digite sua resposta...';
$post_frase_esperada = $_POST["frase_esperada"] ?? '';
$post_pronuncia_fonetica = $_POST["pronuncia_fonetica"] ?? '';
$post_palavras_chave = $_POST["palavras_chave"] ?? '';
$post_audio_url = $_POST["audio_url"] ?? '';
$post_transcricao = $_POST["transcricao"] ?? '';
$post_resposta_audio_correta = $_POST["resposta_audio_correta"] ?? '';
$post_link_video = $_POST["link_video"] ?? '';
$post_pergunta_extra = $_POST["pergunta_extra"] ?? '';
$post_quiz_id = $_POST["quiz_id"] ?? '';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Exercício - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .subtipo-campos {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .input-group-text {
            min-width: 40px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Adicionar Exercício - Vinculado à Unidade: <?php echo htmlspecialchars($unidade_info['nome_unidade'] ?? 'N/A'); ?></h2>
        
        <div class="alert alert-info">
            <strong>📍 Adicionando Exercício para:</strong><br>
            • <strong>Unidade:</strong> <?php echo htmlspecialchars($unidade_info['nome_unidade'] ?? 'N/A'); ?><br>
            • <strong>Idioma:</strong> <?php echo htmlspecialchars($unidade_info['idioma'] ?? 'N/A'); ?><br>
            • <strong>Nível:</strong> <?php echo htmlspecialchars($unidade_info['nivel'] ?? 'N/A'); ?><br>
            <small class="text-muted">Este exercício ficará disponível APENAS nesta unidade específica.</small>
        </div>
        
        <a href="gerenciar_exercicios.php?unidade_id=<?php echo htmlspecialchars($unidade_id); ?>" class="btn btn-secondary mb-3">← Voltar para Exercícios</a>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <form action="adicionar_atividades.php?unidade_id=<?php echo htmlspecialchars($unidade_id); ?>" method="POST">
                    
                    <!-- Campo Caminho -->
                    <div class="mb-3">
                        <label for="caminho_id" class="form-label">Selecionar Caminho *</label>
                        <select class="form-select" id="caminho_id" name="caminho_id" required onchange="carregarBlocos(this.value)">
                            <option value="">-- Selecione um caminho --</option>
                            <?php foreach ($caminhos as $caminho): ?>
                                <option value="<?php echo $caminho['id']; ?>" 
                                    <?php echo ($post_caminho_id == $caminho['id']) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($caminho['nome_caminho']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo Bloco (será carregado via AJAX) -->
                    <div class="mb-3">
                        <label for="bloco_id" class="form-label">Selecionar Bloco *</label>
                        <select class="form-select" id="bloco_id" name="bloco_id" required>
                            <option value="">-- Primeiro selecione um caminho --</option>
                        </select>
                    </div>

                    <!-- Se houver um caminho selecionado no POST, pré-carregar os blocos -->
                    <?php if (!empty($post_caminho_id)): ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        carregarBlocos(<?php echo $post_caminho_id; ?>, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
                    });
                    </script>
                    <?php endif; ?>

                    <!-- Campo Ordem -->
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem do Exercício no Bloco</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($post_ordem); ?>" required>
                        <div class="form-text">Define a sequência em que este exercício aparecerá dentro do bloco selecionado</div>
                    </div>

                    <!-- Campo Tipo -->
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Exercício</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="normal" <?php echo ($post_tipo == "normal") ? "selected" : ""; ?>>Normal</option>
                            <option value="especial" <?php echo ($post_tipo == "especial") ? "selected" : ""; ?>>Especial (Vídeo/Áudio)</option>
                            <option value="quiz" <?php echo ($post_tipo == "quiz") ? "selected" : ""; ?>>Quiz</option>
                        </select>
                    </div>
                    
                    <!-- Campo Subtipo -->
                    <div class="mb-3">
                        <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                        <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                            <option value="multipla_escolha" <?php echo ($post_tipo_exercicio == "multipla_escolha") ? "selected" : ""; ?>>Múltipla Escolha</option>
                            <option value="texto_livre" <?php echo ($post_tipo_exercicio == "texto_livre") ? "selected" : ""; ?>>Texto Livre (Completar)</option>
                            <option value="completar" <?php echo ($post_tipo_exercicio == "completar") ? "selected" : ""; ?>>Completar Frase</option>
                            <option value="fala" <?php echo ($post_tipo_exercicio == "fala") ? "selected" : ""; ?>>Exercício de Fala</option>
                            <option value="audicao" <?php echo ($post_tipo_exercicio == "audicao") ? "selected" : ""; ?>>Exercício de Audição</option>
                        </select>
                    </div>

                    <!-- Campo Pergunta -->
                    <div class="mb-3">
                        <label for="pergunta" class="form-label">Pergunta</label>
                        <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($post_pergunta); ?></textarea>
                    </div>

                    <!-- Campos Dinâmicos -->
                    <div id="conteudo-campos">
                        <div id="campos-normal">
                            <!-- Campos para Múltipla Escolha -->
                            <div id="campos-multipla" class="subtipo-campos">
                                <h5>Configuração - Múltipla Escolha</h5>
                                <div class="mb-3">
                                    <label class="form-label">Alternativas</label>
                                    <div id="alternativas-container">
                                        <?php
                                        if (!empty($post_alt_texto)) {
                                            foreach ($post_alt_texto as $index => $texto) {
                                                $letra = chr(65 + $index);
                                                echo "
                                                <div class=\"input-group mb-2\">
                                                    <span class=\"input-group-text\">{$letra}</span>
                                                    <input type=\"text\" class=\"form-control\" name=\"alt_texto[]\" placeholder=\"Texto da alternativa\" value=\"" . htmlspecialchars($texto) . "\">
                                                    <div class=\"input-group-text\">
                                                        <input type=\"radio\" name=\"alt_correta\" value=\"{$index}\" " . (($post_alt_correta == $index) ? "checked" : "") . " title=\"Marcar como correta\">
                                                    </div>
                                                    <button type=\"button\" class=\"btn btn-outline-danger\" onclick=\"this.parentElement.remove()\">×</button>
                                                </div>";
                                            }
                                        } else {
                                            // Adiciona uma alternativa vazia por padrão se não houver POST
                                            echo "
                                            <div class=\"input-group mb-2\">
                                                <span class=\"input-group-text\">A</span>
                                                <input type=\"text\" class=\"form-control\" name=\"alt_texto[]\" placeholder=\"Texto da alternativa\">
                                                <div class=\"input-group-text\">
                                                    <input type=\"radio\" name=\"alt_correta\" value=\"0\" title=\"Marcar como correta\">
                                                </div>
                                                <button type=\"button\" class=\"btn btn-outline-danger\" onclick=\"this.parentElement.remove()\">×</button>
                                            </div>";
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="adicionarAlternativa()">Adicionar Alternativa</button>
                                </div>
                                <div class="mb-3">
                                    <label for="explicacao" class="form-label">Explicação (Opcional)</label>
                                    <textarea class="form-control" id="explicacao" name="explicacao" rows="2"><?php echo htmlspecialchars($post_explicacao); ?></textarea>
                                </div>
                            </div>

                            <!-- Campos para Texto Livre -->
                            <div id="campos-texto" class="subtipo-campos">
                                <h5>Configuração - Texto Livre</h5>
                                <div class="mb-3">
                                    <label for="resposta_esperada" class="form-label">Resposta Esperada *</label>
                                    <input type="text" class="form-control" id="resposta_esperada" name="resposta_esperada" value="<?php echo htmlspecialchars($post_resposta_esperada); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_aceitas" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_aceitas" name="alternativas_aceitas" value="<?php echo htmlspecialchars($post_alternativas_aceitas); ?>">
                                    <div class="form-text">Ex: resposta um, resposta dois. Inclua a resposta esperada aqui também.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="dica_texto" class="form-label">Dica (Opcional)</label>
                                    <input type="text" class="form-control" id="dica_texto" name="dica_texto" value="<?php echo htmlspecialchars($post_dica_texto); ?>">
                                </div>
                            </div>

                            <!-- Campos para Completar Frase -->
                            <div id="campos-completar" class="subtipo-campos">
                                <h5>Configuração - Completar Frase</h5>
                                <div class="mb-3">
                                    <label for="frase_completar" class="form-label">Frase para Completar *</label>
                                    <textarea class="form-control" id="frase_completar" name="frase_completar" rows="2"><?php echo htmlspecialchars($post_frase_completar); ?></textarea>
                                    <div class="form-text">Use <code>___</code> para indicar o espaço a ser preenchido. Ex: "Eu gosto de ___ maçãs."</div>
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_completar" class="form-label">Resposta Correta *</label>
                                    <input type="text" class="form-control" id="resposta_completar" name="resposta_completar" value="<?php echo htmlspecialchars($post_resposta_completar); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_completar" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_completar" name="alternativas_completar" value="<?php echo htmlspecialchars($post_alternativas_completar); ?>">
                                    <div class="form-text">Ex: resposta um, resposta dois. Inclua a resposta correta aqui também.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="dica_completar" class="form-label">Dica (Opcional)</label>
                                    <input type="text" class="form-control" id="dica_completar" name="dica_completar" value="<?php echo htmlspecialchars($post_dica_completar); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="placeholder_completar" class="form-label">Placeholder do Campo (Opcional)</label>
                                    <input type="text" class="form-control" id="placeholder_completar" name="placeholder_completar" value="<?php echo htmlspecialchars($post_placeholder_completar); ?>">
                                </div>
                            </div>

                            <!-- Campos para Exercício de Fala -->
                            <div id="campos-fala" class="subtipo-campos">
                                <h5>Configuração - Exercício de Fala</h5>
                                <div class="mb-3">
                                    <label for="frase_esperada" class="form-label">Frase Esperada para Fala *</label>
                                    <textarea class="form-control" id="frase_esperada" name="frase_esperada" rows="2"><?php echo htmlspecialchars($post_frase_esperada); ?></textarea>
                                    <div class="form-text">A frase que o usuário deve falar.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética (Opcional)</label>
                                    <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" value="<?php echo htmlspecialchars($post_pronuncia_fonetica); ?>">
                                    <div class="form-text">Para ajudar na avaliação da pronúncia.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="palavras_chave" class="form-label">Palavras-chave (separadas por vírgula, opcional)</label>
                                    <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php echo htmlspecialchars($post_palavras_chave); ?>">
                                    <div class="form-text">Palavras importantes para a correção.</div>
                                </div>
                            </div>

                            <!-- Campos para Exercício de Audição -->
                            <div id="campos-audicao" class="subtipo-campos">
                                <h5>Configuração - Exercício de Audição</h5>
                                <div class="mb-3">
                                    <label for="audio_url" class="form-label">URL do Áudio *</label>
                                    <input type="url" class="form-control" id="audio_url" name="audio_url" value="<?php echo htmlspecialchars($post_audio_url); ?>">
                                    <div class="form-text">Link para o arquivo de áudio (MP3, WAV, etc.).</div>
                                </div>
                                <div class="mb-3">
                                    <label for="transcricao" class="form-label">Transcrição do Áudio (Opcional)</label>
                                    <textarea class="form-control" id="transcricao" name="transcricao" rows="2"><?php echo htmlspecialchars($post_transcricao); ?></textarea>
                                    <div class="form-text">Texto do áudio para referência.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_audio_correta" class="form-label">Resposta Correta *</label>
                                    <input type="text" class="form-control" id="resposta_audio_correta" name="resposta_audio_correta" value="<?php echo htmlspecialchars($post_resposta_audio_correta); ?>">
                                    <div class="form-text">A resposta esperada do usuário após ouvir o áudio.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="exercicio_id_audicao" class="form-label">ID do Exercício (para correção)</label>
                                    <input type="number" class="form-control" id="exercicio_id_audicao" name="exercicio_id_audicao" placeholder="Preenchido após salvar o exercício">
                                    <button type="button" class="btn btn-info mt-2" onclick="testarCorrecaoAudio()">Testar Correção de Áudio</button>
                                    <div id="resultado_correcao_audio" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para Tipo Especial -->
                        <div id="campos-especial" class="subtipo-campos">
                            <h5>Configuração - Exercício Especial (Vídeo/Áudio)</h5>
                            <div class="mb-3">
                                <label for="link_video" class="form-label">Link do Vídeo/Áudio (YouTube, Vimeo, etc.) *</label>
                                <input type="url" class="form-control" id="link_video" name="link_video" value="<?php echo htmlspecialchars($post_link_video); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="pergunta_extra" class="form-label">Pergunta Extra *</label>
                                <textarea class="form-control" id="pergunta_extra" name="pergunta_extra" rows="3"><?php echo htmlspecialchars($post_pergunta_extra); ?></textarea>
                            </div>
                        </div>

                        <!-- Campos para Tipo Quiz -->
                        <div id="campos-quiz" class="subtipo-campos">
                            <h5>Configuração - Quiz</h5>
                            <div class="mb-3">
                                <label for="quiz_id" class="form-label">ID do Quiz *</label>
                                <input type="number" class="form-control" id="quiz_id" name="quiz_id" value="<?php echo htmlspecialchars($post_quiz_id); ?>">
                                <div class="form-text">Insira o ID do quiz existente.</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Adicionar Exercício</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const blocosPorCaminho = <?php echo json_encode($blocos_por_caminho, JSON_UNESCAPED_UNICODE); ?>;

    function carregarBlocos(caminhoId, blocoSelecionado = null) {
        const selectBloco = document.getElementById("bloco_id");
        selectBloco.innerHTML = '<option value="">-- Selecione um bloco --</option>'; // Limpa as opções anteriores

        const blocos = blocosPorCaminho[caminhoId] || [];

        if (blocos.length > 0) {
            blocos.forEach(bloco => {
                const option = document.createElement("option");
                option.value = bloco.id;
                option.textContent = `Bloco ${bloco.ordem}: ${bloco.nome_bloco}`;
                
                if (blocoSelecionado && bloco.id == blocoSelecionado) {
                    option.selected = true;
                }
                
                selectBloco.appendChild(option);
            });
        } else {
            selectBloco.innerHTML = '<option value="">-- Nenhum bloco encontrado --</option>';
        }
    }
    
    function preencherSelectBlocos(blocos, blocoSelecionado = null) {
        const selectBloco = document.getElementById("bloco_id");
        selectBloco.innerHTML = '<option value="">-- Selecione um bloco --</option>';
        
        blocos.forEach(bloco => {
            const option = document.createElement("option");
            option.value = bloco.id;
            option.textContent = `Bloco ${bloco.ordem}: ${bloco.nome_bloco}`;
            
            if (blocoSelecionado && bloco.id == blocoSelecionado) {
                option.selected = true;
            }
            
            selectBloco.appendChild(option);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const tipoSelect = document.getElementById("tipo");
        const tipoExercicioSelect = document.getElementById("tipo_exercicio");
        const camposNormal = document.getElementById("campos-normal");
        const camposEspecial = document.getElementById("campos-especial");
        const camposQuiz = document.getElementById("campos-quiz");
        
        const camposMultipla = document.getElementById("campos-multipla");
        const camposTexto = document.getElementById("campos-texto");
        const camposCompletar = document.getElementById("campos-completar");
        const camposFala = document.getElementById("campos-fala");
        const camposAudicao = document.getElementById("campos-audicao");

        function atualizarCampos() {
            // Esconder todos os campos principais
            camposNormal.style.display = "none";
            camposEspecial.style.display = "none";
            camposQuiz.style.display = "none";
            
            // Esconder todos os subcampos
            camposMultipla.style.display = "none";
            camposTexto.style.display = "none";
            camposCompletar.style.display = "none";
            camposFala.style.display = "none";
            camposAudicao.style.display = "none";

            if (tipoSelect.value === "normal") {
                camposNormal.style.display = "block";
                
                // Mostrar subcampo baseado no tipo de exercício
                switch (tipoExercicioSelect.value) {
                    case "multipla_escolha":
                        camposMultipla.style.display = "block";
                        break;
                    case "texto_livre":
                        camposTexto.style.display = "block";
                        break;
                    case "completar":
                        camposCompletar.style.display = "block";
                        break;
                    case "fala":
                        camposFala.style.display = "block";
                        break;
                    case "audicao":
                        camposAudicao.style.display = "block";
                        break;
                }
            } else if (tipoSelect.value === "especial") {
                camposEspecial.style.display = "block";
            } else if (tipoSelect.value === "quiz") {
                camposQuiz.style.display = "block";
            }
        }

        tipoSelect.addEventListener("change", atualizarCampos);
        tipoExercicioSelect.addEventListener("change", atualizarCampos);
        
        // Inicializar
        atualizarCampos();

        // Carregar blocos se um caminho já estiver selecionado (útil após POST com erro)
        const initialCaminhoId = document.getElementById('caminho_id').value;
        if (initialCaminhoId) {
            carregarBlocos(initialCaminhoId, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
        }
    });
    
    function adicionarAlternativa() {
        const container = document.getElementById("alternativas-container");
        const index = container.children.length;
        const novaAlternativa = document.createElement("div");
        novaAlternativa.className = "input-group mb-2";
        const letra = String.fromCharCode(65 + index);
        novaAlternativa.innerHTML = `
            <span class="input-group-text">${letra}</span>
            <input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">
            <div class="input-group-text">
                <input type="radio" name="alt_correta" value="${index}" title="Marcar como correta">
            </div>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">×</button>
        `;
        container.appendChild(novaAlternativa);
    }

    async function testarCorrecaoAudio() {
        const exercicioId = document.getElementById("exercicio_id_audicao").value;
        const transcricaoUsuario = prompt("Digite a transcrição do áudio para testar a correção:");
        const resultadoDiv = document.getElementById("resultado_correcao_audio");

        if (!exercicioId || !transcricaoUsuario) {
            resultadoDiv.innerHTML = '';
            resultadoDiv.classList.remove("alert-success", "alert-danger");
            resultadoDiv.classList.add("alert", "alert-warning");
            resultadoDiv.textContent = "Por favor, insira o ID do exercício e a transcrição para testar.";
            return;
        }

        resultadoDiv.innerHTML = '';
        resultadoDiv.classList.remove("alert-success", "alert-danger", "alert-warning");
        resultadoDiv.classList.add("alert", "alert-info");
        resultadoDiv.textContent = "Testando correção...";

        try {
            // A requisição AJAX agora aponta para o próprio arquivo, com uma action específica
            const response = await fetch(`adicionar_atividades.php?unidade_id=${<?php echo $unidade_id; ?>}&action=corrigir_audio`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    exercicio_id: exercicioId,
                    transcricao: transcricaoUsuario
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.acertou) {
                    resultadoDiv.classList.remove("alert-info", "alert-warning");
                    resultadoDiv.classList.add("alert-success");
                    resultadoDiv.innerHTML = `✅ Correto! Similaridade: ${data.similaridade}%<br>Frase esperada: ${data.frase_correta}`;
                } else {
                    resultadoDiv.classList.remove("alert-info", "alert-warning");
                    resultadoDiv.classList.add("alert-danger");
                    resultadoDiv.innerHTML = `❌ Incorreto. Similaridade: ${data.similaridade}%<br>Frase esperada: ${data.frase_correta}`;
                }
            } else {
                resultadoDiv.classList.remove("alert-info", "alert-success");
                resultadoDiv.classList.add("alert-danger");
                resultadoDiv.textContent = `Erro: ${data.message}`;
            }
        } catch (error) {
            console.error("Erro ao testar correção de áudio:", error);
            resultadoDiv.classList.remove("alert-info", "alert-success");
            resultadoDiv.classList.add("alert-danger");
            resultadoDiv.textContent = "Erro ao conectar com o servidor de correção.";
        }
    }
    </script>
</body>
</html>
