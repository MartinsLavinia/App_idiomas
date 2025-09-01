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
        COALESCE(qr.nivel_resultado, 'Não avaliado') as nivel_atual,
        qr.data_realizacao as data_ultimo_quiz
    FROM usuarios u
    LEFT JOIN (
        SELECT 
            id_usuario,
            nivel_resultado,
            data_realizacao,
            ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY data_realizacao DESC) as rn
        FROM quiz_resultados
    ) qr ON u.id = qr.id_usuario AND qr.rn = 1
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
    JOIN caminhos_aprendizagem ca ON pu.id_caminho = ca.id
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
        e.titulo,
        ca.idioma,
        ca.nivel,
        re.pontuacao,
        re.data_resposta
    FROM respostas_exercicios re
    JOIN exercicios e ON re.id_exercicio = e.id
    JOIN caminhos_aprendizagem ca ON e.id_caminho = ca.id
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

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-user"></i> Informações Pessoais</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Nome:</strong></td>
                <td><?php echo htmlspecialchars($user['nome']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <td><strong>Data de Registro:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($user['data_registro'])); ?></td>
            </tr>
            <tr>
                <td><strong>Último Login:</strong></td>
                <td>
                    <?php if ($user['ultimo_login']): ?>
                        <?php echo date('d/m/Y H:i', strtotime($user['ultimo_login'])); ?>
                    <?php else: ?>
                        <span class="text-muted">Nunca fez login</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge bg-<?php echo $user['ativo'] ? 'success' : 'danger'; ?>">
                        <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Nível Atual:</strong></td>
                <td>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['nivel_atual']); ?></span>
                    <?php if ($user['data_ultimo_quiz']): ?>
                        <br><small class="text-muted">Avaliado em <?php echo date('d/m/Y', strtotime($user['data_ultimo_quiz'])); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6><i class="fas fa-chart-line"></i> Estatísticas de Aprendizado</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Caminhos Iniciados:</strong></td>
                <td><?php echo count($progress); ?></td>
            </tr>
            <tr>
                <td><strong>Quizzes Realizados:</strong></td>
                <td><?php echo count($quizzes); ?></td>
            </tr>
            <tr>
                <td><strong>Exercícios Feitos:</strong></td>
                <td><?php echo count($exercises); ?></td>
            </tr>
            <tr>
                <td><strong>Progresso Médio:</strong></td>
                <td>
                    <?php if (!empty($progress)): ?>
                        <?php $avg_progress = array_sum(array_column($progress, 'progresso')) / count($progress); ?>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" style="width: <?php echo round($avg_progress); ?>%">
                                <?php echo round($avg_progress); ?>%
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">Nenhum progresso</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-route"></i> Progresso nos Caminhos</h6>
        <?php if (!empty($progress)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Caminho</th>
                            <th>Idioma/Nível</th>
                            <th>Progresso</th>
                            <th>Última Atividade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progress as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($p['nome_caminho'], 0, 20)) . (strlen($p['nome_caminho']) > 20 ? '...' : ''); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($p['idioma']); ?></small><br>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($p['nivel']); ?></span>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo $p['progresso']; ?>%">
                                        <?php echo round($p['progresso']); ?>%
                                    </div>
                                </div>
                            </td>
                            <td><small><?php echo date('d/m/Y', strtotime($p['ultima_atividade'])); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nenhum caminho iniciado.</p>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h6><i class="fas fa-history"></i> Histórico de Quizzes</h6>
        <?php if (!empty($quizzes)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Idioma</th>
                            <th>Nível</th>
                            <th>Pontuação</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['idioma']); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($q['nivel_resultado']); ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $q['pontuacao'] >= 80 ? 'success' : ($q['pontuacao'] >= 60 ? 'warning' : 'danger'); ?>">
                                    <?php echo $q['pontuacao']; ?>%
                                </span>
                            </td>
                            <td><small><?php echo date('d/m/Y', strtotime($q['data_realizacao'])); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nenhum quiz realizado.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($exercises)): ?>
<hr>
<h6><i class="fas fa-dumbbell"></i> Exercícios Recentes</h6>
<div class="table-responsive">
    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>Exercício</th>
                <th>Idioma/Nível</th>
                <th>Pontuação</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exercises as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars(substr($e['titulo'], 0, 30)) . (strlen($e['titulo']) > 30 ? '...' : ''); ?></td>
                <td>
                    <small><?php echo htmlspecialchars($e['idioma']); ?></small><br>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($e['nivel']); ?></span>
                </td>
                <td>
                    <span class="badge bg-<?php echo $e['pontuacao'] >= 80 ? 'success' : ($e['pontuacao'] >= 60 ? 'warning' : 'danger'); ?>">
                        <?php echo $e['pontuacao']; ?>%
                    </span>
                </td>
                <td><small><?php echo date('d/m/Y', strtotime($e['data_resposta'])); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>