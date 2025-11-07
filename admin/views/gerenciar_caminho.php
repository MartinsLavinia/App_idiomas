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

// Lógica para buscar os idiomas únicos do banco de dados das tabelas caminhos_aprendizagem e quiz_nivelamento
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$stmt_idiomas = $conn->prepare($sql_idiomas);
$stmt_idiomas->execute();
$idiomas_db = $stmt_idiomas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas->close();

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// Buscar unidades do banco de dados
$sql_unidades = "SELECT id, nome_unidade FROM unidades ORDER BY nome_unidade";
$stmt_unidades = $conn->prepare($sql_unidades);
$stmt_unidades->execute();
$unidades_db = $stmt_unidades->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_unidades->close();

// Definição dos níveis de A1 a C2
$niveis_db = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

// Lógica para buscar os caminhos com base na pesquisa
$sql_caminhos = "SELECT id, idioma, nome_caminho, nivel FROM caminhos_aprendizagem WHERE 1=1";
$params = [];
$types = '';

if (isset($_GET['idioma']) && !empty($_GET['idioma'])) {
    $sql_caminhos .= " AND idioma = ?";
    $params[] = $_GET['idioma'];
    $types .= 's';
}

if (isset($_GET['nivel']) && !empty($_GET['nivel'])) {
    $sql_caminhos .= " AND nivel = ?";
    $params[] = $_GET['nivel'];
    $types .= 's';
}

$sql_caminhos .= " ORDER BY idioma, nivel, nome_caminho";

$stmt_caminhos = $conn->prepare($sql_caminhos);
if (!empty($params)) {
    $stmt_caminhos->bind_param($types, ...$params);
}

$stmt_caminhos->execute();
$caminhos = $stmt_caminhos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_caminhos->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Caminhos - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
   <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">

</head>

