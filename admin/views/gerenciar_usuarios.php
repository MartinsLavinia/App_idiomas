<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar foto do admin (igual ao outro arquivo)
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

// Filtros de pesquisa
$filtro_nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$filtro_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';

// Query base para buscar usuários - CORRIGIDA
$sql_usuarios = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.data_registro,
        u.ultimo_login,
        u.ativo,
        CASE 
            WHEN u.ultimo_login IS NULL THEN 'Nunca logou'
            WHEN u.ultimo_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Inativo há mais de 30 dias'
            ELSE 'Ativo'
        END as status_login,
        CASE 
            WHEN u.ultimo_login IS NULL THEN NULL
            ELSE DATEDIFF(NOW(), u.ultimo_login)
        END as dias_sem_login,
        COALESCE(
            (SELECT qr2.nivel_resultado 
             FROM quiz_resultados qr2 
             WHERE qr2.id_usuario = u.id 
             ORDER BY qr2.data_realizacao DESC 
             LIMIT 1), 
            'Não avaliado'
        ) as nivel_atual,
        COUNT(DISTINCT CASE WHEN pu.progresso > 0 THEN pu.caminho_id END) as caminhos_iniciados,
        COALESCE(AVG(CASE WHEN pu.progresso > 0 THEN pu.progresso END), 0) as progresso_medio
    FROM usuarios u
    LEFT JOIN progresso_usuario pu ON u.id = pu.id_usuario
    WHERE u.ativo = 1
";

$params = [];
$types = '';

if (!empty($filtro_nome)) {
    $sql_usuarios .= " AND u.nome LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= 's';
}

if (!empty($filtro_email)) {
    $sql_usuarios .= " AND u.email LIKE ?";
    $params[] = "%$filtro_email%";
    $types .= 's';
}

if ($filtro_status !== '') {
    if ($filtro_status === '0') {
        $sql_usuarios = str_replace('WHERE u.ativo = 1', 'WHERE 1=1', $sql_usuarios);
    }
    $sql_usuarios .= " AND u.ativo = ?";
    $params[] = $filtro_status;
    $types .= 'i';
}

if (!empty($filtro_nivel)) {
    if ($filtro_nivel === 'Não avaliado') {
        $sql_usuarios .= " AND NOT EXISTS (
            SELECT 1 FROM quiz_resultados qr3 
            WHERE qr3.id_usuario = u.id
        )";
    } else {
        $sql_usuarios .= " AND EXISTS (
            SELECT 1 FROM quiz_resultados qr4 
            WHERE qr4.id_usuario = u.id 
            AND qr4.nivel_resultado = ?
            ORDER BY qr4.data_realizacao DESC 
            LIMIT 1
        )";
        $params[] = $filtro_nivel;
        $types .= 's';
    }
}

$sql_usuarios .= " GROUP BY u.id, u.nome, u.email, u.data_registro, u.ultimo_login, u.ativo";
$sql_usuarios .= " ORDER BY u.data_registro DESC";

$stmt_usuarios = $conn->prepare($sql_usuarios);
if (!empty($params)) {
    $stmt_usuarios->bind_param($types, ...$params);
}

$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_usuarios->close();

