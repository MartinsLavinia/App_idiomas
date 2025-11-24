<?php
// pagina_adicionar_idiomas.php
session_start();
include_once __DIR__ . '/../../conexao.php';

$database = new Database();
$conn = $database->conn;
// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;
// 1. Configurações de Paginação
$limit = 5; // Limite de perguntas por página
$total_perguntas = 20; // Total de perguntas a serem exibidas
$total_paginas = ceil($total_perguntas / $limit); // 20 / 5 = 4 páginas

// Pega o número da página atual da URL (GET), padrão é 1
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Garante que a página atual esteja dentro dos limites válidos
if ($pagina_atual < 1) {
    $pagina_atual = 1;
} elseif ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

// Calcula o índice inicial (offset) para o loop (ex: Pág 1 começa em 1, Pág 2 em 6)
$offset_inicial = ($pagina_atual - 1) * $limit + 1;

// Se necessário, inclua arquivos de configuração, autenticação, etc.
// include_once '../../config.php';
// include_once '../../auth.php';

// Buscar idiomas existentes para o modal
$query_idiomas = "SELECT nome_idioma FROM idiomas ORDER BY nome_idioma";
$stmt_idiomas = $conn->prepare($query_idiomas);
if ($stmt_idiomas) {
    $stmt_idiomas->execute();
    $result_idiomas = $stmt_idiomas->get_result();
    $idiomas_db = $result_idiomas->fetch_all(MYSQLI_ASSOC);
    $stmt_idiomas->close();
} else {
    // Tratar erro de preparação da query, se necessário
    $idiomas_db = [];
}

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Idioma Completo - Página <?php echo $pagina_atual; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
   <!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Idioma Completo - Página <?php echo $pagina_atual; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
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
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: var(--cinza-claro);
            color: var(--preto-texto);
            min-height: 100vh;          
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

        .logout-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        /* Barra de Navegação */
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

        .btn-outline-warning {
            color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-warning:hover {
            background-color: var(--amarelo-detalhe);
            box-shadow: 0 4px 8px rgba(235, 183, 14, 0.77);
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

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

/* CONTAINER DA FOTO DO PERFIL - APENAS QUANDO HÁ FOTO */
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

/* IMAGEM DO PERFIL - CIRCULAR */
.profile-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* ÍCONE PADRÃO (QUANDO NÃO HÁ FOTO) - SEM CÍRCULO */
.profile-icon-sidebar {
    font-size: 3.5rem;
    color: var(--amarelo-detalhe);
    margin: 0 auto 15px;
    display: block;
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

.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: margin-left 0.3s ease-in-out;
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
    
    .main-content {
        padding: 20px 15px;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 20px 10px;
    }
}
       .btn-salvar-quiz{
    background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
    color: var(--preto-texto);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    min-width: 220px; /* Mesma largura */
    padding: 12px 24px; /* Mesmo padding */
    font-size: 1.1rem; /* Mesmo tamanho de fonte */
    border: none;
    margin-bottom: 7px;
    border-radius: 8px; /* Mesmo border-radius */
    
}

        .btn-salvar-quiz:hover {
            background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
            color: var(--preto-texto);
        }

        /* ESTILOS EXISTENTES DO SEGUNDO CÓDIGO (MANTIDOS) */
        .card {
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px rgba(106,13,173,0.07);
            border: none;
            background: var(--branco);
            transition: box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 8px 32px rgba(106,13,173,0.12);
        }

        .card-header {
            background: linear-gradient(90deg, var(--roxo-principal) 70%, var(--roxo-escuro) 100%);
            color: var(--branco);
            border-radius: 1.2rem 1.2rem 0 0;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: none;
        }

        .btn-secondary {
            background: var(--cinza-medio);
            color: var(--preto-texto);
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-secondary:hover {
            background: var(--cinza-escuro);
        }

        .btn-success {
            background: var(--amarelo-detalhe);
            color: var(--preto-texto);
            border: none;
            font-weight: 600;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(255,215,0,0.08);
            transition: background 0.2s;
        }

        .btn-success:hover {
            background: #e6c200;
            color: var(--preto-texto);
        }

        label {
            font-weight: 600;
            color: var(--roxo-escuro);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        h5.card-title {
            color: var(--branco);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid var(--cinza-medio);
            border-bottom: 2px solid var(--cinza-escuro);
            background: var(--branco);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s, border-bottom 0.3s;
            padding: 0.6rem 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal);
            border-bottom: 3px solid var(--roxo-principal);
            box-shadow: none;
            background: #f6f8fc;
        }
        
        textarea.form-control {
            min-height: 80px;
        }

        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        .gap-2 {
            gap: .7rem !important;
        }

        .card-body {
            background: rgba(255,255,255,0.97);
            border-radius: 0 0 1.2rem 1.2rem;
            padding: 1.8rem;
        }

        .card.mb-3 {
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--cinza-medio);
            background: var(--branco);
            overflow: hidden;
        }

        .card.mb-3 .card-header {
            background: var(--roxo-principal);
            color: var(--branco);
            border-radius: 0.5rem 0.5rem 0 0;
            font-size: 1.15rem;
            font-weight: 700;
            border-bottom: 3px solid var(--amarelo-detalhe);
            padding: 0.8rem 1.25rem;
        }

        .text-muted {
            color: var(--roxo-escuro) !important;
            font-size: 0.95rem;
            font-style: italic;
        }

        .pagination {
            --bs-pagination-padding-x: 0.8rem;
            --bs-pagination-padding-y: 0.5rem;
            --bs-pagination-font-size: 1rem;
        }

        .pagination .page-item .page-link {
            color: var(--roxo-principal);
            border: 1px solid var(--cinza-medio);
            border-radius: 0.5rem;
            margin: 0 0.2rem;
            background: var(--branco);
            font-weight: 500;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            box-shadow: none;
        }

        .pagination .page-item.active .page-link {
            z-index: 3;
            color: var(--branco);
            background: var(--roxo-principal);
            border-color: var(--roxo-escuro);
            font-weight: 600;
            box-shadow: none;
        }

        .pagination .page-item .page-link:hover:not(.disabled) {
            color: var(--roxo-principal);
            background: #f6f8fc;
            border-color: var(--roxo-principal);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background: var(--cinza-claro);
            border-color: var(--cinza-medio);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Responsividade para formulários */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            
            .row .col-md-3 {
                margin-bottom: 1rem;
            }
            
            .pagination {
                --bs-pagination-padding-x: 0.6rem;
                --bs-pagination-padding-y: 0.4rem;
                --bs-pagination-font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            h2 {
                font-size: 1.5rem;
            }
            
            .card-header h5 {
                font-size: 1.1rem;
            }
            
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
            
            label {
                font-size: 0.8rem;
            }
            
            .btn {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
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

        /* Estilos para o botão Limpar Dados */
       .btn-outline-danger {
    min-width: 220px; /* Mesma largura */
    padding: 12px 24px; /* Mesmo padding */
    font-size: 1.1rem; /* Mesmo tamanho de fonte */
    border-radius: 8px; /* Mesmo border-radius */
    font-weight: 600; /* Mesmo peso da fonte */
    background: rgba(220, 53, 69, 0.06);
    color: #dc3545;
    border: 2px solid #dc3545;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
}

.btn-outline-danger:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border-color: #dc3545;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.25);
}

        .btn-outline-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
        }

            /* Modal de Confirmação de Limpeza */
        #confirmClearModal .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(106, 13, 173, 0.2);
        }

        #confirmClearModal .modal-header {
            background: var(--roxo-principal);
            color: var(--branco);
            border-bottom: none;
            padding: 1.5rem;
        }

        #confirmClearModal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 1;
        }

        #confirmClearModal .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #confirmClearModal .modal-title::before {
            content: '⚠️';
            font-size: 1.2rem;
        }

        #confirmClearModal .modal-body {
            padding: 1.5rem;
            text-align: center;
        }

        #confirmClearModal .modal-body .text-danger {
            background: rgba(255, 215, 0, 0.1);
            border-left: 3px solid var(--amarelo-detalhe);
            padding: 0.75rem;
            border-radius: 5px;
            color: var(--roxo-escuro);
            font-weight: 600;
        }

        #confirmClearModal .modal-footer {
            border-top: 1px solid var(--cinza-medio);
            padding: 1rem 1.5rem;
        }

        #confirmClearModal .btn-secondary {
            background: var(--cinza-medio);
            border: none;
            color: var(--preto-texto);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #confirmClearModal .btn-secondary:hover {
            background: #6c757d;
            color: var(--branco);
        }

        #confirmClearModal .btn-danger {
            background: #b02a37;
            border: none;
            color: var(--branco);
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        #confirmClearModal .btn-danger:hover {
            background: #a02332;
            color: var(--branco);
        }

        /* Estilos para o toast de confirmação personalizado */
        .toast {
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: none;
            overflow: hidden;
        }

        .toast-header {
            border-bottom: none;
            padding: 1rem 1.25rem 0.75rem;
            font-weight: 600;
        }

        .toast-body {
            padding: 1rem 1.25rem 1.25rem;
        }

        .toast .btn {
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .toast .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }

        .toast .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-1px);
        }

        .toast .btn-secondary {
            background: #6c757d;
            border: none;
            color: white;
        }

        .toast .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Animação para o toast */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast.show {
            animation: slideInRight 0.3s ease-out;
        }

        /* Estilos para ícones nos toasts */
        .toast .fas {
            font-size: 1.1rem;
        }

        /* Container de toasts */
        .toast-container {
            z-index: 1060;
        }

        /* Botão Gerenciar Quiz */
        .btn-quiz-manage {
            background: rgba(173, 216, 230, 0.2);
            color: #4682b4;
            border: 2px solid #87ceeb;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-quiz-manage:hover {
            background: rgba(173, 216, 230, 0.4);
            color: #2f4f4f;
            border-color: #4682b4;
            transform: translateY(-1px);
        }
    </style>
