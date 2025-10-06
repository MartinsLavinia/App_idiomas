let modalDeck = null;
let deckAtual = null;
const nivelAtual = '<?php echo $nivel_atual; ?>';

// Inicialização
document.addEventListener('DOMContentLoaded', function () {
    modalDeck = new bootstrap.Modal(document.getElementById('modalDeck'));
    carregarDecks();
});

// Carrega decks do backend
function carregarDecks() {
    const filtroIdioma = document.getElementById('filtroIdioma').value;
    const filtroNivel = document.getElementById('filtroNivel') ? document.getElementById('filtroNivel').value : nivelAtual;
    const tipoDecks = document.getElementById('tipoDecks').value;

    let url;
    if (tipoDecks === 'publicos') {
        url = `flashcard_controller.php?action=listar_decks_publicos&idioma=${filtroIdioma}&nivel=${filtroNivel}`;
    } else {
        url = `flashcard_controller.php?action=listar_decks&idioma=${filtroIdioma}&nivel=${filtroNivel}`;
    }

    // MOSTRAR LOADING
    document.getElementById('listaDecks').innerHTML = `
        <div class="col-12">
            <div class="loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando decks...</p>
            </div>
        </div>
    `;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                exibirDecks(data.decks);
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

// Salvar deck
function salvarDeck() {
    const form = document.getElementById('formDeck');
    const formData = new FormData(form);

    const isEdicao = document.getElementById('deckId').value !== '';
    const action = isEdicao ? 'atualizar_deck' : 'criar_deck';

    formData.append('action', action);

    // VALIDAÇÃO BÁSICA
    const nome = formData.get('nome');
    const idioma = formData.get('idioma');
    const nivel = formData.get('nivel');
    
    if (!nome || !idioma || !nivel) {
        alert('Por favor, preencha todos os campos obrigatórios (Nome, Idioma e Nível).');
        return;
    }

    // MOSTRAR LOADING NO BOTÃO
    const btnSalvar = document.querySelector('#modalDeck .btn-primary');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
    btnSalvar.disabled = true;

    fetch('flashcard_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            modalDeck.hide();
            mostrarMensagem('Deck salvo com sucesso!', 'success');
            carregarDecks();
        } else {
            throw new Error(data.message || 'Erro desconhecido ao salvar deck');
        }
    })
    .catch(error => {
        console.error('Erro detalhado:', error);
        mostrarMensagem('Erro ao salvar deck: ' + error.message, 'danger');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

// Mostrar mensagens toast
function mostrarMensagem(mensagem, tipo = 'success') {
    // Criar elemento de mensagem
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} alert-dismissible fade show`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <strong>${tipo === 'success' ? 'Sucesso!' : 'Erro!'}</strong> ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Exibe decks na lista com opções de editar/excluir
function exibirDecks(decks) {
    const container = document.getElementById('listaDecks');
    container.innerHTML = '';

    if (!decks || decks.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Nenhum deck encontrado</h3>
                    <p>Crie seu primeiro deck ou ajuste os filtros.</p>
                    <button class="btn btn-primary mt-3" onclick="abrirModalCriarDeck()">
                        <i class="fas fa-plus me-2"></i>Criar Primeiro Deck
                    </button>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    decks.forEach(deck => {
        html += `
            <div class="col-md-4 mb-4">
                <div class="card deck-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title" style="cursor: pointer;" onclick="abrirDeck(${deck.id})">${deck.nome}</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" onclick="event.stopPropagation()">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="abrirModalEditarDeck(${deck.id}, event)">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="excluirDeck(${deck.id}, '${deck.nome.replace(/'/g, "\\'")}', event)">
                                            <i class="fas fa-trash me-2"></i>Excluir
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p class="card-text text-muted">${deck.descricao || 'Sem descrição'}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">${deck.idioma}</span>
                            <span class="badge bg-info">${deck.nivel}</span>
                            ${deck.publico == 1
                                ? '<span class="badge bg-success">Público</span>'
                                : '<span class="badge bg-secondary">Privado</span>'}
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
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm w-100" onclick="abrirDeck(${deck.id})">
                                <i class="fas fa-folder-open me-2"></i>Abrir Deck
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

// Abrir deck
function abrirDeck(idDeck) {
    window.location.href = `flashcard_deck.php?id=${idDeck}`;
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
    document.getElementById('filtroIdioma').value = '';
    document.getElementById('tipoDecks').value = 'meus';
    const filtroNivel = document.getElementById('filtroNivel');
    if (filtroNivel) filtroNivel.value = '';
    carregarDecks();
}

// Abre modal para criar novo deck
function abrirModalCriarDeck() {
    deckAtual = null;
    document.getElementById('tituloModalDeck').textContent = 'Novo Deck';
    document.getElementById('formDeck').reset();
    document.getElementById('deckId').value = '';
    
    // Preencher idioma e nível com valores padrão
    const filtroIdioma = document.getElementById('filtroIdioma').value;
    if (filtroIdioma) {
        document.getElementById('deckIdioma').value = filtroIdioma;
    }
    
    document.getElementById('deckNivel').value = nivelAtual;
    document.getElementById('deckPublico').checked = false;
    
    modalDeck.show();
}

// Abre modal para editar deck
function abrirModalEditarDeck(idDeck, event) {
    // Prevenir que o evento de clique propague para o card
    if (event) {
        event.stopPropagation();
    }
    
    // Buscar dados do deck via AJAX
    fetch(`flashcard_controller.php?action=obter_deck&id_deck=${idDeck}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const deck = data.deck;
                deckAtual = deck;
                
                document.getElementById('tituloModalDeck').textContent = 'Editar Deck';
                document.getElementById('deckId').value = deck.id;
                document.getElementById('deckNome').value = deck.nome;
                document.getElementById('deckDescricao').value = deck.descricao || '';
                document.getElementById('deckIdioma').value = deck.idioma;
                document.getElementById('deckNivel').value = deck.nivel;
                document.getElementById('deckPublico').checked = deck.publico == 1;
                
                modalDeck.show();
            } else {
                mostrarMensagem('Erro ao carregar deck: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarMensagem('Erro de conexão ao carregar deck.', 'danger');
        });
}

// Excluir deck
function excluirDeck(idDeck, nomeDeck, event) {
    // Prevenir que o evento de clique propague para o card
    if (event) {
        event.stopPropagation();
    }
    
    if (!confirm(`Tem certeza que deseja excluir o deck "${nomeDeck}"? Esta ação não pode ser desfeita e todos os flashcards serão perdidos.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'excluir_deck');
    formData.append('id_deck', idDeck);

    fetch('flashcard_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensagem('Deck excluído com sucesso!', 'success');
            carregarDecks();
        } else {
            mostrarMensagem('Erro ao excluir deck: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarMensagem('Erro de conexão ao excluir deck.', 'danger');
    });
}

// Estudar todos os flashcards
function estudarFlashcards() {
    window.location.href = 'flashcard_estudo.php';
}

// Função para debug - testar conexão
function testarConexao() {
    console.log('Testando conexão com o controller...');
    
    fetch('flashcard_controller.php?action=listar_decks')
        .then(response => {
            console.log('Status da resposta:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Resposta do servidor:', data);
        })
        .catch(error => {
            console.error('Erro na conexão:', error);
        });
}

// Adicionar event listeners para melhor UX
document.addEventListener('DOMContentLoaded', function() {
    // Enter no modal salva o deck
    const modal = document.getElementById('modalDeck');
    if (modal) {
        modal.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                salvarDeck();
            }
        });
    }
    
    // Focar no primeiro campo do modal quando abrir
    modal.addEventListener('shown.bs.modal', function() {
        document.getElementById('deckNome').focus();
    });
});