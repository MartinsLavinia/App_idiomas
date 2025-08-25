<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$mensagem = '';

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
        $database = new Database();
        $conn = $database->conn;
        
        // Insere a nova teoria na tabela
        $sql_insert = "INSERT INTO teorias (titulo, nivel, ordem, conteudo, resumo, palavras_chave, data_criacao) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        
        if ($stmt_insert) {
            $stmt_insert->bind_param("ssisss", $titulo, $nivel, $ordem, $conteudo, $resumo, $palavras_chave);
            
            if ($stmt_insert->execute()) {
                $mensagem = '<div class="alert alert-success">Teoria adicionada com sucesso!</div>';
                // Limpar campos após sucesso
                $titulo = $nivel = $ordem = $conteudo = $resumo = $palavras_chave = '';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao adicionar teoria: ' . $stmt_insert->error . '</div>';
            }
            $stmt_insert->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }

        $database->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Teoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Adicionar Nova Teoria</h2>
        <a href="gerenciar_teorias.php" class="btn btn-secondary mb-3">← Voltar para Teorias</a>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <form action="adicionar_teoria.php" method="POST">
                    <!-- Campo Título -->
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título da Teoria *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo ?? ''); ?>" required>
                    </div>

                    <!-- Campo Nível -->
                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível *</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="">Selecione o nível</option>
                            <option value="Iniciante" <?php echo (isset($nivel) && $nivel == 'Iniciante') ? 'selected' : ''; ?>>A1</option>
                            <option value="Intermediário" <?php echo (isset($nivel) && $nivel == 'Básico') ? 'selected' : ''; ?>>A2</option>
                            <option value="Avançado" <?php echo (isset($nivel) && $nivel == 'Intermediário') ? 'selected' : ''; ?>>B1</option>
                            <option value="Iniciante" <?php echo (isset($nivel) && $nivel == 'Intermediário Avançado') ? 'selected' : ''; ?>>B2</option>
                            <option value="Intermediário" <?php echo (isset($nivel) && $nivel == 'Avançado') ? 'selected' : ''; ?>>C1</option>
                            <option value="Avançado" <?php echo (isset($nivel) && $nivel == 'Proficiente') ? 'selected' : ''; ?>>C2</option>
                        </select>
                    </div>

                    <!-- Campo Ordem -->
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem de Exibição *</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($ordem ?? ''); ?>" min="1" required>
                        <div class="form-text">Ordem em que a teoria aparecerá na lista (1, 2, 3...)</div>
                    </div>

                    <!-- Campo Resumo -->
                    <div class="mb-3">
                        <label for="resumo" class="form-label">Resumo</label>
                        <textarea class="form-control" id="resumo" name="resumo" rows="3" placeholder="Breve resumo da teoria"><?php echo htmlspecialchars($resumo ?? ''); ?></textarea>
                        <div class="form-text">Resumo que aparecerá na lista de teorias</div>
                    </div>

                    <!-- Campo Palavras-chave -->
                    <div class="mb-3">
                        <label for="palavras_chave" class="form-label">Palavras-chave</label>
                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php echo htmlspecialchars($palavras_chave ?? ''); ?>" placeholder="gramática, verbos, presente simples">
                        <div class="form-text">Palavras-chave separadas por vírgula para facilitar a busca</div>
                    </div>

                    <!-- Campo Conteúdo -->
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo da Teoria *</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="15" required><?php echo htmlspecialchars($conteudo ?? ''); ?></textarea>
                        <div class="form-text">Conteúdo completo da teoria. Você pode usar HTML para formatação.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Adicionar Teoria</button>
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
