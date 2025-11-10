<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar foto do admin
$id_admin = $_SESSION['id_admin'];
$foto_admin = null;
$check_column_sql = "SHOW COLUMNS FROM administradores LIKE 'foto_perfil'";
$result_check = $conn->query($check_column_sql);

if ($result_check && $result_check->num_rows > 0) {
    $sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
    $stmt_foto = $conn->prepare($sql_foto);
    $stmt_foto->bind_param("i", $id_admin);
    $stmt_foto->execute();
    $resultado_foto = $stmt_foto->get_result();
    
    if ($resultado_foto && $resultado_foto->num_rows > 0) {
        $admin_foto = $resultado_foto->fetch_assoc();
        $foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;
    }
    $stmt_foto->close();
}

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
     <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
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

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--cinza-claro);
    color: var(--preto-texto);
    
}

@keyframes fadeIn {
    from { opacity: 0; } to { opacity: 1; }
}

.logout-icon {
    color: var(--roxo-principal) !important;
    transition: all 0.3s ease;
    text-decoration: none;
}

.navbar {
    background-color: transparent !important;
    border-bottom: 3px solid var(--amarelo-detalhe);
    box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
}

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
/* Sidebar */
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
    transition: transform 0.3s ease-in-out;
}

/* Menu Hamburguer */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--roxo-principal) !important;
    font-size: 1.5rem;
    cursor: pointer;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    transition: all 0.3s ease;
}

.menu-toggle:hover {
    color: var(--roxo-escuro) !important;
    transform: scale(1.1);
}

/* CORREÇÃO: Quando a sidebar está ativa */
body:has(.sidebar.active) .menu-toggle,
.sidebar.active ~ .menu-toggle {
    color: var(--amarelo-detalhe) !important;
}

body:has(.sidebar.active) .menu-toggle:hover,
.sidebar.active ~ .menu-toggle:hover {
    color: var(--amarelo-hover) !important;
}

/* Overlay para mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

@media (max-width: 992px) {
    .menu-toggle {
        display: block;
    }
    
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

.sidebar .profile {
    text-align: center;
    margin-bottom: 30px;
    padding: 0 15px;
}

.profile-avatar-sidebar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid var(--amarelo-detalhe);
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.profile-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-avatar-sidebar:has(.profile-avatar-img) i {
    display: none;
}

.profile-avatar-sidebar i {
    font-size: 3.5rem;
    color: var(--amarelo-detalhe);
}

.sidebar .profile h5 {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--branco);
    font-size: 1.1rem;
    word-wrap: break-word;
    max-width: 200px;
    text-align: center;
    line-height: 1.3;
}

.sidebar .profile small {
    color: var(--cinza-claro);
    font-size: 0.9rem;
    word-wrap: break-word;
    max-width: 200px;
    text-align: center;
    line-height: 1.2;
    margin-top: 5px;
}

.sidebar .list-group {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.sidebar .list-group-item.sair {
    background-color: transparent;
    color: var(--branco);
    border: none;
    padding: 15px 25px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 40px !important;
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
    text-decoration: none;
}

.sidebar .list-group-item:hover {
    background-color: var(--roxo-escuro);
    cursor: pointer;
    transform: translateX(5px);
}

.sidebar .list-group-item.active {
    background-color: var(--roxo-escuro) !important;
    color: var(--branco) !important;
    font-weight: 600;
    border-left: 4px solid var(--amarelo-detalhe);
}

.sidebar .list-group-item i {
    color: var(--amarelo-detalhe);
    width: 20px;
    text-align: center;
}

/* bottom-nav removido (usamos menu hambúrguer para mobile) */

/* Ajustes de layout para diferentes tamanhos de tela */
@media (min-width: 992px) {
    .main-content {
        margin-left: 250px;
        padding: 20px;
    }
}

@media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
            padding: 20px; /* Ajusta padding para não reservar espaço para a bottom-nav removida */
    }
    /* A sidebar em telas pequenas agora é off-canvas (transform) e
       controlada pelo botão hambúrguer. Não usar display:none aqui. */
}

/* Efeito de brilho para o botão Adicionar Nova Unidade */
.btn-warning {
    background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
    color: var(--preto-texto);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    min-width: 180px;
    border: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-warning::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    transition: left 0.7s ease;
}

.btn-warning:hover::before {
    left: 100%;
}

.btn-warning:hover {
    background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
    color: var(--preto-texto);
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

/* Estilo fixo e elegante para o header da tabela */
.table thead th {
    background: linear-gradient(145deg, #6a0dad, #5a0b9a);
    color: var(--branco);
    border: none;
    font-weight: 600;
    padding: 16px 15px;
    text-align: center;
    font-size: 0.9rem;
    letter-spacing: 0.3px;
    border-bottom: 2px solid var(--amarelo-detalhe);
    position: relative;
    transition: all 0.2s ease;
}

.table thead th:first-child {
    border-top-left-radius: 10px;
}

.table thead th:last-child {
    border-top-right-radius: 10px;
}

/* Efeito sutil de profundidade */
.table thead th {
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1),
                0 2px 4px rgba(0, 0, 0, 0.1);
}



/* Ícones nos headers */
.table thead th i {
    margin-right: 6px;
    color: var(--amarelo-detalhe);
    font-size: 0.85em;
}

/* Borda inferior fixa */
.table thead tr {
    border-bottom: 3px solid var(--amarelo-detalhe);
}

/* Sombra suave para a tabela */
.table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    background-color: var(--branco);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(106, 13, 173, 0.08);
    margin-bottom: 0;
    border: 1px solid rgba(106, 13, 173, 0.08);
}

