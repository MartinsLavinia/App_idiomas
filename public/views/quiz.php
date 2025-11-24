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
        --roxo-principal: #6a0dad;
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
        padding-top: 0;
        margin: 0;
    }

    /* Logo Centralizada */
    .logo-container {
        text-align: center;
        padding: 2rem 1rem 1rem;
        background-color: var(--fundo-pagina);
    }
    
    .logo-img {
        height: 120px;
        max-width: 100%;
        object-fit: contain;
    }

    /* CONTAINER MAIS EQUILIBRADO */
    .resultado-container {
        width: 100%;
        max-width: 1100px; /* Tamanho equilibrado */
        margin: 0 auto 2rem;
        padding: 0 1rem;
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
    
    /* Header equilibrado */
    .resultado-header {
        background-color: #6a0dad;
        background-image: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
        color: white;
        padding: 2.5rem; /* Padding equilibrado */
        text-align: center;
    }
    
    .pontuacao-display {
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        width: 160px; /* Tamanho equilibrado */
        height: 160px; /* Tamanho equilibrado */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 1.5rem auto;
        border: 4px solid rgba(255,255,255,0.3);
    }
    
    .pontuacao-numero {
        font-size: 3.2em; /* Tamanho equilibrado */
        font-weight: 800;
        line-height: 1;
    }
    
    .pontuacao-texto {
        font-size: 1em; /* Tamanho original */
        opacity: 0.9;
    }
    
    .performance-badge {
        display: inline-block;
        padding: 8px 20px; /* Tamanho original */
        border-radius: 25px;
        font-weight: bold;
        margin: 10px 0;
        color: white;
        background-color: transparent !important;
        position: relative;
        font-size: 1em; /* Tamanho original */
    }
    
    /* Corpo do card equilibrado */
    .resultado-body {
        padding: 2.5rem; /* Padding equilibrado */
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2.5rem; /* Gap equilibrado */
        align-items: start;
    }
    
    /* Estilos para as estatísticas e seções equilibrados */
    .estatisticas-container {
        grid-column: 1;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .secoes-container {
        grid-column: 2;
    }

    .detalhes-resultado, .secao {
        background: var(--fundo-pagina);
        padding: 2rem; /* Padding equilibrado */
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
        padding: 1.5rem; /* Padding equilibrado */
        border-radius: 12px;
        background: var(--fundo-card);
        border-left: 5px solid var(--roxo-principal);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .estatistica-numero {
        font-size: 2em; /* Tamanho equilibrado */
        font-weight: 700;
        color: var(--roxo-principal);
    }
    
    .estatistica-label {
        font-size: 0.9em; /* Tamanho original */
        color: #6c757d;
        margin-top: 5px;
    }
    
    .secao.hidden {
        display: none;
    }
    
    .nivel-badge {
        background: var(--amarelo-destaque);
        color: var(--cinza-texto);
        padding: 10px 25px; /* Tamanho equilibrado */
        border-radius: 50px;
        font-weight: bold;
        display: inline-block;
        margin: 1rem 0;
        box-shadow: 0 4px 10px rgba(245, 197, 49, 0.2);
        font-size: 1.2em; /* Tamanho equilibrado */
    }
    
    /* Botões equilibrados */
    .btn-custom {
        border-radius: 25px;
        padding: 12px 25px; /* Tamanho equilibrado */
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        margin: 5px;
        font-size: 1em; /* Tamanho original */
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
        gap: 10px; /* Gap equilibrado */
        margin: 1.5rem 0;
        justify-content: center;
    }
    
    .btn-nivel {
        background-color: var(--roxo-principal);
        color: white;
        border: none;
        padding: 10px 20px; /* Tamanho equilibrado */
        border-radius: 20px;
        font-weight: bold;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 1em; /* Tamanho original */
        min-width: 180px; /* Largura equilibrada */
    }
    
    .btn-nivel:hover {
        background-color: var(--roxo-claro);
        transform: translateY(-2px);
    }

    /* Títulos equilibrados */
    h3.fw-bold {
        font-size: 1.8em; /* Tamanho equilibrado */
    }
    
    h4.fw-bold {
        font-size: 1.5em; /* Tamanho equilibrado */
    }
    
    h5 {
        font-size: 1.3em; /* Tamanho equilibrado */
    }

    p {
        font-size: 1em; /* Tamanho original */
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

    /* RESPONSIVIDADE */
    @media (max-width: 1200px) {
        .resultado-container {
            max-width: 100%;
            padding: 0 1rem;
        }
    }

    @media (max-width: 992px) {
        .resultado-body {
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 2rem;
        }
        .detalhes-resultado, .secao {
            padding: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 0.5rem;
        }
        .resultado-container {
            margin: 0.5rem auto;
        }
        .resultado-card {
            border-radius: 15px;
        }
        .resultado-header {
            padding: 2rem 1.5rem;
        }
        .resultado-body {
            padding: 1.5rem;
        }
        .detalhes-resultado, .secao {
            padding: 1.5rem;
        }
        .estatisticas {
            grid-template-columns: 1fr 1fr;
        }
        .btn-custom {
            width: calc(50% - 10px);
            margin: 5px 0;
        }
        .logo-img {
            height: 100px;
        }
        .pontuacao-display {
            width: 140px;
            height: 140px;
        }
        .pontuacao-numero {
            font-size: 2.8em;
        }
    }

    @media (max-width: 576px) {
        .resultado-header {
            padding: 1.5rem 1rem;
        }
        .estatisticas {
            grid-template-columns: 1fr;
        }
        .btn-custom {
            width: 100%;
            margin: 5px 0;
        }
        .logo-img {
            height: 80px;
        }
        .pontuacao-display {
            width: 120px;
            height: 120px;
        }
        .pontuacao-numero {
            font-size: 2.5em;
        }
    }

    .btn-aceitar {
        background: transparent;
        color: var(--cinza-texto);
        border: 2px solid var(--verde-sucesso);
        min-width: 160px; /* Tamanho equilibrado */
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
        min-width: 180px; /* Tamanho equilibrado */
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
    <!-- Logo Centralizada -->
    <div class="logo-container">
        <img src="../../imagens/logo-idiomas.png" alt="Logo" class="logo-img">
    </div>

    <div class="loading-overlay position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" id="loadingOverlay">
        <div class="loading-content text-center p-4 bg-white rounded-4 shadow-sm">
            <div class="spinner-roxo"></div>
            <h4>Salvando seu progresso...</h4>
            <p>Aguarde um momento</p>
        </div>
    </div>

    <!-- CONTAINER EQUILIBRADO -->
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

        // Variável para controlar se o redirecionamento é permitido
        let redirecionamentoPermitido = false;

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
            // Marca que o redirecionamento é permitido
            redirecionamentoPermitido = true;
            
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');
            
            // Redireciona para o painel com o idioma e nível escolhidos
            window.location.href = `painel.php?idioma=<?php echo htmlspecialchars($idioma_quiz); ?>&nivel_escolhido=${nivel}`;
        }

        // Modifica o evento beforeunload para não bloquear quando o redirecionamento é permitido
        window.addEventListener('beforeunload', function(e) {
            if (loadingOverlay.classList.contains('d-flex') && !redirecionamentoPermitido) {
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