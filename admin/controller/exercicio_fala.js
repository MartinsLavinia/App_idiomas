// Sistema de Exercícios de Fala com Correção de Áudio (Usando Web Speech API)
class ExercicioFala {
    constructor() {
        this.recognition = null;
        this.isListening = false;
        this.exercicioId = null;
        this.fraseEsperada = null;
        this.idioma = 'en-US';
        
        // Verificar suporte de forma mais robusta
        this.suporteReconhecimento = this.verificarSuporte();
        
        if (this.suporteReconhecimento) {
            this.inicializarReconhecimento();
        } else {
            this.mostrarErro('Seu navegador não suporta reconhecimento de voz. Use Chrome, Edge ou Safari.');
        }
    }

    // Método para verificar suporte
    verificarSuporte() {
        return ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
    }

    // Inicializar reconhecimento com tratamento de erro melhorado
    inicializarReconhecimento() {
        try {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            this.recognition.continuous = false;
            this.recognition.lang = this.idioma;
            this.recognition.interimResults = false;
            this.recognition.maxAlternatives = 1;

            this.setupRecognitionEvents();
            return true;
        } catch (error) {
            console.error('Erro ao inicializar reconhecimento:', error);
            this.mostrarErro('Erro ao inicializar sistema de voz: ' + error.message);
            return false;
        }
    }

    // Inicializar o sistema de reconhecimento
    async inicializar(idioma) {
        if (!this.suporteReconhecimento) return false;
        
        // Tenta obter o idioma da sessão ou do exercício
        if (idioma) {
            this.idioma = idioma;
            if (this.recognition) {
                this.recognition.lang = idioma;
            }
        }

        // Verificar permissão do microfone
        const temPermissao = await this.verificarPermissaoMicrofone();
        if (!temPermissao) {
            this.mostrarErro('Permissão de microfone necessária para exercícios de fala.');
            return false;
        }

        this.atualizarInterface('pronto');
        return true;
    }

