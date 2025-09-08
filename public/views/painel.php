<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    // Feche a conexão antes de redirecionar
    $database->closeConnection();
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$idioma_escolhido = null;
$nivel_usuario = null;
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$mostrar_selecao_idioma = false;

// Processa seleção de idioma para usuários sem progresso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["idioma_inicial"])) {
    $idioma_inicial = $_POST["idioma_inicial"];
    $nivel_inicial = "A1";
    
    // Insere progresso inicial para o usuário
    $sql_insert_progresso = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert_progresso);
    $stmt_insert->bind_param("iss", $id_usuario, $idioma_inicial, $nivel_inicial);
    
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        // Redireciona para o quiz de nivelamento
        $database->closeConnection();
        header("Location: quiz.php?idioma=$idioma_inicial");
        exit();
    } else {
        $erro_selecao = "Erro ao registrar idioma. Tente novamente.";
    }
    $stmt_insert->close();
}

// Tenta obter o idioma e o nível da URL (se veio do pop-up de resultados)
if (isset($_GET["idioma"]) && isset($_GET["nivel_escolhido"])) {
    $idioma_escolhido = $_GET["idioma"];
    $nivel_usuario = $_GET["nivel_escolhido"];
    
    // Atualiza o nível do usuário no banco de dados com a escolha final
    $sql_update_nivel = "UPDATE progresso_usuario SET nivel = ? WHERE id_usuario = ? AND idioma = ?";
    $stmt_update_nivel = $conn->prepare($sql_update_nivel);
    $stmt_update_nivel->bind_param("sis", $nivel_usuario, $id_usuario, $idioma_escolhido);
    $stmt_update_nivel->execute();
    $stmt_update_nivel->close();

} else {
    // Se não veio da URL, busca o último idioma e nível do banco de dados
    $sql_progresso = "SELECT idioma, nivel FROM progresso_usuario WHERE id_usuario = ? ORDER BY id DESC LIMIT 1";
    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param("i", $id_usuario);
    $stmt_progresso->execute();
    $resultado = $stmt_progresso->get_result()->fetch_assoc();
    $stmt_progresso->close();

    if ($resultado) {
        $idioma_escolhido = $resultado["idioma"];
        $nivel_usuario = $resultado["nivel"];
    } else {
        // Se o usuário não tem progresso, mostra seleção de idioma
        $mostrar_selecao_idioma = true;
    }
}

// Busca unidades apenas se o usuário tem progresso
if (!$mostrar_selecao_idioma) {
    $sql_unidades = "SELECT * FROM unidades WHERE idioma = ? AND nivel = ? ORDER BY numero_unidade ASC";
    $stmt_unidades = $conn->prepare($sql_unidades);
    $stmt_unidades->bind_param("ss", $idioma_escolhido, $nivel_usuario);
    $stmt_unidades->execute();
    $unidades = $stmt_unidades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_unidades->close();
}

// Feche a conexão usando o método da classe
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - <?php echo htmlspecialchars($idioma_escolhido); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
    from { opacity: 0; }
    to { opacity: 1; }
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
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
    from { width: 0; }
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
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}

