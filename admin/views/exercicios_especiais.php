<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$caminho_id = $_GET['caminho_id'] ?? null;
if (!$caminho_id) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$mensagem = '';

// Exibir mensagem de sucesso se existir
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['mensagem_sucesso'] . '</div>';
    unset($_SESSION['mensagem_sucesso']);
}

$database = new Database();
$conn = $database->conn;

// BUSCAR DADOS DO ADMINISTRADOR PARA O SIDEBAR
$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// Função para adicionar exercício especial na tabela exercicios_especiais
function adicionarExercicioEspecial($conn, $caminhoId, $pergunta, $conteudo) {
    $sql = "INSERT INTO exercicios_especiais (titulo, conteudo, url_media, pergunta) VALUES (?, ?, '', ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $pergunta, $conteudo, $pergunta);
        if ($stmt->execute()) {
            $exercicio_id = $conn->insert_id;
            $stmt->close();
            return $exercicio_id;
        } else {
            $stmt->close();
            return false;
        }
    }
    return false;
}

// Get path info
$sql_caminho = "SELECT nome_caminho, idioma, nivel FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

if (!$caminho) {
    header("Location: gerenciar_caminho.php");
    exit();
}

// Variáveis para pré-preencher o formulário
$post_pergunta = $_POST["pergunta"] ?? '';
$post_link_video = $_POST["link_video"] ?? '';
$post_letra_musica = $_POST["letra_musica"] ?? '';
$post_letra = $_POST["letra"] ?? '';
$post_tipo_exercicio = $_POST["tipo_exercicio"] ?? 'observar';

// Processar exclusão de exercício especial
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_exercicio'])) {
    $exercicio_id = $_POST['exercicio_id'] ?? null;
    
    if ($exercicio_id) {
        $sql_delete = "DELETE FROM exercicios_especiais WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $exercicio_id);
        
        if ($stmt_delete->execute()) {
            $_SESSION['mensagem_sucesso'] = 'Exercício especial excluído com sucesso!';
        }
        $stmt_delete->close();
    }
    header("Location: exercicios_especiais.php?caminho_id=" . $caminho_id);
    exit();
}

// Processar edição de exercício especial
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_exercicio'])) {
    $exercicio_id = $_POST['exercicio_id'] ?? null;
    $pergunta = $_POST['pergunta_edit'] ?? null;
    $link_video = $_POST['link_video_edit'] ?? null;
    $letra_musica = $_POST['letra_musica_edit'] ?? null;
    $letra = $_POST['letra_edit'] ?? null;
    $tipo_exercicio = $_POST['tipo_exercicio_edit'] ?? 'observar';
    
    if (empty($pergunta) || empty($link_video) || empty($letra_musica)) {
        $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        $conteudo = json_encode([
            'link_video' => $link_video,
            'letra_musica' => $letra_musica,
            'letra' => $letra ?? '',
            'tipo_exercicio' => $tipo_exercicio
        ], JSON_UNESCAPED_UNICODE);
        
        $sql_update = "UPDATE exercicios_especiais SET titulo = ?, conteudo = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $pergunta, $conteudo, $exercicio_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['mensagem_sucesso'] = 'Exercício especial atualizado com sucesso!';
            header("Location: exercicios_especiais.php?caminho_id=" . $caminho_id);
            exit();
        } else {
            $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao atualizar exercício especial.</div>';
        }
        $stmt_update->close();
    }
}

// Processar formulário (usando a mesma lógica do adicionar_atividades)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['editar_exercicio'])) {
    $pergunta = $_POST["pergunta"] ?? null;
    $link_video = $_POST["link_video"] ?? null;
    $letra_musica = $_POST["letra_musica"] ?? null;
    $letra = $_POST["letra"] ?? null;
    $tipo_exercicio = $_POST["tipo_exercicio"] ?? 'observar';
    
    // Dados específicos por tipo
    $alternativas = [];
    $palavras_completar = '';
    
    if ($tipo_exercicio == 'alternativa') {
        $alternativas = [
            'a' => $_POST['alternativa_a'] ?? '',
            'b' => $_POST['alternativa_b'] ?? '',
            'c' => $_POST['alternativa_c'] ?? '',
            'd' => $_POST['alternativa_d'] ?? '',
            'correta' => $_POST['alternativa_correta'] ?? 'a'
        ];
    } elseif ($tipo_exercicio == 'completar') {
        $palavras_completar = $_POST['palavras_completar'] ?? '';
    }
    
    if (empty($pergunta) || empty($link_video) || empty($letra_musica)) {
        $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        $conteudo = json_encode([
            'link_video' => $link_video,
            'letra_musica' => $letra_musica,
            'letra' => $letra ?? '',
            'tipo_exercicio' => $tipo_exercicio,
            'alternativas' => $alternativas,
            'palavras_completar' => $palavras_completar
        ], JSON_UNESCAPED_UNICODE);
        
        $exercicio_id = adicionarExercicioEspecial($conn, $caminho_id, $pergunta, $conteudo);
        if ($exercicio_id) {
            $_SESSION['mensagem_sucesso'] = 'Exercício especial adicionado com sucesso! Ele aparecerá como um bloco independente no caminho de aprendizagem.';
            header("Location: exercicios_especiais.php?caminho_id=" . $caminho_id);
            exit();
        } else {
            $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao adicionar exercício especial no banco de dados.</div>';
        }
    }
}

