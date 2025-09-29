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

// Verifica se o ID do caminho foi passado via URL
if (!isset($_GET['caminho_id']) || !is_numeric($_GET['caminho_id'])) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

$caminho_id = $_GET['caminho_id'];
$mensagem = '';

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ordem = $_POST['ordem'];
    $tipo = $_POST['tipo'];
    $pergunta = $_POST['pergunta'];
    $conteudo = null;

    // Log para debug
    error_log("POST recebido - Ordem: $ordem, Tipo: $tipo, Pergunta: $pergunta");

    // Validação simples (exemplo: não permitir campos vazios)
    if (empty($ordem) || empty($pergunta)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        // Constrói o conteúdo JSON com base no tipo de exercício
        $tipo_exercicio = $_POST['tipo_exercicio'] ?? 'multipla_escolha';
        
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
                                    'id' => chr(97 + $index), // a, b, c, d...
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

        // Se não houve erros, insere os dados no banco
        if (empty($mensagem)) {
            $database = new Database();
            $conn = $database->conn;
            
            // Debug: Verifique os dados antes de inserir
            error_log("Tentando inserir exercício: caminho_id=$caminho_id, ordem=$ordem, tipo=$tipo");
            error_log("Pergunta: $pergunta");
            error_log("Conteúdo: " . $conteudo);
            
            // Insere o novo exercício na tabela
            $sql_insert = "INSERT INTO exercicios (caminho_id, ordem, tipo, pergunta, conteudo) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            
            if ($stmt_insert) {
                $stmt_insert->bind_param("iisss", $caminho_id, $ordem, $tipo, $pergunta, $conteudo);
                
                if ($stmt_insert->execute()) {
                    $mensagem = '<div class="alert alert-success">Exercício adicionado com sucesso!</div>';
                    // Limpar os campos do formulário após sucesso
                    $_POST = array();
                } else {
                    $error_msg = $stmt_insert->error;
                    $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício: ' . $error_msg . '</div>';
                    error_log("Erro MySQL: " . $error_msg);
                }
                $stmt_insert->close();
            } else {
                $error_msg = $conn->error;
                $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $error_msg . '</div>';
                error_log("Erro preparação: " . $error_msg);
            }

            $database->closeConnection();
        } else {
            error_log("Mensagem de erro presente: " . strip_tags($mensagem));
        }
    }
}

