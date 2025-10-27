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
        COALESCE(qr.nivel_resultado, 'Sem nível') as nivel,
        COUNT(DISTINCT u.id) as quantidade
    FROM usuarios u
    LEFT JOIN (
        SELECT
            id_usuario,
            nivel_resultado,
            ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY data_realizacao DESC) as rn
        FROM quiz_resultados
    ) qr ON u.id = qr.id_usuario AND qr.rn = 1
    GROUP BY nivel
    ORDER BY
        CASE
            WHEN nivel = 'A1' THEN 1
            WHEN nivel = 'A2' THEN 2
            WHEN nivel = 'B1' THEN 3
            WHEN nivel = 'B2' THEN 4
            WHEN nivel = 'C1' THEN 5
            WHEN nivel = 'C2' THEN 6
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

// Idiomas mais populares (baseado nos quizzes realizados)
$sql_idiomas_populares = "
    SELECT
        qn.idioma,
        COUNT(DISTINCT qr.id_usuario) as usuarios_unicos,
        COUNT(qr.id) as total_quizzes
    FROM quiz_resultados qr
    JOIN quiz_nivelamento qn ON qr.id_quiz = qn.id
    GROUP BY qn.idioma
    ORDER BY usuarios_unicos DESC
    LIMIT 10
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
        ca.idioma,
        ca.nivel,
        COUNT(DISTINCT pu.id_usuario) as usuarios_iniciaram,
        AVG(pu.progresso) as progresso_medio
    FROM progresso_usuario pu
    JOIN caminhos_aprendizagem ca ON pu.caminho_id = ca.id
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
        ca.idioma,
        ca.nivel,
        COUNT(re.id) as total_realizacoes,
        AVG(re.pontuacao) as pontuacao_media
    FROM respostas_exercicios re
    JOIN exercicios e ON re.id_exercicio = e.id
    JOIN caminhos_aprendizagem ca ON e.caminho_id = ca.id
    GROUP BY e.id, e.pergunta, ca.idioma, ca.nivel
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

    .logout-icon {
        color: var(--roxo-principal) !important;
        transition: all 0.3s ease;
        text-decoration: none;
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

    /* Cartões de Estatísticas - ATUALIZADO */
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

/* Bottom Navigation Bar para mobile */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3); /* Mesmo gradiente da sidebar */
    box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.15);
    z-index: 1020;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 5px 0;
}

.bottom-nav-item {
    flex: 1;
    text-align: center;
    color: var(--branco);
    text-decoration: none;
    padding: 8px 0;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.bottom-nav-item i {
    font-size: 1.5rem; /* Tamanho do ícone */
    display: block;
    margin: 0 auto;
    color: var(--amarelo-detalhe);
}

.bottom-nav-item.active {
    background-color: rgba(255, 255, 255, 0.15);
}

.bottom-nav-item.active i {
    transform: scale(1.1);
}

.bottom-nav-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

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
        padding: 20px 20px 80px 20px; /* Adiciona padding-bottom para a bottom-nav */
    }
    .sidebar {
        display: none !important; /* Esconde a sidebar desktop em telas menores */
    }
}
.main-content {
    margin-left: 250px;
    padding: 20px;
}

@media (max-width: 992px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
    }
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

    /* Responsividade */
    @media (max-width: 992px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }
        .main-content {
            margin-left: 0;
        }
    }

   /* Menu Lateral - ATUALIZADO */
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
    padding: 0 15px;
}

/* Container do avatar - PARA QUANDO TEM FOTO (80x80px COM CÍRCULO) */
.profile-avatar-sidebar {
    width: 80px;
    height: 80px;
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

/* Ícone quando NÃO tem foto - SEM CÍRCULO (apenas ícone) */
.sidebar .profile i.fa-user-circle {
    font-size: 4rem; /* Tamanho do ícone */
    color: var(--amarelo-detalhe);
    margin: 0 auto 15px;
    display: block;
    /* REMOVIDO: border, background, box-shadow, width, height */
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

/*--*/

.main-content {
    margin-left: 250px;
    padding: 20px;
}

@media (max-width: 992px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
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
    background-color:var(--roxo-escuro);
    border-color: var(--branco); 
    color: var(--branco);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
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
                <a href="logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

 <!-- Menu Lateral ATUALIZADO com foto de perfil -->
    <div class="sidebar">
        <div class="profile">
            <?php if ($foto_admin): ?>
                <!-- COM FOTO: Com círculo amarelo de 80x80px -->
                <div class="profile-avatar-sidebar">
                    <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
                </div>
            <?php else: ?>
                <!-- SEM FOTO: Apenas ícone, SEM círculo -->
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item">
                <i class="fas fa-plus-circle"></i> Gerenciar Caminhos
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
            <a href="gerenciar_usuarios.php" class="list-group-item ">
                <i class="fas fa-users"></i> Gerenciar Usuários
            </a>
            <a href="estatisticas_usuarios.php" class="list-group-item active">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container mt-4">
            <div class="page-header">
                <h2 class="mb-0"><i class="fas fa-chart-bar"></i> Estatísticas de Usuários</h2>
                 <a href="gerenciar_caminho.php" class="btn-back">
    <i class="fas fa-arrow-left"></i>Voltar para Caminhos
</a>
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

    <!-- Bottom Navigation Bar para telas pequenas -->
    <nav class="bottom-nav d-lg-none">
        <a href="gerenciar_caminho.php" class="bottom-nav-item">
            <i class="fas fa-plus-circle"></i>
        </a>
        <a href="pagina_adicionar_idiomas.php" class="bottom-nav-item">
            <i class="fas fa-language"></i>
        </a>
        <a href="gerenciar_teorias.php" class="bottom-nav-item">
            <i class="fas fa-book-open"></i>
        </a>
        <a href="gerenciar_unidades.php" class="bottom-nav-item">
            <i class="fas fa-cubes"></i>
        </a>
        <a href="gerenciar_usuarios.php" class="bottom-nav-item">
            <i class="fas fa-users"></i>
        </a>
        <a href="estatisticas_usuarios.php" class="bottom-nav-item active">
            <i class="fas fa-chart-bar"></i>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Usuários por Nível
        const niveisCtx = document.getElementById('niveisChart').getContext('2d');
        const niveisChart = new Chart(niveisCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo !empty($usuarios_por_nivel) ? implode(',', array_map(function($n) { return '"' . $n['nivel'] . '"'; }, $usuarios_por_nivel)) : ''; ?>],
                datasets: [{
                    data: [<?php echo !empty($usuarios_por_nivel) ? implode(',', array_map(function($n) { return $n['quantidade']; }, $usuarios_por_nivel)) : ''; ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Idiomas Populares
        const idiomasCtx = document.getElementById('idiomasChart').getContext('2d');
        const idiomasChart = new Chart(idiomasCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo !empty($idiomas_populares) ? implode(',', array_map(function($i) { return '"' . $i['idioma'] . '"'; }, $idiomas_populares)) : ''; ?>],
                datasets: [{
                    label: 'Usuários Únicos',
                    data: [<?php echo !empty($idiomas_populares) ? implode(',', array_map(function($i) { return $i['usuarios_unicos']; }, $idiomas_populares)) : ''; ?>],
                    backgroundColor: '#6a0dad'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Registros Mensais
        const registrosCtx = document.getElementById('registrosChart').getContext('2d');
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
                    borderColor: '#ffd700',
                    backgroundColor: 'rgba(255, 215, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>