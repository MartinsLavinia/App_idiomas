// Sistema de Atividades Especiais - Música e Filme/Série
class AtividadesEspeciais {
    constructor() {
        this.unidadeAtual = null;
        this.atividadeAtual = null;
        this.tipoEscolhido = null;
        this.perguntaAtual = 0;
        this.respostasUsuario = [];
        this.pontuacaoTotal = 0;
    }

    // Inicializar sistema de atividades especiais
    inicializar(unidadeId) {
        this.unidadeAtual = unidadeId;
        this.mostrarEscolhaOpcoes();
    }

    // Mostrar opções de escolha (música ou filme/série)
    mostrarEscolhaOpcoes() {
        const container = document.getElementById('atividades-especiais-container');
        if (!container) {
            console.error('Container de atividades especiais não encontrado');
            return;
        }

        container.innerHTML = `
            <div class="escolha-atividade-especial">
                <div class="cabecalho-especial">
                    <h2>🎯 Atividade Especial</h2>
                    <p>Escolha o tipo de conteúdo que você prefere para praticar:</p>
                </div>
                
                <div class="opcoes-especiais">
                    <div class="opcao-card" onclick="atividadesEspeciais.escolherOpcao('musica')">
                        <div class="opcao-icone">🎵</div>
                        <h3>Música</h3>
                        <p>Aprenda inglês através de letras de músicas populares</p>
                        <div class="opcao-beneficios">
                            <span>✓ Melhora pronúncia</span>
                            <span>✓ Vocabulário natural</span>
                            <span>✓ Expressões idiomáticas</span>
                        </div>
                        <button class="btn-escolher">Escolher Música</button>
                    </div>
                    
                    <div class="opcao-card" onclick="atividadesEspeciais.escolherOpcao('filme_serie')">
                        <div class="opcao-icone">🎬</div>
                        <h3>Filme/Série</h3>
                        <p>Pratique com diálogos de filmes e séries famosos</p>
                        <div class="opcao-beneficios">
                            <span>✓ Conversação real</span>
                            <span>✓ Contexto cultural</span>
                            <span>✓ Linguagem cotidiana</span>
                        </div>
                        <button class="btn-escolher">Escolher Filme/Série</button>
                    </div>
                </div>
                
                <div class="info-atividade-especial">
                    <h4>💡 Como funciona?</h4>
                    <ol>
                        <li>Escolha entre música ou filme/série</li>
                        <li>Leia/escute o conteúdo apresentado</li>
                        <li>Responda às perguntas sobre o conteúdo</li>
                        <li>Receba feedback detalhado sobre suas respostas</li>
                    </ol>
                </div>
            </div>
        `;
    }

    // Escolher opção (música ou filme/série)
    async escolherOpcao(tipo) {
        this.tipoEscolhido = tipo;
        this.mostrarCarregamento('Carregando atividade...');

        try {
            const response = await fetch('admin/controller/atividades_especiais.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    acao: 'escolher_opcao',
                    unidade_id: this.unidadeAtual,
                    tipo_escolhido: tipo
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.atividadeAtual = resultado.atividade;
                this.mostrarAtividade();
            } else {
                this.mostrarErro(resultado.message || 'Erro ao carregar atividade');
            }
            
        } catch (error) {
            console.error('Erro ao escolher opção:', error);
            this.mostrarErro('Erro de conexão ao carregar atividade');
        }
    }

