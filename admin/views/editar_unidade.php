<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;
$mensagem = '';

// Buscar foto do admin
$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// Buscar dados da unidade
if (isset($_GET['id'])) {
    $id_unidade = $_GET['id'];
    
    $sql = "SELECT u.*, i.nome_idioma FROM unidades u 
            JOIN idiomas i ON u.id_idioma = i.id 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_unidade);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Unidade não encontrada.";
        header("Location: gerenciar_unidades.php");
        exit();
    }
    
    $unidade = $result->fetch_assoc();
    $stmt->close();
} else {
    header("Location: gerenciar_unidades.php");
    exit();
}

// Buscar idiomas disponíveis
$sql_idiomas = "SELECT id, nome_idioma FROM idiomas ORDER BY nome_idioma";
$result_idiomas = $conn->query($sql_idiomas);
$idiomas = $result_idiomas->fetch_all(MYSQLI_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_unidade = trim($_POST['nome_unidade']);
    $descricao = trim($_POST['descricao']);
    $nivel = trim($_POST['nivel']);
    $numero_unidade = trim($_POST['numero_unidade']);
    $id_idioma = $_POST['id_idioma'];
    
    if (empty($nome_unidade) || empty($id_idioma)) {
        $mensagem = '<div class="alert alert-danger">Nome da unidade e idioma são obrigatórios.</div>';
    } else {
        $sql_update = "UPDATE unidades SET nome_unidade = ?, descricao = ?, nivel = ?, numero_unidade = ?, id_idioma = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssii", $nome_unidade, $descricao, $nivel, $numero_unidade, $id_idioma, $id_unidade);
        
        if ($stmt_update->execute()) {
            $mensagem = '<div class="alert alert-success">Unidade atualizada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao atualizar unidade: ' . $conn->error . '</div>';
        }
        $stmt_update->close();
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Unidade - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
    <?php include __DIR__ . '/gerenciamento.css'; ?>
    
    /* Estilos específicos para a página de edição */
    .form-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(106, 13, 173, 0.1);
        transition: all 0.3s ease;
        animation: slideInUp 0.5s ease-out;
    }

    .form-container:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .form-label {
        font-weight: 500;
        color: var(--roxo-principal);
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        border: 1px solid var(--cinza-medio);
        border-radius: 10px;
        padding: 10px 15px;
        transition: all 0.3s ease;
        background-color: var(--branco);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.15);
        transform: translateY(-2px);
    }

    .form-text {
        color: var(--cinza-escuro);
        font-size: 0.875rem;
        margin-top: 5px;
    }

    /* Header da página */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(106, 13, 173, 0.1);
        animation: slideInDown 0.5s ease-out;
    }

    .page-header-icon {
        background: transparent;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        box-shadow: none;
        border: none;
    }

    .page-header-icon i {
        color: var(--preto-texto) !important;
        font-size: 1.5rem;
    }

    .page-header h1 {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        color: var(--preto-texto);
        font-weight: 700;
        font-size: 1.8rem;
    }

    /* Botões específicos */
    .btn-primary {
        background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        border: none;
        color: var(--branco);
        font-weight: 600;
        padding: 12px 30px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
        color: var(--branco);
    }

    .btn-secondary {
        background: rgba(33, 37, 41, 0.08);
        border: 1.5px solid var(--preto-texto);
        color: var(--preto-texto);
        padding: 0.75rem 2rem;
        border-radius: 6px;
        font-weight: 600;
        transition: background 0.2s, color 0.2s, border 0.2s, transform 0.2s;
        box-shadow: none;
    }

    .btn-secondary:hover, .btn-secondary:focus {
        background: rgba(33, 37, 41, 0.18);
        color: var(--preto-texto);
        transform: translateY(-2px) scale(1.03);
        outline: none;
    }

    .btn-back {
        background: rgba(106, 13, 173, 0.06);
        border: 2px solid rgba(106, 13, 173, 0.15);
        color: var(--roxo-principal);
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        transition: all 0.25s ease;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        backdrop-filter: blur(6px);
    }

    .btn-back:hover {
        background: rgba(106, 13, 173, 0.12);
        color: var(--roxo-escuro);
        border-color: rgba(106, 13, 173, 0.25);
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(106, 13, 173, 0.12);
    }

    /* Grupo de botões de ação */
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: flex-start;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    /* Campos obrigatórios */
    .required-field::after {
        content: " *";
        color: #dc3545;
    }

    /* Responsividade específica */
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
            margin: 10px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        
        .action-buttons .btn {
            width: 100%;
            justify-content: center;
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
        }
    }

    @media (max-width: 576px) {
        .form-container {
            padding: 15px;
            margin: 5px;
        }
        
        .page-header-icon {
            width: 40px;
            height: 40px;
        }
        
        .page-header-icon i {
            font-size: 1.2rem;
        }
        
        .page-header h1 {
            font-size: 1.3rem;
        }
    }

    /* Estados de loading */
    .btn-loading {
        position: relative;
        color: transparent !important;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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

    // Prevenir envio duplo do formulário
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('btn-loading')) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            }
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
        <div class="container-fluid mt-4">
            <div class="page-header">
                <h1>
                    <div class="page-header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    Editar Unidade
                </h1>
                <a href="gerenciar_unidades.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar para Unidades
                </a>
            </div>

            <?php echo $mensagem; ?>

            <div class="form-container">
                <form method="POST">
                    <!-- Campo Nome da Unidade -->
                    <div class="mb-4">
                        <label for="nome_unidade" class="form-label required-field">Nome da Unidade</label>
                        <input type="text" class="form-control" id="nome_unidade" name="nome_unidade" 
                               value="<?= htmlspecialchars($unidade['nome_unidade']); ?>" 
                               placeholder="Digite o nome da unidade" required>
                    </div>
                    
                    <!-- Campo Idioma -->
                    <div class="mb-4">
                        <label for="id_idioma" class="form-label required-field">Idioma</label>
                        <select class="form-select" id="id_idioma" name="id_idioma" required>
                            <?php foreach ($idiomas as $idioma): ?>
                                <option value="<?= $idioma['id']; ?>" 
                                        <?= $idioma['id'] == $unidade['id_idioma'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($idioma['nome_idioma']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Campos em linha para Nível e Número -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="nivel" class="form-label">Nível</label>
                            <input type="text" class="form-control" id="nivel" name="nivel" 
                                   value="<?= htmlspecialchars($unidade['nivel']); ?>" 
                                   placeholder="Ex: A1, Iniciante">
                        </div>
                        <div class="col-md-6">
                            <label for="numero_unidade" class="form-label">Número da Unidade</label>
                            <input type="number" class="form-control" id="numero_unidade" name="numero_unidade" 
                                   value="<?= htmlspecialchars($unidade['numero_unidade']); ?>" 
                                   min="1" placeholder="1">
                            <div class="form-text">Ordem em que a unidade aparecerá na lista</div>
                        </div>
                    </div>
                    
                    <!-- Campo Descrição -->
                    <div class="mb-4">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                  placeholder="Descrição da unidade (opcional)"><?= htmlspecialchars($unidade['descricao']); ?></textarea>
                        <div class="form-text">Descrição que aparecerá na lista de unidades</div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Atualizar Unidade
                        </button>
                        <a href="gerenciar_unidades.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>