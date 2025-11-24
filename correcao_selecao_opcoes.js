/**
 * Correção para o problema "Selecione uma resposta ou digite sua resposta"
 * Este script corrige problemas de validação e seleção de opções em exercícios
 */

class CorrecaoSelecaoOpcoes {
    constructor() {
        this.inicializar();
    }

    inicializar() {
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.configurar());
        } else {
            this.configurar();
        }
    }

    configurar() {
        // Interceptar cliques em opções
        document.addEventListener('click', (event) => {
            const opcao = event.target.closest('.alternativa, .opcao-exercicio, .option-audio');
            if (opcao) {
                this.processarSelecaoOpcao(opcao, event);
            }
        });

        // Interceptar submissões de formulários
        document.addEventListener('submit', (event) => {
            if (event.target.classList.contains('form-exercicio')) {
                this.validarSubmissao(event);
            }
        });

        // Interceptar cliques em botões de responder
        document.addEventListener('click', (event) => {
            if (event.target.closest('.btn-responder, .btn-submit-exercicio')) {
                this.processarBotaoResponder(event);
            }
        });

        // Remover mensagens de erro existentes
        this.removerMensagensErro();
    }

    processarSelecaoOpcao(opcao, event) {
        const container = opcao.closest('.exercicio-container, [id^="exercicio-"], .question-container');
        if (!container) return;

        // Remover seleções anteriores
        container.querySelectorAll('.alternativa, .opcao-exercicio, .option-audio').forEach(opt => {
            opt.classList.remove('selecionada', 'selected', 'active');
            opt.removeAttribute('data-selected');
        });

        // Marcar como selecionada
        opcao.classList.add('selecionada', 'selected', 'active');
        opcao.setAttribute('data-selected', 'true');

        // Habilitar botão de responder
        const btnResponder = container.querySelector('.btn-responder, .btn-submit-exercicio, button[type="submit"]');
        if (btnResponder) {
            btnResponder.disabled = false;
            btnResponder.classList.remove('disabled');
        }

        // Remover mensagens de erro
        this.removerMensagemErro(container);

        // Atualizar campos hidden se existirem
        this.atualizarCamposHidden(container, opcao);

        console.log('Opção selecionada:', opcao.textContent.trim());
    }

    processarBotaoResponder(event) {
        const btn = event.target.closest('.btn-responder, .btn-submit-exercicio');
        const container = btn.closest('.exercicio-container, [id^="exercicio-"], .question-container, form');
        
        // Verificar se há uma opção selecionada
        const opcaoSelecionada = container.querySelector('.selecionada, .selected, [data-selected="true"], input[type="radio"]:checked');
        
        if (!opcaoSelecionada) {
            event.preventDefault();
            event.stopPropagation();
            
            this.mostrarMensagemSelecao(container);
            return false;
        }

        // Se chegou até aqui, há uma seleção válida
        this.removerMensagemErro(container);
        return true;
    }

    validarSubmissao(event) {
        const form = event.target;
        const container = form.closest('.exercicio-container, [id^="exercicio-"], .question-container') || form;
        
        // Verificar se há uma opção selecionada
        const opcaoSelecionada = container.querySelector('.selecionada, .selected, [data-selected="true"], input[type="radio"]:checked, input[type="checkbox"]:checked');
        
        if (!opcaoSelecionada) {
            event.preventDefault();
            event.stopPropagation();
            
            this.mostrarMensagemSelecao(container);
            return false;
        }

        return true;
    }

    mostrarMensagemSelecao(container) {
        // Remover mensagem anterior se existir
        this.removerMensagemErro(container);

        // Criar nova mensagem
        const mensagem = document.createElement('div');
        mensagem.className = 'alert alert-warning mensagem-selecao-erro';
        mensagem.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção:</strong> Por favor, selecione uma resposta antes de continuar.
        `;

        // Inserir mensagem
        const local = container.querySelector('.opcoes-container, .alternativas-container') || 
                     container.querySelector('.btn-responder, .btn-submit-exercicio')?.parentElement ||
                     container;
        
        if (local) {
            local.appendChild(mensagem);
            
            // Scroll para a mensagem
            mensagem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remover após 5 segundos
            setTimeout(() => {
                if (mensagem.parentElement) {
                    mensagem.remove();
                }
            }, 5000);
        }
    }

    removerMensagemErro(container) {
        const mensagens = container.querySelectorAll('.mensagem-selecao-erro, .alert-warning');
        mensagens.forEach(msg => {
            if (msg.textContent.includes('selecione') || msg.textContent.includes('resposta')) {
                msg.remove();
            }
        });
    }

    removerMensagensErro() {
        // Remover mensagens de erro existentes na página
        const mensagensErro = document.querySelectorAll('.alert-warning, .error-message');
        mensagensErro.forEach(msg => {
            if (msg.textContent.includes('Selecione uma resposta') || 
                msg.textContent.includes('digite sua resposta')) {
                msg.remove();
            }
        });
    }

    atualizarCamposHidden(container, opcaoSelecionada) {
        // Atualizar campos hidden com a resposta selecionada
        const campoResposta = container.querySelector('input[name="resposta"], input[name="resposta_selecionada"]');
        if (campoResposta) {
            const indice = opcaoSelecionada.getAttribute('data-index') || 
                          opcaoSelecionada.getAttribute('data-value') ||
                          Array.from(opcaoSelecionada.parentElement.children).indexOf(opcaoSelecionada);
            campoResposta.value = indice;
        }

        // Atualizar radio buttons se existirem
        const radioButton = opcaoSelecionada.querySelector('input[type="radio"]') ||
                           container.querySelector(`input[type="radio"][value="${opcaoSelecionada.getAttribute('data-value') || ''}"]`);
        if (radioButton) {
            radioButton.checked = true;
        }
    }

    // Método público para forçar seleção de uma opção
    selecionarOpcao(container, indice) {
        const opcoes = container.querySelectorAll('.alternativa, .opcao-exercicio, .option-audio');
        if (opcoes[indice]) {
            opcoes[indice].click();
        }
    }

    // Método público para verificar se há seleção
    temSelecao(container) {
        return !!container.querySelector('.selecionada, .selected, [data-selected="true"], input[type="radio"]:checked');
    }
}

// Função global para compatibilidade
function corrigirSelecaoOpcoes() {
    if (!window.correcaoSelecaoOpcoes) {
        window.correcaoSelecaoOpcoes = new CorrecaoSelecaoOpcoes();
    }
}

// Função para forçar habilitação de botões
function habilitarBotoesResposta() {
    document.querySelectorAll('.btn-responder, .btn-submit-exercicio').forEach(btn => {
        btn.disabled = false;
        btn.classList.remove('disabled');
    });
}

// Função para limpar validações problemáticas
function limparValidacoesProblematicas() {
    // Remover event listeners problemáticos
    const elementos = document.querySelectorAll('.alternativa, .opcao-exercicio, .option-audio');
    elementos.forEach(el => {
        el.style.pointerEvents = 'auto';
        el.removeAttribute('disabled');
    });

    // Remover mensagens de erro
    document.querySelectorAll('.alert-warning, .error-message').forEach(msg => {
        if (msg.textContent.includes('Selecione') || msg.textContent.includes('resposta')) {
            msg.remove();
        }
    });
}

// Inicializar automaticamente
document.addEventListener('DOMContentLoaded', () => {
    window.correcaoSelecaoOpcoes = new CorrecaoSelecaoOpcoes();
    
    // Aguardar um pouco e limpar validações problemáticas
    setTimeout(() => {
        limparValidacoesProblematicas();
        habilitarBotoesResposta();
    }, 1000);
});

// Expor para uso global
window.CorrecaoSelecaoOpcoes = CorrecaoSelecaoOpcoes;
window.corrigirSelecaoOpcoes = corrigirSelecaoOpcoes;
window.habilitarBotoesResposta = habilitarBotoesResposta;
window.limparValidacoesProblematicas = limparValidacoesProblematicas;