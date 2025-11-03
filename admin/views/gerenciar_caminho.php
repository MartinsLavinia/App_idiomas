<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Lógica para buscar os idiomas únicos do banco de dados das tabelas caminhos_aprendizagem e quiz_nivelamento
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$stmt_idiomas = $conn->prepare($sql_idiomas);
$stmt_idiomas->execute();
$idiomas_db = $stmt_idiomas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas->close();

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// Buscar unidades do banco de dados
$sql_unidades = "SELECT id, nome_unidade FROM unidades ORDER BY nome_unidade";
$stmt_unidades = $conn->prepare($sql_unidades);
$stmt_unidades->execute();
$unidades_db = $stmt_unidades->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_unidades->close();

// Definição dos níveis de A1 a C2
$niveis_db = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

// Lógica para buscar os caminhos com base na pesquisa
$sql_caminhos = "SELECT id, idioma, nome_caminho, nivel FROM caminhos_aprendizagem WHERE 1=1";
$params = [];
$types = '';

if (isset($_GET['idioma']) && !empty($_GET['idioma'])) {
    $sql_caminhos .= " AND idioma = ?";
    $params[] = $_GET['idioma'];
    $types .= 's';
}

if (isset($_GET['nivel']) && !empty($_GET['nivel'])) {
    $sql_caminhos .= " AND nivel = ?";
    $params[] = $_GET['nivel'];
    $types .= 's';
}

$sql_caminhos .= " ORDER BY idioma, nivel, nome_caminho";

