<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: /../../index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$id_deck = intval($_GET['id'] ?? 0);

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Buscar foto do usuário
$sql_foto_usuario = "SELECT foto_perfil FROM usuarios WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto_usuario);
$stmt_foto->bind_param("i", $id_usuario);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result()->fetch_assoc();
$stmt_foto->close();

$foto_usuario = $resultado_foto['foto_perfil'] ?? null;

// Feche a conexão
$database->closeConnection();

if (!$id_deck) {
    header("Location: flashcards.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Deck - Site de Idiomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="painel.css" rel="stylesheet">
    <!-- Estilos específicos para esta página -->
    <style>
        /* Paleta de Cores */
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --cinza-escuro: #6c757d;
            --cinza-medio: #dee2e6;
        }

        /* Estilos Gerais do Corpo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
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

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* Cabeçalho da página */
        .page-header .card-header {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        /* Estilo unificado do Flashcard (baseado em flashcard_estudo.php) */
        .flashcard-preview {
            perspective: 1000px;
            height: 200px;
            margin-bottom: 1rem;
        }
        
        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard-preview.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }

        .flashcard-side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--cinza-medio);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: var(--branco);
        }

        .flashcard-front {
            /* Estilo da frente do card */
        }

        .flashcard-back {
            transform: rotateY(180deg);
        }

        .flashcard-header {
            padding: 0.75rem 1rem;
            color: var(--branco);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .flashcard-front .flashcard-header { background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)); }
        .flashcard-back .flashcard-header { background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12); color: var(--preto-texto); }

        .flashcard-content {
            flex-grow: 1;
            align-items: center;
            justify-content: center;
            display: flex;
            text-align: center;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Flashcard List Item */
        .flashcard-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .flashcard-item .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .flashcard-item .dropdown-item:hover {
            background-color: #f2e9f9;
            color: var(--roxo-escuro);
        }

        .flashcard-item .dropdown-item.text-danger:hover {
            background-color: #fceaea;
            color: #b02a37;
        }

        /* Botões */
        .btn-action {
            padding: 0.65rem 1.25rem; /* Padding ajustado */
            border-radius: 12px; /* Bordas menos arredondadas */
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-claro));
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, var(--roxo-claro), var(--roxo-principal));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
            color: var(--branco);
        }

        .btn-action-warning {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-action-warning:hover {
            background: linear-gradient(135deg, #f39c12, var(--amarelo-detalhe));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            color: var(--preto-texto);
        }

        .btn-action-danger {
            background: linear-gradient(135deg, #e55353, #c82333);
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-action-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71d2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: var(--branco);
        }

        /* Modal Flashcard */
        #modalFlashcard .modal-content {
            border-radius: 1rem;
            border: none;
        }
        #modalFlashcard .modal-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
        }
        #modalFlashcard .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        #modalFlashcard .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
        }

        #modalFlashcard .form-label {
            font-weight: 600;
            color: var(--roxo-escuro);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #modalFlashcard .form-label i {
            color: var(--amarelo-detalhe);
            font-size: 1.1rem;
        }

        #modalFlashcard .form-control,
        #modalFlashcard .form-select {
            border-radius: 8px;
            border: 1px solid var(--cinza-medio);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        #modalFlashcard .form-control:focus,
        #modalFlashcard .form-select:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.15);
        }

        #modalFlashcard .modal-footer .btn-secondary {
            background-color: transparent;
            border: 2px solid #adb5bd;
            color: #495057;
            font-weight: 600;
        }

        #modalFlashcard .modal-footer .btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
        }

        #modalFlashcard .modal-footer .btn-primary {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-claro));
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
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
            padding: 4rem;
            background-color: var(--branco);
            border-radius: 1rem;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--roxo-principal);
        }

        .empty-state .btn-cta-empty { /* Estilo igual ao de flashcards.php */
            background: linear-gradient(135deg, var(--amarelo-detalhe) 0%, #f39c12 100%);
            color: var(--preto-texto);
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 20px;
            max-width: 500px; /* Limita a largura máxima */
            margin: 1rem auto 0; /* Centraliza o botão */
            display: block; /* Garante que margin: auto funcione */
            animation: pulse-glow 2s infinite;
        }

        .empty-state .btn-cta-empty:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 6px 25px rgba(255, 215, 0, 0.5); }
        }

        /* Estilos para o Modal de Confirmação de Exclusão */
        #modalConfirmarExclusao .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        #modalConfirmarExclusao .modal-header {
            background: linear-gradient(135deg, #e55353, #c82333);
            color: var(--branco);
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
        }

        #modalConfirmarExclusao .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
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
        }

        #modalConfirmarExclusao .modal-footer {
            background-color: var(--cinza-claro);
            border-top: none;
            border-radius: 0 0 1rem 1rem;
            justify-content: center;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <?php if ($foto_usuario): ?>
                <img src="../../<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto de perfil" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid var(--amarelo-detalhe);">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
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
            <!-- Cabeçalho e Ações -->
            <div class="row mb-4 align-items-center">
                <div class="col-lg-6">
                    <h1 class="mb-2">
                        <a href="flashcards.php" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
                        Gerenciar Deck
                    </h1>
                    <p class="text-muted mb-0">Adicione, edite e estude os flashcards do seu deck.</p>
                </div>
                <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                    <button class="btn btn-action btn-action-primary me-2" onclick="abrirModalFlashcard()">
                        <i class="fas fa-plus me-2"></i>Novo Flashcard
                    </button>
                    <button class="btn btn-action btn-action-warning me-2" onclick="estudarDeck()">
                        <i class="fas fa-play me-2"></i>Estudar Deck
                    </button>
                    <button class="btn btn-action btn-action-danger" onclick="excluirDeckAtual()">
                        <i class="fas fa-trash me-2"></i>Excluir Deck
                    </button>
                </div>
            </div>

            <!-- Informações do Deck -->
            <div id="infoDeck" class="row mb-4">
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando informações do deck...</p>
                </div>
            </div>

            <hr class="my-4">

            <h3 class="mb-4"><i class="fas fa-clone me-2 text-primary"></i>Flashcards no Deck</h3>
            <!-- Lista de Flashcards -->
            <div id="listaFlashcards" class="row">
                <!-- O conteúdo dos flashcards será carregado aqui via JavaScript -->
            </div>

            <!-- Container da Paginação -->
            <div id="paginationContainer" class="d-flex justify-content-center mt-4">
                <!-- A paginação será injetada aqui pelo JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Criar/Editar Flashcard -->
    <div class="modal fade" id="modalFlashcard" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header page-header">
                    <h5 class="modal-title" id="tituloModalFlashcard">Novo Flashcard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form id="formFlashcard">
                                <input type="hidden" id="flashcardId" name="id_flashcard">
                                <input type="hidden" id="flashcardDeckId" name="id_deck" value="<?php echo $id_deck; ?>">
                                
                                <div class="mb-3">
                                    <label for="flashcardFrente" class="form-label"><i class="fas fa-align-left"></i>Frente do Card *</label>
                                    <textarea class="form-control" id="flashcardFrente" name="frente" rows="3" required placeholder="Digite o conteúdo da frente do flashcard"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="flashcardVerso" class="form-label"><i class="fas fa-align-right"></i>Verso do Card *</label>
                                    <textarea class="form-control" id="flashcardVerso" name="verso" rows="3" required placeholder="Digite o conteúdo do verso do flashcard"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="flashcardDica" class="form-label"><i class="fas fa-lightbulb"></i>Dica (opcional)</label>
                                    <textarea class="form-control" id="flashcardDica" name="dica" rows="2" placeholder="Digite uma dica para ajudar na memorização"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="flashcardDificuldade" class="form-label"><i class="fas fa-tachometer-alt"></i>Dificuldade</label>
                                            <select class="form-select" id="flashcardDificuldade" name="dificuldade">
                                                <option value="facil">Fácil</option>
                                                <option value="medio" selected>Médio</option>
                                                <option value="dificil">Difícil</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="flashcardOrdem" class="form-label"><i class="fas fa-sort-numeric-up"></i>Ordem</label>
                                            <input type="number" class="form-control" id="flashcardOrdem" name="ordem_no_deck" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Campos para imagens e áudios (futuro) -->
                                <div class="mb-3">
                                    <label class="form-label">Mídia (em desenvolvimento)</label>
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Suporte para imagens e áudios será adicionado em breve.
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Preview do Flashcard</label>
                            <div class="flashcard-preview" id="flashcardPreview" onclick="virarPreview()">
                                <div class="flashcard-inner" id="previewInner">
                                    <div class="flashcard-side flashcard-front">
                                        <div class="flashcard-header" id="previewHeaderFrente">
                                            <span>Pergunta</span>
                                        </div>
                                        <div class="flashcard-content" id="previewFrente">Digite o conteúdo da frente</div>
                                    </div>
                                    <div class="flashcard-side flashcard-back">
                                        <div class="flashcard-header" id="previewHeaderVerso">
                                            <span>Resposta</span>
                                        </div>
                                        <div class="flashcard-content" id="previewVerso">Digite o conteúdo do verso</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">Clique no card para virar</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="salvarFlashcard()">
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
                    <p id="mensagemModalExclusao">Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.</p>
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
    <script>
        // Variáveis globais
        const idDeck = <?php echo $id_deck; ?>;
        let modalFlashcard = null;
        let modalConfirmarExclusao = null;
        let deckAtual = null;
        let flashcardAtual = null;
        let allFlashcards = [];
        let currentPage = 1;
        const cardsPerPage = 6; // Define quantos cards por página

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            modalFlashcard = new bootstrap.Modal(document.getElementById('modalFlashcard'));
            modalConfirmarExclusao = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
            carregarDeck();
            carregarFlashcards();
            
            // Event listeners para preview
            document.getElementById('flashcardFrente').addEventListener('input', atualizarPreview);
            document.getElementById('flashcardVerso').addEventListener('input', atualizarPreview);
            document.getElementById('flashcardDificuldade').addEventListener('change', atualizarPreview);
        });

        // Carrega informações do deck
        function carregarDeck() {
            fetch(`flashcard_controller.php?action=obter_deck&id_deck=${idDeck}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deckAtual = data.deck;
                        exibirInfoDeck(data.deck);
                    } else {
                        console.error('Erro ao carregar deck:', data.message);
                        exibirErroDeck('Erro ao carregar informações do deck: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                    exibirErroDeck('Erro de conexão. Tente novamente.');
                });
        }

        // Exibe informações do deck
        function exibirInfoDeck(deck) {
            const container = document.getElementById('infoDeck');
            container.innerHTML = `
                <div class="col-12 page-header">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-1">${deck.nome}</h2>
                                    <p class="mb-0 opacity-75">${deck.descricao || 'Sem descrição'}</p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark me-2">${deck.idioma}</span>
                                    <span class="badge bg-light text-dark me-2">${deck.nivel}</span>
                                    ${deck.publico == 1 ? '<span class="badge bg-success">Público</span>' : '<span class="badge bg-secondary">Privado</span>'}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3 col-6 mb-3 mb-md-0">
                                    <div class="h4 text-primary">${deck.total_flashcards || 0}</div>
                                    <div class="text-muted">Total de Cards</div>
                                </div>
                                <div class="col-md-3 col-6 mb-3 mb-md-0">
                                    <div class="h4 text-success">${deck.flashcards_estudados || 0}</div>
                                    <div class="text-muted">Cards Estudados</div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="h4 text-warning">${deck.total_flashcards > 0 ? Math.round(((deck.flashcards_estudados || 0) / deck.total_flashcards) * 100) : 0}%</div>
                                    <div class="text-muted">Progresso</div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="h4 text-info">${deck.nome_criador || 'Você'}</div>
                                    <div class="text-muted">Criador</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Exibe erro do deck
        function exibirErroDeck(mensagem) {
            const container = document.getElementById('infoDeck');
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${mensagem}
                    </div>
                </div>
            `;
        }

        // Excluir deck atual
        function excluirDeckAtual() {
            if (!deckAtual) return;

            // Prepara e exibe o modal de confirmação
            const mensagem = `Tem certeza que deseja excluir o deck "<strong>${deckAtual.nome}</strong>"? Esta ação não pode ser desfeita e todos os flashcards serão perdidos.`;
            document.getElementById('mensagemModalExclusao').innerHTML = mensagem;

            const btnConfirmar = document.getElementById('btnConfirmarExclusao');

            // Remove listeners antigos para evitar múltiplas execuções
            const novoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(novoBtn, btnConfirmar);

            novoBtn.addEventListener('click', function() {
                const formData = new FormData();
                formData.append('action', 'excluir_deck');
                formData.append('id_deck', idDeck);

                fetch('flashcard_controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    modalConfirmarExclusao.hide();
                    if (data.success) {
                        alert('Deck excluído com sucesso!'); // Pode ser substituído por um toast
                        window.location.href = 'flashcards.php';
                    } else {
                        alert('Erro ao excluir deck: ' + data.message);
                    }
                })
                .catch(error => {
                    modalConfirmarExclusao.hide();
                    console.error('Erro:', error);
                    alert('Erro de conexão ao excluir deck.');
                });
            });

            modalConfirmarExclusao.show();
        }

        // Carrega flashcards do deck
        function carregarFlashcards() {
            fetch(`flashcard_controller.php?action=listar_flashcards&id_deck=${idDeck}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allFlashcards = data.flashcards;
                        currentPage = 1;
                        exibirFlashcards();
                        renderPagination();
                    } else {
                        console.error('Erro ao carregar flashcards:', data.message);
                        exibirErroFlashcards('Erro ao carregar flashcards: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                    exibirErroFlashcards('Erro de conexão. Tente novamente.');
                });
        }

        // Exibe flashcards
        function exibirFlashcards() {
            const container = document.getElementById('listaFlashcards');
            container.innerHTML = '';

            const startIndex = (currentPage - 1) * cardsPerPage;
            const endIndex = startIndex + cardsPerPage;
            const paginatedFlashcards = allFlashcards.slice(startIndex, endIndex);
            
            if (allFlashcards.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state card card-body">
                            <i class="fas fa-layer-group"></i>
                            <h3>Nenhum flashcard encontrado</h3>
                            <p>Adicione o primeiro flashcard a este deck para começar.</p>
                            <button class="btn btn-cta-empty" onclick="abrirModalFlashcard()">
                                <i class="fas fa-plus me-2"></i>Adicionar Primeiro Flashcard
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            paginatedFlashcards.forEach((flashcard, index) => {
                const dificuldadeClass = {
                    'facil': 'success',
                    'medio': 'warning',
                    'dificil': 'danger'
                };
                
                const dificuldadeTexto = {
                    'facil': 'Fácil',
                    'medio': 'Médio',
                    'dificil': 'Difícil'
                };

                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 flashcard-item">
                            <div class="card-body d-flex flex-column">
                                <div class="flashcard-preview mb-3" onclick="virarFlashcard(this, event)">
                                <div class="flashcard-inner">
                                    <div class="flashcard-side flashcard-front">
                                        <div class="flashcard-header">
                                            <span>Pergunta</span>
                                            <span class="badge bg-white bg-opacity-25 text-white">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                                        </div>
                                        <div class="flashcard-content">
                                            <div>${flashcard.frente}</div>
                                        </div>
                                    </div>
                                    <div class="flashcard-side flashcard-back">
                                        <div class="flashcard-header">
                                            <span>Resposta</span>
                                            <span class="badge bg-black bg-opacity-25 text-black">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                                        </div>
                                        <div class="flashcard-content">
                                            <div>${flashcard.verso}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <small class="text-muted">Card #${startIndex + index + 1}</small>
                                <div class="d-flex align-items-center">
                                    ${flashcard.acertos !== undefined ? `
                                        <small class="text-muted me-2">
                                            <i class="fas fa-check text-success me-1"></i>${flashcard.acertos || 0}
                                            <i class="fas fa-times text-danger ms-2 me-1"></i>${flashcard.erros || 0}
                                        </small>
                                    ` : ''}
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" onclick="event.stopPropagation()">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="editarFlashcard(${flashcard.id})">
                                                <i class="fas fa-edit me-2"></i>Editar
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="excluirFlashcard(${flashcard.id})">
                                                <i class="fas fa-trash me-2"></i>Excluir
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Exibe erro dos flashcards
        function exibirErroFlashcards(mensagem) {
            const container = document.getElementById('listaFlashcards');
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${mensagem}
                    </div>
                </div>
            `;
        }

        // Vira flashcard na lista
        function virarFlashcard(element, event) {
            // Impede que o clique no dropdown vire o card
            if (event.target.closest('.dropdown')) return;
            
            element.classList.toggle('flipped');
        }

        // Vira preview no modal
        function virarPreview() {
            document.getElementById('flashcardPreview').classList.toggle('flipped');
        }

        // Atualiza preview no modal
        function atualizarPreview() {
            const frente = document.getElementById('flashcardFrente').value || 'Digite o conteúdo da frente';
            const verso = document.getElementById('flashcardVerso').value || 'Digite o conteúdo do verso';
            const dificuldade = document.getElementById('flashcardDificuldade').value;
            const dificuldadeTexto = {
                'facil': 'Fácil',
                'medio': 'Médio',
                'dificil': 'Difícil'
            };
            
            document.getElementById('previewFrente').innerHTML = `<div>${frente}</div>`;
            document.getElementById('previewVerso').innerHTML = `<div>${verso}</div>`;

            // Atualiza o header do preview com a dificuldade
            const headerFrente = document.getElementById('previewHeaderFrente');
            headerFrente.innerHTML = `
                <span>Pergunta</span>
                <span class="badge bg-white bg-opacity-25 text-white">${dificuldadeTexto[dificuldade] || 'Médio'}</span>
            `;
            const headerVerso = document.getElementById('previewHeaderVerso');
            headerVerso.innerHTML = `
                <span>Resposta</span>
                <span class="badge bg-black bg-opacity-25 text-black">${dificuldadeTexto[dificuldade] || 'Médio'}</span>
            `;
        }

        // Abre modal para criar flashcard
        function abrirModalFlashcard() {
            flashcardAtual = null;
            document.getElementById('tituloModalFlashcard').textContent = 'Novo Flashcard';
            document.getElementById('formFlashcard').reset();
            document.getElementById('flashcardId').value = '';
            document.getElementById('flashcardDeckId').value = idDeck;
            
            // Reset preview
            document.getElementById('flashcardPreview').classList.remove('flipped');
            atualizarPreview();
            
            modalFlashcard.show();
        }

        // Renderiza a paginação
        function renderPagination() {
            const paginationContainer = document.getElementById('paginationContainer');
            paginationContainer.innerHTML = '';
            const pageCount = Math.ceil(allFlashcards.length / cardsPerPage);

            if (pageCount <= 1) return;

            let paginationHTML = '<nav><ul class="pagination">';

            // Botão Anterior
            paginationHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Anterior</a></li>`;

            // Botões de Página
            for (let i = 1; i <= pageCount; i++) {
                paginationHTML += `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
            }

            // Botão Próximo
            paginationHTML += `<li class="page-item ${currentPage === pageCount ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Próximo</a></li>`;

            paginationHTML += '</ul></nav>';
            paginationContainer.innerHTML = paginationHTML;
        }

        // Muda de página
        function changePage(page) {
            const pageCount = Math.ceil(allFlashcards.length / cardsPerPage);
            if (page < 1 || page > pageCount) return;

            currentPage = page;
            exibirFlashcards();
            renderPagination();
            window.scrollTo(0, 0); // Rola para o topo da página
        }

        // Edita flashcard
        function editarFlashcard(idFlashcard) {
            fetch(`flashcard_controller.php?action=obter_flashcard&id_flashcard=${idFlashcard}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const flashcard = data.flashcard;
                        flashcardAtual = flashcard;
                        
                        document.getElementById('tituloModalFlashcard').textContent = 'Editar Flashcard';
                        document.getElementById('flashcardId').value = flashcard.id;
                        document.getElementById('flashcardFrente').value = flashcard.frente;
                        document.getElementById('flashcardVerso').value = flashcard.verso;
                        document.getElementById('flashcardDica').value = flashcard.dica || '';
                        document.getElementById('flashcardDificuldade').value = flashcard.dificuldade;
                        document.getElementById('flashcardOrdem').value = flashcard.ordem_no_deck;
                        
                        // Reset preview
                        document.getElementById('flashcardPreview').classList.remove('flipped');
                        atualizarPreview();
                        
                        modalFlashcard.show();
                    } else {
                        alert('Erro ao carregar dados do flashcard: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro de conexão ao carregar flashcard.');
                });
        }

        // Salva flashcard (criar ou editar)
        function salvarFlashcard() {
            const form = document.getElementById('formFlashcard');
            const formData = new FormData(form);
            
            const isEdicao = document.getElementById('flashcardId').value !== '';
            const action = isEdicao ? 'atualizar_flashcard' : 'criar_flashcard';
            
            formData.append('action', action);
            
            fetch('flashcard_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalFlashcard.hide();
                    carregarFlashcards();
                    carregarDeck(); // Atualiza estatísticas
                } else {
                    alert('Erro ao salvar flashcard: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro de conexão ao salvar flashcard.');
            });
        }

        // Exclui flashcard
        function excluirFlashcard(idFlashcard) {
            if (!confirm('Tem certeza que deseja excluir este flashcard? Esta ação não pode ser desfeita.')) {
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
                    carregarFlashcards();
                    carregarDeck(); // Atualiza estatísticas
                } else {
                    alert('Erro ao excluir flashcard: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro de conexão ao excluir flashcard.');
            });
        }

        // Estuda o deck
        function estudarDeck() {
            window.location.href = `flashcard_estudo.php?deck=${idDeck}`;
        }
    </script>
</body>
</html>