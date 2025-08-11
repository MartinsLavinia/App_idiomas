<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

// Lógica para buscar os caminhos
$database = new Database();
$conn = $database->conn;

$sql_caminhos = "SELECT id, idioma, nome_caminho, nivel FROM caminhos_aprendizagem ORDER BY idioma, nivel, nome_caminho";
$stmt_caminhos = $conn->prepare($sql_caminhos);
$stmt_caminhos->execute();
$caminhos = $stmt_caminhos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_caminhos->close();
$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Caminhos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>
        <a href="adicionar_caminho.php" class="btn btn-success mb-3">Adicionar Novo Caminho</a>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Idioma</th>
                    <th>Caminho</th>
                    <th>Nível</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($caminhos)): ?>
                    <?php foreach ($caminhos as $caminho): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($caminho['id']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['idioma']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nome_caminho']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nivel']); ?></td>
                            <td>
                                <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-info">Ver Exercícios</a>
                                <a href="editar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="eliminar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este caminho?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum caminho de aprendizado encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>