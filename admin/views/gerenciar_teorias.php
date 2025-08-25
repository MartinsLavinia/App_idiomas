<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$mensagem = '';

// Lógica para exibir mensagem de sucesso após uma exclusão
if (isset($_GET['status']) && $_GET['status'] == 'sucesso_exclusao') {
    $mensagem = '<div class="alert alert-success">Teoria excluída com sucesso!</div>';
}

$database = new Database();
$conn = $database->conn;

// Busca todas as teorias
$sql_teorias = "SELECT id, titulo, nivel, ordem, data_criacao FROM teorias ORDER BY nivel, ordem";
$stmt_teorias = $conn->prepare($sql_teorias);
$stmt_teorias->execute();
$teorias = $stmt_teorias->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_teorias->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Teorias - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Gerenciar Teorias</h2>
        <a href="gerenciar_caminho.php" class="btn btn-secondary mb-3">← Voltar para Caminhos</a>
        <a href="adicionar_teoria.php" class="btn btn-success mb-3">Adicionar Nova Teoria</a>
        
        <?php echo $mensagem; ?>
        

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Nível</th>
                    <th>Ordem</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($teorias)): ?>
                    <?php foreach ($teorias as $teoria): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teoria['id']); ?></td>
                            <td><?php echo htmlspecialchars($teoria['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($teoria['nivel']); ?></td>
                            <td><?php echo htmlspecialchars($teoria['ordem']); ?></td>
                            <td><?php echo htmlspecialchars($teoria['data_criacao']); ?></td>
                            <td>
                                <a href="editar_teoria.php?id=<?php echo htmlspecialchars($teoria['id']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="eliminar_teoria.php?id=<?php echo htmlspecialchars($teoria['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta teoria?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma teoria encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
