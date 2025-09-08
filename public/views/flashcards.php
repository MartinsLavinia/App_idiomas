<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Busca o idioma e nível atual do usuário
$sql_progresso = "SELECT idioma, nivel FROM progresso_usuario WHERE id_usuario = ? ORDER BY id DESC LIMIT 1";
$stmt_progresso = $conn->prepare($sql_progresso);
$stmt_progresso->bind_param("i", $id_usuario);
$stmt_progresso->execute();
$resultado = $stmt_progresso->get_result()->fetch_assoc();
$stmt_progresso->close();

$idioma_atual = $resultado["idioma"] ?? 'Ingles';
$nivel_atual = $resultado["nivel"] ?? 'A1';

// Fecha a conexão
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Cards - Site de Idiomas</title>
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

        /* Estilos Gerais */
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

        /* Cards */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        /* Deck Cards */
        .deck-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .deck-card:hover {
            border-color: var(--amarelo-detalhe);
            transform: translateY(-8px) scale(1.02);
        }

        .deck-stats {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--amarelo-detalhe);
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Botões */
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

        .btn-warning {
            background-color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            color: var(--preto-texto);
            font-weight: 600;
        }

        /* Filtros */
        .filter-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
        }

        .spinner-border {
            color: var(--roxo-principal);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--cinza-medio);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--cinza-medio);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .filter-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="painel.php">
                <i class="fas fa-arrow-left me-2"></i>Site de Idiomas
            </a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="logout.php" class="btn btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-layer-group me-2 text-warning"></i>
                            Flash Cards
                        </h1>
                        <p class="text-muted mb-0">Estude com flashcards personalizados</p>
                    </div>
                    <div>
                        <button class="btn btn-primary me-2" onclick="abrirModalCriarDeck()">
                            <i class="fas fa-plus me-2"></i>Novo Deck
                        </button>
                        <button class="btn btn-warning" onclick="estudarFlashcards()">
                            <i class="fas fa-play me-2"></i>Estudar Agora
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="filtroIdioma" class="form-label">Idioma</label>
                    <select class="form-select" id="filtroIdioma" onchange="aplicarFiltros()">
                        <option value="">Todos os idiomas</option>
                        <option value="Ingles" <?php echo $idioma_atual === 'Ingles' ? 'selected' : ''; ?>>Inglês</option>
                        <option value="Japones" <?php echo $idioma_atual === 'Japones' ? 'selected' : ''; ?>>Japonês</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtroNivel" class="form-label">Nível</label>
                    <select class="form-select" id="filtroNivel" onchange="aplicarFiltros()">
                        <option value="">Todos os níveis</option>
                        <option value="A1" <?php echo $nivel_atual === 'A1' ? 'selected' : ''; ?>>A1</option>
                        <option value="A2" <?php echo $nivel_atual === 'A2' ? 'selected' : ''; ?>>A2</option>
                        <option value="B1" <?php echo $nivel_atual === 'B1' ? 'selected' : ''; ?>>B1</option>
                        <option value="B2" <?php echo $nivel_atual === 'B2' ? 'selected' : ''; ?>>B2</option>
                        <option value="C1" <?php echo $nivel_atual === 'C1' ? 'selected' : ''; ?>>C1</option>
                        <option value="C2" <?php echo $nivel_atual === 'C2' ? 'selected' : ''; ?>>C2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tipoDecks" class="form-label">Tipo</label>
                    <select class="form-select" id="tipoDecks" onchange="aplicarFiltros()">
                        <option value="meus">Meus Decks</option>
                        <option value="publicos">Decks Públicos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" onclick="limparFiltros()">
                        <i class="fas fa-times me-2"></i>Limpar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de Decks -->
        <div id="listaDecks" class="row">
            <div class="loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando seus decks...</p>
            </div>
        </div>
    </div>

    <!-- Modal Criar/Editar Deck -->
    <div class="modal fade" id="modalDeck" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalDeck">Novo Deck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formDeck">
                        <input type="hidden" id="deckId" name="id_deck">
                        <div class="mb-3">
                            <label for="deckNome" class="form-label">Nome do Deck *</label>
                            <input type="text" class="form-control" id="deckNome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="deckDescricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="deckDescricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckIdioma" class="form-label">Idioma *</label>
                                    <select class="form-select" id="deckIdioma" name="idioma" required>
                                        <option value="">Selecione...</option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckNivel" class="form-label">Nível *</label>
                                    <select class="form-select" id="deckNivel" name="nivel" required>
                                        <option value="">Selecione...</option>
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="deckPublico" name="publico">
                                <label class="form-check-label" for="deckPublico">
                                    Tornar este deck público (outros usuários poderão estudá-lo)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarDeck()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="flashcard_script.js"></script>
</body>
</html>
