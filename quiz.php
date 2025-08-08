<?php
session_start();
// Inclua o arquivo de conexão em POO
include 'conexao.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Lógica de processamento do formulário do quiz
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Crie uma instância da classe Database para obter a conexão
    $database = new Database();
    $conn = $database->conn;

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
    
    // Calcula a pontuação por nível
    $pontuacao_a1 = 0; $pontuacao_a2 = 0; $pontuacao_b1 = 0;
    foreach ($respostas_usuario as $id_pergunta => $resposta_do_usuario) {
        if (isset($respostas_corretas_map[$id_pergunta]) && $respostas_corretas_map[$id_pergunta]['resposta'] == $resposta_do_usuario) {
            $nivel_pergunta = $respostas_corretas_map[$id_pergunta]['nivel'];
            if ($nivel_pergunta == 'A1') { $pontuacao_a1++; } elseif ($nivel_pergunta == 'A2') { $pontuacao_a2++; } elseif ($nivel_pergunta == 'B1') { $pontuacao_b1++; }
        }
    }
    
    // Determina o nível final
    $nivel_final = 'A1';
    if ($pontuacao_b1 >= 4) { $nivel_final = 'B1'; } elseif ($pontuacao_a2 >= 4) { $nivel_final = 'A2'; }
    
    // Atualiza o nível do usuário no banco de dados
    $sql_update = "UPDATE progresso_usuario SET nivel = ? WHERE id_usuario = ? AND idioma = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sis", $nivel_final, $id_usuario, $idioma_quiz);
    $stmt_update->execute();
    $stmt_update->close();
    
    // Fecha a conexão usando o método da classe
    $database->closeConnection();
    
    // Redireciona para a página de resultados
    header("Location: resultado_quiz.php?idioma=$idioma_quiz&nivel=$nivel_final");
    exit();
}

// Lógica para exibir o formulário do quiz
$idioma_quiz = isset($_GET['idioma']) ? $_GET['idioma'] : null;
if (!$idioma_quiz) {
    header("Location: painel.php");
    exit();
}

// Crie uma instância da classe Database para obter a conexão para exibir o quiz
$database = new Database();
$conn = $database->conn;

// Busca as perguntas para o quiz
$perguntas_quiz = [];
$sql_a1 = "SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'A1' ORDER BY RAND() LIMIT 8";
$stmt_a1 = $conn->prepare($sql_a1);
$stmt_a1->bind_param("s", $idioma_quiz);
$stmt_a1->execute();
$perguntas_quiz = array_merge($perguntas_quiz, $stmt_a1->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt_a1->close();

$sql_a2 = "SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'A2' ORDER BY RAND() LIMIT 6";
$stmt_a2 = $conn->prepare($sql_a2);
$stmt_a2->bind_param("s", $idioma_quiz);
$stmt_a2->execute();
$perguntas_quiz = array_merge($perguntas_quiz, $stmt_a2->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt_a2->close();

$sql_b1 = "SELECT * FROM quiz_nivelamento WHERE idioma = ? AND nivel = 'B1' ORDER BY RAND() LIMIT 6";
$stmt_b1 = $conn->prepare($sql_b1);
$stmt_b1->bind_param("s", $idioma_quiz);
$stmt_b1->execute();
$perguntas_quiz = array_merge($perguntas_quiz, $stmt_b1->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt_b1->close();

// Embaralha todas as perguntas para não seguir uma ordem de nível
shuffle($perguntas_quiz);

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
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Quiz de Nivelamento de <?php echo htmlspecialchars($idioma_quiz); ?></h2>
                        <p>Responda às perguntas para descobrir seu nível.</p>
                    </div>
                    <div class="card-body">
                        <form action="quiz.php" method="POST">
                            <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_quiz); ?>">
                            <?php foreach ($perguntas_quiz as $key => $pergunta): ?>
                                <div class="mb-4">
                                    <h5><?php echo ($key + 1) . ". " . htmlspecialchars($pergunta['pergunta']); ?></h5>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_a" value="A" required>
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_a"><?php echo htmlspecialchars($pergunta['alternativa_a']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_b" value="B" required>
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_b"><?php echo htmlspecialchars($pergunta['alternativa_b']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_c" value="C" required>
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_c"><?php echo htmlspecialchars($pergunta['alternativa_c']); ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resposta[<?php echo $pergunta['id']; ?>]" id="pergunta_<?php echo $pergunta['id']; ?>_d" value="D" required>
                                        <label class="form-check-label" for="pergunta_<?php echo $pergunta['id']; ?>_d"><?php echo htmlspecialchars($pergunta['alternativa_d']); ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success">Finalizar Quiz</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>