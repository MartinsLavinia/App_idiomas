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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* Paleta de Cores */
    :root {
        --roxo-principal: #6a0dad;
        --roxo-escuro: #4c087c;
        --amarelo-detalhe: #ffd700;
        --amarelo-botao: #ffd700;
        --amarelo-hover: #e7c500;
        --branco: #ffffff;
        --preto-texto: #212529;
        --cinza-claro: #f8f9fa;
        --cinza-medio: #dee2e6;
    }

    /* Estilos Gerais do Corpo */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--cinza-claro);
        color: var(--preto-texto);
        animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Barra de Navegação - MODIFICADA PARA TRANSPARENTE */
    .navbar {
        background-color: transparent !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
        box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
    }

    /* Ajuste da logo no header */
    .navbar-brand {
        margin-left: auto;
        margin-right: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: 100%;
    }

    .navbar-brand .logo-header {
        height: 70px;
        width: auto;
        display: block;
    }

    .navbar {
        display: flex;
        align-items: center;
    }

    .btn-outline-light {
        color: var(--amarelo-detalhe);
        border-color: var(--amarelo-detalhe);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-light:hover {
        background-color: var(--amarelo-detalhe);
        color: var(--preto-texto);
    }

    /* Estilos de Cartões (Cards) */
    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }

    .card-header {
        background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3) !important;
        color: var(--branco);
        border-radius: 1rem 1rem 0 0 !important;
        padding: 1.5rem;
    }

    .card-header h2 {
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* Menu Lateral */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100%;
        background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
        color: var(--branco);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        padding-top: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .sidebar .profile {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar .profile i {
        font-size: 4rem;
        color: var(--amarelo-detalhe);
        margin-bottom: 10px;
    }

    .sidebar .profile h5 {
        font-weight: 600;
        margin-bottom: 0;
        color: var(--branco);
    }

    .sidebar .profile small {
        color: var(--cinza-claro);
    }

    .sidebar .list-group {
        width: 100%;
    }

    .sidebar .list-group-item {
        background-color: transparent;
        color: var(--branco);
        border: none;
        padding: 15px 25px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .sidebar .list-group-item:hover {
        background-color: var(--roxo-escuro);
        cursor: pointer;
    }

    .sidebar .list-group-item.active {
        background-color: var(--roxo-escuro) !important;
        color: var(--branco) !important;
        font-weight: 600;
        border-left: 4px solid var(--amarelo-detalhe);
    }

    .sidebar .list-group-item i {
        color: var(--amarelo-detalhe);
    }

    /* Ajuste do conteúdo principal para não ficar por baixo do sidebar */
    .main-content {
        margin-left: 250px;
        padding: 20px;
    }

    /* Botão amarelo principal */
    .btn-warning {
        background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
        color: var(--preto-texto);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        min-width: 180px;
        border: none;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
        color: var(--preto-texto);
    }

    /* Botão outline amarelo */
    .btn-outline-warning {
        background: transparent;
        color: var(--preto-texto);
        border: 2px solid var(--amarelo-botao);
        min-width: 150px;
    }

    .btn-outline-warning:hover {
        background: transparent;
        color: var(--preto-texto);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    }

    /* Botões personalizados */
    .btn-primary {
        background-color: var(--roxo-principal);
        border-color: var(--roxo-principal);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: var(--roxo-escuro);
        border-color: var(--roxo-escuro);
        transform: scale(1.05);
    }

    .btn-secondary {
        background-color: var(--cinza-medio);
        border-color: var(--cinza-medio);
        color: var(--preto-texto);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: #c8c9cb;
        border-color: #c8c9cb;
        transform: scale(1.05);
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #218838;
        transform: scale(1.05);
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #c82333;
        border-color: #c82333;
        transform: scale(1.05);
    }

    /* Tabelas personalizadas */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        background-color: var(--branco);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 0;
    }

    .table thead th {
        background-color: var(--roxo-principal);
        color: var(--branco);
        border: none;
        font-weight: 600;
        padding: 15px;
        text-align: center;
    }

    .table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--cinza-medio);
        text-align: center;
        vertical-align: middle;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(106, 13, 173, 0.1);
    }

    /* Alertas personalizados */
    .alert {
        border-radius: 10px;
        border: none;
        padding: 15px 20px;
        font-weight: 500;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.15);
        color: #155724;
        border-left: 4px solid #28a745;
    }

    /* Badges personalizadas */
    .badge {
        font-weight: 600;
        padding: 0.5em 1em;
        border-radius: 50px;
    }

    .badge.bg-primary {
        background-color: var(--roxo-principal) !important;
    }

    /* Responsividade */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .main-content {
            margin-left: 200px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
        }
        .main-content {
            margin-left: 0;
        }
    }

    /* Animações adicionais */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    /* Estilo para o cabeçalho da página */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(106, 13, 173, 0.2);
    }

    /* Estilo para os botões de ação */
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Estilo para a tabela de teorias */
    .teorias-table {
        margin-top: 30px;
        padding-bottom: 0;
        border-radius: 10px;
        overflow: hidden;
    }

    /* Container da navbar */
    .navbar .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Estilo para o ícone de configurações */
    .settings-icon {
        color: var(--roxo-principal) !important;
        transition: all 0.3s ease;
    }

    .settings-icon:hover {
        transform: scale(1.1);
        color: var(--roxo-escuro) !important;
    }

    /* Estilo para a mensagem "Nenhuma teoria encontrada" */
    .empty-state {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--cinza-medio);
    }

    /* Loading spinner */
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    /* Estilos de Feedback */
    .feedback-container {
        margin-top: 1.5rem;
        padding: 1.5rem;
        border-radius: 1rem;
        font-weight: 500;
        display: none;
        animation: slideInUp 0.5s ease;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .feedback-success {
        background: #e6ffed;
        border: 1px solid #28a745;
        color: #155724;
    }

    .feedback-error {
        background: #fff0f0;
        border: 1px solid #dc3545;
        color: #721c24;
    }

    /* Animações e Efeitos */
    .fs-4 .badge {
        background-color: var(--amarelo-detalhe) !important;
        color: var(--preto-texto);
        font-weight: 700;
        padding: 0.5em 1em;
        border-radius: 50px;
        animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(255, 215, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
    }
    
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon" style="color: var(--roxo-principal) !important;">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item" data-bs-toggle="modal" data-bs-target="#addCaminhoModal">
                <i class="fas fa-plus-circle"></i> Adicionar Caminho
            </a>
            <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                <i class="fas fa-language"></i> Adicionar Idioma com Quiz
            </a>
            <a href="#" class="list-group-item" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal">
                <i class="fas fa-globe"></i> Gerenciar Idiomas
            </a>
            <a href="gerenciar_teorias.php" class="list-group-item active">
                <i class="fas fa-book-open"></i> Gerenciar Teorias
            </a>
            <a href="gerenciar_unidades.php" class="list-group-item">
                <i class="fas fa-cubes"></i> Gerenciar Unidades
            </a>
            <a href="gerenciar_usuarios.php" class="list-group-item">
                <i class="fas fa-users"></i> Gerenciar Usuários
            </a>
            <a href="estatisticas_usuarios.php" class="list-group-item">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </a>
            <a href="logout.php" class="list-group-item mt-auto">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="page-header">
                <h2 class="mb-0"><i class="fas fa-book-open"></i> Gerenciar Teorias</h2>
                <div class="action-buttons">
                    <a href="gerenciar_caminho.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar para Caminhos</a>
                    <a href="adicionar_teoria.php" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar Nova Teoria</a>
                </div>
            </div>
            
            <?php echo $mensagem; ?>
            
            <div class="teorias-table">
                <table class="table table-striped table-hover">
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
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($teoria['nivel']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($teoria['ordem']); ?></td>
                                    <td><?php echo htmlspecialchars($teoria['data_criacao']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar_teoria.php?id=<?php echo htmlspecialchars($teoria['id']); ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="eliminar_teoria.php?id=<?php echo htmlspecialchars($teoria['id']); ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta teoria?');">
                                                <i class="fas fa-trash"></i> Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-book-open text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Nenhuma teoria encontrada.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>