    // Mostrar atividade escolhida
    mostrarAtividade() {
        const container = document.getElementById('atividades-especiais-container');
        if (!container || !this.atividadeAtual) return;

        const { nome, tipo, conteudo_texto, conteudo_url } = this.atividadeAtual;
        const icone = tipo === 'musica' ? '🎵' : '🎬';
        const tipoTexto = tipo === 'musica' ? 'Música' : 'Filme/Série';

        container.innerHTML = `
            <div class="atividade-especial-conteudo">
                <div class="cabecalho-atividade">
                    <button class="btn-voltar" onclick="atividadesEspeciais.voltarEscolha()">
                        ← Voltar
                    </button>
                    <div class="info-atividade">
                        <span class="tipo-badge">${icone} ${tipoTexto}</span>
                        <h2>${nome}</h2>
                    </div>
                </div>
                
                <div class="conteudo-principal">
                    ${conteudo_url ? `
                        <div class="media-container">
                            <iframe src="${conteudo_url}" frameborder="0" allowfullscreen></iframe>
                        </div>
                    ` : ''}
                    
                    <div class="texto-conteudo">
                        <h3>${tipo === 'musica' ? '🎤 Letra da Música:' : '🎭 Diálogo:'}</h3>
                        <div class="conteudo-texto">
                            ${this.formatarConteudo(conteudo_texto)}
                        </div>
                    </div>
                </div>
                
                <div class="instrucoes-atividade">
                    <h4>📋 Instruções:</h4>
                    <p>Leia o ${tipo === 'musica' ? 'letra da música' : 'diálogo'} acima e responda às perguntas a seguir.</p>
                </div>
                
                <div class="area-exercicios">
                    <button class="btn-iniciar-exercicios" onclick="atividadesEspeciais.iniciarExercicios()">
                        🚀 Iniciar Exercícios
                    </button>
                </div>
            </div>
        `;
    }

    // Formatar conteúdo de texto
    formatarConteudo(texto) {
        if (!texto) return '';
        
        // Quebrar linhas e destacar diálogos
        return texto
            .split('\n')
            .map(linha => {
                linha = linha.trim();
                if (!linha) return '';
                
                // Detectar se é diálogo (nome: fala)
                if (linha.includes(':')) {
                    const [nome, fala] = linha.split(':', 2);
                    return `<div class="linha-dialogo">
                        <span class="nome-personagem">${nome.trim()}:</span>
                        <span class="fala-personagem">${fala.trim()}</span>
                    </div>`;
                } else {
                    return `<div class="linha-texto">${linha}</div>`;
                }
            })
            .join('');
    }

    // Iniciar exercícios
    iniciarExercicios() {
        if (!this.atividadeAtual || !this.atividadeAtual.exercicios) {
            this.mostrarErro('Exercícios não encontrados');
            return;
        }

        this.perguntaAtual = 0;
        this.respostasUsuario = [];
        this.pontuacaoTotal = 0;
        
        this.mostrarPergunta();
    }

