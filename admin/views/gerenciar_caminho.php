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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon" style="color: var(--roxo-principal) !important;">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <?php
        if (isset($_GET["message_type"]) && isset($_GET["message_content"])) {
            $message_type = htmlspecialchars($_GET["message_type"]);
            $message_content = htmlspecialchars(urldecode($_GET["message_content"]));
            echo '<div class="alert alert-' . ($message_type == 'success' ? 'success' : 'danger') . ' mt-3">' . $message_content . '</div>';
        }
        ?>
        <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>

        <a href="#" class="btn btn-warning mb-4" data-bs-toggle="modal" data-bs-target="#addCaminhoModal">
            <i class="fas fa-plus-circle me-2"></i>Adicionar Caminho
        </a>

        <div class="sidebar">
            <div class="profile">
                <i class="fas fa-user-circle"></i>
                <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
                <small>Administrador(a)</small>
            </div>

            <div class="list-group">
                <a href="gerenciar_caminho.php" class="list-group-item" >
                    <i class="fas fa-plus-circle"></i> Adicionar Caminho
                </a>
                <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                    <i class="fas fa-language "></i> Gerenciar Idiomas
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
                <a href="logout.php" class="list-group-item mt-auto">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>

         <div class="col-md-9" style="width: 95%;">
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
                <div class="card-header">Pesquisar Caminhos</div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-auto">
                                <label for="idioma_busca" class="col-form-label">Idioma:</label>
                                <select id="idioma_busca" name="idioma" class="form-select">
                                    <option value="">Todos os Idiomas</option>
                                    <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>"
                                        <?php echo (isset($_GET['idioma']) && $_GET['idioma'] === $idioma['idioma']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($idioma['idioma']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-auto">
                                <label for="nivel_busca" class="col-form-label">Nível:</label>
                                <select id="nivel_busca" name="nivel" class="form-select">
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
                                <button type="submit" class="btn btn-outline-warning" style="margin-top: 40px;">Pesquisar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <table class="table table-container table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Idioma</th>
                        <th>Caminho</th>
                        <th>Nível</th>
                        <th>Ações</th>
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

                        <p class="text-muted">Use o botão "Adicionar Novo Idioma com Quiz" para criar um novo idioma completo com quiz de nivelamento.</p>

                        <h5>Idiomas Existentes</h5>
                        <ul class="list-group">
                            <?php if (!empty($idiomas_db)): ?>
                            <?php foreach ($idiomas_db as $idioma): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($idioma['idioma']); ?></span>
                                <div>
                                    <a href="gerenciador_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma['idioma']); ?>" class="btn btn-info btn-sm me-2">Gerenciar Quiz</a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                        data-id="<?php echo urlencode($idioma['idioma']); ?>" data-nome="<?php echo htmlspecialchars($idioma['idioma']); ?>" data-tipo="idioma" data-action="excluir_idioma.php">
                                        Excluir
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

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <h5>Total de Caminhos</h5>
                    <span class="fs-3 fw-bold"><?= count($caminhos) ?></span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center p-3">
                    <h5>Total de Unidades</h5>
                    <span class="fs-3 fw-bold"><?= count($unidades_db) ?></span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center p-3">
                    <h5>Total de Idiomas</h5>
                    <span class="fs-3 fw-bold"><?= count($idiomas_db) ?></span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center p-3">
                    <h5>Quizzes Concluídos</h5>
                    <span class="fs-3 fw-bold"><?= isset($quizzes_concluidos) ? $quizzes_concluidos : 0 ?></span>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // AJAX para adicionar caminho
            const formAddCaminho = document.getElementById('formAddCaminho');
            if (formAddCaminho) {
                const btnAddCaminho = document.getElementById('btnAddCaminho');
                const alertCaminho = document.getElementById('alertCaminho');
                const spinner = btnAddCaminho.querySelector('.spinner-border');

                formAddCaminho.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Mostrar loading
                    btnAddCaminho.disabled = true;
                    spinner.classList.remove('d-none');
                    
                    // Coletar dados do formulário
                    const formData = new FormData(this);
                    
                    // Adicionar header para identificar como AJAX
                    const headers = new Headers();
                    headers.append('X-Requested-With', 'XMLHttpRequest');
                    
                    // Enviar via AJAX
                    fetch('adicionar_caminho.php', {
                        method: 'POST',
                        headers: headers,
                        body: formData
                    })
                    .then(response => {
                        // Primeiro verificar se a resposta é JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            // Se não for JSON, retornar o texto para debug
                            return response.text().then(text => {
                                throw new Error('Resposta não é JSON: ' + text.substring(0, 100));
                            });
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            // Sucesso
                            alertCaminho.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                            
                            // Limpar formulário
                            formAddCaminho.reset();
                            
                            // Recarregar a página após 1.5 segundos para mostrar o novo caminho
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Erro
                            alertCaminho.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Erro completo:', error);
                        alertCaminho.innerHTML = `<div class="alert alert-danger">Erro ao adicionar caminho: ${error.message}</div>`;
                    })
                    .finally(() => {
                        // Esconder loading
                        btnAddCaminho.disabled = false;
                        spinner.classList.add('d-none');
                    });
                });

                // Limpar alerta quando o modal for fechado
                const addCaminhoModal = document.getElementById('addCaminhoModal');
                if (addCaminhoModal) {
                    addCaminhoModal.addEventListener('hidden.bs.modal', function() {
                        if (alertCaminho) {
                            alertCaminho.innerHTML = '';
                        }
                        formAddCaminho.reset();
                    });
                }
            }
        });
        </script>
</body>
</html>