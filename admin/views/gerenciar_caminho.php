<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Lógica para buscar os idiomas únicos do banco de dados das tabelas caminhos_aprendizagem e quiz_nivelamento
// Isso garante que idiomas com apenas um quiz, mas sem caminhos, também sejam exibidos
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$stmt_idiomas = $conn->prepare($sql_idiomas);
$stmt_idiomas->execute();
$idiomas_db = $stmt_idiomas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas->close();

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    .list-group-item {
        background-color: var(--branco);
        color: var(--preto-texto);
        border: 1px solid var(--cinza-medio);
    }

    .list-group-item:hover {
        background-color: var(--cinza-claro);
        color: var(--preto-texto);
    }

    .list-group-item.active {
        background-color: var(--roxo-principal);
        color: var(--branco);
        border-color: var(--roxo-principal);
    }

    .list-group-item.active:hover {
        background-color: var(--roxo-escuro);
        color: var(--branco);
    }

    .list-group-item i {
        color: var(--amarelo-detalhe);
    }

    /* Paleta de Cores */
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

    /* Barra de Navegação */
    .navbar {
        background: var(--roxo-principal) !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

    /* Estilos de Cartões (Cards) */
    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }

    .card-header {
        background-color: var(--roxo-principal);
        color: var(--branco);
        border-radius: 1rem 1rem 0 0 !important;
        padding: 1.5rem;
    }

    .card-header h2 {
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* Card de Unidade (unidade-card) */
    .unidade-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        cursor: pointer;
        border: 2px solid transparent;
    }

    .unidade-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border-color: var(--amarelo-detalhe);
    }

    .unidade-card .progress {
        height: 10px;
        background-color: var(--cinza-medio);
    }

    .unidade-card .progress-bar {
        background-color: var(--amarelo-detalhe);
        animation: progressFill 1s ease-out forwards;
    }

    @keyframes progressFill {
        from {
            width: 0;
        }
    }

    /* Card de Atividade (atividade-card) */
    .atividade-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid var(--cinza-medio);
        border-radius: 0.75rem;
        background: var(--branco);
    }

    .atividade-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--roxo-principal);
    }

    .atividade-icon {
        font-size: 3rem;
        color: var(--amarelo-detalhe);
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
    }

    .atividade-card:hover .atividade-icon {
        transform: scale(1.1);
    }

    /* Estilos de Modal */
    .modal-overlay {
        background-color: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
    }

    .popup-modal {
        max-width: 900px;
        animation: modalSlideIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-50px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal-content {
        border-radius: 1.5rem;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        border-bottom: none;
        padding: 1.5rem 2rem;
        background-color: var(--roxo-principal);
        color: var(--branco);
        border-radius: 1.5rem 1.5rem 0 0;
    }

    .modal-header h5 {
        font-weight: 600;
    }

    .modal-body {
        padding: 2rem;
    }

    .btn-close {
        filter: invert(1);
        background-size: 0.8rem;
    }

    /* Botões de Resposta do Quiz */
    .btn-resposta {
        margin: 0.75rem 0;
        padding: 1rem 1.5rem;
        text-align: left;
        border: 2px solid var(--cinza-medio);
        background: var(--branco);
        border-radius: 0.75rem;
        transition: all 0.3s ease;
        width: 100%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .btn-resposta:hover {
        border-color: var(--amarelo-detalhe);
        background: var(--cinza-claro);
        transform: translateY(-2px);
    }

    .btn-resposta.selected {
        border-color: var(--roxo-principal);
        background: #e3d4ff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-resposta.correct {
        border-color: #28a745;
        background: #d4edda;
        animation: correctAnim 0.5s ease;
    }

    .btn-resposta.incorrect {
        border-color: #dc3545;
        background: #f8d7da;
        animation: incorrectAnim 0.5s ease;
    }

    @keyframes correctAnim {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.03);
        }
    }

    @keyframes incorrectAnim {

        0%,
        100% {
            transform: translateX(0);
        }

        25%,
        75% {
            transform: translateX(-5px);
        }

        50% {
            transform: translateX(5px);
        }
    }

    /* Estilos de Feedback */
    .feedback-container {
        margin-top: 1.5rem;
        padding: 1.5rem;
        border-radius: 1rem;
        font-weight: 500;
        display: none;
        animation: slideInUp 0.5s ease;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .feedback-success {
        background: #e6ffed;
        border: 1px solid #28a745;
        color: #155724;
    }

    .feedback-error {
        background: #fff0f0;
        border: 1px solid #dc3545;
        color: #721c24;
    }

    .btn-proximo-custom {
        background-color: var(--roxo-principal);
        border-color: var(--roxo-principal);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-proximo-custom:hover {
        background-color: var(--roxo-escuro);
        border-color: var(--roxo-escuro);
        transform: scale(1.05);
    }

    /* Animações e Efeitos */
    .fs-4 .badge {
        background-color: var(--amarelo-detalhe) !important;
        color: var(--preto-texto);
        font-weight: 700;
        padding: 0.5em 1em;
        border-radius: 50px;
        animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4);
        }

        70% {
            box-shadow: 0 0 0 15px rgba(255, 215, 0, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
        }
    }

    .progress-bar-custom .progress-bar {
        background-color: var(--amarelo-detalhe);
        box-shadow: 0 0 10px var(--amarelo-detalhe);
    }

    /* Menu Lateral Lateral Esquerda */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100%;
        background-color: var(--roxo-principal);
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
    }

    .sidebar .profile i {
        font-size: 4rem;
        color: var(--amarelo-detalhe);
        margin-bottom: 10px;
    }

    .sidebar .profile h5 {
        font-weight: 600;
        margin-bottom: 0;
    }

    .sidebar .profile small {
        color: var(--cinza-claro);
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
        background-color: var(--amarelo-detalhe);
        color: var(--roxo-principal);
        font-weight: 600;
    }

    .sidebar .list-group-item i {
        color: var(--amarelo-detalhe);
    }

    /* Ajuste do conteúdo principal para não ficar por baixo do sidebar */
    .main-content {
        margin-left: 250px;
        padding: 20px;
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
    }

    /* Ajuste da logo no header */
    .navbar-brand .logo-header {
        height: 70px;
        /* altura da logo */
        width: auto;
        /* mantém proporção */
        display: block;
    }

    /* Se quiser centralizar verticalmente em relação ao navbar */
    .navbar {
        display: flex;
        align-items: center;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
            </a>

            <!-- Espaço flexível entre a logo e o ícone -->
            <div class="ms-auto">
                <a href="editar_perfil.php" class="text-white settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>



    <div class="container mt-5">
        <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>

        <div class="sidebar">
            <div class="profile">
                <i class="fas fa-user-circle"></i>
                <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
                <small>Administrador</small>
            </div>

            <div class="list-group">
                <a href="#" class="list-group-item" data-bs-toggle="modal" data-bs-target="#addCaminhoModal">
                    <i class="fas fa-plus-circle"></i> Adicionar Caminho
                </a>
                <a href="#" class="list-group-item" data-bs-toggle="modal"
                    data-bs-target="#adicionarIdiomaCompletoModal">
                    <i class="fas fa-language"></i> Adicionar Idioma com Quiz
                </a>
                <a href="#" class="list-group-item" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal">
                    <i class="fas fa-globe"></i> Gerenciar Idiomas
                </a>
                <a href="gerenciar_teorias.php" class="list-group-item">
                    <i class="fas fa-book-open"></i> Gerenciar Teorias
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



        <div class="col-md-9">

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
                    Pesquisar Caminhos
                </div>
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
                                <button type="submit" class="btn btn-primary"
                                    style="margin-top: 40px;">Pesquisar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <table class="table table-bordered table-striped">
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
                        <td>
                            <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                class="btn btn-sm btn-info">Ver Exercícios</a>
                            <a href="editar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>"
                                class="btn btn-sm btn-primary">Editar</a>
                            <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-id="<?php echo htmlspecialchars($caminho['id']); ?>"
                                data-nome="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>"
                                data-tipo="caminho" data-action="eliminar_caminho.php">
                                Eliminar
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

        <div class="modal fade" id="addCaminhoModal" tabindex="-1" aria-labelledby="addCaminhoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="adicionar_caminho.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCaminhoModalLabel">Adicionar Novo Caminho</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="idioma_novo" class="form-label">Idioma</label>
                                <select id="idioma_novo" name="idioma" class="form-select" required>
                                    <option value="">Selecione o Idioma</option>
                                    <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>">
                                        <?php echo htmlspecialchars($idioma['idioma']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nivel_novo" class="form-label">Nível</label>
                                <select id="nivel_novo" name="nivel" class="form-select" required>
                                    <option value="">Selecione o Nível</option>
                                    <?php foreach ($niveis_db as $nivel): ?>
                                    <option value="<?php echo htmlspecialchars($nivel); ?>">
                                        <?php echo htmlspecialchars($nivel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                                <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-success">Adicionar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="adicionarIdiomaCompletoModal" tabindex="-1"
            aria-labelledby="adicionarIdiomaCompletoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="adicionar_idioma_completo.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="adicionarIdiomaCompletoModalLabel">Adicionar Novo Idioma com
                                Quiz</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="idioma_novo_completo" class="form-label">Nome do Idioma</label>
                                <input type="text" class="form-control" id="idioma_novo_completo" name="idioma"
                                    placeholder="Ex: Espanhol" required>
                            </div>

                            <hr>
                            <h5>Perguntas do Quiz de Nivelamento (20 perguntas)</h5>
                            <p class="text-muted">A resposta correta para cada pergunta deve ser "A", "B" ou "C".
                            </p>

                            <?php for ($i = 1; $i <= 20; $i++): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    Pergunta #<?php echo $i; ?>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="pergunta_<?php echo $i; ?>" class="form-label">Pergunta</label>
                                        <textarea class="form-control" id="pergunta_<?php echo $i; ?>"
                                            name="pergunta_<?php echo $i; ?>" rows="2" required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_a_<?php echo $i; ?>" class="form-label">Opção
                                                A</label>
                                            <input type="text" class="form-control" id="opcao_a_<?php echo $i; ?>"
                                                name="opcao_a_<?php echo $i; ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_b_<?php echo $i; ?>" class="form-label">Opção
                                                B</label>
                                            <input type="text" class="form-control" id="opcao_b_<?php echo $i; ?>"
                                                name="opcao_b_<?php echo $i; ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_c_<?php echo $i; ?>" class="form-label">Opção
                                                C</label>
                                            <input type="text" class="form-control" id="opcao_c_<?php echo $i; ?>"
                                                name="opcao_c_<?php echo $i; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="resposta_correta_<?php echo $i; ?>" class="form-label">Resposta
                                            Correta</label>
                                        <select id="resposta_correta_<?php echo $i; ?>"
                                            name="resposta_correta_<?php echo $i; ?>" class="form-select" required>
                                            <option value="">Selecione a resposta correta</option>
                                            <option value="A">Opção A</option>
                                            <option value="B">Opção B</option>
                                            <option value="C">Opção C</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-success">Salvar Idioma e Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="gerenciarIdiomasModal" tabindex="-1" aria-labelledby="gerenciarIdiomasModalLabel"
            aria-hidden="true">
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
                                            <input type="text" class="form-control" name="nome_idioma"
                                                placeholder="Nome do idioma (ex: Alemão)" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-success w-100">Adicionar</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Adiciona apenas o idioma. Você pode criar o quiz
                                        depois.</small>
                                </form>
                            </div>
                        </div>

                        <p class="text-muted">
                            Use o botão "Adicionar Novo Idioma com Quiz" para criar um novo idioma completo com quiz
                            de nivelamento.
                        </p>

                        <h5>Idiomas Existentes</h5>
                        <ul class="list-group">
                            <?php if (!empty($idiomas_db)): ?>
                            <?php foreach ($idiomas_db as $idioma): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($idioma['idioma']); ?></span>
                                <div>
                                    <a href="gerenciador_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma['idioma']); ?>"
                                        class="btn btn-info btn-sm me-2">Gerenciar Quiz</a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn"
                                        data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                        data-id="<?php echo urlencode($idioma['idioma']); ?>"
                                        data-nome="<?php echo htmlspecialchars($idioma['idioma']); ?>"
                                        data-tipo="idioma" data-action="excluir_idioma.php">
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

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmação de Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="confirmDeleteModalBody">
                    </div>
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

        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notificationModalLabel">Notificação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="notificationModalBody">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <h5>Total de Caminhos</h5>
                    <span class="fs-3 fw-bold"><?= count($caminhos) ?></span>
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
                    const button = event.relatedTarget; // Botão que acionou o modal
                    const itemId = button.getAttribute('data-id');
                    const itemName = button.getAttribute('data-nome');
                    const itemType = button.getAttribute('data-tipo');
                    const formAction = button.getAttribute('data-action');

                    const modalBody = confirmDeleteModal.querySelector('#confirmDeleteModalBody');
                    const modalForm = confirmDeleteModal.querySelector('#deleteForm');
                    const hiddenInput = confirmDeleteModal.querySelector('#deleteItemId');

                    let message = '';
                    if (itemType === 'idioma') {
                        message =
                            `Tem certeza que deseja excluir o idioma '<strong>${itemName}</strong>'? Isso excluirá todos os caminhos, exercícios e quizzes associados a ele.`;
                    } else {
                        message =
                            `Tem certeza que deseja excluir o caminho '<strong>${itemName}</strong>'?`;
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

                // Limpa a URL para evitar que o modal apareça novamente ao recarregar
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        </script>
</body>

</html>