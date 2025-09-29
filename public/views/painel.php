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
    <!-- link direto dos icones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" />
</head>

<body>
    <header id="myHeader">
    <div class="header-content">
        <div class="user-profile">
            <img src="..\..\imagens\avatar-placeholder.png" alt="Avatar Usuário" class="user-avatar">
            <span class="user-name">Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
        </div>
        <nav>
            <ul>
                <li>
                    <a href="painel.php" class="nav-link-item">
                        <span class="material-symbols-outlined">home</span>
                        <span>Início</span>
                    </a>
                </li>
                <li>
                    <a href="flashcards.php" class="nav-link-item">
                        <span class="material-symbols-outlined">cards_star</span>
                        <span>Flash Cards</span>
                    </a>
                </li>
                <li>
                    <a href="../../logout.php" class="nav-link-item">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Sair</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>


    <div class="container mt-5">
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
                                <div class="col-md-8">
                                    <h5 class="mb-0"> <i class="fas fa-book me-2"></i> Minhas Palavras </h5>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn btn-light btn-sm w-100" onclick="abrirModalAdicionarPalavra()">
                                        <i class="fas fa-plus me-2"></i>Adicionar Palavra
                                    </button>

                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filtros de Palavras -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" id="filtroPalavrasStatus" onchange="carregarPalavras()">
                                        <option value="">Todas as palavras</option>
                                        <option value="0">Não aprendidas</option>
                                        <option value="1">Aprendidas</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" id="filtroPalavrasBusca" placeholder="Buscar palavra..." onkeyup="filtrarPalavrasLocal()">
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="carregarPalavras()">
                                        <i class="fas fa-sync me-1"></i>Atualizar
                                    </button>
                                </div>
                            </div>
                           
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
                                    <div class="card unidade-card h-100" onclick="abrirAtividades(<?php echo $unidade["id"]; ?>, '<?php echo htmlspecialchars($unidade["nome_unidade"]); ?>', <?php echo $unidade["numero_unidade"]; ?>)">
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


    <!-- Modal Adicionar Palavra -->
    <div class="modal fade" id="modalAdicionarPalavra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Adicionar Nova Palavra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formAdicionarPalavra">
                        <div class="mb-3">
                            <label for="palavraFrente" class="form-label">Palavra/Frase *</label>
                            <input type="text" class="form-control" id="palavraFrente" name="palavra_frente" required placeholder="Ex: Hello">
                        </div>
                        <div class="mb-3">
                            <label for="palavraVerso" class="form-label">Tradução *</label>
                            <input type="text" class="form-control" id="palavraVerso" name="palavra_verso" required placeholder="Ex: Olá">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="palavraIdioma" class="form-label">Idioma</label>
                                    <select class="form-select" id="palavraIdioma" name="idioma">
                                        <option value="Ingles">Inglês</option>
                                        <option value="Japones">Japonês</option>
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
                    </form>
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

    <!-- Modal Atividades -->
    <div class="modal fade" id="modalAtividades" tabindex="-1" aria-labelledby="modalAtividadesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAtividadesLabel">
                        <i class="fas fa-tasks me-2"></i>
                        <span id="tituloAtividades">Atividades da Unidade</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaAtividades" class="row">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando atividades...</p>
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
                    <button type="button" class="btn btn-secondary" onclick="voltarParaAtividades()">
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

    <script>
    // ==================== VARIÁVEIS GLOBAIS ====================
    let modalAtividades = null;
    let modalExercicios = null;
    let modalAdicionarPalavra = null;
    let unidadeAtual = null;
    let atividadeAtual = null;
    let exercicioAtual = null;
    let exerciciosLista = [];
    let exercicioIndex = 0;
    let respostaSelecionada = null;
    let palavrasCarregadas = [];

    // ==================== INICIALIZAÇÃO ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Inicialização dos modais
        modalAtividades = new bootstrap.Modal(document.getElementById('modalAtividades'));
        modalExercicios = new bootstrap.Modal(document.getElementById('modalExercicios'));
        modalAdicionarPalavra = new bootstrap.Modal(document.getElementById('modalAdicionarPalavra'));

        // Carrega palavras do usuário ao inicializar
        if (typeof carregarPalavras === 'function') {
            carregarPalavras();
        }

        // Event listeners para cards de unidades
        const unidadeCards = document.querySelectorAll('.unidade-card');
        unidadeCards.forEach(card => {
            card.addEventListener('click', function() {
                const unidadeId = this.getAttribute('onclick').match(/abrirAtividades\((\d+),/)[1];
                const titulo = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                const numero = this.getAttribute('onclick').match(/,\s*(\d+)\)/)[1];

                if (unidadeId && titulo && numero) {
                    abrirAtividades(parseInt(unidadeId), titulo, parseInt(numero));
                }
            });
        });
    });

    // ==================== FUNÇÕES PRINCIPAIS DE NAVEGAÇÃO E LÓGICA ====================

    // Função para abrir modal de atividades
    window.abrirAtividades = function(unidadeId, tituloUnidade, numeroUnidade) {
        console.log('Abrindo atividades para unidade:', unidadeId, tituloUnidade, numeroUnidade);
        
        // Fecha o modal de exercícios se estiver aberto
        if (modalExercicios && modalExercicios._isShown) {
            modalExercicios.hide();
        }
       
        unidadeAtual = unidadeId;
        document.getElementById("tituloAtividades").textContent = `Atividades da Unidade ${numeroUnidade}: ${tituloUnidade}`;
       
        // Carregar atividades via AJAX
        fetch(`../../admin/controller/get_atividades.php?unidade_id=${unidadeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                if (data.success) {
                    exibirAtividades(data.atividades);
                    modalAtividades.show();
                } else {
                    alert("Erro ao carregar atividades: " + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro ao carregar atividades:", error);
                alert("Erro de rede ao carregar atividades: " + error.message);
            });
    };

    // Função para exibir atividades no modal
    function exibirAtividades(atividades) {
        const container = document.getElementById("listaAtividades");
        container.innerHTML = ""; // Limpa o container antes de adicionar novas atividades

        if (!atividades || atividades.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma atividade encontrada para esta unidade.
                    </div>
                </div>
            `;
            return;
        }

        atividades.forEach(atividade => {
            const col = document.createElement("div");
            col.className = "col-md-6 mb-3";
           
            const icone = atividade.icone || 'fa-book';
            const progresso = atividade.progresso || 0;
           
            col.innerHTML = `
                <div class="card atividade-card h-100" onclick="abrirExercicios(${atividade.id}, '${atividade.nome.replace(/'/g, "\\'")}')">
                    <div class="card-body text-center">
                        <i class="fas ${icone} atividade-icon mb-3" style="font-size: 2rem; color: #007bff;"></i>
                        <h5 class="card-title">${atividade.nome}</h5>
                        <p class="card-text text-muted">${atividade.descricao || 'Descrição não disponível'}</p>
                        <div class="progress progress-bar-custom mb-2">
                            <div class="progress-bar" role="progressbar" style="width: ${progresso}%" aria-valuenow="${progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">${progresso}% concluído</small>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="event.stopPropagation(); abrirTeoriaAtividade(${atividade.id}, '${atividade.nome.replace(/'/g, "\\'")}')">
                                <i class="fas fa-info-circle me-1"></i>Teoria
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    }

    // Função para abrir modal de exercícios
    window.abrirExercicios = function(atividadeId, tituloAtividade) {
        console.log('Abrindo exercícios para atividade:', atividadeId, tituloAtividade);
        
        atividadeAtual = atividadeId;
        document.getElementById("tituloExercicios").textContent = `Exercícios: ${tituloAtividade}`;
       
        // Carregar exercícios via AJAX
        fetch(`../../admin/controller/get_exercicio.php?atividade_id=${atividadeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Exercícios recebidos:', data);
                if (data.success) {
                    exerciciosLista = data.exercicios;
                    exercicioIndex = 0;
                    if (exerciciosLista.length > 0) {
                        carregarExercicio(exercicioIndex);
                        modalAtividades.hide(); // Fecha modal de atividades
                        modalExercicios.show(); // Abre modal de exercícios
                    } else {
                        alert("Nenhum exercício encontrado para esta atividade.");
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

    // Função para carregar um exercício específico no modal de Exercícios
    function carregarExercicio(index) {
        if (!exerciciosLista || exerciciosLista.length === 0) return;

        exercicioAtual = exerciciosLista[index];
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        conteudoExercicioDiv.innerHTML = ""; // Limpa conteúdo anterior
        respostaSelecionada = null; // Reseta a resposta selecionada

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

        // Parse do conteúdo JSON do exercício
        let conteudo = {};
        try {
            conteudo = JSON.parse(exercicioAtual.conteudo);
        } catch (e) {
            console.error("Erro ao fazer parse do conteúdo do exercício:", e);
            conteudo = {};
        }

        // Renderiza o conteúdo com base no tipo de exercício
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            if (conteudo.alternativas) {
                htmlConteudo += '<div class="d-grid gap-2">';
                conteudo.alternativas.forEach(alt => {
                    htmlConteudo += `
                        <button type="button" class="btn btn-outline-primary btn-resposta text-start" data-id="${alt.id}" onclick="selecionarResposta(this)">
                            ${alt.texto}
                        </button>
                    `;
                });
                htmlConteudo += '</div>';
            }
        } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
            htmlConteudo += `
                <div class="mb-3">
                    <label for="respostaTextoLivre" class="form-label">Sua resposta:</label>
                    <textarea id="respostaTextoLivre" class="form-control" rows="4" placeholder="Digite sua resposta aqui..."></textarea>
                </div>
            `;
        } else if (exercicioAtual.tipo_exercicio === "fala") {
            htmlConteudo += `
                <div class="text-center p-4">
                    <i class="fas fa-microphone fa-5x text-primary mb-3" id="microfoneIcon" style="cursor: pointer;" onclick="iniciarGravacao()"></i>
                    <p id="statusGravacao" class="text-muted fs-5">Clique no microfone para falar</p>
                    <div class="mt-3 p-3 bg-light rounded">
                        <p class="mb-1 text-muted">Frase para repetir:</p>
                        <p id="fraseParaFalar" class="fs-4 fw-bold text-dark">"${conteudo.frase_esperada || 'Nenhuma frase definida'}"</p>
                    </div>
                </div>
            `;
        }

        conteudoExercicioDiv.innerHTML = htmlConteudo;

        // Atualiza botões de ação
        document.getElementById("btnEnviarResposta").style.display = "block";
        document.getElementById("btnProximoExercicio").style.display = "none";
       
        // Remove feedback anterior se existir
        const feedbackDiv = document.getElementById("feedbackExercicio");
        if (feedbackDiv) feedbackDiv.remove();
    }

    // Função para selecionar resposta (botão de múltipla escolha)
    window.selecionarResposta = function(button) {
        // Remove a seleção de todos os botões de resposta
        document.querySelectorAll(".btn-resposta").forEach(btn => {
            btn.classList.remove("selected", "btn-primary");
            btn.classList.add("btn-outline-primary");
        });
        // Adiciona a seleção ao botão clicado
        button.classList.remove("btn-outline-primary");
        button.classList.add("selected", "btn-primary");
        respostaSelecionada = button.dataset.id; // Armazena o ID da resposta selecionada
    };

    // Função para enviar a resposta do usuário
    window.enviarResposta = function() {
        let respostaUsuario = null;
       
        if (!exercicioAtual) {
            alert("Erro: Nenhum exercício está ativo.");
            return;
        }

        // Captura a resposta com base no tipo de exercício
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            respostaUsuario = respostaSelecionada;
        } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
            const textarea = document.getElementById("respostaTextoLivre");
            if (!textarea) {
                alert("Erro: Campo de resposta de texto não encontrado.");
                return;
            }
            respostaUsuario = textarea.value.trim();
        } else if (exercicioAtual.tipo_exercicio === "fala") {
            respostaUsuario = "aguardando_processamento_fala";
        }

        // Validação básica da resposta
        if (!respostaUsuario) {
            alert("Por favor, selecione uma opção ou digite sua resposta.");
            return;
        }

        // Envia a resposta para o backend
        fetch(`../../admin/controller/processar_exercicio.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: respostaUsuario,
                tipo_exercicio: exercicioAtual.tipo_exercicio
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            exibirFeedback(data);
            document.getElementById("btnEnviarResposta").style.display = "none";
            document.getElementById("btnProximoExercicio").style.display = "block";
        })
        .catch(error => {
            console.error("Erro ao enviar resposta:", error);
            alert("Ocorreu um erro ao processar sua resposta. Tente novamente.");
        });
    };

    // Função para exibir feedback (correto/incorreto)
    window.exibirFeedback = function(data) {
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
       
        // Cria o elemento de feedback dinamicamente
        const feedbackDiv = document.createElement('div');
        feedbackDiv.id = "feedbackExercicio";
        feedbackDiv.className = `alert ${data.correto ? 'alert-success' : 'alert-danger'} mt-3`;
        feedbackDiv.innerHTML = `
            <h6 class="alert-heading">
                <i class="fas ${data.correto ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
                ${data.correto ? 'Correto!' : 'Incorreto!'}
            </h6>
            <p class="mb-0">${data.explicacao || 'Sem explicação disponível.'}</p>
        `;
       
        conteudoExercicioDiv.appendChild(feedbackDiv);

        // Atualiza a aparência dos botões de múltipla escolha após a resposta
        if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
            document.querySelectorAll(".btn-resposta").forEach(btn => {
                btn.disabled = true; // Desabilita todos os botões
                const altId = btn.dataset.id;
               
                // Parse do conteúdo do exercício para encontrar a alternativa correta
                let conteudo = {};
                try {
                    conteudo = JSON.parse(exercicioAtual.conteudo);
                } catch (e) { 
                    console.error("Erro ao parsear conteúdo:", e); 
                }
               
                if (conteudo.alternativas) {
                    const alternativaCorreta = conteudo.alternativas.find(alt => alt.correta);
                    if (alternativaCorreta && altId === alternativaCorreta.id) {
                        btn.classList.remove("btn-primary", "btn-outline-primary");
                        btn.classList.add("btn-success"); // Marca a correta
                    } else if (btn.classList.contains("selected")) {
                        btn.classList.remove("btn-primary", "btn-outline-primary");
                        btn.classList.add("btn-danger"); // Marca a incorreta se foi a selecionada
                    }
                }
            });
        }
    };

    // Função para avançar para o próximo exercício
    window.proximoExercicio = function() {
        exercicioIndex++;
        if (exercicioIndex < exerciciosLista.length) {
            carregarExercicio(exercicioIndex);
        } else {
            // Fim dos exercícios
            alert("Parabéns! Você completou todos os exercícios desta atividade.");
            modalExercicios.hide();
            modalAtividades.show(); // Volta para o modal de atividades
        }
    };

    // Função para voltar para atividades
    window.voltarParaAtividades = function() {
        modalExercicios.hide();
        modalAtividades.show();
    };

    // Função para abrir teoria da atividade (placeholder)
    window.abrirTeoriaAtividade = function(atividadeId, nomeAtividade) {
        alert(`Teoria da atividade "${nomeAtividade}" será implementada em breve.`);
    };

    // Função para iniciar gravação (placeholder para exercícios de fala)
    window.iniciarGravacao = function() {
        alert("Funcionalidade de gravação de fala será implementada em breve.");
    };

    // ==================== FUNCIONALIDADES DE FLASHCARDS ====================
   
    // Função para abrir modal de adicionar palavra
    window.abrirModalAdicionarPalavra = function() {
        // Limpa o formulário
        document.getElementById('formAdicionarPalavra').reset();
       
        // Define valores padrão baseados no usuário atual
        document.getElementById('palavraIdioma').value = '<?php echo htmlspecialchars($idioma_escolhido ?? "Ingles"); ?>';
        document.getElementById('palavraNivel').value = '<?php echo htmlspecialchars($nivel_usuario ?? "A1"); ?>';
       
        // Abre o modal
        modalAdicionarPalavra.show();
    };
   
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
                carregarPalavras(); // Recarrega a lista
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
        formData.append('action', 'listar_flashcards');
        if (status !== '') {
            formData.append('status', status);
        }
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                palavrasCarregadas = data.flashcards;
                exibirPalavras(data.flashcards);
            } else {
                exibirErroPalavras(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            exibirErroPalavras('Erro de conexão. Tente novamente.');
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
                carregarPalavras(); // Recarrega a lista
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
        if (!confirm('Tem certeza que deseja excluir esta palavra?')) {
            return;
        }
       
        const formData = new FormData();
        formData.append('action', 'excluir_flashcard');
        formData.append('id_flashcard', idFlashcard);
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarPalavras(); // Recarrega a lista
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
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