    // Verificar permissão do microfone
    async verificarPermissaoMicrofone() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Navegador não suporta acesso ao microfone');
            }
            
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            // Liberar stream imediatamente após verificação
            stream.getTracks().forEach(track => track.stop());
            return true;
        } catch (error) {
            console.error('Erro de permissão do microfone:', error);
            return false;
        }
    }

    // Configurar eventos do reconhecimento
    setupRecognitionEvents() {
        this.recognition.onresult = (event) => {
            this.isListening = false;
            const transcricao = event.results[0][0].transcript;
            const confidence = event.results[0][0].confidence;
            
            console.log('Transcrição:', transcricao, 'Confiança:', confidence);
            
            // Envia a transcrição para o backend para correção
            this.enviarParaCorrecao(transcricao);
        };

        this.recognition.onspeechend = () => {
            if (this.isListening) {
                this.isListening = false;
                this.atualizarInterface('processando');
            }
        };

        this.recognition.onerror = (event) => {
            this.isListening = false;
            this.tratarErroReconhecimento(event);
        };
        
        this.recognition.onstart = () => {
            this.atualizarInterface('ouvindo');
        };

        this.recognition.onend = () => {
            if (!this.isListening) {
                this.atualizarInterface('pronto');
            }
        };
    }

    // Tratamento melhorado de erros
    tratarErroReconhecimento(erro) {
        let mensagem = 'Erro desconhecido no reconhecimento de voz';
        
        switch(erro.name || erro.error) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                mensagem = 'Permissão de microfone negada. Clique no ícone de cadeado na barra de endereços e permita o microfone.';
                break;
            case 'NotSupportedError':
                mensagem = 'Navegador não suporta reconhecimento de voz. Use Chrome, Edge ou Safari.';
                break;
            case 'NoSpeechError':
                mensagem = 'Nenhuma fala detectada. Tente novamente.';
                break;
            case 'AudioCaptureError':
                mensagem = 'Nenhum microfone detectado. Verifique seu dispositivo.';
                break;
            default:
                mensagem = `Erro: ${erro.message || erro}`;
        }
        
        this.mostrarErro(mensagem);
        this.atualizarInterface('pronto');
    }

    // Iniciar reconhecimento de fala
    async iniciarReconhecimento(exercicioId, fraseEsperada, idioma) {
        // Verificar permissões primeiro
        if (!await this.verificarPermissaoMicrofone()) {
            this.mostrarErro('Permissão de microfone negada. Por favor, permita o acesso ao microfone.');
            return;
        }

        if (!this.recognition) {
            this.mostrarErro('Sistema de reconhecimento de fala não disponível.');
            return;
        }
        
        this.exercicioId = exercicioId;
        this.fraseEsperada = fraseEsperada;
        
        // Atualiza o idioma se fornecido
        if (idioma) {
            this.idioma = idioma;
            this.recognition.lang = idioma;
        }

        try {
            this.recognition.start();
            this.isListening = true;
            this.atualizarInterface('ouvindo');
        } catch (e) {
            console.error('Erro ao iniciar reconhecimento:', e);
            this.tratarErroReconhecimento(e);
        }
    }

    // Parar reconhecimento (se necessário)
    pararReconhecimento() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
            this.isListening = false;
            this.atualizarInterface('processando');
        }
    }

    // Enviar transcrição para correção no backend
    async enviarParaCorrecao(transcricao) {
        this.atualizarInterface('processando');
        try {
            const response = await fetch('../../admin/controller/processar_exercicio.php', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: this.exercicioId,
                    resposta: transcricao, // O backend espera a transcrição no campo 'resposta'
                    tipo_exercicio: 'fala' // Adiciona o tipo para o backend saber como processar
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.mostrarResultado(resultado.resultado);
            } else {
                this.mostrarErro(resultado.message || 'Erro ao analisar fala');
            }
            
        } catch (error) {
            console.error('Erro ao enviar transcrição:', error);
            this.mostrarErro('Erro de conexão ao enviar transcrição');
            this.atualizarInterface('pronto');
        }
    }

    // Mostrar resultado da análise
    mostrarResultado(resultado) {
        const container = document.getElementById('resultado-audio');
        if (!container) return;

        const { status, pontuacao_percentual, feedback_detalhado } = resultado;
        
        let statusClass = '';
        let statusTexto = '';
        let icone = '';
        
        switch (status) {
            case 'correto':
                statusClass = 'resultado-correto';
                statusTexto = 'Excelente!';
                icone = '✅';
                break;
            case 'meio_correto':
                statusClass = 'resultado-meio-correto';
                statusTexto = 'Bom, mas pode melhorar';
                icone = '⚠️';
                break;
            case 'errado':
                statusClass = 'resultado-errado';
                statusTexto = 'Precisa praticar mais';
                icone = '❌';
                break;
            default:
                statusClass = 'resultado-errado';
                statusTexto = 'Erro de Correção';
                icone = '❌';
                break;
        }

        let html = `
            <div class="resultado-feedback ${statusClass}">
                <div class="resultado-header">
                    <span class="resultado-icone">${icone}</span>
                    <h3>${statusTexto} - ${pontuacao_percentual}%</h3>
                </div>
                
                <div class="transcricao-comparacao">
                    <div class="frase-esperada">
                        <strong>Frase Esperada:</strong> "${feedback_detalhado.frase_esperada}"
                    </div>
                    <div class="frase-transcrita">
                        <strong>Você disse:</strong> "${feedback_detalhado.frase_transcrita}"
                    </div>
                </div>
        `;

        // Mostrar palavras corretas e incorretas
        if (feedback_detalhado.palavras_corretas && feedback_detalhado.palavras_corretas.length > 0) {
            html += `
                <div class="palavras-corretas">
                    <strong>✅ Palavras corretas:</strong> 
                    ${feedback_detalhado.palavras_corretas.join(', ')}
                </div>
            `;
        }

        if (feedback_detalhado.palavras_incorretas && feedback_detalhado.palavras_incorretas.length > 0) {
            html += `<div class="palavras-incorretas">
                <strong>❌ Palavras para melhorar:</strong>
                <ul>`;
            
            feedback_detalhado.palavras_incorretas.forEach(palavra => {
                html += `
                    <li>
                        <strong>${palavra.esperada}</strong> 
                        (você disse: "${palavra.transcrita}")
                        <br><em>${palavra.sugestao}</em>
                    </li>
                `;
            });
            
            html += `</ul></div>`;
        }

        // Mostrar sugestões
        if (feedback_detalhado.sugestoes && feedback_detalhado.sugestoes.length > 0) {
            html += `
                <div class="sugestoes">
                    <strong>💡 Sugestões:</strong>
                    <ul>
            `;
            
            feedback_detalhado.sugestoes.forEach(sugestao => {
                html += `<li>${sugestao}</li>`;
            });
            
            html += `</ul></div>`;
        }

        // Mostrar pontos de melhoria (Explicação da resposta)
        if (feedback_detalhado.explicacao) {
            html += `
                <div class="explicacao-gramatical">
                    <strong>📚 Explicação:</strong>
                    <p>${feedback_detalhado.explicacao}</p>
                </div>
            `;
        }
        
        // Botões de ação
        html += `
                <div class="acoes-resultado">
                    <button onclick="exercicioFala.tentarNovamente()" class="btn-tentar-novamente">
                        🔄 Tentar Novamente
                    </button>
                    <button onclick="exercicioFala.avancarExercicio()" class="btn-proximo">
                        ➡️ Próximo Exercício
                    </button>
                </div>
            </div>
        `;

        container.innerHTML = html;
        this.atualizarInterface('resultado');
    }

    // Atualizar interface baseada no estado
    atualizarInterface(estado) {
        const btnFalar = document.getElementById('btn-falar');
        const statusFala = document.getElementById('status-fala');
        const resultadoContainer = document.getElementById('resultado-audio');
        
        if (!btnFalar || !statusFala || !resultadoContainer) return;

        // Limpa o resultado apenas se estiver voltando ao estado pronto
        if (estado === 'pronto') {
            resultadoContainer.innerHTML = ''; 
        }

        switch (estado) {
            case 'pronto':
                btnFalar.disabled = false;
                btnFalar.innerHTML = '<i class="fas fa-microphone"></i> Falar';
                statusFala.textContent = 'Clique para começar a falar a frase.';
                statusFala.className = 'status-pronto';
                break;
                
            case 'ouvindo':
                btnFalar.disabled = true;
                btnFalar.innerHTML = '<i class="fas fa-volume-up"></i> Ouvindo...';
                statusFala.textContent = '🔴 Falando... Diga a frase agora!';
                statusFala.className = 'status-ouvindo';
                break;
                
            case 'processando':
                btnFalar.disabled = true;
                btnFalar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                statusFala.textContent = 'Aguarde a correção...';
                statusFala.className = 'status-processando';
                break;

            case 'resultado':
                btnFalar.disabled = true;
                btnFalar.innerHTML = '<i class="fas fa-microphone"></i> Falar';
                statusFala.textContent = 'Resultado da correção.';
                statusFala.className = 'status-resultado';
                break;
        }
    }

    // Mostrar erro na interface
    mostrarErro(mensagem) {
        const container = document.getElementById('resultado-audio');
        if (!container) return;
        container.innerHTML = `
            <div class="erro-audio">
                <span class="erro-icone">⚠️</span>
                <p><strong>Erro:</strong> ${mensagem}</p>
                ${mensagem.includes('microfone') ? `
                    <div class="mt-2">
                        <small>
                            <strong>Como permitir o microfone:</strong><br>
                            1. Clique no ícone de cadeado/câmera na barra de endereços<br>
                            2. Encontre "Microfone" e mude para "Permitir"<br>
                            3. Recarregue a página
                        </small>
                    </div>
                ` : ''}
            </div>
        `;
        this.atualizarInterface('pronto');
    }

    // Ações do usuário
    tentarNovamente() {
        // Limpa o resultado e volta ao estado pronto
        const resultadoContainer = document.getElementById('resultado-audio');
        if (resultadoContainer) {
            resultadoContainer.innerHTML = '';
        }
        this.atualizarInterface('pronto');
    }

    // Função para avançar o exercício (será definida no painel.php)
    avancarExercicio() {
        if (typeof window.proximoExercicio === 'function') {
            window.proximoExercicio();
        } else {
            alert('Função de próximo exercício não definida.');
        }
    }
}

