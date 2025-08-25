<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Lógica para buscar os idiomas únicos do banco de dados das tabelas caminhos_aprendizagem e quiz_nivelamento
// Isso garante que idiomas com apenas um quiz, mas sem caminhos, também sejam exibidos
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$stmt_idiomas = $conn->prepare($sql_idiomas);
$stmt_idiomas->execute();
$idiomas_db = $stmt_idiomas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas->close();

// Definição dos níveis de A1 a C2
$niveis_db = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

// Lógica para buscar os caminhos com base na pesquisa
$sql_caminhos = "SELECT id, idioma, nome_caminho, nivel FROM caminhos_aprendizagem WHERE 1=1";
$params = [];
$types = '';

if (isset($_GET['idioma']) && !empty($_GET['idioma'])) {
    $sql_caminhos .= " AND idioma = ?";
    $params[] = $_GET['idioma'];
    $types .= 's';
}

if (isset($_GET['nivel']) && !empty($_GET['nivel'])) {
    $sql_caminhos .= " AND nivel = ?";
    $params[] = $_GET['nivel'];
    $types .= 's';
}

$sql_caminhos .= " ORDER BY idioma, nivel, nome_caminho";

$stmt_caminhos = $conn->prepare($sql_caminhos);
if (!empty($params)) {
    $stmt_caminhos->bind_param($types, ...$params);
}

$stmt_caminhos->execute();
$caminhos = $stmt_caminhos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_caminhos->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Caminhos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Gerenciar Caminhos de Aprendizagem</h2>

        <div class="d-flex justify-content-start mb-3">
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCaminhoModal">
                Adicionar Caminho
            </button>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#adicionarIdiomaCompletoModal">
                Adicionar Novo Idioma com Quiz
            </button>
            <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal">
                Gerenciar Idiomas
            </button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                Pesquisar Caminhos
            </div>
            <div class="card-body">
                <form action="" method="GET">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-auto">
                            <label for="idioma_busca" class="col-form-label">Idioma:</label>
                            <select id="idioma_busca" name="idioma" class="form-select">
                                <option value="">Todos os Idiomas</option>
                                <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>" <?php echo (isset($_GET['idioma']) && $_GET['idioma'] === $idioma['idioma']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($idioma['idioma']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label for="nivel_busca" class="col-form-label">Nível:</label>
                            <select id="nivel_busca" name="nivel" class="form-select">
                                <option value="">Todos os Níveis</option>
                                <?php foreach ($niveis_db as $nivel): ?>
                                    <option value="<?php echo htmlspecialchars($nivel); ?>" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === $nivel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nivel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Pesquisar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Idioma</th>
                    <th>Caminho</th>
                    <th>Nível</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($caminhos)): ?>
                    <?php foreach ($caminhos as $caminho): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($caminho['id']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['idioma']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nome_caminho']); ?></td>
                            <td><?php echo htmlspecialchars($caminho['nivel']); ?></td>
                            <td>
                                <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-info">Ver Exercícios</a>
                                <a href="editar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="eliminar_caminho.php?id=<?php echo htmlspecialchars($caminho['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este caminho?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum caminho de aprendizado encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="addCaminhoModal" tabindex="-1" aria-labelledby="addCaminhoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="adicionar_caminho.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCaminhoModalLabel">Adicionar Novo Caminho</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="idioma_novo" class="form-label">Idioma</label>
                            <select id="idioma_novo" name="idioma" class="form-select" required>
                                <option value="">Selecione o Idioma</option>
                                <?php foreach ($idiomas_db as $idioma): ?>
                                    <option value="<?php echo htmlspecialchars($idioma['idioma']); ?>"><?php echo htmlspecialchars($idioma['idioma']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nivel_novo" class="form-label">Nível</label>
                            <select id="nivel_novo" name="nivel" class="form-select" required>
                                <option value="">Selecione o Nível</option>
                                <?php foreach ($niveis_db as $nivel): ?>
                                    <option value="<?php echo htmlspecialchars($nivel); ?>"><?php echo htmlspecialchars($nivel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                            <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-success">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adicionarIdiomaCompletoModal" tabindex="-1" aria-labelledby="adicionarIdiomaCompletoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form action="adicionar_idioma_completo.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adicionarIdiomaCompletoModalLabel">Adicionar Novo Idioma com Quiz</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="idioma_novo_completo" class="form-label">Nome do Idioma</label>
                            <input type="text" class="form-control" id="idioma_novo_completo" name="idioma" placeholder="Ex: Espanhol" required>
                        </div>

                        <hr>
                        <h5>Perguntas do Quiz de Nivelamento (20 perguntas)</h5>
                        <p class="text-muted">A resposta correta para cada pergunta deve ser "A", "B" ou "C".</p>

                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    Pergunta #<?php echo $i; ?>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="pergunta_<?php echo $i; ?>" class="form-label">Pergunta</label>
                                        <textarea class="form-control" id="pergunta_<?php echo $i; ?>" name="pergunta_<?php echo $i; ?>" rows="2" required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_a_<?php echo $i; ?>" class="form-label">Opção A</label>
                                            <input type="text" class="form-control" id="opcao_a_<?php echo $i; ?>" name="opcao_a_<?php echo $i; ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_b_<?php echo $i; ?>" class="form-label">Opção B</label>
                                            <input type="text" class="form-control" id="opcao_b_<?php echo $i; ?>" name="opcao_b_<?php echo $i; ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="opcao_c_<?php echo $i; ?>" class="form-label">Opção C</label>
                                            <input type="text" class="form-control" id="opcao_c_<?php echo $i; ?>" name="opcao_c_<?php echo $i; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="resposta_correta_<?php echo $i; ?>" class="form-label">Resposta Correta</label>
                                        <select id="resposta_correta_<?php echo $i; ?>" name="resposta_correta_<?php echo $i; ?>" class="form-select" required>
                                            <option value="">Selecione a resposta correta</option>
                                            <option value="A">Opção A</option>
                                            <option value="B">Opção B</option>
                                            <option value="C">Opção C</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-success">Salvar Idioma e Quiz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="gerenciarIdiomasModal" tabindex="-1" aria-labelledby="gerenciarIdiomasModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gerenciarIdiomasModalLabel">Gerenciar Idiomas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        Use o botão "Adicionar Novo Idioma com Quiz" para criar um novo idioma do zero.
                    </p>
                    <h5>Idiomas Existentes</h5>
                    <ul class="list-group">
                        <?php if (!empty($idiomas_db)): ?>
                            <?php foreach ($idiomas_db as $idioma): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($idioma['idioma']); ?></span>
                                    <div>
                                        <a href="gerenciador_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma['idioma']); ?>" class="btn btn-info btn-sm me-2">Gerenciar Quiz</a>
                                        <a href="excluir_idioma.php?idioma=<?php echo urlencode($idioma['idioma']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('ATENÇÃO: Isso excluirá o idioma \'<?php echo htmlspecialchars($idioma['idioma']); ?>\' e TODOS os seus caminhos, exercícios e quizzes. Tem certeza?');">Excluir</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">Nenhum idioma encontrado.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>