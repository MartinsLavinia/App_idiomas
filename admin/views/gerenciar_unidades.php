<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
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
    <title>Gerenciar Unidades - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* ===== VARIÁVEIS E CONFIGURAÇÕES GLOBAIS ===== */
    :root {
        --roxo-principal: #6a0dad;
        --roxo-escuro: #4c087c;
        --roxo-claro: #8b5cf6;
        --amarelo-detalhe: #ffd700;
        --amarelo-escuro: #e6c200;
        --branco: #ffffff;
        --preto-texto: #212529;
        --cinza-claro: #f8f9fa;
        --cinza-medio: #dee2e6;
        --cinza-escuro: #6c757d;
        --sucesso: #28a745;
        --perigo: #dc3545;
        --alerta: #ffc107;
        --info: #17a2b8;
        
        --sombra-leve: 0 2px 10px rgba(0, 0, 0, 0.08);
        --sombra-media: 0 5px 20px rgba(0, 0, 0, 0.12);
        --sombra-forte: 0 10px 30px rgba(0, 0, 0, 0.15);
        --sombra-destaque: 0 15px 35px rgba(106, 13, 173, 0.2);
        
        --borda-radius: 12px;
        --transicao-rapida: all 0.3s ease;
        --transicao-lenta: all 0.5s ease;
    }

    /* ===== RESET E ESTILOS BASE ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: var(--preto-texto);
        line-height: 1.6;
        min-height: 100vh;
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Barra de Navegação */
    .navbar {
        background: transparent !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
        box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
    }

    .navbar-brand {
        font-weight: 700;
        letter-spacing: 1px;
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

    /* Conteúdo principal */
    .main-content {
        margin-left: 250px;
        padding: 20px;
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

    /* ===== HEADER ===== */
    .content-header {
        background: var(--branco);
        border-radius: var(--borda-radius);
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--sombra-leve);
        border-left: 4px solid var(--roxo-principal);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 0.5rem;
    }

    .header-title i {
        color: var(--roxo-principal);
        font-size: 1.5rem;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    /* ===== CARDS DE CONTEÚDO ===== */
    .content-card {
        background: var(--branco);
        border-radius: var(--borda-radius);
        box-shadow: var(--sombra-leve);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: var(--transicao-rapida);
    }

    .content-card:hover {
        box-shadow: var(--sombra-media);
    }

    .card-header {
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-escuro) 100%);
        color: var(--branco);
        padding: 1.5rem 2rem;
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: glassEffect 3s infinite;
    }

    @keyframes glassEffect {
        0% { left: -100%; }
        50% { left: 100%; }
        100% { left: 100%; }
    }

    .card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-weight: 600;
    }

    .card-body {
        padding: 2rem;
    }

    /* ===== TABELAS - CORRIGIDO ===== */
    .table-container {
        background: var(--branco);
        border-radius: var(--borda-radius);
        overflow: hidden;
        box-shadow: var(--sombra-leve);
        border: 2px solid rgba(106, 13, 173, 0.2) !important; /* Borda roxa adicionada */
    }

    .table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table thead th {
        background: linear-gradient(135deg, var(--cinza-claro) 0%, #e9ecef 100%);
        color: var(--roxo-principal);
        font-weight: 600;
        padding: 1.25rem 1rem;
        border: none;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid var(--cinza-medio);
        vertical-align: middle;
    }

    .table tbody tr:last-child td {
        border-bottom: none; /* Remove borda da última linha */
    }

    .table tbody tr:hover {
        background: rgba(106, 13, 173, 0.04);
    }

    /* ===== BADGES ===== */
    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-primary {
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
    }

    .badge-success {
        background: linear-gradient(135deg, var(--sucesso) 0%, #20c997 100%);
    }

    .badge-danger {
        background: linear-gradient(135deg, var(--perigo) 0%, #e83e8c 100%);
    }

    .badge-warning {
        background: linear-gradient(135deg, var(--alerta) 0%, #fd7e14 100%);
        color: var(--preto-texto);
    }

    /* ===== BOTÕES ===== */
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: var(--transicao-rapida);
        border: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-escuro) 100%);
        border-color: var(--roxo-principal);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--sombra-destaque);
    }

    /* Efeito de brilho para todos os botões */
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -150%;
        width: 150%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.5),
            transparent
        );
        transform: skewX(-25deg);
        transition: left 0.8s ease;
    }

    .btn:hover::before {
        left: 150%;
    }

    /* BOTÃO EDITAR - TRANSPARENTE COM BORDA AMARELA */
    .btn-warning {
        background: transparent;
        border: 2px solid var(--amarelo-detalhe);
        color: var(--preto-texto);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    }

    .btn-warning:hover {
        background: var(--amarelo-detalhe);
        border-color: var(--amarelo-detalhe);
        color: var(--preto-texto);
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(255, 215, 0, 0.5);
    }

    .btn-warning:active {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    }

    /* Botão de Eliminar - Mais vermelho */
    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-color: #dc3545;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
        border-color: #c82333;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(220, 53, 69, 0.6);
    }

    .btn-danger:active {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.5);
    }

    .btn-outline-primary {
        border-color: var(--roxo-principal);
        color: var(--roxo-principal);
    }

    .btn-outline-primary:hover {
        background: var(--roxo-principal);
        border-color: var(--roxo-principal);
        transform: translateY(-2px);
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    /* ===== ALERTAS ===== */
    .alert {
        border-radius: var(--borda-radius);
        border: none;
        box-shadow: var(--sombra-leve);
    }

    .alert-success {
        background: linear-gradient(135deg, var(--sucesso) 0%, #20c997 100%);
        color: var(--branco);
    }

    .alert-danger {
        background: linear-gradient(135deg, var(--perigo) 0%, #e83e8c 100%);
        color: var(--branco);
    }

    /* ===== RESPONSIVIDADE ===== */
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

    @media (max-width: 576px) {
        .header-actions {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }
        .btn {
            width: 100%;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
        }
    }

    /* ===== SCROLLBAR PERSONALIZADA ===== */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--cinza-claro);
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--roxo-escuro);
    }

    /* ===== ESTADOS E UTILITÁRIOS ===== */
    .text-gradient {
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .shadow-custom {
        box-shadow: var(--sombra-destaque);
    }

    .hover-lift:hover {
        transform: translateY(-3px);
        transition: var(--transicao-rapida);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--cinza-escuro);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .settings-icon {
        color: var(--roxo-principal) !important;
    }

   /* Efeito de brilho para o botão Amarelo com degrade */
.pesquisar-btn {
    background: linear-gradient(135deg, #e6b800 0%, #ffd700 100%);
    border: 2px solid #e6b800;
    color: var(--preto-texto);
    font-weight: 700;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    position: relative;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(230, 184, 0, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pesquisar-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -150%;
    width: 150%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.5),
        transparent
    );
    transform: skewX(-25deg);
    transition: left 0.8s ease;
}

.pesquisar-btn:hover::before {
    left: 150%;
}

.pesquisar-btn:hover {
    background: linear-gradient(135deg, #cc9900 0%, #e6b800d2 100%);
    border-color: #cc9900;
    transform: translateY(-3px) scale(1.05);
    color: var(--preto-texto);
    box-shadow: 0 12px 30px rgba(204, 153, 0, 0.6);
}

.pesquisar-btn:active {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 4px 15px rgba(204, 153, 0, 0.5);
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
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5 id="nome-admin"><?php echo isset($_SESSION['nome_admin']) ? htmlspecialchars($_SESSION['nome_admin']) : 'Administrador'; ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item">
                <i class="fas fa-road"></i> Gerenciar Caminhos
            </a>
            <a href="#" class="list-group-item">
                <i class="fas fa-language"></i> Adicionar Idioma com Quiz
            </a>
            <a href="#" class="list-group-item">
                <i class="fas fa-globe"></i> Gerenciar Idiomas
            </a>
            <a href="gerenciar_teorias.php" class="list-group-item">
                <i class="fas fa-book-open"></i> Gerenciar Teorias
            </a>
            <a href="gerenciar_unidades.php" class="list-group-item active">
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
            <!-- Cabeçalho do Conteúdo -->
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <i class="fas fa-cubes"></i>
                        <h1 class="h2 mb-0">Gerenciar Unidades</h1>
                    </div>
                    
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Botão Adicionar -->
          <div class="mb-4">
    <a href="adicionar_unidade.php" class="btn btn-warning pesquisar-btn position-relative overflow-hidden" style="padding: 12px 24px; font-size: 1.1rem;">
        <i class="fas fa-plus me-2"></i>Adicionar Nova Unidade
    </a>
</div>

            <!-- Tabela de Unidades -->
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i>
                        Unidades Existentes
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                                    <?php if (count($unidades) > 0): ?>
                                        <?php foreach ($unidades as $unidade): ?>
                                        <tr>
                                            <td><strong><?= $unidade['id']; ?></strong></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($unidade['nome_unidade']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary"><?= htmlspecialchars($unidade['nome_idioma']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($unidade['nivel']): ?>
                                                    <span class="badge badge-warning"><?= htmlspecialchars($unidade['nivel']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($unidade['numero_unidade']): ?>
                                                    <span class="fw-bold"><?= htmlspecialchars($unidade['numero_unidade']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($unidade['descricao'], 0, 50)) . (strlen($unidade['descricao']) > 50 ? '...' : ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="editar_unidade.php?id=<?= $unidade['id']; ?>" class="btn btn-warning">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <a href="eliminar_unidade.php?id=<?= $unidade['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja eliminar esta unidade?');">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="fas fa-cubes"></i>
                                                    <h4 class="text-muted">Nenhuma unidade cadastrada</h4>
                                                    <p class="text-muted mb-0">Comece adicionando uma nova unidade</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>