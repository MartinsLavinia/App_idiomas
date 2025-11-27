<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: /../../index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";

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

// Buscar idiomas disponíveis do banco de dados
$sql_idiomas_disponiveis = "SELECT nome_idioma FROM idiomas ORDER BY nome_idioma ASC";
$result_idiomas = $conn->query($sql_idiomas_disponiveis);
$idiomas_disponiveis = [];
$idiomas_display = [];
if ($result_idiomas && $result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $nome_original = $row['nome_idioma'];
        $nome_normalizado = str_replace(['ê', 'ã'], ['e', 'a'], $nome_original);
        $idiomas_disponiveis[] = $nome_normalizado;
        $idiomas_display[$nome_normalizado] = $nome_original;
    }
}

// Buscar todos os idiomas que o usuário já estudou
$sql_idiomas_usuario = "SELECT idioma, nivel, data_inicio, ultima_atividade FROM progresso_usuario WHERE id_usuario = ? ORDER BY ultima_atividade DESC";
$stmt_idiomas_usuario = $conn->prepare($sql_idiomas_usuario);
$stmt_idiomas_usuario->bind_param("i", $id_usuario);
$stmt_idiomas_usuario->execute();
$idiomas_usuario = $stmt_idiomas_usuario->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas_usuario->close();

// Processa troca de idioma
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["trocar_idioma"])) {
    $novo_idioma = $_POST["novo_idioma"];
    
    $sql_check_progresso = "SELECT COUNT(*) as count FROM progresso_usuario WHERE id_usuario = ? AND idioma = ?";
    $stmt_check = $conn->prepare($sql_check_progresso);
    $stmt_check->bind_param("is", $id_usuario, $novo_idioma);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($result_check['count'] > 0) {
        $sql_update_atividade = "UPDATE progresso_usuario SET ultima_atividade = NOW() WHERE id_usuario = ? AND idioma = ?";
        $stmt_update = $conn->prepare($sql_update_atividade);
        $stmt_update->bind_param("is", $id_usuario, $novo_idioma);
        $stmt_update->execute();
        $stmt_update->close();
        
        $database->closeConnection();
        header("Location: flashcards.php");
        exit();
    } else {
        $sql_insert_novo = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, data_inicio, ultima_atividade) VALUES (?, ?, 'A1', NOW(), NOW())";
        $stmt_insert_novo = $conn->prepare($sql_insert_novo);
        $stmt_insert_novo->bind_param("is", $id_usuario, $novo_idioma);
        
        if ($stmt_insert_novo->execute()) {
            $stmt_insert_novo->close();
            $database->closeConnection();
            header("Location: ../../quiz.php?idioma=$novo_idioma");
            exit();
        }
        $stmt_insert_novo->close();
    }
}

// Limpar registros inválidos
$sql_clean = "DELETE FROM progresso_usuario WHERE id_usuario = ? AND (idioma IS NULL OR idioma = '' OR idioma LIKE '%object%' OR idioma LIKE '%PointerEvent%' OR idioma LIKE '%[%' OR LENGTH(idioma) > 50)";
$stmt_clean = $conn->prepare($sql_clean);
$stmt_clean->bind_param("i", $id_usuario);
$stmt_clean->execute();
$stmt_clean->close();

// Busca idioma válido
$sql_progresso = "SELECT idioma, nivel FROM progresso_usuario WHERE id_usuario = ? AND idioma REGEXP '^[a-zA-Z]+$' ORDER BY ultima_atividade DESC LIMIT 1";
$stmt_progresso = $conn->prepare($sql_progresso);
$stmt_progresso->bind_param("i", $id_usuario);
$stmt_progresso->execute();
$resultado = $stmt_progresso->get_result()->fetch_assoc();
$stmt_progresso->close();

if ($resultado && preg_match('/^[a-zA-Z]+$/', $resultado["idioma"]) && !empty($resultado["idioma"])) {
    $idioma_escolhido = $resultado["idioma"];
    $nivel_usuario = $resultado["nivel"];
} else {
    $idioma_escolhido = null;
    $nivel_usuario = 'A1';
}