</head>
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
            // REMOVIDO: Código que mudava o ícone para X
        });
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                // REMOVIDO: Código que voltava o ícone para hamburguer
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
                    // REMOVIDO: Código que voltava o ícone para hamburguer
                }
            });
        });
    }

    // Lógica para o modal de confirmação de exclusão
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-id');
            const itemName = button.getAttribute('data-nome');
            const itemType = button.getAttribute('data-tipo');
            const formAction = button.getAttribute('data-action');

            const modalBody = confirmDeleteModal.querySelector('#confirmDeleteModalBody');
            const modalForm = confirmDeleteModal.querySelector('#deleteForm');
            const hiddenInput = confirmDeleteModal.querySelector('#deleteItemId');

            let message = '';
            if (itemType === 'idioma') {
                message = `Tem certeza que deseja excluir o idioma '<strong>${itemName}</strong>'? Isso excluirá todos os caminhos, exercícios e quizzes associados a ele.`;
            } else {
                message = `Tem certeza que deseja excluir o item '<strong>${itemName}</strong>'?`;
            }

            modalBody.innerHTML = `<p>${message}</p>`;
            modalForm.action = formAction;
            hiddenInput.value = itemId;
        });
    }

    // Lógica para o modal de notificação
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');

    if (status && message) {
        const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const modalBody = document.getElementById('notificationModalBody');

        modalBody.textContent = decodeURIComponent(message.replace(/\+/g, ' '));

        const modalTitle = document.getElementById('notificationModalLabel');
        if (status === 'success') {
            modalTitle.textContent = 'Sucesso';
        } else if (status === 'error') {
            modalTitle.textContent = 'Erro';
        }

        notificationModal.show();
        window.history.replaceState({}, document.title, window.location.pathname);
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
        <a href="gerenciar_caminho.php" class="list-group-item ">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item active">
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

            <h2 class="mb-4">
                <i class="fas fa-language me-2"></i>
                Gerenciar idiomas simples
            </h2>

            <div class="d-flex gap-2 mb-4">
                <a href="#" class="btn btn-salvar-quiz" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal" style="padding-top 5px;">
                    <i class="fas fa-plus-circle me-2"></i>Gerenciar idiomas
                </a>
                <?php if (isset($_SESSION['quiz_data']) && !empty($_SESSION['quiz_data'])): ?>
                <button class="btn btn-outline-danger" onclick="confirmarLimpeza()">
                    <i class="fas fa-trash me-2"></i>Limpar Dados Salvos
                </button>
                <?php endif; ?>
            </div>

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
            
            <?php if (isset($_SESSION['quiz_data']) && !empty($_SESSION['quiz_data'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i> 
                Você tem dados salvos de um formulário anterior. 
                <strong><?php echo count($_SESSION['quiz_data']); ?> pergunta(s)</strong> já preenchida(s).
                <?php if (isset($_SESSION['idioma_novo'])): ?>
                    Idioma: <strong><?php echo htmlspecialchars($_SESSION['idioma_novo']); ?></strong>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Adicionar Novo Idioma com Quiz (Página <?php echo $pagina_atual . ' de ' . $total_paginas; ?>)</h5>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($pagina_atual / $total_paginas) * 100; ?>%" aria-valuenow="<?php echo $pagina_atual; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_paginas; ?>"></div>
                    </div>
                    <small class="text-light mt-1 d-block">Progresso: <?php echo $pagina_atual; ?>/<?php echo $total_paginas; ?> páginas (<?php echo round(($pagina_atual / $total_paginas) * 100); ?>%)</small>
                </div>
                <div class="card-body">
                    <form action="adicionar_idioma_completo.php?page=<?php echo $pagina_atual + 1; ?>" method="POST" id="quizForm">
                        <?php if ($pagina_atual === 1): ?>
                        <div class="mb-3">
                            <label for="idioma_novo_completo" class="form-label">Nome do Idioma</label>
                            <input type="text" class="form-control" id="idioma_novo_completo" name="idioma" placeholder="Ex: Espanhol" value="<?php echo isset($_SESSION['idioma_novo']) ? htmlspecialchars($_SESSION['idioma_novo']) : ''; ?>" required>
                        </div>
                        <hr>
                        <?php endif; ?>

                        <h5>Perguntas do Quiz de Nivelamento (Total: 20 perguntas)</h5>
                        
                        <p class="text-muted">A resposta correta para cada pergunta deve ser "A", "B", "C" ou "D".</p>
                        
                        <?php for ($i = $offset_inicial; $i < $offset_inicial + $limit && $i <= $total_perguntas; $i++): 
                            $saved_data = isset($_SESSION['quiz_data'][$i]) ? $_SESSION['quiz_data'][$i] : [];
                        ?>
                        <div class="card mb-3">
                            <div class="card-header">Pergunta #<?php echo $i; ?></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="pergunta_<?php echo $i; ?>" class="form-label">Pergunta</label>
                                    <textarea class="form-control" id="pergunta_<?php echo $i; ?>" name="pergunta_<?php echo $i; ?>" rows="2" required><?php echo isset($saved_data['pergunta']) ? htmlspecialchars($saved_data['pergunta']) : ''; ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="opcao_a_<?php echo $i; ?>" class="form-label">Opção A</label>
                                        <input type="text" class="form-control" id="opcao_a_<?php echo $i; ?>" name="opcao_a_<?php echo $i; ?>" value="<?php echo isset($saved_data['opcao_a']) ? htmlspecialchars($saved_data['opcao_a']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="opcao_b_<?php echo $i; ?>" class="form-label">Opção B</label>
                                        <input type="text" class="form-control" id="opcao_b_<?php echo $i; ?>" name="opcao_b_<?php echo $i; ?>" value="<?php echo isset($saved_data['opcao_b']) ? htmlspecialchars($saved_data['opcao_b']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="opcao_c_<?php echo $i; ?>" class="form-label">Opção C</label>
                                        <input type="text" class="form-control" id="opcao_c_<?php echo $i; ?>" name="opcao_c_<?php echo $i; ?>" value="<?php echo isset($saved_data['opcao_c']) ? htmlspecialchars($saved_data['opcao_c']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="opcao_d_<?php echo $i; ?>" class="form-label">Opção D</label>
                                        <input type="text" class="form-control" id="opcao_d_<?php echo $i; ?>" name="opcao_d_<?php echo $i; ?>" value="<?php echo isset($saved_data['opcao_d']) ? htmlspecialchars($saved_data['opcao_d']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="resposta_correta_<?php echo $i; ?>" class="form-label">Resposta Correta</label>
                                    <select id="resposta_correta_<?php echo $i; ?>" name="resposta_correta_<?php echo $i; ?>" class="form-select" required>
                                        <option value="">Selecione a resposta correta</option>
                                        <option value="A" <?php echo (isset($saved_data['resposta_correta']) && $saved_data['resposta_correta'] === 'A') ? 'selected' : ''; ?>>Opção A</option>
                                        <option value="B" <?php echo (isset($saved_data['resposta_correta']) && $saved_data['resposta_correta'] === 'B') ? 'selected' : ''; ?>>Opção B</option>
                                        <option value="C" <?php echo (isset($saved_data['resposta_correta']) && $saved_data['resposta_correta'] === 'C') ? 'selected' : ''; ?>>Opção C</option>
                                        <option value="D" <?php echo (isset($saved_data['resposta_correta']) && $saved_data['resposta_correta'] === 'D') ? 'selected' : ''; ?>>Opção D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                        
                        <hr>
                        
                        <nav aria-label="Navegação de Páginas">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="#" onclick="<?php echo ($pagina_atual > 1) ? 'navegarPagina(' . ($pagina_atual - 1) . ')' : ''; ?>; return false;">Anterior</a>
                                </li>
                                
                                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                                <li class="page-item <?php echo ($p == $pagina_atual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="#" onclick="navegarPagina(<?php echo $p; ?>); return false;"><?php echo $p; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="#" onclick="<?php echo ($pagina_atual < $total_paginas) ? 'navegarPagina(' . ($pagina_atual + 1) . ')' : ''; ?>; return false;">Próximo</a>
                                </li>
                            </ul>
                        </nav>

                        <div class="d-flex justify-content-between gap-2 mt-3">
                            <?php if ($pagina_atual > 1): ?>
                                <button type="button" class="btn btn-secondary" onclick="salvarEVoltar()">← Página Anterior</button>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <button type="button" class="btn btn-salvar-quiz" onclick="salvarEContinuar()">Salvar e Continuar →</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-salvar-quiz" style="font-weight: 500px;">Salvar Idioma e Quiz (Fim)</button>
                            <?php endif; ?>
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
                        <h5 class="modal-title" id="gerenciarIdiomasModalLabel">Idiomas Existentes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group">
                            <?php if (!empty($idiomas_db)): ?>
                            <?php foreach ($idiomas_db as $idioma): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($idioma['nome_idioma']); ?></span>
                                <div>
                                    <a href="gerenciador_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma['nome_idioma']); ?>" class="btn btn-quiz-manage btn-sm me-2">
                                        <i class="fas fa-cog me-1"></i>Gerenciar Quiz
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                        data-id="<?php echo urlencode($idioma['nome_idioma']); ?>" data-nome="<?php echo htmlspecialchars($idioma['nome_idioma']); ?>" data-tipo="idioma" data-action="excluir_idioma.php">
                                        <i class="fas fa-trash me-1"></i>Excluir
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <li class="list-group-item text-center">Nenhum idioma encontrado.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="modal-footer">
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
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmação de Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="confirmDeleteModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form id="deleteForm" method="POST" action="">
                            <input type="hidden" name="id" id="deleteItemId">
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Notificação -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notificationModalLabel">Notificação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="notificationModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Limpeza -->
        <div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmClearModalLabel">Confirmar Limpeza</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja limpar todos os dados salvos?</p>
                        <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" onclick="confirmarLimpezaFinal()">Limpar Dados</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Container de Toasts -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer">
        <!-- Os toasts serão inseridos aqui dinamicamente -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const iconMap = {
                'success': 'fa-check-circle',
                'danger': 'fa-exclamation-triangle',
                'warning': 'fa-exclamation-circle',
                'info': 'fa-info-circle'
            };
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${iconMap[tipo]} me-2"></i>${mensagem}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
            toast.show();
            
            // Remove o toast do DOM após ser ocultado
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Função para confirmar limpeza dos dados
        function confirmarLimpeza() {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmClearModal'));
            confirmModal.show();
        }
        
        // Função para confirmar limpeza final
        function confirmarLimpezaFinal() {
            mostrarToast('Limpando dados...', 'info');
            window.location.href = 'limpar_dados_temporarios.php';
        }
    </script>
    <script>
        // Função para salvar dados via AJAX
        function salvarDadosPagina() {
            const form = document.getElementById('quizForm');
            const formData = new FormData(form);
            
            return fetch('salvar_temporario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao salvar dados');
                }
                return data;
            });
        }
        
        // Função para salvar e continuar para próxima página
        function salvarEContinuar() {
            salvarDadosPagina()
                .then(() => {
                    window.location.href = '?page=<?php echo $pagina_atual + 1; ?>';
                })
                .catch(error => {
                    mostrarToast('Erro ao salvar: ' + error.message, 'danger');
                });
        }
        
        // Função para salvar e voltar para página anterior
        function salvarEVoltar() {
            salvarDadosPagina()
                .then(() => {
                    window.location.href = '?page=<?php echo $pagina_atual - 1; ?>';
                })
                .catch(error => {
                    mostrarToast('Erro ao salvar: ' + error.message, 'danger');
                });
        }
        
        // Função para navegar para qualquer página
        function navegarPagina(pagina) {
            salvarDadosPagina()
                .then(() => {
                    window.location.href = '?page=' + pagina;
                })
                .catch(error => {
                    mostrarToast('Erro ao salvar: ' + error.message, 'danger');
                });
        }
        
        // Auto-salvar a cada 30 segundos
        setInterval(() => {
            salvarDadosPagina().catch(() => {});
        }, 30000);
    </script>
    <script>
        // Menu hamburguer functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (hamburgerBtn && sidebar) {
                hamburgerBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    mainContent.classList.toggle('expanded');
                    sidebarOverlay.classList.toggle('show');
                });

                // Fechar menu ao clicar no overlay
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('expanded');
                    sidebarOverlay.classList.remove('show');
                });

                // Fechar menu ao clicar em um link (em dispositivos móveis)
                const sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            sidebar.classList.remove('show');
                            mainContent.classList.remove('expanded');
                            sidebarOverlay.classList.remove('show');
                        }
                    });
                });

                // Fechar menu ao redimensionar a janela para tamanho maior
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 992) {
                        sidebar.classList.remove('show');
                        mainContent.classList.remove('expanded');
                        sidebarOverlay.classList.remove('show');
                    }
                });
            }

            // Lógica para o modal de confirmação de exclusão
            const confirmDeleteModal = document.getElementById('confirmDeleteModal');
            if (confirmDeleteModal) {
                confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const itemId = button.getAttribute('data-id');
                    const itemName = button.getAttribute('data-nome');
                    const itemType = button.getAttribute('data-tipo');
                    const formAction = button.getAttribute('data-action');

                    const modalBody = confirmDeleteModal.querySelector('#confirmDeleteModalBody');
                    const modalForm = confirmDeleteModal.querySelector('#deleteForm');
                    const hiddenInput = confirmDeleteModal.querySelector('#deleteItemId');

                    let message = '';
                    if (itemType === 'idioma') {
                        message = `Tem certeza que deseja excluir o idioma '<strong>${itemName}</strong>'? Isso excluirá todos os caminhos, exercícios e quizzes associados a ele.`;
                    } else {
                        message = `Tem certeza que deseja excluir o caminho '<strong>${itemName}</strong>'?`;
                    }

                    modalBody.innerHTML = `<p>${message}</p>`;
                    modalForm.action = formAction;
                    hiddenInput.value = itemId;
                });
            }

            // Lógica para o modal de notificação
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');

            if (status && message) {
                const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
                const modalBody = document.getElementById('notificationModalBody');

                modalBody.textContent = decodeURIComponent(message.replace(/\+/g, ' '));

                const modalTitle = document.getElementById('notificationModalLabel');
                if (status === 'success') {
                    modalTitle.textContent = 'Sucesso';
                } else if (status === 'error') {
                    modalTitle.textContent = 'Erro';
                }

                notificationModal.show();
                window.history.replaceState({}, document.title, window.location.pathname);
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
            bottom: 10px;
            right: 20px;
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