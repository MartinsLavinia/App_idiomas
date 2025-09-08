?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$id_deck = intval($_GET['id'] ?? 0);

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
        }

        .card-header {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        /* Flashcard Preview */
        .flashcard-preview {
            perspective: 1000px;
            height: 200px;
            margin-bottom: 1rem;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard-preview.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .flashcard-front {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
        }

        .flashcard-back {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #ffed4e);
            color: var(--preto-texto);
            transform: rotateY(180deg);
        }

        /* Flashcard List Item */
        .flashcard-item {
            border: 1px solid var(--cinza-medio);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .flashcard-item:hover {
            border-color: var(--roxo-principal);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Botões */
        .btn-primary {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: var(--roxo-escuro);
            border-color: var(--roxo-escuro);
        }

        .btn-warning {
            background-color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            color: var(--preto-texto);
            font-weight: 600;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="flashcards.php">
                <i class="fas fa-arrow-left me-2"></i>Flash Cards
            </a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="//logout.php" class="btn btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Informações do Deck -->
        <div id="infoDeck" class="row mb-4">
            <div class="loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando informações do deck...</p>
            </div>
        </div>

        <!-- Ações -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3>Flashcards</h3>
                    <div>
                        <button class="btn btn-primary me-2" onclick="abrirModalFlashcard()">
                            <i class="fas fa-plus me-2"></i>Novo Flashcard
                        </button>
                        <button class="btn btn-warning" onclick="estudarDeck()">
                            <i class="fas fa-play me-2"></i>Estudar Deck
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Flashcards -->
        <div id="listaFlashcards" class="row">
            <!-- O conteúdo dos flashcards será carregado aqui via JavaScript -->
        </div>
    </div>

    <!-- Modal Criar/Editar Flashcard -->
    <div class="modal fade" id="modalFlashcard" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
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
                                    <label for="flashcardFrente" class="form-label">Frente do Card *</label>
                                    <textarea class="form-control" id="flashcardFrente" name="frente" rows="3" required placeholder="Digite o conteúdo da frente do flashcard"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="flashcardVerso" class="form-label">Verso do Card *</label>
                                    <textarea class="form-control" id="flashcardVerso" name="verso" rows="3" required placeholder="Digite o conteúdo do verso do flashcard"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="flashcardDica" class="form-label">Dica (opcional)</label>
                                    <textarea class="form-control" id="flashcardDica" name="dica" rows="2" placeholder="Digite uma dica para ajudar na memorização"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="flashcardDificuldade" class="form-label">Dificuldade</label>
                                            <select class="form-select" id="flashcardDificuldade" name="dificuldade">
                                                <option value="facil">Fácil</option>
                                                <option value="medio" selected>Médio</option>
                                                <option value="dificil">Difícil</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="flashcardOrdem" class="form-label">Ordem</label>
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
                                <div class="flashcard-inner">
                                    <div class="flashcard-front">
                                        <div id="previewFrente">Digite o conteúdo da frente</div>
                                    </div>
                                    <div class="flashcard-back">
                                        <div id="previewVerso">Digite o conteúdo do verso</div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarFlashcard()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variáveis globais
        const idDeck = <?php echo $id_deck; ?>;
        let modalFlashcard = null;
        let deckAtual = null;
        let flashcardAtual = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            modalFlashcard = new bootstrap.Modal(document.getElementById('modalFlashcard'));
            carregarDeck();
            carregarFlashcards();
            
            // Event listeners para preview
            document.getElementById('flashcardFrente').addEventListener('input', atualizarPreview);
            document.getElementById('flashcardVerso').addEventListener('input', atualizarPreview);
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
                <div class="col-12">
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
                                <div class="col-md-3">
                                    <div class="h4 text-primary">${deck.total_flashcards || 0}</div>
                                    <div class="text-muted">Total de Cards</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="h4 text-success">${deck.flashcards_estudados || 0}</div>
                                    <div class="text-muted">Cards Estudados</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="h4 text-warning">${deck.total_flashcards > 0 ? Math.round(((deck.flashcards_estudados || 0) / deck.total_flashcards) * 100) : 0}%</div>
                                    <div class="text-muted">Progresso</div>
                                </div>
                                <div class="col-md-3">
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

        // Carrega flashcards do deck
        function carregarFlashcards() {
            fetch(`flashcard_controller.php?action=listar_flashcards&id_deck=${idDeck}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        exibirFlashcards(data.flashcards);
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
        function exibirFlashcards(flashcards) {
            const container = document.getElementById('listaFlashcards');
            
            if (flashcards.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-layer-group"></i>
                            <h3>Nenhum flashcard encontrado</h3>
                            <p>Adicione flashcards a este deck para começar a estudar.</p>
                            <button class="btn btn-primary" onclick="abrirModalFlashcard()">
                                <i class="fas fa-plus me-2"></i>Adicionar Primeiro Flashcard
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            flashcards.forEach((flashcard, index) => {
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
                        <div class="flashcard-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-${dificuldadeClass[flashcard.dificuldade] || 'secondary'}">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
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
                            
                            <div class="flashcard-preview mb-3" onclick="virarFlashcard(this)">
                                <div class="flashcard-inner">
                                    <div class="flashcard-front">
                                        <div>${flashcard.frente}</div>
                                    </div>
                                    <div class="flashcard-back">
                                        <div>${flashcard.verso}</div>
                                    </div>
                                </div>
                            </div>
                            
                            ${flashcard.dica ? `<div class="text-muted small mb-2"><i class="fas fa-lightbulb me-1"></i> ${flashcard.dica}</div>` : ''}
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Card #${index + 1}</small>
                                ${flashcard.acertos !== undefined ? `
                                    <small class="text-muted">
                                        <i class="fas fa-check text-success me-1"></i>${flashcard.acertos || 0}
                                        <i class="fas fa-times text-danger ms-2 me-1"></i>${flashcard.erros || 0}
                                    </small>
                                ` : ''}
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
        function virarFlashcard(element) {
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
            
            document.getElementById('previewFrente').textContent = frente;
            document.getElementById('previewVerso').textContent = verso;
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