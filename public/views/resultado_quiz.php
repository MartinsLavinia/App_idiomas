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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .resultado-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .resultado-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .resultado-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .pontuacao-display {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .pontuacao-numero {
            font-size: 2.5em;
            font-weight: bold;
            line-height: 1;
        }
        
        .pontuacao-texto {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .performance-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px 0;
            background: rgba(255,255,255,0.2);
        }
        
        .resultado-body {
            padding: 30px;
        }
        
        .detalhes-resultado {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .nivel-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .estatisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .estatistica-item {
            background: white;
            padding: 20px 15px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .estatistica-numero {
            font-size: 1.8em;
            font-weight: bold;
            color: #007bff;
        }
        
        .estatistica-label {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .secao {
            margin: 20px 0;
            padding: 20px;
            border-radius: 15px;
            background: #f8f9fa;
        }
        
        .secao.hidden {
            display: none;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            margin: 5px;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .opcoes-niveis {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .btn-nivel {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-nivel:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .resultado-container {
                padding: 10px;
            }
            
            .resultado-header {
                padding: 30px 20px;
            }
            
            .resultado-body {
                padding: 20px;
            }
            
            .estatisticas {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h4>Salvando seu progresso...</h4>
            <p>Aguarde um momento</p>
        </div>
    </div>

    <div class="resultado-container">
        <div class="resultado-card">
            <div class="resultado-header">
                <h3><?php echo $icone_performance; ?> <?php echo $texto_performance; ?></h3>
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
                        <h4>Seu nível determinado:</h4>
                        <div class="nivel-badge"><?php echo htmlspecialchars($nivel_determinado); ?></div>
                    </div>
                    
                    <h5 id="tituloNivel"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['titulo'] ?? 'Nível ' . $nivel_determinado); ?></h5>
                    <p id="conteudoNivel"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['descricao'] ?? ''); ?></p>
                    
                    <?php if ($percentual < 60): ?>
                        <div class="alert alert-info">
                            <strong>💡 Dica:</strong> Com <?php echo $percentual; ?>% de aproveitamento, recomendamos revisar os conteúdos básicos antes de prosseguir.
                        </div>
                    <?php elseif ($percentual >= 80): ?>
                        <div class="alert alert-success">
                            <strong>🎉 Parabéns!</strong> Excelente desempenho! Você está pronto para os desafios do nível <?php echo $nivel_determinado; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    <p><strong>Você gostaria de ficar neste nível?</strong></p>
                    <div class="text-center">
                        <button type="button" class="btn btn-success btn-custom" onclick="confirmarNivelFinal('<?php echo htmlspecialchars($nivel_determinado); ?>')">
                            ✓ Sim, aceitar nível
                        </button>
                        <button type="button" class="btn btn-warning btn-custom" onclick="mostrarEscolhaNivel()">
                            ↔️ Escolher outro nível
                        </button>
                    </div>
                </div>

                <div id="secaoMudarNivel" class="secao hidden">
                    <h4>Escolher outro nível</h4>
                    <p><strong>Você quer um nível mais fácil ou mais difícil?</strong></p>
                    <div class="text-center">
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="mostrarNiveisAbaixo()">
                            📉 Nível Mais Fácil
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="mostrarNiveisAcima()">
                            📈 Nível Mais Difícil
                        </button>
                    </div>
                    <div id="opcoesNiveisDinamicas" class="mt-3"></div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary btn-custom" onclick="voltarNivelDeterminado()">
                            ← Voltar
                        </button>
                    </div>
                </div>

                <div id="secaoConfirmacao" class="secao hidden">
                    <h4 id="tituloNivelConfirmar"></h4>
                    <p id="conteudoNivelConfirmar"></p>
                    <hr>
                    <p><strong>Você tem certeza que quer este nível?</strong></p>
                    <div class="text-center">
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

        // Elementos do DOM
        const secaoNivelDeterminado = document.getElementById('secaoNivelDeterminado');
        const secaoMudarNivel = document.getElementById('secaoMudarNivel');
        const secaoConfirmacao = document.getElementById('secaoConfirmacao');
        const opcoesNiveisDinamicas = document.getElementById('opcoesNiveisDinamicas');
        const loadingOverlay = document.getElementById('loadingOverlay');

        function mostrarSecao(secaoAtiva) {
            // Esconder todas as seções
            document.querySelectorAll('.secao').forEach(secao => {
                secao.classList.add('hidden');
            });
            
            // Mostrar seção ativa
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
            // Mostrar loading
            loadingOverlay.style.display = 'flex';
            
            // Simular salvamento e redirecionar
            setTimeout(() => {
                window.location.href = `painel.php?idioma=<?php echo htmlspecialchars($idioma_quiz); ?>&nivel_escolhido=${nivel}&acertos=<?php echo $acertos; ?>&total=<?php echo $total_perguntas; ?>&percentual=<?php echo $percentual; ?>`;
            }, 2000);
        }

        // Prevenir fechamento acidental da página
        window.addEventListener('beforeunload', function(e) {
            if (!loadingOverlay.style.display || loadingOverlay.style.display === 'none') {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Garantir que a página não fique em branco
        document.addEventListener('DOMContentLoaded', function() {
            // Forçar exibição do conteúdo
            document.body.style.visibility = 'visible';
            document.body.style.opacity = '1';
        });

        // Fallback para garantir que o conteúdo seja exibido
        setTimeout(() => {
            document.body.style.visibility = 'visible';
            document.body.style.opacity = '1';
        }, 100);
    </script>
</body>
</html>
