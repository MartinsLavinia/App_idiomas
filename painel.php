<?php
session_start();
// Inclua o arquivo de conexão em POO
include 'conexao.php';

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    // Feche a conexão antes de redirecionar
    $database->closeConnection();
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$idioma_escolhido = null;
$nivel_usuario = null;
$nome_usuario = $_SESSION['nome_usuario'] ?? 'usuário';
$mostrar_selecao_idioma = false;

// Processa seleção de idioma para usuários sem progresso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['idioma_inicial'])) {
    $idioma_inicial = $_POST['idioma_inicial'];
    $nivel_inicial = "A1";
    
    // Insere progresso inicial para o usuário
    $sql_insert_progresso = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert_progresso);
    $stmt_insert->bind_param("iss", $id_usuario, $idioma_inicial, $nivel_inicial);
    
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        // Redireciona para o quiz de nivelamento
        $database->closeConnection();
        header("Location: quiz.php?idioma=$idioma_inicial");
        exit();
    } else {
        $erro_selecao = "Erro ao registrar idioma. Tente novamente.";
    }
    $stmt_insert->close();
}

// Tenta obter o idioma e o nível da URL (se veio do pop-up de resultados)
if (isset($_GET['idioma']) && isset($_GET['nivel_escolhido'])) {
    $idioma_escolhido = $_GET['idioma'];
    $nivel_usuario = $_GET['nivel_escolhido'];
    
    // Atualiza o nível do usuário no banco de dados com a escolha final
    $sql_update_nivel = "UPDATE progresso_usuario SET nivel = ? WHERE id_usuario = ? AND idioma = ?";
    $stmt_update_nivel = $conn->prepare($sql_update_nivel);
    $stmt_update_nivel->bind_param("sis", $nivel_usuario, $id_usuario, $idioma_escolhido);
    $stmt_update_nivel->execute();
    $stmt_update_nivel->close();

} else {
    // Se não veio da URL, busca o último idioma e nível do banco de dados
    $sql_progresso = "SELECT idioma, nivel FROM progresso_usuario WHERE id_usuario = ? ORDER BY id DESC LIMIT 1";
    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param("i", $id_usuario);
    $stmt_progresso->execute();
    $resultado = $stmt_progresso->get_result()->fetch_assoc();
    $stmt_progresso->close();

    if ($resultado) {
        $idioma_escolhido = $resultado['idioma'];
        $nivel_usuario = $resultado['nivel'];
    } else {
        // Se o usuário não tem progresso, mostra seleção de idioma
        $mostrar_selecao_idioma = true;
    }
}

// Busca unidades apenas se o usuário tem progresso
if (!$mostrar_selecao_idioma) {
    $sql_unidades = "SELECT * FROM unidades WHERE idioma = ? AND nivel = ? ORDER BY numero_unidade ASC";
    $stmt_unidades = $conn->prepare($sql_unidades);
    $stmt_unidades->bind_param("ss", $idioma_escolhido, $nivel_usuario);
    $stmt_unidades->execute();
    $unidades = $stmt_unidades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_unidades->close();
}

// Feche a conexão usando o método da classe
$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - <?php echo htmlspecialchars($idioma_escolhido); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Site de Idiomas</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="logout.php" class="btn btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($mostrar_selecao_idioma): ?>
                    <!-- Seleção de idioma para usuários sem progresso -->
                    <div class="card">
                        <div class="card-header text-center">
                            <h2>Bem-vindo! Escolha seu primeiro idioma</h2>
                        </div>
                        <div class="card-body">
                            <?php if (isset($erro_selecao)): ?>
                                <div class="alert alert-danger"><?php echo $erro_selecao; ?></div>
                            <?php endif; ?>
                            <p class="text-center mb-4">Para começar sua jornada de aprendizado, selecione o idioma que deseja estudar:</p>
                            <form method="POST" action="painel.php">
                                <div class="mb-3">
                                    <label for="idioma_inicial" class="form-label">Escolha seu idioma</label>
                                    <select class="form-select" id="idioma_inicial" name="idioma_inicial" required>
                                        <option value="" disabled selected>Selecione um idioma</option>
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Começar Quiz de Nivelamento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Painel normal para usuários com progresso -->
                    <div class="card mb-4">
                        <div class="card-header text-center">
                            <h2>Seu Caminho de Aprendizado em <?php echo htmlspecialchars($idioma_escolhido); ?></h2>
                        </div>
                        <div class="card-body text-center">
                            <p class="fs-4">Seu nível atual é: <span class="badge bg-success"><?php echo htmlspecialchars($nivel_usuario); ?></span></p>
                        </div>
                    </div>

                    <h4>Unidades do Nível <?php echo htmlspecialchars($nivel_usuario); ?></h4>
                    <div class="list-group">
                        <?php if (count($unidades) > 0): ?>
                            <?php foreach ($unidades as $unidade): ?>
                                <a href="#" class="list-group-item list-group-item-action mb-2">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Unidade <?php echo htmlspecialchars($unidade['numero_unidade']); ?>: <?php echo htmlspecialchars($unidade['titulo']); ?></h5>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($unidade['descricao']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                Nenhuma unidade encontrada para este nível e idioma.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>