<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

$idioma_selecionado = isset($_GET['idioma']) ? trim($_GET['idioma']) : null;

if (empty($idioma_selecionado)) {
    header("Location: gerenciar_caminho.php?msg=" . urlencode("Erro: Idioma não especificado para o quiz."));
    exit();
}

// Lógica para adicionar uma nova pergunta (via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $pergunta = $_POST['pergunta'];
    $alternativa_a = $_POST['alternativa_a'];
    $alternativa_b = $_POST['alternativa_b'];
    $alternativa_c = $_POST['alternativa_c'];
    $alternativa_d = $_POST['alternativa_d'];
    $resposta_correta = $_POST['resposta_correta'];

    $sql_insert_quiz = "INSERT INTO quiz_nivelamento (idioma, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_quiz = $conn->prepare($sql_insert_quiz);
    $stmt_insert_quiz->bind_param('sssssss', $idioma_selecionado, $pergunta, $alternativa_a, $alternativa_b, $alternativa_c, $alternativa_d, $resposta_correta);
    
    if ($stmt_insert_quiz->execute()) {
        $msg = "Pergunta adicionada com sucesso!";
    } else {
        $msg = "Erro ao adicionar a pergunta: " . $stmt_insert_quiz->error;
    }
    $stmt_insert_quiz->close();

    header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado) . "&msg=" . urlencode($msg));
    exit();
}

// Lógica para excluir uma pergunta (via GET)
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id_pergunta = $_GET['id'];

    $sql_delete_quiz = "DELETE FROM quiz_nivelamento WHERE id = ? AND idioma = ?";
    $stmt_delete_quiz = $conn->prepare($sql_delete_quiz);
    $stmt_delete_quiz->bind_param('is', $id_pergunta, $idioma_selecionado);

    if ($stmt_delete_quiz->execute()) {
        $msg = "Pergunta excluída com sucesso!";
    } else {
        $msg = "Erro ao excluir a pergunta: " . $stmt_delete_quiz->error;
    }
    $stmt_delete_quiz->close();

    header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado) . "&msg=" . urlencode($msg));
    exit();
}