// Fecha a conexão
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Cards - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="painel.css" rel="stylesheet">
     <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Paleta de Cores - MESMAS DO ADMIN */
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --cinza-medio: #dee2e6;
        }

        /* Estilos Gerais do Corpo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* SIDEBAR FIXO */
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

        .sidebar .profile .profile-avatar-sidebar {
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

        .sidebar .profile .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
            text-decoration: none;
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
            width: 20px;
            text-align: center;
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
                padding: 15px;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
            
            .deck-card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
            }
            
            .deck-card {
                padding: 15px;
            }
            
            .card-body {
                padding: 0.75rem;
            }
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        /* Deck Cards */
        .deck-card {
            background-color: var(--branco);
            border: 1px solid var(--cinza-medio);
            border-top: 4px solid transparent; /* Espaço para o gradiente no hover */
            border-radius: 1rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
        }

        .deck-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top-color: var(--roxo-principal);
        }

        .deck-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d; /* Cinza mais escuro para melhor leitura */
        }

        .deck-info-item i {
            color: var(--amarelo-detalhe);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .deck-info-item.public {
            color: #198754; /* Verde para público */
        }

        .deck-stats {
            border-top: 1px solid var(--cinza-medio);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--roxo-principal);
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            color: var(--cinza-escuro);
        }

        /* Botões */
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

        .btn-open-deck {
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
            border: none;
            color: var(--branco);
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.2);
        }

        .btn-open-deck:hover {
            background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
            color: var(--branco);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.3);
        }

        .btn-open-deck::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: skewX(-25deg);
            transition: left 0.8s ease;
        }

        .btn-warning {
            background-color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            color: var(--preto-texto);
            font-weight: 600;
        }

        /* Botão de Call-to-Action para o estado vazio */
        .btn-cta-empty {
            background: linear-gradient(135deg, var(--amarelo-detalhe) 0%, #f39c12 100%);
            border: none;
            color: var(--preto-texto);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-cta-empty:hover {
            background: linear-gradient(135deg, #f39c12 0%, var(--amarelo-detalhe) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }

        /* Foto do perfil no sidebar */
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
            background-color: #f2e9f9; /* Roxo bem claro */
            color: var(--roxo-escuro);
            transform: translateX(4px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fceaea; /* Vermelho bem claro */
            color: #b02a37;
            transform: translateX(4px);
        }

        /* Botão Limpar Filtros */
        .btn-limpar-filtros {
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            border: 1px solid var(--cinza-medio);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-limpar-filtros:hover {
            background-color: var(--cinza-medio);
            border-color: #adb5bd;
            transform: translateY(-2px);
        }

        /* Botão de opções */
        .btn-outline-secondary {
            border: 1px solid var(--cinza-medio);
            color: var(--preto-texto);
            padding: 0.25rem 0.5rem;
        }

        .btn-outline-secondary:hover {
            background-color: var(--cinza-medio);
            border-color: var(--cinza-medio);
        }

        /* Filtros */
        .filter-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
        }

        .spinner-border {
            color: var(--roxo-principal);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--branco);
            color: var(--preto-texto);
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--roxo-principal);
        }

        /* Botão CTA Empty com animação */
        .btn-cta-empty i {
            font-size: 1.3rem;
            font-weight: 700;
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }

        .btn-cta-empty:hover i {
            transform: rotate(180deg) scale(1.15);
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 6px 25px rgba(255, 215, 0, 0.5); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .filter-section {
                padding: 1rem;
            }
        }


        
        /* Estilos para o Modal de Deck */
        #modalDeck .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        #modalDeck .modal-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
        }

        #modalDeck .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        #modalDeck .modal-body {
            padding: 2rem;
        }

        #modalDeck .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
            padding: 1.5rem;
        }

        #modalDeck .form-label {
            font-weight: 600;
            color: var(--roxo-escuro);
        }

        #modalDeck .form-label i {
            color: var(--amarelo-detalhe);
            margin-right: 0.5rem;
        }

        #modalDeck .form-check-input:checked {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
        }

        #modalDeck .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--cinza-medio);
            color: var(--preto-texto);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #modalDeck .btn-secondary:hover {
            background-color: var(--cinza-claro);
            border-color: #adb5bd;
            transform: scale(1.05); /* Efeito de zoom */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        /* Estilos para o Modal de Confirmação de Exclusão */
        #modalConfirmarExclusao .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        #modalConfirmarExclusao .modal-header {
            background: var(--branco); /* Remove o fundo vermelho */
            color: #c82333; /* Deixa o texto vermelho */
            border-bottom: 1px solid var(--cinza-medio); /* Adiciona uma linha sutil */
            border-radius: 1rem 1rem 0 0;
            padding: 1.25rem 1.5rem;
        }

        #modalConfirmarExclusao .modal-header .btn-close {
            filter: none; /* Reseta o filtro do botão de fechar */
        }

        #modalConfirmarExclusao .modal-body {
            padding: 2.5rem;
            text-align: center;
        }

        #modalConfirmarExclusao .modal-body .icon-warning {
            font-size: 3.5rem;
            color: #e55353;
            margin-bottom: 1.5rem;
            display: block;
            animation: pulse-warning 1.5s infinite;
        }

        @keyframes pulse-warning {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        #modalConfirmarExclusao .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
            padding: 1.5rem;
            justify-content: center;
            gap: 1rem;
        }

        #modalConfirmarExclusao .modal-footer .btn-secondary {
            background: transparent;
            border: 2px solid #adb5bd;
            color: #495057;
            padding: 0.75rem 1.5rem; /* Garante o mesmo padding */
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #modalConfirmarExclusao .modal-footer .btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
            transform: translateY(-2px);
        }

        #modalConfirmarExclusao .modal-footer .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: var(--branco);
            padding: 0.75rem 1.5rem; /* Garante o mesmo padding */
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transform: skewX(-25deg);
            transition: left 0.8s ease;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger:hover::before {
            left: 150%;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71d2a);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: var(--branco);
        }
    </style>
