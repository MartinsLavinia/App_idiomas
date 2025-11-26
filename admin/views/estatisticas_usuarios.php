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

$id_admin = $_SESSION['id_admin'];

// Verificar se a coluna foto_perfil existe antes de tentar buscar
$foto_admin = null;
$check_column_sql = "SHOW COLUMNS FROM administradores LIKE 'foto_perfil'";
$result_check = $conn->query($check_column_sql);

if ($result_check && $result_check->num_rows > 0) {
    // A coluna existe, podemos fazer a consulta
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

// Estatísticas gerais de usuários
$sql_total_usuarios = "SELECT COUNT(*) as total FROM usuarios";
$result_total = $conn->query($sql_total_usuarios);
$total_usuarios = $result_total ? $result_total->fetch_assoc()['total'] : 0;

// Usuários ativos (que fizeram login nos últimos 30 dias)
$sql_usuarios_ativos = "SELECT COUNT(*) as ativos FROM usuarios WHERE ultimo_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_ativos = $conn->query($sql_usuarios_ativos);
$usuarios_ativos = $result_ativos ? $result_ativos->fetch_assoc()['ativos'] : 0;

// Usuários por nível (baseado no último quiz realizado)
$sql_usuarios_por_nivel = "
    SELECT 
        COALESCE(nivel_final, 'Sem nível') as nivel,
        COUNT(*) as quantidade
    FROM (
        SELECT 
            u.id,
            (SELECT qr.nivel_resultado 
             FROM quiz_resultados qr 
             WHERE qr.id_usuario = u.id 
             ORDER BY qr.data_realizacao DESC 
             LIMIT 1) as nivel_final
        FROM usuarios u
    ) as usuarios_com_nivel
    GROUP BY nivel_final
    ORDER BY
        CASE nivel_final
            WHEN 'A1' THEN 1
            WHEN 'A2' THEN 2
            WHEN 'B1' THEN 3
            WHEN 'B2' THEN 4
            WHEN 'C1' THEN 5
            WHEN 'C2' THEN 6
            ELSE 7
        END
";


$result_niveis = $conn->query($sql_usuarios_por_nivel);
$usuarios_por_nivel = [];
if ($result_niveis) {
    while ($row = $result_niveis->fetch_assoc()) {
        $usuarios_por_nivel[] = $row;
    }
}

// Idiomas disponíveis e sua popularidade
$sql_idiomas_populares = "
    SELECT 
        i.nome_idioma as idioma,
        COALESCE(COUNT(DISTINCT pu.id_usuario), 0) as usuarios_unicos,
        COALESCE(COUNT(pu.id), 0) as total_progressos
    FROM idiomas i
    LEFT JOIN progresso_usuario pu ON i.nome_idioma = pu.idioma
    GROUP BY i.nome_idioma
    ORDER BY usuarios_unicos DESC, i.nome_idioma ASC
";
$result_idiomas = $conn->query($sql_idiomas_populares);
$idiomas_populares = [];
if ($result_idiomas) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas_populares[] = $row;
    }
}

// Progresso dos usuários nos caminhos
$sql_progresso_caminhos = "
    SELECT
        COALESCE(ca.idioma, 'Desconhecido') as idioma,
        COALESCE(ca.nivel, 'N/A') as nivel,
        COUNT(DISTINCT pu.id_usuario) as usuarios_iniciaram,
        AVG(pu.progresso) as progresso_medio
    FROM progresso_usuario pu
    LEFT JOIN caminhos_aprendizagem ca ON pu.caminho_id = ca.id
    WHERE pu.progresso > 0
    GROUP BY ca.idioma, ca.nivel
    ORDER BY ca.idioma, ca.nivel
";
$result_progresso = $conn->query($sql_progresso_caminhos);
$progresso_caminhos = [];
if ($result_progresso) {
    while ($row = $result_progresso->fetch_assoc()) {
        $progresso_caminhos[] = $row;
    }
}

// Usuários registrados por mês (últimos 12 meses)
$sql_registros_mensais = "
    SELECT
        DATE_FORMAT(data_registro, '%Y-%m') as mes,
        COUNT(*) as novos_usuarios
    FROM usuarios
    WHERE data_registro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(data_registro, '%Y-%m')
    ORDER BY mes DESC
";
$result_mensais = $conn->query($sql_registros_mensais);
$registros_mensais = [];
if ($result_mensais) {
    while ($row = $result_mensais->fetch_assoc()) {
        $registros_mensais[] = $row;
    }
}