// Estatísticas rápidas - Calculadas dinamicamente
$sql_stats = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as usuarios_ativos,
    SUM(CASE WHEN ultimo_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ativos_semana,
    SUM(CASE WHEN data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as novos_mes
    FROM usuarios";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
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
        padding: 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(106, 13, 173, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card-header h5 {
        font-size: 1.3rem;
        font-family: 'Poppins', sans-serif;
    }

    .card-header h5 i {
        color: var(--amarelo-detalhe);
    }

    /* Cartões de Estatísticas */
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
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
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

    .badge {
        font-weight: 600;
        padding: 0.5em 1em;
        border-radius: 50px;
    }

    .badge.bg-primary {
        background-color: var(--roxo-principal) !important;
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

    /* Avatar do usuário */
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-escuro) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }

    .status-badge {
        font-size: 0.8rem;
    }

    /* Efeito de brilho para o botão Pesquisar */
    .pesquisar-btn {
        background-color: var(--amarelo-detalhe);
        border-color: var(--amarelo-detalhe);
        color: var(--preto-texto);
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .pesquisar-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: left 0.5s ease;
    }

    .pesquisar-btn:hover::before {
        left: 100%;
    }

    .pesquisar-btn:hover {
        background-color: #e6c200;
        border-color: #e6c200;
        transform: scale(1.05);
        color: var(--preto-texto);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
    }

    /* Barras de progresso personalizadas */
    .progress {
        height: 20px;
        background-color: var(--cinza-medio);
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        background-color: var(--amarelo-detalhe);
        transition: width 0.5s ease;
    }

    @media (max-width: 576px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
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

.navbar .logout-icon i {
    color: var(--roxo-principal) !important;
}

.navbar .logout-icon:hover i {
    color: var(--roxo-escuro) !important;
}


        /* Correção para o ícone de usuários no header */
.page-header-icon {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
    border: 2px solid var(--amarelo-detalhe);
}

.page-header-icon i {
    color: var(--amarelo-detalhe) !important;
    font-size: 1.5rem;
}

/* Ajuste do título para incluir o ícone */
.page-header h1 {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

/* FUNDO ROXO APENAS NO HEADER DOS FILTROS */
.filtros-header-roxo .card-header {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px 15px 0 0 !important;
    padding: 15px 20px;
}

.filtros-header-roxo .card-header h5 {
    color: var(--branco) !important;
    font-weight: 600;
    margin: 0;
}

.filtros-header-roxo .card-header h5 i {
    color: var(--amarelo-detalhe) !important;
}

.filtros-header-roxo .card-body {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 0 0 15px 15px;
    padding: 20px;
}

/* Estilo para os elementos do formulário nos filtros */
.filtros-header .form-label {
    color: var(--roxo-principal) !important;
    font-weight: 600;
    font-size: 0.9rem;
}

.filtros-heade .form-control,
.filtros-header .form-select {
    border: 2px solid var(--roxo-principal);
    border-radius: 8px;
    font-size: 0.9rem;
}

.filtros-header .form-control:focus,
.filtros-header .form-select:focus {
    border-color: var(--roxo-escuro);
    box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
}

.filtros-header .btn-warning {
    background: linear-gradient(135deg, var(--amarelo-detalhe), #ffc107);
    border: none;
    color: var(--preto-texto);
    font-weight: 600;
    transition: all 0.3s ease;
}

.filtros-header .btn-warning:hover {
    background: linear-gradient(135deg, #e6c200, #e0a800);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
}
        
    /* Modal de Confirmação */
    #confirmModal .modal-content {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(106, 13, 173, 0.2);
    }

    #confirmModal .modal-header {
        background: var(--roxo-principal);
        color: var(--branco);
        border-bottom: none;
        padding: 1.5rem;
    }

    #confirmModal .btn-close {
        filter: brightness(0) invert(1);
        opacity: 1;
    }

    #confirmModal .modal-title {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    #confirmModal .modal-title::before {
        content: '⚠️';
        font-size: 1.2rem;
    }

    #confirmModal .modal-body {
        padding: 1.5rem;
        text-align: center;
    }

    #confirmModal .modal-body .text-danger {
        background: rgba(255, 215, 0, 0.1);
        border-left: 3px solid var(--amarelo-detalhe);
        padding: 0.75rem;
        border-radius: 5px;
        color: var(--roxo-escuro);
        font-weight: 600;
    }

    #confirmModal .modal-footer {
        border-top: 1px solid var(--cinza-medio);
        padding: 1rem 1.5rem;
    }

    #confirmModal .btn-secondary {
        background: var(--cinza-medio);
        border: none;
        color: var(--preto-texto);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #confirmModal .btn-secondary:hover {
        background: #6c757d;
        color: var(--branco);
    }

    #confirmModal .btn-warning {
        background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
        border: none;
        color: var(--preto-texto);
        font-weight: 600;
        border-radius: 4px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    }

    #confirmModal .btn-warning:hover {
        background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 217, 0, 0.4);
        color: var(--preto-texto);
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
        <a href="gerenciar_caminho.php" class="list-group-item">
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
        <a href="gerenciar_usuarios.php" class="list-group-item active">
            <i class="fas fa-users"></i> Gerenciar Usuários
        </a>
        <a href="estatisticas_usuarios.php" class="list-group-item">
            <i class="fas fa-chart-bar"></i> Estatísticas
        </a>
    </div>
