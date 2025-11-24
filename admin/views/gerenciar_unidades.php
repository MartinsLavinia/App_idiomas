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

/* Modal de Confirmação de Exclusão */
#confirmDeleteModal .modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(106, 13, 173, 0.2);
}

#confirmDeleteModal .modal-header {
    background: var(--roxo-principal);
    color: var(--branco);
    border-bottom: none;
    padding: 1.5rem;
}

#confirmDeleteModal .btn-close {
    filter: brightness(0) invert(1);
    opacity: 1;
}

#confirmDeleteModal .modal-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#confirmDeleteModal .modal-title::before {
    content: '⚠️';
    font-size: 1.2rem;
}

#confirmDeleteModal .modal-body {
    padding: 1.5rem;
    text-align: center;
}

#confirmDeleteModal .modal-body .text-danger {
    background: rgba(255, 215, 0, 0.1);
    border-left: 3px solid var(--amarelo-detalhe);
    padding: 0.75rem;
    border-radius: 5px;
    color: var(--roxo-escuro);
    font-weight: 600;
}

#confirmDeleteModal .modal-footer {
    border-top: 1px solid var(--cinza-medio);
    padding: 1rem 1.5rem;
}

#confirmDeleteModal .btn-secondary {
    background: var(--cinza-medio);
    border: none;
    color: var(--preto-texto);
    font-weight: 600;
    transition: all 0.3s ease;
}

#confirmDeleteModal .btn-secondary:hover {
    background: #6c757d;
    color: var(--branco);
}

#confirmDeleteModal .btn-danger {
    background: #b02a37;
    border: none;
    color: var(--branco);
    font-weight: 600;
    border-radius: 4px;
    transition: all 0.3s ease;
}

#confirmDeleteModal .btn-danger:hover {
    background: #a02332;
    color: var(--branco);
}

/* Modal de Adicionar Unidade */
#addUnidadeModal .modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(106, 13, 173, 0.2);
}

#addUnidadeModal .modal-header {
    background: var(--roxo-principal);
    color: var(--branco);
    border-bottom: none;
    padding: 1.5rem;
}

#addUnidadeModal .btn-close {
    filter: brightness(0) invert(1);
    opacity: 1;
}

#addUnidadeModal .modal-title {
    font-weight: 600;
}

#addUnidadeModal .modal-body {
    padding: 1.5rem;
}

#addUnidadeModal .modal-footer {
    border-top: 1px solid var(--cinza-medio);
    padding: 1rem 1.5rem;
}

#addUnidadeModal .btn-secondary {
    background: var(--cinza-medio);
    border: none;
    color: var(--preto-texto);
    font-weight: 600;
    transition: all 0.3s ease;
}

#addUnidadeModal .btn-secondary:hover {
    background: #6c757d;
    color: var(--branco);
}

#addUnidadeModal .btn-warning {
    background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
    border: none;
    color: var(--preto-texto);
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

