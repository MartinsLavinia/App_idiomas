class ExercicioFala {
    constructor() {
        this.recognition = null;
        this.isRecording = false;
        this.isSupported = this.checkSupport();
        this.currentExercise = null;
        this.attempts = 0;
        this.maxAttempts = 3;
    }

    /**
     * Verifica se o navegador suporta Web Speech API
     */
    checkSupport() {
        return 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
    }

    /**
     * Inicializa o reconhecimento de fala
     */
    initRecognition() {
        if (!this.isSupported) {
            console.warn('Web Speech API não suportada neste navegador');
            return false;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        
        // Configurações do reconhecimento
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        this.recognition.lang = 'en-US'; // Pode ser alterado dinamicamente
        this.recognition.maxAlternatives = 3;

        // Event listeners
        this.recognition.onstart = () => this.onRecordingStart();
        this.recognition.onresult = (event) => this.onRecordingResult(event);
        this.recognition.onerror = (event) => this.onRecordingError(event);
        this.recognition.onend = () => this.onRecordingEnd();

        return true;
    }

    /**
     * Inicia a gravação
     */
    startRecording(exerciseData) {
        if (!this.isSupported) {
            this.showFallbackInterface(exerciseData);
            return;
        }

        if (!this.recognition) {
            if (!this.initRecognition()) {
                this.showFallbackInterface(exerciseData);
                return;
            }
        }

        this.currentExercise = exerciseData;
        this.attempts++;

        // Configurar idioma baseado no exercício
        if (exerciseData.idioma) {
            this.recognition.lang = exerciseData.idioma === 'Ingles' ? 'en-US' : 'ja-JP';
        }

        try {
            this.recognition.start();
        } catch (error) {
            console.error('Erro ao iniciar reconhecimento:', error);
            this.showError('Erro ao iniciar gravação. Tente novamente.');
        }
    }

    /**
     * Para a gravação
     */
    stopRecording() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
        }
    }

    /**
     * Callback quando a gravação inicia
     */
    onRecordingStart() {
        this.isRecording = true;
        this.updateUI('recording');
        console.log('Gravação iniciada');
    }

    /**
     * Callback quando há resultado do reconhecimento
     */
    onRecordingResult(event) {
        const results = event.results;
        const transcript = results[0][0].transcript.toLowerCase().trim();
        const confidence = results[0][0].confidence;

        console.log('Resultado:', transcript, 'Confiança:', confidence);

        // Processar resultado
        this.processResult(transcript, confidence);
    }

    /**
     * Callback quando há erro no reconhecimento
     */
    onRecordingError(event) {
        console.error('Erro no reconhecimento:', event.error);
        
        let errorMessage = 'Erro no reconhecimento de voz.';
        
        switch (event.error) {
            case 'no-speech':
                errorMessage = 'Nenhuma fala detectada. Tente falar mais alto.';
                break;
            case 'audio-capture':
                errorMessage = 'Erro ao capturar áudio. Verifique seu microfone.';
                break;
            case 'not-allowed':
                errorMessage = 'Permissão de microfone negada. Permita o acesso ao microfone.';
                break;
            case 'network':
                errorMessage = 'Erro de rede. Verifique sua conexão.';
                break;
        }

        this.showError(errorMessage);
        this.updateUI('error');
    }

    /**
     * Callback quando a gravação termina
     */
    onRecordingEnd() {
        this.isRecording = false;
        console.log('Gravação finalizada');
    }

    /**
     * Processa o resultado do reconhecimento
     */
    processResult(transcript, confidence) {
        if (!this.currentExercise) return;

        const expectedPhrase = this.currentExercise.frase_esperada.toLowerCase();
        const similarity = this.calculateSimilarity(transcript, expectedPhrase);
        const threshold = this.currentExercise.tolerancia_erro || 0.8;

        console.log('Similaridade:', similarity, 'Threshold:', threshold);

        const isCorrect = similarity >= threshold && confidence >= 0.7;

        this.showResult({
            transcript: transcript,
            expected: expectedPhrase,
            similarity: similarity,
            confidence: confidence,
            isCorrect: isCorrect,
            attempts: this.attempts
        });
    }

    /**
     * Calcula similaridade entre duas strings
     */
    calculateSimilarity(str1, str2) {
        // Normalizar strings
        const normalize = (str) => str.toLowerCase().replace(/[^\w\s]/g, '').trim();
        const s1 = normalize(str1);
        const s2 = normalize(str2);

        // Verificar palavras-chave se disponíveis
        if (this.currentExercise.palavras_chave) {
            const keywords = this.currentExercise.palavras_chave;
            const foundKeywords = keywords.filter(keyword => 
                s1.includes(keyword.toLowerCase())
            );
            const keywordScore = foundKeywords.length / keywords.length;
            
            // Se encontrou a maioria das palavras-chave, considerar como boa similaridade
            if (keywordScore >= 0.7) {
                return Math.max(keywordScore, this.levenshteinSimilarity(s1, s2));
            }
        }

        return this.levenshteinSimilarity(s1, s2);
    }

    /**
     * Calcula similaridade usando distância de Levenshtein
     */
    levenshteinSimilarity(str1, str2) {
        const matrix = [];
        const len1 = str1.length;
        const len2 = str2.length;

        for (let i = 0; i <= len2; i++) {
            matrix[i] = [i];
        }

        for (let j = 0; j <= len1; j++) {
            matrix[0][j] = j;
        }

        for (let i = 1; i <= len2; i++) {
            for (let j = 1; j <= len1; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }

        const distance = matrix[len2][len1];
        const maxLength = Math.max(len1, len2);
        return maxLength === 0 ? 1 : (maxLength - distance) / maxLength;
    }

    /**
     * Atualiza a interface do usuário
     */
    updateUI(state) {
        const microfoneIcon = document.getElementById('microfoneIcon');
        const btnGravar = document.getElementById('btnGravar');
        const status = document.getElementById('statusGravacao');

        if (!microfoneIcon || !btnGravar || !status) return;

        switch (state) {
            case 'recording':
                microfoneIcon.className = 'fas fa-microphone fa-5x text-danger';
                microfoneIcon.style.animation = 'pulse 1s infinite';
                btnGravar.disabled = true;
                btnGravar.innerHTML = '<i class="fas fa-stop me-2"></i>Gravando...';
                status.innerHTML = '<div class="text-info"><i class="fas fa-circle text-danger me-2"></i>Ouvindo... Fale agora!</div>';
                break;

            case 'processing':
                microfoneIcon.className = 'fas fa-microphone fa-5x text-warning';
                microfoneIcon.style.animation = 'none';
                btnGravar.innerHTML = '<i class="fas fa-cog fa-spin me-2"></i>Processando...';
                status.innerHTML = '<div class="text-warning"><i class="fas fa-cog fa-spin me-2"></i>Processando sua fala...</div>';
                break;

            case 'success':
                microfoneIcon.className = 'fas fa-microphone fa-5x text-success';
                microfoneIcon.style.animation = 'none';
                btnGravar.innerHTML = '<i class="fas fa-check me-2"></i>Sucesso!';
                btnGravar.disabled = false;
                break;

            case 'error':
                microfoneIcon.className = 'fas fa-microphone fa-5x text-danger';
                microfoneIcon.style.animation = 'none';
                btnGravar.innerHTML = '<i class="fas fa-microphone me-2"></i>Tentar Novamente';
                btnGravar.disabled = false;
                break;

            case 'ready':
            default:
                microfoneIcon.className = 'fas fa-microphone fa-5x text-muted';
                microfoneIcon.style.animation = 'none';
                btnGravar.innerHTML = '<i class="fas fa-microphone me-2"></i>Clique para Gravar';
                btnGravar.disabled = false;
                status.innerHTML = '';
                break;
        }
    }

    /**
     * Mostra o resultado do exercício
     */
    showResult(result) {
        this.updateUI('processing');

        setTimeout(() => {
            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackContent = document.getElementById('feedbackContent');

            if (result.isCorrect) {
                this.updateUI('success');
                feedbackContainer.className = 'feedback-container feedback-success';
                feedbackContent.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Excelente!</strong> Sua pronúncia está correta.
                    <br><small>Você disse: "${result.transcript}"</small>
                    <br><small>Precisão: ${Math.round(result.similarity * 100)}%</small>
                `;

                // Habilitar botão próximo
                document.getElementById('btnEnviarResposta').style.display = 'none';
                document.getElementById('btnProximoExercicio').style.display = 'inline-block';

            } else {
                this.updateUI('error');
                feedbackContainer.className = 'feedback-container feedback-error';
                
                let feedbackText = `
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Tente novamente.</strong> Sua pronúncia precisa melhorar.
                    <br><small>Você disse: "${result.transcript}"</small>
                    <br><small>Esperado: "${result.expected}"</small>
                    <br><small>Precisão: ${Math.round(result.similarity * 100)}%</small>
                `;

                if (this.attempts < this.maxAttempts) {
                    feedbackText += `<br><small><i class="fas fa-lightbulb me-1"></i><strong>Dica:</strong> Fale mais devagar e articule bem as palavras.</small>`;
                } else {
                    feedbackText += `<br><small><i class="fas fa-info-circle me-1"></i>Você pode continuar para o próximo exercício.</small>`;
                    document.getElementById('btnEnviarResposta').style.display = 'none';
                    document.getElementById('btnProximoExercicio').style.display = 'inline-block';
                }

                feedbackContent.innerHTML = feedbackText;
            }

            feedbackContainer.style.display = 'block';

            // Enviar resultado para o servidor
            this.sendResultToServer(result);

        }, 1000);
    }

    /**
     * Mostra interface alternativa quando Web Speech API não está disponível
     */
    showFallbackInterface(exerciseData) {
        const container = document.getElementById('conteudoExercicio');
        
        container.innerHTML = `
            <h5 class="mb-4">${exerciseData.pergunta || 'Exercício de Pronúncia'}</h5>
            <div class="text-center">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Seu navegador não suporta reconhecimento de voz automático.
                </div>
                <div class="mb-4">
                    <i class="fas fa-volume-up fa-5x text-primary"></i>
                </div>
                <p class="lead mb-4">Frase para pronunciar:</p>
                <p class="fs-4 fw-bold text-primary">"${exerciseData.frase_esperada}"</p>
                
                ${exerciseData.pronuncia_fonetica ? `
                    <p class="text-muted mb-4">
                        <small>Pronúncia: ${exerciseData.pronuncia_fonetica}</small>
                    </p>
                ` : ''}
                
                <div class="mb-4">
                    <button class="btn btn-primary me-2" onclick="playAudioExample()">
                        <i class="fas fa-play me-2"></i>Ouvir Exemplo
                    </button>
                    <button class="btn btn-success" onclick="markSpeechComplete()">
                        <i class="fas fa-check me-2"></i>Pratiquei a Pronúncia
                    </button>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Dica:</strong> Pratique a pronúncia em voz alta e clique em "Pratiquei a Pronúncia" quando estiver pronto.
                    </small>
                </div>
            </div>
            
            <div class="feedback-container" id="feedbackContainer">
                <div id="feedbackContent"></div>
            </div>
        `;
    }

    /**
     * Mostra mensagem de erro
     */
    showError(message) {
        const status = document.getElementById('statusGravacao');
        if (status) {
            status.innerHTML = `<div class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>${message}</div>`;
        }
    }

    /**
     * Envia resultado para o servidor
     */
    sendResultToServer(result) {
        if (!exercicioAtual) return;

        fetch('processar_exercicio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: result.isCorrect ? 'fala_processada' : 'fala_incorreta',
                tipo_exercicio: 'fala',
                detalhes: {
                    transcript: result.transcript,
                    similarity: result.similarity,
                    confidence: result.confidence,
                    attempts: result.attempts
                }
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resultado enviado:', data);
        })
        .catch(error => {
            console.error('Erro ao enviar resultado:', error);
        });
    }

    /**
     * Reseta o estado do exercício
     */
    reset() {
        this.currentExercise = null;
        this.attempts = 0;
        this.updateUI('ready');
    }
}

// Instância global do exercício de fala
const exercicioFala = new ExercicioFala();

// Funções globais para integração com o HTML
function iniciarGravacao() {
    if (!exercicioAtual || exercicioAtual.tipo_exercicio !== 'fala') {
        console.error('Exercício de fala não encontrado');
        return;
    }

    try {
        const conteudo = JSON.parse(exercicioAtual.conteudo);
        exercicioFala.startRecording(conteudo);
    } catch (error) {
        console.error('Erro ao parsear conteúdo do exercício:', error);
        exercicioFala.showError('Erro ao carregar exercício de fala.');
    }
}

function pararGravacao() {
    exercicioFala.stopRecording();
}

function playAudioExample() {
    // Implementar reprodução de áudio de exemplo
    console.log('Reproduzindo áudio de exemplo...');
    // Aqui seria implementada a reprodução do áudio de exemplo
}

function markSpeechComplete() {
    // Marcar exercício como completo para navegadores sem suporte
    const feedbackContainer = document.getElementById('feedbackContainer');
    const feedbackContent = document.getElementById('feedbackContent');
    
    feedbackContainer.className = 'feedback-container feedback-success';
    feedbackContent.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        <strong>Ótimo!</strong> Continue praticando sua pronúncia.
    `;
    feedbackContainer.style.display = 'block';
    
    // Habilitar botão próximo
    document.getElementById('btnEnviarResposta').style.display = 'none';
    document.getElementById('btnProximoExercicio').style.display = 'inline-block';
    
    // Enviar resultado simulado para o servidor
    if (exercicioAtual) {
        fetch('processar_exercicio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: 'fala_processada',
                tipo_exercicio: 'fala'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resultado enviado:', data);
        })
        .catch(error => {
            console.error('Erro ao enviar resultado:', error);
        });
    }
}

// CSS para animação de pulse
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
