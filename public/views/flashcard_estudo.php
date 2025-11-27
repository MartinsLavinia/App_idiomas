<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$id_deck = intval($_GET['deck'] ?? 0);

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Buscar foto do usuário
$sql_foto_usuario = "SELECT foto_perfil FROM usuarios WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto_usuario);
$stmt_foto->bind_param("i", $id_usuario);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result()->fetch_assoc();
$stmt_foto->close();

$foto_usuario = $resultado_foto['foto_perfil'] ?? null;

// Feche a conexão
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudar Flash Cards - Site de Idiomas</title>
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
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --cinza-escuro: #6c757d;
            --cinza-medio: #dee2e6;
            --verde-sucesso: #28a745;
            --vermelho-erro: #dc3545;
        }

        /* Estilos Gerais do Corpo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR FIXO */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
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
            color: var(--branco);
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
            min-height: 100vh;
            position: relative;
        }

        /* Menu Hamburguer */
        .menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--roxo-principal);
            color: var(--roxo-principal) !important;
            font-size: 1.5rem;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 50px;
            height: 50px;
            align-items: center;
            justify-content: center;
        }

        .menu-toggle:hover {
            color: var(--roxo-escuro) !important;
            transform: scale(1.1);
        }

        /* Quando a sidebar está ativa */
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

        /* Menu hamburger só aparece no mobile */
        @media (max-width: 992px) {
            .menu-toggle {
                display: flex !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 1050;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding-top: 80px;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Navbar igual ao admin */
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

        .settings-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
            padding: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .settings-icon:hover {
            color: var(--roxo-escuro) !important;
            transform: rotate(90deg);
            background: rgba(255, 255, 255, 0.2);
        }

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

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f2e9f9;
            color: var(--roxo-escuro);
            transform: translateX(4px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fceaea;
            color: #b02a37;
            transform: translateX(4px);
        }

        /* Container Principal de Estudo */
        .study-container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Cabeçalho da página */
        .page-header .card-header {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        /* Seção de Progresso */
        .progress-section {
            background: var(--branco);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--cinza-medio);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .progress-section h5 {
            color: var(--preto-texto);
        }

        .progress {
            height: 10px;
            background: var(--cinza-medio);
            border-radius: 5px;
        }

        .progress-bar {
            background: var(--amarelo-detalhe);
            border-radius: 5px;
        }

        /* Container do Flashcard */
        .flashcard-container {
            perspective: 1000px;
            margin-bottom: 2rem;
            min-height: 420px;
        }

        .flashcard {
            position: relative;
            width: 100%;
            height: 400px;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--cinza-medio);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .flashcard-front {
            background: var(--branco);
        }

        .flashcard-back {
            background: var(--branco);
            transform: rotateY(180deg);
        }

        .flashcard-header {
            padding: 1rem 1.5rem;
            color: var(--branco);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .flashcard-front .flashcard-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        }

        .flashcard-back .flashcard-header {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
        }

        .flashcard-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            text-align: center;
        }

        .flashcard-hint {
            font-size: 1rem;
            opacity: 0.8;
            font-style: italic;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .flashcard-footer {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Botões de Resposta */
        .response-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-response {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-response:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-again {
            background: linear-gradient(135deg, #e55353, #c82333);
            color: white;
        }

        .btn-hard {
            background: linear-gradient(135deg, #8a2be2, #6a0dad);
            color: white;
        }

        .btn-good {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
        }

        .btn-easy {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
        }

        /* Estatísticas */
        .stats-section {
            background: var(--branco);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--cinza-medio);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--amarelo-detalhe);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Estados (Loading, Vazio, Concluído) */
        .loading, .empty-state, .completed-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--branco);
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .loading h3, .empty-state h3, .completed-state h3 {
            color: var(--preto-texto);
            font-weight: 600;
        }

        .loading i, .empty-state i, .completed-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--amarelo-detalhe);
        }

        .completed-state .stat-number {
            color: var(--roxo-principal);
        }

        .completed-state .text-success {
            color: var(--verde-sucesso) !important;
        }

        .completed-state .text-warning {
            color: var(--amarelo-detalhe) !important;
        }

        /* Botões com estilo unificado */
        .btn-action {
            padding: 0.65rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
            color: var(--branco);
        }

        .btn-action-warning {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-action-warning:hover {
            background: linear-gradient(135deg, #f39c12, var(--amarelo-detalhe));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            color: var(--preto-texto);
        }

        /* Animações */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .flashcard-container.slide-in {
            animation: slideInUp 0.5s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .study-container {
                padding: 1rem;
            }

            .flashcard {
                height: 300px;
            }
            
            .flashcard-content {
                font-size: 1.2rem;
            }

            .response-buttons {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .btn-response {
                padding: 0.75rem 1rem;
                font-size: 1rem;
                min-width: 100px;
            }
            
            .stats-section .row {
                gap: 1rem;
            }
            
            .stat-item {
                flex: 1;
                min-width: calc(50% - 0.5rem);
            }
        }

        /* Spinner de loading */
        .spinner-border {
            color: var(--roxo-principal);
        }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.5rem 0.75rem;
        }

        /* Links de navegação */
        .text-decoration-none.text-muted:hover {
            color: var(--roxo-principal) !important;
        }
    </style>
</head>
<body>
    <!-- Menu Hamburguer - AGORA SÓ APARECE NO MOBILE -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Navbar igual ao admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil_usuario.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
                <a href="../../logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="profile">
            <?php if ($foto_usuario): ?>
                <div class="profile-avatar-sidebar">
                    <img src="../../<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto de perfil" class="profile-avatar-img">
                </div>
            <?php else: ?>
                <div class="profile-avatar-sidebar">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <h5><?php echo htmlspecialchars($nome_usuario); ?></h5>
            <small>Usuário</small>
        </div>

        <div class="list-group">
            <a href="painel.php" class="list-group-item">
                <i class="fas fa-home"></i> Início
            </a>
            <a href="flashcards.php" class="list-group-item active">
                <i class="fas fa-layer-group"></i> Flash Cards
            </a>
        
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="study-container">
                <!-- Cabeçalho -->
                <div class="row mb-4 align-items-center">
                    <div class="col">
                        <h1 class="mb-2">
                            <a href="flashcards.php" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
                            Sessão de Estudo
                        </h1>
                        <p class="text-muted mb-0">Concentre-se e revise seus flashcards.</p>
                    </div>
                </div>

                <!-- Progresso -->
                <div class="progress-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Progresso da Sessão</h5>
                        <span id="progressText" class="fw-bold">0 / 0</span>
                    </div>
                    <div class="progress">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div id="statsSection" class="stats-section" style="display: none;">
                    <div class="row">
                        <div class="col-3 stat-item"><div id="statTotal" class="stat-number" style="color: #4c087c">0</div><div class="stat-label">Total</div></div>
                        <div class="col-3 stat-item"><div id="statCorrect" class="stat-number" style="color: #4c087c">0</div><div class="stat-label">Acertos</div></div>
                        <div class="col-3 stat-item"><div id="statWrong" class="stat-number" style="color: #4c087c">0</div><div class="stat-label">Erros</div></div>
                        <div class="col-3 stat-item"><div id="statAccuracy" class="stat-number" style="color: #4c087c">0%</div><div class="stat-label">Precisão</div></div>
                    </div>
                </div>

                <!-- Área de Estudo -->
                <div id="studyArea">
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <h3 class="mt-3">Carregando flashcards...</h3>
                        <p class="text-muted">Preparando sua sessão de estudo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variáveis globais
        const idDeck = <?php echo $id_deck ?: 'null'; ?>;
        let flashcards = [];
        let currentIndex = 0;
        let isFlipped = false;
        let sessionStats = {
            total: 0,
            correct: 0,
            wrong: 0,
            completed: 0
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar menu hamburguer
            initializeHamburgerMenu();
            carregarFlashcards();
        });

        // Função para inicializar menu hambúrguer
        function initializeHamburgerMenu() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            console.log('Inicializando menu hambúrguer:', {
                menuToggle: !!menuToggle,
                sidebar: !!sidebar,
                sidebarOverlay: !!sidebarOverlay
            });
            
            if (menuToggle && sidebar) {
                // Remover listeners existentes
                menuToggle.replaceWith(menuToggle.cloneNode(true));
                const newMenuToggle = document.getElementById('menuToggle');
                
                newMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Menu toggle clicado');
                    
                    sidebar.classList.toggle('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('active');
                    }
                    
                    console.log('Sidebar ativo:', sidebar.classList.contains('active'));
                });
                
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        console.log('Overlay clicado - fechando menu');
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                    });
                }
                
                // Fechar menu ao clicar em um link (mobile)
                const sidebarLinks = sidebar.querySelectorAll('.list-group-item');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            console.log('Link clicado - fechando menu mobile');
                            sidebar.classList.remove('active');
                            if (sidebarOverlay) {
                                sidebarOverlay.classList.remove('active');
                            }
                        }
                    });
                });
                
                // Fechar menu com ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('active');
                        }
                    }
                });
                
                console.log('Menu hambúrguer inicializado com sucesso');
            } else {
                console.error('Elementos do menu não encontrados:', {
                    menuToggle: !!menuToggle,
                    sidebar: !!sidebar
                });
            }
        }

        // Carrega flashcards para estudo
        function carregarFlashcards() {
            let url = 'flashcard_controller.php?action=obter_flashcards_para_revisar';
            if (idDeck) {
                url += `&id_deck=${idDeck}`;
            }
            url += '&limite=50';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        flashcards = data.flashcards;
                        sessionStats.total = flashcards.length;
                        
                        if (flashcards.length > 0) {
                            iniciarEstudo();
                        } else {
                            exibirEstadoVazio();
                        }
                    } else {
                        console.error('Erro ao carregar flashcards:', data.message);
                        exibirErro('Erro ao carregar flashcards: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                    exibirErro('Erro de conexão. Tente novamente.');
                });
        }

        // Inicia o estudo
        function iniciarEstudo() {
            document.getElementById('statsSection').style.display = 'block';
            atualizarEstatisticas();
            exibirFlashcard();
        }

        // Exibe o flashcard atual
        function exibirFlashcard() {
            if (currentIndex >= flashcards.length) {
                exibirEstadoConcluido();
                return;
            }

            const flashcard = flashcards[currentIndex];
            isFlipped = false;
            
            const dificuldadeTexto = {
                'facil': 'Fácil',
                'medio': 'Médio',
                'dificil': 'Difícil'
            };

            // Adiciona a classe de animação
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = ''; // Limpa a área

            // Cria o novo card
            studyArea.innerHTML = `
                <div class="flashcard-container slide-in">
                    <div class="flashcard" onclick="virarFlashcard()">
                        <div class="flashcard-side flashcard-front">
                            <div class="flashcard-header">
                                <span>Pergunta</span>
                                <span class="badge bg-white bg-opacity-25 text-white">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                            </div>
                            <div class="flashcard-content">
                                <span>${flashcard.frente}</span>
                                ${flashcard.dica ? `<div class="flashcard-hint"><i class="fas fa-lightbulb me-1"></i> ${flashcard.dica}</div>` : ''}
                            </div>
                            <div class="flashcard-footer">
                                Clique no card para ver a resposta
                            </div>
                        </div>
                        <div class="flashcard-side flashcard-back">
                            <div class="flashcard-header">
                                <span>Resposta</span>
                                <span class="badge bg-black bg-opacity-25 text-black">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                            </div>
                            <div class="flashcard-content">
                                <span>${flashcard.verso}</span>
                            </div>
                            <div class="flashcard-footer">
                                Como você se saiu?
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="responseButtons" class="response-buttons" style="display: none;">
                    <button class="btn btn-response btn-again" onclick="responder(0)">
                        <i class="fas fa-times me-2"></i>Errei
                    </button>
                    <button class="btn btn-response btn-hard" onclick="responder(2)">
                        <i class="fas fa-frown me-2"></i>Difícil
                    </button>
                    <button class="btn btn-response btn-good" onclick="responder(4)">
                        <i class="fas fa-smile me-2"></i>Bom
                    </button>
                    <button class="btn btn-response btn-easy" onclick="responder(4)">
                        <i class="fas fa-laugh me-2"></i>Fácil
                    </button>
                </div>
            `;

            atualizarProgresso();
        }

        // Vira o flashcard
        function virarFlashcard() {
            const flashcard = document.querySelector('.flashcard');
            if (!isFlipped) {
                flashcard.classList.add('flipped');
                isFlipped = true;
                
                // Mostra botões de resposta após um delay
                setTimeout(() => {
                    document.getElementById('responseButtons').style.display = 'flex';
                }, 400);
            }
        }

        // Registra resposta do usuário
        function responder(facilidade) {
            const flashcard = flashcards[currentIndex];
            const acertou = facilidade >= 2; // 2, 3, 4 = acertou; 0 = errou
            
            // Atualiza estatísticas da sessão
            if (acertou) {
                sessionStats.correct++;
            } else {
                sessionStats.wrong++;
            }
            sessionStats.completed++;
            
            // Registra no backend
            const formData = new FormData();
            formData.append('action', 'registrar_resposta');
            formData.append('id_flashcard', flashcard.id);
            formData.append('acertou', acertou ? '1' : '0');
            formData.append('facilidade_resposta', facilidade);
            
            fetch('flashcard_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erro ao registrar resposta:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao registrar resposta:', error);
            });
            
            // Avança para o próximo flashcard
            currentIndex++;
            atualizarEstatisticas();
            
            setTimeout(() => {
                exibirFlashcard();
            }, 300);
        }

        // Atualiza progresso
        function atualizarProgresso() {
            const progress = flashcards.length > 0 ? (currentIndex / flashcards.length) * 100 : 0;
            document.getElementById('progressBar').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = `${currentIndex} / ${flashcards.length}`;
        }

        // Atualiza estatísticas
        function atualizarEstatisticas() {
            document.getElementById('statTotal').textContent = sessionStats.total;
            document.getElementById('statCorrect').textContent = sessionStats.correct;
            document.getElementById('statWrong').textContent = sessionStats.wrong;
            
            const accuracy = sessionStats.completed > 0 ? 
                Math.round((sessionStats.correct / sessionStats.completed) * 100) : 0;
            document.getElementById('statAccuracy').textContent = `${accuracy}%`;
        }

        // Exibe estado vazio
        function exibirEstadoVazio() {
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Nenhum flashcard para revisar</h3>
                    <p>Você está em dia com seus estudos! Volte mais tarde ou adicione novos flashcards.</p>
                    <div class="mt-4">
                        <a href="flashcards.php" class="btn btn-action btn-action-primary me-2">
                            <i class="fas fa-layer-group me-2></i>Ver Meus Decks
                        </a>
                        <a href="flashcard_deck.php?id=${idDeck}" class="btn btn-action btn-action-warning">
                            <i class="fas fa-plus me-2" style="color: #6a0dad";></i>Adicionar Cards
                        </a>
                    </div>
                </div>
            `;
        }

        // Exibe estado concluído
        function exibirEstadoConcluido() {
            const accuracy = sessionStats.completed > 0 ? 
                Math.round((sessionStats.correct / sessionStats.completed) * 100) : 0;
            
            let message = '';
            let icon = '';
            
            if (accuracy >= 90) {
                message = 'Excelente trabalho!';
                icon = 'fas fa-trophy';
            } else if (accuracy >= 70) {
                message = 'Bom trabalho!';
                icon = 'fas fa-thumbs-up';
            } else {
                message = 'Continue praticando!';
                icon = 'fas fa-dumbbell';
            }

            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="completed-state">
                    <i class="${icon}"></i>
                    <h3>${message}</h3>
                    <p>Você completou sua sessão de estudo com ${accuracy}% de precisão.</p>
                    
                    <div class="row mt-4 mb-4">
                        <div class="col-sm-4 stat-item mb-3 mb-sm-0">
                            <div class="stat-number">${sessionStats.total}</div>
                            <div class="stat-label">Cards Estudados</div>
                        </div>
                        <div class="col-sm-4 stat-item mb-3 mb-sm-0">
                            <div class="stat-number text-success">${sessionStats.correct}</div>
                            <div class="stat-label">Acertos</div>
                        </div>
                        <div class="col-sm-4 stat-item">
                            <div class="stat-number text-warning">${accuracy}%</div>
                            <div class="stat-label">Precisão</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-action btn-action-primary me-2" onclick="reiniciarEstudo()">
                            <i class="fas fa-redo me-2"></i>Estudar Novamente
                        </button>
                        <a href="flashcards.php" class="btn btn-action btn-action-warning">
                            <i class="fas fa-layer-group me-2"></i>Ver Meus Decks
                        </a>
                    </div>
                </div>
            `;
            
            // Atualiza progresso para 100%
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressText').textContent = `${flashcards.length} / ${flashcards.length}`;
        }

        // Reinicia o estudo
        function reiniciarEstudo() {
            currentIndex = 0;
            sessionStats = {
                total: flashcards.length,
                correct: 0,
                wrong: 0,
                completed: 0
            };
            atualizarEstatisticas();
            exibirFlashcard();
        }

        // Exibe erro
        function exibirErro(mensagem) {
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h3>Erro</h3>
                    <p>${mensagem}</p>
                    <div class="mt-4">
                        <button class="btn btn-action btn-action-primary" onclick="carregarFlashcards()">
                            <i class="fas fa-redo me-2"></i>Tentar Novamente
                        </button>
                    </div>
                </div>
            `;
        }
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
</body>
</html>