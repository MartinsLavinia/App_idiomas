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

// Query base para buscar usuários
$sql_usuarios = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.data_registro,
        u.ultimo_login,
        u.ativo,
        COALESCE(qr.nivel_resultado, 'Não avaliado') as nivel_atual,
        COUNT(DISTINCT pu.caminho_id) as caminhos_iniciados,
        AVG(pu.progresso) as progresso_medio
    FROM usuarios u
    LEFT JOIN (
        SELECT 
            id_usuario,
            nivel_resultado,
            ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY data_realizacao DESC) as rn
        FROM quiz_resultados
    ) qr ON u.id = qr.id_usuario AND qr.rn = 1
    LEFT JOIN progresso_usuario pu ON u.id = pu.id_usuario
    WHERE 1=1
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
    $sql_usuarios .= " AND u.ativo = ?";
    $params[] = $filtro_status;
    $types .= 'i';
}

if (!empty($filtro_nivel)) {
    if ($filtro_nivel === 'Não avaliado') {
        $sql_usuarios .= " AND qr.nivel_resultado IS NULL";
    } else {
        $sql_usuarios .= " AND qr.nivel_resultado = ?";
        $params[] = $filtro_nivel;
        $types .= 's';
    }
}

$sql_usuarios .= " GROUP BY u.id, u.nome, u.email, u.data_registro, u.ultimo_login, u.ativo, qr.nivel_resultado";
$sql_usuarios .= " ORDER BY u.data_registro DESC";

$stmt_usuarios = $conn->prepare($sql_usuarios);
if (!empty($params)) {
    $stmt_usuarios->bind_param($types, ...$params);
}

$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_usuarios->close();

