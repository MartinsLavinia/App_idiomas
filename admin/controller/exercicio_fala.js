
// Sistema de Exercícios de Fala com Web Speech API
class ExercicioFala {
    constructor() {
        this.recognition = null;
        this.isRecording = false;
        this.exercicioId = null;
        this.fraseEsperada = null;
        this.idioma = 'en-US'; // Idioma padrão, pode ser configurado
        this.setupRecognition();
    }

    // Configurar o Web Speech API
    setupRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            this.mostrarErro('Seu navegador não suporta a Web Speech API. Use Chrome ou Edge.');
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        
        this.recognition.continuous = false;
        this.recognition.lang = this.idioma;
        this.recognition.interimResults = false;
        this.recognition.maxAlternatives = 1;

        this.recognition.onstart = () => {
            this.isRecording = true;
            this.atualizarInterface('gravando');
        };

        this.recognition.onresult = (event) => {
            const recognizedText = event.results[0][0].transcript;
            this.processarFala(recognizedText);
        };

        this.recognition.onerror = (event) => {
            this.isRecording = false;
            this.atualizarInterface('pronto');
            this.mostrarErro('Erro de reconhecimento de fala: ' + event.error);
        };

        this.recognition.onend = () => {
            this.isRecording = false;
            this.atualizarInterface('pronto');
        };
    }

    async inicializar() {
        return !!this.recognition;
    }

    iniciarGravacao(exercicioId) {
        if (!this.recognition) {
            this.mostrarErro('Sistema de reconhecimento de fala não está pronto.');
            return;
        }

        this.exercicioId = exercicioId;
        
        try {
            this.recognition.start();
        } catch (e) {
            if (e.name === 'InvalidStateError') {
                this.mostrarErro('O microfone já está ativo.');
            } else {
                this.mostrarErro('Erro ao iniciar a gravação: ' + e.message);
            }
        }
    }

    pararGravacao() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
        }
    }

    async processarFala(recognizedText) {
        this.atualizarInterface('processando');
        await this.enviarParaCorrecao(recognizedText);
    }

    async enviarParaCorrecao(recognizedText) {
        try {
            const response = await fetch('speech_exercise_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: this.exercicioId,
                    recognized_text: recognizedText
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.mostrarResultado(resultado.result);
            } else {
                this.mostrarErro(resultado.message || 'Erro ao analisar fala');
            }
            
        } catch (error) {
            this.mostrarErro('Erro de conexão ao enviar fala para correção');
        }
    }

    mostrarResultado(resultado) {
        const container = document.getElementById('resultado-audio');
        if (!container) return;

        const { expected, recognized, similarity_score, required_score, is_correct, message } = resultado;
        
        let statusClass = is_correct ? 'resultado-correto' : 'resultado-errado';
        let statusTexto = is_correct ? 'Correto!' : 'Incorreto';
        let icone = is_correct ? '✅' : '❌';
        
        let html = `
            <div class="resultado-feedback ${statusClass}">
                <div class="resultado-header">
                    <span class="resultado-icone">${icone}</span>
                    <h3>${statusTexto}</h3>
                    <div class="pontuacao">${(similarity_score * 100).toFixed(2)}% de similaridade</div>
                </div>
                
                <div class="transcricao-comparacao">
                    <div class="frase-esperada">
                        <strong>Frase Esperada:</strong> "${expected}"
                    </div>
                    <div class="frase-transcrita">
                        <strong>Você disse:</strong> "${recognized}"
                    </div>
                </div>
                
                <div class="feedback-mensagem">
                    <p>${message}</p>
                    <p>Pontuação mínima necessária: ${(required_score * 100).toFixed(2)}%</p>
                </div>

                <div class="acoes-resultado">
                    <button onclick="window.location.reload()" class="btn-tentar-novamente">
                        🔄 Tentar Novamente
                    </button>
                </div>
            </div>
        `;

        container.innerHTML = html;
        this.atualizarInterface('resultado');
    }

    mostrarErro(mensagem) {
        const container = document.getElementById('resultado-audio');
        if (container) {
            container.innerHTML = `<div class="alert alert-danger">${mensagem}</div>`;
        }
        this.atualizarInterface('pronto');
    }

    atualizarInterface(estado) {
        const btnGravar = document.getElementById('btn-gravar');
        const btnParar = document.getElementById('btn-parar');
        const statusGravacao = document.getElementById('status-gravacao');

        if (!btnGravar || !btnParar || !statusGravacao) return;

        switch (estado) {
            case 'pronto':
                btnGravar.disabled = false;
                btnParar.disabled = true;
                statusGravacao.textContent = 'Clique em gravar e fale a frase';
                break;
                
            case 'gravando':
                btnGravar.disabled = true;
                btnParar.disabled = false;
                statusGravacao.textContent = '🔴 Escutando... Fale agora!';
                break;
                
            case 'processando':
                btnGravar.disabled = true;
                btnParar.disabled = true;
                statusGravacao.textContent = '⚙️ Processando fala...';
                break;

            case 'resultado':
                btnGravar.disabled = false;
                btnParar.disabled = true;
                statusGravacao.textContent = 'Resultado exibido. Tente novamente ou avance.';
                break;
        }
    }
}


document.addEventListener('DOMContentLoaded', () => {
    window.exercicioFala = new ExercicioFala();
    
    const exercicioContainer = document.getElementById('exercicio-fala-container');
    const exercicioId = exercicioContainer ? exercicioContainer.dataset.exercicioId : null;
    
    if (exercicioId) {
        exercicioFala.inicializar().then(isReady => {
            if (isReady) {
                fetch(`speech_exercise_controller.php?exercicio_id=${exercicioId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const fraseElement = document.getElementById('frase-esperada-display');
                            if (fraseElement) {
                                fraseElement.textContent = data.frase_esperada;
                            }

                            exercicioFala.atualizarInterface('pronto');
                            
                            document.getElementById('btn-gravar').addEventListener('click', () => {
                                exercicioFala.iniciarGravacao(exercicioId);
                            });
                            
                            document.getElementById('btn-parar').addEventListener('click', () => {
                                exercicioFala.pararGravacao();
                            });
                            
                        } else {
                            exercicioFala.mostrarErro(data.message || 'Erro ao carregar dados do exercício.');
                        }
                    })
                    .catch(error => {
                        exercicioFala.mostrarErro('Erro de conexão ao carregar o exercício.');
                    });
            } else {
                exercicioFala.mostrarErro('O sistema de fala não está pronto.');
            }
        });
    } else {
        console.error('ID do exercício de fala não encontrado.');
    }
});

