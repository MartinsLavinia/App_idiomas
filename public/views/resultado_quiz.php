<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Pega o idioma, nível e pontuação da URL
$idioma_quiz = $_GET['idioma'] ?? 'Ingles';
$nivel_determinado = $_GET['nivel'] ?? 'A1';
$acertos = $_GET['acertos'] ?? 0;
$total_perguntas = $_GET['total'] ?? 30;
$percentual = $total_perguntas > 0 ? round(($acertos / $total_perguntas) * 100) : 0;

// **********************************************
// *** NOVO TRECHO: LÓGICA DE CLASSIFICAÇÃO DE 6 NÍVEIS BASEADA EM PERCENTUAL ***
// **********************************************
// Se você quiser que o nível exibido ao usuário reflita a classificação por %:
if ($percentual >= 95) {
    $nivel_determinado = 'C2'; 
} elseif ($percentual >= 90) {
    $nivel_determinado = 'C1';
} elseif ($percentual >= 80) {
    $nivel_determinado = 'B2';
} elseif ($percentual >= 65) {
    $nivel_determinado = 'B1';
} elseif ($percentual >= 45) {
    $nivel_determinado = 'A2';
} else {
    $nivel_determinado = 'A1'; 
}
// Mapeamento dos níveis para uma ordem
$niveis = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];


// Conteúdo detalhado para cada nível
$conteudo_niveis = [
    'A1' => [
        'titulo' => 'Nível A1: Básico',
        'descricao' => 'Conteúdos do Nível A1: Vocabulário básico, cumprimentos, verbo "to be".'
    ],
    'A2' => [
        'titulo' => 'Nível A2: Pré-Intermediário',
        'descricao' => 'Conteúdos do Nível A2: Presente simples, preposições de lugar, rotinas.'
    ],
    'B1' => [
        'titulo' => 'Nível B1: Intermediário',
        'descricao' => 'Conteúdos do Nível B1: Tempos verbais do passado, futuro, conversas sobre viagens.'
    ],
    'B2' => [
        'titulo' => 'Nível B2: Intermediário Avançado',
        'descricao' => 'Conteúdos do Nível B2: Compreensão de textos complexos e conversas fluentes.'
    ],
    'C1' => [
        'titulo' => 'Nível C1: Avançado',
        'descricao' => 'Conteúdos do Nível C1: Uso avançado da língua, expressões idiomáticas.'
    ],
    'C2' => [
        'titulo' => 'Nível C2: Proficiência',
        'descricao' => 'Conteúdos do Nível C2: Proficiência, fluência e precisão em qualquer contexto.'
    ]
];

// Determinar cor e ícone baseado na performance
$cor_performance = '#6c757d';
$icone_performance = '📊';
$texto_performance = 'Resultado';