// Estatísticas rápidas - Valores fixos conforme solicitado
$stats = [
    'total_usuarios' => 5,
    'usuarios_ativos' => 5,
    'ativos_semana' => 0,
    'novos_mes' => 5
];

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
        margin: 0;
        padding: 0;
    }

    @keyframes fadeIn {
        from { opacity: 0; } to { opacity: 1; }
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

    /* Botão amarelo principal */
    .btn-warning {
        background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
        color: var(--cinza-texto);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        min-width: 180px;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
        color: var(--cinza-texto);
    }

    /* Estilos de Cartões (Cards) */
    .card {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 2px solid rgba(106, 13, 173, 0.1);
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        animation: cardEntrance 0.6s ease-out;
    }

    @keyframes cardEntrance {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 35px rgba(106, 13, 173, 0.2);
        border-color: rgba(106, 13, 173, 0.3);
    }

    .card-header {
        background-color: var(--roxo-principal);
        color: var(--branco);
        border-radius: 1rem 1rem 0 0 !important;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .card-header h2 {
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .card-header h5 {
        color: var(--branco) !important;
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
    }

    @keyframes statsCardAnimation {
        from { opacity: 0; transform: translateY(30px) rotateX(-10deg); }
        to { opacity: 1; transform: translateY(0) rotateX(0); }
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

    /*--*/

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

    /* Containers para tabelas */
    .table-container {
        background: transparent !important;
        border: 2px solid rgba(106, 13, 173, 0.2) !important;
        border-top: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        border-radius: 10px !important;
        margin-bottom: 30px;
        transition: all 0.3s ease;
    }

    .table-container .card-header {
        border-radius: 10px 10px 0 0 !important;
        position: relative;
        overflow: hidden;
        border: 2px solid var(--roxo-principal) !important;
        border-bottom: none !important;
    }

    .table-container .card-body {
        border-radius: 0 0 10px 10px !important;
        background: rgba(255, 255, 255, 0.95) !important;
        padding: 20px !important;
    }

    .table-container:hover .card-body {
        border-color: rgba(106, 13, 173, 0.3) !important;
    }

    .table-container .card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .table-container .card-body {
        background: transparent !important;
    }

    /* Animação do efeito vidro apenas no cabeçalho roxo - CONTÍNUA */
    .table-container .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: glassEffect 3s infinite;
        border-radius: 10px 10px 0 0;
        z-index: 1;
    }

    @keyframes glassEffect {
        0% { left: -100%; }
        50% { left: 100%; }
        100% { left: 100%; }
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

    /* Badges personalizadas */
    .badge {
        font-weight: 600;
        padding: 0.5em 1em;
        border-radius: 50px;
    }

    /* Títulos e cabeçalhos */
    h1, h2, h3, h4, h6 {
        color: var(--roxo-principal);
        font-weight: 600;
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
        background: linear-gradient(135deg, #9ca3a8ff, #8e9caaff);
        border: none;
        color: var(--branco);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        color: var(--branco);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
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

    /* Tabelas personalizadas */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table thead th {
        background-color: rgba(255, 255, 255, 1);
        color: var(--roxo-principal);
        border: none;
        font-weight: 600;
        padding: 15px;
    }

    .table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--cinza-medio);
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(106, 13, 173, 0.1);
    }

    .settings-icon {
        color: var(--roxo-principal) !important;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 1.2rem;
    }

    .settings-icon:hover {
        color: var(--roxo-principal) !important;
        transform: rotate(90deg);
    }

    /* Responsividade */
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
        .stats-card h3 {
            font-size: 2rem;
        }
    }

    /* Animações adicionais */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .stats-card:nth-child(1) { animation-delay: 0.1s; }
    .stats-card:nth-child(2) { animation-delay: 0.2s; }
    .stats-card:nth-child(3) { animation-delay: 0.3s; }
    .stats-card:nth-child(4) { animation-delay: 0.4s; }

    .fas.fa-search { color: var(--amarelo-detalhe); }
    .fas.fa-users { color: var(--amarelo-detalhe); }
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
            <a href="gerenciar_usuarios.php" class="list-group-item active">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2"><i class="fas fa-users" style="color: black;"></i> Gerenciar Usuários</h1>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3>5</h3>
                        <p><i class="fas fa-users" style="color: black;"></i> Total de Usuários</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3>5</h3>
                        <p><i class="fas fa-user-check"></i> Contas Ativas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3>0</h3>
                        <p><i class="fas fa-calendar-week"></i> Ativos esta Semana</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <h3>5</h3>
                        <p><i class="fas fa-user-plus"></i> Novos este Mês</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de Pesquisa -->
            <div class="table-container mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-white"><i class="fas fa-search"></i> Filtros de Pesquisa</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Buscar por nome">
                            </div>
                            <div class="col-md-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="Buscar por email">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo $filtro_status === '1' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $filtro_status === '0' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-warning w-100 pesquisar-btn">
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
                   <h5 class="mb-0 text-white">
    <i class="fas fa-users me-2"></i>Lista de Usuários (<?php echo count($usuarios); ?> encontrados)
</h5>
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
                                                <div class="progress" style="height: 5px;">
                                                    <div class="progress-bar" style="width: <?php echo round($usuario['progresso_medio'] ?? 0); ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo round($usuario['progresso_medio'] ?? 0); ?>%</small>
                                            <?php else: ?>
                                                <small class="text-muted">Nenhum progresso</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y', strtotime($usuario['data_registro'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($usuario['ultimo_login']): ?>
                                                <small><?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Nunca</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?php echo $usuario['ativo'] ? 'success' : 'danger'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
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
                                                        onclick="toggleUserStatus(<?php echo $usuario['id']; ?>, <?php echo $usuario['ativo'] ? 0 : 1; ?>)">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">Detalhes do Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadUserDetails(userId) {
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;
            
            fetch(`detalhes_usuario.php?id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('userDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Erro ao carregar detalhes do usuário.
                        </div>
                    `;
                });
        }

        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'ativar' : 'desativar';
            if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
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
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação.');
                });
            }
        }
    </script>
</body>
</html>