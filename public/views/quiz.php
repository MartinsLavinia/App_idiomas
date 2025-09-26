<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . '/../../conexao.php';
// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Lógica de processamento do formulário do quiz
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['id_usuario'];
    $idioma_quiz = $_POST['idioma'];
    $respostas_usuario = $_POST['resposta'];
    $ids_perguntas = array_keys($respostas_usuario);
    
    // Constrói a consulta SQL para buscar as respostas corretas
    $placeholders = implode(',', array_fill(0, count($ids_perguntas), '?'));
    $sql_respostas = "SELECT id, resposta_correta, nivel FROM quiz_nivelamento WHERE id IN ($placeholders)";
    
    $stmt_respostas = $conn->prepare($sql_respostas);
    $tipos = str_repeat('i', count($ids_perguntas));
    $stmt_respostas->bind_param($tipos, ...$ids_perguntas);
    $stmt_respostas->execute();
    $respostas_corretas_db = $stmt_respostas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_respostas->close();
    
    // Mapeia as respostas corretas para fácil acesso
    $respostas_corretas_map = [];
    foreach ($respostas_corretas_db as $item) {
        $respostas_corretas_map[$item['id']] = ['resposta' => $item['resposta_correta'], 'nivel' => $item['nivel']];
    }
    
    // Novo cálculo de pontuação para uma avaliação mais precisa
    $pontuacao_total = 0;
    $total_perguntas = count($respostas_usuario);
    foreach ($respostas_usuario as $id_pergunta => $resposta_do_usuario) {
        if (isset($respostas_corretas_map[$id_pergunta]) && $respostas_corretas_map[$id_pergunta]['resposta'] === $resposta_do_usuario) {
            $pontuacao_total++;
        }
    }
    
    // Determina o nível final com base na pontuação total (agora com 6 níveis)
  $percentual = $total_perguntas > 0 ? round(($pontuacao_total / $total_perguntas) * 100) : 0;
$nivel_final = 'A1'; // Deixa como A1 ou usa uma classificação simplificada. Vamos usar a nova lógica para garantir que o nível salvo no banco seja o mesmo que o usuário vê.

if ($percentual >= 95) { $nivel_final = 'C2'; } 
elseif ($percentual >= 90) { $nivel_final = 'C1'; } 
elseif ($percentual >= 80) { $nivel_final = 'B2'; } 
elseif ($percentual >= 65) { $nivel_final = 'B1'; } 
elseif ($percentual >= 45) { $nivel_final = 'A2'; } 
else { $nivel_final = 'A1'; } 
    
    // Atualiza o nível do usuário no banco de dados
    $sql_update = "UPDATE progresso_usuario SET nivel = ? WHERE id_usuario = ? AND idioma = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sis", $nivel_final, $id_usuario, $idioma_quiz);
    $stmt_update->execute();
    $stmt_update->close();
    
    // Fecha a conexão
    $database->closeConnection();
    
    // Redireciona para a página de resultados com a pontuação
    header("Location: resultado_quiz.php?idioma=$idioma_quiz&nivel=$nivel_final&acertos=$pontuacao_total&total=$total_perguntas");
    exit();
}

// Lógica para exibir o formulário do quiz (requisição GET)
$idioma_quiz = isset($_GET['idioma']) ? $_GET['idioma'] : null;
if (!$idioma_quiz) {
    header("Location: painel.php");
    exit();
}

// Busca perguntas de todos os 6 níveis em uma única consulta
$sql_perguntas = "
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'A1' ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'A2' ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'B1' ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'B2' ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'C1' ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'C2' ORDER BY RAND() LIMIT 5)
    ORDER BY RAND()
";
$stmt_perguntas = $conn->prepare($sql_perguntas);
$stmt_perguntas->bind_param("ssssss", $idioma_quiz, $idioma_quiz, $idioma_quiz, $idioma_quiz, $idioma_quiz, $idioma_quiz);
$stmt_perguntas->execute();
$perguntas_quiz = $stmt_perguntas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_perguntas->close();