// BUSCA AS INFORMAÇÕES DO CAMINHO PARA EXIBIÇÃO NO TÍTULO
$database = new Database();
$conn = $database->conn;
$sql_caminho = "SELECT nome_caminho, nivel FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho_info = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();
$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Exercício - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Adicionar Exercício ao Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?></h2>
        <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn btn-secondary mb-3">← Voltar para Exercícios</a>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <form action="adicionar_atividades.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" method="POST">
                    <!-- Campo Ordem -->
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem do Exercício</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo isset($_POST['ordem']) ? htmlspecialchars($_POST['ordem']) : ''; ?>" required>
                    </div>

                    <!-- Campo Tipo -->
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Exercício</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="normal" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'normal') ? 'selected' : ''; ?>>Normal (Múltipla Escolha)</option>
                            <option value="especial" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'especial') ? 'selected' : ''; ?>>Especial (Vídeo/Áudio)</option>
                            <option value="quiz" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'quiz') ? 'selected' : ''; ?>>Quiz (ID de um quiz)</option>
                        </select>
                    </div>
                    
                    <!-- Campo Subtipo -->
                    <div class="mb-3">
                        <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                        <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                            <option value="multipla_escolha" <?php echo (isset($_POST['tipo_exercicio']) && $_POST['tipo_exercicio'] == 'multipla_escolha') ? 'selected' : ''; ?>>Múltipla Escolha</option>
                            <option value="texto_livre" <?php echo (isset($_POST['tipo_exercicio']) && $_POST['tipo_exercicio'] == 'texto_livre') ? 'selected' : ''; ?>>Texto Livre (Completar)</option>
                            <option value="completar" <?php echo (isset($_POST['tipo_exercicio']) && $_POST['tipo_exercicio'] == 'completar') ? 'selected' : ''; ?>>Completar Frase</option>
                            <option value="fala" <?php echo (isset($_POST['tipo_exercicio']) && $_POST['tipo_exercicio'] == 'fala') ? 'selected' : ''; ?>>Exercício de Fala</option>
                            <option value="audicao" <?php echo (isset($_POST['tipo_exercicio']) && $_POST['tipo_exercicio'] == 'audicao') ? 'selected' : ''; ?>>Exercício de Audição</option>
                        </select>
                    </div>

                    <!-- Campo Pergunta -->
                    <div class="mb-3">
                        <label for="pergunta" class="form-label">Pergunta</label>
                        <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo isset($_POST['pergunta']) ? htmlspecialchars($_POST['pergunta']) : ''; ?></textarea>
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
                                        // Se houve POST, tentar preencher as alternativas
                                        if (isset($_POST['alt_texto']) && is_array($_POST['alt_texto'])) {
                                            foreach ($_POST['alt_texto'] as $index => $texto) {
                                                $letra = chr(65 + $index);
                                                $checked = (isset($_POST['alt_correta']) && $_POST['alt_correta'] == $index) ? 'checked' : '';
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
                                            // Padrão: duas alternativas
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
                                    <textarea class="form-control" id="explicacao" name="explicacao" placeholder="Explicação da resposta correta"><?php echo isset($_POST['explicacao']) ? htmlspecialchars($_POST['explicacao']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Texto Livre -->
                            <div id="campos-texto" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Texto Livre</h5>
                                <div class="mb-3">
                                    <label for="resposta_esperada" class="form-label">Resposta Esperada</label>
                                    <input type="text" class="form-control" id="resposta_esperada" name="resposta_esperada" placeholder="Resposta principal" value="<?php echo isset($_POST['resposta_esperada']) ? htmlspecialchars($_POST['resposta_esperada']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_aceitas" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_aceitas" name="alternativas_aceitas" placeholder="is, é, am" value="<?php echo isset($_POST['alternativas_aceitas']) ? htmlspecialchars($_POST['alternativas_aceitas']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dica_texto" class="form-label">Dica</label>
                                    <textarea class="form-control" id="dica_texto" name="dica_texto" placeholder="Dica para ajudar o usuário"><?php echo isset($_POST['dica_texto']) ? htmlspecialchars($_POST['dica_texto']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Completar -->
                            <div id="campos-completar" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Completar Frase</h5>
                                <div class="mb-3">
                                    <label for="frase_completar" class="form-label">Frase para Completar</label>
                                    <input type="text" class="form-control" id="frase_completar" name="frase_completar" placeholder="I _____ a student. (use _____ para indicar onde completar)" value="<?php echo isset($_POST['frase_completar']) ? htmlspecialchars($_POST['frase_completar']) : ''; ?>">
                                    <div class="form-text">Use _____ para indicar onde o usuário deve completar</div>
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_completar" class="form-label">Resposta Correta</label>
                                    <input type="text" class="form-control" id="resposta_completar" name="resposta_completar" placeholder="am" value="<?php echo isset($_POST['resposta_completar']) ? htmlspecialchars($_POST['resposta_completar']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alternativas_completar" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="alternativas_completar" name="alternativas_completar" placeholder="am, 'm" value="<?php echo isset($_POST['alternativas_completar']) ? htmlspecialchars($_POST['alternativas_completar']) : ''; ?>">
                                    <div class="form-text">Outras formas aceitas da resposta</div>
                                </div>
                                <div class="mb-3">
                                    <label for="placeholder_completar" class="form-label">Placeholder do Campo</label>
                                    <input type="text" class="form-control" id="placeholder_completar" name="placeholder_completar" placeholder="Digite sua resposta..." value="<?php echo isset($_POST['placeholder_completar']) ? htmlspecialchars($_POST['placeholder_completar']) : 'Digite sua resposta...'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dica_completar" class="form-label">Dica</label>
                                    <textarea class="form-control" id="dica_completar" name="dica_completar" placeholder="Dica para ajudar o usuário"><?php echo isset($_POST['dica_completar']) ? htmlspecialchars($_POST['dica_completar']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para Fala -->
                            <div id="campos-fala" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Exercício de Fala</h5>
                                <div class="mb-3">
                                    <label for="frase_esperada" class="form-label">Frase para Pronunciar</label>
                                    <input type="text" class="form-control" id="frase_esperada" name="frase_esperada" placeholder="Hello, my name is John" value="<?php echo isset($_POST['frase_esperada']) ? htmlspecialchars($_POST['frase_esperada']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética (opcional)</label>
                                    <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" placeholder="/həˈloʊ maɪ neɪm ɪz ʤɑn/" value="<?php echo isset($_POST['pronuncia_fonetica']) ? htmlspecialchars($_POST['pronuncia_fonetica']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="palavras_chave" class="form-label">Palavras-chave (separadas por vírgula)</label>
                                    <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" placeholder="hello, name, john" value="<?php echo isset($_POST['palavras_chave']) ? htmlspecialchars($_POST['palavras_chave']) : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Campos para Audição -->
                            <div id="campos-audicao" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Exercício de Audição</h5>
                                <div class="mb-3">
                                    <label for="audio_url" class="form-label">URL do Áudio</label>
                                    <input type="url" class="form-control" id="audio_url" name="audio_url" placeholder="https://exemplo.com/audio.mp3" value="<?php echo isset($_POST['audio_url']) ? htmlspecialchars($_POST['audio_url']) : ''; ?>">
                                    <div class="form-text">Cole aqui o link do arquivo de áudio (MP3, WAV, etc.)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="transcricao" class="form-label">Transcrição do Áudio</label>
                                    <textarea class="form-control" id="transcricao" name="transcricao" rows="3" placeholder="Texto que é falado no áudio"><?php echo isset($_POST['transcricao']) ? htmlspecialchars($_POST['transcricao']) : ''; ?></textarea>
                                    <div class="form-text">Digite exatamente o que é dito no áudio</div>
                                </div>
                                <div class="mb-3">
                                    <label for="pergunta_audio" class="form-label">Pergunta sobre o Áudio</label>
                                    <input type="text" class="form-control" id="pergunta_audio" name="pergunta_audio" placeholder="O que a pessoa disse?" value="<?php echo isset($_POST['pergunta_audio']) ? htmlspecialchars($_POST['pergunta_audio']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_audio_correta" class="form-label">Resposta Correta</label>
                                    <input type="text" class="form-control" id="resposta_audio_correta" name="resposta_audio_correta" placeholder="Resposta esperada do usuário" value="<?php echo isset($_POST['resposta_audio_correta']) ? htmlspecialchars($_POST['resposta_audio_correta']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div id="campos-especial" style="display: none;">
                            <div class="mb-3">
                                <label for="link_video" class="form-label">Link do Vídeo/Áudio</label>
                                <input type="text" class="form-control" id="link_video" name="link_video" value="<?php echo isset($_POST['link_video']) ? htmlspecialchars($_POST['link_video']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="pergunta_extra" class="form-label">Pergunta Extra</label>
                                <textarea class="form-control" id="pergunta_extra" name="pergunta_extra"><?php echo isset($_POST['pergunta_extra']) ? htmlspecialchars($_POST['pergunta_extra']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div id="campos-quiz" style="display: none;">
                            <div class="mb-3">
                                <label for="quiz_id" class="form-label">ID do Quiz</label>
                                <input type="number" class="form-control" id="quiz_id" name="quiz_id" value="<?php echo isset($_POST['quiz_id']) ? htmlspecialchars($_POST['quiz_id']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Salvar Exercício</button>
                </form>
            </div>
        </div>
    </div>

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

        function atualizarCampos() {
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

        tipoSelect.addEventListener('change', atualizarCampos);
        tipoExercicioSelect.addEventListener('change', atualizarCampos);
        
        // Inicializar
        atualizarCampos();
    });
    
    function adicionarAlternativa() {
        const container = document.getElementById("alternativas-container");
        const index = container.children.length;
        const novaAlternativa = document.createElement("div");
        novaAlternativa.className = "input-group mb-2";
        const letra = String.fromCharCode(65 + index); // 65 é o código ASCII para 'A'
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