.table tbody td {
    padding: 14px 15px;
    border-bottom: 1px solid rgba(106, 13, 173, 0.08);
    text-align: center;
    vertical-align: middle;
    transition: background-color 0.2s ease;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(106, 13, 173, 0.02);
}

.table-hover tbody tr:hover {
    background-color: rgba(106, 13, 173, 0.05);
}

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

.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.badge {
    font-weight: 600;
    padding: 0.5em 1em;
    border-radius: 50px;
}

.badge.bg-primary {
    background-color: var(--roxo-principal) !important;
}

.badge.bg-warning {
    background-color: var(--amarelo-detalhe) !important;
    color: var(--preto-texto);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(106, 13, 173, 0.2);
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.unidades-table {
    background: var(--branco);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 20px #ab4aef63;
    border: none;
}

.settings-icon {
    color: var(--roxo-principal) !important;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1.2rem;
}

.settings-icon:hover {
    color: var(--roxo-escuro) !important;
    transform: rotate(90deg);
}

.empty-state {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
}

.empty-state i {
    font-size: 2.5rem;
    color: var(--cinza-medio);
}

/* Efeitos para botões de ação na tabela */
.btn-group-sm .btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

/* Botão Editar - versão minimalista */
.btn-primary {
    background: transparent;
    color: var(--roxo-principal);
    border: 2px solid #6a0dad;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 6px;
    position: relative;
    transition: background 0.12s ease, color 0.12s ease, transform 0.12s ease;
}

.btn-primary:hover {
    background: rgba(106, 13, 173, 0.06);
    color: var(--roxo-principal);
    border: 2px solid #6a0dad;
    transform: translateY(-1px);
}

/* Botão Eliminar - Efeito de pulsação vermelha */
.btn-danger {
    /* refinado: aviso sem ser agressivo, formato pill */
    background: rgba(220, 53, 69, 0.06);
    color: #8a1820; /* tom menos saturado */
    /* borda fina e cor firme */
    border: 2px solid #c82333;
    box-sizing: border-box;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 999px;
    transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.12s ease;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.04);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-danger:hover {
    background: rgba(220, 53, 69, 0.12);
    color: #7a151b;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(220, 53, 69, 0.08);
}

/* Efeito de ícones nos botões */
.btn-primary i, .btn-danger i {
    transition: transform 0.3s ease;
}

.btn-primary:hover i {
    transform: scale(1.2) rotate(-5deg);
}

.btn-danger:hover i {
    transform: scale(1.2) rotate(10deg);
}

/* Container dos botões de ação */
.btn-group-sm {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem;
    overflow: hidden;
}

/* Responsividade para os botões */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }
    
    .btn-group-sm .btn i {
        margin-right: 2px;
    }
    
    .table thead th {
        padding: 12px 8px;
        font-size: 0.8rem;
    }
    
    .table thead th i {
        margin-right: 4px;
        font-size: 0.75em;
    }
    
    .table tbody td {
        padding: 10px 8px;
        font-size: 0.85rem;
    }
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.3rem;
}

/* Otimização da visualização da tabela */
.table-unidades .col-nome-unidade,
.table-unidades .col-descricao {
    max-width: 180px; /* Limita a largura */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* Adiciona reticências */
    text-align: left;
}
.table-unidades .col-idioma {
    white-space: nowrap;
}

@media (max-width: 576px) {
    .page-header { flex-direction: column; align-items: flex-start; }
}
    </style>
<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu Hamburguer Functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Fechar menu ao clicar em um link (mobile)
        const sidebarLinks = sidebar.querySelectorAll('.list-group-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        });
    }
});
</script>

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
                <a href="logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

        <!-- Menu Hamburguer -->
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Overlay para mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="profile">
        <?php if ($foto_admin): ?>
            <div class="profile-avatar-sidebar">
                <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
            </div>
        <?php else: ?>
            <div class="profile-avatar-sidebar">
                <i class="fa-solid fa-user" style="color: var(--amarelo-detalhe); font-size: 3.5rem;"></i>
            </div>
        <?php endif; ?>
        <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
        <small>Administrador(a)</small>
    </div>

    <div class="list-group">
        <a href="gerenciar_caminho.php" class="list-group-item ">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item">
            <i class="fas fa-language"></i> Gerenciar Idiomas
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
    </div>
</div>


    <div class="main-content">
        <div class="container mt-4">            
            <div class="page-header flex-column flex-sm-row">
                <h2 class="mb-0"><i class="fas fa-cubes"></i> Gerenciar Unidades</h2>
                <div class="action-buttons">
                    <a href="adicionar_unidade.php" class="btn btn-warning">
                        <i class="fas fa-plus-circle"></i> Adicionar Nova Unidade
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="unidades-table">
                <div class="table-responsive table-responsive-sm">
                    <table class="table table-striped table-hover table-unidades">
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
                                    <td class="col-nome-unidade" title="<?= htmlspecialchars($unidade['nome_unidade']); ?>">
                                        <div class="fw-bold"><?= htmlspecialchars($unidade['nome_unidade']); ?></div>
                                    </td>
                                    <td class="col-idioma">
                                        <span class="badge bg-primary"><?= htmlspecialchars($unidade['nome_idioma']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($unidade['nivel']): ?>
                                            <span class="badge bg-warning"><?= htmlspecialchars($unidade['nivel']); ?></span>
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
                                    <td class="col-descricao" data-full="<?= htmlspecialchars($unidade['descricao']); ?>" title="<?= htmlspecialchars($unidade['descricao']); ?>">
                                        <small class="text-muted"><?= htmlspecialchars($unidade['descricao']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar_unidade.php?id=<?= $unidade['id']; ?>" class="btn btn-primary">
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

    <!-- bottom-nav removido; mobile usa menu hambúrguer -->

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