// Fecha a conexão
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz de Nivelamento - <?php echo htmlspecialchars($idioma_quiz); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Paleta de Cores */
:root {
    --roxo-principal: #5e3b8b; /* Roxo escuro e profundo */
    --roxo-claro: #8a6aae;    /* Roxo mais suave para toques */
    --amarelo-destaque: #f5c531; /* Amarelo vibrante e energizante */
    --amarelo-botao: #ffd700;
    --amarelo-hover: #e7c500;
    --amarelo-claro: #fcf1d0;    /* Amarelo muito suave, quase creme */
    --branco-fundo: #f8f9fa;     /* Fundo branco suave */
    --cinza-texto: #444;       /* Cinza escuro para textos */
    --gradiente-btn: linear-gradient(45deg, #f5c531, #ffec8b); /* Gradiente amarelo mais suave */
    --gradiente-btn-hover: linear-gradient(45deg, #7a54a3, #5e3b8b); /* Gradiente roxo para hover */
    --gradiente-progresso: linear-gradient(90deg, #fff9b0, #ffe344, #ffc107); /* Gradiente para a barra de progresso */
}

body {
    background-color: var(--branco-fundo);
    font-family: 'Poppins', sans-serif;
    color: var(--cinza-texto);
    line-height: 1.6;
}

/* Container principal */
.container {
    padding-top: 3rem;
    padding-bottom: 3rem;
}

/* Estilo do card principal do quiz */
.quiz-card {
    background-color: #fff;
    border: none;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.8s ease-out;
}

/* Header do quiz */
.quiz-header {
    background: linear-gradient(45deg, #5e3b8b, #7a54a3);
    color: #fff;
    padding: 2.5rem !important;
    border-radius: 1.5rem 1.5rem 0 0;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

.quiz-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.quiz-subtitle {
    font-size: 1.2rem;
    font-weight: 400;
    opacity: 0.95;
}

/* Estilo da barra de progresso */
.progresso-quiz {
    background-color: #e0e0e0;
    height: 12px;
    border-radius: 6px;
    margin-top: 2rem;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progresso-preenchido {
    background: var(--gradiente-progresso);
    height: 100%;
    transition: width 0.4s ease-out;
    border-radius: 6px;
}

/* Estilo de cada bloco de pergunta */
.quiz-question {
    background-color: #fff;
    border: 1px solid #e9ecef;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.3rem 1rem rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
}

.quiz-question:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.6rem 2rem rgba(0, 0, 0, 0.12);
}

.quiz-question-title {
    color: var(--roxo-principal);
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

/* Estilo dos radio buttons e seus labels */
.form-check {
    cursor: pointer;
    padding: 1.2rem 1.8rem;
    border-radius: 0.75rem;
    margin-bottom: 0.75rem;
    border: 1px solid #ced4da;
    transition: border-color 0.2s ease-in-out;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* NOVO: Efeito de destaque ao selecionar a alternativa */
.form-check.alternativa-selecionada {
    background-color: #fff;
    border-color: var(--amarelo-destaque);
    box-shadow: 0 4px 8px rgba(245, 197, 49, 0.2);
}
.form-check:hover {
    border-color: var(--roxo-claro);
}

.form-check-label {
    color: var(--cinza-texto);
    font-size: 1.1rem;
    font-weight: 500;
    transition: color 0.15s ease-in-out;
    /* Faz o label preencher toda a área de clique */
    display: block;
    width: 100%;
    cursor: pointer;
}

/* NOVO: Garante que o input e o label se comportem como um bloco único */
.form-check input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.form-check .form-check-label {
    position: relative;
    padding-left: 2em;
}

/* NOVO: Estilo do 'radio button' personalizado */
.form-check .form-check-label::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 1.4em;
    height: 1.4em;
    border-radius: 50%;
    border: 2px solid var(--roxo-claro);
    background-color: #fff;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

/* NOVO: Estilo do 'radio button' quando checado */
.form-check input[type="radio"]:checked + .form-check-label::before {
    background-color: var(--roxo-principal); /* Bolinha roxa */
    border-color: var(--roxo-principal);
}

/* NOVO: Adiciona a bolinha branca dentro do 'radio button' quando checado */
.form-check input[type="radio"]:checked + .form-check-label::after {
    content: '';
    position: absolute;
    left: 0.4em;
    top: 50%;
    transform: translateY(-50%);
    width: 0.6em;
    height: 0.6em;
    border-radius: 50%;
    background-color: #fff;
    transition: all 0.2s ease-in-out;
}


/* Botão amarelo principal */
.btn-submit {
    background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
    color: var(--cinza-texto);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    min-width: 180px;
}

.btn-submit:hover {
    background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
    color: var(--cinza-texto);
}

/* Botão desabilitado */
.btn-secondary {
    background-color: #e9ecef !important;
    border-color: #e9ecef !important;
    color: #6c757d !important;
    cursor: not-allowed;
    pointer-events: none;
    box-shadow: none;
}

/* Oculta contador de acertos */
.contador-acertos {
    display: none;
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsividade */
@media (max-width: 768px) {
    .quiz-title {
        font-size: 2rem;
    }
    .quiz-subtitle {
        font-size: 1rem;
    }
    .quiz-card {
        border-radius: 1rem;
    }
    .quiz-header {
        padding: 1.5rem !important;
        border-radius: 1rem 1rem 0 0;
    }
    .quiz-question {
        padding: 1rem;
        border-radius: 0.75rem;
    }
    .quiz-question-title {
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }
    .form-check {
        padding: 1rem 1.2rem;
        border-radius: 0.5rem;
    }
    .form-check-label {
        font-size: 1rem;
    }
    .form-check .form-check-label::before {
        width: 1.3em;
        height: 1.3em;
    }
    .form-check input[type="radio"]:checked + .form-check-label::after {
        left: 0.35em;
        width: 0.6em;
        height: 0.6em;
    }
    .btn-submit {
        font-size: 1.1rem;
        padding: 0.7rem 2rem;
    }
}

/* Container da Animação de Fundo */
#background-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: -1; /* Garante que a animação fique atrás de todo o conteúdo */
}

/* Estilo das Bolhas */
.bubble {
    position: absolute;
    bottom: -100px; /* Começa fora da tela, na parte inferior */
    background: rgba(94, 59, 139, 0.2); /* Roxo semitransparente */
    border-radius: 50%;
    opacity: 0; /* Começa invisível */
    animation: float-up linear infinite; /* Animação principal de subida */
}

/* Animação de Flutuação */
@keyframes float-up {
    0% {
        transform: translateY(0) scale(0.5);
        opacity: 0;
    }
    50% {
        opacity: 1; /* Fica visível no meio do caminho */
    }
    100% {
        transform: translateY(-100vh) scale(1.5);
        opacity: 0; /* Desaparece no topo */
    }
}
    </style>
</head>
<body>

<div class="modal fade" id="confirmarModal" tabindex="-1" aria-labelledby="confirmarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmarModalLabel">Finalizar Quiz?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Você respondeu <span id="perguntas-respondidas-modal"></span> de <span id="total-perguntas-modal"></span> perguntas. Tem certeza que deseja finalizar o quiz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-submit" id="btn-confirmar-envio">Sim, finalizar!</button>
            </div>
        </div>
    </div>
</div>

<div id="background-animation"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7">
                <div class="card quiz-card">
                    <div class="card-header quiz-header text-center rounded-top-4">
                        <h1 class="quiz-title mb-1">Quiz de Nivelamento de <?php echo htmlspecialchars($idioma_quiz); ?></h1>
                        <p class="quiz-subtitle mb-0">Responda às perguntas para descobrir seu nível.</p>
                        <div class="progresso-quiz">
                            <div class="progresso-preenchido" id="progresso-barra" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="quiz.php" method="POST" id="quiz-form">
                            <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_quiz); ?>">
                            <?php foreach ($perguntas_quiz as $key => $pergunta): ?>
                                <div class="quiz-question" data-pergunta="<?php echo $key + 1; ?>">
                                    <div class="mb-3">
                                        <h5 class="quiz-question-title"><?php echo ($key + 1) . ". " . htmlspecialchars($pergunta['pergunta']); ?></h5>
                                    </div>
                                    <div class="quiz-options d-grid gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_a" value="A" required data-pergunta="<?php echo $key + 1; ?>">
                                            <label class="form-check-label w-100" for="pergunta_<?php echo $pergunta['id']; ?>_a"><?php echo htmlspecialchars($pergunta['alternativa_a']); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_b" value="B" required data-pergunta="<?php echo $key + 1; ?>">
                                            <label class="form-check-label w-100" for="pergunta_<?php echo $pergunta['id']; ?>_b"><?php echo htmlspecialchars($pergunta['alternativa_b']); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_c" value="C" required data-pergunta="<?php echo $key + 1; ?>">
                                            <label class="form-check-label w-100" for="pergunta_<?php echo $pergunta['id']; ?>_c"><?php echo htmlspecialchars($pergunta['alternativa_c']); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_d" value="D" required data-pergunta="<?php echo $key + 1; ?>">
                                            <label class="form-check-label w-100" for="pergunta_<?php echo $pergunta['id']; ?>_d"><?php echo htmlspecialchars($pergunta['alternativa_d']); ?></label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-submit btn-lg fw-bold rounded-pill" id="btn-finalizar">Finalizar Quiz</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

    document.addEventListener('DOMContentLoaded', function() {
    const totalPerguntas = <?php echo count($perguntas_quiz); ?>;
    let perguntasRespondidas = 0;
    
    const radios = document.querySelectorAll('.resposta-radio');
    const perguntasRespondidas_set = new Set();
    
    // NOVO: Inicializa o objeto do Modal do Bootstrap
    const confirmarModal = new bootstrap.Modal(document.getElementById('confirmarModal'));
    const btnConfirmarEnvio = document.getElementById('btn-confirmar-envio');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const perguntaId = this.name;
            
            document.querySelectorAll(`input[name="${perguntaId}"]`).forEach(alt => {
                alt.closest('.form-check').classList.remove('alternativa-selecionada');
            });
            
            this.closest('.form-check').classList.add('alternativa-selecionada');
            
            perguntasRespondidas_set.add(perguntaId);
            perguntasRespondidas = perguntasRespondidas_set.size;
            
            const progresso = (perguntasRespondidas / totalPerguntas) * 100;
            document.getElementById('progresso-barra').style.width = progresso + '%';
            
            const btnFinalizar = document.getElementById('btn-finalizar');
            if (perguntasRespondidas === totalPerguntas) {
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = 'Finalizar Quiz ✓';
                btnFinalizar.classList.remove('btn-secondary');
                btnFinalizar.classList.add('btn-submit');
            } else {
                btnFinalizar.disabled = true;
                btnFinalizar.textContent = 'Responda todas as perguntas';
                btnFinalizar.classList.add('btn-secondary');
                btnFinalizar.classList.remove('btn-submit');
            }
        });
    });
    
    const btnFinalizar = document.getElementById('btn-finalizar');
    btnFinalizar.disabled = true;
    btnFinalizar.textContent = 'Responda todas as perguntas';
    btnFinalizar.classList.add('btn-secondary');
    
    // NOVO: Adiciona um listener para o envio do formulário
    document.getElementById('quiz-form').addEventListener('submit', function(e) {
        // Impede o envio padrão do formulário
        e.preventDefault();
        
        // Verifica se todas as perguntas foram respondidas
        if (perguntasRespondidas < totalPerguntas) {
            alert('Por favor, responda todas as perguntas antes de finalizar o quiz.');
            return false;
        }
        
        // NOVO: Atualiza a contagem no modal e o exibe
        document.getElementById('perguntas-respondidas-modal').textContent = perguntasRespondidas;
        document.getElementById('total-perguntas-modal').textContent = totalPerguntas;
        confirmarModal.show();
    });

    // NOVO: Adiciona um listener para o botão de confirmação dentro do modal
    btnConfirmarEnvio.addEventListener('click', function() {
        // Agora, o formulário é realmente enviado
        document.getElementById('quiz-form').submit();
    });

    // --- Animação de Fundo (Novo Código) ---
    function createBubble() {
        const animationContainer = document.getElementById('background-animation');
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        const size = Math.random() * 60 + 20;
        const left = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${left}%`;
        bubble.style.animationDuration = `${duration}s`;
        bubble.style.animationDelay = `${delay}s`;
        animationContainer.appendChild(bubble);
        setTimeout(() => {
            bubble.remove();
        }, (duration + delay) * 1000);
    }
    setInterval(createBubble, 500);
});
    </script>
</body>
</html>