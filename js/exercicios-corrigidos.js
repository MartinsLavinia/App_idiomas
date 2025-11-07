/**
 * Sistema JavaScript corrigido para exercícios
 * Corrige problemas de interface e integração
 */

class SistemaExercicios {
    constructor() {
        this.exercicioAtual = null;
        this.respostaSelecionada = null;
        this.audioAtual = null;
        this.gravacaoAtiva = false;
        this.mediaRecorder = null;
        this.audioChunks = [];
        
        this.inicializar();
    }

    inicializar() {
        this.configurarEventListeners();
        this.verificarSuporteAudio();
    }

    configurarEventListeners() {
        // Delegação de eventos para opções de exercícios
        document.addEventListener('click', (e) => {
            if (e.target.closest('.opcao-exercicio, .option-audio, .alternativa')) {
                this.selecionarOpcao(e);
            }
            
            if (e.target.closest('.btn-audio-play')) {
                this.reproduzirAudio(e);
            }
            
            if (e.target.closest('.btn-responder')) {
                this.responderExercicio(e);
            }
            
            if (e.target.closest('.microphone-btn')) {
                this.toggleGravacao(e);
            }
        });
    }

    verificarSuporteAudio() {
        if (!('MediaRecorder' in window) || !navigator.mediaDevices) {
            this.mostrarAviso('Seu navegador não suporta gravação de áudio. Use Chrome, Firefox ou Edge.');
        }
    }

    // ==================== EXERCÍCIOS DE LISTENING ====================

