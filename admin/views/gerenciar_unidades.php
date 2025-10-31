<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Buscar foto do admin
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

$unidades = [];
$sql_unidades = "SELECT u.id, u.nome_unidade, u.descricao, u.nivel, u.numero_unidade, i.nome_idioma 
                 FROM unidades u 
                 JOIN idiomas i ON u.id_idioma = i.id 
                 ORDER BY i.nome_idioma, u.nivel, u.numero_unidade, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);

if ($result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Buscar estatísticas
$total_unidades = count($unidades);
$sql_idiomas = "SELECT COUNT(DISTINCT id_idioma) as total_idiomas FROM unidades";
$result_idiomas = $conn->query($sql_idiomas);
$total_idiomas = $result_idiomas->fetch_assoc()['total_idiomas'];

$sql_niveis = "SELECT COUNT(DISTINCT nivel) as total_niveis FROM unidades WHERE nivel IS NOT NULL";
$result_niveis = $conn->query($sql_niveis);
$total_niveis = $result_niveis->fetch_assoc()['total_niveis'];

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Unidades - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">

    <style>
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

        /* Cartões de Estatísticas - MESMO ESTILO DO GERENCIAR_CAMINHO */
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

        /* Container do avatar - PARA QUANDO TEM FOTO */
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

        /* Estilo para botões de ação */
        .btn-acoes {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-blocos, .btn-editar, .btn-eliminar {
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            background-color: transparent !important;
            border-width: 2px;
            border-style: solid;
            color: inherit !important;
        }

        .btn-editar {
            border-color: #3b82f6;
            color: #3b82f6 !important;
        }

        .btn-eliminar {
            border-color: #dc2626;
            color: #dc2626 !important;
        }

        .btn-editar:hover,
        .btn-editar:focus {
            background-color: #60a5fa !important;
            color: #fff !important;
            border-color: #60a5fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);
        }

        .btn-eliminar:hover,
        .btn-eliminar:focus {
            background-color: #ef4444 !important;
            color: #fff !important;
            border-color: #ef4444;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-editar:active,
        .btn-eliminar:active {
            transform: scale(0.97);
            box-shadow: none;
        }

        /* Estilo para logout no sidebar */
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

        .logout-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-icon:hover {
            color: var(--roxo-escuro) !important;
        }
    </style>
</head>

<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu hamburguer functionality
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        });
    }

    // Fechar menu ao clicar em um link (em dispositivos móveis)
    const sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
                mainContent.classList.remove('expanded');
            }
        });
    });

    // Fechar menu ao redimensionar a janela para tamanho maior
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('show');
            mainContent.classList.remove('expanded');
        }
    });
});
</script>

    <!-- Botão Hamburguer -->
    <button class="hamburger-btn" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>

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
                <i class="fas fa-user-circle"></i>
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
            <a href="logout.php" class="list-group-item sair">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="container-fluid mt-4">
            <?php
            if (isset($_GET["message_type"]) && isset($_GET["message_content"])) {
                $message_type = htmlspecialchars($_GET["message_type"]);
                $message_content = htmlspecialchars(urldecode($_GET["message_content"]));
                echo '<div class="alert alert-' . ($message_type == 'success' ? 'success' : 'danger') . ' mt-3">' . $message_content . '</div>';
            }
            ?>

            <h2 class="mb-4">Gerenciar Unidades</h2>

            <a href="adicionar_unidade.php" class="btn btn-warning mb-4">
                <i class="fas fa-plus-circle me-2"></i>Adicionar Unidade
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

            <!-- Estatísticas - MESMO LAYOUT DO GERENCIAR_CAMINHO -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-cubes"></i>
                        <h3><?= $total_unidades ?></h3>
                        <p>Total de Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-language"></i>
                        <h3><?= $total_idiomas ?></h3>
                        <p>Idiomas com Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-layer-group"></i>
                        <h3><?= $total_niveis ?></h3>
                        <p>Níveis Diferentes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line"></i>
                        <h3><?= $total_unidades ?></h3>
                        <p>Unidades Ativas</p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-container table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome da Unidade</th>
                            <th>Idioma</th>
                            <th>Nível</th>
                            <th>Número</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($unidades)): ?>
                        <?php foreach ($unidades as $unidade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unidade['id']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['nome_unidade']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['nome_idioma']); ?></td>
                            <td>
                                <?php if ($unidade['nivel']): ?>
                                    <span class="badge bg-warning"><?php echo htmlspecialchars($unidade['nivel']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($unidade['numero_unidade']): ?>
                                    <span class="fw-bold"><?php echo htmlspecialchars($unidade['numero_unidade']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($unidade['descricao'], 0, 50)) . (strlen($unidade['descricao']) > 50 ? '...' : ''); ?>
                                </small>
                            </td>
                            <td class="btn-acoes">
                                <a href="editar_unidade.php?id=<?php echo htmlspecialchars($unidade['id']); ?>"
                                    class="btn btn-sm btn-primary btn-editar">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-danger delete-btn btn-eliminar"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-id="<?php echo htmlspecialchars($unidade['id']); ?>"
                                    data-nome="<?php echo htmlspecialchars($unidade['nome_unidade']); ?>"
                                    data-tipo="unidade" 
                                    data-action="eliminar_unidade.php">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma unidade encontrada.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal de Confirmação de Eliminação -->
            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminação</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Tem certeza que deseja eliminar esta unidade?</p>
                            <p><strong id="itemNome"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Eliminar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Modal de confirmação de eliminação
        $(document).ready(function() {
            $('.delete-btn').on('click', function() {
                var id = $(this).data('id');
                var nome = $(this).data('nome');
                var action = $(this).data('action');
                var tipo = $(this).data('tipo');

                $('#itemNome').text(nome);
                $('#confirmDeleteBtn').attr('href', action + '?id=' + id + '&tipo=' + tipo);
            });
        });
    </script>
</body>
</html>