<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Simular usuário logado para teste
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
    <link href="exercicios.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-4">🧪 Teste - Exercícios Corrigidos</h1>
                
                <!-- Teste 1: Exercício de Fala -->
                <div class="exercicio-container" id="exercicio-fala">
                    <h3><i class="fas fa-microphone text-primary"></i> Exercício de Fala</h3>
                    <p class="mb-3">Fale a seguinte frase em inglês:</p>
                    
                    <div class="text-center mb-3">
                        <div class="frase-para-falar p-3 bg-light rounded">
                            <strong>"Hello, how are you today?"</strong>
                        </div>
                    </div>
                    
                    <div class="microphone-section">
                        <button id="microphone-btn" class="microphone-btn" onclick="iniciarExercicioFala()">
                            <i class="fas fa-microphone"></i>
                        </button>
                        
                        <div id="speech-status" class="speech-status speech-ready">
                            Clique no microfone para começar a gravar
                        </div>
                        
                        <div class="https-warning" id="https-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Aviso:</strong> Para usar o microfone, certifique-se de que está acessando via HTTPS ou localhost.
                        </div>
                        
                        <div id="resultado-audio"></div>
                    </div>
                </div>
                
                <!-- Teste 2: Exercício de Listening -->
                <div class="exercicio-container exercicio-listening" id="exercicio-1">
                    <h3><i class="fas fa-headphones text-info"></i> Exercício de Listening</h3>
                    <p class="mb-3">Ouça o áudio e escolha a resposta correta:</p>
                    
                    <div class="audio-player mb-4">
                        <p><em>🔊 Áudio simulado: "Good morning, how are you?"</em></p>
                        <div class="audio-controls">
                            <button class="btn-audio" onclick="simularAudio()">
                                <i class="fas fa-play me-2"></i>Reproduzir Áudio
                            </button>
                        </div>
                    </div>
                    
                    <div class="opcoes-container">
                        <div class="option-audio alternativa" data-index="0" onclick="selecionarOpcao(0, this)">
                            <strong>A)</strong> Good morning
                        </div>
                        <div class="option-audio alternativa" data-index="1" onclick="selecionarOpcao(1, this)">
                            <strong>B)</strong> Good afternoon
                        </div>
                        <div class="option-audio alternativa" data-index="2" onclick="selecionarOpcao(2, this)">
                            <strong>C)</strong> Good evening
                        </div>
                        <div class="option-audio alternativa" data-index="3" onclick="selecionarOpcao(3, this)">
                            <strong>D)</strong> Good night
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button id="btn-responder" class="btn btn-primary btn-lg" onclick="responderExercicio()" disabled>
                            <i class="fas fa-check me-2"></i>Responder
                        </button>
                        <button class="btn btn-secondary btn-lg ms-2" onclick="resetarExercicio()">
                            <i class="fas fa-redo me-2"></i>Resetar
                        </button>
                    </div>
                </div>
                
                <!-- Teste 3: Múltipla Escolha -->
                <div class="exercicio-container" id="exercicio-2">
                    <h3><i class="fas fa-question-circle text-success"></i> Múltipla Escolha</h3>
                    <p class="mb-3">What is the capital of England?</p>
                    
                    <div class="opcoes-container">
                        <div class="alternativa opcao-exercicio" data-index="0" onclick="selecionarOpcaoMultipla(0, this)">
                            <strong>A)</strong> Manchester
                        </div>
                        <div class="alternativa opcao-exercicio" data-index="1" onclick="selecionarOpcaoMultipla(1, this)">
                            <strong>B)</strong> London
                        </div>
                        <div class="alternativa opcao-exercicio" data-index="2" onclick="selecionarOpcaoMultipla(2, this)">
                            <strong>C)</strong> Birmingham
                        </div>
                        <div class="alternativa opcao-exercicio" data-index="3" onclick="selecionarOpcaoMultipla(3, this)">
                            <strong>D)</strong> Liverpool
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button id="btn-responder-multipla" class="btn btn-success btn-lg" onclick="responderMultipla()" disabled>
                            <i class="fas fa-check me-2"></i>Responder
                        </button>
                        <button class="btn btn-secondary btn-lg ms-2" onclick="resetarMultipla()">
                            <i class="fas fa-redo me-2"></i>Resetar
                        </button>
                    </div>
                </div>
                
                <!-- Instruções -->
                <div class="exercicio-container">
                    <h3><i class="fas fa-info-circle text-warning"></i> Instruções de Teste</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Exercício de Fala:</h6>
                            <p class="small">Clique no microfone e permita o acesso. Fale a frase em inglês.</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Exercício de Listening:</h6>
                            <p class="small">Selecione uma alternativa ERRADA (B, C ou D) para testar o feedback visual.</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Múltipla Escolha:</h6>
                            <p class="small">Selecione uma alternativa ERRADA (A, C ou D) para ver a correção visual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../admin/controller/exercicio_fala.js"></script>
    <script src="../../admin/controller/correcao_visual_exercicios.js"></script>
    
    <script>
        let respostaSelecionada = null;
        let respostaMultipla = null;
        
        // Verificar HTTPS
        function verificarHTTPS() {
            const isHTTPS = location.protocol === 'https:';
            const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            
            if (!isHTTPS && !isLocalhost) {
                document.getElementById('https-warning').style.display = 'block';
            }
        }
        
        // Exercício de Fala
        function iniciarExercicioFala() {
            if (typeof window.exercicioFala !== 'undefined') {
                window.exercicioFala.iniciarReconhecimento(1, "Hello, how are you today?", "en-US");
            } else {
                document.getElementById('resultado-audio').innerHTML = `
                    <div class="alert alert-danger">
                        Sistema de exercícios de fala não carregado.
                    </div>
                `;
            }
        }
        
        // Exercício de Listening
        function selecionarOpcao(index, elemento) {
            // Remover seleção anterior
            document.querySelectorAll('#exercicio-1 .option-audio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected');
            });
            
            // Selecionar atual
            elemento.classList.add('selecionada', 'selected');
            respostaSelecionada = index;
            
            // Habilitar botão
            document.getElementById('btn-responder').disabled = false;
        }
        
        function simularAudio() {
            const btn = event.target.closest('.btn-audio');
            btn.classList.add('audio-playing');
            btn.innerHTML = '<i class="fas fa-pause me-2"></i>Reproduzindo...';
            
            setTimeout(() => {
                btn.classList.remove('audio-playing');
                btn.innerHTML = '<i class="fas fa-play me-2"></i>Reproduzir Áudio';
            }, 2000);
        }
        
        async function responderExercicio() {
            if (respostaSelecionada === null) return;
            
            // Simular resposta do servidor
            const correto = (respostaSelecionada === 0); // A resposta correta é a primeira
            const resultado = {
                success: true,
                correto: correto,
                alternativa_correta_id: 0,
                explicacao: correto ? 
                    '✅ Correto! Você compreendeu o áudio perfeitamente!' : 
                    '❌ Incorreto. A resposta correta é: "Good morning". Explicação: O áudio diz "Good morning, how are you?" que significa "Bom dia, como você está?"'
            };
            
            // Aplicar feedback visual
            if (typeof window.correcaoVisualExercicios !== 'undefined') {
                const container = document.getElementById('exercicio-1');
                window.correcaoVisualExercicios.aplicarFeedbackVisual(container, resultado, respostaSelecionada);
                window.correcaoVisualExercicios.mostrarExplicacao(container, resultado);
            }
            
            // Desabilitar botão
            document.getElementById('btn-responder').disabled = true;
        }
        
        function resetarExercicio() {
            respostaSelecionada = null;
            document.getElementById('btn-responder').disabled = true;
            
            // Limpar feedback
            document.querySelectorAll('#exercicio-1 .option-audio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected', 'option-correct', 'option-incorrect', 'resposta-correta', 'resposta-incorreta');
                opt.disabled = false;
                opt.style.pointerEvents = 'auto';
            });
            
            // Limpar explicação
            const feedback = document.querySelector('#exercicio-1 .feedback-message');
            if (feedback) feedback.remove();
        }
        
        // Múltipla Escolha
        function selecionarOpcaoMultipla(index, elemento) {
            document.querySelectorAll('#exercicio-2 .opcao-exercicio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected');
            });
            
            elemento.classList.add('selecionada', 'selected');
            respostaMultipla = index;
            document.getElementById('btn-responder-multipla').disabled = false;
        }
        
        async function responderMultipla() {
            if (respostaMultipla === null) return;
            
            const correto = (respostaMultipla === 1); // London é a resposta correta
            const resultado = {
                success: true,
                correto: correto,
                alternativa_correta_id: 1,
                explicacao: correto ? 
                    '✅ Correto! London é a capital da Inglaterra.' : 
                    '❌ Incorreto. A resposta correta é: "London". Explicação: London (Londres) é a capital da Inglaterra e do Reino Unido.'
            };
            
            if (typeof window.correcaoVisualExercicios !== 'undefined') {
                const container = document.getElementById('exercicio-2');
                window.correcaoVisualExercicios.aplicarFeedbackVisual(container, resultado, respostaMultipla);
                window.correcaoVisualExercicios.mostrarExplicacao(container, resultado);
            }
            
            document.getElementById('btn-responder-multipla').disabled = true;
        }
        
        function resetarMultipla() {
            respostaMultipla = null;
            document.getElementById('btn-responder-multipla').disabled = true;
            
            document.querySelectorAll('#exercicio-2 .opcao-exercicio').forEach(opt => {
                opt.classList.remove('selecionada', 'selected', 'option-correct', 'option-incorrect', 'resposta-correta', 'resposta-incorreta');
                opt.disabled = false;
                opt.style.pointerEvents = 'auto';
            });
            
            const feedback = document.querySelector('#exercicio-2 .feedback-message');
            if (feedback) feedback.remove();
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            verificarHTTPS();
            
            if (typeof ExercicioFala !== 'undefined') {
                window.exercicioFala = new ExercicioFala();
                window.exercicioFala.inicializar('en-US');
            }
        });
    </script>
</body>
</html>