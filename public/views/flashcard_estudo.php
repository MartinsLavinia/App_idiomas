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
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Barra de Navegação */
        .navbar {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand, .navbar-text {
            color: var(--branco) !important;
        }

        .btn-outline-light {
            color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            font-weight: 600;
        }

        .btn-outline-light:hover {
            background-color: var(--amarelo-detalhe);
            color: var(--preto-texto);
        }

        /* Container Principal */
        .study-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Progresso */
        .progress-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .progress {
            height: 10px;
            background: rgba(255, 255, 255, 0.2);
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
        }

        .flashcard {
            position: relative;
            width: 100%;
            height: 400px;
            text-align: center;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .flashcard-front {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            color: var(--preto-texto);
        }

        .flashcard-back {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #ffed4e);
            color: var(--preto-texto);
            transform: rotateY(180deg);
        }

        .flashcard-content {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.4;
            text-align: center;
        }

        .flashcard-hint {
            margin-top: 1rem;
            font-size: 1rem;
            opacity: 0.8;
            font-style: italic;
        }

        .flashcard-difficulty {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-facil {
            background: var(--verde-sucesso);
            color: white;
        }

        .difficulty-medio {
            background: var(--amarelo-detalhe);
            color: var(--preto-texto);
        }

        .difficulty-dificil {
            background: var(--vermelho-erro);
            color: white;
        }

        /* Botões de Resposta */
        .response-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-response {
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .btn-response:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-again {
            background: var(--vermelho-erro);
            color: white;
        }

        .btn-hard {
            background: #fd7e14;
            color: white;
        }

        .btn-good {
            background: var(--verde-sucesso);
            color: white;
        }

        .btn-easy {
            background: var(--amarelo-detalhe);
            color: var(--preto-texto);
        }

        /* Estatísticas */
        .stats-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            opacity: 0.8;
        }

        /* Estados */
        .loading, .empty-state, .completed-state {
            text-align: center;
            padding: 3rem;
        }

        .loading i, .empty-state i, .completed-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--amarelo-detalhe);
        }

        /* Animações */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in {
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
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
                min-width: 100px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="flashcards.php">
                <i class="fas fa-arrow-left me-2"></i>Flash Cards
            </a>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="//logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="study-container">
        <!-- Progresso -->
        <div class="progress-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Progresso da Sessão</h5>
                <span id="progressText">0 / 0</span>
            </div>
            <div class="progress">
                <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div id="statsSection" class="stats-section" style="display: none;">
            <div class="row">
                <div class="col-3 stat-item">
                    <div id="statTotal" class="stat-number">0</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="col-3 stat-item">
                    <div id="statCorrect" class="stat-number">0</div>
                    <div class="stat-label">Acertos</div>
                </div>
                <div class="col-3 stat-item">
                    <div id="statWrong" class="stat-number">0</div>
                    <div class="stat-label">Erros</div>
                </div>
                <div class="col-3 stat-item">
                    <div id="statAccuracy" class="stat-number">0%</div>
                    <div class="stat-label">Precisão</div>
                </div>
            </div>
        </div>

        <!-- Área de Estudo -->
        <div id="studyArea">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Carregando flashcards...</h3>
                <p>Preparando sua sessão de estudo</p>
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
            
            const dificuldadeClass = {
                'facil': 'difficulty-facil',
                'medio': 'difficulty-medio',
                'dificil': 'difficulty-dificil'
            };
            
            const dificuldadeTexto = {
                'facil': 'Fácil',
                'medio': 'Médio',
                'dificil': 'Difícil'
            };

            const studyArea = document.getElementById('studyArea');
            studyArea.innerHTML = `
                <div class="flashcard-container slide-in">
                    <div class="flashcard" onclick="virarFlashcard()">
                        <div class="flashcard-side flashcard-front">
                            <div class="flashcard-difficulty ${dificuldadeClass[flashcard.dificuldade] || 'difficulty-medio'}">
                                ${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}
                            </div>
                            <div class="flashcard-content">${flashcard.frente}</div>
                            ${flashcard.dica ? `<div class="flashcard-hint"><i class="fas fa-lightbulb me-1"></i> ${flashcard.dica}</div>` : ''}
                            <div class="mt-4">
                                <small class="opacity-75">Clique para ver a resposta</small>
                            </div>
                        </div>
                        <div class="flashcard-side flashcard-back">
                            <div class="flashcard-difficulty ${dificuldadeClass[flashcard.dificuldade] || 'difficulty-medio'}">
                                ${dificuldadeTexto[flashcard.dificuldade] || 'Médio'}
                            </div>
                            <div class="flashcard-content">${flashcard.verso}</div>
                            <div class="mt-4">
                                <small class="opacity-75">Como foi sua resposta?</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="responseButtons" class="response-buttons" style="display: none;">
                    <button class="btn btn-response btn-again" onclick="responder(1)">
                        <i class="fas fa-times me-2"></i>Errei
                    </button>
                    <button class="btn btn-response btn-hard" onclick="responder(2)">
                        <i class="fas fa-frown me-2"></i>Difícil
                    </button>
                    <button class="btn btn-response btn-good" onclick="responder(4)">
                        <i class="fas fa-smile me-2"></i>Bom
                    </button>
                    <button class="btn btn-response btn-easy" onclick="responder(5)">
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
            const acertou = facilidade >= 3; // 3, 4, 5 = acertou; 1, 2 = errou
            
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
                        <a href="flashcards.php" class="btn btn-primary me-2">
                            <i class="fas fa-layer-group me-2"></i>Ver Meus Decks
                        </a>
                        <a href="flashcard_deck.php?id=${idDeck}" class="btn btn-outline-light">
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
                        <div class="col-4 stat-item">
                            <div class="stat-number text-info">${sessionStats.total}</div>
                            <div class="stat-label">Cards Estudados</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-number text-success">${sessionStats.correct}</div>
                            <div class="stat-label">Acertos</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-number text-warning">${accuracy}%</div>
                            <div class="stat-label">Precisão</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-primary me-2" onclick="reiniciarEstudo()">
                            <i class="fas fa-redo me-2"></i>Estudar Novamente
                        </button>
                        <a href="flashcards.php" class="btn btn-outline-light">
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
                    <i class="fas fa-exclamation-triangle"></i>
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
