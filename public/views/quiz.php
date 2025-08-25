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
    $nivel_final = 'A1';
    if ($pontuacao_total >= 25) { 
        $nivel_final = 'C2';
    } elseif ($pontuacao_total >= 20) { 
        $nivel_final = 'C1';
    } elseif ($pontuacao_total >= 15) { 
        $nivel_final = 'B2';
    } elseif ($pontuacao_total >= 10) { 
        $nivel_final = 'B1';
    } elseif ($pontuacao_total >= 5) { 
        $nivel_final = 'A2';
    }
    
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
        .contador-acertos {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 200px;
            text-align: center;
        }
        
        .acertos-numero {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .acertos-texto {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .pergunta-atual {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .progresso-quiz {
            background: #e9ecef;
            height: 10px;
            border-radius: 5px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .progresso-preenchido {
            background: linear-gradient(90deg, #007bff, #28a745);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .alternativa-selecionada {
            background-color: #e7f3ff !important;
            border-color: #007bff !important;
        }
        
        @media (max-width: 768px) {
            .contador-acertos {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 20px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="contador-acertos">
        <span class="acertos-numero" id="acertos-count">0</span>
        <span class="acertos-texto">acertos de <span id="total-count">0</span> perguntas</span>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Quiz de Nivelamento de <?php echo htmlspecialchars($idioma_quiz); ?></h2>
                        <p>Responda às perguntas para descobrir seu nível.</p>
                        <div class="progresso-quiz">
                            <div class="progresso-preenchido" id="progresso-barra" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="quiz.php" method="POST" id="quiz-form">
                            <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_quiz); ?>">
                            <?php foreach ($perguntas_quiz as $key => $pergunta): ?>
                                <div class="mb-4 pergunta-item" data-pergunta="<?php echo $key + 1; ?>">
                                    <div class="pergunta-atual">
                                        <h5><?php echo ($key + 1) . ". " . htmlspecialchars($pergunta['pergunta']); ?></h5>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_a" value="A" required data-pergunta="<?php echo $key + 1; ?>">
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_a"><?php echo htmlspecialchars($pergunta['alternativa_a']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_b" value="B" required data-pergunta="<?php echo $key + 1; ?>">
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_b"><?php echo htmlspecialchars($pergunta['alternativa_b']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_c" value="C" required data-pergunta="<?php echo $key + 1; ?>">
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_c"><?php echo htmlspecialchars($pergunta['alternativa_c']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input resposta-radio" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_d" value="D" required data-pergunta="<?php echo $key + 1; ?>">
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_d"><?php echo htmlspecialchars($pergunta['alternativa_d']); ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success" id="btn-finalizar">Finalizar Quiz</button>
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
            
            // Atualizar contador total
            document.getElementById('total-count').textContent = totalPerguntas;
            
            // Adicionar event listeners para todas as opções de resposta
            const radios = document.querySelectorAll('.resposta-radio');
            const perguntasRespondidas_set = new Set();
            
            radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Adicionar classe visual para alternativa selecionada
                    const perguntaId = this.name;
                    const todasAlternativas = document.querySelectorAll(`input[name="${perguntaId}"]`);
                    
                    todasAlternativas.forEach(alt => {
                        alt.closest('.form-check').classList.remove('alternativa-selecionada');
                    });
                    
                    this.closest('.form-check').classList.add('alternativa-selecionada');
                    
                    // Atualizar contador de perguntas respondidas
                    perguntasRespondidas_set.add(perguntaId);
                    perguntasRespondidas = perguntasRespondidas_set.size;
                    
                    // Atualizar barra de progresso
                    const progresso = (perguntasRespondidas / totalPerguntas) * 100;
                    document.getElementById('progresso-barra').style.width = progresso + '%';
                    
                    // Atualizar contador de acertos (simulado - na verdade só mostra perguntas respondidas)
                    document.getElementById('acertos-count').textContent = perguntasRespondidas;
                    
                    // Habilitar botão finalizar se todas as perguntas foram respondidas
                    const btnFinalizar = document.getElementById('btn-finalizar');
                    if (perguntasRespondidas === totalPerguntas) {
                        btnFinalizar.disabled = false;
                        btnFinalizar.textContent = 'Finalizar Quiz ✓';
                        btnFinalizar.classList.add('btn-success');
                        btnFinalizar.classList.remove('btn-secondary');
                    }
                });
            });
            
            // Inicialmente desabilitar o botão finalizar
            const btnFinalizar = document.getElementById('btn-finalizar');
            btnFinalizar.disabled = true;
            btnFinalizar.textContent = 'Responda todas as perguntas';
            btnFinalizar.classList.add('btn-secondary');
            btnFinalizar.classList.remove('btn-success');
            
            // Adicionar confirmação antes de enviar
            document.getElementById('quiz-form').addEventListener('submit', function(e) {
                if (perguntasRespondidas < totalPerguntas) {
                    e.preventDefault();
                    alert('Por favor, responda todas as perguntas antes de finalizar o quiz.');
                    return false;
                }
                
                const confirmacao = confirm(`Você respondeu ${perguntasRespondidas} de ${totalPerguntas} perguntas. Deseja finalizar o quiz?`);
                if (!confirmacao) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
