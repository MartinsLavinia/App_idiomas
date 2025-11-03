// Sistema de Exercícios de Fala com Gravação de Áudio (Usando MediaRecorder)
class ExercicioFala {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.exercicioId = null;
        this.fraseEsperada = null;
        this.idioma = 'en-US';
        this.stream = null; // Para manter a referência ao stream do microfone

        this.suporteGravacao = ('MediaRecorder' in window) && ('navigator' in window && 'mediaDevices' in navigator);

        if (!this.suporteGravacao) {
            this.mostrarErro('Seu navegador não suporta gravação de áudio. Use um navegador moderno como Chrome, Firefox ou Edge.');
        }
    }

    // Inicializa o sistema de áudio
    async inicializar(idioma) {
        if (!this.suporteGravacao) return false;
        if (idioma) {
            this.idioma = idioma;
        }
        this.atualizarInterface('pronto');
        return true;
    }

    // Trata erros de gravação
    tratarErroGravacao(erro) {
        let mensagem = '';
        
        if (erro.message && erro.message.includes('NotFoundError')) {
            mensagem = `
                <strong>❌ Nenhum microfone encontrado</strong><br>
                <div class="mt-2">
                    <small>Soluções:</small><br>
                    <small>• Conecte um microfone ou headset</small><br>
                    <small>• Verifique se o microfone está funcionando</small><br>
                    <small>• Teste em: <a href="teste_microfone.html" target="_blank">Teste de Microfone</a></small>
                </div>
            `;
        } else {
            switch(erro.name) {
                case 'NotAllowedError':
                case 'PermissionDeniedError':
                    mensagem = `
                        <strong>❌ Permissão negada</strong><br>
                        <small>Clique no ícone 🔒 na barra de endereços e permita o microfone</small>
                    `;
                    break;
                case 'NotFoundError':
                case 'DevicesNotFoundError':
                    mensagem = `
                        <strong>❌ Microfone não encontrado</strong><br>
                        <small>Conecte um microfone e recarregue a página</small>
                    `;
                    break;
                default:
                    mensagem = `<strong>❌ Erro:</strong> ${erro.message || erro.name}`;
            }
        }
        
        this.mostrarErro(mensagem);
        this.atualizarInterface('erro');
    }

    // Inicia a gravação do áudio
    async iniciarGravacao(exercicioId, fraseEsperada, idioma) {
        if (!this.suporteGravacao || this.isRecording) return;

        this.exercicioId = exercicioId;
        this.fraseEsperada = fraseEsperada;
        if (idioma) this.idioma = idioma;

        this.audioChunks = [];
        this.atualizarInterface('iniciando');

        try {
            // Primeiro verificar se há dispositivos de áudio
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioInputs = devices.filter(device => device.kind === 'audioinput');
            
            if (audioInputs.length === 0) {
                throw new Error('NotFoundError: Nenhum dispositivo de áudio encontrado');
            }
            
            console.log('Dispositivos de áudio encontrados:', audioInputs.length);
            
            // Solicitar permissão com configurações básicas
            const constraints = { audio: true };
            
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('Permissão concedida, iniciando gravação...');
            
            this.mediaRecorder = new MediaRecorder(this.stream);

            this.mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.isRecording = false;
                this.atualizarInterface('processando');
                
                if (this.audioChunks.length > 0) {
                    const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    this.enviarAudioParaBackend(audioBlob);
                } else {
                    this.mostrarErro('Nenhum áudio foi gravado. Tente novamente.');
                    this.atualizarInterface('pronto');
                }
                
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
            };

            this.mediaRecorder.start();
            this.isRecording = true;
            this.atualizarInterface('gravando');

        } catch (e) {
            console.error('Erro ao iniciar gravação:', e);
            this.tratarErroGravacao(e);
        }
    }

    // Para a gravação do áudio
    pararGravacao() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
        }
    }

    // Envia o áudio para o backend
    async enviarAudioParaBackend(audioBlob) {
        const formData = new FormData();
        formData.append('exercicio_id', this.exercicioId);
        formData.append('frase_esperada', this.fraseEsperada);
        formData.append('idioma', this.idioma);
        formData.append('audio_file', audioBlob, 'gravacao.webm');

        try {
            const response = await fetch('../../admin/controller/correcao_audio.php', {
                method: 'POST',
                body: formData
            });

            const resultado = await response.json();
            if (resultado.success) {
                this.mostrarResultado(resultado.resultado);
            } else {
                this.mostrarErro(resultado.message || 'Erro ao processar o áudio no servidor.');
            }
        } catch (error) {
            console.error('Erro ao enviar áudio:', error);
            this.mostrarErro('Erro de conexão ao enviar sua resposta. Verifique sua internet.');
            this.atualizarInterface('pronto');
        }
    }

    // Funções de compatibilidade para o HTML existente
    iniciarReconhecimento(exercicioId, fraseEsperada, idioma) {
        this.iniciarGravacao(exercicioId, fraseEsperada, idioma);
    }

    pararReconhecimento() {
        this.pararGravacao();
    }

    // Lógica de Correção de Exercício de Listening
    async processarRespostaAudio(exercicioId, respostaSelecionada, tipoExercicio) {
        const container = document.getElementById(`exercicio-${exercicioId}`);
        if (!container) return;

        container.querySelectorAll('.option-audio').forEach(opt => opt.disabled = true);

        try {
            const response = await fetch('processar_exercicio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: exercicioId,
                    resposta: respostaSelecionada,
                    tipo_exercicio: tipoExercicio
                })
            });

            const resultado = await response.json();
            if (resultado.success) {
                this.aplicarFeedbackVisual(container, resultado, respostaSelecionada);
            } else {
                alert(resultado.message || 'Erro ao processar exercício.');
            }
        } catch (error) {
            console.error('Erro ao processar resposta de áudio:', error);
            alert('Erro de conexão ao processar resposta.');
            container.querySelectorAll('.option-audio').forEach(opt => opt.disabled = false);
        }
    }

    // Aplica o feedback visual para exercícios de listening
    aplicarFeedbackVisual(container, resultado, respostaSelecionada) {
        const { correto, alternativa_correta_id, explicacao } = resultado;

        const opcaoSelecionada = container.querySelector(`[data-index="${respostaSelecionada}"]`);
        if (opcaoSelecionada) {
            opcaoSelecionada.classList.add(correto ? 'option-correct' : 'option-incorrect');
        }

        if (!correto && alternativa_correta_id !== null) {
            const opcaoCorreta = container.querySelector(`[data-index="${alternativa_correta_id}"]`);
            if (opcaoCorreta) {
                opcaoCorreta.classList.add('option-correct');
            }
        }

        const feedbackContainer = container.querySelector('.feedback-message');
        if (feedbackContainer && explicacao) {
            feedbackContainer.innerHTML = explicacao;
            feedbackContainer.style.display = 'block';
        }

        container.querySelectorAll('.option-audio').forEach(opt => opt.disabled = true);
    }

    // Funções de UI
    mostrarResultado(resultado) {
        const container = document.getElementById('resultado-audio');
        if (!container) return;
        // ... (código de mostrar resultado de fala, que depende do backend)
        this.atualizarInterface('pronto');
    }

    mostrarErro(mensagem) {
        const container = document.getElementById('resultado-audio');
        if (!container) return;
        container.innerHTML = `<div class="resultado-feedback resultado-erro"><p>${mensagem}</p></div>`;
        container.style.display = 'block';
        this.atualizarInterface('pronto');
    }

    atualizarInterface(estado) {
        const btnStart = document.getElementById('btn-start-speech');
        const btnStop = document.getElementById('btn-stop-speech');
        const statusText = document.getElementById('speech-status');
        const microphoneBtn = document.querySelector('.microphone-btn');

        // Resetar estados dos botões
        if (btnStart) btnStart.disabled = true;
        if (btnStop) btnStop.disabled = true;
        if (microphoneBtn) {
            microphoneBtn.disabled = true;
            microphoneBtn.classList.remove('listening');
        }

        switch (estado) {
            case 'pronto':
                if (btnStart) btnStart.disabled = false;
                if (microphoneBtn) microphoneBtn.disabled = false;
                if (statusText) {
                    statusText.textContent = 'Pronto para gravar.';
                    statusText.className = 'speech-status speech-ready';
                }
                break;
                
            case 'iniciando':
                if (statusText) {
                    statusText.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Solicitando permissão do microfone...
                        </div>
                    `;
                    statusText.className = 'speech-status speech-requesting';
                }
                break;
                
            case 'gravando':
                if (btnStop) btnStop.disabled = false;
                if (microphoneBtn) {
                    microphoneBtn.classList.add('listening');
                    microphoneBtn.disabled = false;
                }
                if (statusText) {
                    statusText.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-microphone text-danger me-2"></i>
                            <strong>Gravando... Fale agora!</strong>
                        </div>
                    `;
                    statusText.className = 'speech-status speech-listening';
                }
                break;
                
            case 'processando':
                if (statusText) {
                    statusText.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Processando áudio...
                        </div>
                    `;
                    statusText.className = 'speech-status speech-processing';
                }
                break;
                
            case 'erro':
                if (btnStart) btnStart.disabled = false;
                if (microphoneBtn) microphoneBtn.disabled = false;
                if (statusText) {
                    statusText.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Erro. Tente novamente.
                        </div>
                    `;
                    statusText.className = 'speech-status speech-error';
                }
                break;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.exercicioFala = new ExercicioFala();
    // O HTML deve chamar `window.exercicioFala.inicializar()`
});

// Expor a classe para uso global
window.ExercicioFala = ExercicioFala;
