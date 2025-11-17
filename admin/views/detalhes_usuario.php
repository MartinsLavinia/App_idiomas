<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin']) || !isset($_GET['id'])) {
    exit('Acesso negado');
}

$user_id = (int)$_GET['id'];

$database = new Database();
$conn = $database->conn;

// Buscar dados do usuário
$sql_user = "
    SELECT 
        u.*,
        COALESCE(
            (SELECT qr2.nivel_resultado 
             FROM quiz_resultados qr2 
             WHERE qr2.id_usuario = u.id 
             ORDER BY qr2.data_realizacao DESC 
             LIMIT 1), 
            'Não avaliado'
        ) as nivel_atual,
        COALESCE(
            (SELECT qr3.data_realizacao 
             FROM quiz_resultados qr3 
             WHERE qr3.id_usuario = u.id 
             ORDER BY qr3.data_realizacao DESC 
             LIMIT 1), 
            NULL
        ) as data_ultimo_quiz
    FROM usuarios u
    WHERE u.id = ?
";

$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user) {
    echo '<div class="alert alert-danger">Usuário não encontrado.</div>';
    exit;
}

// Buscar progresso nos caminhos
$sql_progress = "
    SELECT 
        ca.idioma,
        ca.nome_caminho,
        ca.nivel,
        pu.progresso,
        pu.data_inicio,
        pu.ultima_atividade
    FROM progresso_usuario pu
    JOIN caminhos_aprendizagem ca ON pu.caminho_id = ca.id
    WHERE pu.id_usuario = ?
    ORDER BY pu.ultima_atividade DESC
";

$stmt_progress = $conn->prepare($sql_progress);
$stmt_progress->bind_param("i", $user_id);
$stmt_progress->execute();
$progress = $stmt_progress->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_progress->close();

// Buscar histórico de quizzes
$sql_quizzes = "
    SELECT 
        qn.idioma,
        qr.nivel_resultado,
        qr.pontuacao,
        qr.data_realizacao
    FROM quiz_resultados qr
    JOIN quiz_nivelamento qn ON qr.id_quiz = qn.id
    WHERE qr.id_usuario = ?
    ORDER BY qr.data_realizacao DESC
    LIMIT 10
";

$stmt_quizzes = $conn->prepare($sql_quizzes);
$stmt_quizzes->bind_param("i", $user_id);
$stmt_quizzes->execute();
$quizzes = $stmt_quizzes->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quizzes->close();

// Buscar exercícios recentes
$sql_exercises = "
    SELECT 
        e.pergunta,
        ca.idioma,
        ca.nivel,
        re.pontuacao,
        re.data_resposta
    FROM respostas_exercicios re
    JOIN exercicios e ON re.id_exercicio = e.id
    JOIN caminhos_aprendizagem ca ON e.caminho_id = ca.id
    WHERE re.id_usuario = ?
    ORDER BY re.data_resposta DESC
    LIMIT 10
";

$stmt_exercises = $conn->prepare($sql_exercises);
$stmt_exercises->bind_param("i", $user_id);
$stmt_exercises->execute();
$exercises = $stmt_exercises->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exercises->close();

$database->closeConnection();
?>

