<?php
session_start();
include_once __DIR__ . '/config/conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

$unidades = [];
$sql_unidades = "SELECT u.id, u.nome_unidade, u.descricao, u.nivel, u.numero_unidade, i.nome_idioma 
                 FROM unidades u 
                 JOIN idiomas i ON u.id_idioma = i.id 
                 ORDER BY i.nome_idioma, u.nivel, u.numero_unidade, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);

if ($result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Unidades</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Unidades</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <p><a href="adicionar_unidade.php" class="btn">Adicionar Nova Unidade</a></p>

        <h2>Unidades Existentes</h2>
        <?php if (count($unidades) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome da Unidade</th>
                        <th>Idioma</th>
                        <th>Nível</th>
                        <th>Número</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unidades as $unidade): ?>
                        <tr>
                            <td><?= $unidade['id']; ?></td>
                            <td><?= htmlspecialchars($unidade['nome_unidade']); ?></td>
                            <td><?= htmlspecialchars($unidade['nome_idioma']); ?></td>
                            <td><?= htmlspecialchars($unidade['nivel'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($unidade['numero_unidade'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars(substr($unidade['descricao'], 0, 50)) . (strlen($unidade['descricao']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <a href="editar_unidade.php?id=<?= $unidade['id']; ?>">Editar</a> |
                                <a href="eliminar_unidade.php?id=<?= $unidade['id']; ?>" onclick="return confirm('Tem certeza que deseja eliminar esta unidade?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma unidade cadastrada ainda.</p>
        <?php endif; ?>

        <p><a href="admin_dashboard.php">Voltar para o Painel Administrativo</a></p>
    </div>
</body>
</html>
