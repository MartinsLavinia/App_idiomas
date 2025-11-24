// Sistema de Correção Visual para Exercícios
// Corrige problemas de feedback visual em exercícios de listening e múltipla escolha

class CorrecaoVisualExercicios {
    constructor() {
        this.exerciciosProcessados = new Set();
        this.inicializar();
    }

    inicializar() {
        // Aguardar o DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.configurarEventos());
        } else {
            this.configurarEventos();
        }
    }

    configurarEventos() {
        // Interceptar cliques em alternativas de exercícios
        document.addEventListener('click', (event) => {
            const alternativa = event.target.closest('.option-audio, .alternativa, .opcao-exercicio');
            if (alternativa) {
                this.processarClique(alternativa, event);
            }
        });

        // Interceptar submissões de formulários de exercícios
        document.addEventListener('submit', (event) => {
            if (event.target.classList.contains('form-exercicio')) {
                this.interceptarSubmissao(event);
            }
        });
    }

    processarClique(alternativa, event) {
        const container = alternativa.closest('.exercicio-container, .exercicio-teste, [id^="exercicio-"]');
        if (!container) return;

        const exercicioId = this.extrairExercicioId(container);
        if (!exercicioId) return;

        // Marcar alternativa como selecionada
        this.marcarAlternativaSelecionada(alternativa, container);

        // Se for um clique direto para responder (não apenas selecionar)
        if (alternativa.hasAttribute('data-responder-direto')) {
            event.preventDefault();
            const indiceResposta = this.extrairIndiceResposta(alternativa);
            this.processarRespostaExercicio(exercicioId, indiceResposta, container);
        }
    }

    interceptarSubmissao(event) {
        const form = event.target;
        const container = form.closest('.exercicio-container, [id^="exercicio-"]');
        const exercicioId = this.extrairExercicioId(container);
        const respostaSelecionada = this.obterRespostaSelecionada(container);

        if (respostaSelecionada === null) {
            // Não impedir o envio, apenas mostrar aviso
            console.warn('Nenhuma resposta selecionada detectada');
            return; // Deixar o formulário processar normalmente
        }
        
        event.preventDefault();
        if (exercicioId && respostaSelecionada !== null) {
            this.processarRespostaExercicio(exercicioId, respostaSelecionada, container);
        }
    }

    extrairExercicioId(container) {
        // Tentar várias formas de extrair o ID do exercício
        if (container.id && container.id.includes('exercicio-')) {
            return container.id.replace('exercicio-', '');
        }
        
        const dataId = container.getAttribute('data-exercicio-id');
        if (dataId) return dataId;

        const inputId = container.querySelector('input[name="exercicio_id"]');
        if (inputId) return inputId.value;

        return null;
    }

    extrairIndiceResposta(alternativa) {
        // Tentar várias formas de extrair o índice da resposta
        const dataIndex = alternativa.getAttribute('data-index');
        if (dataIndex !== null) return parseInt(dataIndex);

        const dataValue = alternativa.getAttribute('data-value');
        if (dataValue !== null) return parseInt(dataValue);

        // Buscar pelo índice na lista de irmãos
        const alternativas = alternativa.parentElement.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio');
        return Array.from(alternativas).indexOf(alternativa);
    }

    marcarAlternativaSelecionada(alternativa, container) {
        // Remover seleção anterior
        container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio').forEach(opt => {
            opt.classList.remove('selecionada', 'selected', 'active');
            opt.removeAttribute('data-clicked');
        });

        // Marcar como selecionada
        alternativa.classList.add('selecionada', 'selected', 'active');
        alternativa.setAttribute('data-clicked', 'true');
        
        // Habilitar botão de responder se existir
        const btnResponder = container.querySelector('.btn-responder, button[type="submit"]');
        if (btnResponder) {
            btnResponder.disabled = false;
        }
    }

    obterRespostaSelecionada(container) {
        const selecionada = container.querySelector('.selecionada, .selected, input[type="radio"]:checked');
        if (!selecionada) {
            // Se não encontrou seleção, verificar se há alguma opção clicada recentemente
            const opcoes = container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio');
            for (let i = 0; i < opcoes.length; i++) {
                if (opcoes[i].classList.contains('active') || opcoes[i].getAttribute('data-clicked') === 'true') {
                    return i;
                }
            }
            return null;
        }

        if (selecionada.type === 'radio') {
            return parseInt(selecionada.value);
        }

        return this.extrairIndiceResposta(selecionada);
    }

    async processarRespostaExercicio(exercicioId, respostaSelecionada, container) {
        // Evitar processamento duplo
        const chaveProcessamento = `${exercicioId}-${respostaSelecionada}`;
        if (this.exerciciosProcessados.has(chaveProcessamento)) {
            return;
        }
        this.exerciciosProcessados.add(chaveProcessamento);

        // Desabilitar todas as alternativas
        this.desabilitarAlternativas(container);

        // Mostrar loading
        this.mostrarLoading(container);

        try {
            const response = await fetch('admin/controller/processar_exercicio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: exercicioId,
                    resposta: respostaSelecionada,
                    tipo_exercicio: this.detectarTipoExercicio(container)
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.aplicarFeedbackVisual(container, resultado, respostaSelecionada);
                this.mostrarExplicacao(container, resultado);
            } else {
                this.mostrarErro(container, resultado.message || 'Erro ao processar exercício.');
            }
        } catch (error) {
            console.error('Erro ao processar resposta:', error);
            this.mostrarErro(container, 'Erro de conexão ao processar resposta.');
            this.reabilitarAlternativas(container);
            this.exerciciosProcessados.delete(chaveProcessamento);
        } finally {
            this.ocultarLoading(container);
        }
    }

    detectarTipoExercicio(container) {
        // Detectar tipo baseado em classes CSS ou atributos
        if (container.classList.contains('exercicio-listening') || 
            container.querySelector('.audio-player, audio')) {
            return 'listening';
        }
        
        if (container.classList.contains('exercicio-fala') || 
            container.querySelector('.microphone-btn, .btn-fala')) {
            return 'fala';
        }

        return 'multipla_escolha'; // padrão
    }

    aplicarFeedbackVisual(container, resultado, respostaSelecionada) {
        const { correto, alternativa_correta_id } = resultado;
        
        // Aplicar feedback na alternativa selecionada
        const alternativaSelecionada = this.obterAlternativaPorIndice(container, respostaSelecionada);
        if (alternativaSelecionada) {
            alternativaSelecionada.classList.remove('option-correct', 'option-incorrect', 'resposta-correta', 'resposta-incorreta');
            
            if (correto) {
                alternativaSelecionada.classList.add('option-correct', 'resposta-correta');
            } else {
                alternativaSelecionada.classList.add('option-incorrect', 'resposta-incorreta');
            }
        }

        // Se a resposta está incorreta, mostrar a alternativa correta
        if (!correto && alternativa_correta_id !== null && alternativa_correta_id !== respostaSelecionada) {
            const alternativaCorreta = this.obterAlternativaPorIndice(container, alternativa_correta_id);
            if (alternativaCorreta) {
                alternativaCorreta.classList.remove('option-incorrect', 'resposta-incorreta');
                alternativaCorreta.classList.add('option-correct', 'resposta-correta');
            }
        }
    }

    obterAlternativaPorIndice(container, indice) {
        // Tentar várias formas de encontrar a alternativa pelo índice
        let alternativa = container.querySelector(`[data-index="${indice}"]`);
        if (alternativa) return alternativa;

        alternativa = container.querySelector(`[data-value="${indice}"]`);
        if (alternativa) return alternativa;

        // Buscar pela posição na lista
        const alternativas = container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio');
        return alternativas[indice] || null;
    }

    mostrarExplicacao(container, resultado) {
        let explicacaoContainer = container.querySelector('.feedback-message, .explicacao-exercicio');
        
        if (!explicacaoContainer) {
            explicacaoContainer = document.createElement('div');
            explicacaoContainer.className = 'feedback-message explicacao-exercicio mt-3';
            container.appendChild(explicacaoContainer);
        }

        const explicacao = resultado.explicacao || 'Sem explicação disponível.';
        explicacaoContainer.innerHTML = `
            <div class="alert alert-${resultado.correto ? 'success' : 'info'}">
                <strong>${resultado.correto ? '✅ Correto!' : '❌ Incorreto!'}</strong><br>
                ${explicacao}
            </div>
        `;
        explicacaoContainer.style.display = 'block';
    }

    mostrarLoading(container) {
        let loadingContainer = container.querySelector('.loading-exercicio');
        if (!loadingContainer) {
            loadingContainer = document.createElement('div');
            loadingContainer.className = 'loading-exercicio';
            container.appendChild(loadingContainer);
        }

        loadingContainer.innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Processando...</span>
                </div>
                <p class="mt-2">Processando resposta...</p>
            </div>
        `;
        loadingContainer.style.display = 'block';
    }

    ocultarLoading(container) {
        const loadingContainer = container.querySelector('.loading-exercicio');
        if (loadingContainer) {
            loadingContainer.style.display = 'none';
        }
    }

    mostrarErro(container, mensagem) {
        let erroContainer = container.querySelector('.erro-exercicio');
        if (!erroContainer) {
            erroContainer = document.createElement('div');
            erroContainer.className = 'erro-exercicio';
            container.appendChild(erroContainer);
        }

        erroContainer.innerHTML = `
            <div class="alert alert-danger">
                <strong>Erro:</strong> ${mensagem}
            </div>
        `;
        erroContainer.style.display = 'block';
    }

    desabilitarAlternativas(container) {
        container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio, button').forEach(elemento => {
            elemento.disabled = true;
            elemento.style.pointerEvents = 'none';
        });
    }

    reabilitarAlternativas(container) {
        container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio, button').forEach(elemento => {
            elemento.disabled = false;
            elemento.style.pointerEvents = 'auto';
        });
    }
}

// Função global para compatibilidade com código existente
function aplicarFeedbackVisualExercicio(respostaSelecionada, respostaCorreta, correto) {
    const alternativas = document.querySelectorAll('.alternativa, .option-audio, .opcao-exercicio');
    
    // Limpar feedback anterior
    alternativas.forEach(alt => {
        alt.classList.remove('option-correct', 'option-incorrect', 'resposta-correta', 'resposta-incorreta');
    });

    // Aplicar feedback na alternativa selecionada
    if (alternativas[respostaSelecionada]) {
        if (correto) {
            alternativas[respostaSelecionada].classList.add('option-correct', 'resposta-correta');
        } else {
            alternativas[respostaSelecionada].classList.add('option-incorrect', 'resposta-incorreta');
        }
    }

    // Se incorreto, mostrar a resposta correta
    if (!correto && alternativas[respostaCorreta]) {
        alternativas[respostaCorreta].classList.add('option-correct', 'resposta-correta');
    }
}

// Função para remover validações problemáticas
function removerValidacoesProblematicas() {
    // Remover mensagens de erro que impedem seleção
    document.querySelectorAll('.alert-warning, .error-message').forEach(msg => {
        if (msg.textContent.includes('Selecione uma resposta') || 
            msg.textContent.includes('digite sua resposta')) {
            msg.remove();
        }
    });
    
    // Habilitar todas as opções
    document.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio').forEach(opt => {
        opt.style.pointerEvents = 'auto';
        opt.removeAttribute('disabled');
    });
    
    // Habilitar botões de responder
    document.querySelectorAll('.btn-responder, button[type="submit"]').forEach(btn => {
        if (btn.textContent.includes('Responder') || btn.type === 'submit') {
            btn.disabled = false;
        }
    });
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.correcaoVisualExercicios = new CorrecaoVisualExercicios();
    
    // Remover validações problemáticas após um breve delay
    setTimeout(removerValidacoesProblematicas, 500);
});

// Expor para uso global
window.CorrecaoVisualExercicios = CorrecaoVisualExercicios;
window.aplicarFeedbackVisualExercicio = aplicarFeedbackVisualExercicio;
window.removerValidacoesProblematicas = removerValidacoesProblematicas;