<!-- Header do Usuário -->
<div class="bg-gradient-warning text-dark p-4 mb-0">
    <div class="row align-items-center">
        <div class="col-auto">
            <?php if (!empty($user['imagem_perfil']) && file_exists(__DIR__ . '/../../uploads/perfil/' . $user['imagem_perfil'])): ?>
                <img src="../../uploads/perfil/<?php echo htmlspecialchars($user['imagem_perfil']); ?>" 
                     alt="Foto do usuário" 
                     class="avatar-lg rounded-circle object-fit-cover border border-2 border-dark border-opacity-25">
            <?php else: ?>
                <div class="avatar-lg bg-dark bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-user" style="color: var(--amarelo-detalhe); font-size: 2rem;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col">
            <h4 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($user['nome']); ?></h4>
            <p class="mb-2 text-dark opacity-75"><?php echo htmlspecialchars($user['email']); ?></p>
            <div class="d-flex gap-2">
                <span class="badge bg-<?php echo $user['ativo'] ? 'success' : 'danger'; ?> text-white">
                    <i class="fas fa-<?php echo $user['ativo'] ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                    <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                </span>
                <span class="badge bg-dark text-white">
                    <i class="fas fa-graduation-cap me-1"></i>
                    <?php echo htmlspecialchars($user['nivel_atual']); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="p-4">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10 h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-route fa-2x"></i>
                    </div>
                    <h5 class="card-title text-warning mb-1"><?php echo count($progress); ?></h5>
                    <p class="card-text small text-muted mb-0">Caminhos Iniciados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10 h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-quiz fa-2x"></i>
                    </div>
                    <h5 class="card-title text-success mb-1"><?php echo count($quizzes); ?></h5>
                    <p class="card-text small text-muted mb-0">Quizzes Realizados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info bg-opacity-10 h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-dumbbell fa-2x"></i>
                    </div>
                    <h5 class="card-title text-info mb-1"><?php echo count($exercises); ?></h5>
                    <p class="card-text small text-muted mb-0">Exercícios Feitos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-secondary bg-opacity-10 h-100">
                <div class="card-body text-center">
                    <div class="text-secondary mb-2">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <?php if (!empty($progress)): ?>
                        <?php $avg_progress = array_sum(array_column($progress, 'progresso')) / count($progress); ?>
                        <h5 class="card-title text-secondary mb-1"><?php echo round($avg_progress); ?>%</h5>
                    <?php else: ?>
                        <h5 class="card-title text-muted mb-1">0%</h5>
                    <?php endif; ?>
                    <p class="card-text small text-muted mb-0">Progresso Médio</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações Detalhadas -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 info-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle text-warning me-2"></i>
                        Informações Pessoais
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-calendar-plus me-2"></i>Data de Registro</span>
                                <span class="fw-medium"><?php echo date('d/m/Y H:i', strtotime($user['data_registro'])); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-clock me-2"></i>Último Login</span>
                                <span class="fw-medium">
                                    <?php if ($user['ultimo_login']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($user['ultimo_login'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca fez login</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($user['data_ultimo_quiz']): ?>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted"><i class="fas fa-graduation-cap me-2"></i>Última Avaliação</span>
                                <span class="fw-medium"><?php echo date('d/m/Y', strtotime($user['data_ultimo_quiz'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 info-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar text-warning me-2"></i>
                        Resumo de Atividades
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($progress)): ?>
                        <?php $avg_progress = array_sum(array_column($progress, 'progresso')) / count($progress); ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Progresso Geral</span>
                                <span class="fw-bold text-warning"><?php echo round($avg_progress); ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-gradient-warning" style="width: <?php echo round($avg_progress); ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-warning"><?php echo count($progress); ?></div>
                                <small class="text-muted">Caminhos</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-success"><?php echo count($quizzes); ?></div>
                                <small class="text-muted">Quizzes</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-warning"><?php echo count($exercises); ?></div>
                                <small class="text-muted">Exercícios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seções de Detalhes -->
    <div class="row g-4">
        <!-- Progresso nos Caminhos -->
        <div class="col-lg-6">
            <div class="card border-0 info-card h-100">
                <div class="card-header bg-transparent border-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-route text-warning me-2"></i>
                        Progresso nos Caminhos
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($progress)): ?>
                        <div class="progress-list">
                            <?php foreach (array_slice($progress, 0, 5) as $p): ?>
                            <div class="progress-item mb-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-truncate" title="<?php echo htmlspecialchars($p['nome_caminho']); ?>">
                                            <?php echo htmlspecialchars(substr($p['nome_caminho'], 0, 25)) . (strlen($p['nome_caminho']) > 25 ? '...' : ''); ?>
                                        </h6>
                                        <div class="d-flex gap-2 mb-2">
                                            <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($p['idioma']); ?></span>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo htmlspecialchars($p['nivel']); ?></span>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-primary"><?php echo round($p['progresso']); ?>%</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-warning" style="width: <?php echo $p['progresso']; ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Última atividade: <?php echo date('d/m/Y', strtotime($p['ultima_atividade'])); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($progress) > 5): ?>
                            <div class="text-center">
                                <small class="text-muted">E mais <?php echo count($progress) - 5; ?> caminhos...</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-route fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Nenhum caminho iniciado ainda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Histórico de Quizzes -->
        <div class="col-lg-6">
            <div class="card border-0 info-card h-100">
                <div class="card-header bg-transparent border-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history text-warning me-2"></i>
                        Histórico de Quizzes
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($quizzes)): ?>
                        <div class="quiz-list">
                            <?php foreach (array_slice($quizzes, 0, 5) as $q): ?>
                            <div class="quiz-item d-flex justify-content-between align-items-center p-3 mb-2 bg-light rounded">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($q['idioma']); ?></span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo htmlspecialchars($q['nivel_resultado']); ?></span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($q['data_realizacao'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $q['pontuacao'] >= 80 ? 'success' : ($q['pontuacao'] >= 60 ? 'warning' : 'danger'); ?> fs-6">
                                        <?php echo $q['pontuacao']; ?>%
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($quizzes) > 5): ?>
                            <div class="text-center">
                                <small class="text-muted">E mais <?php echo count($quizzes) - 5; ?> quizzes...</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-quiz fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Nenhum quiz realizado ainda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($exercises)): ?>
    <!-- Exercícios Recentes -->
    <div class="mt-4">
        <div class="card border-0 info-card">
            <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                    <i class="fas fa-dumbbell text-warning me-2"></i>
                    Exercícios Recentes
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach (array_slice($exercises, 0, 6) as $e): ?>
                    <div class="col-md-6">
                        <div class="exercise-item p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-truncate" title="<?php echo htmlspecialchars($e['pergunta']); ?>">
                                        <?php echo htmlspecialchars(substr($e['pergunta'], 0, 30)) . (strlen($e['pergunta']) > 30 ? '...' : ''); ?>
                                    </h6>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge bg-warning bg-opacity-10 text-warning"><?php echo htmlspecialchars($e['idioma']); ?></span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo htmlspecialchars($e['nivel']); ?></span>
                                    </div>
                                </div>
                                <span class="badge bg-<?php echo $e['pontuacao'] >= 80 ? 'success' : ($e['pontuacao'] >= 60 ? 'warning' : 'danger'); ?> fs-6">
                                    <?php echo $e['pontuacao']; ?>%
                                </span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y', strtotime($e['data_resposta'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($exercises) > 6): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">E mais <?php echo count($exercises) - 6; ?> exercícios...</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>