// Estilos para o componente
const estilosAudio = `
<style id="estilos-audio">
.resultado-feedback {
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}

.resultado-correto {
    background: #d4edda;
    border: 2px solid #c3e6cb;
    color: #155724;
}

.resultado-meio-correto {
    background: #fff3cd;
    border: 2px solid #ffeeba;
    color: #856404;
}

.resultado-errado {
    background: #f8d7da;
    border: 2px solid #f5c6cb;
    color: #721c24;
}

.resultado-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding-bottom: 10px;
}

.resultado-icone {
    font-size: 2.5rem;
}

.resultado-header h3 {
    margin: 0;
    font-weight: 600;
}

.pontuacao {
    margin-left: auto;
    font-size: 1.5rem;
    font-weight: bold;
}

.transcricao-comparacao {
    margin-bottom: 15px;
    padding: 10px;
    border-left: 3px solid #007bff;
    background-color: #f7f9fc;
}

.palavras-incorretas, .sugestoes, .explicacao-gramatical {
    margin-top: 15px;
    padding: 10px;
    border-radius: 5px;
}

.palavras-incorretas {
    background-color: #fce4e4;
    border: 1px solid #f0b8b8;
}

.sugestoes {
    background-color: #e6f7ff;
    border: 1px solid #b3e0ff;
}

.explicacao-gramatical {
    background-color: #e8f5e9;
    border: 1px solid #c8e6c9;
}

.palavras-incorretas ul, .sugestoes ul {
    list-style-type: disc;
    padding-left: 20px;
    margin-top: 5px;
}

.acoes-resultado {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-tentar-novamente, .btn-proximo {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-tentar-novamente {
    background: #6c757d;
    color: white;
}

.btn-tentar-novamente:hover {
    background: #5a6268;
}

.btn-proximo {
    background: #007bff;
    color: white;
}

.btn-proximo:hover {
    background: #0056b3;
}

.status-ouvindo {
    color: #dc3545;
    font-weight: bold;
    animation: pulse 1s infinite;
}

.status-processando {
    color: #ffc107;
    font-weight: bold;
}

.status-pronto {
    color: #28a745;
}

.status-resultado {
    color: #007bff;
    font-weight: bold;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.erro-audio {
    text-align: center;
    padding: 20px;
    background: #f8d7da;
    border: 2px solid #dc3545;
    border-radius: 10px;
    margin: 20px 0;
}

.erro-icone {
    font-size: 2em;
    display: block;
    margin-bottom: 10px;
}

.microphone-permission {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
}

.microphone-permission h5 {
    color: #856404;
    margin-bottom: 10px;
}

.microphone-permission ol {
    text-align: left;
    margin-bottom: 10px;
}

.microphone-permission li {
    margin-bottom: 5px;
}
</style>
`;

// Adicionar estilos ao head se não existirem
if (!document.getElementById('estilos-audio')) {
    document.head.insertAdjacentHTML('beforeend', estilosAudio);
}

// Instancia global para uso no painel.php
const exercicioFala = new ExercicioFala();

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('Sistema de exercícios de fala carregado');
});