</div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2"><i class="fas fa-users" style="color: black;"></i> Gerenciar Usuários</h1>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3><?php echo $stats['total_usuarios']; ?></h3>
                        <p><i class="fas fa-users" style="color: var(--amarelo-detalhe);"></i> Total de Usuários</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3><?php echo $stats['usuarios_ativos']; ?></h3>
                        <p><i class="fas fa-user-check"></i> Contas Ativas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3><?php echo $stats['ativos_semana']; ?></h3>
                        <p><i class="fas fa-calendar-week"></i> Ativos esta Semana</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3><?php echo $stats['novos_mes']; ?></h3>
                        <p><i class="fas fa-user-plus"></i> Novos este Mês</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de Pesquisa - Versão Simplificada -->
<div class="table-container mb-4 filtros-header-roxo">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-search"></i> Filtros de Pesquisa</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Buscar por nome">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="Buscar por email">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $filtro_status === '1' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo $filtro_status === '0' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="nivel" class="form-label">Nível</label>
                    <select class="form-select" id="nivel" name="nivel">
                        <option value="">Todos</option>
                        <option value="Não avaliado" <?php echo $filtro_nivel === 'Não avaliado' ? 'selected' : ''; ?>>Não avaliado</option>
                        <option value="A1" <?php echo $filtro_nivel === 'A1' ? 'selected' : ''; ?>>A1</option>
                        <option value="A2" <?php echo $filtro_nivel === 'A2' ? 'selected' : ''; ?>>A2</option>
                        <option value="B1" <?php echo $filtro_nivel === 'B1' ? 'selected' : ''; ?>>B1</option>
                        <option value="B2" <?php echo $filtro_nivel === 'B2' ? 'selected' : ''; ?>>B2</option>
                        <option value="C1" <?php echo $filtro_nivel === 'C1' ? 'selected' : ''; ?>>C1</option>
                        <option value="C2" <?php echo $filtro_nivel === 'C2' ? 'selected' : ''; ?>>C2</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-12">
                    <label class="form-label d-none d-md-block" style="visibility: hidden;">Pesquisar</label>
                    <button type="submit" class="btn btn-warning w-100" style="height: 38px; font-size: 0.875rem;">
                       <i class="fas fa-filter"></i> Pesquisar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

            <!-- Lista de Usuários -->
            <div class="table-container" style="margin-top: 30px;">
                <div class="card-header">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Nível Atual</th>
                                    <th>Progresso</th>
                                    <th>Registro</th>
                                    <th>Último Login</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($usuarios)): ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                                    <br><small class="text-muted">ID: <?php echo $usuario['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $usuario['nivel_atual'] === 'Não avaliado' ? 'secondary' : 'primary'; ?>">
                                                <?php echo htmlspecialchars($usuario['nivel_atual']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['caminhos_iniciados'] > 0): ?>
                                                <small><?php echo $usuario['caminhos_iniciados']; ?> caminhos</small><br>
                                                <div class="progress mb-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo round($usuario['progresso_medio'] ?? 0); ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo round($usuario['progresso_medio'] ?? 0); ?>% concluído</small>
                                            <?php else: ?>
                                                <div class="progress mb-1" style="height: 8px;">
                                                    <div class="progress-bar bg-secondary" style="width: 0%"></div>
                                                </div>
                                                <small class="text-muted">0% - Não iniciado</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y', strtotime($usuario['data_registro'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($usuario['ultimo_login']): ?>
                                                <?php 
                                                $dias_sem_login = $usuario['dias_sem_login'];
                                                if ($dias_sem_login >= 30): 
                                                ?>
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                                        <br><span class="badge bg-danger">Há <?php echo $dias_sem_login; ?> dias</span>
                                                    </small>
                                                <?php elseif ($dias_sem_login == 0): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                                        <br><span class="badge bg-success">Hoje</span>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                                        <br><span class="badge bg-success">Há <?php echo $dias_sem_login; ?> dias</span>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    Nunca logou
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_real = $usuario['status_login'];
                                            $cor_badge = 'secondary';
                                            if ($status_real === 'Ativo') {
                                                $cor_badge = 'success';
                                            } elseif ($status_real === 'Inativo há mais de 30 dias') {
                                                $cor_badge = 'danger';
                                                $status_real = 'Inativo';
                                            } elseif ($status_real === 'Nunca logou') {
                                                $cor_badge = 'warning';
                                            }
                                            ?>
                                            <span class="badge status-badge bg-<?php echo $cor_badge; ?>">
                                                <?php echo $status_real; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userDetailsModal"
                                                        onclick="loadUserDetails(<?php echo $usuario['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-<?php echo $usuario['ativo'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleUserStatus(<?php echo $usuario['id']; ?>, <?php echo $usuario['ativo'] ? 0 : 1; ?>)"
                                                        title="<?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?> usuário">
                                                    <i class="fas fa-<?php echo $usuario['ativo'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Nenhum usuário encontrado com os filtros aplicados.</p>
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

    <!-- Modal de Detalhes do Usuário -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-warning text-dark fw-bold">
                    <h5 class="modal-title d-flex align-items-center" id="userDetailsModalLabel">
                        <i class="fas fa-user-circle me-2"></i>
                        Detalhes do Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="userDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3 text-muted">Carregando informações do usuário...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Tem certeza que deseja realizar esta ação?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação alterará o status do usuário!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirmBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        :root {
            --amarelo-detalhe: #ffd700;
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
        }
        
        .avatar-lg {
            width: 60px;
            height: 60px;
        }
        
        .object-fit-cover {
            object-fit: cover;
        }
        
        .progress-item:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        
        .quiz-item:hover, .exercise-item:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card {
            transition: all 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .progress-item, .quiz-item, .exercise-item {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .progress-item:hover, .quiz-item:hover, .exercise-item:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .info-card {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .info-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        
        .progress-bar {
            transition: width 0.6s ease;
        }
        
        .badge {
            font-weight: 500;
        }
        
        .modal-xl {
            max-width: 1200px;
        }
        
        @media (max-width: 768px) {
            .modal-xl {
                max-width: 95%;
                margin: 1rem;
            }
        }
    </style>
    <script>
        function loadUserDetails(userId) {
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando informações do usuário...</p>
                </div>
            `;
            
            fetch(`detalhes_usuario.php?id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('userDetailsContent').innerHTML = data;
                    // Animar as barras de progresso
                    setTimeout(() => {
                        const progressBars = document.querySelectorAll('.progress-bar');
                        progressBars.forEach(bar => {
                            const width = bar.style.width;
                            bar.style.width = '0%';
                            setTimeout(() => {
                                bar.style.width = width;
                            }, 100);
                        });
                    }, 100);
                })
                .catch(error => {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="alert alert-danger m-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar detalhes do usuário.
                        </div>
                    `;
                });
        }

        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'ativar' : 'desativar';
            const message = `Tem certeza que deseja ${action} este usuário?`;
            
            document.getElementById('confirmMessage').textContent = message;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            
            document.getElementById('confirmBtn').onclick = function() {
                confirmModal.hide();
                
                // Mostrar loading
                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';
                btn.disabled = true;
                
                fetch('toggle_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao alterar status do usuário: ' + data.message);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            };
            
            confirmModal.show();
        }
        
        // Melhorar a experiência do modal
        document.getElementById('userDetailsModal').addEventListener('shown.bs.modal', function () {
            // Focar no modal quando abrir
            this.focus();
        });
        
        document.getElementById('userDetailsModal').addEventListener('hidden.bs.modal', function () {
            // Limpar conteúdo quando fechar
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando informações do usuário...</p>
                </div>
            `;
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