// Lógica para buscar as perguntas do quiz de nivelamento
$sql_quiz = "SELECT id, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta FROM quiz_nivelamento WHERE idioma = ?";
$stmt_quiz = $conn->prepare($sql_quiz);
$stmt_quiz->bind_param('s', $idioma_selecionado);
$stmt_quiz->execute();
$quiz_perguntas = $stmt_quiz->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quiz->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Quiz de Nivelamento - <?php echo htmlspecialchars($idioma_selecionado); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 🔹 adicionado: CSS para rolagem suave dentro do modal -->
    <style>
        .scroll-area {
            max-height: 60vh;           /* limita a área visível */
            overflow-y: auto;           /* ativa rolagem vertical */
            padding-right: 6px;         /* espaço pro scrollbar */
            -webkit-overflow-scrolling: touch; /* scroll suave no iOS */
            overscroll-behavior: contain;      /* evita “scroll bleed” pro fundo */
        }
        @media (min-height: 900px) {
            .scroll-area { max-height: 70vh; }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Gerenciar Quiz de Nivelamento para <?php echo htmlspecialchars($idioma_selecionado); ?></h2>
        
        <a href="gerenciar_caminho.php" class="btn btn-secondary mb-4">Voltar para Gerenciar Caminhos</a>
        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addPerguntaModal">
            Adicionar Nova Pergunta
        </button>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="mt-5">
            <h4 class="mb-3">Perguntas Existentes</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pergunta</th>
                        <th>Opção A</th>
                        <th>Opção B</th>
                        <th>Opção C</th>
                        <th>Opção D</th>
                        <th>Resposta Correta</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($quiz_perguntas)): ?>
                        <?php foreach ($quiz_perguntas as $pergunta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pergunta['id']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['pergunta']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['alternativa_a']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['alternativa_b']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['alternativa_c']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['alternativa_d']); ?></td>
                                <td><?php echo htmlspecialchars($pergunta['resposta_correta']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editQuizModal_<?php echo $pergunta['id']; ?>">Editar</button>
                                    
                                    <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmDeleteModal"
                                            data-id="<?php echo htmlspecialchars($pergunta['id']); ?>" 
                                            data-nome="<?php echo htmlspecialchars($pergunta['pergunta']); ?>"
                                            data-action="gerenciar_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma_selecionado); ?>&acao=excluir">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal de Edição (🔹 adicionado: modal-dialog-scrollable + .scroll-area) -->
                            <div class="modal fade" id="editQuizModal_<?php echo $pergunta['id']; ?>" tabindex="-1" aria-labelledby="editQuizModalLabel_<?php echo $pergunta['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable"><!-- 🔹 -->
                                    <div class="modal-content">
                                        <form action="editar_quiz.php" method="POST">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pergunta['id']); ?>">
                                            <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_selecionado); ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editQuizModalLabel_<?php echo $pergunta['id']; ?>">Editar Pergunta #<?php echo $pergunta['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body scroll-area"><!-- 🔹 -->
                                                <div class="mb-3">
                                                    <label for="edit_pergunta_<?php echo $pergunta['id']; ?>" class="form-label">Pergunta</label>
                                                    <textarea class="form-control" id="edit_pergunta_<?php echo $pergunta['id']; ?>" name="pergunta" rows="2" required><?php echo htmlspecialchars($pergunta['pergunta']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_alternativa_a_<?php echo $pergunta['id']; ?>" class="form-label">Opção A</label>
                                                    <input type="text" class="form-control" id="edit_alternativa_a_<?php echo $pergunta['id']; ?>" name="alternativa_a" value="<?php echo htmlspecialchars($pergunta['alternativa_a']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_alternativa_b_<?php echo $pergunta['id']; ?>" class="form-label">Opção B</label>
                                                    <input type="text" class="form-control" id="edit_alternativa_b_<?php echo $pergunta['id']; ?>" name="alternativa_b" value="<?php echo htmlspecialchars($pergunta['alternativa_b']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_alternativa_c_<?php echo $pergunta['id']; ?>" class="form-label">Opção C</label>
                                                    <input type="text" class="form-control" id="edit_alternativa_c_<?php echo $pergunta['id']; ?>" name="alternativa_c" value="<?php echo htmlspecialchars($pergunta['alternativa_c']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_alternativa_d_<?php echo $pergunta['id']; ?>" class="form-label">Opção D</label>
                                                    <input type="text" class="form-control" id="edit_alternativa_d_<?php echo $pergunta['id']; ?>" name="alternativa_d" value="<?php echo htmlspecialchars($pergunta['alternativa_d']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_resposta_correta_<?php echo $pergunta['id']; ?>" class="form-label">Resposta Correta</label>
                                                    <select id="edit_resposta_correta_<?php echo $pergunta['id']; ?>" name="resposta_correta" class="form-select" required>
                                                        <option value="A" <?php echo ($pergunta['resposta_correta'] == 'A') ? 'selected' : ''; ?>>Opção A</option>
                                                        <option value="B" <?php echo ($pergunta['resposta_correta'] == 'B') ? 'selected' : ''; ?>>Opção B</option>
                                                        <option value="C" <?php echo ($pergunta['resposta_correta'] == 'C') ? 'selected' : ''; ?>>Opção C</option>
                                                        <option value="D" <?php echo ($pergunta['resposta_correta'] == 'D') ? 'selected' : ''; ?>>Opção D</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Nenhuma pergunta de quiz encontrada para este idioma.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Adicionar (🔹 adicionado: modal-dialog-scrollable + .scroll-area) -->
    <div class="modal fade" id="addPerguntaModal" tabindex="-1" aria-labelledby="addPerguntaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable"><!-- 🔹 -->
            <div class="modal-content">
                <form action="gerenciar_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma_selecionado); ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPerguntaModalLabel">Adicionar Nova Pergunta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body scroll-area"><!-- 🔹 -->
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_a" class="form-label">Alternativa A</label>
                            <input type="text" class="form-control" id="alternativa_a" name="alternativa_a" required>
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_b" class="form-label">Alternativa B</label>
                            <input type="text" class="form-control" id="alternativa_b" name="alternativa_b" required>
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_c" class="form-label">Alternativa C</label>
                            <input type="text" class="form-control" id="alternativa_c" name="alternativa_c" required>
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_d" class="form-label">Alternativa D</label>
                            <input type="text" class="form-control" id="alternativa_d" name="alternativa_d" required>
                        </div>
                        <div class="mb-3">
                            <label for="resposta_correta" class="form-label">Resposta Correta</label>
                            <select id="resposta_correta" name="resposta_correta" class="form-select" required>
                                <option value="">Selecione a resposta correta</option>
                                <option value="A">Alternativa A</option>
                                <option value="B">Alternativa B</option>
                                <option value="C">Alternativa C</option>
                                <option value="D">Alternativa D</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-success">Salvar Pergunta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmação de Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmDeleteModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="GET" action="">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="idioma" value="<?php echo urlencode($idioma_selecionado); ?>">
                        <input type="hidden" name="id" id="deleteItemId">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        if (confirmDeleteModal) {
            confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const itemId = button.getAttribute('data-id');
                const formAction = button.getAttribute('data-action');

                const modalBody = confirmDeleteModal.querySelector('#confirmDeleteModalBody');
                const modalForm = confirmDeleteModal.querySelector('#deleteForm');
                const hiddenInput = confirmDeleteModal.querySelector('#deleteItemId');

                modalBody.innerHTML = `<p>Tem certeza que deseja excluir a pergunta de ID <strong>${itemId}</strong>?</p>`;
                modalForm.action = formAction;
                hiddenInput.value = itemId;
            });
        }
    });
    </script>
</body>
</html>
