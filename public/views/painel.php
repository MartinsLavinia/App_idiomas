<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    // Feche a conexão antes de redirecionar
    $database->closeConnection();
    header("Location: /../../index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$idioma_escolhido = null;
$nivel_usuario = null;
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$mostrar_selecao_idioma = false;

// Processa seleção de idioma para usuários sem progresso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["idioma_inicial"])) {
    $idioma_inicial = $_POST["idioma_inicial"];
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
if (isset($_GET["idioma"]) && isset($_GET["nivel_escolhido"])) {
    $idioma_escolhido = $_GET["idioma"];
    $nivel_usuario = $_GET["nivel_escolhido"];
   
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
        $idioma_escolhido = $resultado["idioma"];
        $nivel_usuario = $resultado["nivel"];
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
    <link href="painel.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Paleta de Cores - MESMAS DO ADMIN */
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
        }

        /* Estilos Gerais do Corpo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
        }

        /* SIDEBAR FIXO */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .sidebar .profile i {
            font-size: 4rem;
            color: var(--amarelo-detalhe);
            margin-bottom: 10px;
        }

        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 0;
            color: var(--branco);
        }

        .sidebar .profile small {
            color: var(--cinza-claro);
        }

        .sidebar .list-group {
            width: 100%;
        }

        .sidebar .list-group-item {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .list-group-item:hover {
            background-color: var(--roxo-escuro);
            cursor: pointer;
        }

        .sidebar .list-group-item.active {
            background-color: var(--roxo-escuro) !important;
            color: var(--branco) !important;
            font-weight: 600;
            border-left: 4px solid var(--amarelo-detalhe);
        }

        .sidebar .list-group-item i {
            color: var(--amarelo-detalhe);
            width: 20px; /* Alinhamento dos ícones */
            text-align: center;
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        /* Estilos para exercícios de fala */
        .microphone-section {
            text-align: center;
            margin: 30px 0;
        }

        .microphone-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
            margin: 20px auto;
            display: block;
        }

        .microphone-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.5);
        }

        .microphone-btn.listening {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { 
                transform: scale(1);
                box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            }
            50% { 
                transform: scale(1.1);
                box-shadow: 0 12px 35px rgba(220, 53, 69, 0.5);
            }
            100% { 
                transform: scale(1);
                box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            }
        }

        .speech-status {
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
        }

        .speech-listening {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffd700;
            color: #856404;
        }

        .speech-success {
            background: linear-gradient(135deg, #d1edff, #b3d9ff);
            border: 2px solid #28a745;
            color: #004085;
        }

        .speech-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
            color: #721c24;
        }

        /* Estilos para exercícios de listening */
        .audio-player-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
        }

        .audio-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
        }

        .listening-options {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-option {
            text-align: left;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .btn-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .btn-option.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }

        /* Cards de unidade */
        .unidade-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .unidade-card:hover {
            transform: translateY(-5px);
            border-color: var(--roxo-principal);
            box-shadow: 0 5px 15px rgba(106, 13, 173, 0.3);
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }

        /* Estilos específicos para exercícios de fala */
        .fala-container {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 2px solid var(--roxo-principal);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }

        .frase-pronunciar {
            background: white;
            border: 2px dashed var(--roxo-principal);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--roxo-escuro);
        }

        /* Estilos para preview de flashcards */
        .flashcard-preview {
            width: 100%;
            height: 200px;
            perspective: 1000px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flashcard-preview.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .flashcard-front {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .flashcard-back {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            transform: rotateY(180deg);
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5><?php echo htmlspecialchars($nome_usuario); ?></h5>
            <small>Usuário</small>
        </div>

        <div class="list-group">
            <a href="painel.php" class="list-group-item active">
                <i class="fas fa-home"></i> Início
            </a>
            <a href="flashcards.php" class="list-group-item">
                <i class="fas fa-layer-group"></i> Flash Cards
            </a>
            <a href="perfil.php" class="list-group-item">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <a href="../../logout.php" class="list-group-item mt-auto">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11">
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
                                        <option value="Coreano">Coreano</option>
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

                    <!-- Seção Flash Cards -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Coluna de texto -->
                                <div class="col-md-8">
                                    <h5 class="card-title mb-2">
                                        <i class="fas fa-layer-group me-2 text-warning"></i>
                                        Flash Cards
                                    </h5>
                                    <p class="card-text text-muted mb-0">
                                        Estude com flashcards personalizados e melhore sua memorização
                                    </p>
                                </div>

                                <!-- Coluna dos botões (um abaixo do outro) -->
                                <div class="col-md-4 text-end">
                                    <div class="d-flex gap-2">
                                        <a href="flashcards.php" class="btn btn-warning">
                                            <i class="fas fa-layer-group me-2"></i>Meus Decks
                                        </a>
                                        <a href="flashcard_estudo.php" class="btn btn-outline-warning">
                                            <i class="fas fa-play me-2"></i>Estudar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Gerenciamento de Palavras -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h5 class="mb-0"> <i class="fas fa-book me-2"></i> Minhas Palavras </h5>
                                </div>
                                <div class="col-md-8 text-end">
                                    <div class="row g-2 justify-content-end align-items-center">
                                        <div class="col-md-4">
                                            <select class="form-select form-select-sm form-select-dark" id="filtroPalavrasStatus" onchange="carregarPalavras()">
                                                <option value="">Todas as palavras</option>
                                                <option value="0">Não aprendidas</option>
                                                <option value="1">Aprendidas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm form-control-dark" id="filtroPalavrasBusca" placeholder="Buscar palavra..." onkeyup="filtrarPalavrasLocal()">
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-sm btn-light" type="button" onclick="carregarPalavras()"><i class="fas fa-search"></i></button>
                                        </div>
                                        <div class="col-md-auto">
                                            <button class="btn btn-light btn-sm w-auto" onclick="abrirModalAdicionarPalavra()">
                                        <i class="fas fa-plus me-2"></i>Adicionar Palavra
                                    </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                           
                            <!-- Lista de Palavras -->
                            <div id="listaPalavras" class="row">
                                <div class="col-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando suas palavras...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4>Unidades do Nível <?php echo htmlspecialchars($nivel_usuario); ?></h4>
                    <div class="row">
                        <?php if (count($unidades) > 0): ?>
                            <?php foreach ($unidades as $unidade): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card unidade-card h-100" 
                                         data-unidade-id="<?php echo $unidade['id']; ?>"
                                         data-unidade-titulo="<?php echo htmlspecialchars($unidade['nome_unidade']); ?>"
                                         data-unidade-numero="<?php echo $unidade['numero_unidade']; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-book-open me-2"></i>
                                                Unidade <?php echo htmlspecialchars($unidade["numero_unidade"]); ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($unidade["nome_unidade"]); ?></h6>
                                            <p class="card-text"><?php echo htmlspecialchars($unidade["descricao"]); ?></p>
                                            <div class="progress progress-bar-custom">
                                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">0% concluído</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    Nenhuma unidade encontrada para este nível e idioma.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

   <!-- Modal Adicionar Palavra ATUALIZADO -->
<div class="modal fade" id="modalAdicionarPalavra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Adicionar Nova Palavra/Flashcard
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <form id="formAdicionarPalavra">
                            <input type="hidden" id="palavraId" name="id_flashcard">
                            
                            <div class="mb-3">
                                <label for="palavraFrente" class="form-label">Frente do Card *</label>
                                <textarea class="form-control" id="palavraFrente" name="palavra_frente" rows="3" required placeholder="Digite a palavra/frase na língua estrangeira"><?php echo htmlspecialchars($palavra_frente ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="palavraVerso" class="form-label">Verso do Card *</label>
                                <textarea class="form-control" id="palavraVerso" name="palavra_verso" rows="3" required placeholder="Digite a tradução ou significado"><?php echo htmlspecialchars($palavra_verso ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="palavraDica" class="form-label">Dica (opcional)</label>
                                <textarea class="form-control" id="palavraDica" name="dica" rows="2" placeholder="Digite uma dica para ajudar na memorização"><?php echo htmlspecialchars($dica ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="palavraDificuldade" class="form-label">Dificuldade</label>
                                        <select class="form-select" id="palavraDificuldade" name="dificuldade">
                                            <option value="facil">Fácil</option>
                                            <option value="medio" selected>Médio</option>
                                            <option value="dificil">Difícil</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="palavraOrdem" class="form-label">Ordem</label>
                                        <input type="number" class="form-control" id="palavraOrdem" name="ordem_no_deck" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="palavraIdioma" class="form-label">Idioma</label>
                                        <select class="form-select" id="palavraIdioma" name="idioma">
                                            <option value="Ingles">Inglês</option>
                                            <option value="Japones">Japonês</option>
                                            <option value="Coreano">Coreano</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="palavraNivel" class="form-label">Nível</label>
                                        <select class="form-select" id="palavraNivel" name="nivel">
                                            <option value="A1">A1</option>
                                            <option value="A2">A2</option>
                                            <option value="B1">B1</option>
                                            <option value="B2">B2</option>
                                            <option value="C1">C1</option>
                                            <option value="C2">C2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="palavraCategoria" class="form-label">Categoria (opcional)</label>
                                <input type="text" class="form-control" id="palavraCategoria" name="categoria" placeholder="Ex: Verbos, Substantivos, Cumprimentos">
                            </div>
                            
                            <!-- Campos para imagens e áudios (futuro) -->
                            <div class="mb-3">
                                <label class="form-label">Mídia (em desenvolvimento)</label>
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Suporte para imagens e áudios será adicionado em breve.
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preview do Flashcard</label>
                        <div class="flashcard-preview" id="palavraPreview" onclick="virarPreviewPalavra()">
                            <div class="flashcard-inner">
                                <div class="flashcard-front">
                                    <div id="previewPalavraFrente">Digite o conteúdo da frente</div>
                                </div>
                                <div class="flashcard-back">
                                    <div id="previewPalavraVerso">Digite o conteúdo do verso</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">Clique no card para virar</small>
                        </div>
                        
                        <!-- Dica preview -->
                        <div class="mt-3" id="previewDicaContainer" style="display: none;">
                            <div class="alert alert-info py-2">
                                <small>
                                    <i class="fas fa-lightbulb me-1"></i>
                                    <span id="previewPalavraDica">Dica aparecerá aqui</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarPalavra()">
                    <i class="fas fa-save me-2"></i>Salvar Palavra
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Blocos da Unidade -->
    <div class="modal fade" id="modalBlocos" tabindex="-1" aria-labelledby="modalBlocosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBlocosLabel">
                        <i class="fas fa-cubes me-2"></i>
                        <span id="tituloBlocos">Blocos da Unidade</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaBlocos" class="row">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando blocos...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exercícios -->
    <div class="modal fade" id="modalExercicios" tabindex="-1" aria-labelledby="modalExerciciosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExerciciosLabel">
                        <i class="fas fa-pencil-alt me-2"></i>
                        <span id="tituloExercicios">Exercícios</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Barra de progresso dos exercícios -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Progresso:</span>
                            <span id="contadorExercicios" class="badge bg-primary">1/5</span>
                        </div>
                        <div class="progress">
                            <div id="progressoExercicios" class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <!-- Conteúdo do exercício -->
                    <div id="conteudoExercicio">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando exercício...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="voltarParaBlocos()">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEnviarResposta" onclick="enviarResposta()">
                        <i class="fas fa-check me-2"></i>Enviar Resposta
                    </button>
                    <button type="button" class="btn btn-success" id="btnProximoExercicio" onclick="proximoExercicio()" style="display: none;">
                        <i class="fas fa-arrow-right me-2"></i>Próximo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalExclusao"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="mensagemModalExclusao">Tem certeza que deseja excluir esta palavra? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarExclusao"><i class="fas fa-trash me-2"></i>Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ==================== VARIÁVEIS GLOBAIS ====================
    let modalBlocos = null;
    let modalExercicios = null;
    let modalAdicionarPalavra = null;
    let modalConfirmarExclusao = null;
    let unidadeAtual = null;
    let blocoAtual = null;
    let exercicioAtual = null;
    let exerciciosLista = [];
    let exercicioIndex = 0;
    let respostaSelecionada = null;
    let palavrasCarregadas = [];
    let recognition = null;

    // ==================== INICIALIZAÇÃO ====================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== INICIALIZANDO PAINEL ===');
        
        // Inicialização dos modais
        modalBlocos = new bootstrap.Modal(document.getElementById('modalBlocos'));
        modalExercicios = new bootstrap.Modal(document.getElementById('modalExercicios'));
        modalAdicionarPalavra = new bootstrap.Modal(document.getElementById('modalAdicionarPalavra'));
        modalConfirmarExclusao = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));

        // Configurar event listeners para cards de unidades
        configurarEventListenersUnidades();

        // Configurar event listeners para preview de flashcards
        configurarPreviewFlashcards();

        // Carrega palavras do usuário ao inicializar
        if (typeof carregarPalavras === 'function') {
            carregarPalavras();
        }

        console.log('Painel inicializado com sucesso');
    });

    // ==================== CONFIGURAÇÃO DOS EVENT LISTENERS ====================
    function configurarEventListenersUnidades() {
        const unidadeCards = document.querySelectorAll('.unidade-card');
        console.log(`Encontrados ${unidadeCards.length} cards de unidade`);
        
        unidadeCards.forEach((card, index) => {
            card.addEventListener('click', function() {
                const unidadeId = this.getAttribute('data-unidade-id');
                const titulo = this.getAttribute('data-unidade-titulo');
                const numero = this.getAttribute('data-unidade-numero');
                
                console.log(`Clicado na unidade:`, {unidadeId, titulo, numero});
                
                if (unidadeId && titulo && numero) {
                    abrirUnidade(parseInt(unidadeId), titulo, parseInt(numero));
                } else {
                    console.error('Dados da unidade não encontrados:', {unidadeId, titulo, numero});
                    alert('Erro: Dados da unidade não encontrados.');
                }
            });
        });
    }

    function configurarPreviewFlashcards() {
        // Atualizar preview quando os campos mudarem
        const frenteInput = document.getElementById('palavraFrente');
        const versoInput = document.getElementById('palavraVerso');
        const dicaInput = document.getElementById('palavraDica');

        if (frenteInput) frenteInput.addEventListener('input', atualizarPreviewFlashcard);
        if (versoInput) versoInput.addEventListener('input', atualizarPreviewFlashcard);
        if (dicaInput) dicaInput.addEventListener('input', atualizarPreviewFlashcard);
    }

    // ==================== FUNÇÕES PRINCIPAIS DE NAVEGAÇÃO ====================

    // Função para abrir modal de blocos da unidade
    window.abrirUnidade = function(unidadeId, tituloUnidade, numeroUnidade) {
        console.log('Abrindo unidade:', unidadeId, tituloUnidade, numeroUnidade);
        
        unidadeAtual = unidadeId;
        document.getElementById("tituloBlocos").textContent = `Blocos da Unidade ${numeroUnidade}: ${tituloUnidade}`;
       
        // Carregar blocos via AJAX
        fetch(`../../admin/controller/get_blocos.php?unidade_id=${unidadeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                if (data.success) {
                    exibirBlocos(data.blocos);
                    modalBlocos.show();
                } else {
                    alert("Erro ao carregar blocos: " + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro ao carregar blocos:", error);
                alert("Erro de rede ao carregar blocos: " + error.message);
            });
    };

    // Função para exibir blocos no modal
    function exibirBlocos(blocos) {
        const container = document.getElementById("listaBlocos");
        container.innerHTML = "";

        if (!blocos || blocos.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum bloco encontrado para esta unidade.
                    </div>
                </div>
            `;
            return;
        }

        blocos.forEach(bloco => {
            const progresso = bloco.progresso?.progresso_percentual || 0;
            const concluido = bloco.progresso?.concluido || false;
            const atividadesConcluidas = bloco.progresso?.atividades_concluidas || 0;
            const totalAtividades = bloco.progresso?.total_atividades || bloco.total_atividades || 0;
           
            const col = document.createElement("div");
            col.className = "col-md-6 mb-3";
            col.innerHTML = `
                <div class="card bloco-card h-100" onclick="abrirExercicios(${bloco.id}, '${bloco.nome_bloco.replace(/'/g, "\\'")}')" style="cursor: pointer;">
                    <div class="card-body text-center">
                        <i class="fas fa-cube bloco-icon mb-3" style="font-size: 2rem; color: #007bff;"></i>
                        <h5 class="card-title">${bloco.nome_bloco}</h5>
                        <p class="card-text text-muted">${bloco.descricao || 'Descrição não disponível'}</p>
                        <div class="progress progress-bar-custom mb-2">
                            <div class="progress-bar ${concluido ? 'bg-success' : ''}" role="progressbar" 
                                 style="width: ${progresso}%" aria-valuenow="${progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">${atividadesConcluidas}/${totalAtividades} atividades (${progresso}%)</small>
                        ${concluido ? '<div class="mt-2"><span class="badge bg-success"><i class="fas fa-check me-1"></i>Concluído</span></div>' : ''}
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    }

    // Função para abrir modal de exercícios
    window.abrirExercicios = function(blocoId, tituloBloco) {
        console.log('Abrindo exercícios para bloco:', blocoId, tituloBloco);
        
        blocoAtual = blocoId;
        document.getElementById("tituloExercicios").textContent = `Exercícios: ${tituloBloco}`;
       
        // Carregar exercícios via AJAX - usando get_exercicio.php com bloco_id
        fetch(`../../admin/controller/get_exercicio.php?bloco_id=${blocoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Exercícios recebidos:', data);
                if (data.success) {
                    if (data.exercicios && data.exercicios.length > 0) {
                        exerciciosLista = data.exercicios;
                        exercicioIndex = 0;
                        carregarExercicio(exercicioIndex);
                        modalBlocos.hide();
                        modalExercicios.show();
                    } else {
                        alert("Nenhum exercício encontrado para este bloco.");
                    }
                } else {
                    alert("Erro ao carregar exercícios: " + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro ao carregar exercícios:", error);
                alert("Erro de rede ao carregar exercícios: " + error.message);
            });
    };

    // ==================== FUNÇÃO PRINCIPAL PARA CARREGAR EXERCÍCIOS ====================
    function carregarExercicio(index) {
        if (!exerciciosLista || exerciciosLista.length === 0) return;

        exercicioAtual = exerciciosLista[index];
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        conteudoExercicioDiv.innerHTML = "";
        respostaSelecionada = null;

        // Atualiza contador de progresso do exercício
        document.getElementById("contadorExercicios").textContent = `${index + 1}/${exerciciosLista.length}`;
        const progresso = ((index + 1) / exerciciosLista.length) * 100;
        const progressoBar = document.getElementById("progressoExercicios");
        if (progressoBar) {
            progressoBar.style.width = `${progresso}%`;
            progressoBar.setAttribute("aria-valuenow", progresso);
        }

        let htmlConteudo = `
            <div class="mb-4">
                <h6 class="text-muted">Pergunta ${index + 1}:</h6>
                <p class="fs-5 mb-4">${exercicioAtual.pergunta}</p>
            </div>
        `;

        // CORREÇÃO: PROCESSAR CONTEÚDO DO EXERCÍCIO DE FORMA CORRETA
        let conteudo = exercicioAtual.conteudo || {};
        
        // Se o conteúdo ainda é string, tentar parsear JSON
        if (typeof conteudo === 'string' && conteudo.trim().startsWith('{')) {
            try {
                conteudo = JSON.parse(conteudo);
                exercicioAtual.conteudo = conteudo; // Atualizar o objeto
            } catch (e) {
                console.error("Erro ao fazer parse do conteúdo:", e);
                conteudo = {};
            }
        }

        // CORREÇÃO: DETERMINAR TIPO DE EXERCÍCIO USANDO O CAMPO CORRETO
        const tipoExercicio = determinarTipoExercicioCorrigido(exercicioAtual, conteudo);
        exercicioAtual.tipoExercicioDeterminado = tipoExercicio;

        console.log('Exercício carregado:', {
            id: exercicioAtual.id,
            tipoDeterminado: tipoExercicio,
            tipoOriginal: exercicioAtual.tipo,
            tipo_exercicio: exercicioAtual.tipo_exercicio,
            conteudo: conteudo
        });

        // RENDERIZAR CONTEÚDO BASEADO NO TIPO CORRETO
        if (tipoExercicio === "multipla_escolha") {
            htmlConteudo += renderizarMultiplaEscolha(conteudo);
        } else if (tipoExercicio === "texto_livre") {
            htmlConteudo += renderizarTextoLivre(conteudo);
        } else if (tipoExercicio === "completar") {
            htmlConteudo += renderizarCompletar(conteudo);
        } else if (tipoExercicio === "fala") {
            htmlConteudo += renderizarExercicioFala(conteudo);
        } else if (tipoExercicio === "listening") {
            htmlConteudo += renderizarListening(conteudo);
        } else if (tipoExercicio === "audicao") {
            htmlConteudo += renderizarAudicao(conteudo);
        } else {
            // Fallback para tipo desconhecido
            htmlConteudo += `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Tipo de exercício não suportado: ${tipoExercicio}
                </div>
            `;
        }

        conteudoExercicioDiv.innerHTML = htmlConteudo;
        document.getElementById("btnEnviarResposta").style.display = "block";
        document.getElementById("btnProximoExercicio").style.display = "none";
       
        const feedbackDiv = document.getElementById("feedbackExercicio");
        if (feedbackDiv) feedbackDiv.remove();
    }

    // CORREÇÃO: FUNÇÃO PARA DETERMINAR TIPO DE EXERCÍCIO CORRETAMENTE
    function determinarTipoExercicioCorrigido(exercicio, conteudo) {
        // Priorizar o campo 'tipo' que vem do banco de dados
        if (exercicio.tipo && exercicio.tipo !== 'normal') {
            return exercicio.tipo;
        }
        
        // Se 'tipo' for 'normal', verificar o campo 'tipo_exercicio'
        if (exercicio.tipo_exercicio) {
            return exercicio.tipo_exercicio;
        }
        
        // Fallback: analisar conteúdo para determinar tipo
        if (conteudo.alternativas && Array.isArray(conteudo.alternativas)) {
            return 'multipla_escolha';
        } else if (conteudo.frase_completar) {
            return 'completar';
        } else if (conteudo.frase_esperada || conteudo.texto_para_falar) {
            return 'fala';
        } else if (conteudo.audio_url || conteudo.arquivo_audio) {
            return 'listening';
        } else if (conteudo.resposta_correta && conteudo.alternativas_aceitas) {
            return 'texto_livre';
        }
        
        // Default seguro
        return 'texto_livre';
    }

    // ==================== FUNÇÕES DE RENDERIZAÇÃO DE EXERCÍCIOS ====================

    // FUNÇÃO PARA RENDERIZAR EXERCÍCIO DE FALA - CORRIGIDA
    function renderizarExercicioFala(conteudo) {
        const textoParaFalar = conteudo.texto_para_falar || conteudo.frase_esperada || 'Nenhuma frase definida';
        const idioma = conteudo.idioma || mapIdiomaParaReconhecimento('<?php echo $idioma_escolhido; ?>');
        
        return `
            <div class="fala-container">
                <div class="text-center">
                    <h6 class="mb-3"><i class="fas fa-microphone me-2"></i>Exercício de Pronúncia</h6>
                    <div class="frase-pronunciar">
                        <p class="mb-1 text-muted">Repita a frase abaixo:</p>
                        <h4 id="fraseParaFalar" class="fw-bold text-dark">"${textoParaFalar}"</h4>
                    </div>
                    <div class="microphone-section">
                        <button id="btnMicrofone" class="microphone-btn" onclick="iniciarGravacaoFala('${idioma}')">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <p id="statusFala" class="text-muted mt-2">Clique no microfone para começar a falar</p>
                    </div>
                    <div id="resultadoFala" class="mt-3"></div>
                </div>
            </div>
        `;
    }

    function renderizarMultiplaEscolha(conteudo) {
        let html = '<div class="d-grid gap-2">';
        if (conteudo.alternativas && Array.isArray(conteudo.alternativas)) {
            conteudo.alternativas.forEach(alt => {
                html += `
                    <button type="button" class="btn btn-outline-primary btn-resposta text-start" 
                            data-id="${alt.id || alt.texto}" onclick="selecionarResposta(this)">
                        ${alt.texto}
                    </button>
                `;
            });
        }
        html += '</div>';
        return html;
    }

    function renderizarTextoLivre(conteudo) {
        const dica = conteudo.dica || '';
        
        return `
            <div class="mb-3">
                <label for="respostaTextoLivre" class="form-label">Sua resposta:</label>
                <textarea id="respostaTextoLivre" class="form-control" rows="4" placeholder="Digite sua resposta aqui..."></textarea>
                ${dica ? `<div class="form-text text-muted"><i class="fas fa-lightbulb me-1"></i>${dica}</div>` : ''}
            </div>
        `;
    }

    function renderizarCompletar(conteudo) {
        const fraseCompletar = conteudo.frase_completar || '';
        const placeholderCompletar = conteudo.placeholder || 'Digite sua resposta...';
        const dica = conteudo.dica || '';
        
        const fraseRenderizada = fraseCompletar.replace(/_____+/g, 
            `<input type="text" class="form-control d-inline-block w-auto mx-1" id="respostaCompletar" placeholder="${placeholderCompletar}" value="">`);

        return `
            <div class="mb-3">
                <label for="respostaCompletar" class="form-label">Complete a frase:</label>
                <p class="fs-5">${fraseRenderizada}</p>
                ${dica ? `<div class="form-text text-muted"><i class="fas fa-lightbulb me-1"></i>${dica}</div>` : ''}
            </div>
        `;
    }

    function renderizarListening(conteudo) {
        const audioUrl = conteudo.audio_url || conteudo.arquivo_audio || '';
        
        let html = `
            <div class="audio-player-container">
                <h6 class="text-center mb-3">🎧 Exercício de Listening</h6>
                <div class="text-center mb-4">
                    <h6>Ouça o áudio e selecione a opção correta:</h6>
                    <audio controls class="w-100 mb-3" id="audioPlayerListening">
                        <source src="${audioUrl}" type="audio/mpeg">
                        Seu navegador não suporta o elemento de áudio.
                    </audio>
                    <div class="audio-controls">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('audioPlayerListening').play()">
                            <i class="fas fa-play me-1"></i>Reproduzir
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('audioPlayerListening').pause()">
                            <i class="fas fa-pause me-1"></i>Pausar
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="document.getElementById('audioPlayerListening').currentTime = 0">
                            <i class="fas fa-redo me-1"></i>Reiniciar
                        </button>
                    </div>
                </div>
                <div class="listening-options">
        `;
        
        // Mostrar opções de resposta
        if (conteudo.opcoes && Array.isArray(conteudo.opcoes)) {
            conteudo.opcoes.forEach((opcao, index) => {
                if (opcao && opcao.trim() !== '') {
                    html += `
                        <button type="button" class="btn btn-option btn-resposta" 
                                data-id="${index}" onclick="selecionarResposta(this)">
                            ${opcao}
                        </button>
                    `;
                }
            });
        }
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    }

    function renderizarAudicao(conteudo) {
        const audioUrl = conteudo.audio_url || '';
        const transcricao = conteudo.transcricao || '';
        
        return `
            <div class="audio-player-container">
                <h6 class="text-center mb-3">🎧 Exercício de Audição</h6>
                <div class="text-center mb-4">
                    <audio controls class="w-100 mb-3" id="audioPlayerAudicao">
                        <source src="${audioUrl}" type="audio/mpeg">
                        Seu navegador não suporta o elemento de áudio.
                    </audio>
                    <div class="audio-controls">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('audioPlayerAudicao').play()">
                            <i class="fas fa-play me-1"></i>Reproduzir
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('audioPlayerAudicao').pause()">
                            <i class="fas fa-pause me-1"></i>Pausar
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="respostaAudicao" class="form-label">O que você entendeu?</label>
                    <textarea id="respostaAudicao" class="form-control" rows="4" placeholder="Descreva o que você ouviu..."></textarea>
                    ${transcricao ? `<div class="form-text text-muted"><i class="fas fa-eye me-1"></i>Transcrição disponível após resposta</div>` : ''}
                </div>
            </div>
        `;
    }

    // ==================== FUNÇÕES DE FALA ====================

    // MAPEAR IDIOMA PARA RECONHECIMENTO
    function mapIdiomaParaReconhecimento(idioma) {
        const mapa = {
            'Ingles': 'en-US',
            'Inglês': 'en-US',
            'English': 'en-US',
            'Japones': 'ja-JP',
            'Japonês': 'ja-JP',
            'Japanese': 'ja-JP',
            'Coreano': 'ko-KR',
            'Korean': 'ko-KR',
            'Portugues': 'pt-BR',
            'Português': 'pt-BR',
            'Portuguese': 'pt-BR',
            'Espanhol': 'es-ES',
            'Spanish': 'es-ES'
        };
        
        return mapa[idioma] || 'en-US';
    }

    // FUNÇÃO PARA INICIAR GRAVAÇÃO DE FALA - CORRIGIDA
    window.iniciarGravacaoFala = function(idioma = 'en-US') {
        if (!exercicioAtual) {
            alert('Erro: Nenhum exercício carregado.');
            return;
        }

        const btnMicrofone = document.getElementById('btnMicrofone');
        const statusFala = document.getElementById('statusFala');
        const resultadoFala = document.getElementById('resultadoFala');
        
        // Verificar suporte do navegador
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            resultadoFala.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Seu navegador não suporta reconhecimento de voz. Use Chrome, Edge ou Safari.
                </div>
            `;
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        try {
            recognition = new SpeechRecognition();
            recognition.lang = idioma;
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            // Atualizar UI para estado de gravação
            btnMicrofone.classList.add('listening');
            statusFala.innerHTML = '<i class="fas fa-circle text-danger me-2"></i>Ouvindo... Fale agora!';
            resultadoFala.innerHTML = '';

            recognition.start();

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                const confidence = event.results[0][0].confidence;
                
                // Atualizar UI com resultado
                btnMicrofone.classList.remove('listening');
                statusFala.innerHTML = '<i class="fas fa-check text-success me-2"></i>Fala detectada!';
                
                // Mostrar o que foi reconhecido
                resultadoFala.innerHTML = `
                    <div class="alert alert-info">
                        <h6><i class="fas fa-comment me-2"></i>Você disse:</h6>
                        <p class="mb-2">"${transcript}"</p>
                        <small class="text-muted">Confiança: ${(confidence * 100).toFixed(1)}%</small>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary me-2" onclick="enviarRespostaFala('${transcript.replace(/'/g, "\\'")}')">
                            <i class="fas fa-check me-1"></i>Enviar Resposta
                        </button>
                        <button class="btn btn-outline-secondary" onclick="iniciarGravacaoFala('${idioma}')">
                            <i class="fas fa-redo me-1"></i>Tentar Novamente
                        </button>
                    </div>
                `;
            };

            recognition.onerror = function(event) {
                btnMicrofone.classList.remove('listening');
                
                let errorMessage = 'Erro desconhecido';
                switch(event.error) {
                    case 'not-allowed':
                    case 'permission-denied':
                        errorMessage = 'Permissão de microfone negada. Permita o acesso ao microfone.';
                        break;
                    case 'no-speech':
                        errorMessage = 'Nenhuma fala detectada. Tente novamente.';
                        break;
                    case 'audio-capture':
                        errorMessage = 'Nenhum microfone detectado. Verifique seu microfone.';
                        break;
                    default:
                        errorMessage = `Erro: ${event.error}`;
                }
                
                statusFala.innerHTML = '<i class="fas fa-times text-danger me-2"></i>Erro ao gravar';
                resultadoFala.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times me-2"></i>${errorMessage}
                    </div>
                    <div class="text-center">
                        <button class="btn btn-outline-primary" onclick="iniciarGravacaoFala('${idioma}')">
                            <i class="fas fa-redo me-1"></i>Tentar Novamente
                        </button>
                    </div>
                `;
            };

            recognition.onend = function() {
                btnMicrofone.classList.remove('listening');
            };

        } catch (error) {
            console.error('Erro ao inicializar reconhecimento de voz:', error);
            resultadoFala.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times me-2"></i>Erro ao inicializar reconhecimento de voz: ${error.message}
                </div>
            `;
        }
    };

    // FUNÇÃO CORRIGIDA PARA ENVIAR RESPOSTA DE FALA
    window.enviarRespostaFala = function(transcript) {
        if (!exercicioAtual) {
            alert('Erro: Nenhum exercício carregado.');
            return;
        }

        const resultadoFala = document.getElementById('resultadoFala');
        
        // Mostrar loading
        resultadoFala.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Processando...</span>
                </div>
                <p class="mt-2 text-muted">Processando sua resposta...</p>
            </div>
        `;

        // CORREÇÃO: Enviar para o controller correto
        fetch('../../admin/controller/processar_exercicio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: transcript,
                tipo_exercicio: 'fala'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Resposta do servidor:', data);
            
            if (data.success) {
                resultadoFala.innerHTML = `
                    <div class="alert ${data.correto ? 'alert-success' : 'alert-danger'}">
                        <h6><i class="fas ${data.correto ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                        ${data.correto ? 'Parabéns! Pronúncia correta!' : 'Precisa melhorar a pronúncia'}</h6>
                        <p class="mb-2">${data.explicacao || 'Sem explicação disponível.'}</p>
                        ${data.dica ? `<small><strong>Dica:</strong> ${data.dica}</small>` : ''}
                        ${data.feedback_pronuncia ? `<div class="mt-2"><small><strong>Feedback:</strong> ${data.feedback_pronuncia}</small></div>` : ''}
                    </div>
                    <div class="text-center">
                        <button class="btn btn-success" onclick="proximoExercicio()">
                            <i class="fas fa-arrow-right me-1"></i>Próximo Exercício
                        </button>
                    </div>
                `;
                
                document.getElementById("btnEnviarResposta").style.display = "none";
                document.getElementById("btnProximoExercicio").style.display = "block";
            } else {
                resultadoFala.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times me-2"></i>Erro ao processar resposta: ${data.message}
                    </div>
                    <div class="text-center">
                        <button class="btn btn-outline-primary" onclick="iniciarGravacaoFala()">
                            <i class="fas fa-redo me-1"></i>Tentar Novamente
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            resultadoFala.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times me-2"></i>Erro de conexão: ${error.message}
                </div>
                <div class="text-center">
                    <button class="btn btn-outline-primary" onclick="iniciarGravacaoFala()">
                        <i class="fas fa-redo me-1"></i>Tentar Novamente
                    </button>
                </div>
            `;
        });
    };

    // ==================== FUNÇÕES DE RESPOSTA ====================

    // Função para selecionar resposta (botão de múltipla escolha)
    window.selecionarResposta = function(button) {
        document.querySelectorAll(".btn-resposta").forEach(btn => {
            btn.classList.remove("selected", "btn-primary");
            btn.classList.add("btn-outline-primary");
        });
        button.classList.remove("btn-outline-primary");
        button.classList.add("selected", "btn-primary");
        respostaSelecionada = button.dataset.id;
    };

    // CORREÇÃO: Função para enviar a resposta do usuário
    window.enviarResposta = function() {
        let respostaUsuario = null;
       
        if (!exercicioAtual) {
            alert("Erro: Nenhum exercício está ativo.");
            return;
        }

        const tipoExercicio = exercicioAtual.tipoExercicioDeterminado || "texto_livre";

        // Captura a resposta com base no tipo de exercício
        if (tipoExercicio === "multipla_escolha") {
            respostaUsuario = respostaSelecionada;
            if (!respostaUsuario) {
                alert("Por favor, selecione uma opção.");
                return;
            }
        } else if (tipoExercicio === "texto_livre") {
            const textarea = document.getElementById("respostaTextoLivre");
            respostaUsuario = textarea ? textarea.value.trim() : null;
            if (!respostaUsuario) {
                alert("Por favor, digite sua resposta.");
                return;
            }
        } else if (tipoExercicio === "completar") {
            const input = document.getElementById("respostaCompletar");
            respostaUsuario = input ? input.value.trim() : null;
            if (!respostaUsuario) {
                alert("Por favor, preencha o campo.");
                return;
            }
        } else if (tipoExercicio === "fala") {
            // Para fala, a resposta é enviada através da função enviarRespostaFala
            alert("Por favor, use o botão de gravação para enviar sua resposta de fala.");
            return;
        } else if (tipoExercicio === "listening") {
            respostaUsuario = respostaSelecionada;
            if (!respostaUsuario) {
                alert("Por favor, selecione uma opção após ouvir o áudio.");
                return;
            }
        } else if (tipoExercicio === "audicao") {
            const textarea = document.getElementById("respostaAudicao");
            respostaUsuario = textarea ? textarea.value.trim() : null;
            if (!respostaUsuario) {
                alert("Por favor, descreva o que você ouviu.");
                return;
            }
        }

        if (!respostaUsuario) {
            alert("Por favor, forneça uma resposta.");
            return;
        }

        // CORREÇÃO: Enviar resposta para o controller correto
        fetch('../../admin/controller/processar_exercicio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: respostaUsuario,
                tipo_exercicio: tipoExercicio
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                exibirFeedback(data);
                document.getElementById("btnEnviarResposta").style.display = "none";
                document.getElementById("btnProximoExercicio").style.display = "block";
            } else {
                alert("Erro ao processar resposta: " + data.message);
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            alert("Erro de conexão. Tente novamente.");
        });
    };

    // Função para exibir feedback (correto/incorreto)
    window.exibirFeedback = function(data) {
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
       
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = "feedbackExercicio";
        feedbackDiv.className = `alert ${data.correto ? 'alert-success' : 'alert-danger'} mt-3`;
        feedbackDiv.innerHTML = `
            <h6 class="alert-heading">
                <i class="fas ${data.correto ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                ${data.correto ? 'Correto!' : 'Incorreto!'}
            </h6>
            <p class="mb-0">${data.explicacao || 'Sem explicação disponível.'}</p>
            ${data.dica ? `<hr><p class="mb-0"><strong>Dica:</strong> ${data.dica}</p>` : ''}
        `;
       
        conteudoExercicioDiv.appendChild(feedbackDiv);

        const tipoExercicio = exercicioAtual.tipoExercicioDeterminado || "texto_livre";

        // Atualiza a aparência dos elementos após a resposta
        if (tipoExercicio === "multipla_escolha") {
            document.querySelectorAll(".btn-resposta").forEach(btn => {
                btn.disabled = true;
                const altId = btn.dataset.id;
               
                let conteudo = exercicioAtual.conteudo;
                if (typeof conteudo === 'string') {
                    try {
                        conteudo = JSON.parse(conteudo);
                    } catch (e) { 
                        console.error("Erro ao parsear conteúdo:", e); 
                    }
                }
               
                if (conteudo.alternativas) {
                    const alternativaCorreta = conteudo.alternativas.find(alt => alt.correta);
                    if (alternativaCorreta && altId === alternativaCorreta.id) {
                        btn.classList.remove("btn-primary", "btn-outline-primary");
                        btn.classList.add("btn-success");
                    } else if (btn.classList.contains("selected")) {
                        btn.classList.remove("btn-primary", "btn-outline-primary");
                        btn.classList.add("btn-danger");
                    }
                }
            });
        } else if (tipoExercicio === "texto_livre" || tipoExercicio === "completar" || tipoExercicio === "audicao") {
            const inputField = document.getElementById(
                tipoExercicio === "texto_livre" ? "respostaTextoLivre" : 
                tipoExercicio === "completar" ? "respostaCompletar" : "respostaAudicao"
            );
            if (inputField) {
                inputField.disabled = true;
                if (data.correto) {
                    inputField.classList.add("is-valid");
                } else {
                    inputField.classList.add("is-invalid");
                }
            }
        }
        // Feedback para listening
        else if (tipoExercicio === "listening") {
            document.querySelectorAll(".btn-resposta").forEach(btn => {
                btn.disabled = true;
                const respostaIndex = parseInt(btn.dataset.id);
                const conteudo = typeof exercicioAtual.conteudo === 'string' ? 
                    JSON.parse(exercicioAtual.conteudo) : exercicioAtual.conteudo;
                
                const respostaCorreta = conteudo.resposta_correta || 0;
                
                if (respostaIndex === respostaCorreta) {
                    btn.classList.remove("btn-outline-primary");
                    btn.classList.add("btn-success");
                } else if (btn.classList.contains("selected")) {
                    btn.classList.remove("btn-outline-primary");
                    btn.classList.add("btn-danger");
                }
            });
            
            // Mostrar frase original do áudio
            if (data.frase_original) {
                const fraseDiv = document.createElement('div');
                fraseDiv.className = 'alert alert-info mt-3';
                fraseDiv.innerHTML = `
                    <strong><i class="fas fa-volume-up me-2"></i>Frase do áudio:</strong>
                    <p class="mb-0 mt-2">"${data.frase_original}"</p>
                `;
                conteudoExercicioDiv.appendChild(fraseDiv);
            }
        }
    };

    // Função para avançar para o próximo exercício
    window.proximoExercicio = function() {
        exercicioIndex++;
        if (exercicioIndex < exerciciosLista.length) {
            carregarExercicio(exercicioIndex);
        } else {
            alert("Parabéns! Você completou todos os exercícios deste bloco.");
            modalExercicios.hide();
            modalBlocos.show();
        }
    };

    // Função para voltar para blocos
    window.voltarParaBlocos = function() {
        modalExercicios.hide();
        modalBlocos.show();
    };

    // ==================== FUNCIONALIDADES DE FLASHCARDS ====================
       
    // Função para abrir modal de adicionar palavra
    window.abrirModalAdicionarPalavra = function() {
        document.getElementById('formAdicionarPalavra').reset();
        document.getElementById('palavraIdioma').value = '<?php echo htmlspecialchars($idioma_escolhido ?? "Ingles"); ?>';
        document.getElementById('palavraNivel').value = '<?php echo htmlspecialchars($nivel_usuario ?? "A1"); ?>';
        modalAdicionarPalavra.show();
    };

    // Função para virar preview do flashcard
    window.virarPreviewPalavra = function() {
        const preview = document.getElementById('palavraPreview');
        preview.classList.toggle('flipped');
    };

    // Função para atualizar preview do flashcard
    function atualizarPreviewFlashcard() {
        const frente = document.getElementById('palavraFrente').value || 'Digite o conteúdo da frente';
        const verso = document.getElementById('palavraVerso').value || 'Digite o conteúdo do verso';
        const dica = document.getElementById('palavraDica').value;

        document.getElementById('previewPalavraFrente').textContent = frente;
        document.getElementById('previewPalavraVerso').textContent = verso;

        const dicaContainer = document.getElementById('previewDicaContainer');
        const dicaText = document.getElementById('previewPalavraDica');
        
        if (dica && dica.trim() !== '') {
            dicaText.textContent = dica;
            dicaContainer.style.display = 'block';
        } else {
            dicaContainer.style.display = 'none';
        }
    }
       
    // Função para salvar palavra
    window.salvarPalavra = function() {
        const form = document.getElementById('formAdicionarPalavra');
        const formData = new FormData(form);
        formData.append('action', 'adicionar_flashcard');
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalAdicionarPalavra.hide();
                carregarPalavras();
                alert('Palavra adicionada com sucesso!');
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    };
       
    // Função para carregar palavras do usuário
    window.carregarPalavras = function() {
        const status = document.getElementById('filtroPalavrasStatus').value;
        const container = document.getElementById('listaPalavras');
        
        console.log('=== CARREGAR PALAVRAS INICIADO ===');
        console.log('Status:', status);
        console.log('URL do controller:', 'flashcard_controller.php');
        
        // Mostra loading
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2 text-muted">Carregando suas palavras...</p>
            </div>
        `;
        
        const formData = new FormData();
        formData.append('action', 'listar_flashcards_painel');
        if (status !== '') {
            formData.append('status', status);
        }
        
        console.log('Enviando requisição para flashcard_controller.php');
        
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Resposta recebida - Status:', response.status);
            console.log('Resposta OK:', response.ok);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            if (data.success) {
                palavrasCarregadas = data.flashcards;
                exibirPalavras(data.flashcards);
            } else {
                console.error('Erro do servidor:', data.message);
                exibirErroPalavras(data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            console.error('Detalhes do erro:', error.message);
            exibirErroPalavras('Erro de conexão. Tente novamente. Detalhes: ' + error.message);
        });
    };

    // Função para exibir palavras na interface
    window.exibirPalavras = function(palavras) {
        const container = document.getElementById('listaPalavras');
       
        if (!palavras || palavras.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma palavra encontrada. Adicione suas primeiras palavras!
                    </div>
                </div>
            `;
            return;
        }
       
        let html = '';
        palavras.forEach(palavra => {
            const statusClass = palavra.aprendido == 1 ? 'success' : 'warning';
            const statusText = palavra.aprendido == 1 ? 'Aprendida' : 'Estudando';
            const statusIcon = palavra.aprendido == 1 ? 'fa-check-circle' : 'fa-clock';
           
            html += `
                <div class="col-md-6 mb-3 palavra-item" data-palavra="${palavra.palavra_frente.toLowerCase()}" data-traducao="${palavra.palavra_verso.toLowerCase()}">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">${palavra.palavra_frente}</h6>
                                <span class="badge bg-${statusClass}">
                                    <i class="fas ${statusIcon} me-1"></i>${statusText}
                                </span>
                            </div>
                            <p class="card-text text-muted mb-2">${palavra.palavra_verso}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    ${palavra.idioma} • ${palavra.nivel}
                                    ${palavra.categoria ? ' • ' + palavra.categoria : ''}
                                </small>
                                <div class="btn-group" role="group">
                                    ${palavra.aprendido == 1 ? 
                                        `<button class="btn btn-outline-warning btn-sm" onclick="alterarStatusPalavra(${palavra.id}, false)">
                                            <i class="fas fa-undo me-1"></i>Estudar
                                        </button>` :
                                        `<button class="btn btn-outline-success btn-sm" onclick="alterarStatusPalavra(${palavra.id}, true)">
                                            <i class="fas fa-check me-1"></i>Aprendi
                                        </button>`
                                    }
                                    <button class="btn btn-outline-danger btn-sm" onclick="excluirPalavra(${palavra.id})">
                                        <i class="fas fa-trash me-1"></i>Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
       
        container.innerHTML = html;
    };
       
    // Função para filtrar palavras localmente
    window.filtrarPalavrasLocal = function() {
        const busca = document.getElementById('filtroPalavrasBusca').value.toLowerCase();
        const items = document.querySelectorAll('.palavra-item');
       
        items.forEach(item => {
            const palavra = item.dataset.palavra;
            const traducao = item.dataset.traducao;
           
            if (palavra.includes(busca) || traducao.includes(busca)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    };
       
    // Função para alterar status de palavra (aprendida/não aprendida)
    window.alterarStatusPalavra = function(idFlashcard, marcarComoAprendida) {
        const action = marcarComoAprendida ? 'marcar_como_aprendido' : 'desmarcar_como_aprendido';
       
        const formData = new FormData();
        formData.append('action', action);
        formData.append('id_flashcard', idFlashcard);
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarPalavras();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    };
       
    // Função para excluir palavra
    window.excluirPalavra = function(idFlashcard) {
        // Prepara e exibe o modal de confirmação
        document.getElementById('mensagemModalExclusao').innerHTML = "Tem certeza que deseja excluir esta palavra? Esta ação não pode ser desfeita.";
        
        const btnConfirmar = document.getElementById('btnConfirmarExclusao');
        
        // Remove listeners antigos para evitar múltiplas execuções
        const novoBtn = btnConfirmar.cloneNode(true);
        btnConfirmar.parentNode.replaceChild(novoBtn, btnConfirmar);

        novoBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'excluir_flashcard');
            formData.append('id_flashcard', idFlashcard);
        
            fetch('flashcard_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                modalConfirmarExclusao.hide();
                if (data.success) {
                    carregarPalavras();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                modalConfirmarExclusao.hide();
                console.error('Erro:', error);
                alert('Erro de conexão. Tente novamente.');
            });
        });
        modalConfirmarExclusao.show();
    };
       
    // Função para exibir erro ao carregar palavras
    window.exibirErroPalavras = function(mensagem) {
        const container = document.getElementById('listaPalavras');
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${mensagem}
                </div>
            </div>
        `;
    };

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>