#addUnidadeModal .btn-warning:hover {
    background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(255, 217, 0, 0.4);
    color: var(--preto-texto);
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
    <div class="container-fluid d-flex justify-content-end align-items-center">
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
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addUnidadeModal" onclick="console.log('Botão clicado')">
                        <i class="fas fa-plus-circle"></i> Adicionar Nova Unidade
                    </button>
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
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= $unidade['id']; ?>" data-nome="<?= htmlspecialchars($unidade['nome_unidade']); ?>">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
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

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a unidade <strong id="unidadeNome"></strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Adicionar Unidade -->
    <div class="modal fade" id="addUnidadeModal" tabindex="-1" aria-labelledby="addUnidadeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnidadeModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>
                        Adicionar Nova Unidade
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formAddUnidade" action="adicionar_unidade.php" method="POST">
                    <div class="modal-body">
                        <div id="alertUnidade"></div>
                        <div class="mb-3">
                            <label for="nome_unidade" class="form-label">Nome da Unidade</label>
                            <input type="text" class="form-control" id="nome_unidade" name="nome_unidade" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="nivel" class="form-label">Nível</label>
                            <select class="form-select" id="nivel" name="nivel">
                                <option value="">Selecione o nível</option>
                                <option value="A1">A1 - Iniciante</option>
                                <option value="A2">A2 - Básico</option>
                                <option value="B1">B1 - Intermediário</option>
                                <option value="B2">B2 - Intermediário Superior</option>
                                <option value="C1">C1 - Avançado</option>
                                <option value="C2">C2 - Proficiente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="numero_unidade" class="form-label">Número da Unidade</label>
                            <input type="number" class="form-control" id="numero_unidade" name="numero_unidade" min="1">
                        </div>
                        <div class="mb-3">
                            <label for="id_idioma" class="form-label">Idioma</label>
                            <select class="form-select" id="id_idioma" name="id_idioma" required>
                                <option value="">Selecione um idioma</option>
                                <?php
                                // Buscar idiomas para o modal
                                $database_modal = new Database();
                                $conn_modal = $database_modal->conn;
                                $sql_idiomas_modal = "SELECT id, nome_idioma FROM idiomas ORDER BY nome_idioma";
                                $result_idiomas_modal = $conn_modal->query($sql_idiomas_modal);
                                if ($result_idiomas_modal && $result_idiomas_modal->num_rows > 0) {
                                    while ($idioma = $result_idiomas_modal->fetch_assoc()) {
                                        echo '<option value="' . $idioma['id'] . '">' . htmlspecialchars($idioma['nome_idioma']) . '</option>';
                                    }
                                }
                                $database_modal->closeConnection();
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning" id="btnAddUnidade">
                            <i class="fas fa-save me-2"></i>Adicionar Unidade
                        </button>
                    </div>
                </form>
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
            
            // Debug: verificar se modal existe
            const modal = document.getElementById('addUnidadeModal');
            console.log('Modal encontrado:', modal ? 'Sim' : 'Não');
            
            // Modal de confirmação de exclusão
            const confirmDeleteModal = document.getElementById('confirmDeleteModal');
            if (confirmDeleteModal) {
                confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const unidadeId = button.getAttribute('data-id');
                    const unidadeNome = button.getAttribute('data-nome');
                    
                    document.getElementById('unidadeNome').textContent = unidadeNome;
                    document.getElementById('confirmDeleteBtn').href = 'eliminar_unidade.php?id=' + unidadeId;
                });
            }
            
            // Formulário de adicionar unidade via AJAX
            const formAddUnidade = document.getElementById('formAddUnidade');
            if (formAddUnidade) {
                formAddUnidade.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const btnAddUnidade = document.getElementById('btnAddUnidade');
                    const alertUnidade = document.getElementById('alertUnidade');
                    
                    btnAddUnidade.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adicionando...';
                    btnAddUnidade.disabled = true;
                    alertUnidade.innerHTML = '';
                    
                    fetch('adicionar_unidade.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alertUnidade.innerHTML = `
                                <div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle me-2"></i>${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                            formAddUnidade.reset();
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            alertUnidade.innerHTML = `
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-circle me-2"></i>${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        alertUnidade.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>Erro ao adicionar unidade. Tente novamente.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                    })
                    .finally(() => {
                        btnAddUnidade.innerHTML = '<i class="fas fa-save me-2"></i>Adicionar Unidade';
                        btnAddUnidade.disabled = false;
                    });
                });
            }
        });
    </script>

      
    <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
      <div class="vw-plugin-top-wrapper"></div>
    </div>
  </div>
  <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>


  
 <style>
        /* Botão de Acessibilidade */
        .accessibility-widget {
            position: fixed;
            bottom: 50px;
            right: 5px;
            z-index: 10000;
            font-family: 'arial';
        }

        .accessibility-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .accessibility-toggle:hover, .accessibility-toggle:focus-visible {
            outline: none;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(45, 62, 143, 1);
        }

        .accessibility-panel {
            position: absolute;
            bottom: 60px;
            right: 0;
            width: 320px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            padding: 10px 15px 15px 15px;
            font-size: 14px;
            z-index: 10001;
            color: #222;
        }

        .accessibility-header {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            padding: 12px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        .accessibility-header h3 {
            margin: 0;
            color: white;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover, .close-btn:focus-visible {
            background: rgba(255, 255, 255, 0.25);
            outline: none;
        }

        /* GRID DOS BOTÕES - TAMANHO CONSISTENTE */
        .accessibility-options {
            padding: 10px 5px 0 5px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: 95px;
            gap: 10px;
            justify-items: stretch;
        }

        .option-btn {
            background: white;
            border: 2px solid #d5d9db;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            padding: 8px 6px;
            font-size: 13px;
            color: #2d3e8f;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            user-select: none;
            box-shadow: 0 1px 1px rgb(0 0 0 / 0.05);
            font-weight: 600;
            height: 95px;
            min-height: 95px;
            max-height: 95px;
            width: 100%;
            box-sizing: border-box;
            gap: 0;
        }

        .option-btn i {
            font-size: 28px;
            margin-bottom: 0;
            color: #2d3e8f;
            flex-shrink: 0;
            line-height: 1;
        }

        .option-btn:hover, .option-btn:focus-visible {
            background: #e1e8f8;
            border-color: #1a2980;
            box-shadow: 0 2px 6px rgb(26 41 128 / 0.25);
            outline: none;
            transform: translateY(-2px);
        }

        .option-btn[aria-pressed="true"] {
            background: #3952a3;
            color: white;
            border-color: #1a2980;
        }

        .option-btn[aria-pressed="true"] i {
            color: white;
        }

        .reset-btn {
            background: #f5f5f7;
            border-color: #c9c9d7;
            color: #71717a;
        }

        .reset-btn:hover, .reset-btn:focus-visible {
            background: #d6d6e1;
            border-color: #71717a;
            color: #1a1a28;
        }

        /* CONTAINERS E SUBMENUS */
        .option-btn-container {
            position: relative;
            height: 95px;
        }

        /* SUBMENUS ESTILIZADOS */
        .submenu {
            display: none;
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            z-index: 10002;
            width: 280px;
            top: -150px;
            left: 0;
            border: 2px solid #e1e8f8;
        }

        .submenu.active {
            display: block;
        }

        .submenu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #2d3e8f;
            margin-bottom: 12px;
            font-size: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e1e8f8;
        }

        .submenu-close {
            background: none;
            border: none;
            color: #2d3e8f;
            font-size: 14px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .submenu-close:hover {
            background: #e1e8f8;
            color: #3952a3;
        }

        /* CONTROLES DESLIZANTES NOS SUBMENUS */
        .slider-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            margin: 15px 0;
        }

        .slider-btn {
            background: #e1e8f8;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #2d3e8f;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .slider-btn:hover {
            background: #3952a3;
            color: white;
            border-color: #2d3e8f;
        }

        .slider-wrapper {
            flex: 1;
            position: relative;
        }

        .slider-track {
            position: relative;
            height: 8px;
            background: #e1e8f8;
            border-radius: 4px;
            overflow: visible;
        }

        .slider-fill {
            position: absolute;
            height: 100%;
            background: linear-gradient(90deg, #2d3e8f, #3952a3);
            border-radius: 4px;
            width: 0%;
            transition: width 0.2s ease;
        }

        /* SLIDER COM BOLINHA VISÍVEL */
        .slider {
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
            opacity: 1;
            -webkit-appearance: none;
            background: transparent;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-webkit-slider-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-moz-range-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider-value {
            font-size: 12px;
            font-weight: 600;
            color: #2d3e8f;
            text-align: center;
            margin-top: 8px;
        }

        /* BOTÕES DO SUBMENU DE ALINHAMENTO */
        .submenu-btn {
            width: 100%;
            padding: 10px 12px;
            margin: 6px 0;
            background: white;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2d3e8f;
        }

        .submenu-btn:hover {
            background: #e1e8f8;
            border-color: #2d3e8f;
        }

        .submenu-btn i {
            font-size: 14px;
            width: 16px;
        }

        /* CLASSES PARA FUNCIONALIDADES */
        /* MODO DE ALTO CONTRASTE APENAS COM AMARELO/PRETO */
        .contrast-mode {
            background-color: #000000 !important;
            color: #ffff00 !important;
        }

        .contrast-mode * {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border-color: #ffff00 !important;
        }

        .contrast-mode a {
            color: #ffff00 !important;
            text-decoration: underline !important;
        }

        .contrast-mode button,
        .contrast-mode input,
        .contrast-mode select,
        .contrast-mode textarea {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode img {
            filter: grayscale(100%) contrast(150%) !important;
        }

        .contrast-mode .accessibility-panel {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn:hover,
        .contrast-mode .option-btn:focus-visible {
            background-color: #ffff00 !important;
            color: #000000 !important;
        }

        .highlight-links a, .highlight-links button {
            outline: 2px solid #00ffff !important;
            box-shadow: 0 0 8px #00ffff !important;
            position: relative;
        }

        .pause-animations * {
            animation-play-state: paused !important;
            transition: none !important;
        }

        @import url('https://fonts.googleapis.com/css2?family=Open+Dyslexic&display=swap');

        .dyslexia-friendly {
            font-family: 'Open Dyslexic', Arial, sans-serif !important;
            letter-spacing: 0.12em !important;
            word-spacing: 0.2em !important;
        }

        .text-spacing {
            letter-spacing: 0.12em !important;
            word-spacing: 0.3em !important;
        }

        .text-align-left * {
            text-align: left !important;
        }

        .text-align-center * {
            text-align: center !important;
        }

        .text-align-justify * {
            text-align: justify !important;
        }

        .tooltip-enabled a[title], .tooltip-enabled button[title] {
            position: relative;
            outline: none;
        }

        .tooltip-enabled a[title]:hover::after,
        .tooltip-enabled button[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3e8f;
            color: white;
            padding: 5px 8px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 11px;
            z-index: 2000;
            opacity: 0.95;
            pointer-events: none;
            font-weight: 600;
        }

        .accessibility-widget.moved {
            right: auto !important;
            left: 20px !important;
            top: 20px !important;
            bottom: auto !important;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 400px) {
            .accessibility-widget {
                right: 5px;
                width: 300px;
            }
            
            .accessibility-panel {
                width: 300px;
            }
            
            .submenu {
                width: 260px;
                left: -130px;
            }
        }

        /* Estilo para o botão de parar leitura */
        #stop-reading-btn {
            background: #dc3545 !important;
            color: white !important;
            border-color: #dc3545 !important;
        }

        #stop-reading-btn:hover {
            background: #c82333 !important;
            border-color: #bd2130 !important;
        }

        #stop-reading-btn i {
            color: white !important;
        }

        /* Feedback visual para leitura ativa */
        .reading-active {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
</head>
<body>
  

    <!-- Botão de Acessibilidade -->
    <div id="accessibility-widget" class="accessibility-widget" aria-label="Menu de acessibilidade">
        <button id="accessibility-toggle" class="accessibility-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="accessibility-panel" aria-label="Abrir menu de acessibilidade">
            <i class="fas fa-universal-access" aria-hidden="true"></i>
        </button>
        <div id="accessibility-panel" class="accessibility-panel" role="dialog" aria-modal="true" aria-labelledby="accessibility-title" tabindex="-1" hidden>
            <div class="accessibility-header">
                <h3 id="accessibility-title">Menu de Acessibilidade (CTRL+U)</h3>
                <button id="close-panel" class="close-btn" aria-label="Fechar menu de acessibilidade">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="accessibility-options grid">
                <button class="option-btn" data-action="contrast" aria-pressed="false" title="Contraste + (Alt+1)">
                    <i class="fas fa-adjust" aria-hidden="true"></i><br> Contraste +
                </button>
                <button class="option-btn" data-action="highlight-links" aria-pressed="false" title="Destacar links (Alt+2)">
                    <i class="fas fa-link" aria-hidden="true"></i><br> Destacar links
                </button>
                
                <!-- Botão de fonte com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="font-size-btn" title="Tamanho da fonte (Alt+3)">
                        <i class="fas fa-text-height" aria-hidden="true"></i><br> Tamanho da fonte
                    </button>
                    <div class="font-submenu submenu" id="font-submenu">
                        <div class="submenu-header">
                            <span>Tamanho da Fonte</span>
                            <button class="submenu-close" id="font-close" aria-label="Fechar menu de fonte">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-controls">
                            <button class="slider-btn" id="font-decrease" title="Diminuir fonte">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                            <div class="slider-wrapper">
                                <div class="slider-track">
                                    <div class="slider-fill" id="font-fill"></div>
                                    <input type="range" id="font-slider" class="slider" min="0" max="32" value="0" step="2">
                                </div>
                            </div>
                            <button class="slider-btn" id="font-increase" title="Aumentar fonte">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-value" id="font-value">Original</div>
                    </div>
                </div>
                
                <button class="option-btn" data-action="text-spacing" aria-pressed="false" title="Espaçamento texto (Alt+4)">
                    <i class="fas fa-arrows-alt-h" aria-hidden="true"></i><br> Espaçamento texto
                </button>
                <button class="option-btn" data-action="pause-animations" aria-pressed="false" title="Pausar animações (Alt+5)">
                    <i class="fas fa-pause-circle" aria-hidden="true"></i><br> Pausar animações
                </button>
                <button class="option-btn" data-action="dyslexia-friendly" aria-pressed="false" title="Modo dislexia (Alt+6)">
                    <i class="fas fa-font" aria-hidden="true"></i><br> Modo dislexia
                </button>
                
                <!-- Botão de leitura de página -->
                <button class="option-btn" id="read-page-btn" title="Ler página (Alt+7)">
                    <i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página
                </button>
                
                <button class="option-btn" data-action="tooltips" aria-pressed="false" title="Tooltips (Alt+8)">
                    <i class="fas fa-info-circle" aria-hidden="true"></i><br> Tooltips
                </button>
                
                <!-- Botão de alinhamento com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="align-btn" title="Alinhar texto (Alt+0)">
                        <i class="fas fa-align-left" aria-hidden="true"></i><br> Alinhar texto
                    </button>
                    <div class="align-submenu submenu" id="align-submenu">
                        <div class="submenu-header">
                            <span>Alinhar Texto</span>
                            <button class="submenu-close" id="align-close" aria-label="Fechar menu de alinhamento">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <button class="submenu-btn" data-action="text-align-original">
                            <i class="fas fa-undo"></i> Original
                        </button>
                        <button class="submenu-btn" data-action="text-align-left">
                            <i class="fas fa-align-left"></i> Alinhar à esquerda
                        </button>
                        <button class="submenu-btn" data-action="text-align-center">
                            <i class="fas fa-align-center"></i> Alinhar ao centro
                        </button>
                        <button class="submenu-btn" data-action="text-align-justify">
                            <i class="fas fa-align-justify"></i> Justificar
                        </button>
                    </div>
                </div>
                
                <button class="option-btn reset-btn" data-action="reset-all" title="Redefinir tudo">
                    <i class="fas fa-undo" aria-hidden="true"></i><br> Redefinir tudo
                </button>
                <button class="option-btn" data-action="move-hide" title="Mover/Ocultar menu">
                    <i class="fas fa-arrows-alt" aria-hidden="true"></i><br> Mover/Ocultar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const widget = document.getElementById('accessibility-widget');
            const toggleBtn = document.getElementById('accessibility-toggle');
            const panel = document.getElementById('accessibility-panel');
            const closeBtn = document.getElementById('close-panel');
            const optionBtns = document.querySelectorAll('.option-btn');
            const submenuBtns = document.querySelectorAll('.submenu-btn');
            
            // Elementos dos controles deslizantes
            const fontSlider = document.getElementById('font-slider');
            const fontFill = document.getElementById('font-fill');
            const fontValue = document.getElementById('font-value');
            const fontDecrease = document.getElementById('font-decrease');
            const fontIncrease = document.getElementById('font-increase');
            const fontBtn = document.getElementById('font-size-btn');
            const fontSubmenu = document.getElementById('font-submenu');
            const fontClose = document.getElementById('font-close');
            
            const alignBtn = document.getElementById('align-btn');
            const alignSubmenu = document.getElementById('align-submenu');
            const alignClose = document.getElementById('align-close');

            // Botões de leitura
            const readPageBtn = document.getElementById('read-page-btn');
            let speechSynthesis = window.speechSynthesis;
            let isReading = false;
            let currentUtterance = null;
            let userStopped = false;

            // Estado para fonte (0 = tamanho original)
            let fontSize = parseInt(localStorage.getItem('fontSize')) || 0;

            // Estado dos botões com toggle
            let states = {
                contrast: false,
                highlightLinks: false,
                textSpacing: false,
                pauseAnimations: false,
                dyslexiaFriendly: false,
                tooltips: false,
                textAlign: 'original'
            };

            // Função para atualizar o preenchimento do slider
            function updateSliderFill(slider, fill) {
                const value = slider.value;
                const min = slider.min;
                const max = slider.max;
                const percentage = ((value - min) / (max - min)) * 100;
                fill.style.width = percentage + '%';
            }

            // Inicializar sliders
            function initializeSliders() {
                updateSliderFill(fontSlider, fontFill);
                updateFontValue();
            }

            // Atualizar valor exibido da fonte
            function updateFontValue() {
                if (fontSize === 0) {
                    fontValue.textContent = 'Original';
                } else {
                    fontValue.textContent = fontSize + 'px';
                }
            }

            // Função para garantir tamanho consistente dos botões
            function enforceConsistentButtonSizes() {
                const optionBtns = document.querySelectorAll('.option-btn');
                const containers = document.querySelectorAll('.option-btn-container');
                
                optionBtns.forEach(btn => {
                    btn.style.height = '95px';
                    btn.style.minHeight = '95px';
                    btn.style.maxHeight = '95px';
                });
                
                containers.forEach(container => {
                    container.style.height = '95px';
                    container.style.minHeight = '95px';
                });
            }

            // Mostra ou esconde painel e atualiza aria-expanded
            function togglePanel(show) {
                if (show) {
                    panel.hidden = false;
                    panel.classList.add('active');
                    toggleBtn.setAttribute('aria-expanded', 'true');
                    panel.focus();
                    setTimeout(enforceConsistentButtonSizes, 10);
                } else {
                    panel.hidden = true;
                    panel.classList.remove('active');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    closeAllSubmenus();
                }
            }

            toggleBtn.addEventListener('click', () => {
                const isActive = !panel.hidden;
                togglePanel(!isActive);
            });
            
            closeBtn.addEventListener('click', () => togglePanel(false));

            // Fecha painel clicando fora
            document.addEventListener('click', e => {
                if (!widget.contains(e.target) && !panel.hidden) {
                    togglePanel(false);
                }
            });

            // Navegação pelo teclado no painel: ESC para fechar
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !panel.hidden) {
                    togglePanel(false);
                    toggleBtn.focus();
                }
            });

            // Eventos para os botões principais
            optionBtns.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const action = this.getAttribute('data-action');
                    
                    // Verificar se é um botão com submenu
                    if (this.id === 'font-size-btn') {
                        toggleSubmenu(fontSubmenu);
                    } else if (this.id === 'align-btn') {
                        toggleSubmenu(alignSubmenu);
                    } else {
                        handleAccessibilityAction(action, this);
                    }
                });
            });

            // Evento para o botão de ler página
            readPageBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!isReading) {
                    startReading();
                } else {
                    userStopped = true;
                    stopReading();
                }
            });

            // Função para iniciar leitura da página
            function startReading() {
                if (!speechSynthesis) {
                    console.log('Seu navegador não suporta leitura de texto.');
                    return;
                }

                // Parar qualquer leitura anterior
                stopReading();

                // Obter todo o texto da página
                const pageText = getPageText();
                
                if (!pageText.trim()) {
                    console.log('Nenhum texto encontrado para ler.');
                    return;
                }

                // Criar utterance
                currentUtterance = new SpeechSynthesisUtterance(pageText);
                currentUtterance.lang = 'pt-BR';
                currentUtterance.rate = 0.8;
                currentUtterance.pitch = 1;
                currentUtterance.volume = 1;

                // Resetar flag
                userStopped = false;

                // Atualizar interface
                isReading = true;
                readPageBtn.innerHTML = '<i class="fas fa-stop" aria-hidden="true"></i><br> Parar leitura';
                readPageBtn.id = 'stop-reading-btn';
                readPageBtn.classList.add('reading-active');

                // Evento quando a leitura terminar
                currentUtterance.onend = function() {
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Evento quando ocorrer erro - apenas log, sem alert
                currentUtterance.onerror = function(event) {
                    console.log('Erro na leitura:', event.error);
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Iniciar leitura
                speechSynthesis.speak(currentUtterance);
            }

            // Função para parar leitura
            function stopReading() {
                if (speechSynthesis && isReading) {
                    speechSynthesis.cancel();
                }
                
                isReading = false;
                currentUtterance = null;
                readPageBtn.innerHTML = '<i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página';
                readPageBtn.id = 'read-page-btn';
                readPageBtn.classList.remove('reading-active');
            }

            // Função para obter texto da página (excluindo elementos irrelevantes)
            function getPageText() {
                // Clonar o body para não modificar o DOM original
                const clone = document.body.cloneNode(true);
                
                // Remover elementos que não devem ser lidos
                const elementsToRemove = clone.querySelectorAll(
                    'script, style, nav, header, footer, .accessibility-widget, [aria-hidden="true"]'
                );
                elementsToRemove.forEach(el => el.remove());
                
                // Obter texto limpo
                return clone.textContent.replace(/\s+/g, ' ').trim();
            }

            // Eventos para os botões dos submenus
            submenuBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const action = this.getAttribute('data-action');
                    handleAccessibilityAction(action, this);
                    closeAllSubmenus();
                });
            });

            // Botões de fechar nos submenus
            fontClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            alignClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            // Funções para controlar submenus
            function toggleSubmenu(submenu) {
                closeAllSubmenus();
                submenu.classList.add('active');
            }

            function closeAllSubmenus() {
                fontSubmenu.classList.remove('active');
                alignSubmenu.classList.remove('active');
            }

            // Fechar submenus ao clicar fora deles
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.option-btn-container')) {
                    closeAllSubmenus();
                }
            });

            // Controle deslizante de fonte
            fontSlider.value = fontSize;
            
            fontSlider.addEventListener('input', function() {
                fontSize = parseInt(this.value);
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontDecrease.addEventListener('click', function() {
                fontSize = Math.max(parseInt(fontSlider.min), fontSize - 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontIncrease.addEventListener('click', function() {
                fontSize = Math.min(parseInt(fontSlider.max), fontSize + 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            function applyFontSize() {
                const elements = document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, a, span, li, label, button, div');
                
                if (fontSize === 0) {
                    // Volta ao tamanho original
                    elements.forEach(el => {
                        el.style.fontSize = '';
                    });
                } else {
                    // Aplica o tamanho personalizado
                    elements.forEach(el => {
                        el.style.fontSize = fontSize + 'px';
                    });
                }
                localStorage.setItem('fontSize', fontSize);
            }

            function applyTextAlign() {
                // Remove todas as classes de alinhamento
                document.body.classList.remove('text-align-left', 'text-align-center', 'text-align-justify');
                
                if (states.textAlign !== 'original') {
                    document.body.classList.add(states.textAlign);
                }
            }

            function handleAccessibilityAction(action, btn) {
                const body = document.body;
                switch (action) {
                    case 'contrast':
                        states.contrast = !states.contrast;
                        body.classList.toggle('contrast-mode', states.contrast);
                        btn.setAttribute('aria-pressed', states.contrast);
                        break;

                    case 'highlight-links':
                        states.highlightLinks = !states.highlightLinks;
                        body.classList.toggle('highlight-links', states.highlightLinks);
                        btn.setAttribute('aria-pressed', states.highlightLinks);
                        break;

                    case 'text-spacing':
                        states.textSpacing = !states.textSpacing;
                        body.classList.toggle('text-spacing', states.textSpacing);
                        btn.setAttribute('aria-pressed', states.textSpacing);
                        break;

                    case 'pause-animations':
                        states.pauseAnimations = !states.pauseAnimations;
                        body.classList.toggle('pause-animations', states.pauseAnimations);
                        btn.setAttribute('aria-pressed', states.pauseAnimations);
                        break;

                    case 'dyslexia-friendly':
                        states.dyslexiaFriendly = !states.dyslexiaFriendly;
                        body.classList.toggle('dyslexia-friendly', states.dyslexiaFriendly);
                        btn.setAttribute('aria-pressed', states.dyslexiaFriendly);
                        break;

                    case 'tooltips':
                        states.tooltips = !states.tooltips;
                        body.classList.toggle('tooltip-enabled', states.tooltips);
                        btn.setAttribute('aria-pressed', states.tooltips);
                        break;

                    case 'text-align-original':
                        states.textAlign = 'original';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-left':
                        states.textAlign = 'text-align-left';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-center':
                        states.textAlign = 'text-align-center';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-justify':
                        states.textAlign = 'text-align-justify';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;

                    case 'reset-all':
                        resetAll();
                        break;

                    case 'move-hide':
                        const moved = widget.classList.toggle('moved');
                        if (moved) {
                            btn.style.backgroundColor = '#fbbf24';
                        } else {
                            btn.style.backgroundColor = '';
                        }
                        break;
                }
            }

            function resetAll() {
                // Parar leitura se estiver ativa
                userStopped = true;
                stopReading();
                
                // Remove todas as classes de acessibilidade
                document.body.className = '';
                
                // Remove todos os estilos inline
                document.querySelectorAll('*').forEach(el => {
                    el.style.fontSize = '';
                    el.style.lineHeight = '';
                    el.style.letterSpacing = '';
                    el.style.wordSpacing = '';
                    el.style.textAlign = '';
                    el.style.fontFamily = '';
                });
                
                // Reseta estados
                fontSize = 0;
                fontSlider.value = fontSize;
                
                states = {
                    contrast: false,
                    highlightLinks: false,
                    textSpacing: false,
                    pauseAnimations: false,
                    dyslexiaFriendly: false,
                    tooltips: false,
                    textAlign: 'original'
                };

                initializeSliders();
                applyFontSize();

                // Reseta botões
                optionBtns.forEach(btn => {
                    btn.setAttribute('aria-pressed', false);
                    btn.style.backgroundColor = '';
                });

                // Limpa localStorage
                localStorage.removeItem('fontSize');
                closeAllSubmenus();
            }

            // Inicialização
            enforceConsistentButtonSizes();
            window.addEventListener('resize', enforceConsistentButtonSizes);
            initializeSliders();

            // Aplica configurações salvas ao carregar
            if (localStorage.getItem('fontSize')) {
                applyFontSize();
            }

            // Atalhos: Alt+1 até Alt+0 para facilitar uso rápido
            document.addEventListener('keydown', e => {
                if (e.altKey && !e.ctrlKey && !e.metaKey) {
                    switch (e.key) {
                        case '1': document.querySelector('[data-action="contrast"]').click(); break;
                        case '2': document.querySelector('[data-action="highlight-links"]').click(); break;
                        case '3': fontBtn.click(); break;
                        case '4': document.querySelector('[data-action="text-spacing"]').click(); break;
                        case '5': document.querySelector('[data-action="pause-animations"]').click(); break;
                        case '6': document.querySelector('[data-action="dyslexia-friendly"]').click(); break;
                        case '7': readPageBtn.click(); break;
                        case '8': document.querySelector('[data-action="tooltips"]').click(); break;
                        case '0': alignBtn.click(); break;
                        default: break;
                    }
                }

                // CTRL+U alterna painel
                if (e.ctrlKey && e.key.toLowerCase() === 'u') {
                    e.preventDefault();
                    togglePanel(panel.hidden);
                }

                // ESC para parar leitura
                if (e.key === 'Escape' && isReading) {
                    userStopped = true;
                    stopReading();
                }
            });

            // Parar leitura quando a página for fechada
            window.addEventListener('beforeunload', function() {
                userStopped = true;
                stopReading();
            });
        });
    </script>
    
</body>
</html>