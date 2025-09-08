let modalDeck = null;
let deckAtual = null;
// Inicialização
document.addEventListener(\'DOMContentLoaded\', function() {
    modalDeck = new bootstrap.Modal(document.getElementById(\'modalDeck\'));
    carregarDecks();
});

// Carrega decks do backend
function carregarDecks() {
    const filtroIdioma = document.getElementById(\'filtroIdioma\').value;
    const tipoDecks = document.getElementById(\'tipoDecks\').value;

    const url = `flashcard_controller.php?action=obter_decks&idioma=${filtroIdioma}&tipo=${tipoDecks}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {         exibirDecks(data.decks);
            } else {
                console.error('Erro ao carregar decks:', data.message);
                exibirErroDecks('Erro ao carregar decks: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro de rede:', error);
            exibirErroDecks('Erro de conexão. Tente novamente.');
        });
}

// Exibe os decks na página
function exibirDecks(decks) {
    const container = document.getElementById('listaDecks');
    container.innerHTML = ''; // Limpa o conteúdo atual

    if (decks.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Nenhum deck encontrado</h3>
                    <p>Crie seu primeiro deck ou ajuste os filtros.</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    decks.forEach(deck => {
        html += `
            <div class="col-md-4 mb-4">
                <div class="card deck-card" onclick="window.location.href='flashcard_deck.php?id=${deck.id}'">
                    <div class="card-body">
                        <h5 class="card-title">${deck.nome}</h5>
                        <p class="card-text text-muted">${deck.descricao || 'Sem descrição'}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">${deck.idioma}</span>
                            <span class="badge bg-info">${deck.nivel}</span>
                            ${deck.publico == 1 ? '<span class="badge bg-success">Público</span>' : '<span class="badge bg-secondary">Privado</span>'}
                        </div>
                        <div class="deck-stats mt-3">
                            <div class="row">
                                <div class="col-6 stat-item">
                                    <div class="stat-number">${deck.total_flashcards || 0}</div>
                                    <div class="stat-label">Cards</div>
                                </div>
                                <div class="col-6 stat-item">
                                    <div class="stat-number">${deck.flashcards_estudados || 0}</div>
                                    <div class="stat-label">Estudados</div>
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

// Exibe erro ao carregar decks
function exibirErroDecks(mensagem) {
    const container = document.getElementById('listaDecks');
    container.innerHTML = `
        <div class="col-12">
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${mensagem}
            </div>
        </div>
    `;
}

// Aplica filtros
function aplicarFiltros() {
    carregarDecks();
}

// Limpa filtros
function limparFiltros() {
    document.getElementById(\'filtroIdioma\').value = \'\';
    document.getElementById(\'tipoDecks\').value = \'meus\';
    carregarDecks();
}

// Abre modal para criar novo deck
function abrirModalCriarDeck() {
    deckAtual = null;
    document.getElementById('tituloModalDeck').textContent = 'Novo Deck';
    document.getElementById('formDeck').reset();
    document.getElementById('deckId').value = '';
    modalDeck.show();
}

// Salva deck (criar ou editar)
function salvarDeck() {
    const form = document.getElementById('formDeck');
    const formData = new FormData(form);
    
    const isEdicao = document.getElementById('deckId').value !== '';
    const action = isEdicao ? 'atualizar_deck' : 'criar_deck';
    
    formData.append('action', action);
    
    fetch('flashcard_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modalDeck.hide();
            carregarDecks();
        } else {
            alert('Erro ao salvar deck: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão ao salvar deck.');
    });
}

// Estudar todos os flashcards (redireciona para flashcard_estudo.php sem id_deck)
function estudarFlashcards() {
    window.location.href = 'flashcard_estudo.php';
}

