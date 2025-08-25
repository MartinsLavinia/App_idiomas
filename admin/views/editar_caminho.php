<?php
session_start();
include_once __DIR__ . '/../../conexao.php';
include_once __DIR__ . '/../models/CaminhoAprendizagem.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}
$mensagem = '';
$database = new Database();
$conn = $database->conn;
$caminhoObj = new CaminhoAprendizagem($conn);

// 1. Bloco de processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $idioma = $_POST['idioma'];
    $nome_caminho = $_POST['nome_caminho'];
    $nivel = $_POST['nivel'];

    // Chama o método para atualizar os dados no banco
    if ($caminhoObj->atualizarCaminho($id, $idioma, $nome_caminho, $nivel)) {
        $mensagem = "<div class='alert alert-success'>Caminho atualizado com sucesso!</div>";
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao atualizar o caminho.</div>";
    }
}

// 2. Lógica para buscar e exibir os dados (para GET ou após o POST)
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($id <= 0) {
    header("Location: gerenciar_caminho.php?erro=ID_invalido");
    exit();
}

$caminho = $caminhoObj->buscarPorId($id);

if (!$caminho) {
    header("Location: gerenciar_caminho.php?erro=caminho_nao_encontrado");
    exit();
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Caminho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Editar Caminho de Aprendizagem</h2>
        
        <?php echo $mensagem; ?>

        <form action="editar_caminho.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($caminho['id']); ?>">

            <div class="mb-3">
                <label for="idioma" class="form-label">Idioma</label>
                <input type="text" class="form-control" id="idioma" name="idioma" value="<?php echo htmlspecialchars($caminho['idioma']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" value="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nivel" class="form-label">Nível</label>
                <input type="text" class="form-control" id="nivel" name="nivel" value="<?php echo htmlspecialchars($caminho['nivel']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>