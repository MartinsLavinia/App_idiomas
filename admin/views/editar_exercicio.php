<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Apenas admin logado pode acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
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
    $pergunta = $_POST['pergunta'];
    $conteudo = null;

    // Constrói o conteúdo JSON com base no tipo de exercício
    switch ($tipo) {
        case 'normal':
            $conteudo = json_encode([
                'alternativas' => explode(',', $_POST['alternativas']),
                'resposta_correta' => $_POST['resposta_correta']
            ]);
            break;
        case 'especial':
            $conteudo = json_encode([
                'link_video' => $_POST['link_video'],
                'pergunta_extra' => $_POST['pergunta_extra']
            ]);
            break;
        case 'quiz':
            $conteudo = json_encode([
                'quiz_id' => $_POST['quiz_id']
            ]);
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

// BUSCA AS INFORMAÇÕES DO CAMINHO PARA EXIBIÇÃO NO TÍTULO
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
    <title>Editar Exercício - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Editar Exercício do Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?></h2>
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
                            <option value="normal" <?php if ($exercicio['tipo'] == 'normal') echo 'selected'; ?>>Normal (Múltipla Escolha)</option>
                            <option value="especial" <?php if ($exercicio['tipo'] == 'especial') echo 'selected'; ?>>Especial (Vídeo/Áudio)</option>
                            <option value="quiz" <?php if ($exercicio['tipo'] == 'quiz') echo 'selected'; ?>>Quiz (ID de um quiz)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pergunta" class="form-label">Pergunta</label>
                        <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($exercicio['pergunta']); ?></textarea>
                    </div>
                    
                    <div id="conteudo-campos">
                        <div id="campos-normal" style="display: none;">
                            <div class="mb-3">
                                <label for="alternativas" class="form-label">Alternativas (separadas por vírgula)</label>
                                <input type="text" class="form-control" id="alternativas" name="alternativas" value="<?php echo isset($conteudo_array['alternativas']) ? htmlspecialchars(implode(',', $conteudo_array['alternativas'])) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="resposta_correta" class="form-label">Resposta Correta</label>
                                <input type="text" class="form-control" id="resposta_correta" name="resposta_correta" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const camposNormal = document.getElementById('campos-normal');
        const camposEspecial = document.getElementById('campos-especial');
        const camposQuiz = document.getElementById('campos-quiz');
        
        function mostrarCampos(tipo) {
            camposNormal.style.display = 'none';
            camposEspecial.style.display = 'none';
            camposQuiz.style.display = 'none';
            
            switch (tipo) {
                case 'normal':
                    camposNormal.style.display = 'block';
                    break;
                case 'especial':
                    camposEspecial.style.display = 'block';
                    break;
                case 'quiz':
                    camposQuiz.style.display = 'block';
                    break;
            }
        }
        
        // Inicializa os campos com o tipo atual do exercício
        mostrarCampos(tipoSelect.value);

        // Adiciona um listener para mudanças
        tipoSelect.addEventListener('change', function() {
            mostrarCampos(this.value);
        });
    });
    </script>
</body>
</html>