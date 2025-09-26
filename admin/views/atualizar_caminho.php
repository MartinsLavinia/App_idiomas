<?php
session_start();

include_once __DIR__ . 
'/../config/conexao.php';
include_once __DIR__ . 
'/../models/CaminhoAprendizagem.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar.
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$mensagem = '';
$database = new Database();
$conn = $database->conn;
$caminhoObj = new CaminhoAprendizagem($conn);

$unidades = [];
$sql_unidades = "SELECT u.id, u.nome_unidade, i.nome_idioma FROM unidades u JOIN idiomas i ON u.id_idioma = i.id ORDER BY i.nome_idioma, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);
if ($result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// 1. Lógica para processar o formulário de atualização (via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $id_unidade = $_POST['id_unidade'];
    $nome_caminho = $_POST['nome_caminho'];
    $nivel = $_POST['nivel'];

    // Obter o idioma da unidade selecionada para atualizar na tabela caminhos_aprendizagem
    $sql_get_idioma = "SELECT i.nome_idioma FROM unidades u JOIN idiomas i ON u.id_idioma = i.id WHERE u.id = ?";
    $stmt_get_idioma = $conn->prepare($sql_get_idioma);
    $stmt_get_idioma->bind_param("i", $id_unidade);
    $stmt_get_idioma->execute();
    $result_get_idioma = $stmt_get_idioma->get_result();
    $idioma_unidade = $result_get_idioma->fetch_assoc()['nome_idioma'];
    $stmt_get_idioma->close();

    // Chamada à função do CaminhoAprendizagem para atualizar o caminho
    if ($caminhoObj->atualizarCaminho($id, $idioma_unidade, $nome_caminho, $nivel, $id_unidade)) {
        $_SESSION['success'] = "Caminho atualizado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao atualizar o caminho.";
    }
    header("Location: gerenciar_caminho.php");
    exit();

} else {
    // 2. Lógica para exibir o formulário (via GET)
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        header("Location: gerenciar_caminho.php?erro=ID_invalido");
        exit();
    }

    $caminho = $caminhoObj->buscarPorId($id);

    if (!$caminho) {
        header("Location: gerenciar_caminho.php?erro=caminho_nao_encontrado");
        exit();
    }
}
$database->closeConnection();

$niveis_db = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
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
        
        <?php if (isset($_SESSION['error'])): ?>
            <p class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form action="atualizar_caminho.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($caminho['id']); ?>">

            <div class="mb-3">
                <label for="id_unidade" class="form-label">Unidade</label>
                <select class="form-control" id="id_unidade" name="id_unidade" required>
                    <option value="">Selecione uma unidade</option>
                    <?php foreach ($unidades as $unidade): ?>
                        <option value="<?= $unidade['id']; ?>" <?= ($unidade['id'] == $caminho['id_unidade']) ? 'selected' : ''; ?>><?= $unidade['nome_unidade']; ?> (<?= $unidade['nome_idioma']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" value="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nivel" class="form-label">Nível</label>
                <select class="form-control" id="nivel" name="nivel" required>
                    <option value="">Selecione um nível</option>
                    <?php foreach ($niveis_db as $nivel_option): ?>
                        <option value="<?= $nivel_option; ?>" <?= ($nivel_option == $caminho['nivel']) ? 'selected' : ''; ?>><?= $nivel_option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>