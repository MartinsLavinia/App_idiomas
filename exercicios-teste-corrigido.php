<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Exercícios Corrigidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/exercicios-corrigidos.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">🧪 Teste - Exercícios Corrigidos</h1>
        
        <!-- Teste Listening -->
        <div class="exercicio-container exercicio-listening" id="exercicio-listening">
            <h3><i class="fas fa-headphones text-info"></i> Exercício de Listening</h3>
            <p class="mb-3">Ouça o áudio e escolha a resposta correta:</p>
            
            <div class="audio-section mb-4">
                <p><em>🔊 Áudio: "Good morning, how are you?"</em></p>
                <div class="audio-controls">
                    <button class="btn btn-primary btn-audio-play">
                        <i class="fas fa-play me-2"></i>Reproduzir Áudio
                    </button>
                </div>
            </div>
            
            <div class="opcoes-container">
                <div class="opcao-exercicio option-audio" data-index="0" onclick="selecionarOpcaoListening(0, this)">
                    <strong>A)</strong> Good morning
                </div>
                <div class="opcao-exercicio option-audio" data-index="1" onclick="selecionarOpcaoListening(1, this)">
                    <strong>B)</strong> Good afternoon
                </div>
                <div class="opcao-exercicio option-audio" data-index="2" onclick="selecionarOpcaoListening(2, this)">
                    <strong>C)</strong> Good evening
                </div>
                <div class="opcao-exercicio option-audio" data-index="3" onclick="selecionarOpcaoListening(3, this)">
                    <strong>D)</strong> Good night
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-success btn-lg btn-responder" onclick="responderListening()" disabled>
                    <i class="fas fa-check me-2"></i>Responder
                </button>
                <button class="btn btn-secondary btn-lg ms-2" onclick="resetarListening()">
                    <i class="fas fa-redo me-2"></i>Resetar
                </button>
            </div>
            
            <div class="feedback-container mt-3" style="display: none;"></div>
        </div>
        
        <!-- Teste Fala -->
        <div class="exercicio-container exercicio-fala" id="exercicio-fala">
            <h3><i class="fas fa-microphone text-primary"></i> Exercício de Fala</h3>
            
            <div class="frase-section mb-4">
                <p class="mb-3">Fale a seguinte frase em inglês:</p>
                <div class="frase-para-falar p-3 bg-light rounded text-center">
                    <strong>"Hello, how are you today?"</strong>
                </div>
            </div>

            <div class="gravacao-section text-center">
                <button class="microphone-btn" onclick="toggleGravacao()">
                    <i class="fas fa-microphone"></i>
                </button>
                
                <div class="status-gravacao mt-3">
                    <span class="status-text">Clique no microfone para começar a gravar</span>
                </div>
                
                <div class="resultado-fala mt-3" style="display: none;"></div>
            </div>
            
            <!-- Status do Microfone -->
            <div id="microfone-status" class="mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Verificando Microfone...</strong>
                    <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="window.sistemaAudio?.solicitarPermissaoMicrofone()">
                        <i class="fas fa-microphone me-1"></i>Testar Microfone
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Instruções -->
        <div class="exercicio-container">
            <h3><i class="fas fa-info-circle text-warning"></i> Instruções de Teste</h3>
            <div class="row">
                <div class="col-md-6">
                    <h6>Exercício de Listening:</h6>
                    <p class="small">A resposta correta é <strong>A) Good morning</strong>. Teste selecionando uma opção errada para ver o feedback.</p>
                </div>
                <div class="col-md-6">
                    <h6>Exercício de Fala:</h6>
                    <p class="small">Clique no microfone e permita o acesso quando solicitado. O sistema irá simular a análise de pronúncia.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/exercicios-microfone-corrigido.js"></script>
    
    <script>
        let respostaListeningSelecionada = null;
        
        // Listening
        function selecionarOpcaoListening(index, elemento) {
            // Limpar seleções anteriores
            document.querySelectorAll('#exercicio-listening .opcao-exercicio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected');
            });
            
            // Selecionar atual
            elemento.classList.add('selecionada', 'selected');
            respostaListeningSelecionada = index;
            
            // Habilitar botão
            document.querySelector('#exercicio-listening .btn-responder').disabled = false;
        }
        
        async function responderListening() {
            if (respostaListeningSelecionada === null) return;
            
            // Simular dados do exercício de listening
            const exercicioData = {
                exercicio_id: 1,
                resposta: respostaListeningSelecionada,
                tipo_exercicio: 'listening'
            };
            
            try {
                const response = await fetch('admin/controller/processar_exercicio_corrigido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(exercicioData)
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    aplicarFeedbackListening(resultado);
                } else {
                    // Fallback: usar lógica local
                    const resultadoLocal = {
                        success: true,
                        correto: respostaListeningSelecionada === 0,
                        alternativa_correta_id: 0,
                        explicacao: respostaListeningSelecionada === 0 ? 
                            '✅ Correto! Você compreendeu o áudio perfeitamente! O áudio diz "Good morning, how are you?" que é uma saudação matinal.' : 
                            '❌ Incorreto. A resposta correta é: "Good morning". Transcrição: "Good morning, how are you?". O áudio diz uma saudação matinal típica.',
                        pontuacao: respostaListeningSelecionada === 0 ? 100 : 0
                    };
                    aplicarFeedbackListening(resultadoLocal);
                }
                
            } catch (error) {
                console.error('Erro:', error);
                // Fallback local
                const resultadoLocal = {
                    success: true,
                    correto: respostaListeningSelecionada === 0,
                    alternativa_correta_id: 0,
                    explicacao: respostaListeningSelecionada === 0 ? 
                        '✅ Correto! Você compreendeu o áudio perfeitamente!' : 
                        '❌ Incorreto. A resposta correta é: "Good morning".',
                    pontuacao: respostaListeningSelecionada === 0 ? 100 : 0
                };
                aplicarFeedbackListening(resultadoLocal);
            }
        }
        
        function aplicarFeedbackListening(resultado) {
            const container = document.getElementById('exercicio-listening');
            const { correto, alternativa_correta_id, explicacao, pontuacao } = resultado;
            
            // Marcar resposta selecionada
            const opcaoSelecionada = container.querySelector(`[data-index="${respostaListeningSelecionada}"]`);
            if (opcaoSelecionada) {
                opcaoSelecionada.classList.add(correto ? 'resposta-correta' : 'resposta-incorreta');
            }
            
            // Marcar resposta correta se errou
            if (!correto && alternativa_correta_id !== null) {
                const opcaoCorreta = container.querySelector(`[data-index="${alternativa_correta_id}"]`);
                if (opcaoCorreta) {
                    opcaoCorreta.classList.add('resposta-correta');
                }
            }
            
            // Mostrar feedback
            const feedbackContainer = container.querySelector('.feedback-container');
            feedbackContainer.innerHTML = `
                <div class="alert ${correto ? 'alert-success' : 'alert-danger'}">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas ${correto ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                        <strong>${correto ? 'Correto!' : 'Incorreto'}</strong>
                        <span class="ms-auto badge ${correto ? 'bg-success' : 'bg-danger'}">${pontuacao}%</span>
                    </div>
                    <p class="mb-0">${explicacao}</p>
                </div>
            `;
            feedbackContainer.style.display = 'block';
            
            // Desabilitar opções
            container.querySelectorAll('.opcao-exercicio').forEach(opt => {
                opt.style.pointerEvents = 'none';
            });
            
            // Desabilitar botão
            container.querySelector('.btn-responder').disabled = true;
        }
        
        function resetarListening() {
            respostaListeningSelecionada = null;
            const container = document.getElementById('exercicio-listening');
            
            // Limpar feedback
            container.querySelectorAll('.opcao-exercicio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected', 'resposta-correta', 'resposta-incorreta');
                opt.style.pointerEvents = 'auto';
            });
            
            const feedback = container.querySelector('.feedback-container');
            feedback.style.display = 'none';
            feedback.innerHTML = '';
            
            container.querySelector('.btn-responder').disabled = true;
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Sistema de teste carregado');
        });
    </script>
</body>
</html>