<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pesquisarCard = document.querySelector('.card-header h5');
    
    if (pesquisarCard) {
        // Criar elemento para o efeito de brilho
        const brilho = document.createElement('div');
        brilho.style.position = 'absolute';
        brilho.style.top = '0';
        brilho.style.left = '-100%';
        brilho.style.width = '50%';
        brilho.style.height = '100%';
        brilho.style.background = 'linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent)';
        brilho.style.transform = 'skewX(-20deg)';
        brilho.style.transition = 'none';
        brilho.style.pointerEvents = 'none';
        
        // Adicionar brilho ao card header
        pesquisarCard.parentElement.style.position = 'relative';
        pesquisarCard.parentElement.style.overflow = 'hidden';
        pesquisarCard.parentElement.appendChild(brilho);
        
        // Função para ativar o brilho
        function ativarBrilho() {
            brilho.style.transition = 'left 0.8s ease-in-out';
            brilho.style.left = '150%';
            
            // Reset após animação
            setTimeout(() => {
                brilho.style.transition = 'none';
                brilho.style.left = '-100%';
            }, 800);
        }
        
        // Ativar brilho a cada 3 segundos
        setInterval(ativarBrilho, 3000);
        
        // Ativar também ao passar o mouse
        pesquisarCard.parentElement.addEventListener('mouseenter', ativarBrilho);
    }
    
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
        <a href="estatisticas_usuarios.php" class="list-group-item">
            <i class="fas fa-chart-bar"></i> Estatísticas
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
            <div class="row mb-4" style="display: inline-flex;flex-flow: row nowrap;align-items: flex-start;justify-content: flex-start; width:80%">
                        <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>
                        <a href="#" class="btn btn-warning mb-4" data-bs-toggle="modal" data-bs-target="#addCaminhoModal" style="width: 220px; padding:15px">
                            <i class="fas fa-plus-circle me-2"></i>Adicionar Caminho
                        </a>
            </div>

            <!-- Estatísticas - MANTIDAS COMO ESTAVAM ANTES (EMBAIXO) -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-road"></i>
                        <h3><?= count($caminhos) ?></h3>
                        <p>Total de Caminhos</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-cubes"></i>
                        <h3><?= count($unidades_db) ?></h3>
                        <p>Total de Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-globe"></i>
                        <h3><?= count($idiomas_db) ?></h3>
                        <p>Total de Idiomas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-tasks"></i>
                        <h3><?= isset($quizzes_concluidos) ? $quizzes_concluidos : 0 ?></h3>
                        <p>Quizzes Concluídos</p>
                    </div>
                </div>
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

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-search me-2"></i>Pesquisar Caminhos
                    </h5>
                </div>
                <div class="card-body" style="display: inline-flex; align-items: flex-start; justify-content: flex-start; align-content: center; gap: 15px;">
                    <form action="" method="GET" style="display: inline-flex;width: fit-content; flex-flow: row nowrap;align-items: flex-start;justify-content: flex-start; gap:15px">
                        <div class="row-cols-md-auto g-3 align-items-center" style="display: inline-flex;width: fit-content; flex-flow: row nowrap;align-items: flex-start;justify-content: flex-start; gap:5px">
                            <div class="row-cols-md-auto" style="display: inline-flex;width: fit-content; flex-flow: row nowrap;align-items: flex-start;justify-content: flex-start; gap:5px">
                                <label for="idioma_busca" class="col-form-label">Idioma:</label>
                                <select id="idioma_busca" name="idioma" class="form-select" style="width:fit-content;padding: 10px 35px;">
                                    <option value="">Todos os Idiomas</option>
                                    <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>"
                                        <?php echo (isset($_GET['idioma']) && $_GET['idioma'] === $idioma['idioma']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($idioma['idioma']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row-cols-md-auto" style="display: inline-flex;width: fit-content; flex-flow: row nowrap;align-items: flex-start;justify-content: flex-start; gap:5px">
                                <label for="nivel_busca" class="col-form-label">Nível:</label>
                                <select id="nivel_busca" name="nivel" class="form-select" style="width:fit-content;padding: 10px 35px;">
                                    <option value="">Todos os Níveis</option>
                                    <?php foreach ($niveis_db as $nivel): ?>
                                    <option value="<?php echo htmlspecialchars($nivel); ?>"
                                        <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === $nivel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nivel); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                           <div class="col-md-auto d-flex align-items-end">
    <button type="submit" class="btn btn-outline-warning" style="margin: auto; color: black !important;">
        <i class="fas fa-search me-2" style="color: black;"></i>Pesquisar
    </button>
</div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-container table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Idioma</th>
                            <th>Caminho</th>
                            <th>Nível</th>
                            <th style="width:30%">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($caminhos)): ?>
                        <?php foreach ($caminhos as $caminho): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($caminho['id']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['idioma']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nome_caminho']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nivel']); ?></td>
                            <td class="btn-acoes">
                                <a href="gerenciar_blocos.php?caminho_id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                    class="btn btn-sm btn-info btn-blocos">
                                    <i class="fas fa-eye"></i> Ver Blocos
                                </a>
                                
                                <a href="editar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                    class="btn btn-sm btn-primary btn-editar">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-danger delete-btn btn-eliminar" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-id="<?php echo htmlspecialchars($caminho['id']); ?>"
                                    data-nome="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>"
                                    data-tipo="caminho" data-action="eliminar_caminho.php">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum caminho de aprendizado encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal para Adicionar Caminho -->
            <div class="modal fade" id="addCaminhoModal" tabindex="-1" aria-labelledby="addCaminhoModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCaminhoModalLabel">Adicionar Novo Caminho</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formAddCaminho">
                            <div class="modal-body">
                                <div id="alertCaminho"></div>
                                <div class="mb-3">
                                    <label for="idioma_novo" class="form-label">Idioma</label>
                                    <select id="idioma_novo" name="idioma" class="form-select" required>
                                        <option value="">Selecione o Idioma</option>
                                        <?php foreach ($idiomas_db as $idioma): ?>
                                        <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>">
                                            <?php echo htmlspecialchars($idioma['idioma']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nivel_novo" class="form-label">Nível</label>
                                    <select id="nivel_novo" name="nivel" class="form-select" required>
                                        <option value="">Selecione o Nível</option>
                                        <?php foreach ($niveis_db as $nivel): ?>
                                        <option value="<?php echo htmlspecialchars($nivel); ?>">
                                            <?php echo htmlspecialchars($nivel); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                                    <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" required>
                                </div>
                                <div class="mb-3">
                                    <label for="unidade_id" class="form-label">Unidade</label>
                                    <select id="unidade_id" name="unidade_id" class="form-select" required>
                                        <option value="">Selecione a Unidade</option>
                                        <?php foreach ($unidades_db as $unidade): ?>
                                        <option value="<?php echo htmlspecialchars($unidade['id']); ?>">
                                            <?php echo htmlspecialchars($unidade['nome_unidade']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="submit" class="btn btn-success" id="btnAddCaminho">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    Adicionar
                                </button>
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
                            <h5 class="modal-title" id="gerenciarIdiomasModalLabel">Gerenciar Idiomas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulário para adicionar idioma simples -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">➕ Adicionar Novo Idioma (Simples)</h6>
                                </div>
                                <div class="card-body">
                                    <form action="adicionar_idioma_simples.php" method="POST">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="nome_idioma" placeholder="Nome do idioma (ex: Alemão)" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-success w-100">Adicionar</button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Adiciona apenas o idioma. Você pode criar o quiz depois.</small>
                                    </form>
                                </div>
                            </div>

                            <p class="text-muted">Use o botão "Adicionar Novo Idioma com Quiz" para criar um idioma com quiz de nivelamento completo.</p>
                        </div>
                        <div class="modal-footer">
                            <a href="pagina_adicionar_idiomas.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Adicionar Novo Idioma com Quiz
                            </a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Confirmação de Eliminação -->
            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Tem certeza que deseja excluir o item <strong id="itemNome"></strong>?</p>
                            <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Script para o modal de confirmação de exclusão
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const itemNome = document.getElementById('itemNome');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    const tipo = this.getAttribute('data-tipo');
                    const action = this.getAttribute('data-action');

                    itemNome.textContent = nome;
                    confirmDeleteBtn.href = `${action}?id=${id}`;
                });
            });

            // Script para adicionar caminho via AJAX
            const formAddCaminho = document.getElementById('formAddCaminho');
            const btnAddCaminho = document.getElementById('btnAddCaminho');
            const alertCaminho = document.getElementById('alertCaminho');

            formAddCaminho.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const spinner = btnAddCaminho.querySelector('.spinner-border');
                const btnText = btnAddCaminho.querySelector('span:not(.spinner-border)');
                
                // Mostrar loading
                spinner.classList.remove('d-none');
                btnText.textContent = 'Adicionando...';
                btnAddCaminho.disabled = true;
                alertCaminho.innerHTML = '';

                fetch('adicionar_caminho.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertCaminho.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        formAddCaminho.reset();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alertCaminho.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertCaminho.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> Erro ao adicionar caminho. Tente novamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                })
                .finally(() => {
                    spinner.classList.add('d-none');
                    btnText.textContent = 'Adicionar';
                    btnAddCaminho.disabled = false;
                });
            });
        });
    </script>
</body>
</html>