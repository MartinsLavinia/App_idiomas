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
    header("Location: index.php");
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .unidade-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .unidade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .popup-modal {
            max-width: 800px;
            margin: 2rem auto;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-50px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .atividade-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #dee2e6;
        }
        
        .atividade-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .atividade-icon {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        
        .exercicio-container {
            min-height: 400px;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        
        .btn-resposta {
            margin: 0.5rem 0;
            padding: 1rem;
            text-align: left;
            border: 2px solid #dee2e6;
            background: white;
            transition: all 0.3s ease;
        }
        
        .btn-resposta:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        
        .btn-resposta.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .btn-resposta.correct {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .btn-resposta.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .feedback-container {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            display: none;
        }
        
        .feedback-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .feedback-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Site de Idiomas</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </span>
                <a href="//logout.php" class="btn btn-outline-light">Sair</a>
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
                    <div class="row">
                        <?php if (count($unidades) > 0): ?>
                            <?php foreach ($unidades as $unidade): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card unidade-card h-100" onclick="abrirAtividades(<?php echo $unidade["id"]; ?>, '<?php echo htmlspecialchars($unidade["titulo"]); ?>', <?php echo $unidade["numero_unidade"]; ?>)">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-book-open me-2"></i>
                                                Unidade <?php echo htmlspecialchars($unidade["numero_unidade"]); ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($unidade["titulo"]); ?></h6>
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

    <!-- Modal de Atividades -->
    <div class="modal fade" id="modalAtividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg popup-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloAtividades">Atividades da Unidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaAtividades" class="row">
                        <!-- Atividades serão carregadas aqui via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exercícios -->
    <div class="modal fade" id="modalExercicios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg popup-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloExercicios">Exercícios</h5>
                    <div class="d-flex align-items-center">
                        <span id="contadorExercicios" class="badge bg-primary me-3">1/12</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body exercicio-container">
                    <div class="progress progress-bar-custom mb-4">
                        <div id="progressoExercicios" class="progress-bar" role="progressbar" style="width: 8.33%" aria-valuenow="8.33" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="conteudoExercicio">
                        <!-- Conteúdo do exercício será carregado aqui via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="voltarParaAtividades()">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </button>
                    <button type="button" id="btnEnviarResposta" class="btn btn-primary" onclick="enviarResposta()">
                        Enviar Resposta
                    </button>
                    <button type="button" id="btnProximoExercicio" class="btn btn-success" onclick="proximoExercicio()" style="display: none;">
                        Próximo <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variáveis globais
        let unidadeAtual = null;
        let atividadeAtual = null;
        let exercicioAtual = null;
        let exerciciosLista = [];
        let exercicioIndex = 0;
        let respostaSelecionada = null;

        // Função para abrir modal de atividades
        function abrirAtividades(unidadeId, tituloUnidade, numeroUnidade) {
            unidadeAtual = unidadeId;
            document.getElementById("tituloAtividades").textContent = `Atividades da Unidade ${numeroUnidade}: ${tituloUnidade}`;
            
            // Carregar atividades via AJAX
            fetch(`../../admin/controller/get_atividades.php?unidade_id=${unidadeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        exibirAtividades(data.atividades);
                        new bootstrap.Modal(document.getElementById("modalAtividades")).show();
                    } else {
                        alert("Erro ao carregar atividades: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erro:", error);
                    alert("Erro ao carregar atividades");
                });
        }

        // Função para exibir atividades no modal
        function exibirAtividades(atividades) {
            const container = document.getElementById("listaAtividades");
            container.innerHTML = "";

            atividades.forEach(atividade => {
                const col = document.createElement("div");
                col.className = "col-md-6 mb-3";
                
                col.innerHTML = `
                    <div class="card atividade-card h-100" onclick="abrirExercicios(${atividade.id}, '${atividade.nome}')">
                        <div class="card-body text-center">
                            <i class="fas ${atividade.icone} atividade-icon"></i>
                            <h5 class="card-title">${atividade.nome}</h5>
                            <p class="card-text text-muted">${atividade.descricao}</p>
                            <div class="progress progress-bar-custom mb-2">
                                <div class="progress-bar" role="progressbar" style="width: ${atividade.progresso}%" aria-valuenow="${atividade.progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">${atividade.progresso}% concluído</small>
                            <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="event.stopPropagation(); abrirTeoriaAtividade(${atividade.id}, '${atividade.nome}')">
                                <i class="fas fa-info-circle me-1"></i>Teoria
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(col);
            });
        }

        // Função para abrir modal de exercícios
        function abrirExercicios(atividadeId, tituloAtividade) {
            atividadeAtual = atividadeId;
            document.getElementById("tituloExercicios").textContent = `Exercícios: ${tituloAtividade}`;
            
            // Carregar exercícios via AJAX
            fetch(`../../admin/controller/get_exercicio.php?atividade_id=${atividadeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        exerciciosLista = data.exercicios;
                        exercicioIndex = 0;
                        if (exerciciosLista.length > 0) {
                            carregarExercicio(exercicioIndex);
                            new bootstrap.Modal(document.getElementById("modalExercicios")).show();
                        } else {
                            alert("Nenhum exercício encontrado para esta atividade.");
                        }
                    } else {
                        alert("Erro ao carregar exercícios: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erro:", error);
                    alert("Erro ao carregar exercícios");
                });
        }

        // Função para carregar um exercício específico
        function carregarExercicio(index) {
            exercicioAtual = exerciciosLista[index];
            const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
            conteudoExercicioDiv.innerHTML = ""; // Limpa conteúdo anterior
            respostaSelecionada = null;

            document.getElementById("contadorExercicios").textContent = `${index + 1}/${exerciciosLista.length}`;
            const progresso = ((index + 1) / exerciciosLista.length) * 100;
            document.getElementById("progressoExercicios").style.width = `${progresso}%`;
            document.getElementById("progressoExercicios").setAttribute("aria-valuenow", progresso);

            let htmlConteudo = `
                <p class="fs-5 mb-4">${exercicioAtual.pergunta}</p>
            `;

            // Parse do conteúdo JSON
            let conteudo = {};
            try {
                conteudo = JSON.parse(exercicioAtual.conteudo);
            } catch (e) {
                console.error("Erro ao fazer parse do conteúdo:", e);
                conteudo = {};
            }

            if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
                if (conteudo.alternativas) {
                    conteudo.alternativas.forEach(alt => {
                        htmlConteudo += `
                            <button type="button" class="btn btn-light btn-resposta w-100" data-id="${alt.id}" onclick="selecionarResposta(this)">
                                ${alt.texto}
                            </button>
                        `;
                    });
                }
            } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
                htmlConteudo += `
                    <div class="mb-3">
                        <textarea id="respostaTextoLivre" class="form-control" rows="4" placeholder="Digite sua resposta aqui..." style="width: 100%; min-height: 100px;"></textarea>
                    </div>
                `;
            } else if (exercicioAtual.tipo_exercicio === "fala") {
                htmlConteudo += `
                    <div class="text-center">
                        <i class="fas fa-microphone fa-5x text-primary mb-3" id="microfoneIcon" style="cursor: pointer;" onclick="iniciarGravacao()"></i>
                        <p id="statusGravacao" class="text-muted">Clique no microfone para falar</p>
                        <p id="fraseParaFalar" class="fs-4">"${conteudo.frase_esperada || ''}"</p>
                    </div>
                `;
            } else if (exercicioAtual.tipo_exercicio === "especial") {
                // Lógica para exercícios especiais (resumo, cena final)
                htmlConteudo += `
                    <div class="alert alert-info">Este é um exercício especial.</div>
                    <div id="conteudoEspecial"></div>
                `;
                // Chamar função específica para carregar conteúdo especial
                carregarConteudoEspecial(conteudo);
            }

            conteudoExercicioDiv.innerHTML = htmlConteudo;
            document.getElementById("btnEnviarResposta").style.display = "block";
            document.getElementById("btnProximoExercicio").style.display = "none";
            // Remover feedback anterior
            const feedbackDiv = document.getElementById("feedbackExercicio");
            if (feedbackDiv) feedbackDiv.remove();
        }

        // Função para selecionar resposta (múltipla escolha)
        function selecionarResposta(button) {
            document.querySelectorAll(".btn-resposta").forEach(btn => {
                btn.classList.remove("selected");
            });
            button.classList.add("selected");
            respostaSelecionada = button.dataset.id;
        }

        // Função para enviar resposta
        function enviarResposta() {
            let respostaUsuario = null;
            if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
                respostaUsuario = respostaSelecionada;
            } else if (exercicioAtual.tipo_exercicio === "texto_livre") {
                const textarea = document.getElementById("respostaTextoLivre");
                if (!textarea) {
                    alert("Campo de resposta não encontrado!");
                    return;
                }
                respostaUsuario = textarea.value.trim();
            } else if (exercicioAtual.tipo_exercicio === "fala") {
                // A resposta da fala será processada pela Web Speech API
                // Por enquanto, apenas para demonstração
                respostaUsuario = "resposta_fala_simulada"; 
            } else if (exercicioAtual.tipo_exercicio === "especial") {
                // Lógica de envio para exercícios especiais
                // Isso pode ser mais complexo e envolver outro endpoint
                alert("Funcionalidade de envio para exercícios especiais ainda não implementada.");
                return;
            }

            if (!respostaUsuario) {
                alert("Por favor, selecione ou digite uma resposta.");
                return;
            }

            // Enviar resposta para o backend
            fetch(`../../admin/controller/processar_exercicio.php`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    exercicio_id: exercicioAtual.id,
                    resposta: respostaUsuario,
                    tipo_exercicio: exercicioAtual.tipo_exercicio
                })
            })
            .then(response => response.json())
            .then(data => {
                exibirFeedback(data);
                document.getElementById("btnEnviarResposta").style.display = "none";
                document.getElementById("btnProximoExercicio").style.display = "block";
            })
            .catch(error => {
                console.error("Erro ao enviar resposta:", error);
                alert("Erro ao processar sua resposta.");
            });
        }

        // Função para exibir feedback
        function exibirFeedback(data) {
            const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
            let feedbackHtml = `
                <div id="feedbackExercicio" class="feedback-container ${data.correto ? 'feedback-success' : 'feedback-error'}">
                    <p class="mb-0"><strong>${data.correto ? 'Correto!' : 'Incorreto!'}</strong> ${data.explicacao}</p>
                </div>
            `;
            conteudoExercicioDiv.insertAdjacentHTML('beforeend', feedbackHtml);

            // Marcar a resposta correta/incorreta para múltipla escolha
            if (exercicioAtual.tipo_exercicio === "multipla_escolha") {
                document.querySelectorAll(".btn-resposta").forEach(btn => {
                    btn.disabled = true; // Desabilita botões após resposta
                    const altId = btn.dataset.id;
                    
                    // Parse do conteúdo para encontrar a alternativa correta
                    let conteudo = {};
                    try {
                        conteudo = JSON.parse(exercicioAtual.conteudo);
                    } catch (e) {
                        console.error("Erro ao fazer parse do conteúdo:", e);
                    }
                    
                    if (conteudo.alternativas) {
                        const alternativaCorreta = conteudo.alternativas.find(alt => alt.correta);
                        if (alternativaCorreta && altId === alternativaCorreta.id) {
                            btn.classList.add("correct");
                        } else if (btn.classList.contains("selected")) {
                            btn.classList.add("incorrect");
                        }
                    }
                });
            }
        }

        // Função para ir para o próximo exercício
        function proximoExercicio() {
            exercicioIndex++;
            if (exercicioIndex < exerciciosLista.length) {
                carregarExercicio(exercicioIndex);
            } else {
                alert("Atividade concluída!");
                // Fechar modal de exercícios e talvez reabrir o de atividades
                const modalExercicios = bootstrap.Modal.getInstance(document.getElementById("modalExercicios"));
                if (modalExercicios) modalExercicios.hide();
                // Opcional: recarregar atividades para atualizar progresso
                // abrirAtividades(unidadeAtual, document.getElementById('tituloAtividades').textContent.split(': ')[1], document.getElementById('tituloAtividades').textContent.split(' ')[3].replace(':', ''));
            }
        }

        // Função para voltar para o modal de atividades
        function voltarParaAtividades() {
            const modalExercicios = bootstrap.Modal.getInstance(document.getElementById("modalExercicios"));
            if (modalExercicios) modalExercicios.hide();
            new bootstrap.Modal(document.getElementById("modalAtividades")).show();
        }

        // Funções para Exercício de Fala (simulação)
        function iniciarGravacao() {
            document.getElementById("statusGravacao").textContent = "Gravando...";
            document.getElementById("microfoneIcon").classList.add("text-danger");
            // Simular gravação e reconhecimento
            setTimeout(() => {
                document.getElementById("statusGravacao").textContent = "Processando...";
                setTimeout(() => {
                    document.getElementById("statusGravacao").textContent = "Concluído!";
                    document.getElementById("microfoneIcon").classList.remove("text-danger");
                    // Aqui você chamaria enviarResposta() com o resultado real da fala
                    // Para demonstração, vamos simular uma resposta correta
                    exibirFeedback({ correto: true, explicacao: "Sua pronúncia está ótima!" });
                    document.getElementById("btnEnviarResposta").style.display = "none";
                    document.getElementById("btnProximoExercicio").style.display = "block";
                }, 1500);
            }, 2000);
        }

        // Funções para Exercícios Especiais (simulação)
        function carregarConteudoEspecial(conteudo) {
            const conteudoEspecialDiv = document.getElementById("conteudoEspecial");
            if (conteudo.tipo === "resumo") {
                conteudoEspecialDiv.innerHTML = `<p>Conteúdo do resumo: ${conteudo.texto_resumo}</p>`;
            } else if (conteudo.tipo === "cena_final") {
                conteudoEspecialDiv.innerHTML = `<p>Cena final: ${conteudo.descricao_cena}</p>`;
            }
        }

        // Funções para Explicações Teóricas (simulação)
        function abrirTeoriaAtividade(atividadeId, tituloAtividade) {
            alert(`Abrindo teoria para a atividade: ${tituloAtividade} (ID: ${atividadeId})`);
            // Aqui você faria um fetch para admin/controller/explicacoes_teoricas.php
            // e exibiria o conteúdo em um novo modal.
        }

        // Inicialização do modal de seleção de idioma se necessário
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($mostrar_selecao_idioma): ?>
                var idiomaModal = new bootstrap.Modal(document.getElementById('idiomaModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                idiomaModal.show();
            <?php endif; ?>
        });
    </script>
    <script src="../js/exercicio_fala.js"></script>
    <script src="../js/exercicios_especiais.js"></script>
    <script src="../js/explicacoes_teoricas.js"></script>
</body>
</html>