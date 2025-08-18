// Sistema de Exercícios de Escrita com Correção Automática
class ExercicioEscrita {
    constructor() {
        this.exercicioAtual = null;
        this.tentativas = 0;
        this.maxTentativas = 3;
        this.respostaCorreta = null;
        this.alternativasAceitas = [];
        this.tipoExercicio = 'texto_livre';
    }

    // Inicializar exercício de escrita
    inicializar(exercicioId, pergunta, respostaEsperada, alternativasAceitas = [], tipoExercicio = 'texto_livre') {
        this.exercicioAtual = exercicioId;
        this.respostaCorreta = respostaEsperada;
        this.alternativasAceitas = alternativasAceitas.length > 0 ? alternativasAceitas : [respostaEsperada];
        this.tipoExercicio = tipoExercicio;
        this.tentativas = 0;
        
        this.criarInterface(pergunta);
        this.configurarEventos();
    }

    // Criar interface do exercício
    criarInterface(pergunta) {
        const container = document.getElementById('exercicio-escrita-container');
        if (!container) {
            console.error('Container do exercício de escrita não encontrado');
            return;
        }

        container.innerHTML = `
            <div class="exercicio-escrita">
                <div class="pergunta-escrita">
                    <h3>${pergunta}</h3>
                    ${this.tipoExercicio === 'completar_frase' ? '<p class="dica">💡 Complete a frase com a palavra ou expressão correta</p>' : ''}
                    ${this.tipoExercicio === 'traducao' ? '<p class="dica">💡 Traduza a frase para o português ou inglês</p>' : ''}
                    ${this.tipoExercicio === 'gramatica' ? '<p class="dica">💡 Use a gramática correta para completar</p>' : ''}
                </div>
                
                <div class="area-resposta">
                    <textarea 
                        id="resposta-usuario" 
                        placeholder="Digite sua resposta aqui..."
                        rows="3"
                        maxlength="500"
                    ></textarea>
                    <div class="contador-caracteres">
                        <span id="contador">0</span>/500 caracteres
                    </div>
                </div>
                
                <div class="acoes-escrita">
                    <button id="btn-verificar" class="btn-verificar" onclick="exercicioEscrita.verificarResposta()">
                        ✓ Verificar Resposta
                    </button>
                    <button id="btn-dica" class="btn-dica" onclick="exercicioEscrita.mostrarDica()">
                        💡 Dica
                    </button>
                    <button id="btn-limpar" class="btn-limpar" onclick="exercicioEscrita.limparResposta()">
                        🗑️ Limpar
                    </button>
                </div>
                
                <div id="area-dica" class="area-dica" style="display: none;"></div>
                <div id="resultado-escrita" class="resultado-escrita"></div>
                
                <div class="info-tentativas">
                    <span id="contador-tentativas">Tentativa: <strong>1</strong> de ${this.maxTentativas}</span>
                </div>
            </div>
        `;
    }