@keyframes incorrectAnim {
    0%, 100% { transform: translateX(0); }
    25%, 75% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
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
    0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
    70% { box-shadow: 0 0 0 15px rgba(255, 215, 0, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
}

.progress-bar-custom .progress-bar {
    background-color: var(--amarelo-detalhe);
    box-shadow: 0 0 10px var(--amarelo-detalhe);
}
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Site de Idiomas</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="//logout.php" class="btn btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($mostrar_selecao_idioma): ?>
                    <!-- Seleção de idioma para usuários sem progresso -->
                    <div class="card">
                        <div class="card-header text-center">
                            <h2>Bem-vindo! Escolha seu primeiro idioma</h2>
                        </div>
                        <div class="card-body">
                            <?php if (isset($erro_selecao)): ?>
                                <div class="alert alert-danger"><?php echo $erro_selecao; ?></div>
                            <?php endif; ?>
                            <p class="text-center mb-4">Para começar sua jornada de aprendizado, selecione o idioma que deseja estudar:</p>
                            <form method="POST" action="painel.php">
                                <div class="mb-3">
                                    <label for="idioma_inicial" class="form-label">Escolha seu idioma</label>
                                    <select class="form-select" id="idioma_inicial" name="idioma_inicial" required>
                                        <option value="" disabled selected>Selecione um idioma</option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Começar Quiz de Nivelamento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Painel normal para usuários com progresso -->
                    <div class="card mb-4">
                        <div class="card-header text-center">
                            <h2>Seu Caminho de Aprendizado em <?php echo htmlspecialchars($idioma_escolhido); ?></h2>
                        </div>
                        <div class="card-body text-center">
                            <p class="fs-4">Seu nível atual é: <span class="badge bg-success"><?php echo htmlspecialchars($nivel_usuario); ?></span></p>
                        </div>
                    </div>

                    <!-- Seção Flash Cards -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title mb-2">
                                        <i class="fas fa-layer-group me-2 text-warning"></i>
                                        Flash Cards
                                    </h5>
                                    <p class="card-text text-muted mb-0">
                                        Estude com flashcards personalizados e melhore sua memorização
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="flashcards.php" class="btn btn-warning me-2">
                                        <i class="fas fa-layer-group me-2"></i>Meus Decks</a>
                            </div>
                            <div class="col-md-4 text-end">
                                    <a href="flashcard_estudo.php" class="btn btn-outline-warning">
                                        <i class="fas fa-play me-2"></i>Estudar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Gerenciamento de Palavras -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <i class="fas fa-book me-2"></i>
                                        Minhas Palavras
                                    </h5>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn btn-light btn-sm" onclick="abrirModalAdicionarPalavra()">
                                        <i class="fas fa-plus me-2"></i>Adicionar Palavra
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filtros de Palavras -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" id="filtroPalavrasStatus" onchange="carregarPalavras()">
                                        <option value="">Todas as palavras</option>
                                        <option value="0">Não aprendidas</option>
                                        <option value="1">Aprendidas</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" id="filtroPalavrasBusca" placeholder="Buscar palavra..." onkeyup="filtrarPalavrasLocal()">
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="carregarPalavras()">
                                        <i class="fas fa-sync me-1"></i>Atualizar
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Lista de Palavras -->
                            <div id="listaPalavras" class="row">
                                <div class="col-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando suas palavras...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4>Unidades do Nível <?php echo htmlspecialchars($nivel_usuario); ?></h4>
                    <div class="row">
                        <?php if (count($unidades) > 0): ?>
                            <?php foreach ($unidades as $unidade): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card unidade-card h-100" onclick="abrirAtividades(<?php echo $unidade["id"]; ?>, '<?php echo htmlspecialchars($unidade["titulo"]); ?>', <?php echo $unidade["numero_unidade"]; ?>)">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-book-open me-2"></i>
                                                Unidade <?php echo htmlspecialchars($unidade["numero_unidade"]); ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($unidade["titulo"]); ?></h6>
                                            <p class="card-text"><?php echo htmlspecialchars($unidade["descricao"]); ?></p>
                                            <div class="progress progress-bar-custom">
                                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">0% concluído</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    Nenhuma unidade encontrada para este nível e idioma.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Palavra -->
    <div class="modal fade" id="modalAdicionarPalavra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Adicionar Nova Palavra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formAdicionarPalavra">
                        <div class="mb-3">
                            <label for="palavraFrente" class="form-label">Palavra/Frase *</label>
                            <input type="text" class="form-control" id="palavraFrente" name="palavra_frente" required placeholder="Ex: Hello">
                        </div>
                        <div class="mb-3">
                            <label for="palavraVerso" class="form-label">Tradução *</label>
                            <input type="text" class="form-control" id="palavraVerso" name="palavra_verso" required placeholder="Ex: Olá">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="palavraIdioma" class="form-label">Idioma</label>
                                    <select class="form-select" id="palavraIdioma" name="idioma">
                                        <option value="<?php echo htmlspecialchars($idioma_escolhido); ?>" selected>
                                            <?php echo htmlspecialchars($idioma_escolhido); ?>
                                        </option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="palavraNivel" class="form-label">Nível</label>
                                    <select class="form-select" id="palavraNivel" name="nivel">
                                        <option value="<?php echo htmlspecialchars($nivel_usuario); ?>" selected>
                                            <?php echo htmlspecialchars($nivel_usuario); ?>
                                        </option>
                                        <option value="A1">A1</option>
                                        <option value="A2">A2</option>
                                        <option value="B1">B1</option>
                                        <option value="B2">B2</option>
                                        <option value="C1">C1</option>
                                        <option value="C2">C2</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="adicionarPalavra()">
                        <i class="fas fa-plus me-2"></i>Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Atividades -->
    <div class="modal fade" id="modalAtividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg popup-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloAtividades">Atividades da Unidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaAtividades" class="row">
                        <!-- Atividades serão carregadas aqui via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exercícios -->
    <div class="modal fade" id="modalExercicios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg popup-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloExercicios">Exercícios</h5>
                    <div class="d-flex align-items-center">
                        <span id="contadorExercicios" class="badge bg-primary me-3">1/12</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body exercicio-container">
                    <div class="progress progress-bar-custom mb-4">
                        <div id="progressoExercicios" class="progress-bar" role="progressbar" style="width: 8.33%" aria-valuenow="8.33" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="conteudoExercicio">
                        <!-- Conteúdo do exercício será carregado aqui via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="voltarParaAtividades()">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </button>
                    <button type="button" id="btnEnviarResposta" class="btn btn-primary" onclick="enviarResposta()">
                        Enviar Resposta
                    </button>
                    <button type="button" id="btnProximoExercicio" class="btn btn-success" onclick="proximoExercicio()" style="display: none;">
                        Próximo <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Garante que o script só execute após o DOM estar completamente carregado
document.addEventListener('DOMContentLoaded', function() {

    // --- Variáveis Globais ---
    let unidadeAtual = null;
    let atividadeAtual = null;
    let exercicioAtual = null;
    let exerciciosLista = [];
    let exercicioIndex = 0;
    let respostaSelecionada = null;

    // Inicializa os Modais do Bootstrap
    const modalAtividades = new bootstrap.Modal(document.getElementById('modalAtividades'));
    const modalExercicios = new bootstrap.Modal(document.getElementById('modalExercicios'));

    // --- Manipulação de Eventos e UI ---

    // Adiciona evento de clique para os cards de unidade (se existirem no seu HTML)
    const unidadeCards = document.querySelectorAll('.unidade-card');
    unidadeCards.forEach(card => {
        card.addEventListener('click', () => {
            // Assume que o card tem atributos data-id, data-titulo e data-numero para obter as informações
            const unidadeId = card.dataset.id;
            const titulo = card.dataset.titulo;
            const numero = card.dataset.numero;

            if (unidadeId && titulo && numero) {
                abrirAtividades(unidadeId, titulo, numero);
            }
            // Adiciona uma classe para animação, se desejado (animação já deve estar no CSS)
            card.classList.add('animate__pulse');
            // Remove a classe após a animação para permitir que ela seja repetida
            card.addEventListener('animationend', () => card.classList.remove('animate__pulse'), { once: true });
        });
    });

    // Animação para os botões de resposta dos exercícios (seleção)
    const btnResposta = document.querySelectorAll('.btn-resposta');
    btnResposta.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove a classe 'selected' de todos os botões e adiciona ao clicado
            btnResposta.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            respostaSelecionada = btn.dataset.id; // Armazena o ID da resposta selecionada
        });
    });

    // --- Funções Principais de Navegação e Lógica ---

    // Função para abrir modal de atividades
    window.abrirAtividades = function(unidadeId, tituloUnidade, numeroUnidade) {
        // Fecha o modal de exercícios se estiver aberto
        if (modalExercicios && modalExercicios._isShown) {
            modalExercicios.hide();
        }
        
        unidadeAtual = unidadeId;
        document.getElementById("tituloAtividades").textContent = `Atividades da Unidade ${numeroUnidade}: ${tituloUnidade}`;
        
        // Carregar atividades via AJAX
        fetch(`../../admin/controller/get_atividades.php?unidade_id=${unidadeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exibirAtividades(data.atividades);
                    modalAtividades.show();
                } else {
                    alert("Erro ao carregar atividades: " + data.message);
                }
            })
            .catch(error => {
                console.error("Erro ao carregar atividades:", error);
                alert("Erro de rede ao carregar atividades.");
            });
    };

    // Função para exibir atividades no modal
    function exibirAtividades(atividades) {
        const container = document.getElementById("listaAtividades");
        container.innerHTML = ""; // Limpa o container antes de adicionar novas atividades

        atividades.forEach(atividade => {
            const col = document.createElement("div");
            col.className = "col-md-6 mb-3";
            
            col.innerHTML = `
                <div class="card atividade-card h-100" onclick="abrirExercicios(${atividade.id}, '${atividade.nome}')">
                    <div class="card-body text-center">
                        <i class="fas ${atividade.icone} atividade-icon"></i>
                        <h5 class="card-title">${atividade.nome}</h5>
                        <p class="card-text text-muted">${atividade.descricao}</p>
                        <div class="progress progress-bar-custom mb-2">
                            <div class="progress-bar" role="progressbar" style="width: ${atividade.progresso}%" aria-valuenow="${atividade.progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">${atividade.progresso}% concluído</small>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="event.stopPropagation(); abrirTeoriaAtividade(${atividade.id}, '${atividade.nome}')">
                            <i class="fas fa-info-circle me-1"></i>Teoria
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    }

    // Função para abrir modal de exercícios
    window.abrirExercicios = function(atividadeId, tituloAtividade) {
        atividadeAtual = atividadeId;
        document.getElementById("tituloExercicios").textContent = `Exercícios: ${tituloAtividade}`;
        
        // Carregar exercícios via AJAX
        fetch(`../../admin/controller/get_exercicio.php?atividade_id=${atividadeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exerciciosLista = data.exercicios;
                    exercicioIndex = 0;
                    if (exerciciosLista.length > 0) {
                        carregarExercicio(exercicioIndex);
                        modalAtividades.hide(); // Fecha modal de atividades
                        modalExercicios.show(); // Abre modal de exercícios
                        bootstrap.Modal(document.getElementById("modalAtividades")).show();
                    } else {
                        showCustomAlert("Nenhum exercício encontrado para esta atividade." + data.message, "error");
                    }
                } else {
                    showCustomAlert("Erro ao carregar", "Erro ao carregar atividades: " + data.message, "error");
                }
            })
            .catch(error => {
                console.error("Erro ao carregar exercícios:", error);
                alert("Erro de rede ao carregar exercícios.");
            });
    };

    // Função para carregar um exercício específico no modal de Exercícios
    function carregarExercicio(index) {
        if (!exerciciosLista || exerciciosLista.length === 0) return;

        exercicioAtual = exerciciosLista[index];
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        conteudoExercicioDiv.innerHTML = ""; // Limpa conteúdo anterior
        respostaSelecionada = null; // Reseta a resposta selecionada

        // Atualiza contador de progresso do exercício
        document.getElementById("contadorExercicios").textContent = `${index + 1}/${exerciciosLista.length}`;
        const progresso = ((index + 1) / exerciciosLista.length) * 100;
        const progressoBar = document.getElementById("progressoExercicios");
        if (progressoBar) {
            progressoBar.style.width = `${progresso}%`;
            progressoBar.setAttribute("aria-valuenow", progresso);
        }

        let htmlConteudo = `
            <p class="fs-5 mb-4">${exercicioAtual.pergunta}</p>
        `;

        // Parse do conteúdo JSON do exercício
        let conteudo = {};
        try {
            conteudo = JSON.parse(exercicioAtual.conteudo);
        } catch (e) {
            console.error("Erro ao fazer parse do conteúdo do exercício:", e);
            conteudo = {};
        }

        // Renderiza o conteúdo com base no tipo de exercício
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            if (conteudo.alternativas) {
                conteudo.alternativas.forEach(alt => {
                    htmlConteudo += `
                        <button type="button" class="btn btn-light btn-resposta w-100 mb-2" data-id="${alt.id}" onclick="selecionarResposta(this)">
                            ${alt.texto}
                        </button>
                    `;
                });
            }
        } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
            htmlConteudo += `
                <div class="mb-3">
                    <textarea id="respostaTextoLivre" class="form-control" rows="4" placeholder="Digite sua resposta aqui..." style="width: 100%; min-height: 100px;"></textarea>
                </div>
            `;
        } else if (exercicioAtual.tipo_exercicio === "fala") {
            htmlConteudo += `
                <div class="text-center p-4">
                    <i class="fas fa-microphone fa-5x text-primary mb-3" id="microfoneIcon" style="cursor: pointer;" onclick="iniciarGravacao()"></i>
                    <p id="statusGravacao" class="text-muted fs-5">Clique no microfone para falar</p>
                    <p id="fraseParaFalar" class="fs-4 fw-bold text-secondary">"${conteudo.frase_esperada || 'Nenhuma frase definida'}"</p>
                </div>
            `;
        } else if (exercicioAtual.tipo_exercicio === "especial") {
            htmlConteudo += `
                <div id="conteudoEspecial">
                    <p class="alert alert-info">Carregando exercício especial...</p>
                </div>
            `;
            // Chama a função específica para carregar o conteúdo especial (assumindo que ela existe em outro script ou é definida aqui)
            carregarConteudoEspecial(conteudo);
        }

        conteudoExercicioDiv.innerHTML = htmlConteudo;

        // Atualiza botões de ação
        document.getElementById("btnEnviarResposta").style.display = "block";
        document.getElementById("btnProximoExercicio").style.display = "none";
        
        // Remove feedback anterior se existir
        const feedbackDiv = document.getElementById("feedbackExercicio");
        if (feedbackDiv) feedbackDiv.remove();
    }

    // Função para selecionar resposta (botão de múltipla escolha)
    window.selecionarResposta = function(button) {
        // Remove a seleção de todos os botões de resposta
        document.querySelectorAll(".btn-resposta").forEach(btn => {
            btn.classList.remove("selected");
        });
        // Adiciona a seleção ao botão clicado
        button.classList.add("selected");
        respostaSelecionada = button.dataset.id; // Armazena o ID da resposta selecionada
    };

    // Função para enviar a resposta do usuário
    window.enviarResposta = function() {
        let respostaUsuario = null;
        
        if (!exercicioAtual) {
            alert("Erro: Nenhum exercício está ativo.");
            return;
        }

        // Captura a resposta com base no tipo de exercício
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            respostaUsuario = respostaSelecionada;
        } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
            const textarea = document.getElementById("respostaTextoLivre");
            if (!textarea) {
                alert("Erro: Campo de resposta de texto não encontrado.");
                return;
            }
            respostaUsuario = textarea.value.trim();
        } else if (exercicioAtual.tipo_exercicio === "fala") {
            // A resposta da fala é gerada pela função iniciarGravacao() e processada no feedback
            // Para este ponto, apenas simulamos que a resposta seria enviada
            respostaUsuario = "aguardando_processamento_fala"; 
        } else if (exercicioAtual.tipo_exercicio === "especial") {
            // Lógica de envio para exercícios especiais (pode requerer um endpoint diferente ou lógica mais complexa)
            alert("A funcionalidade de envio para exercícios especiais ainda está em desenvolvimento.");
            return;
        }

        // Validação básica da resposta
        if (!respostaUsuario) {
            alert("Por favor, selecione uma opção ou digite sua resposta.");
            return;
        }

        // Envia a resposta para o backend
        fetch(`../../admin/controller/processar_exercicio.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: respostaUsuario,
                tipo_exercicio: exercicioAtual.tipo_exercicio
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            exibirFeedback(data);
            document.getElementById("btnEnviarResposta").style.display = "none";
            document.getElementById("btnProximoExercicio").style.display = "block";
        })
        .catch(error => {
            console.error("Erro ao enviar resposta:", error);
            alert("Ocorreu um erro ao processar sua resposta. Tente novamente.");
        });
    };

    // Função para exibir feedback (correto/incorreto)
    window.exibirFeedback = function(data) {
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        
        // Cria o elemento de feedback dinamicamente
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = "feedbackExercicio";
        feedbackDiv.className = `feedback-container ${data.correto ? 'feedback-success' : 'feedback-error'} mt-3 p-3 rounded`;
        feedbackDiv.innerHTML = `<p class="mb-0"><strong>${data.correto ? 'Correto!' : 'Incorreto!'}</strong> ${data.explicacao}</p>`;
        
        conteudoExercicioDiv.appendChild(feedbackDiv);

        // Atualiza a aparência dos botões de múltipla escolha após a resposta
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            document.querySelectorAll(".btn-resposta").forEach(btn => {
                btn.disabled = true; // Desabilita todos os botões
                const altId = btn.dataset.id;
                
                // Parse do conteúdo do exercício para encontrar a alternativa correta
                let conteudo = {};
                try {
                    conteudo = JSON.parse(exercicioAtual.conteudo);
                } catch (e) { console.error("Erro ao parsear conteúdo:", e); }
                
                if (conteudo.alternativas) {
                    const alternativaCorreta = conteudo.alternativas.find(alt => alt.correta);
                    if (alternativaCorreta && altId === alternativaCorreta.id) {
                        btn.classList.add("correct"); // Marca a correta
                    } else if (btn.classList.contains("selected")) {
                        btn.classList.add("incorrect"); // Marca a incorreta se foi a selecionada
                    }
                }
            });
        }
    };

    // Função para avançar para o próximo exercício
    window.proximoExercicio = function() {
        exercicioIndex++;
        if (exercicioIndex < exerciciosLista.length) {
            carregarExercicio(exercicioIndex);
        } else {
            // Fim dos exercícios
            alert("Parabéns! Você completou todos os exercícios desta atividade.");
            modalExercicios.hide();
            modalAtividades.show(); // Volta para o modal de atividades
        }
    };

    // Função para voltar para atividades
    window.voltarParaAtividades = function() {
        modalExercicios.hide();
        modalAtividades.show();
    };

    // Função para abrir teoria da atividade (placeholder)
    window.abrirTeoriaAtividade = function(atividadeId, nomeAtividade) {
        alert(`Teoria da atividade "${nomeAtividade}" será implementada em breve.`);
    };

    // Função para carregar conteúdo especial (placeholder)
    function carregarConteudoEspecial(conteudo) {
        // Implementar lógica específica para exercícios especiais
        console.log("Carregando conteúdo especial:", conteudo);
    }

    // Função para iniciar gravação (placeholder para exercícios de fala)
    window.iniciarGravacao = function() {
        alert("Funcionalidade de gravação de fala será implementada em breve.");
    };

    // ==================== FUNCIONALIDADES DE FLASHCARDS ====================
    
    // Variáveis globais para flashcards
    let modalAdicionarPalavra = null;
    let palavrasCarregadas = [];
    
    // Inicialização dos modais de flashcards
    modalAdicionarPalavra = new bootstrap.Modal(document.getElementById('modalAdicionarPalavra'));
    
    // Carrega palavras do usuário ao inicializar
    if (typeof carregarPalavras === 'function') {
        carregarPalavras();
    }
    
    // Função para abrir modal de adicionar palavra
    window.abrirModalAdicionarPalavra = function() {
        // Limpa o formulário
        document.getElementById('formAdicionarPalavra').reset();
        
        // Define valores padrão baseados no usuário atual
        document.getElementById('palavraIdioma').value = '<?php echo htmlspecialchars($idioma_escolhido ?? "Ingles"); ?>';
        document.getElementById('palavraNivel').value = '<?php echo htmlspecialchars($nivel_usuario ?? "A1"); ?>';
        
        modalAdicionarPalavra.show();
    };
    
    // Função para adicionar nova palavra
    window.adicionarPalavra = function() {
        const form = document.getElementById('formAdicionarPalavra');
        const formData = new FormData(form);
        
        // Validação básica
        const palavraFrente = formData.get('palavra_frente').trim();
        const palavraVerso = formData.get('palavra_verso').trim();
        
        if (!palavraFrente || !palavraVerso) {
            alert('Por favor, preencha a palavra e sua tradução.');
            return;
        }
        
        // Adiciona dados do usuário atual
        formData.append('action', 'criar_flashcard_rapido');
        
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                modalAdicionarPalavra.hide();
                carregarPalavras(); // Recarrega a lista
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    };
    
    // Função para carregar palavras do usuário
    window.carregarPalavras = function() {
        const status = document.getElementById('filtroPalavrasStatus').value;
        const idioma = '<?php echo htmlspecialchars($idioma_escolhido ?? ""); ?>';
        const nivel = '<?php echo htmlspecialchars($nivel_usuario ?? ""); ?>';
        
        let url = `flashcard_controller.php?action=listar_palavras_usuario`;
        if (idioma) url += `&idioma=${encodeURIComponent(idioma)}`;
        if (nivel) url += `&nivel=${encodeURIComponent(nivel)}`;
        if (status !== '') url += `&aprendidas=${status}`;
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                palavrasCarregadas = data.palavras;
                exibirPalavras(data.palavras);
            } else {
                console.error('Erro ao carregar palavras:', data.message);
                exibirErroPalavras('Erro ao carregar palavras: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro de rede:', error);
            exibirErroPalavras('Erro de conexão. Tente novamente.');
        });
    };
    
    // Função para exibir palavras na interface
    window.exibirPalavras = function(palavras) {
        const container = document.getElementById('listaPalavras');
        
        if (palavras.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <div class="text-muted">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <h5>Nenhuma palavra encontrada</h5>
                        <p>Adicione suas primeiras palavras para começar a estudar!</p>
                    </div>
                </div>
            `;
            return;
        }
        
        let html = '';
        palavras.forEach(palavra => {
            const aprendida = palavra.aprendido == 1;
            const statusClass = aprendida ? 'success' : 'secondary';
            const statusIcon = aprendida ? 'check-circle' : 'clock';
            const statusText = aprendida ? 'Aprendida' : 'Estudando';
            
            html += `
                <div class="col-md-6 col-lg-4 mb-3 palavra-item" data-palavra="${palavra.frente.toLowerCase()}" data-traducao="${palavra.verso.toLowerCase()}">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">${palavra.frente}</h6>
                                <span class="badge bg-${statusClass}">
                                    <i class="fas fa-${statusIcon} me-1"></i>${statusText}
                                </span>
                            </div>
                            <p class="card-text text-muted mb-2">${palavra.verso}</p>
                            <small class="text-muted">
                                <i class="fas fa-layer-group me-1"></i>${palavra.nome_deck}
                            </small>
                            <div class="mt-2">
                                ${aprendida ? 
                                    `<button class="btn btn-outline-secondary btn-sm" onclick="alterarStatusPalavra(${palavra.id}, false)">
                                        <i class="fas fa-undo me-1"></i>Estudar Novamente
                                    </button>` :
                                    `<button class="btn btn-outline-success btn-sm" onclick="alterarStatusPalavra(${palavra.id}, true)">
                                        <i class="fas fa-check me-1"></i>Marcar como Aprendida
                                    </button>`
                                }
                                <button class="btn btn-outline-danger btn-sm ms-1" onclick="excluirPalavra(${palavra.id})">
                                    <i class="fas fa-trash me-1"></i>Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    };
    
    // Função para filtrar palavras localmente
    window.filtrarPalavrasLocal = function() {
        const busca = document.getElementById('filtroPalavrasBusca').value.toLowerCase();
        const items = document.querySelectorAll('.palavra-item');
        
        items.forEach(item => {
            const palavra = item.dataset.palavra;
            const traducao = item.dataset.traducao;
            
            if (palavra.includes(busca) || traducao.includes(busca)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    };
    
    // Função para alterar status de palavra (aprendida/não aprendida)
    window.alterarStatusPalavra = function(idFlashcard, marcarComoAprendida) {
        const action = marcarComoAprendida ? 'marcar_como_aprendido' : 'desmarcar_como_aprendido';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('id_flashcard', idFlashcard);
        
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarPalavras(); // Recarrega a lista
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    };
    
    // Função para excluir palavra
    window.excluirPalavra = function(idFlashcard) {
        if (!confirm('Tem certeza que deseja excluir esta palavra?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'excluir_flashcard');
        formData.append('id_flashcard', idFlashcard);
        
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarPalavras(); // Recarrega a lista
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    };
    
    // Função para exibir erro ao carregar palavras
    window.exibirErroPalavras = function(mensagem) {
        const container = document.getElementById('listaPalavras');
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${mensagem}
                </div>
            </div>
        `;
    };

});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>