if ($percentual >= 80) {
    $cor_performance = '#28a745';
    $icone_performance = '🏆';
    $texto_performance = 'Excelente!';
} elseif ($percentual >= 60) {
    $cor_performance = '#ffc107';
    $icone_performance = '⭐';
    $texto_performance = 'Bom trabalho!';
} elseif ($percentual >= 40) {
    $cor_performance = '#fd7e14';
    $icone_performance = '📈';
    $texto_performance = 'Pode melhorar';
} else {
    $cor_performance = '#dc3545';
    $icone_performance = '📚';
    $texto_performance = 'Continue estudando';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Variáveis de Cores */
    :root {
        --roxo-principal: #5e3b8b;
        --roxo-claro: #8a6aae;
        --amarelo-destaque: #f5c531;
        --amarelo-botao: #ffd700;
        --amarelo-hover: #e7c500;
        --cinza-texto: #343a40;
        --fundo-pagina: #f8f9fa;
        --fundo-card: #ffffff;
        --verde-sucesso: #25da01ff;
    }

    /* Estilo do corpo da página */
    body {
        background-color: var(--fundo-pagina);
        font-family: 'Poppins', sans-serif;
        color: var(--cinza-texto);
        line-height: 1.6;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start; /* Alinha o card no topo em telas maiores */
        padding: 3rem 1rem;
    }

    /* Container e Card */
    .resultado-container {
        width: 100%;
        max-width: 1000px;
    }
    
    .resultado-card {
        background: var(--fundo-card);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
        width: 100%;
        margin: auto;
        animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Header (Cabeçalho do card) */
    .resultado-header {
        background: var(--gradiente-roxo);
        background-image: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
        color: white;
        padding: 3rem;
        text-align: center;
    }
    
    .pontuacao-display {
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        width: 150px;
        height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 1.5rem auto;
        border: 4px solid rgba(255,255,255,0.3);
    }
    
    .pontuacao-numero {
        font-size: 3.5em;
        font-weight: 800;
        line-height: 1;
    }
    
    .pontuacao-texto {
        font-size: 1em;
        opacity: 0.9;
    }
    
    .performance-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 25px;
        font-weight: bold;
        margin: 10px 0;
        color: white;
        background-color: transparent !important;
        position: relative;
    }
    
    /* Corpo do card com layout em duas colunas para desktop */
    .resultado-body {
        padding: 2.5rem;
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 3rem;
        align-items: start;
    }
    
    /* Estilos para as estatísticas e seções */
    .estatisticas-container {
        grid-column: 1; /* Coluna das estatísticas */
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .secoes-container {
        grid-column: 2; /* Coluna das seções */
    }

    .detalhes-resultado, .secao {
        background: var(--fundo-pagina);
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    
    .estatisticas {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .estatistica-item {
        padding: 1.5rem;
        border-radius: 12px;
        background: var(--fundo-card);
        border-left: 5px solid var(--roxo-principal);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        text-align: center;
    }
    
    .estatistica-numero {
        font-size: 2em;
        font-weight: 700;
        color: var(--roxo-principal);
    }
    
    .estatistica-label {
        font-size: 0.9em;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .secao.hidden {
        display: none;
    }
    
    .nivel-badge {
        background: var(--amarelo-destaque);
        color: var(--cinza-texto);
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: bold;
        display: inline-block;
        margin: 1rem 0;
        box-shadow: 0 4px 10px rgba(245, 197, 49, 0.2);
    }
    
    /* Botões */
    .btn-custom {
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        margin: 5px;
    }
    
    .btn-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    
    .btn-outline-primary {
        color: var(--roxo-principal) !important;
        border-color: var(--roxo-principal) !important;
    }
    .btn-outline-primary:hover {
        background-color: var(--roxo-principal) !important;
        color: white !important;
    }

    .opcoes-niveis {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 1.5rem 0;
    }
    
    .btn-nivel {
        background-color: var(--roxo-principal);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: bold;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-nivel:hover {
        background-color: var(--roxo-claro);
        transform: translateY(-2px);
    }

    /* Loading Overlay */
    .loading-overlay {
        background: rgba(255,255,255,0.95);
        z-index: 9999;
    }
    .spinner-roxo {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--roxo-principal);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsividade */
    @media (min-width: 769px) {
        .resultado-body {
            grid-template-columns: 1fr 2fr;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
            align-items: center;
        }
        .resultado-card {
            border-radius: 15px;
        }
        .resultado-body {
            grid-template-columns: 1fr; /* Volta a ser uma única coluna */
            gap: 1.5rem;
        }
        .detalhes-resultado, .secao {
            padding: 1.5rem;
        }
        .estatisticas {
            grid-template-columns: 1fr 1fr; /* As estatísticas ficam em duas colunas */
        }
        .btn-custom {
            width: calc(50% - 10px);
            margin: 5px 0;
        }
        .btn-custom.w-100 { /* Classe para os botões que devem ocupar 100% em mobile */
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .resultado-header {
            padding: 2.5rem 1.5rem;
        }
        .estatisticas {
            grid-template-columns: 1fr; /* Em telas muito pequenas, uma coluna */
        }
        .btn-custom {
            width: 100%; /* Botões ocupam 100% da largura */
            margin: 5px 0;
        }
    }

    .btn-aceitar {
    background: transparent;
    color: var(--cinza-texto);
    border: 2px solid var(--verde-sucesso);
    min-width: 150px;
}

.btn-aceitar:hover {
    background: transparent;
    color: var(--cinza-texto);
    transform: translateY(-2px);
    border: 2px solid var(--verde-sucesso);
    box-shadow: 0 4px 15px rgba(0, 158, 34, 0.3);
}

.btn-escolha {
    background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
    color: var(--cinza-texto);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    min-width: 180px;
}

.btn-escolha:hover {
    background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
    color: var(--cinza-texto);
}

    </style>
</head>
<body>
    <div class="loading-overlay position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" id="loadingOverlay">
        <div class="loading-content text-center p-4 bg-white rounded-4 shadow-sm">
            <div class="spinner-roxo"></div>
            <h4>Salvando seu progresso...</h4>
            <p>Aguarde um momento</p>
        </div>
    </div>

    <div class="resultado-container">
        <div class="resultado-card">
            <div class="resultado-header">
                <h3 class="fw-bold"><?php echo $icone_performance; ?> <?php echo $texto_performance; ?></h3>
                <div class="pontuacao-display">
                    <div class="pontuacao-numero"><?php echo $percentual; ?>%</div>
                    <div class="pontuacao-texto">de aproveitamento</div>
                </div>
                <div class="performance-badge" style="background-color: <?php echo $cor_performance; ?>;">
                    <?php echo $acertos; ?> de <?php echo $total_perguntas; ?> acertos
                </div>
            </div>
            
            <div class="resultado-body">
                <div class="detalhes-resultado">
                    <div class="estatisticas">
                        <div class="estatistica-item">
                            <div class="estatistica-numero"><?php echo $acertos; ?></div>
                            <div class="estatistica-label">Acertos</div>
                        </div>
                        <div class="estatistica-item">
                            <div class="estatistica-numero"><?php echo $total_perguntas - $acertos; ?></div>
                            <div class="estatistica-label">Erros</div>
                        </div>
                        <div class="estatistica-item">
                            <div class="estatistica-numero"><?php echo $total_perguntas; ?></div>
                            <div class="estatistica-label">Total</div>
                        </div>
                        <div class="estatistica-item">
                            <div class="estatistica-numero"><?php echo $percentual; ?>%</div>
                            <div class="estatistica-label">Aproveitamento</div>
                        </div>
                    </div>
                </div>
                
                <div id="secaoNivelDeterminado" class="secao">
                    <div class="text-center">
                        <h4 class="fw-bold mb-3">Seu nível determinado:</h4>
                        <div class="nivel-badge"><?php echo htmlspecialchars($nivel_determinado); ?></div>
                    </div>
                    
                    <h5 id="tituloNivel" class="mt-4 text-center"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['titulo'] ?? 'Nível ' . $nivel_determinado); ?></h5>
                    <p id="conteudoNivel" class="text-center text-muted"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['descricao'] ?? ''); ?></p>
                    
                    <?php if ($percentual < 60): ?>
                        <div class="alert alert-info border-info-subtle mt-4 text-center">
                            <strong>💡 Dica:</strong> Com <?php echo $percentual; ?>% de aproveitamento, recomendamos revisar os conteúdos básicos antes de prosseguir.
                        </div>
                    <?php elseif ($percentual >= 80): ?>
                        <div class="alert alert-success border-success-subtle mt-4 text-center">
                            <strong>🎉 Parabéns!</strong> Excelente desempenho! Você está pronto para os desafios do nível <?php echo $nivel_determinado; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    <p class="text-center"><strong>Você gostaria de ficar neste nível?</strong></p>
                    <div class="text-center d-flex justify-content-center flex-wrap">
                        <button type="button" class="btn btn-escolha btn-custom" onclick="confirmarNivelFinal('<?php echo htmlspecialchars($nivel_determinado); ?>')">
                            ✓ Sim, aceitar nível
                        </button>
                        <button type="button" class="btn btn-aceitar btn-custom" onclick="mostrarEscolhaNivel()">
                            ↔️ Escolher outro nível
                        </button>
                    </div>
                </div>

                <div id="secaoMudarNivel" class="secao hidden">
                    <h4 class="fw-bold text-center">Escolher outro nível</h4>
                    <p class="text-center text-muted">Você quer um nível mais fácil ou mais difícil?</p>
                    <div class="text-center d-flex justify-content-center flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="mostrarNiveisAbaixo()">
                            📉 Nível Mais Fácil
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="mostrarNiveisAcima()">
                            📈 Nível Mais Difícil
                        </button>
                    </div>
                    <div id="opcoesNiveisDinamicas" class="mt-3 text-center"></div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-aceitar btn-custom" onclick="voltarNivelDeterminado()">
                            ← Voltar
                        </button>
                    </div>
                </div>

                <div id="secaoConfirmacao" class="secao hidden">
                    <h4 id="tituloNivelConfirmar" class="fw-bold text-center"></h4>
                    <p id="conteudoNivelConfirmar" class="text-center text-muted"></p>
                    <hr class="my-4">
                    <p class="text-center"><strong>Você tem certeza que quer este nível?</strong></p>
                    <div class="text-center d-flex justify-content-center flex-wrap">
                        <button type="button" class="btn btn-success btn-custom" onclick="confirmarNivelEscolhido()">
                            ✓ Sim, tenho certeza
                        </button>
                        <button type="button" class="btn btn-danger btn-custom" onclick="cancelarConfirmacao()">
                            ✗ Não, cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const niveis = <?php echo json_encode($niveis); ?>;
        const conteudo_niveis = <?php echo json_encode($conteudo_niveis); ?>;
        const nivelAtualIndex = niveis.indexOf('<?php echo htmlspecialchars($nivel_determinado); ?>');
        let nivelSelecionado = '<?php echo htmlspecialchars($nivel_determinado); ?>';

        const secaoNivelDeterminado = document.getElementById('secaoNivelDeterminado');
        const secaoMudarNivel = document.getElementById('secaoMudarNivel');
        const secaoConfirmacao = document.getElementById('secaoConfirmacao');
        const opcoesNiveisDinamicas = document.getElementById('opcoesNiveisDinamicas');
        const loadingOverlay = document.getElementById('loadingOverlay');

        function mostrarSecao(secaoAtiva) {
            document.querySelectorAll('.secao').forEach(secao => {
                secao.classList.add('hidden');
            });
            secaoAtiva.classList.remove('hidden');
        }

        function mostrarEscolhaNivel() {
            mostrarSecao(secaoMudarNivel);
        }

        function voltarNivelDeterminado() {
            mostrarSecao(secaoNivelDeterminado);
            opcoesNiveisDinamicas.innerHTML = '';
        }

        function mostrarNiveisAbaixo() {
            let opcoesHtml = '';
            for (let i = 0; i < nivelAtualIndex; i++) {
                const nivel = niveis[i];
                opcoesHtml += `
                    <button type="button" class="btn-nivel" onclick="mostrarConfirmacao('${nivel}')">
                        ${conteudo_niveis[nivel].titulo}
                    </button>
                `;
            }
            
            if (opcoesHtml === '') {
                opcoesHtml = '<p class="text-muted text-center">Não há níveis mais baixos disponíveis.</p>';
            } else {
                opcoesHtml = '<div class="opcoes-niveis">' + opcoesHtml + '</div>';
            }
            
            opcoesNiveisDinamicas.innerHTML = opcoesHtml;
        }

        function mostrarNiveisAcima() {
            let opcoesHtml = '';
            for (let i = nivelAtualIndex + 1; i < niveis.length; i++) {
                const nivel = niveis[i];
                opcoesHtml += `
                    <button type="button" class="btn-nivel" onclick="mostrarConfirmacao('${nivel}')">
                        ${conteudo_niveis[nivel].titulo}
                    </button>
                `;
            }
            
            if (opcoesHtml === '') {
                opcoesHtml = '<p class="text-muted text-center">Não há níveis mais avançados disponíveis.</p>';
            } else {
                opcoesHtml = '<div class="opcoes-niveis">' + opcoesHtml + '</div>';
            }
            
            opcoesNiveisDinamicas.innerHTML = opcoesHtml;
        }

        function mostrarConfirmacao(nivel) {
            nivelSelecionado = nivel;
            document.getElementById('tituloNivelConfirmar').innerText = conteudo_niveis[nivel].titulo;
            document.getElementById('conteudoNivelConfirmar').innerText = conteudo_niveis[nivel].descricao;
            mostrarSecao(secaoConfirmacao);
        }

        function cancelarConfirmacao() {
            mostrarSecao(secaoMudarNivel);
        }

        function confirmarNivelEscolhido() {
            confirmarNivelFinal(nivelSelecionado);
        }

        function confirmarNivelFinal(nivel) {
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');
            
            setTimeout(() => {
                window.location.href = `painel.php?idioma=<?php echo htmlspecialchars($idioma_quiz); ?>&nivel_escolhido=${nivel}&acertos=<?php echo $acertos; ?>&total=<?php echo $total_perguntas; ?>&percentual=<?php echo $percentual; ?>`;
            }, 2000);
        }

        // Prevenir fechamento acidental da página
        window.addEventListener('beforeunload', function(e) {
            if (loadingOverlay.classList.contains('d-flex')) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Garantir que a página não fique em branco
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '1';
        });

        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    </script>
</body>
</html>