    // Mostrar pergunta atual
    mostrarPergunta() {
        const exercicios = this.atividadeAtual.exercicios;
        if (!exercicios || !exercicios.perguntas) {
            this.mostrarErro('Perguntas não encontradas');
            return;
        }

        const perguntas = exercicios.perguntas;
        if (this.perguntaAtual >= perguntas.length) {
            this.mostrarResultadoFinal();
            return;
        }

        const pergunta = perguntas[this.perguntaAtual];
        const container = document.getElementById('atividades-especiais-container');
        
        container.innerHTML = `
            <div class="exercicio-especial">
                <div class="cabecalho-exercicio">
                    <button class="btn-voltar" onclick="atividadesEspeciais.mostrarAtividade()">
                        ← Voltar ao Conteúdo
                    </button>
                    <div class="progresso-exercicio">
                        <span>Pergunta ${this.perguntaAtual + 1} de ${perguntas.length}</span>
                        <div class="barra-progresso">
                            <div class="progresso-preenchido" style="width: ${((this.perguntaAtual + 1) / perguntas.length) * 100}%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="pergunta-container">
                    <h3>${pergunta.pergunta}</h3>
                    
                    <div class="alternativas-container">
                        ${pergunta.alternativas.map((alternativa, index) => `
                            <div class="alternativa" onclick="atividadesEspeciais.selecionarAlternativa(${index})">
                                <input type="radio" name="resposta" value="${index}" id="alt_${index}">
                                <label for="alt_${index}">
                                    <span class="letra-alternativa">${String.fromCharCode(65 + index)}</span>
                                    <span class="texto-alternativa">${alternativa}</span>
                                </label>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="acoes-pergunta">
                        <button id="btn-responder" class="btn-responder" onclick="atividadesEspeciais.responderPergunta()" disabled>
                            ✓ Responder
                        </button>
                        <button class="btn-pular" onclick="atividadesEspeciais.pularPergunta()">
                            ⏭️ Pular
                        </button>
                    </div>
                </div>
                
                <div id="feedback-pergunta" class="feedback-pergunta"></div>
            </div>
        `;
    }

    // Selecionar alternativa
    selecionarAlternativa(index) {
        // Remover seleção anterior
        document.querySelectorAll('.alternativa').forEach(alt => {
            alt.classList.remove('selecionada');
        });
        
        // Adicionar seleção atual
        const alternativa = document.querySelectorAll('.alternativa')[index];
        alternativa.classList.add('selecionada');
        
        // Marcar radio button
        const radio = document.getElementById(`alt_${index}`);
        if (radio) radio.checked = true;
        
        // Habilitar botão responder
        const btnResponder = document.getElementById('btn-responder');
        if (btnResponder) btnResponder.disabled = false;
    }

    // Responder pergunta
    async responderPergunta() {
        const respostaSelecionada = document.querySelector('input[name="resposta"]:checked');
        if (!respostaSelecionada) {
            this.mostrarErro('Selecione uma alternativa');
            return;
        }

        const respostaIndex = parseInt(respostaSelecionada.value);
        this.desabilitarInterface(true);

        try {
            const response = await fetch('admin/controller/atividades_especiais.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    acao: 'responder_exercicio',
                    atividade_id: this.atividadeAtual.id,
                    pergunta_id: this.perguntaAtual,
                    resposta_usuario: respostaIndex
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.mostrarFeedbackPergunta(resultado.feedback, respostaIndex);
                this.respostasUsuario.push({
                    pergunta: this.perguntaAtual,
                    resposta: respostaIndex,
                    correto: resultado.feedback.correto
                });
                
                if (resultado.feedback.correto) {
                    this.pontuacaoTotal++;
                }
            } else {
                this.mostrarErro(resultado.message || 'Erro ao processar resposta');
            }
            
        } catch (error) {
            console.error('Erro ao responder pergunta:', error);
            this.mostrarErro('Erro de conexão ao processar resposta');
        } finally {
            this.desabilitarInterface(false);
        }
    }

    // Mostrar feedback da pergunta
    mostrarFeedbackPergunta(feedback, respostaUsuario) {
        const container = document.getElementById('feedback-pergunta');
        if (!container) return;

        const { correto, resposta_correta, explicacao, alternativa_correta } = feedback;
        const icone = correto ? '✅' : '❌';
        const statusClass = correto ? 'feedback-correto' : 'feedback-incorreto';
        const statusTexto = correto ? 'Correto!' : 'Incorreto';

        container.innerHTML = `
            <div class="feedback-conteudo ${statusClass}">
                <div class="feedback-header">
                    <span class="feedback-icone">${icone}</span>
                    <h4>${statusTexto}</h4>
                </div>
                
                ${!correto ? `
                    <div class="resposta-correta">
                        <strong>Resposta correta:</strong> 
                        <span class="letra-correta">${String.fromCharCode(65 + resposta_correta)}</span>
                        ${alternativa_correta}
                    </div>
                ` : ''}
                
                ${explicacao ? `
                    <div class="explicacao">
                        <strong>💡 Explicação:</strong> ${explicacao}
                    </div>
                ` : ''}
                
                <div class="acoes-feedback">
                    <button class="btn-proxima" onclick="atividadesEspeciais.proximaPergunta()">
                        ${this.perguntaAtual < this.atividadeAtual.exercicios.perguntas.length - 1 ? 'Próxima Pergunta' : 'Ver Resultado'} →
                    </button>
                </div>
            </div>
        `;

        container.scrollIntoView({ behavior: 'smooth' });
    }

    // Pular pergunta
    pularPergunta() {
        this.respostasUsuario.push({
            pergunta: this.perguntaAtual,
            resposta: null,
            correto: false
        });
        
        this.proximaPergunta();
    }

    // Próxima pergunta
    proximaPergunta() {
        this.perguntaAtual++;
        this.mostrarPergunta();
    }

    // Mostrar resultado final
    mostrarResultadoFinal() {
        const totalPerguntas = this.atividadeAtual.exercicios.perguntas.length;
        const percentual = Math.round((this.pontuacaoTotal / totalPerguntas) * 100);
        
        let statusFinal = '';
        let icone = '';
        let mensagem = '';
        
        if (percentual >= 80) {
            statusFinal = 'excelente';
            icone = '🏆';
            mensagem = 'Excelente trabalho!';
        } else if (percentual >= 60) {
            statusFinal = 'bom';
            icone = '👍';
            mensagem = 'Bom trabalho!';
        } else {
            statusFinal = 'precisa-melhorar';
            icone = '📚';
            mensagem = 'Continue praticando!';
        }

        const container = document.getElementById('atividades-especiais-container');
        
        container.innerHTML = `
            <div class="resultado-final-especial">
                <div class="cabecalho-resultado">
                    <div class="icone-resultado">${icone}</div>
                    <h2>${mensagem}</h2>
                    <div class="pontuacao-final">
                        <span class="pontos">${this.pontuacaoTotal}</span>
                        <span class="total">/ ${totalPerguntas}</span>
                        <span class="percentual">(${percentual}%)</span>
                    </div>
                </div>
                
                <div class="estatisticas-resultado">
                    <div class="estatistica">
                        <span class="numero">${this.pontuacaoTotal}</span>
                        <span class="label">Corretas</span>
                    </div>
                    <div class="estatistica">
                        <span class="numero">${totalPerguntas - this.pontuacaoTotal}</span>
                        <span class="label">Incorretas</span>
                    </div>
                    <div class="estatistica">
                        <span class="numero">${percentual}%</span>
                        <span class="label">Aproveitamento</span>
                    </div>
                </div>
                
                <div class="resumo-respostas">
                    <h3>📊 Resumo das Respostas:</h3>
                    <div class="lista-respostas">
                        ${this.respostasUsuario.map((resposta, index) => `
                            <div class="item-resposta ${resposta.correto ? 'correto' : 'incorreto'}">
                                <span class="numero-pergunta">Q${index + 1}</span>
                                <span class="status-resposta">${resposta.correto ? '✅' : '❌'}</span>
                                <span class="texto-pergunta">${this.atividadeAtual.exercicios.perguntas[index].pergunta}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="acoes-finais">
                    <button class="btn-tentar-novamente" onclick="atividadesEspeciais.tentarNovamente()">
                        🔄 Tentar Novamente
                    </button>
                    <button class="btn-nova-atividade" onclick="atividadesEspeciais.voltarEscolha()">
                        🎯 Nova Atividade
                    </button>
                    <button class="btn-continuar" onclick="atividadesEspeciais.continuarAprendizado()">
                        ➡️ Continuar Aprendizado
                    </button>
                </div>
            </div>
        `;
    }

    // Tentar novamente
    tentarNovamente() {
        this.iniciarExercicios();
    }

    // Voltar à escolha de opções
    voltarEscolha() {
        this.atividadeAtual = null;
        this.tipoEscolhido = null;
        this.mostrarEscolhaOpcoes();
    }

    // Continuar aprendizado
    continuarAprendizado() {
        // Implementar navegação para próxima atividade
        if (typeof continuarAprendizado === 'function') {
            continuarAprendizado();
        } else {
            console.log('Função continuarAprendizado não definida');
        }
    }

    // Mostrar carregamento
    mostrarCarregamento(mensagem) {
        const container = document.getElementById('atividades-especiais-container');
        if (container) {
            container.innerHTML = `
                <div class="carregamento-especial">
                    <div class="spinner"></div>
                    <p>${mensagem}</p>
                </div>
            `;
        }
    }

    // Mostrar erro
    mostrarErro(mensagem) {
        const container = document.getElementById('atividades-especiais-container');
        if (container) {
            container.innerHTML = `
                <div class="erro-especial">
                    <span class="erro-icone">❌</span>
                    <p>${mensagem}</p>
                    <button onclick="atividadesEspeciais.voltarEscolha()" class="btn-voltar-erro">
                        Voltar
                    </button>
                </div>
            `;
        }
    }

    // Desabilitar/habilitar interface
    desabilitarInterface(desabilitar) {
        const elementos = document.querySelectorAll('button, input');
        elementos.forEach(elemento => {
            elemento.disabled = desabilitar;
        });
    }
}

// Instância global
const atividadesEspeciais = new AtividadesEspeciais();

// Função para inicializar atividades especiais
function iniciarAtividadesEspeciais(unidadeId) {
    atividadesEspeciais.inicializar(unidadeId);
}

// CSS para os estilos (adicionar ao head da página)
const estilosAtividadesEspeciais = `
<style>
.escolha-atividade-especial {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.cabecalho-especial {
    text-align: center;
    margin-bottom: 30px;
}

.cabecalho-especial h2 {
    color: #333;
    margin-bottom: 10px;
}

.opcoes-especiais {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.opcao-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.opcao-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.opcao-icone {
    font-size: 4em;
    margin-bottom: 15px;
}

.opcao-card h3 {
    margin: 15px 0;
    font-size: 1.5em;
}

.opcao-card p {
    margin-bottom: 20px;
    opacity: 0.9;
}

.opcao-beneficios {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 25px;
}

.opcao-beneficios span {
    background: rgba(255,255,255,0.2);
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
}

.btn-escolher {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid white;
    padding: 12px 25px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-escolher:hover {
    background: white;
    color: #333;
}

.info-atividade-especial {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    border-left: 4px solid #007bff;
}

.info-atividade-especial h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.info-atividade-especial ol {
    margin: 0;
    padding-left: 20px;
}

.info-atividade-especial li {
    margin: 8px 0;
}

.atividade-especial-conteudo {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.cabecalho-atividade {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.btn-voltar {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-voltar:hover {
    background: #5a6268;
}

.tipo-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
}

.info-atividade h2 {
    margin: 10px 0 0 0;
    color: #333;
}

.conteudo-principal {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.texto-conteudo h3 {
    color: #333;
    margin-bottom: 15px;
}

.conteudo-texto {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    font-family: 'Courier New', monospace;
    line-height: 1.6;
}

.linha-dialogo {
    margin: 10px 0;
}

.nome-personagem {
    font-weight: bold;
    color: #007bff;
}

.fala-personagem {
    margin-left: 10px;
}

.linha-texto {
    margin: 8px 0;
}

.instrucoes-atividade {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.instrucoes-atividade h4 {
    margin: 0 0 10px 0;
    color: #0056b3;
}

.area-exercicios {
    text-align: center;
}

.btn-iniciar-exercicios {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s;
}

.btn-iniciar-exercicios:hover {
    transform: scale(1.05);
}

.exercicio-especial {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.cabecalho-exercicio {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

.progresso-exercicio {
    text-align: right;
}

.barra-progresso {
    width: 200px;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    margin-top: 5px;
    overflow: hidden;
}

.progresso-preenchido {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #28a745);
    transition: width 0.3s;
}

.pergunta-container {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.pergunta-container h3 {
    color: #333;
    margin-bottom: 25px;
    font-size: 1.3em;
}

.alternativas-container {
    margin-bottom: 25px;
}

.alternativa {
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    margin: 10px 0;
    cursor: pointer;
    transition: all 0.3s;
}

.alternativa:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.alternativa.selecionada {
    border-color: #007bff;
    background: #e7f3ff;
}

.alternativa label {
    display: flex;
    align-items: center;
    padding: 15px;
    cursor: pointer;
    margin: 0;
}

.alternativa input[type="radio"] {
    display: none;
}

.letra-alternativa {
    background: #007bff;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.alternativa.selecionada .letra-alternativa {
    background: #28a745;
}

.texto-alternativa {
    flex: 1;
}

.acoes-pergunta {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-responder, .btn-pular {
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-responder {
    background: #28a745;
    color: white;
}

.btn-responder:hover:not(:disabled) {
    background: #218838;
}

.btn-responder:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.btn-pular {
    background: #6c757d;
    color: white;
}

.btn-pular:hover {
    background: #5a6268;
}

.feedback-pergunta {
    margin-top: 20px;
}

.feedback-conteudo {
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.feedback-correto {
    background: #d4edda;
    border: 2px solid #28a745;
}

.feedback-incorreto {
    background: #f8d7da;
    border: 2px solid #dc3545;
}

.feedback-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.feedback-icone {
    font-size: 1.5em;
}

.resposta-correta {
    background: rgba(255,255,255,0.7);
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.letra-correta {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 50%;
    font-weight: bold;
    margin: 0 5px;
}

.explicacao {
    background: rgba(255,255,255,0.7);
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.acoes-feedback {
    text-align: center;
    margin-top: 20px;
}

.btn-proxima {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-proxima:hover {
    background: #0056b3;
}

.resultado-final-especial {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px;
    text-align: center;
}

.cabecalho-resultado {
    margin-bottom: 30px;
}

.icone-resultado {
    font-size: 4em;
    margin-bottom: 15px;
}

.pontuacao-final {
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.pontos {
    color: #28a745;
}

.total {
    color: #6c757d;
}

.percentual {
    color: #007bff;
}

.estatisticas-resultado {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.estatistica {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-top: 4px solid #007bff;
}

.estatistica .numero {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.estatistica .label {
    color: #6c757d;
    font-size: 0.9em;
}

.resumo-respostas {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: left;
}

.resumo-respostas h3 {
    margin: 0 0 20px 0;
    text-align: center;
}

.lista-respostas {
    max-height: 300px;
    overflow-y: auto;
}

.item-resposta {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    background: white;
}

.item-resposta.correto {
    border-left: 4px solid #28a745;
}

.item-resposta.incorreto {
    border-left: 4px solid #dc3545;
}

.numero-pergunta {
    background: #007bff;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    font-size: 0.9em;
}

.status-resposta {
    font-size: 1.2em;
}

.texto-pergunta {
    flex: 1;
    font-size: 0.9em;
}

.acoes-finais {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.acoes-finais button {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
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

.btn-nova-atividade {
    background: #ffc107;
    color: #212529;
}

.btn-nova-atividade:hover {
    background: #e0a800;
}

.btn-continuar {
    background: #28a745;
    color: white;
}

.btn-continuar:hover {
    background: #218838;
}

.carregamento-especial {
    text-align: center;
    padding: 50px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.erro-especial {
    text-align: center;
    padding: 50px;
    background: #f8d7da;
    border: 2px solid #dc3545;
    border-radius: 10px;
    margin: 20px;
}

.erro-icone {
    font-size: 3em;
    margin-bottom: 15px;
}

.btn-voltar-erro {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
}

.btn-voltar-erro:hover {
    background: #c82333;
}

@media (max-width: 768px) {
    .opcoes-especiais {
        grid-template-columns: 1fr;
    }
    
    .cabecalho-exercicio {
        flex-direction: column;
        gap: 15px;
    }
    
    .progresso-exercicio {
        text-align: center;
    }
    
    .barra-progresso {
        width: 100%;
    }
    
    .acoes-pergunta, .acoes-finais {
        flex-direction: column;
    }
    
    .acoes-pergunta button, .acoes-finais button {
        width: 100%;
    }
    
    .estatisticas-resultado {
        grid-template-columns: 1fr;
    }
}
</style>
`;

// Adicionar estilos ao head se não existirem
if (!document.getElementById('estilos-atividades-especiais')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'estilos-atividades-especiais';
    styleElement.innerHTML = estilosAtividadesEspeciais;
    document.head.appendChild(styleElement);
}

