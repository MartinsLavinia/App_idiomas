// Sistema de Exercícios de Fala com Correção de Áudio
class ExercicioFala {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.audioContext = null;
        this.analyser = null;
        this.dataArray = null;
        this.animationId = null;
    }

    // Inicializar o sistema de áudio
    async inicializar() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                } 
            });
            
            this.mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });
            
            this.setupAudioVisualization(stream);
            this.setupRecorderEvents();
            
            return true;
        } catch (error) {
            console.error('Erro ao acessar microfone:', error);
            this.mostrarErro('Não foi possível acessar o microfone. Verifique as permissões.');
            return false;
        }
    }

    // Configurar visualização de áudio
    setupAudioVisualization(stream) {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        this.analyser = this.audioContext.createAnalyser();
        const source = this.audioContext.createMediaStreamSource(stream);
        
        source.connect(this.analyser);
        this.analyser.fftSize = 256;
        
        const bufferLength = this.analyser.frequencyBinCount;
        this.dataArray = new Uint8Array(bufferLength);
    }

    // Configurar eventos do gravador
    setupRecorderEvents() {
        this.mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                this.audioChunks.push(event.data);
            }
        };

        this.mediaRecorder.onstop = () => {
            this.processarAudio();
        };
    }

    // Iniciar gravação
    iniciarGravacao(exercicioId, fraseEsperada) {
        if (!this.mediaRecorder) {
            this.mostrarErro('Sistema de áudio não inicializado');
            return;
        }

        this.audioChunks = [];
        this.exercicioId = exercicioId;
        this.fraseEsperada = fraseEsperada;
        
        this.mediaRecorder.start();
        this.isRecording = true;
        
        this.atualizarInterface('gravando');
        this.iniciarVisualizacao();
        
        // Auto-parar após 10 segundos
        setTimeout(() => {
            if (this.isRecording) {
                this.pararGravacao();
            }
        }, 10000);
    }

    // Parar gravação
    pararGravacao() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            this.pararVisualizacao();
            this.atualizarInterface('processando');
        }
    }

    // Processar áudio gravado
    async processarAudio() {
        try {
            const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
            const audioBase64 = await this.blobToBase64(audioBlob);
            
            await this.enviarParaCorrecao(audioBase64);
            
        } catch (error) {
            console.error('Erro ao processar áudio:', error);
            this.mostrarErro('Erro ao processar o áudio gravado');
        }
    }

    // Converter blob para base64
    blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const base64 = reader.result.split(',')[1];
                resolve(base64);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    // Enviar áudio para correção
    async enviarParaCorrecao(audioBase64) {
        try {
            const response = await fetch('admin/controller/correcao_audio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: this.exercicioId,
                    audio_data: audioBase64,
                    frase_esperada: this.fraseEsperada
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.mostrarResultado(resultado.resultado);
            } else {
                this.mostrarErro(resultado.message || 'Erro ao analisar áudio');
            }
            
        } catch (error) {
            console.error('Erro ao enviar áudio:', error);
            this.mostrarErro('Erro de conexão ao enviar áudio');
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
        }

        let html = `
            <div class="resultado-feedback ${statusClass}">
                <div class="resultado-header">
                    <span class="resultado-icone">${icone}</span>
                    <h3>${statusTexto}</h3>
                    <div class="pontuacao">${pontuacao_percentual}%</div>
                </div>
                
                <div class="transcricao-comparacao">
                    <div class="frase-esperada">
                        <strong>Esperado:</strong> "${feedback_detalhado.frase_esperada}"
                    </div>
                    <div class="frase-transcrita">
                        <strong>Você disse:</strong> "${feedback_detalhado.frase_transcrita}"
                    </div>
                </div>
        `;

        // Mostrar palavras corretas e incorretas
        if (feedback_detalhado.palavras_corretas.length > 0) {
            html += `
                <div class="palavras-corretas">
                    <strong>✅ Palavras corretas:</strong> 
                    ${feedback_detalhado.palavras_corretas.join(', ')}
                </div>
            `;
        }

        if (feedback_detalhado.palavras_incorretas.length > 0) {
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
        if (feedback_detalhado.sugestoes.length > 0) {
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

        // Mostrar pontos de melhoria
        if (feedback_detalhado.pontos_melhoria.length > 0) {
            html += `
                <div class="pontos-melhoria">
                    <strong>🎯 Pontos para melhorar:</strong>
                    <ul>
            `;
            
            feedback_detalhado.pontos_melhoria.forEach(ponto => {
                html += `<li>${ponto}</li>`;
            });
            
            html += `</ul></div>`;
        }

        html += `
                <div class="acoes-resultado">
                    <button onclick="exercicioFala.tentarNovamente()" class="btn-tentar-novamente">
                        🔄 Tentar Novamente
                    </button>
                    <button onclick="exercicioFala.proximoExercicio()" class="btn-proximo">
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
        const btnGravar = document.getElementById('btn-gravar');
        const btnParar = document.getElementById('btn-parar');
        const statusGravacao = document.getElementById('status-gravacao');
        const visualizador = document.getElementById('visualizador-audio');

        if (!btnGravar || !btnParar || !statusGravacao) return;

        switch (estado) {
            case 'pronto':
                btnGravar.disabled = false;
                btnGravar.textContent = '🎤 Gravar';
                btnParar.disabled = true;
                statusGravacao.textContent = 'Clique em gravar e fale a frase';
                statusGravacao.className = 'status-pronto';
                if (visualizador) visualizador.style.display = 'none';
                break;
                
            case 'gravando':
                btnGravar.disabled = true;
                btnParar.disabled = false;
                statusGravacao.textContent = '🔴 Gravando... Fale agora!';
                statusGravacao.className = 'status-gravando';
                if (visualizador) visualizador.style.display = 'block';
                break;
                
            case 'processando':
                btnGravar.disabled = true;
                btnParar.disabled = true;
                statusGravacao.textContent = '⏳ Analisando sua pronúncia...';
                statusGravacao.className = 'status-processando';
                if (visualizador) visualizador.style.display = 'none';
                break;
                
            case 'resultado':
                btnGravar.disabled = false;
                btnGravar.textContent = '🎤 Gravar Novamente';
                btnParar.disabled = true;
                statusGravacao.textContent = 'Resultado da análise:';
                statusGravacao.className = 'status-resultado';
                break;
        }
    }

    // Iniciar visualização de áudio
    iniciarVisualizacao() {
        const canvas = document.getElementById('canvas-visualizacao');
        if (!canvas || !this.analyser) return;

        const ctx = canvas.getContext('2d');
        const WIDTH = canvas.width;
        const HEIGHT = canvas.height;

        const draw = () => {
            if (!this.isRecording) return;

            this.animationId = requestAnimationFrame(draw);

            this.analyser.getByteFrequencyData(this.dataArray);

            ctx.fillStyle = 'rgb(240, 240, 240)';
            ctx.fillRect(0, 0, WIDTH, HEIGHT);

            const barWidth = (WIDTH / this.dataArray.length) * 2.5;
            let barHeight;
            let x = 0;

            for (let i = 0; i < this.dataArray.length; i++) {
                barHeight = (this.dataArray[i] / 255) * HEIGHT;

                ctx.fillStyle = `rgb(${barHeight + 100}, 50, 50)`;
                ctx.fillRect(x, HEIGHT - barHeight, barWidth, barHeight);

                x += barWidth + 1;
            }
        };

        draw();
    }

    // Parar visualização
    pararVisualizacao() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    }

    // Tentar novamente
    tentarNovamente() {
        document.getElementById('resultado-audio').innerHTML = '';
        this.atualizarInterface('pronto');
    }

    // Próximo exercício
    proximoExercicio() {
        // Implementar navegação para próximo exercício
        if (typeof proximoExercicio === 'function') {
            proximoExercicio();
        }
    }

    // Mostrar erro
    mostrarErro(mensagem) {
        const container = document.getElementById('resultado-audio');
        if (container) {
            container.innerHTML = `
                <div class="erro-audio">
                    <span class="erro-icone">❌</span>
                    <p>${mensagem}</p>
                    <button onclick="exercicioFala.tentarNovamente()" class="btn-tentar-novamente">
                        Tentar Novamente
                    </button>
                </div>
            `;
        }
        this.atualizarInterface('pronto');
    }
}

// Instância global
const exercicioFala = new ExercicioFala();

// Funções para integração com o sistema existente
async function iniciarExercicioFala(exercicioId, fraseEsperada) {
    const sucesso = await exercicioFala.inicializar();
    if (sucesso) {
        exercicioFala.exercicioId = exercicioId;
        exercicioFala.fraseEsperada = fraseEsperada;
        exercicioFala.atualizarInterface('pronto');
    }
}

function gravarAudio() {
    if (!exercicioFala.isRecording) {
        exercicioFala.iniciarGravacao(exercicioFala.exercicioId, exercicioFala.fraseEsperada);
    }
}

function pararGravacao() {
    exercicioFala.pararGravacao();
}

// CSS para os estilos (adicionar ao head da página)
const estilosAudio = `
<style>
.resultado-feedback {
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.resultado-correto {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: 2px solid #28a745;
}

.resultado-meio-correto {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 2px solid #ffc107;
}

.resultado-errado {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border: 2px solid #dc3545;
}

.resultado-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.resultado-icone {
    font-size: 2em;
}

.pontuacao {
    background: rgba(255,255,255,0.8);
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    margin-left: auto;
}

.transcricao-comparacao {
    background: rgba(255,255,255,0.5);
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.frase-esperada, .frase-transcrita {
    margin: 5px 0;
}

.palavras-corretas {
    color: #155724;
    margin: 10px 0;
}

.palavras-incorretas {
    color: #721c24;
    margin: 10px 0;
}

.palavras-incorretas ul {
    margin: 5px 0 0 20px;
}

.palavras-incorretas li {
    margin: 5px 0;
}

.sugestoes, .pontos-melhoria {
    margin: 15px 0;
}

.sugestoes ul, .pontos-melhoria ul {
    margin: 5px 0 0 20px;
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

.status-gravando {
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

#visualizador-audio {
    margin: 15px 0;
    text-align: center;
}

#canvas-visualizacao {
    border: 1px solid #ddd;
    border-radius: 5px;
    background: #f8f9fa;
}
</style>
`;

// Adicionar estilos ao head se não existirem
if (!document.getElementById('estilos-audio')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'estilos-audio';
    styleElement.innerHTML = estilosAudio;
    document.head.appendChild(styleElement);
}

