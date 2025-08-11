<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
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

    $database = new Database();
    $conn = $database->conn;
    
    // Insere o novo exercício na tabela, usando Prepared Statement
    $sql_insert = "INSERT INTO exercicios (caminho_id, ordem, tipo, pergunta, conteudo) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    
    if ($stmt_insert) {
        $stmt_insert->bind_param("iisss", $caminho_id, $ordem, $tipo, $pergunta, $conteudo);
        
        if ($stmt_insert->execute()) {
            $mensagem = '<div class="alert alert-success">Exercício adicionado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício: ' . $stmt_insert->error . '</div>';
        }
        $stmt_insert->close();
    } else {
        $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
    }

    $database->closeConnection();
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
                <form action="adicionar_exercicio.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" method="POST">
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem do Exercício</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Exercício</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="normal">Normal (Múltipla Escolha)</option>
                            <option value="especial">Especial (Vídeo/Áudio)</option>
                            <option value="quiz">Quiz (ID de um quiz)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pergunta" class="form-label">Pergunta</label>
                        <textarea class="form-control" id="pergunta" name="pergunta" required></textarea>
                    </div>
                    
                    <div id="conteudo-campos">
                        <div id="campos-normal">
                            <div class="mb-3">
                                <label for="alternativas" class="form-label">Alternativas (separadas por vírgula)</label>
                                <input type="text" class="form-control" id="alternativas" name="alternativas">
                            </div>
                            <div class="mb-3">
                                <label for="resposta_correta" class="form-label">Resposta Correta</label>
                                <input type="text" class="form-control" id="resposta_correta" name="resposta_correta">
                            </div>
                        </div>

                        <div id="campos-especial" style="display: none;">
                            <div class="mb-3">
                                <label for="link_video" class="form-label">Link do Vídeo/Áudio</label>
                                <input type="text" class="form-control" id="link_video" name="link_video">
                            </div>
                            <div class="mb-3">
                                <label for="pergunta_extra" class="form-label">Pergunta Extra</label>
                                <textarea class="form-control" id="pergunta_extra" name="pergunta_extra"></textarea>
                            </div>
                        </div>
                        
                        <div id="campos-quiz" style="display: none;">
                            <div class="mb-3">
                                <label for="quiz_id" class="form-label">ID do Quiz</label>
                                <input type="number" class="form-control" id="quiz_id" name="quiz_id">
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
    // LÓGICA JAVASCRIPT PARA ALTERNAR OS CAMPOS DINÂMICOS
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const camposNormal = document.getElementById('campos-normal');
        const camposEspecial = document.getElementById('campos-especial');
        const camposQuiz = document.getElementById('campos-quiz');

        tipoSelect.addEventListener('change', function() {
            camposNormal.style.display = 'none';
            camposEspecial.style.display = 'none';
            camposQuiz.style.display = 'none';

            switch (this.value) {
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
        });
    });
    </script>
</body>
</html>