    // Configurar eventos da interface
    configurarEventos() {
        const textarea = document.getElementById('resposta-usuario');
        const contador = document.getElementById('contador');
        
        if (textarea && contador) {
            // Contador de caracteres
            textarea.addEventListener('input', () => {
                contador.textContent = textarea.value.length;
                
                // Habilitar/desabilitar botão verificar
                const btnVerificar = document.getElementById('btn-verificar');
                if (btnVerificar) {
                    btnVerificar.disabled = textarea.value.trim().length === 0;
                }
            });
            
            // Verificar resposta ao pressionar Enter (com Ctrl)
            textarea.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    this.verificarResposta();
                }
            });
        }
    }

    // Verificar resposta do usuário
    async verificarResposta() {
        const textarea = document.getElementById('resposta-usuario');
        if (!textarea) return;

        const respostaUsuario = textarea.value.trim();
        
        if (respostaUsuario.length === 0) {
            this.mostrarErro('Por favor, digite uma resposta antes de verificar.');
            return;
        }

        this.tentativas++;
        this.atualizarContadorTentativas();
        this.desabilitarInterface(true);

        try {
            const response = await fetch('admin/controller/correcao_escrita.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercicio_id: this.exercicioAtual,
                    resposta_usuario: respostaUsuario,
                    resposta_esperada: this.respostaCorreta,
                    alternativas_aceitas: this.alternativasAceitas,
                    tipo_exercicio: this.tipoExercicio
                })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.mostrarResultado(resultado.resultado);
            } else {
                this.mostrarErro(resultado.message || 'Erro ao verificar resposta');
            }
            
        } catch (error) {
            console.error('Erro ao verificar resposta:', error);
            this.mostrarErro('Erro de conexão ao verificar resposta');
        } finally {
            this.desabilitarInterface(false);
        }
    }

    // Mostrar resultado da correção
    mostrarResultado(resultado) {
        const container = document.getElementById('resultado-escrita');
        if (!container) return;

        const { status, pontuacao, correto, erros_encontrados, sugestoes_melhoria, pontos_positivos } = resultado;
        
        let statusClass = '';
        let statusTexto = '';
        let icone = '';
        let corFundo = '';
        
        switch (status) {
            case 'correto':
                statusClass = 'resultado-correto';
                statusTexto = 'Perfeito!';
                icone = '✅';
                corFundo = '#d4edda';
                break;
            case 'quase_correto':
                statusClass = 'resultado-quase-correto';
                statusTexto = 'Quase lá!';
                icone = '⭐';
                corFundo = '#e2f3ff';
                break;
            case 'meio_correto':
                statusClass = 'resultado-meio-correto';
                statusTexto = 'Pode melhorar';
                icone = '⚠️';
                corFundo = '#fff3cd';
                break;
            case 'errado':
                statusClass = 'resultado-errado';
                statusTexto = 'Vamos tentar novamente';
                icone = '❌';
                corFundo = '#f8d7da';
                break;
        }

        let html = `
            <div class="feedback-escrita ${statusClass}" style="background-color: ${corFundo};">
                <div class="cabecalho-resultado">
                    <span class="icone-resultado">${icone}</span>
                    <h3>${statusTexto}</h3>
                    <div class="pontuacao-escrita">${Math.round(pontuacao * 100)}%</div>
                </div>
                
                <div class="comparacao-respostas">
                    <div class="resposta-usuario-display">
                        <strong>Sua resposta:</strong> "${resultado.resposta_usuario}"
                    </div>
                    <div class="resposta-esperada-display">
                        <strong>Resposta esperada:</strong> "${resultado.resposta_esperada}"
                    </div>
                </div>
        `;

        // Mostrar pontos positivos
        if (pontos_positivos && pontos_positivos.length > 0) {
            html += `
                <div class="pontos-positivos">
                    <h4>✅ Pontos positivos:</h4>
                    <ul>
            `;
            pontos_positivos.forEach(ponto => {
                html += `<li>${ponto}</li>`;
            });
            html += `</ul></div>`;
        }

        // Mostrar erros encontrados
        if (erros_encontrados && erros_encontrados.length > 0) {
            html += `
                <div class="erros-encontrados">
                    <h4>❌ Pontos para melhorar:</h4>
                    <ul>
            `;
            erros_encontrados.forEach(erro => {
                html += `
                    <li>
                        <strong>${erro.descricao}</strong>
                        ${erro.sugestao ? `<br><em>💡 ${erro.sugestao}</em>` : ''}
                    </li>
                `;
            });
            html += `</ul></div>`;
        }

        // Mostrar sugestões de melhoria
        if (sugestoes_melhoria && sugestoes_melhoria.length > 0) {
            html += `
                <div class="sugestoes-melhoria">
                    <h4>💡 Sugestões para melhorar:</h4>
                    <ul>
            `;
            sugestoes_melhoria.forEach(sugestao => {
                html += `<li>${sugestao}</li>`;
            });
            html += `</ul></div>`;
        }

        // Botões de ação baseados no resultado
        html += `<div class="acoes-resultado">`;
        
        if (correto || this.tentativas >= this.maxTentativas) {
            html += `
                <button onclick="exercicioEscrita.proximoExercicio()" class="btn-proximo">
                    ➡️ Próximo Exercício
                </button>
            `;
            if (correto) {
                html += `
                    <button onclick="exercicioEscrita.mostrarExplicacao()" class="btn-explicacao">
                        📚 Ver Explicação
                    </button>
                `;
            }
        } else {
            html += `
                <button onclick="exercicioEscrita.tentarNovamente()" class="btn-tentar-novamente">
                    🔄 Tentar Novamente
                </button>
                <button onclick="exercicioEscrita.mostrarResposta()" class="btn-mostrar-resposta">
                    👁️ Ver Resposta
                </button>
            `;
        }
        
        html += `</div></div>`;

        container.innerHTML = html;
        container.scrollIntoView({ behavior: 'smooth' });
    }

    // Mostrar dica para o usuário
    mostrarDica() {
        const areaDica = document.getElementById('area-dica');
        if (!areaDica) return;

        const dicas = this.gerarDicas();
        
        areaDica.innerHTML = `
            <div class="dica-conteudo">
                <h4>💡 Dica:</h4>
                <p>${dicas[Math.floor(Math.random() * dicas.length)]}</p>
            </div>
        `;
        
        areaDica.style.display = 'block';
        areaDica.scrollIntoView({ behavior: 'smooth' });
    }

    // Gerar dicas baseadas no tipo de exercício
    gerarDicas() {
        const dicasGerais = [
            'Leia a pergunta com atenção antes de responder.',
            'Pense no contexto da frase antes de escrever.',
            'Verifique a ortografia antes de enviar.',
            'Se não souber, tente usar palavras simples que você conhece.'
        ];

        const dicasEspecificas = {
            'completar_frase': [
                'Observe o contexto da frase para escolher a palavra certa.',
                'Pense na gramática: que tipo de palavra falta (verbo, substantivo, etc.)?',
                'Releia a frase completa para ver se faz sentido.'
            ],
            'traducao': [
                'Pense no significado geral da frase, não traduza palavra por palavra.',
                'Use palavras simples que você conhece bem.',
                'Lembre-se das expressões idiomáticas que você aprendeu.'
            ],
            'gramatica': [
                'Revise as regras gramaticais que você estudou.',
                'Pense na estrutura da frase: sujeito + verbo + complemento.',
                'Verifique a concordância entre as palavras.'
            ],
            'texto_livre': [
                'Seja claro e direto na sua resposta.',
                'Use frases simples e corretas.',
                'Inclua as palavras-chave relacionadas ao tema.'
            ]
        };

        return [...dicasGerais, ...(dicasEspecificas[this.tipoExercicio] || [])];
    }

    // Limpar resposta do usuário
    limparResposta() {
        const textarea = document.getElementById('resposta-usuario');
        const contador = document.getElementById('contador');
        const areaDica = document.getElementById('area-dica');
        const resultado = document.getElementById('resultado-escrita');
        
        if (textarea) textarea.value = '';
        if (contador) contador.textContent = '0';
        if (areaDica) areaDica.style.display = 'none';
        if (resultado) resultado.innerHTML = '';
        
        const btnVerificar = document.getElementById('btn-verificar');
        if (btnVerificar) btnVerificar.disabled = true;
    }

    // Tentar novamente
    tentarNovamente() {
        const resultado = document.getElementById('resultado-escrita');
        if (resultado) resultado.innerHTML = '';
        
        const textarea = document.getElementById('resposta-usuario');
        if (textarea) {
            textarea.focus();
            textarea.select();
        }
    }

    // Mostrar resposta correta
    mostrarResposta() {
        const textarea = document.getElementById('resposta-usuario');
        if (textarea) {
            textarea.value = this.respostaCorreta;
            textarea.focus();
        }
        
        const contador = document.getElementById('contador');
        if (contador) {
            contador.textContent = this.respostaCorreta.length;
        }
    }

    // Mostrar explicação detalhada
    mostrarExplicacao() {
        const container = document.getElementById('resultado-escrita');
        if (!container) return;

        const explicacao = this.gerarExplicacao();
        
        const explicacaoHtml = `
            <div class="explicacao-detalhada">
                <h4>📚 Explicação:</h4>
                <div class="conteudo-explicacao">
                    ${explicacao}
                </div>
                <button onclick="this.parentElement.style.display='none'" class="btn-fechar-explicacao">
                    ✖️ Fechar
                </button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', explicacaoHtml);
    }

    // Gerar explicação baseada no tipo de exercício
    gerarExplicacao() {
        const explicacoes = {
            'completar_frase': `
                <p>Para completar frases corretamente:</p>
                <ul>
                    <li>Identifique o tipo de palavra que falta (verbo, substantivo, adjetivo)</li>
                    <li>Observe o contexto e o tempo verbal</li>
                    <li>Verifique a concordância gramatical</li>
                </ul>
            `,
            'traducao': `
                <p>Dicas para tradução:</p>
                <ul>
                    <li>Entenda o sentido geral antes de traduzir</li>
                    <li>Cuidado com expressões idiomáticas</li>
                    <li>Mantenha o mesmo tempo verbal</li>
                </ul>
            `,
            'gramatica': `
                <p>Regras gramaticais importantes:</p>
                <ul>
                    <li>Sujeito e verbo devem concordar</li>
                    <li>Observe a ordem das palavras</li>
                    <li>Use os tempos verbais apropriados</li>
                </ul>
            `,
            'texto_livre': `
                <p>Para respostas em texto livre:</p>
                <ul>
                    <li>Seja claro e objetivo</li>
                    <li>Use vocabulário que você domina</li>
                    <li>Verifique a ortografia</li>
                </ul>
            `
        };

        return explicacoes[this.tipoExercicio] || explicacoes['texto_livre'];
    }

    // Próximo exercício
    proximoExercicio() {
        // Implementar navegação para próximo exercício
        if (typeof proximoExercicio === 'function') {
            proximoExercicio();
        } else {
            console.log('Função proximoExercicio não definida');
        }
    }

    // Atualizar contador de tentativas
    atualizarContadorTentativas() {
        const contador = document.getElementById('contador-tentativas');
        if (contador) {
            contador.innerHTML = `Tentativa: <strong>${this.tentativas}</strong> de ${this.maxTentativas}`;
        }
    }

    // Desabilitar/habilitar interface durante verificação
    desabilitarInterface(desabilitar) {
        const elementos = [
            'resposta-usuario',
            'btn-verificar',
            'btn-dica',
            'btn-limpar'
        ];

        elementos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.disabled = desabilitar;
            }
        });

        if (desabilitar) {
            const btnVerificar = document.getElementById('btn-verificar');
            if (btnVerificar) {
                btnVerificar.textContent = '⏳ Verificando...';
            }
        } else {
            const btnVerificar = document.getElementById('btn-verificar');
            if (btnVerificar) {
                btnVerificar.textContent = '✓ Verificar Resposta';
            }
        }
    }

    // Mostrar erro
    mostrarErro(mensagem) {
        const container = document.getElementById('resultado-escrita');
        if (container) {
            container.innerHTML = `
                <div class="erro-escrita">
                    <span class="erro-icone">❌</span>
                    <p>${mensagem}</p>
                </div>
            `;
        }
    }
}

// Instância global
const exercicioEscrita = new ExercicioEscrita();

// Funções para integração com o sistema existente
function iniciarExercicioEscrita(exercicioId, pergunta, respostaEsperada, alternativasAceitas = [], tipoExercicio = 'texto_livre') {
    exercicioEscrita.inicializar(exercicioId, pergunta, respostaEsperada, alternativasAceitas, tipoExercicio);
}

// CSS para os estilos (adicionar ao head da página)
const estilosEscrita = `
<style>
.exercicio-escrita {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.pergunta-escrita {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}

.pergunta-escrita h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.dica {
    color: #6c757d;
    font-style: italic;
    margin: 10px 0 0 0;
}

.area-resposta {
    margin-bottom: 20px;
}

.area-resposta textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.3s;
}

.area-resposta textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.contador-caracteres {
    text-align: right;
    color: #6c757d;
    font-size: 14px;
    margin-top: 5px;
}

.acoes-escrita {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.acoes-escrita button {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-verificar {
    background: #28a745;
    color: white;
}

.btn-verificar:hover:not(:disabled) {
    background: #218838;
}

.btn-verificar:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.btn-dica {
    background: #ffc107;
    color: #212529;
}

.btn-dica:hover {
    background: #e0a800;
}

.btn-limpar {
    background: #6c757d;
    color: white;
}

.btn-limpar:hover {
    background: #5a6268;
}

.area-dica {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.dica-conteudo h4 {
    margin: 0 0 10px 0;
    color: #0056b3;
}

.feedback-escrita {
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.cabecalho-resultado {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.icone-resultado {
    font-size: 2em;
}

.pontuacao-escrita {
    background: rgba(255,255,255,0.8);
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    margin-left: auto;
}

.comparacao-respostas {
    background: rgba(255,255,255,0.5);
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.resposta-usuario-display, .resposta-esperada-display {
    margin: 8px 0;
    padding: 8px;
    border-radius: 4px;
}

.resposta-usuario-display {
    background: rgba(255,255,255,0.7);
}

.resposta-esperada-display {
    background: rgba(40,167,69,0.1);
}

.pontos-positivos {
    color: #155724;
    margin: 15px 0;
}

.pontos-positivos h4 {
    margin-bottom: 10px;
}

.pontos-positivos ul {
    margin: 5px 0 0 20px;
}

.erros-encontrados {
    color: #721c24;
    margin: 15px 0;
}

.erros-encontrados h4 {
    margin-bottom: 10px;
}

.erros-encontrados ul {
    margin: 5px 0 0 20px;
}

.erros-encontrados li {
    margin: 8px 0;
}

.sugestoes-melhoria {
    color: #856404;
    margin: 15px 0;
}

.sugestoes-melhoria h4 {
    margin-bottom: 10px;
}

.sugestoes-melhoria ul {
    margin: 5px 0 0 20px;
}

.acoes-resultado {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.acoes-resultado button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-proximo {
    background: #007bff;
    color: white;
}

.btn-proximo:hover {
    background: #0056b3;
}

.btn-tentar-novamente {
    background: #6c757d;
    color: white;
}

.btn-tentar-novamente:hover {
    background: #5a6268;
}

.btn-mostrar-resposta {
    background: #17a2b8;
    color: white;
}

.btn-mostrar-resposta:hover {
    background: #138496;
}

.btn-explicacao {
    background: #6f42c1;
    color: white;
}

.btn-explicacao:hover {
    background: #5a32a3;
}

.info-tentativas {
    text-align: center;
    color: #6c757d;
    font-size: 14px;
    margin-top: 15px;
}

.explicacao-detalhada {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.explicacao-detalhada h4 {
    margin: 0 0 15px 0;
    color: #495057;
}

.conteudo-explicacao ul {
    margin: 10px 0 0 20px;
}

.btn-fechar-explicacao {
    background: #6c757d;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 15px;
}

.btn-fechar-explicacao:hover {
    background: #5a6268;
}

.erro-escrita {
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

@media (max-width: 768px) {
    .exercicio-escrita {
        padding: 15px;
    }
    
    .acoes-escrita, .acoes-resultado {
        flex-direction: column;
    }
    
    .acoes-escrita button, .acoes-resultado button {
        width: 100%;
    }
    
    .cabecalho-resultado {
        flex-direction: column;
        text-align: center;
    }
    
    .pontuacao-escrita {
        margin-left: 0;
    }
}
</style>
`;

// Adicionar estilos ao head se não existirem
if (!document.getElementById('estilos-escrita')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'estilos-escrita';
    styleElement.innerHTML = estilosEscrita;
    document.head.appendChild(styleElement);
}