</head>
<body>
    <!-- Menu Hamburguer -->
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

    <!-- Sidebar -->
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
            <div class="list-group-item">
                <div class="dropdown">
                    <a href="#" class="text-decoration-none text-white d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-language me-2" style="color: var(--amarelo-detalhe); width: 20px; text-align: center;"></i> Trocar Idioma
                    </a>
                    <ul class="dropdown-menu">
                        <?php 
                        $idiomas_ja_estudados = array_column($idiomas_usuario, 'idioma');
                        $tem_outros_idiomas = false;
                        $tem_novos_idiomas = false;
                        
                        // Verificar se há outros idiomas já estudados
                        foreach ($idiomas_usuario as $idioma_user) {
                            if ($idioma_user['idioma'] !== $idioma_escolhido) {
                                $tem_outros_idiomas = true;
                                break;
                            }
                        }
                        
                        // Verificar se há novos idiomas disponíveis (excluindo todos os já estudados)
                        foreach ($idiomas_disponiveis as $idioma_disponivel) {
                            if (!in_array($idioma_disponivel, $idiomas_ja_estudados) && !empty($idioma_disponivel)) {
                                $tem_novos_idiomas = true;
                                break;
                            }
                        }
                        ?>
                        
                        <!-- Idioma Atual -->
                        <li><h6 class="dropdown-header">Idioma Atual</h6></li>
                        <li>
                            <span class="dropdown-item-text">
                                <i class="fas fa-check-circle me-2 text-success"></i><?php echo htmlspecialchars($idiomas_display[$idioma_escolhido] ?? $idioma_escolhido); ?> (<?php echo htmlspecialchars($nivel_usuario); ?>)
                            </span>
                        </li>
                        
                        <?php if ($tem_outros_idiomas || $tem_novos_idiomas): ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        
                        <?php if ($tem_outros_idiomas): ?>
                            <li><h6 class="dropdown-header">Meus Outros Idiomas</h6></li>
                            <?php foreach ($idiomas_usuario as $idioma_user): ?>
                                <?php if ($idioma_user['idioma'] !== $idioma_escolhido): ?>
                                    <li>
                                        <button type="button" class="dropdown-item" onclick="trocarIdioma('<?php echo htmlspecialchars($idioma_user['idioma']); ?>')">
                                            <i class="fas fa-exchange-alt me-2"></i><?php echo htmlspecialchars($idiomas_display[$idioma_user['idioma']] ?? $idioma_user['idioma']); ?> (<?php echo htmlspecialchars($idioma_user['nivel']); ?>)
                                        </button>
                                    </li>
                                <?php endif; ?>
            <?php endforeach; ?>
                            <?php if ($tem_novos_idiomas): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($tem_novos_idiomas): ?>
                            <li><h6 class="dropdown-header">Começar Novo Idioma</h6></li>
                            <?php foreach ($idiomas_disponiveis as $idioma_disponivel): ?>
                                <?php if (!in_array($idioma_disponivel, $idiomas_ja_estudados) && !empty($idioma_disponivel)): ?>
                                    <li>
                                        <button type="button" class="dropdown-item" onclick="iniciarNovoIdioma('<?php echo htmlspecialchars($idioma_disponivel); ?>')">
                                            <i class="fas fa-plus me-2"></i><?php echo htmlspecialchars($idiomas_display[$idioma_disponivel] ?? $idioma_disponivel); ?>
                                        </button>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-11">
                    <!-- Cabeçalho -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1 class="mb-2">
                                        <i class="fas fa-layer-group me-2 text-warning"></i>
                                        Flash Cards
                                    </h1>
                                    <p class="text-muted mb-0">Estude com flashcards personalizados</p>
                                </div>
                                <div>
                                   <button class="btn btn-primary me-2" onclick="abrirModalCriarDeck()">
                <i class="fas fa-plus me-2"></i>Novo Deck
            </button>
                                    <button class="btn btn-warning" onclick="estudarFlashcards()">
                                        <i class="fas fa-play me-2"></i>Estudar Agora
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filter-section">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="filtroIdioma" class="form-label">Idioma</label>
                                <select class="form-select" id="filtroIdioma" onchange="aplicarFiltros()">
                                    <option value="">Todos os idiomas</option>
                                    <option value="Ingles" <?php echo $idioma_escolhido === 'Ingles' ? 'selected' : ''; ?>>Inglês</option>
                                    <option value="Japones" <?php echo $idioma_escolhido === 'Japones' ? 'selected' : ''; ?>>Japonês</option>
                                </select>
                            </div>
                    
                            <div class="col-md-3">
                                <label for="filtroNivel" class="form-label">Nível</label>
                                <select class="form-select" id="filtroNivel" onchange="aplicarFiltros()">
                                    <option value="">Todos os níveis</option>
                                    <option value="A1" <?php echo $nivel_usuario === 'A1' ? 'selected' : ''; ?>>A1</option>
                                    <option value="A2" <?php echo $nivel_usuario === 'A2' ? 'selected' : ''; ?>>A2</option>
                                    <option value="B1" <?php echo $nivel_usuario === 'B1' ? 'selected' : ''; ?>>B1</option>
                                    <option value="B2" <?php echo $nivel_usuario === 'B2' ? 'selected' : ''; ?>>B2</option>
                                    <option value="C1" <?php echo $nivel_usuario === 'C1' ? 'selected' : ''; ?>>C1</option>
                                    <option value="C2" <?php echo $nivel_usuario === 'C2' ? 'selected' : ''; ?>>C2</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="tipoDecks" class="form-label">Tipo</label>
                                <select class="form-select" id="tipoDecks" onchange="aplicarFiltros()">
                                    <option value="meus">Meus Decks</option>
                                    <option value="publicos">Decks Públicos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-limpar-filtros w-100" onclick="limparFiltros()">
                                    <i class="fas fa-times me-2"></i>Limpar Filtros
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Decks -->
                    <div id="listaDecks" class="row">
                        <!-- O conteúdo dos decks será carregado aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal Criar/Editar Deck -->
    <div class="modal fade" id="modalDeck" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalDeck"><i class="fas fa-layer-group me-2"></i>Novo Deck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formDeck">
                        <input type="hidden" id="deckId" name="id_deck">
                        <div class="mb-3">
                            <label for="deckNome" class="form-label"><i class="fas fa-heading"></i>Nome do Deck *</label>
                            <input type="text" class="form-control" id="deckNome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="deckDescricao" class="form-label"><i class="fas fa-align-left"></i>Descrição</label>
                            <textarea class="form-control" id="deckDescricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckIdioma" class="form-label"><i class="fas fa-language"></i>Idioma *</label>
                                    <select class="form-select" id="deckIdioma" name="idioma" required>
                                        <option value="">Selecione...</option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                        <!-- Adicionar mais idiomas aqui se necessário -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckNivel" class="form-label"><i class="fas fa-signal"></i>Nível *</label>
                                    <select class="form-select" id="deckNivel" name="nivel" required>
                                        <option value="">Selecione...</option>
                                        <option value="A1">A1</option>
                                        <option value="A2">A2</option>
                                        <option value="B1">B1</option>
                                        <option value="B2">B2</option>
                                        <option value="C1">C1</option>
                                        <option value="C2">C2</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="deckPublico" name="publico">
                                <label class="form-check-label" for="deckPublico">
                                    Tornar este deck público (outros usuários poderão estudá-lo)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="salvarDeck()">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalExclusao"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="fas fa-exclamation-circle icon-warning"></i>
                    <p id="mensagemModalExclusao" class="lead">Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        function trocarIdioma(idioma) {
            if (!idioma || idioma.trim() === '') {
                console.error('Idioma inválido');
                return;
            }
            
            const formData = new FormData();
            formData.append('trocar_idioma', '1');
            formData.append('novo_idioma', idioma.trim());
            
            fetch('flashcards.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                window.location.reload();
            });
        }
    </script>
    <script>
        // Função para trocar idioma
        function trocarIdioma(novoIdioma) {
            // Fazer requisição AJAX para trocar o idioma
            fetch('../../trocar_idioma.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'idioma=' + encodeURIComponent(novoIdioma)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recarregar a página para atualizar o conteúdo
                    window.location.reload();
                } else {
                    alert('Erro ao trocar idioma: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao trocar idioma. Tente novamente.');
            });
        }

        // Função para iniciar novo idioma
        function iniciarNovoIdioma(idioma) {
            if (confirm('Deseja começar a estudar ' + idioma + '? Você será redirecionado para o painel principal.')) {
                // Fazer requisição AJAX para iniciar novo idioma
                fetch('../../iniciar_novo_idioma.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'idioma=' + encodeURIComponent(idioma)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirecionar para o painel
                        window.location.href = 'painel.php';
                    } else {
                        alert('Erro ao iniciar novo idioma: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao iniciar novo idioma. Tente novamente.');
                });
            }
        }
    </script>
    <script src="flashcard_script.js"></script>

    
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

         .accessibility-panel {
            position: absolute;
            bottom: 10px;
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