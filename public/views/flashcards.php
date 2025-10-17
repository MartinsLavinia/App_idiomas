<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: /../../index.php");
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
    <link href="painel.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- link direto dos icones -->
    <style>
        /* Paleta de Cores - MESMAS DO ADMIN */
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
            margin: 0;
            padding: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* SIDEBAR FIXO */
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

        .sidebar .profile i {
            font-size: 4rem;
            color: var(--amarelo-detalhe);
            margin-bottom: 10px;
        }

        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 0;
            color: var(--branco);
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
            text-decoration: none;
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
            width: 20px; /* Alinhamento dos ícones */
            text-align: center;
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
            background-color: var(--branco);
            border: 1px solid var(--cinza-medio);
            border-top: 4px solid transparent; /* Espaço para o gradiente no hover */
            border-radius: 1rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
        }

        .deck-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top-color: var(--roxo-principal);
        }

        .deck-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d; /* Cinza mais escuro para melhor leitura */
        }

        .deck-info-item i {
            color: var(--amarelo-detalhe);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .deck-info-item.public {
            color: #198754; /* Verde para público */
        }

        .deck-stats {
            border-top: 1px solid var(--cinza-medio);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--roxo-principal);
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            color: var(--cinza-escuro);
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

        .btn-open-deck {
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
            border: none;
            color: var(--branco);
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.2);
        }

        .btn-open-deck:hover {
            background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
            color: var(--branco);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.3);
        }

        .btn-open-deck::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: skewX(-25deg);
            transition: left 0.8s ease;
        }

        .btn-warning {
            background-color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            color: var(--preto-texto);
            font-weight: 600;
        }

        /* Botão de Call-to-Action para o estado vazio */
        .btn-cta-empty {
            background: linear-gradient(135deg, var(--amarelo-detalhe) 0%, #f39c12 100%);
            color: var(--preto-texto);
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            animation: pulse-glow 2s infinite;
        }

        .btn-cta-empty:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .btn-cta-empty i {
            font-size: 1.3rem; /* Aumenta significativamente o tamanho do ícone */
            font-weight: 700; /* Deixa o ícone mais "grosso" */
            margin-right: 0.5rem; /* Garante um bom espaçamento */
            transition: transform 0.3s ease;
        }

        .btn-cta-empty:hover i {
            transform: rotate(180deg) scale(1.15); /* Animação mais pronunciada no hover */
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 6px 25px rgba(255, 215, 0, 0.5); }
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f2e9f9; /* Roxo bem claro */
            color: var(--roxo-escuro);
            transform: translateX(4px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fceaea; /* Vermelho bem claro */
            color: #b02a37;
            transform: translateX(4px);
        }

        /* Botão Limpar Filtros */
        .btn-limpar-filtros {
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            border: 1px solid var(--cinza-medio);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-limpar-filtros:hover {
            background-color: var(--cinza-medio);
            border-color: #adb5bd;
            transform: translateY(-2px);
        }

        /* Botão de opções */
        .btn-outline-secondary {
            border: 1px solid var(--cinza-medio);
            color: var(--preto-texto);
            padding: 0.25rem 0.5rem;
        }

        .btn-outline-secondary:hover {
            background-color: var(--cinza-medio);
            border-color: var(--cinza-medio);
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
            background-color: var(--branco); /* Fundo branco */
            color: var(--preto-texto); /* Texto preto */
            border-radius: 1rem; /* Bordas arredondadas */
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--roxo-principal); /* Ícone roxo para destaque */
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

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* Estilos para o Modal de Deck */
        #modalDeck .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        #modalDeck .modal-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
        }

        #modalDeck .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        #modalDeck .modal-body {
            padding: 2rem;
        }

        #modalDeck .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
            padding: 1.5rem;
        }

        #modalDeck .form-label {
            font-weight: 600;
            color: var(--roxo-escuro);
        }

        #modalDeck .form-label i {
            color: var(--amarelo-detalhe);
            margin-right: 0.5rem;
        }

        #modalDeck .form-check-input:checked {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
        }

        #modalDeck .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--cinza-medio);
            color: var(--preto-texto);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #modalDeck .btn-secondary:hover {
            background-color: var(--cinza-claro);
            border-color: #adb5bd;
            transform: scale(1.05); /* Efeito de zoom */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        /* Estilos para o Modal de Confirmação de Exclusão */
        #modalConfirmarExclusao .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        #modalConfirmarExclusao .modal-header {
            background: var(--branco); /* Remove o fundo vermelho */
            color: #c82333; /* Deixa o texto vermelho */
            border-bottom: 1px solid var(--cinza-medio); /* Adiciona uma linha sutil */
            border-radius: 1rem 1rem 0 0;
            padding: 1.25rem 1.5rem;
        }

        #modalConfirmarExclusao .modal-header .btn-close {
            filter: none; /* Reseta o filtro do botão de fechar */
        }

        #modalConfirmarExclusao .modal-body {
            padding: 2.5rem;
            text-align: center;
        }

        #modalConfirmarExclusao .modal-body .icon-warning {
            font-size: 3.5rem;
            color: #e55353;
            margin-bottom: 1.5rem;
            display: block;
            animation: pulse-warning 1.5s infinite;
        }

        @keyframes pulse-warning {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        #modalConfirmarExclusao .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
            padding: 1.5rem;
            justify-content: center;
            gap: 1rem;
        }

        #modalConfirmarExclusao .modal-footer .btn-secondary {
            background: transparent;
            border: 2px solid #adb5bd;
            color: #495057;
            padding: 0.75rem 1.5rem; /* Garante o mesmo padding */
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #modalConfirmarExclusao .modal-footer .btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
            transform: translateY(-2px);
        }

        #modalConfirmarExclusao .modal-footer .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: var(--branco);
            padding: 0.75rem 1.5rem; /* Garante o mesmo padding */
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transform: skewX(-25deg);
            transition: left 0.8s ease;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger:hover::before {
            left: 150%;
        }

        #modalConfirmarExclusao .modal-footer .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71d2a);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: var(--branco);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5><?php echo htmlspecialchars($nome_usuario); ?></h5>
            <small>Usuário</small>
        </div>

        <div class="list-group">
            <a href="painel.php" class="list-group-item">
                <i class="fas fa-home"></i> Início
            </a>
            <a href="flashcards.php" class="list-group-item active">
                <i class="fas fa-layer-group"></i> Flash Cards
            </a>
            <a href="../../logout.php" class="list-group-item mt-auto">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-11">
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
                        <div class="row g-3 align-items-end">
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
                                <button class="btn btn-limpar-filtros w-100" onclick="limparFiltros()">
                                    <i class="fas fa-times me-2"></i>Limpar Filtros
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Decks -->
                    <div id="listaDecks" class="row">
                        <!-- O conteúdo dos decks será carregado aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal Criar/Editar Deck -->
    <div class="modal fade" id="modalDeck" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalDeck"><i class="fas fa-layer-group me-2"></i>Novo Deck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formDeck">
                        <input type="hidden" id="deckId" name="id_deck">
                        <div class="mb-3">
                            <label for="deckNome" class="form-label"><i class="fas fa-heading"></i>Nome do Deck *</label>
                            <input type="text" class="form-control" id="deckNome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="deckDescricao" class="form-label"><i class="fas fa-align-left"></i>Descrição</label>
                            <textarea class="form-control" id="deckDescricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckIdioma" class="form-label"><i class="fas fa-language"></i>Idioma *</label>
                                    <select class="form-select" id="deckIdioma" name="idioma" required>
                                        <option value="">Selecione...</option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                        <!-- Adicionar mais idiomas aqui se necessário -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deckNivel" class="form-label"><i class="fas fa-signal"></i>Nível *</label>
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
                        <div class="mb-3 mt-2">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="deckPublico" name="publico">
                                <label class="form-check-label" for="deckPublico">
                                    Tornar este deck público (outros usuários poderão estudá-lo)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="salvarDeck()">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalExclusao"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="fas fa-exclamation-circle icon-warning"></i>
                    <p id="mensagemModalExclusao" class="lead">Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="flashcard_script.js"></script>
</body>
</html>