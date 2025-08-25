<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID da teoria foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_teorias.php");
    exit();
}

$teoria_id = $_GET['id'];
$mensagem = '';

$database = new Database();
$conn = $database->conn;

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $nivel = $_POST['nivel'];
    $ordem = $_POST['ordem'];
    $conteudo = $_POST['conteudo'];
    $resumo = $_POST['resumo'] ?? '';
    $palavras_chave = $_POST['palavras_chave'] ?? '';

    // Validação simples
    if (empty($titulo) || empty($nivel) || empty($ordem) || empty($conteudo)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        // Atualiza a teoria na tabela
        $sql_update = "UPDATE teorias SET titulo = ?, nivel = ?, ordem = ?, conteudo = ?, resumo = ?, palavras_chave = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("ssisssi", $titulo, $nivel, $ordem, $conteudo, $resumo, $palavras_chave, $teoria_id);
            
            if ($stmt_update->execute()) {
                $mensagem = '<div class="alert alert-success">Teoria atualizada com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar teoria: ' . $stmt_update->error . '</div>';
            }
            $stmt_update->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }
    }
}

// Busca os dados da teoria para edição
$sql_teoria = "SELECT titulo, nivel, ordem, conteudo, resumo, palavras_chave FROM teorias WHERE id = ?";
$stmt_teoria = $conn->prepare($sql_teoria);
$stmt_teoria->bind_param("i", $teoria_id);
$stmt_teoria->execute();
$teoria = $stmt_teoria->get_result()->fetch_assoc();
$stmt_teoria->close();

$database->closeConnection();

if (!$teoria) {
    header("Location: gerenciar_teorias.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Teoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Editar Teoria</h2>
        <a href="gerenciar_teorias.php" class="btn btn-secondary mb-3">← Voltar para Teorias</a>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <form action="editar_teoria.php?id=<?php echo htmlspecialchars($teoria_id); ?>" method="POST">
                    <!-- Campo Título -->
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título da Teoria *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($teoria['titulo']); ?>" required>
                    </div>

                    <!-- Campo Nível -->
                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível *</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="">Selecione o nível</option>
                            <option value="Iniciante" <?php echo ($teoria['nivel'] == 'Iniciante') ? 'selected' : ''; ?>>Iniciante</option>
                            <option value="Intermediário" <?php echo ($teoria['nivel'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                            <option value="Avançado" <?php echo ($teoria['nivel'] == 'Avançado') ? 'selected' : ''; ?>>Avançado</option>
                        </select>
                    </div>

                    <!-- Campo Ordem -->
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem de Exibição *</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($teoria['ordem']); ?>" min="1" required>
                        <div class="form-text">Ordem em que a teoria aparecerá na lista (1, 2, 3...)</div>
                    </div>

                    <!-- Campo Resumo -->
                    <div class="mb-3">
                        <label for="resumo" class="form-label">Resumo</label>
                        <textarea class="form-control" id="resumo" name="resumo" rows="3" placeholder="Breve resumo da teoria"><?php echo htmlspecialchars($teoria['resumo']); ?></textarea>
                        <div class="form-text">Resumo que aparecerá na lista de teorias</div>
                    </div>

                    <!-- Campo Palavras-chave -->
                    <div class="mb-3">
                        <label for="palavras_chave" class="form-label">Palavras-chave</label>
                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php echo htmlspecialchars($teoria['palavras_chave']); ?>" placeholder="gramática, verbos, presente simples">
                        <div class="form-text">Palavras-chave separadas por vírgula para facilitar a busca</div>
                    </div>

                    <!-- Campo Conteúdo -->
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo da Teoria *</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="15" required><?php echo htmlspecialchars($teoria['conteudo']); ?></textarea>
                        <div class="form-text">Conteúdo completo da teoria. Você pode usar HTML para formatação.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Atualizar Teoria</button>
                    <a href="gerenciar_teorias.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar TinyMCE para o campo de conteúdo
        tinymce.init({
            selector: '#conteudo',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    </script>
</body>
</html>