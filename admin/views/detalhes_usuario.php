<?php
include_once __DIR__ . '/../../conexao.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID do usuário não fornecido</div>';
    exit;
}

$database = new Database();
$conn = $database->conn;
$user_id = $_GET['id'];

// Buscar dados do usuário
$sql_user = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo '<div class="alert alert-danger">Usuário não encontrado</div>';
    exit;
}

// Buscar progresso do usuário
$sql_progress = "SELECT pu.*, ca.nome_caminho, ca.idioma, ca.nivel 
                FROM progresso_usuario pu 
                LEFT JOIN caminhos_aprendizagem ca ON pu.caminho_id = ca.id 
                WHERE pu.id_usuario = ? 
                ORDER BY pu.ultima_atividade DESC";
$stmt = $conn->prepare($sql_progress);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar quizzes realizados
$sql_quizzes = "SELECT qr.*, qn.idioma 
               FROM quiz_resultados qr 
               LEFT JOIN quiz_nivelamento qn ON qr.id_quiz = qn.id 
               WHERE qr.id_usuario = ? 
               ORDER BY qr.data_realizacao DESC";
$stmt = $conn->prepare($sql_quizzes);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$database->closeConnection();
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card info-card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3 bg-gradient-primary d-flex align-items-center justify-content-center rounded-circle">
                        <span class="text-white fs-2 fw-bold"><?= strtoupper(substr($user['nome'], 0, 1)) ?></span>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($user['nome']) ?></h4>
                    <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6 class="mb-0"><?= count($progress) ?></h6>
                            <small class="text-muted">Caminhos</small>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0"><?= count($quizzes) ?></h6>
                            <small class="text-muted">Quizzes</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card info-card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Data de Registro</small>
                        <div><?= date('d/m/Y H:i', strtotime($user['data_registro'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Último Login</small>
                        <div><?= $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'Nunca' ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Status</small>
                        <div>
                            <span class="badge bg-<?= $user['ativo'] ? 'success' : 'danger' ?>">
                                <?= $user['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-road me-2"></i>Progresso nos Caminhos</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($progress)): ?>
                        <?php foreach ($progress as $prog): ?>
                        <div class="progress-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?= htmlspecialchars($prog['nome_caminho'] ?? 'Caminho Desconhecido') ?></h6>
                                <span class="badge bg-primary"><?= htmlspecialchars($prog['idioma'] ?? '') ?> - <?= htmlspecialchars($prog['nivel'] ?? '') ?></span>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar" style="width: <?= $prog['progresso'] ?>%">
                                    <?= round($prog['progresso']) ?>%
                                </div>
                            </div>
                            <small class="text-muted">
                                Iniciado em: <?= date('d/m/Y', strtotime($prog['data_inicio'])) ?> | 
                                Última atividade: <?= date('d/m/Y', strtotime($prog['ultima_atividade'])) ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-road fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum progresso registrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-quiz me-2"></i>Histórico de Quizzes</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($quizzes)): ?>
                        <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($quiz['idioma'] ?? 'Idioma Desconhecido') ?></h6>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($quiz['data_realizacao'])) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success fs-6"><?= htmlspecialchars($quiz['nivel_resultado']) ?></span>
                                    <div class="mt-1">
                                        <small class="text-muted"><?= $quiz['pontuacao_total'] ?>% de acerto</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-quiz fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum quiz realizado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>