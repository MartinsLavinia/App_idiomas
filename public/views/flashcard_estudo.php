<?php
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
$id_deck = intval($_GET['deck'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudar Flash Cards - Site de Idiomas</title>
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
            --verde-sucesso: #28a745;
            --vermelho-erro: #dc3545;
        }

        /* Estilos Gerais */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e8f0 100%);
            color: var(--preto-texto);
            min-height: 100vh;
            overflow-x: hidden;
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

        /* Container Principal */
        .study-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0; /* Removido para ser controlado pelo main-content */
        }

        /* Progresso */
        .progress-section {
            background: var(--branco);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--cinza-medio);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .progress-section h5 {
            color: var(--preto-texto);
        }

        .progress {
            height: 10px;
            background: var(--cinza-medio);
            border-radius: 5px;
        }

        .progress-bar {
            background: var(--amarelo-detalhe);
            border-radius: 5px;
        }

        /* Flashcard */
        .flashcard-container {
            perspective: 1000px;
            margin-bottom: 2rem;
            min-height: 420px; /* Garante espaço para o card não pular */
        }

        .flashcard {
            position: relative;
            width: 100%;
            height: 400px;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--cinza-medio);
            overflow: hidden; /* Para o cabeçalho não vazar */
            display: flex;
            flex-direction: column;
        }

        .flashcard-front {
            background: var(--branco);
        }

        .flashcard-back {
            background: var(--branco);
            transform: rotateY(180deg);
        }

        .flashcard-header {
            padding: 1rem 1.5rem;
            color: var(--branco);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .flashcard-front .flashcard-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        }
        .flashcard-back .flashcard-header {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
        }

        .flashcard-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            text-align: center;
        }

        .flashcard-hint {
            font-size: 1rem;
            opacity: 0.8;
            font-style: italic;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .flashcard-footer {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Botões de Resposta */
        .response-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-response {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-response:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-again {
            background: linear-gradient(135deg, #ff6b6b, #ee5253);
            color: white;
        }

        .btn-hard {
            background: linear-gradient(135deg, #feca57, #ff9f43);
            color: var(--preto-texto);
        }

        .btn-good {
            background: linear-gradient(135deg, #54a0ff, #2e86de);
            color: white;
        }

        .btn-easy {
            background: linear-gradient(135deg, #1dd1a1, #10ac84);
            color: white;
        }

        /* Estatísticas */
        .stats-section {
            background: var(--branco);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--cinza-medio);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--amarelo-detalhe);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Estados */
        .loading, .empty-state, .completed-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--branco);
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .loading h3, .empty-state h3, .completed-state h3 {
            color: var(--preto-texto);
            font-weight: 600;
        }

        .loading i, .empty-state i, .completed-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--amarelo-detalhe);
        }
        
        .completed-state .stat-number {
            color: var(--roxo-principal);
        }
        .completed-state .text-success {
            color: var(--verde-sucesso) !important;
        }
        .completed-state .text-warning {
            color: var(--amarelo-detalhe) !important;
        }



        /* Animações */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .flashcard-container.slide-in {
            animation: slideInUp 0.5s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .study-container {
                padding: 1rem;
            }

            .flashcard {
                height: 300px;
            }
            
            .flashcard-content {
                font-size: 1.2rem;
            }

            .response-buttons {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .btn-response {
                padding: 0.75rem 1rem;
                font-size: 1rem;
                min-width: 100px;
            }
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
        <div class="container-fluid">
            <div class="study-container">
                <!-- Cabeçalho -->
                <div class="row mb-4 align-items-center">
                    <div class="col">
                        <h1 class="mb-2">
                            <a href="flashcards.php" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
                            Sessão de Estudo
                        </h1>
                        <p class="text-muted mb-0">Concentre-se e revise seus flashcards.</p>
                    </div>
                </div>

                <!-- Progresso -->
                <div class="progress-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Progresso da Sessão</h5>
                        <span id="progressText" class="fw-bold">0 / 0</span>
                    </div>
                    <div class="progress">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div id="statsSection" class="stats-section" style="display: none;">
                    <div class="row">
                        <div class="col-3 stat-item"><div id="statTotal" class="stat-number">0</div><div class="stat-label">Total</div></div>
                        <div class="col-3 stat-item"><div id="statCorrect" class="stat-number">0</div><div class="stat-label">Acertos</div></div>
                        <div class="col-3 stat-item"><div id="statWrong" class="stat-number">0</div><div class="stat-label">Erros</div></div>
                        <div class="col-3 stat-item"><div id="statAccuracy" class="stat-number">0%</div><div class="stat-label">Precisão</div></div>
                    </div>
                </div>

                <!-- Área de Estudo -->
                <div id="studyArea">
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <h3 class="mt-3">Carregando flashcards...</h3>
                        <p class="text-muted">Preparando sua sessão de estudo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="flashcard_script.js"></script>
    <script>
        // Variáveis globais
        const idDeck = <?php echo $id_deck ?: 'null'; ?>;
        let flashcards = [];
        let currentIndex = 0;
        let isFlipped = false;
        let sessionStats = {
            total: 0,
            correct: 0,
            wrong: 0,
            completed: 0
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            carregarFlashcards();
        });

        // Carrega flashcards para estudo
        function carregarFlashcards() {
            let url = 'flashcard_controller.php?action=obter_flashcards_para_revisar';
            if (idDeck) {
                url += `&id_deck=${idDeck}`;
            }
            url += '&limite=50';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        flashcards = data.flashcards;
                        sessionStats.total = flashcards.length;
                        
                        if (flashcards.length > 0) {
                            iniciarEstudo();
                        } else {
                            exibirEstadoVazio();
                        }
                    } else {
                        console.error('Erro ao carregar flashcards:', data.message);
                        exibirErro('Erro ao carregar flashcards: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                    exibirErro('Erro de conexão. Tente novamente.');
                });
        }

        // Inicia o estudo
        function iniciarEstudo() {
            document.getElementById('statsSection').style.display = 'block';
            atualizarEstatisticas();
            exibirFlashcard();
        }

        // Exibe o flashcard atual
        function exibirFlashcard() {
            if (currentIndex >= flashcards.length) {
                exibirEstadoConcluido();
                return;
            }

            const flashcard = flashcards[currentIndex];
            isFlipped = false;
            
            const dificuldadeTexto = {
                'facil': 'Fácil',
                'medio': 'Médio',
                'dificil': 'Difícil'
            };

            // Adiciona a classe de animação
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = ''; // Limpa a área

            // Cria o novo card
            studyArea.innerHTML = `
                <div class="flashcard-container slide-in">
                    <div class="flashcard" onclick="virarFlashcard()">
                        <div class="flashcard-side flashcard-front">
                            <div class="flashcard-header">
                                <span>Pergunta</span>
                                <span class="badge bg-light text-dark">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                            </div>
                            <div class="flashcard-content">
                                <span>${flashcard.frente}</span>
                                ${flashcard.dica ? `<div class="flashcard-hint"><i class="fas fa-lightbulb me-1"></i> ${flashcard.dica}</div>` : ''}
                            </div>
                            <div class="flashcard-footer">
                                Clique no card para ver a resposta
                            </div>
                        </div>
                        <div class="flashcard-side flashcard-back">
                            <div class="flashcard-header">
                                <span>Resposta</span>
                                <span class="badge bg-dark text-light">${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}</span>
                            </div>
                            <div class="flashcard-content">
                                <span>${flashcard.verso}</span>
                            </div>
                            <div class="flashcard-footer">
                                Como você se saiu?
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="responseButtons" class="response-buttons" style="display: none;">
                    <button class="btn btn-response btn-again" onclick="responder(0)">
                        <i class="fas fa-times me-2"></i>Errei
                    </button>
                    <button class="btn btn-response btn-hard" onclick="responder(2)">
                        <i class="fas fa-frown me-2"></i>Difícil
                    </button>
                    <button class="btn btn-response btn-good" onclick="responder(4)">
                        <i class="fas fa-smile me-2"></i>Bom
                    </button>
                    <button class="btn btn-response btn-easy" onclick="responder(4)">
                        <i class="fas fa-laugh me-2"></i>Fácil
                    </button>
                </div>
            `;

            atualizarProgresso();
        }

        // Vira o flashcard
        function virarFlashcard() {
            const flashcard = document.querySelector('.flashcard');
            if (!isFlipped) {
                flashcard.classList.add('flipped');
                isFlipped = true;
                
                // Mostra botões de resposta após um delay
                setTimeout(() => {
                    document.getElementById('responseButtons').style.display = 'flex';
                }, 400);
            }
        }

        // Registra resposta do usuário
        function responder(facilidade) {
            const flashcard = flashcards[currentIndex];
            const acertou = facilidade >= 2; // 2, 3, 4 = acertou; 0 = errou
            
            // Atualiza estatísticas da sessão
            if (acertou) {
                sessionStats.correct++;
            } else {
                sessionStats.wrong++;
            }
            sessionStats.completed++;
            
            // Registra no backend
            const formData = new FormData();
            formData.append('action', 'registrar_resposta');
            formData.append('id_flashcard', flashcard.id);
            formData.append('acertou', acertou ? '1' : '0');
            formData.append('facilidade_resposta', facilidade);
            
            fetch('flashcard_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erro ao registrar resposta:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao registrar resposta:', error);
            });
            
            // Avança para o próximo flashcard
            currentIndex++;
            atualizarEstatisticas();
            
            setTimeout(() => {
                exibirFlashcard();
            }, 300);
        }

        // Atualiza progresso
        function atualizarProgresso() {
            const progress = flashcards.length > 0 ? (currentIndex / flashcards.length) * 100 : 0;
            document.getElementById('progressBar').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = `${currentIndex} / ${flashcards.length}`;
        }

        // Atualiza estatísticas
        function atualizarEstatisticas() {
            document.getElementById('statTotal').textContent = sessionStats.total;
            document.getElementById('statCorrect').textContent = sessionStats.correct;
            document.getElementById('statWrong').textContent = sessionStats.wrong;
            
            const accuracy = sessionStats.completed > 0 ? 
                Math.round((sessionStats.correct / sessionStats.completed) * 100) : 0;
            document.getElementById('statAccuracy').textContent = `${accuracy}%`;
        }

        // Exibe estado vazio
        function exibirEstadoVazio() {
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Nenhum flashcard para revisar</h3>
                    <p>Você está em dia com seus estudos! Volte mais tarde ou adicione novos flashcards.</p>
                    <div class="mt-4">
                        <a href="flashcards.php" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-layer-group me-2"></i>Ver Meus Decks
                        </a>
                        <a href="flashcard_deck.php?id=${idDeck}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-plus me-2"></i>Adicionar Cards
                        </a>
                    </div>
                </div>
            `;
        }

        // Exibe estado concluído
        function exibirEstadoConcluido() {
            const accuracy = sessionStats.completed > 0 ? 
                Math.round((sessionStats.correct / sessionStats.completed) * 100) : 0;
            
            let message = '';
            let icon = '';
            
            if (accuracy >= 90) {
                message = 'Excelente trabalho!';
                icon = 'fas fa-trophy';
            } else if (accuracy >= 70) {
                message = 'Bom trabalho!';
                icon = 'fas fa-thumbs-up';
            } else {
                message = 'Continue praticando!';
                icon = 'fas fa-dumbbell';
            }

            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="completed-state">
                    <i class="${icon}"></i>
                    <h3>${message}</h3>
                    <p>Você completou sua sessão de estudo com ${accuracy}% de precisão.</p>
                    
                    <div class="row mt-4 mb-4">
                        <div class="col-sm-4 stat-item mb-3 mb-sm-0">
                            <div class="stat-number">${sessionStats.total}</div>
                            <div class="stat-label">Cards Estudados</div>
                        </div>
                        <div class="col-sm-4 stat-item mb-3 mb-sm-0">
                            <div class="stat-number text-success">${sessionStats.correct}</div>
                            <div class="stat-label">Acertos</div>
                        </div>
                        <div class="col-sm-4 stat-item">
                            <div class="stat-number text-warning">${accuracy}%</div>
                            <div class="stat-label">Precisão</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-primary btn-lg me-2" onclick="reiniciarEstudo()">
                            <i class="fas fa-redo me-2"></i>Estudar Novamente
                        </button>
                        <a href="flashcards.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-layer-group me-2"></i>Ver Meus Decks
                        </a>
                    </div>
                </div>
            `;
            
            // Atualiza progresso para 100%
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressText').textContent = `${flashcards.length} / ${flashcards.length}`;
        }

        // Reinicia o estudo
        function reiniciarEstudo() {
            currentIndex = 0;
            sessionStats = {
                total: flashcards.length,
                correct: 0,
                wrong: 0,
                completed: 0
            };
            atualizarEstatisticas();
            exibirFlashcard();
        }

        // Exibe erro
        function exibirErro(mensagem) {
            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h3>Erro</h3>
                    <p>${mensagem}</p>
                    <div class="mt-4">
                        <button class="btn btn-primary" onclick="carregarFlashcards()">
                            <i class="fas fa-redo me-2"></i>Tentar Novamente
                        </button>
                    </div>
                </div>
            `;
        }
    </script>
</body>
</html>

    <script src="flashcard_script.js"></script>