$stmt_caminhos = $conn->prepare($sql_caminhos);
if (!empty($params)) {
    $stmt_caminhos->bind_param($types, ...$params);
}

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
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(106, 13, 173, 0.1);
            transition: all 0.3s ease;
        }

        .card-header h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .card-header h5 i {
            color: var(--amarelo-detalhe);
        }

        /* Cartões de Estatísticas - MESMO ESTILO DO PRIMEIRO CÓDIGO */
        .stats-card {
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--preto-texto);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 2px solid rgba(106, 13, 173, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: statsCardAnimation 0.8s ease-out;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        @keyframes statsCardAnimation {
            from {
                opacity: 0;
                transform: translateY(30px) rotateX(-10deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(106, 13, 173, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .stats-card:hover::before {
            left: 100%;
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 30px rgba(106, 13, 173, 0.25);
            border-color: rgba(106, 13, 173, 0.3);
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--roxo-principal);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-card p {
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 1.1rem;
            color: var(--preto-texto);
        }

        .stats-card i {
            font-size: 2rem;
            color: var(--amarelo-detalhe);
            margin-bottom: 1rem;
        }

        /* Animações adicionais para stats-card */
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }

        /* Barra de Navegação - MODIFICADA PARA TRANSPARENTE */
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

        .btn-outline-light {
            color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            font-weight: 600;
            transition: all 0.3s ease;
        }

       .btn-outline-warning:hover {

background-color: var(--amarelo-detalhe);
border: 0 4px 8px rgba(235, 183, 14, 0.77);

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
        transition: transform 0.3s ease-in-out;
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

    .main-content {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s ease-in-out;
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

@media (max-width: 768px) {
    .sidebar {
        width: 280px;
    }
    
    .stats-card h3 {
        font-size: 2rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-sm {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 15px 10px;
    }
    
    .stats-card {
        padding: 15px;
    }
    
    .stats-card h3 {
        font-size: 1.8rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-container {
        padding: 15px;
    }
}

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
            box-shadow: 0 6px 25px rgba(255, 217, 0, 0.66);
            color: var(--preto-texto);
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

        /* ESTILO PARA O BOTÃO LOGOUT - ADICIONAR */
        .logout-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .logout-icon:hover {
            color: var(--roxo-escuro) !important;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pesquisarCard = document.querySelector('.card-header h5');
    
    if (pesquisarCard) {
        // Criar elemento para o efeito de brilho
        const brilho = document.createElement('div');
        brilho.style.position = 'absolute';
        brilho.style.top = '0';
        brilho.style.left = '-100%';
        brilho.style.width = '50%';
        brilho.style.height = '100%';
        brilho.style.background = 'linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent)';
        brilho.style.transform = 'skewX(-20deg)';
        brilho.style.transition = 'none';
        brilho.style.pointerEvents = 'none';
        
        // Adicionar brilho ao card header
        pesquisarCard.parentElement.style.position = 'relative';
        pesquisarCard.parentElement.style.overflow = 'hidden';
        pesquisarCard.parentElement.appendChild(brilho);
        
        // Função para ativar o brilho
        function ativarBrilho() {
            brilho.style.transition = 'left 0.8s ease-in-out';
            brilho.style.left = '150%';
            
            // Reset após animação
            setTimeout(() => {
                brilho.style.transition = 'none';
                brilho.style.left = '-100%';
            }, 800);
        }
        
        // Ativar brilho a cada 3 segundos
        setInterval(ativarBrilho, 3000);
        
        // Ativar também ao passar o mouse
        pesquisarCard.parentElement.addEventListener('mouseenter', ativarBrilho);
    }
    
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

    <!-- Menu Hamburguer -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
        <a href="gerenciar_caminho.php" class="list-group-item active">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item">
            <i class="fas fa-language"></i> Gerenciar Idiomas
        </a>
        <a href="gerenciar_teorias.php" class="list-group-item">
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
    </div>
</div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <?php
            if (isset($_GET["message_type"]) && isset($_GET["message_content"])) {
                $message_type = htmlspecialchars($_GET["message_type"]);
                $message_content = htmlspecialchars(urldecode($_GET["message_content"]));
                echo '<div class="alert alert-' . ($message_type == 'success' ? 'success' : 'danger') . ' mt-3">' . $message_content . '</div>';
            }
            ?>

            <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>

            <a href="#" class="btn btn-warning mb-4" data-bs-toggle="modal" data-bs-target="#addCaminhoModal">
                <i class="fas fa-plus-circle me-2"></i>Adicionar Caminho
            </a>

            <!-- Notificações -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-search me-2"></i>Pesquisar Caminhos
                    </h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-auto">
                                <label for="idioma_busca" class="col-form-label">Idioma:</label>
                                <select id="idioma_busca" name="idioma" class="form-select">
                                    <option value="">Todos os Idiomas</option>
                                    <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>"
                                        <?php echo (isset($_GET['idioma']) && $_GET['idioma'] === $idioma['idioma']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($idioma['idioma']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-auto">
                                <label for="nivel_busca" class="col-form-label">Nível:</label>
                                <select id="nivel_busca" name="nivel" class="form-select">
                                    <option value="">Todos os Níveis</option>
                                    <?php foreach ($niveis_db as $nivel): ?>
                                    <option value="<?php echo htmlspecialchars($nivel); ?>"
                                        <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === $nivel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nivel); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-auto d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-warning" style="margin-top: 40px;">
                                    <i class="fas fa-search me-2"></i>Pesquisar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-container table-bordered table-striped">
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
                            <td class="btn-acoes">
                                <a href="gerenciar_blocos.php?caminho_id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                    class="btn btn-sm btn-info btn-blocos">
                                    <i class="fas fa-eye"></i> Ver Blocos
                                </a>
                                
                                <a href="editar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                    class="btn btn-sm btn-primary btn-editar">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-danger delete-btn btn-eliminar" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-id="<?php echo htmlspecialchars($caminho['id']); ?>"
                                    data-nome="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>"
                                    data-tipo="caminho" data-action="eliminar_caminho.php">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
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

            <!-- Estatísticas - MANTIDAS COMO ESTAVAM ANTES (EMBAIXO) -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-road"></i>
                        <h3><?= count($caminhos) ?></h3>
                        <p>Total de Caminhos</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-cubes"></i>
                        <h3><?= count($unidades_db) ?></h3>
                        <p>Total de Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-globe"></i>
                        <h3><?= count($idiomas_db) ?></h3>
                        <p>Total de Idiomas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-tasks"></i>
                        <h3><?= isset($quizzes_concluidos) ? $quizzes_concluidos : 0 ?></h3>
                        <p>Quizzes Concluídos</p>
                    </div>
                </div>
            </div>

            <!-- Modal para Adicionar Caminho -->
            <div class="modal fade" id="addCaminhoModal" tabindex="-1" aria-labelledby="addCaminhoModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCaminhoModalLabel">Adicionar Novo Caminho</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formAddCaminho">
                            <div class="modal-body">
                                <div id="alertCaminho"></div>
                                <div class="mb-3">
                                    <label for="idioma_novo" class="form-label">Idioma</label>
                                    <select id="idioma_novo" name="idioma" class="form-select" required>
                                        <option value="">Selecione o Idioma</option>
                                        <?php foreach ($idiomas_db as $idioma): ?>
                                        <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>">
                                            <?php echo htmlspecialchars($idioma['idioma']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nivel_novo" class="form-label">Nível</label>
                                    <select id="nivel_novo" name="nivel" class="form-select" required>
                                        <option value="">Selecione o Nível</option>
                                        <?php foreach ($niveis_db as $nivel): ?>
                                        <option value="<?php echo htmlspecialchars($nivel); ?>">
                                            <?php echo htmlspecialchars($nivel); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                                    <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" required>
                                </div>
                                <div class="mb-3">
                                    <label for="unidade_id" class="form-label">Unidade</label>
                                    <select id="unidade_id" name="unidade_id" class="form-select" required>
                                        <option value="">Selecione a Unidade</option>
                                        <?php foreach ($unidades_db as $unidade): ?>
                                        <option value="<?php echo htmlspecialchars($unidade['id']); ?>">
                                            <?php echo htmlspecialchars($unidade['nome_unidade']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="submit" class="btn btn-success" id="btnAddCaminho">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    Adicionar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal para Gerenciar Idiomas -->
            <div class="modal fade" id="gerenciarIdiomasModal" tabindex="-1" aria-labelledby="gerenciarIdiomasModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="gerenciarIdiomasModalLabel">Gerenciar Idiomas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulário para adicionar idioma simples -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">➕ Adicionar Novo Idioma (Simples)</h6>
                                </div>
                                <div class="card-body">
                                    <form action="adicionar_idioma_simples.php" method="POST">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="nome_idioma" placeholder="Nome do idioma (ex: Alemão)" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-success w-100">Adicionar</button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Adiciona apenas o idioma. Você pode criar o quiz depois.</small>
                                    </form>
                                </div>
                            </div>

                            <p class="text-muted">Use o botão "Adicionar Novo Idioma com Quiz" para criar um idioma com quiz de nivelamento completo.</p>
                        </div>
                        <div class="modal-footer">
                            <a href="pagina_adicionar_idiomas.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Adicionar Novo Idioma com Quiz
                            </a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
                            <p>Tem certeza que deseja excluir o item <strong id="itemNome"></strong>?</p>
                            <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para o modal de confirmação de exclusão
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const itemNome = document.getElementById('itemNome');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    const tipo = this.getAttribute('data-tipo');
                    const action = this.getAttribute('data-action');

                    itemNome.textContent = nome;
                    confirmDeleteBtn.href = `${action}?id=${id}`;
                });
            });

            // Script para adicionar caminho via AJAX
            const formAddCaminho = document.getElementById('formAddCaminho');
            const btnAddCaminho = document.getElementById('btnAddCaminho');
            const alertCaminho = document.getElementById('alertCaminho');

            formAddCaminho.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const spinner = btnAddCaminho.querySelector('.spinner-border');
                const btnText = btnAddCaminho.querySelector('span:not(.spinner-border)');
                
                // Mostrar loading
                spinner.classList.remove('d-none');
                btnText.textContent = 'Adicionando...';
                btnAddCaminho.disabled = true;
                alertCaminho.innerHTML = '';

                fetch('adicionar_caminho.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertCaminho.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        formAddCaminho.reset();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alertCaminho.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertCaminho.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> Erro ao adicionar caminho. Tente novamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                })
                .finally(() => {
                    spinner.classList.add('d-none');
                    btnText.textContent = 'Adicionar';
                    btnAddCaminho.disabled = false;
                });
            });
        });
    </script>
</body>
</html>