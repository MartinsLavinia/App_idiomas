<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Apenas admin logado pode acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do exercício foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

$exercicio_id = $_GET['id'];
$mensagem = '';

$database = new Database();
$conn = $database->conn;

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ordem = $_POST['ordem'];
    $tipo = $_POST['tipo'];
    $tipo_exercicio = $_POST['tipo_exercicio'] ?? 'multipla_escolha';
    $pergunta = $_POST['pergunta'];
    $conteudo = null;

    // Constrói o conteúdo JSON com base no tipo de exercício
    switch ($tipo) {
        case 'normal':
            if ($tipo_exercicio === 'multipla_escolha') {
                if (!empty($_POST['alt_texto'])) {
                    $alternativas = [];
                    foreach ($_POST['alt_texto'] as $index => $texto) {
                        if (!empty($texto)) {
                            $alternativas[] = [
                                'id' => chr(97 + $index),
                                'texto' => $texto,
                                'correta' => (isset($_POST['alt_correta']) && $_POST['alt_correta'] == $index)
                            ];
                        }
                    }
                    $conteudo = json_encode([
                        'alternativas' => $alternativas,
                        'explicacao' => $_POST['explicacao'] ?? ''
                    ], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($tipo_exercicio === 'texto_livre') {
                $alternativas_aceitas = !empty($_POST['alternativas_aceitas']) ? 
                    array_map('trim', explode(',', $_POST['alternativas_aceitas'])) : 
                    [$_POST['resposta_esperada']];
                $conteudo = json_encode([
                    'resposta_correta' => $_POST['resposta_esperada'],
                    'alternativas_aceitas' => $alternativas_aceitas,
                    'dica' => $_POST['dica_texto'] ?? ''
                ], JSON_UNESCAPED_UNICODE);
            } elseif ($tipo_exercicio === 'completar') {
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
            } elseif ($tipo_exercicio === 'fala') {
                $palavras_chave = !empty($_POST['palavras_chave']) ? 
                    array_map('trim', explode(',', $_POST['palavras_chave'])) : 
                    [];
                $conteudo = json_encode([
                    'frase_esperada' => $_POST['frase_esperada'],
                    'pronuncia_fonetica' => $_POST['pronuncia_fonetica'] ?? '',
                    'palavras_chave' => $palavras_chave,
                    'tolerancia_erro' => 0.8
                ], JSON_UNESCAPED_UNICODE);
            } elseif ($tipo_exercicio === 'audicao') {
                $conteudo = json_encode([
                    'audio_url' => $_POST['audio_url'],
                    'transcricao' => $_POST['transcricao'],
                    'pergunta_audio' => $_POST['pergunta_audio'],
                    'resposta_correta' => $_POST['resposta_audio_correta']
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'especial':
            $conteudo = json_encode([
                'link_video' => $_POST['link_video'],
                'pergunta_extra' => $_POST['pergunta_extra']
            ], JSON_UNESCAPED_UNICODE);
            break;
        case 'quiz':
            $conteudo = json_encode([
                'quiz_id' => $_POST['quiz_id']
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

    // Atualiza o exercício na tabela, usando Prepared Statement
    $sql_update = "UPDATE exercicios SET ordem = ?, tipo = ?, pergunta = ?, conteudo = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("isssi", $ordem, $tipo, $pergunta, $conteudo, $exercicio_id);
    
    if ($stmt_update->execute()) {
        $mensagem = '<div class="alert alert-success">Exercício atualizado com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar exercício: ' . $stmt_update->error . '</div>';
    }
    $stmt_update->close();
}

// BUSCA AS INFORMAÇÕES DO EXERCÍCIO EXISTENTE PARA PREENCHER O FORMULÁRIO
$sql_exercicio = "SELECT caminho_id, ordem, tipo, pergunta, conteudo FROM exercicios WHERE id = ?";
$stmt_exercicio = $conn->prepare($sql_exercicio);
$stmt_exercicio->bind_param("i", $exercicio_id);
$stmt_exercicio->execute();
$exercicio = $stmt_exercicio->get_result()->fetch_assoc();
$stmt_exercicio->close();

if (!$exercicio) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

// Decodifica o JSON para que os campos do formulário possam ser preenchidos
$conteudo_array = json_decode($exercicio['conteudo'], true);
$caminho_id = $exercicio['caminho_id'];

// DEBUG: Mostrar o conteúdo para verificar a estrutura
error_log("Conteúdo do exercício: " . print_r($conteudo_array, true));

// Determinar o tipo de exercício baseado no conteúdo
$tipo_exercicio_detectado = 'multipla_escolha'; // padrão

if ($exercicio['tipo'] === 'normal' && $conteudo_array) {
    if (isset($conteudo_array['frase_completar'])) {
        $tipo_exercicio_detectado = 'completar';
    } elseif (isset($conteudo_array['frase_esperada'])) {
        $tipo_exercicio_detectado = 'fala';
    } elseif (isset($conteudo_array['audio_url'])) {
        $tipo_exercicio_detectado = 'audicao';
    } elseif (isset($conteudo_array['resposta_correta']) && !isset($conteudo_array['alternativas'])) {
        $tipo_exercicio_detectado = 'texto_livre';
    } elseif (isset($conteudo_array['alternativas'])) {
        $tipo_exercicio_detectado = 'multipla_escolha';
    }
}

// BUSCA AS INFORMAÇÕES DO CAMINHO PARA EXIBIÇÃO NO TÍTULO
$sql_caminho = "SELECT nome_caminho, nivel, id_unidade FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho_info = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

$database->closeConnection();

// Função auxiliar para converter arrays em string para exibição
function arrayToString($array) {
    if (is_array($array)) {
        return implode(', ', $array);
    }
    return $array;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Exercício - Admin</title>
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
        <h2 class="mb-4">Editar Exercício - Vinculado ao Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?></h2>
        
        <div class="alert alert-info">
            <strong>📌 Localização do Exercício:</strong><br>
            • <strong>Unidade:</strong> <?php echo htmlspecialchars($caminho_info['id_unidade'] ?? 'Não especificada'); ?><br>
            • <strong>Caminho:</strong> <?php echo htmlspecialchars($caminho_info['nome_caminho']); ?> (<?php echo htmlspecialchars($caminho_info['nivel']); ?>)<br>
            • <strong>ID do Caminho:</strong> <?php echo htmlspecialchars($caminho_id); ?><br>
            <small class="text-muted">Este exercício está vinculado exclusivamente a este caminho e não aparecerá em outras unidades.</small>
        </div>
        

        <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn btn-secondary mb-3">← Voltar para Exercícios</a>
        
        <?php echo $mensagem; ?>
        

        <div class="card">
            <div class="card-body">
                <form action="editar_exercicio.php?id=<?php echo htmlspecialchars($exercicio_id); ?>" method="POST">
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem do Exercício</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($exercicio['ordem']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Exercício</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="normal" <?php if ($exercicio['tipo'] == 'normal') echo 'selected'; ?>>Normal</option>
                            <option value="especial" <?php if ($exercicio['tipo'] == 'especial') echo 'selected'; ?>>Especial (Vídeo/Áudio)</option>
                            <option value="quiz" <?php if ($exercicio['tipo'] == 'quiz') echo 'selected'; ?>>Quiz</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                        <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                            <option value="multipla_escolha" <?php echo ($tipo_exercicio_detectado == 'multipla_escolha') ? 'selected' : ''; ?>>Múltipla Escolha</option>
                            <option value="texto_livre" <?php echo ($tipo_exercicio_detectado == 'texto_livre') ? 'selected' : ''; ?>>Texto Livre</option>
                            <option value="completar" <?php echo ($tipo_exercicio_detectado == 'completar') ? 'selected' : ''; ?>>Completar Frase</option>
                            <option value="fala" <?php echo ($tipo_exercicio_detectado == 'fala') ? 'selected' : ''; ?>>Exercício de Fala</option>
                            <option value="audicao" <?php echo ($tipo_exercicio_detectado == 'audicao') ? 'selected' : ''; ?>>Exercício de Audição</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pergunta" class="form-label">Pergunta</label>
                        <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($exercicio['pergunta']); ?></textarea>
                    </div>
                    
                    <div id="conteudo-campos">
                        <div id="campos-normal">
                            <!-- Campos para Múltipla Escolha -->
                            <div id="campos-multipla" class="subtipo-campos">
                                <h5>Configuração - Múltipla Escolha</h5>
                                <div class="mb-3">
                                    <label class="form-label">Alternativas</label>
                                    <div id="alternativas-container">
                                        <?php
                                        if (isset($conteudo_array['alternativas']) && is_array($conteudo_array['alternativas'])) {
                                            foreach ($conteudo_array['alternativas'] as $index => $alt) {
                                                $letra = chr(65 + $index);
                                                $texto = is_array($alt) ? ($alt['texto'] ?? '') : $alt;
                                                $checked = (is_array($alt) && isset($alt['correta']) && $alt['correta']) ? 'checked' : '';
                                                echo '<div class="input-group mb-2">';
                                                echo '<span class="input-group-text">' . $letra . '</span>';
                                                echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa" value="' . htmlspecialchars($texto) . '">';
                                                echo '<div class="input-group-text">';
                                                echo '<input type="radio" name="alt_correta" value="' . $index . '" ' . $checked . ' title="Marcar como correta">';
                                                echo '</div>';
                                                echo '<button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">×</button>';
                                                echo '</div>';
                                            }
                                        } else {
                                            // Alternativas padrão
                                            echo '<div class="input-group mb-2">';
                                            echo '<span class="input-group-text">A</span>';
                                            echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">';
                                            echo '<div class="input-group-text">';
                                            echo '<input type="radio" name="alt_correta" value="0" title="Marcar como correta">';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="input-group mb-2">';
                                            echo '<span class="input-group-text">B</span>';
                                            echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">';
                                            echo '<div class="input-group-text">';
                                            echo '<input type="radio" name="alt_correta" value="1" title="Marcar como correta">';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="adicionarAlternativa()">+ Adicionar Alternativa</button>
                                </div>
                                <div class="mb-3">
                                    <label for="explicacao" class="form-label">Explicação</label>
                                    <textarea class="form-control" id="explicacao" name="explicacao" placeholder="Explicação da resposta correta"><?php 
                                    // CORREÇÃO: Buscar a explicação no local correto
                                    if (isset($conteudo_array['explicacao'])) {
                                        echo htmlspecialchars($conteudo_array['explicacao']);
                                    } elseif (isset($conteudo_array['dica'])) {
                                        echo htmlspecialchars($conteudo_array['dica']);
                                    }
                                    ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Texto Livre -->
                            <div id="campos-texto" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Texto Livre</h5>
                                <div class="mb-3">
                                    <label for="resposta_esperada" class="form-label">Resposta Esperada</label>
                                    <input type="text" class="form-control" id="resposta_esperada" name="resposta_esperada" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_aceitas" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_aceitas" name="alternativas_aceitas" value="<?php 
                                    if (isset($conteudo_array['alternativas_aceitas']) && is_array($conteudo_array['alternativas_aceitas'])) {
                                        echo htmlspecialchars(implode(', ', $conteudo_array['alternativas_aceitas']));
                                    } elseif (isset($conteudo_array['alternativas_aceitas'])) {
                                        echo htmlspecialchars($conteudo_array['alternativas_aceitas']);
                                    }
                                    ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dica_texto" class="form-label">Dica</label>
                                    <textarea class="form-control" id="dica_texto" name="dica_texto"><?php echo isset($conteudo_array['dica']) ? htmlspecialchars($conteudo_array['dica']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Completar -->
                            <div id="campos-completar" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Completar Frase</h5>
                                <div class="mb-3">
                                    <label for="frase_completar" class="form-label">Frase para Completar</label>
                                    <input type="text" class="form-control" id="frase_completar" name="frase_completar" value="<?php echo isset($conteudo_array['frase_completar']) ? htmlspecialchars($conteudo_array['frase_completar']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_completar" class="form-label">Resposta Correta</label>
                                    <input type="text" class="form-control" id="resposta_completar" name="resposta_completar" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_completar" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_completar" name="alternativas_completar" value="<?php 
                                    if (isset($conteudo_array['alternativas_aceitas']) && is_array($conteudo_array['alternativas_aceitas'])) {
                                        echo htmlspecialchars(implode(', ', $conteudo_array['alternativas_aceitas']));
                                    } elseif (isset($conteudo_array['alternativas_aceitas'])) {
                                        echo htmlspecialchars($conteudo_array['alternativas_aceitas']);
                                    }
                                    ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="placeholder_completar" class="form-label">Placeholder</label>
                                    <input type="text" class="form-control" id="placeholder_completar" name="placeholder_completar" value="<?php echo isset($conteudo_array['placeholder']) ? htmlspecialchars($conteudo_array['placeholder']) : 'Digite sua resposta...'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dica_completar" class="form-label">Dica</label>
                                    <textarea class="form-control" id="dica_completar" name="dica_completar"><?php echo isset($conteudo_array['dica']) ? htmlspecialchars($conteudo_array['dica']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Fala -->
                            <div id="campos-fala" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Exercício de Fala</h5>
                                <div class="mb-3">
                                    <label for="frase_esperada" class="form-label">Frase para Pronunciar</label>
                                    <input type="text" class="form-control" id="frase_esperada" name="frase_esperada" value="<?php echo isset($conteudo_array['frase_esperada']) ? htmlspecialchars($conteudo_array['frase_esperada']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética</label>
                                    <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" value="<?php echo isset($conteudo_array['pronuncia_fonetica']) ? htmlspecialchars($conteudo_array['pronuncia_fonetica']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="palavras_chave" class="form-label">Palavras-chave (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php 
                                    if (isset($conteudo_array['palavras_chave']) && is_array($conteudo_array['palavras_chave'])) {
                                        echo htmlspecialchars(implode(', ', $conteudo_array['palavras_chave']));
                                    } elseif (isset($conteudo_array['palavras_chave'])) {
                                        echo htmlspecialchars($conteudo_array['palavras_chave']);
                                    }
                                    ?>">
                                </div>
                            </div>
                            
                            <!-- Campos para Audição -->
                            <div id="campos-audicao" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Exercício de Audição</h5>
                                <div class="mb-3">
                                    <label for="audio_url" class="form-label">URL do Áudio</label>
                                    <input type="url" class="form-control" id="audio_url" name="audio_url" value="<?php echo isset($conteudo_array['audio_url']) ? htmlspecialchars($conteudo_array['audio_url']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="transcricao" class="form-label">Transcrição do Áudio</label>
                                    <textarea class="form-control" id="transcricao" name="transcricao"><?php echo isset($conteudo_array['transcricao']) ? htmlspecialchars($conteudo_array['transcricao']) : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="pergunta_audio" class="form-label">Pergunta sobre o Áudio</label>
                                    <input type="text" class="form-control" id="pergunta_audio" name="pergunta_audio" value="<?php echo isset($conteudo_array['pergunta_audio']) ? htmlspecialchars($conteudo_array['pergunta_audio']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_audio_correta" class="form-label">Resposta Correta</label>
                                    <input type="text" class="form-control" id="resposta_audio_correta" name="resposta_audio_correta" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div id="campos-especial" style="display: none;">
                            <div class="mb-3">
                                <label for="link_video" class="form-label">Link do Vídeo/Áudio</label>
                                <input type="text" class="form-control" id="link_video" name="link_video" value="<?php echo isset($conteudo_array['link_video']) ? htmlspecialchars($conteudo_array['link_video']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="pergunta_extra" class="form-label">Pergunta Extra</label>
                                <textarea class="form-control" id="pergunta_extra" name="pergunta_extra"><?php echo isset($conteudo_array['pergunta_extra']) ? htmlspecialchars($conteudo_array['pergunta_extra']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div id="campos-quiz" style="display: none;">
                            <div class="mb-3">
                                <label for="quiz_id" class="form-label">ID do Quiz</label>
                                <input type="number" class="form-control" id="quiz_id" name="quiz_id" value="<?php echo isset($conteudo_array['quiz_id']) ? htmlspecialchars($conteudo_array['quiz_id']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </form>
            </div>
        </div>
    </div>
     <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn btn-secondary mb-3">← Voltar para Exercícios</a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const tipoExercicioSelect = document.getElementById('tipo_exercicio');
        const camposNormal = document.getElementById('campos-normal');
        const camposEspecial = document.getElementById('campos-especial');
        const camposQuiz = document.getElementById('campos-quiz');
        
        const camposMultipla = document.getElementById('campos-multipla');
        const camposTexto = document.getElementById('campos-texto');
        const camposCompletar = document.getElementById('campos-completar');
        const camposFala = document.getElementById('campos-fala');
        const camposAudicao = document.getElementById('campos-audicao');

        function mostrarCampos() {
            // Esconder todos os campos principais
            camposNormal.style.display = 'none';
            camposEspecial.style.display = 'none';
            camposQuiz.style.display = 'none';
            
            // Esconder todos os subcampos
            camposMultipla.style.display = 'none';
            camposTexto.style.display = 'none';
            camposCompletar.style.display = 'none';
            camposFala.style.display = 'none';
            camposAudicao.style.display = 'none';

            if (tipoSelect.value === 'normal') {
                camposNormal.style.display = 'block';
                
                // Mostrar subcampo baseado no tipo de exercício
                switch (tipoExercicioSelect.value) {
                    case 'multipla_escolha':
                        camposMultipla.style.display = 'block';
                        break;
                    case 'texto_livre':
                        camposTexto.style.display = 'block';
                        break;
                    case 'completar':
                        camposCompletar.style.display = 'block';
                        break;
                    case 'fala':
                        camposFala.style.display = 'block';
                        break;
                    case 'audicao':
                        camposAudicao.style.display = 'block';
                        break;
                }
            } else if (tipoSelect.value === 'especial') {
                camposEspecial.style.display = 'block';
            } else if (tipoSelect.value === 'quiz') {
                camposQuiz.style.display = 'block';
            }
        }
        
        // Inicializar
        mostrarCampos();

        // Listeners
        tipoSelect.addEventListener('change', mostrarCampos);
        tipoExercicioSelect.addEventListener('change', mostrarCampos);
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
    </script>
</body>
</html>