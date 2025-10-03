<?php
session_start();
// O caminho foi corrigido para subir dois níveis
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do caminho foi passado via URL
if (!isset($_GET['caminho_id']) || !is_numeric($_GET['caminho_id'])) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$caminho_id = $_GET['caminho_id'];
$mensagem = '';

// Lógica para exibir mensagem de sucesso após uma exclusão
if (isset($_GET['status']) && $_GET['status'] == 'sucesso_exclusao') {
    $mensagem = '<div class="alert alert-success">Exercício excluído com sucesso!</div>';
}

$database = new Database();
$conn = $database->conn;

// Busca as informações do caminho para exibir no título
$sql_caminho = "SELECT nome_caminho, nivel FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho_info = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

if (!$caminho_info) {
    header("Location: gerenciar_caminho.php");
    exit();
}

// Busca os exercícios do caminho
$sql_exercicios = "SELECT id, ordem, tipo, pergunta FROM exercicios WHERE caminho_id = ? ORDER BY ordem";
$stmt_exercicios = $conn->prepare($sql_exercicios);
$stmt_exercicios->bind_param("i", $caminho_id);
$stmt_exercicios->execute();
$exercicios = $stmt_exercicios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exercicios->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Exercícios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Exercícios do Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?></h2>
        <a href="gerenciar_blocos.php" class="btn btn-secondary mb-3">← Voltar para Blocos</a>
        <a href="gerenciar_caminho.php" class="btn btn-secondary mb-3">← Voltar para Caminhos</a>
        <a href="adicionar_atividades.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn btn-success mb-3">Adicionar Novo Exercício</a>
        
        <?php echo $mensagem; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ordem</th>
                    <th>Tipo</th>
                    <th>Pergunta</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exercicios)): ?>
                    <?php foreach ($exercicios as $exercicio): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exercicio['id']); ?></td>
                            <td><?php echo htmlspecialchars($exercicio['ordem']); ?></td>
                            <td><?php echo htmlspecialchars($exercicio['tipo']); ?></td>
                            <td><?php echo htmlspecialchars(substr($exercicio['pergunta'], 0, 50)) . '...'; ?></td>
                            <td>
                                <a href="editar_exercicio.php?id=<?php echo htmlspecialchars($exercicio['id']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="eliminar_exercicio.php?id=<?php echo htmlspecialchars($exercicio['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este exercício?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum exercício encontrado para este caminho.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>