// Get special exercises for this path from exercicios_especiais table
$sql_especiais = "SELECT * FROM exercicios_especiais ORDER BY id";
$stmt_especiais = $conn->prepare($sql_especiais);
$stmt_especiais->execute();
$exercicios_especiais = $stmt_especiais->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_especiais->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercícios Especiais - <?php echo htmlspecialchars($caminho['nome_caminho']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

    .sidebar .list-group-item i {
        color: var(--amarelo-detalhe);
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

    body:has(.sidebar.active) .menu-toggle,
    .sidebar.active ~ .menu-toggle {
        color: var(--amarelo-detalhe) !important;
    }

    body:has(.sidebar.active) .menu-toggle:hover,
    .sidebar.active ~ .menu-toggle:hover {
        color: var(--amarelo-hover) !important;
    }

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
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 15px 10px;
        }
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
        background: transparent;
        color: var(--roxo-principal);
        border: 2px solid #6a0dad;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background 0.12s ease, color 0.12s ease, transform 0.12s ease;
    }

    .btn-primary:hover {
        background: rgba(106, 13, 173, 0.06);
        color: var(--roxo-principal);
        border: 2px solid #6a0dad;
        transform: translateY(-1px);
    }

    .btn-danger {
        background: rgba(220, 53, 69, 0.06);
        color: #8a1820;
        border: 2px solid #c82333;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 999px;
        transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.12s ease;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.04);
    }

    .btn-danger:hover {
        background: rgba(220, 53, 69, 0.12);
        color: #7a151b;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(220, 53, 69, 0.08);
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

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(106, 13, 173, 0.2);
    }

    .form-control {
        border: 2px solid var(--cinza-medio);
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
    }

    .form-select {
        border: 2px solid var(--cinza-medio);
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
    }

    .modal-content {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(106, 13, 173, 0.2);
    }

    .modal-header {
        background: var(--roxo-principal);
        color: var(--branco);
        border-bottom: none;
        padding: 1.5rem;
    }

    .btn-close {
        filter: brightness(0) invert(1);
        opacity: 1;
    }

    .modal-title {
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid var(--cinza-medio);
        padding: 1rem 1.5rem;
    }

    .btn-secondary {
        background: var(--cinza-medio);
        border: none;
        color: var(--preto-texto);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #6c757d;
        color: var(--branco);
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
        background-color: var(--roxo-principal);
        border-color: var(--roxo-principal);
        color: var(--branco);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
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
            <a href="gerenciar_caminho.php" class="list-group-item active">
                <i class="fas fa-plus-circle" style="color: #ffd700;"></i> Adicionar Caminho
            </a>
            <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                <i class="fas fa-language" style="color: #ffd700;"></i> Gerenciar Idiomas
            </a>
            <a href="gerenciar_teorias.php" class="list-group-item">
                <i class="fas fa-book-open" style="color: #ffd700;"></i> Gerenciar Teorias
            </a>
            <a href="gerenciar_unidades.php" class="list-group-item">
                <i class="fas fa-cubes" style="color: #ffd700;"></i> Gerenciar Unidades
            </a>
            <a href="gerenciar_usuarios.php" class="list-group-item">
                <i class="fas fa-users" style="color: #ffd700;"></i> Gerenciar Usuários
            </a>
            <a href="estatisticas_usuarios.php" class="list-group-item">
                <i class="fas fa-chart-bar" style="color: #ffd700;"></i> Estatísticas
            </a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-star me-2"></i>Exercícios Especiais
                    </h2>
                    <p class="text-muted mb-0">
                        Caminho: <strong><?php echo htmlspecialchars($caminho['nome_caminho']); ?></strong> (<?php echo htmlspecialchars($caminho['nivel']); ?>)
                    </p>
                </div>
                <div>
                    <a href="gerenciar_caminho.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i>Voltar para Caminhos
                    </a>
                </div>
            </div>

            <?php echo $mensagem; ?>

            <div class="alert alert-info">
                <strong>📍 Adicionando Exercício Especial para:</strong><br>
                • <strong>Caminho:</strong> <?php echo htmlspecialchars($caminho['nome_caminho']); ?><br>
                • <strong>Idioma:</strong> <?php echo htmlspecialchars($caminho['idioma']); ?><br>
                • <strong>Nível:</strong> <?php echo htmlspecialchars($caminho['nivel']); ?><br>
                <small class="text-muted">Exercícios especiais aparecem como blocos independentes no caminho de aprendizagem.</small>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-1"></i>Formulário de Exercício Especial
                    </h5>
                </div>
                <div class="card-body">
                    <form action="exercicios_especiais.php?caminho_id=<?php echo $caminho_id; ?>" method="POST">
                        
                        <!-- Campo Pergunta -->
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($post_pergunta); ?></textarea>
                        </div>

                        <!-- Campos para Tipo Especial -->
                        <div class="subtipo-campos">
                            <h5>Configuração - Exercício Especial (Vídeo/Áudio)</h5>
                            <div class="mb-3">
                                <label for="tipo_exercicio" class="form-label">Tipo de Exercício *</label>
                                <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                                    <option value="observar" <?php echo ($post_tipo_exercicio == 'observar') ? 'selected' : ''; ?>>Observar a Letra</option>
                                    <option value="completar" <?php echo ($post_tipo_exercicio == 'completar') ? 'selected' : ''; ?>>Completar a Letra</option>
                                    <option value="alternativa" <?php echo ($post_tipo_exercicio == 'alternativa') ? 'selected' : ''; ?>>Questões de Alternativa</option>
                                </select>
                                <div class="form-text">Define como o usuário irá interagir com a letra da música</div>
                            </div>
                            
                            <!-- Campos para Alternativas -->
                            <div id="campos-alternativas" class="mb-3" style="display: none;">
                                <label class="form-label">Questões de Alternativa</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">A</span>
                                    <input type="text" class="form-control" name="alternativa_a" placeholder="Alternativa A">
                                    <div class="input-group-text">
                                        <input type="radio" name="alternativa_correta" value="a" title="Marcar como correta">
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">B</span>
                                    <input type="text" class="form-control" name="alternativa_b" placeholder="Alternativa B">
                                    <div class="input-group-text">
                                        <input type="radio" name="alternativa_correta" value="b" title="Marcar como correta">
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">C</span>
                                    <input type="text" class="form-control" name="alternativa_c" placeholder="Alternativa C">
                                    <div class="input-group-text">
                                        <input type="radio" name="alternativa_correta" value="c" title="Marcar como correta">
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">D</span>
                                    <input type="text" class="form-control" name="alternativa_d" placeholder="Alternativa D">
                                    <div class="input-group-text">
                                        <input type="radio" name="alternativa_correta" value="d" title="Marcar como correta">
                                    </div>
                                </div>
                                <div class="form-text">Marque a bolinha da alternativa correta</div>
                            </div>
                            
                            <!-- Campos para Completar -->
                            <div id="campos-completar" class="mb-3" style="display: none;">
                                <label for="palavras_completar" class="form-label">Palavras para Completar</label>
                                <textarea class="form-control" id="palavras_completar" name="palavras_completar" rows="3" placeholder="Digite as palavras que serão removidas da letra, separadas por vírgula. Ex: amor, coração, sonhar"></textarea>
                                <div class="form-text">Essas palavras serão substituídas por lacunas na letra da música</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="letra" class="form-label">Letra do Exercício (Opcional)</label>
                                <input type="text" class="form-control" id="letra" name="letra" value="<?php echo htmlspecialchars($post_letra); ?>" placeholder="Ex: A, B, C, 1, 2, 3...">
                                <div class="form-text">Esta letra aparecerá para identificar o exercício para o usuário</div>
                            </div>
                            <div class="mb-3">
                                <label for="link_video" class="form-label">Link do Vídeo/Áudio (YouTube, Vimeo, etc.) *</label>
                                <input type="url" class="form-control" id="link_video" name="link_video" value="<?php echo htmlspecialchars($post_link_video); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="letra_musica" class="form-label">Letra da Música *</label>
                                <textarea class="form-control" id="letra_musica" name="letra_musica" rows="8" required placeholder="Cole aqui a letra da música..."><?php echo htmlspecialchars($post_letra_musica); ?></textarea>
                                <div class="form-text">A letra aparecerá para o usuário acompanhar durante a música</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-1"></i>Adicionar Exercício Especial
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i>Lista de Exercícios Especiais</h5>
                </div>
                    <div class="card-body">
                        <?php if (count($exercicios_especiais) >= 5): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Limite máximo de 5 exercícios especiais atingido para este caminho.
                            </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($exercicios_especiais)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Nenhum exercício especial cadastrado.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($exercicios_especiais as $index => $exercicio): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($exercicio['titulo']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        Especial
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-primary" onclick="editarExercicio(<?php echo $exercicio['id']; ?>)" data-exercicio='<?php echo htmlspecialchars($exercicio['conteudo'], ENT_QUOTES); ?>' data-pergunta="<?php echo htmlspecialchars($exercicio['titulo'], ENT_QUOTES); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-danger" onclick="excluirExercicio(<?php echo $exercicio['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Exercício Especial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="editar_exercicio" value="1">
                        <input type="hidden" name="exercicio_id" id="edit_exercicio_id">
                        
                        <div class="mb-3">
                            <label for="pergunta_edit" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta_edit" name="pergunta_edit" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_exercicio_edit" class="form-label">Tipo de Exercício *</label>
                            <select class="form-select" id="tipo_exercicio_edit" name="tipo_exercicio_edit" required>
                                <option value="observar">Observar a Letra</option>
                                <option value="completar">Completar a Letra</option>
                                <option value="alternativa">Questões de Alternativa</option>
                            </select>
                        </div>
                        
                        <!-- Campos para Alternativas - Modal -->
                        <div id="campos-alternativas-edit" class="mb-3" style="display: none;">
                            <label class="form-label">Questões de Alternativa</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">A</span>
                                <input type="text" class="form-control" id="alternativa_a_edit" name="alternativa_a_edit">
                                <div class="input-group-text">
                                    <input type="radio" name="alternativa_correta_edit" value="a">
                                </div>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">B</span>
                                <input type="text" class="form-control" id="alternativa_b_edit" name="alternativa_b_edit">
                                <div class="input-group-text">
                                    <input type="radio" name="alternativa_correta_edit" value="b">
                                </div>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">C</span>
                                <input type="text" class="form-control" id="alternativa_c_edit" name="alternativa_c_edit">
                                <div class="input-group-text">
                                    <input type="radio" name="alternativa_correta_edit" value="c">
                                </div>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">D</span>
                                <input type="text" class="form-control" id="alternativa_d_edit" name="alternativa_d_edit">
                                <div class="input-group-text">
                                    <input type="radio" name="alternativa_correta_edit" value="d">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campos para Completar - Modal -->
                        <div id="campos-completar-edit" class="mb-3" style="display: none;">
                            <label for="palavras_completar_edit" class="form-label">Palavras para Completar</label>
                            <textarea class="form-control" id="palavras_completar_edit" name="palavras_completar_edit" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="letra_edit" class="form-label">Letra do Exercício</label>
                            <input type="text" class="form-control" id="letra_edit" name="letra_edit">
                        </div>
                        
                        <div class="mb-3">
                            <label for="link_video_edit" class="form-label">Link do Vídeo/Áudio *</label>
                            <input type="url" class="form-control" id="link_video_edit" name="link_video_edit" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="letra_musica_edit" class="form-label">Letra da Música *</label>
                            <textarea class="form-control" id="letra_musica_edit" name="letra_musica_edit" rows="8" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Funcionalidade do menu já carregada no início do body

        // Validação do formulário (igual ao adicionar_atividades)
        document.querySelector('form').addEventListener('submit', function(e) {
            const pergunta = document.getElementById('pergunta').value.trim();
            const linkVideo = document.getElementById('link_video').value.trim();
            const letraMusica = document.getElementById('letra_musica').value.trim();
            
            if (!pergunta || !linkVideo || !letraMusica) {
                e.preventDefault();
                alert('Erro no formulário: O Link do Vídeo/Áudio e a Letra da Música são obrigatórios para este tipo de exercício.');
            }
        });
        
        // Função para editar exercício
        function editarExercicio(id) {
            const button = event.target.closest('button');
            const pergunta = button.getAttribute('data-pergunta');
            const conteudoJson = button.getAttribute('data-exercicio');
            
            try {
                const conteudo = JSON.parse(conteudoJson);
                
                document.getElementById('edit_exercicio_id').value = id;
                document.getElementById('pergunta_edit').value = pergunta;
                document.getElementById('tipo_exercicio_edit').value = conteudo.tipo_exercicio || 'observar';
                document.getElementById('letra_edit').value = conteudo.letra || '';
                document.getElementById('link_video_edit').value = conteudo.link_video || '';
                document.getElementById('letra_musica_edit').value = conteudo.letra_musica || '';
                
                // Preencher campos específicos
                if (conteudo.alternativas) {
                    document.getElementById('alternativa_a_edit').value = conteudo.alternativas.a || '';
                    document.getElementById('alternativa_b_edit').value = conteudo.alternativas.b || '';
                    document.getElementById('alternativa_c_edit').value = conteudo.alternativas.c || '';
                    document.getElementById('alternativa_d_edit').value = conteudo.alternativas.d || '';
                    if (conteudo.alternativas.correta) {
                        document.querySelector(`input[name="alternativa_correta_edit"][value="${conteudo.alternativas.correta}"]`).checked = true;
                    }
                }
                
                if (conteudo.palavras_completar) {
                    document.getElementById('palavras_completar_edit').value = conteudo.palavras_completar;
                }
                
                // Atualizar campos visíveis no modal
                atualizarCamposModal();
                
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            } catch (e) {
                console.error('Erro ao carregar dados:', e);
                const toast = document.createElement('div');
                toast.className = 'toast-notification error';
                toast.textContent = 'Erro ao carregar dados do exercício';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        }
        
        // Controlar campos dinâmicos baseado no tipo de exercício
        document.addEventListener('DOMContentLoaded', function() {
            const tipoExercicio = document.getElementById('tipo_exercicio');
            const camposAlternativas = document.getElementById('campos-alternativas');
            const camposCompletar = document.getElementById('campos-completar');
            
            function atualizarCampos() {
                const tipo = tipoExercicio.value;
                console.log('Tipo selecionado:', tipo); // Debug
                
                // Ocultar todos os campos
                if (camposAlternativas) camposAlternativas.style.display = 'none';
                if (camposCompletar) camposCompletar.style.display = 'none';
                
                // Mostrar campos baseado no tipo
                if (tipo === 'alternativa' && camposAlternativas) {
                    camposAlternativas.style.display = 'block';
                    console.log('Mostrando campos de alternativas'); // Debug
                } else if (tipo === 'completar' && camposCompletar) {
                    camposCompletar.style.display = 'block';
                    console.log('Mostrando campos de completar'); // Debug
                }
            }
            
            if (tipoExercicio) {
                tipoExercicio.addEventListener('change', atualizarCampos);
                atualizarCampos(); // Executar na carga da página
            }
        });
        
        // Função para campos do modal
        window.atualizarCamposModal = function() {
            const tipoEdit = document.getElementById('tipo_exercicio_edit');
            const camposAlternativasEdit = document.getElementById('campos-alternativas-edit');
            const camposCompletarEdit = document.getElementById('campos-completar-edit');
            
            if (camposAlternativasEdit) camposAlternativasEdit.style.display = 'none';
            if (camposCompletarEdit) camposCompletarEdit.style.display = 'none';
            
            if (tipoEdit && tipoEdit.value === 'alternativa' && camposAlternativasEdit) {
                camposAlternativasEdit.style.display = 'block';
            } else if (tipoEdit && tipoEdit.value === 'completar' && camposCompletarEdit) {
                camposCompletarEdit.style.display = 'block';
            }
        }
        
        // Event listener para o modal
        const tipoEdit = document.getElementById('tipo_exercicio_edit');
        if (tipoEdit) {
            tipoEdit.addEventListener('change', atualizarCamposModal);
        }
        
        // Controlar campos dinâmicos
        function atualizarCampos() {
            const tipo = document.getElementById('tipo_exercicio').value;
            const camposAlternativas = document.getElementById('campos-alternativas');
            const camposCompletar = document.getElementById('campos-completar');
            
            if (camposAlternativas) camposAlternativas.style.display = 'none';
            if (camposCompletar) camposCompletar.style.display = 'none';
            
            if (tipo === 'alternativa' && camposAlternativas) {
                camposAlternativas.style.display = 'block';
            } else if (tipo === 'completar' && camposCompletar) {
                camposCompletar.style.display = 'block';
            }
        }
        
        document.getElementById('tipo_exercicio').addEventListener('change', atualizarCampos);
        atualizarCampos();
        
        // Auto-hide para todas as mensagens de alerta
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
        
        function excluirExercicio(id) {
            if (confirm('Tem certeza que deseja excluir este exercício especial?')) {
                fetch('exercicios_especiais.php?caminho_id=<?php echo $caminho_id; ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'excluir_exercicio=1&exercicio_id=' + id
                })
                .then(() => location.reload())
                .catch(() => {
                    const toast = document.createElement('div');
                    toast.className = 'toast-notification error';
                    toast.textContent = 'Erro ao excluir exercício';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                });
            }
        }
    </script>
</body>
</html>