// Exercícios mais realizados
$sql_exercicios_populares = "
    SELECT
        e.pergunta,
        COALESCE(ca.idioma, 'Desconhecido') as idioma,
        COALESCE(ca.nivel, 'N/A') as nivel,
        COUNT(re.id) as total_realizacoes,
        AVG(CASE WHEN re.pontuacao IS NOT NULL THEN re.pontuacao ELSE 0 END) as pontuacao_media
    FROM respostas_exercicios re
    JOIN exercicios e ON re.id_exercicio = e.id
    LEFT JOIN caminhos_aprendizagem ca ON e.caminho_id = ca.id
    GROUP BY e.id, e.pergunta, ca.idioma, ca.nivel
    HAVING total_realizacoes > 0
    ORDER BY total_realizacoes DESC
    LIMIT 15
";
$result_exercicios = $conn->query($sql_exercicios_populares);
$exercicios_populares = [];
if ($result_exercicios) {
    while ($row = $result_exercicios->fetch_assoc()) {
        $exercicios_populares[] = $row;
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas de Usuários - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-container {
            height: 300px;
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
        
        .chart-container {
            height: 250px;
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

    /* Containers para gráficos */
    .chart-container {
        background: var(--branco);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        border: none;
        height: 400px;
    }

    .table-container {
        background: var(--branco);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px #ab4aef63;
        border: none;
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

    /* Badges personalizadas */
    .badge {
        font-weight: 600;
        padding: 0.5em 1em;
        border-radius: 50px;
    }

    .badge.bg-primary {
        background-color: var(--roxo-principal) !important;
    }

    .badge.bg-success {
        background-color: #28a745 !important;
    }

    .badge.bg-warning {
        background-color: var(--amarelo-detalhe) !important;
        color: var(--preto-texto);
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
    }

    .badge.bg-secondary {
        background-color: var(--cinza-medio) !important;
        color: var(--preto-texto);
    }

    /* Barras de progresso */
    .progress {
        height: 20px;
        background-color: var(--cinza-medio);
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        background-color: var(--amarelo-detalhe);
        transition: width 0.5s ease;
        color: var(--preto-texto);
        font-weight: 600;
    }

    /* Títulos */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(106, 13, 173, 0.2);
    }

    .chart-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .chart-title i {
        color: var(--roxo-principal);
        font-size: 1.5rem;
    }

   .btn-back {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid var(--roxo-principal);
        color: var(--roxo-principal);
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-back:hover {
        background-color: var(--roxo-escuro);
        border-color: var(--branco); 
        color: var(--branco);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    @media (max-width: 576px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .btn-back {
            align-self: flex-start;
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

    // Gráficos
    const niveisCtx = document.getElementById('niveisChart').getContext('2d');
    const niveisChart = new Chart(niveisCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo !empty($usuarios_por_nivel) ? implode(',', array_map(function($n) { return '"' . $n['nivel'] . '"'; }, $usuarios_por_nivel)) : ''; ?>],
            datasets: [{
                data: [<?php echo !empty($usuarios_por_nivel) ? implode(',', array_map(function($n) { return $n['quantidade']; }, $usuarios_por_nivel)) : ''; ?>],
                backgroundColor: [
                    '#6a0dad', '#8e44ad', '#9b59b6', '#af7ac5', '#c39bd3', '#d7bde2', '#e8daef'
                ],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverBorderWidth: 5,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '600'
                        },
                        color: '#2d3e8f'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(106, 13, 173, 0.95)',
                    titleColor: '#ffd700',
                    bodyColor: '#ffffff',
                    borderColor: '#ffd700',
                    borderWidth: 2,
                    cornerRadius: 12,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            return 'Nível: ' + context[0].label;
                        },
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.parsed + ' usuários (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1800,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Gráfico de Idiomas Populares
    const idiomasCtx = document.getElementById('idiomasChart').getContext('2d');
    const idiomasChart = new Chart(idiomasCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo !empty($idiomas_populares) ? implode(',', array_map(function($i) { return '"' . $i['idioma'] . '"'; }, $idiomas_populares)) : ''; ?>],
            datasets: [{
                label: 'Usuários por Idioma',
                data: [<?php echo !empty($idiomas_populares) ? implode(',', array_map(function($i) { return max(1, $i['usuarios_unicos']); }, $idiomas_populares)) : ''; ?>],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#36A2EB'
                ],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverBorderWidth: 5,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: {
                            size: 12,
                            weight: '600'
                        },
                        color: '#2d3e8f'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(45, 62, 143, 0.95)',
                    titleColor: '#ffd700',
                    bodyColor: '#ffffff',
                    borderColor: '#ffd700',
                    borderWidth: 2,
                    cornerRadius: 12,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            return 'Idioma: ' + context[0].label;
                        },
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.parsed + ' usuários (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false
            }
        }
    });

    // Gráfico de Registros Mensais
    const registrosCtx = document.getElementById('registrosChart').getContext('2d');
    
    // Criar gradiente
    const gradient = registrosCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(106, 13, 173, 0.8)');
    gradient.addColorStop(0.5, 'rgba(106, 13, 173, 0.4)');
    gradient.addColorStop(1, 'rgba(106, 13, 173, 0.1)');
    
    const registrosChart = new Chart(registrosCtx, {
        type: 'line',
        data: {
            labels: [<?php echo !empty($registros_mensais) ? implode(',', array_map(function($r) { 
                $mes = date('m/Y', strtotime($r['mes'] . '-01'));
                return '"' . $mes . '"'; 
            }, array_reverse($registros_mensais))) : ''; ?>],
            datasets: [{
                label: 'Novos Usuários',
                data: [<?php echo !empty($registros_mensais) ? implode(',', array_map(function($r) { 
                    return $r['novos_usuarios']; 
                }, array_reverse($registros_mensais))) : ''; ?>],
                borderColor: '#6a0dad',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#ffd700',
                pointBorderColor: '#6a0dad',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        color: '#2d3e8f',
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(106, 13, 173, 0.95)',
                    titleColor: '#ffd700',
                    bodyColor: '#ffffff',
                    borderColor: '#ffd700',
                    borderWidth: 2,
                    cornerRadius: 12,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(106, 13, 173, 0.1)',
                        lineWidth: 1
                    },
                    ticks: {
                        color: '#2d3e8f',
                        font: {
                            size: 11,
                            weight: '500'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(106, 13, 173, 0.1)',
                        lineWidth: 1
                    },
                    ticks: {
                        color: '#2d3e8f',
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return value + ' usuários';
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
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
        <a href="gerenciar_usuarios.php" class="list-group-item">
            <i class="fas fa-users"></i> Gerenciar Usuários
        </a>
        <a href="estatisticas_usuarios.php" class="list-group-item active">
            <i class="fas fa-chart-bar"></i> Estatísticas
        </a>
    </div>
</div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="page-header">
                <h2 class="mb-0"><i class="fas fa-chart-bar"></i> Estatísticas de Usuários</h2>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo number_format($total_usuarios); ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-user-check"></i>
                        <h3><?php echo number_format($usuarios_ativos); ?></h3>
                        <p>Usuários Ativos (30 dias)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-globe"></i>
                        <h3><?php echo count($idiomas_populares); ?></h3>
                        <p>Idiomas Disponíveis</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line"></i>
                        <h3><?php echo number_format(($usuarios_ativos / max($total_usuarios, 1)) * 100, 1); ?>%</h3>
                        <p>Taxa de Atividade</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="table-container">
                        <div class="chart-title">
                            <i class="fas fa-users"></i>
                            <h4 class="mb-0">Distribuição por Níveis</h4>
                        </div>
                        <div class="chart-container">
                            <canvas id="niveisChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="table-container">
                        <div class="chart-title">
                            <i class="fas fa-globe-americas"></i>
                            <h4 class="mb-0">Idiomas Mais Populares</h4>
                        </div>
                        <div class="chart-container">
                            <canvas id="idiomasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="table-container">
                        <div class="chart-title">
                            <i class="fas fa-chart-line"></i>
                            <h4 class="mb-0">Novos Registros (Últimos 12 Meses)</h4>
                        </div>
                        <div class="chart-container">
                            <canvas id="registrosChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="table-container">
                        <div class="chart-title">
                            <i class="fas fa-road"></i>
                            <h4 class="mb-0">Progresso nos Caminhos</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Idioma</th>
                                        <th>Nível</th>
                                        <th>Usuários</th>
                                        <th>Progresso Médio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($progresso_caminhos)): ?>
                                        <?php foreach ($progresso_caminhos as $progresso): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($progresso['idioma']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($progresso['nivel']); ?></span></td>
                                            <td><?php echo number_format($progresso['usuarios_iniciaram']); ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                        style="width: <?php echo round($progresso['progresso_medio']); ?>%"
                                                        aria-valuenow="<?php echo round($progresso['progresso_medio']); ?>" 
                                                        aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo round($progresso['progresso_medio']); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nenhum dado de progresso disponível</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="table-container">
                        <div class="chart-title">
                            <i class="fas fa-trophy"></i>
                            <h4 class="mb-0">Exercícios Mais Realizados</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Exercício</th>
                                        <th>Idioma/Nível</th>
                                        <th>Realizações</th>
                                        <th>Pontuação Média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($exercicios_populares)): ?>
                                        <?php foreach ($exercicios_populares as $exercicio): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($exercicio['pergunta'], 0, 30)) . (strlen($exercicio['pergunta']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($exercicio['idioma']); ?> - 
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($exercicio['nivel']); ?></span>
                                                </small>
                                            </td>
                                            <td><?php echo number_format($exercicio['total_realizacoes']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $exercicio['pontuacao_media'] >= 80 ? 'success' : ($exercicio['pontuacao_media'] >= 60 ? 'warning' : 'danger'); ?>">
                                                    <?php echo round($exercicio['pontuacao_media'], 1); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nenhum dado de exercícios disponível</td>
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