    async carregarExercicioListening(exercicioId) {
        try {
            const response = await fetch(`/App_idiomas/api/exercicios/listening.php?id=${exercicioId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }
            
            this.exercicioAtual = data.exercicio;
            this.renderizarExercicioListening(data.exercicio);
            
        } catch (error) {
            this.mostrarErro('Erro ao carregar exercício: ' + error.message);
        }
    }

    renderizarExercicioListening(exercicio) {
        const container = document.getElementById('exercicio-container');
        if (!container) return;

        container.innerHTML = `
            <div class="exercicio-listening" data-id="${exercicio.id}">
                <h3><i class="fas fa-headphones text-info"></i> Exercício de Listening</h3>
                
                <div class="audio-section mb-4">
                    <p class="mb-3">Ouça o áudio e escolha a resposta correta:</p>
                    
                    <div class="audio-player">
                        <audio controls class="w-100 mb-3">
                            <source src="${exercicio.audio_url}" type="audio/mpeg">
                            Seu navegador não suporta áudio.
                        </audio>
                        
                        <button class="btn btn-primary btn-audio-play" data-audio="${exercicio.audio_url}">
                            <i class="fas fa-play me-2"></i>Reproduzir Áudio
                        </button>
                    </div>
                </div>

                <div class="opcoes-container">
                    ${exercicio.opcoes.map((opcao, index) => `
                        <div class="opcao-exercicio option-audio" data-index="${index}">
                            <strong>${String.fromCharCode(65 + index)})</strong> ${opcao}
                        </div>
                    `).join('')}
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-success btn-lg btn-responder" disabled>
                        <i class="fas fa-check me-2"></i>Responder
                    </button>
                </div>

                <div class="feedback-container mt-3" style="display: none;"></div>
                
                ${exercicio.transcricao ? `
                    <div class="transcricao-container mt-3" style="display: none;">
                        <h6><i class="fas fa-file-text"></i> Transcrição:</h6>
                        <p class="text-muted">"${exercicio.transcricao}"</p>
                    </div>
                ` : ''}
                
                ${exercicio.dicas_compreensao ? `
                    <div class="dicas-container mt-3" style="display: none;">
                        <h6><i class="fas fa-lightbulb"></i> Dicas de Compreensão:</h6>
                        <p class="text-info">${exercicio.dicas_compreensao}</p>
                    </div>
                ` : ''}
            </div>
        `;
    }

    selecionarOpcao(event) {
        const opcao = event.target.closest('.opcao-exercicio, .option-audio, .alternativa');
        const container = opcao.closest('.exercicio-listening, .exercicio-container');
        
        // Limpar seleções anteriores
        container.querySelectorAll('.opcao-exercicio, .option-audio, .alternativa').forEach(opt => {
            opt.classList.remove('selecionada', 'selected');
        });
        
        // Selecionar atual
        opcao.classList.add('selecionada', 'selected');
        this.respostaSelecionada = parseInt(opcao.dataset.index);
        
        // Habilitar botão responder
        const btnResponder = container.querySelector('.btn-responder');
        if (btnResponder) {
            btnResponder.disabled = false;
        }
    }

    async responderExercicio(event) {
        if (this.respostaSelecionada === null) return;
        
        const container = event.target.closest('.exercicio-listening, .exercicio-container');
        const exercicioId = this.exercicioAtual?.id || container.dataset.id;
        
        // Desabilitar botão durante processamento
        const btnResponder = container.querySelector('.btn-responder');
        if (btnResponder) {
            btnResponder.disabled = true;
            btnResponder.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
        }
        
        try {
            const response = await fetch('/App_idiomas/api/exercicios/listening.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    exercicio_id: exercicioId,
                    resposta: this.respostaSelecionada
                })
            });
            
            const resultado = await response.json();
            
            if (!resultado.success) {
                throw new Error(resultado.message || 'Erro ao processar resposta');
            }
            
            this.aplicarFeedbackListening(container, resultado);
            
        } catch (error) {
            this.mostrarErro('Erro ao processar resposta: ' + error.message);
            
            // Reabilitar botão em caso de erro
            if (btnResponder) {
                btnResponder.disabled = false;
                btnResponder.innerHTML = '<i class="fas fa-check me-2"></i>Responder';
            }
        }
    }

    aplicarFeedbackListening(container, resultado) {
        const { correto, alternativa_correta_id, explicacao } = resultado;
        
        // Marcar resposta selecionada
        const opcaoSelecionada = container.querySelector(`[data-index="${this.respostaSelecionada}"]`);
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
        this.mostrarFeedback(container, resultado);
        
        // Desabilitar opções
        container.querySelectorAll('.opcao-exercicio, .option-audio').forEach(opt => {
            opt.style.pointerEvents = 'none';
        });
        
        // Desabilitar botão
        const btnResponder = container.querySelector('.btn-responder');
        if (btnResponder) {
            btnResponder.disabled = true;
        }
        
        // Mostrar elementos extras
        this.mostrarElementosExtras(container);
    }

    mostrarFeedback(container, resultado) {
        const feedbackContainer = container.querySelector('.feedback-container');
        if (!feedbackContainer) return;
        
        const { correto, explicacao, pontuacao } = resultado;
        
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
    }

    mostrarElementosExtras(container) {
        // Mostrar transcrição
        const transcricao = container.querySelector('.transcricao-container');
        if (transcricao) {
            transcricao.style.display = 'block';
        }
        
        // Mostrar dicas
        const dicas = container.querySelector('.dicas-container');
        if (dicas) {
            dicas.style.display = 'block';
        }
    }

    // ==================== EXERCÍCIOS DE FALA ====================

    async carregarExercicioFala(exercicioId) {
        try {
            const response = await fetch(`/App_idiomas/api/exercicios/fala.php?id=${exercicioId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }
            
            this.exercicioAtual = data.exercicio;
            this.renderizarExercicioFala(data.exercicio);
            
        } catch (error) {
            this.mostrarErro('Erro ao carregar exercício: ' + error.message);
        }
    }

    renderizarExercicioFala(exercicio) {
        const container = document.getElementById('exercicio-container');
        if (!container) return;

        container.innerHTML = `
            <div class="exercicio-fala" data-id="${exercicio.id}">
                <h3><i class="fas fa-microphone text-primary"></i> Exercício de Fala</h3>
                
                <div class="frase-section mb-4">
                    <p class="mb-3">Fale a seguinte frase em ${this.getNomeIdioma(exercicio.idioma)}:</p>
                    
                    <div class="frase-para-falar p-3 bg-light rounded text-center">
                        <strong>"${exercicio.frase_esperada}"</strong>
                    </div>
                    
                    ${exercicio.contexto ? `
                        <div class="contexto mt-2">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> ${exercicio.contexto}</small>
                        </div>
                    ` : ''}
                </div>

                <div class="gravacao-section text-center">
                    <button class="microphone-btn" data-exercicio-id="${exercicio.id}">
                        <i class="fas fa-microphone"></i>
                    </button>
                    
                    <div class="status-gravacao mt-3">
                        <span class="status-text">Clique no microfone para começar a gravar</span>
                    </div>
                    
                    <div class="resultado-fala mt-3" style="display: none;"></div>
                </div>
                
                ${exercicio.dicas_pronuncia ? `
                    <div class="dicas-pronuncia mt-4">
                        <h6><i class="fas fa-lightbulb"></i> Dicas de Pronúncia:</h6>
                        <p class="text-info">${exercicio.dicas_pronuncia}</p>
                    </div>
                ` : ''}
                
                ${exercicio.palavras_chave && exercicio.palavras_chave.length > 0 ? `
                    <div class="palavras-chave mt-3">
                        <h6><i class="fas fa-key"></i> Palavras-chave:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            ${exercicio.palavras_chave.map(palavra => `
                                <span class="badge bg-secondary">${palavra}</span>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    async toggleGravacao(event) {
        const btn = event.target.closest('.microphone-btn');
        const exercicioId = btn.dataset.exercicioId;
        
        if (!this.gravacaoAtiva) {
            await this.iniciarGravacao(exercicioId);
        } else {
            this.pararGravacao();
        }
    }

    async iniciarGravacao(exercicioId) {
        try {
            this.atualizarStatusGravacao('Solicitando permissão do microfone...');
            
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];
            
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };
            
            this.mediaRecorder.onstop = () => {
                this.processarAudioGravado(exercicioId);
                stream.getTracks().forEach(track => track.stop());
            };
            
            this.mediaRecorder.start();
            this.gravacaoAtiva = true;
            
            this.atualizarStatusGravacao('🔴 Gravando... Fale agora!');
            this.atualizarBotaoMicrofone(true);
            
        } catch (error) {
            this.tratarErroGravacao(error);
        }
    }

    pararGravacao() {
        if (this.mediaRecorder && this.gravacaoAtiva) {
            this.mediaRecorder.stop();
            this.gravacaoAtiva = false;
            this.atualizarStatusGravacao('Processando áudio...');
            this.atualizarBotaoMicrofone(false);
        }
    }

    async processarAudioGravado(exercicioId) {
        try {
            this.atualizarStatusGravacao('Processando áudio...');
            
            // Simular transcrição (em produção, usar API de Speech-to-Text)
            const fraseTranscrita = await this.simularTranscricao();
            
            const response = await fetch('/App_idiomas/api/exercicios/fala.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    exercicio_id: exercicioId,
                    frase_transcrita: fraseTranscrita
                })
            });
            
            const resultado = await response.json();
            
            if (!resultado.success) {
                throw new Error(resultado.message || 'Erro ao processar áudio');
            }
            
            this.mostrarResultadoFala(resultado);
            
        } catch (error) {
            this.mostrarErro('Erro ao processar áudio: ' + error.message);
            this.atualizarStatusGravacao('Erro no processamento');
        }
    }

    async simularTranscricao() {
        // Simulação para demonstração
        // Em produção, integrar com Google Speech-to-Text, Azure Speech, etc.
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve(this.exercicioAtual?.frase_esperada || 'hello how are you');
            }, 2000);
        });
    }

    mostrarResultadoFala(resultado) {
        const container = document.querySelector('.resultado-fala');
        if (!container) return;
        
        const { correto, status, pontuacao, feedback_detalhado } = resultado;
        
        let statusClass = 'alert-success';
        let statusIcon = 'fa-check-circle';
        
        if (status === 'parcialmente_correto') {
            statusClass = 'alert-warning';
            statusIcon = 'fa-exclamation-circle';
        } else if (status === 'incorreto') {
            statusClass = 'alert-danger';
            statusIcon = 'fa-times-circle';
        }
        
        container.innerHTML = `
            <div class="alert ${statusClass}">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas ${statusIcon} me-2"></i>
                    <strong>${feedback_detalhado.explicacao}</strong>
                    <span class="ms-auto badge bg-primary">${pontuacao}%</span>
                </div>
                
                ${feedback_detalhado.sugestoes && feedback_detalhado.sugestoes.length > 0 ? `
                    <div class="mt-3">
                        <h6>Sugestões:</h6>
                        <ul class="mb-0">
                            ${feedback_detalhado.sugestoes.map(sugestao => `<li>${sugestao}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
                
                ${feedback_detalhado.palavras_incorretas && feedback_detalhado.palavras_incorretas.length > 0 ? `
                    <div class="mt-3">
                        <h6>Palavras para praticar:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            ${feedback_detalhado.palavras_incorretas.map(palavra => `
                                <span class="badge bg-warning text-dark">${palavra.esperada}</span>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        container.style.display = 'block';
        this.atualizarStatusGravacao('Pronto para nova gravação');
    }

    // ==================== MÉTODOS AUXILIARES ====================

    atualizarStatusGravacao(texto) {
        const status = document.querySelector('.status-text');
        if (status) {
            status.textContent = texto;
        }
    }

    atualizarBotaoMicrofone(gravando) {
        const btn = document.querySelector('.microphone-btn');
        if (btn) {
            btn.classList.toggle('gravando', gravando);
        }
    }

    tratarErroGravacao(error) {
        let mensagem = 'Erro ao acessar microfone: ';
        
        switch (error.name) {
            case 'NotAllowedError':
                mensagem += 'Permissão negada. Permita o acesso ao microfone.';
                break;
            case 'NotFoundError':
                mensagem += 'Microfone não encontrado. Conecte um microfone.';
                break;
            default:
                mensagem += error.message;
        }
        
        this.mostrarErro(mensagem);
        this.atualizarStatusGravacao('Erro no microfone');
    }

    reproduzirAudio(event) {
        const btn = event.target.closest('.btn-audio-play');
        const audioUrl = btn.dataset.audio;
        
        if (this.audioAtual) {
            this.audioAtual.pause();
        }
        
        this.audioAtual = new Audio(audioUrl);
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Carregando...';
        
        this.audioAtual.onloadeddata = () => {
            btn.innerHTML = '<i class="fas fa-pause me-2"></i>Reproduzindo...';
            this.audioAtual.play();
        };
        
        this.audioAtual.onended = () => {
            btn.innerHTML = '<i class="fas fa-play me-2"></i>Reproduzir Áudio';
        };
        
        this.audioAtual.onerror = () => {
            btn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erro no áudio';
            this.mostrarErro('Erro ao reproduzir áudio');
        };
    }

    getNomeIdioma(codigo) {
        const idiomas = {
            'en-us': 'inglês',
            'pt-br': 'português',
            'es-es': 'espanhol',
            'fr-fr': 'francês',
            'de-de': 'alemão'
        };
        return idiomas[codigo] || codigo;
    }

    mostrarErro(mensagem) {
        console.error(mensagem);
        
        // Criar toast de erro
        const toast = document.createElement('div');
        toast.className = 'toast-erro';
        toast.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${mensagem}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    mostrarAviso(mensagem) {
        console.warn(mensagem);
        
        const aviso = document.createElement('div');
        aviso.className = 'toast-aviso';
        aviso.innerHTML = `
            <div class="alert alert-warning alert-dismissible">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${mensagem}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(aviso);
    }
}

// Inicializar sistema quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.sistemaExercicios = new SistemaExercicios();
});

// Expor para uso global
window.SistemaExercicios = SistemaExercicios;