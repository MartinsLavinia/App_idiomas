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

// Estatísticas gerais de usuários
$sql_total_usuarios = "SELECT COUNT(*) as total FROM usuarios";
$result_total = $conn->query($sql_total_usuarios);
$total_usuarios = $result_total->fetch_assoc()['total'];

// Usuários ativos (que fizeram login nos últimos 30 dias)
$sql_usuarios_ativos = "SELECT COUNT(*) as ativos FROM usuarios WHERE ultimo_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_ativos = $conn->query($sql_usuarios_ativos);
$usuarios_ativos = $result_ativos->fetch_assoc()['ativos'];

// Usuários por nível (baseado no último quiz realizado)
$sql_usuarios_por_nivel = "
    SELECT
        COALESCE(qr.nivel_resultado, 'Sem nível') as nivel,
        COUNT(DISTINCT u.id) as quantidade
    FROM usuarios u
    LEFT JOIN (
        SELECT
            id_usuario,
            nivel_resultado,
            ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY data_realizacao DESC) as rn
        FROM quiz_resultados
    ) qr ON u.id = qr.id_usuario AND qr.rn = 1
    GROUP BY nivel
    ORDER BY
        CASE
            WHEN nivel = 'A1' THEN 1
            WHEN nivel = 'A2' THEN 2
            WHEN nivel = 'B1' THEN 3
            WHEN nivel = 'B2' THEN 4
            WHEN nivel = 'C1' THEN 5
            WHEN nivel = 'C2' THEN 6
            ELSE 7
        END
";
$result_niveis = $conn->query($sql_usuarios_por_nivel);
$usuarios_por_nivel = [];
while ($row = $result_niveis->fetch_assoc()) {
    $usuarios_por_nivel[] = $row;
}

// Idiomas mais populares (baseado nos quizzes realizados)
$sql_idiomas_populares = "
    SELECT
        qn.idioma,
        COUNT(DISTINCT qr.id_usuario) as usuarios_unicos,
        COUNT(qr.id) as total_quizzes
    FROM quiz_resultados qr
    JOIN quiz_nivelamento qn ON qr.id_quiz = qn.id
    GROUP BY qn.idioma
    ORDER BY usuarios_unicos DESC
    LIMIT 10
";
$result_idiomas = $conn->query($sql_idiomas_populares);
$idiomas_populares = [];
while ($row = $result_idiomas->fetch_assoc()) {
    $idiomas_populares[] = $row;
}

// Progresso dos usuários nos caminhos (CORRIGIDO)
$sql_progresso_caminhos = "
    SELECT
        ca.idioma,
        ca.nivel,
        COUNT(DISTINCT pu.id_usuario) as usuarios_iniciaram,
        AVG(pu.progresso) as progresso_medio
    FROM progresso_usuario pu
    JOIN caminhos_aprendizagem ca ON pu.caminho_id = ca.id
    GROUP BY ca.idioma, ca.nivel
    ORDER BY ca.idioma, ca.nivel
";
$result_progresso = $conn->query($sql_progresso_caminhos);
$progresso_caminhos = [];
while ($row = $result_progresso->fetch_assoc()) {
    $progresso_caminhos[] = $row;
}

// Usuários registrados por mês (últimos 12 meses)
$sql_registros_mensais = "
    SELECT
        DATE_FORMAT(data_registro, '%Y-%m') as mes,
        COUNT(*) as novos_usuarios
    FROM usuarios
    WHERE data_registro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(data_registro, '%Y-%m')
    ORDER BY mes DESC
";
$result_mensais = $conn->query($sql_registros_mensais);
$registros_mensais = [];
while ($row = $result_mensais->fetch_assoc()) {
    $registros_mensais[] = $row;
}

// Exercícios mais realizados
$sql_exercicios_populares = "
    SELECT
        e.pergunta,
        ca.idioma,
        ca.nivel,
        COUNT(re.id) as total_realizacoes,
        AVG(re.pontuacao) as pontuacao_media
    FROM respostas_exercicios re
    JOIN exercicios e ON re.id_exercicio = e.id
    JOIN caminhos_aprendizagem ca ON e.caminho_id = ca.id
    GROUP BY e.id, e.pergunta, ca.idioma, ca.nivel
    ORDER BY total_realizacoes DESC
    LIMIT 15
";
$result_exercicios = $conn->query($sql_exercicios_populares);
$exercicios_populares = [];
while ($row = $result_exercicios->fetch_assoc()) {
    $exercicios_populares[] = $row;
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas de Usuários - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">📊 Estatísticas de Usuários</h1>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">← Voltar ao Gerenciamento</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo number_format($total_usuarios); ?></h3>
                    <p>Total de Usuários</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo number_format($usuarios_ativos); ?></h3>
                    <p>Usuários Ativos (30 dias)</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo count($idiomas_populares); ?></h3>
                    <p>Idiomas Disponíveis</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo number_format(($usuarios_ativos / max($total_usuarios, 1)) * 100, 1); ?>%</h3>
                    <p>Taxa de Atividade</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="table-container">
                    <h4 class="mb-3">👥 Distribuição por Níveis</h4>
                    <div class="chart-container">
                        <canvas id="niveisChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="table-container">
                    <h4 class="mb-3">🌍 Idiomas Mais Populares</h4>
                    <div class="chart-container">
                        <canvas id="idiomasChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <h4 class="mb-3">📈 Novos Registros (Últimos 12 Meses)</h4>
                    <div class="chart-container">
                        <canvas id="registrosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="table-container">
                    <h4 class="mb-3">🎯 Progresso nos Caminhos</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Idioma</th>
                                    <th>Nível</th>
                                    <th>Usuários</th>
                                    <th>Progresso Médio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progresso_caminhos as $progresso): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($progresso['idioma']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($progresso['nivel']); ?></span></td>
                                    <td><?php echo number_format($progresso['usuarios_iniciaram']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                style="width: <?php echo round($progresso['progresso_medio']); ?>%"
                                                aria-valuenow="<?php echo round($progresso['progresso_medio']); ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($progresso['progresso_medio']); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="table-container">
                    <h4 class="mb-3">🏆 Exercícios Mais Realizados</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Exercício</th>
                                    <th>Idioma/Nível</th>
                                    <th>Realizações</th>
                                    <th>Pontuação Média</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exercicios_populares as $exercicio): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($exercicio['titulo'], 0, 30)) . (strlen($exercicio['titulo']) > 30 ? '...' : ''); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($exercicio['idioma']); ?> - 
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($exercicio['nivel']); ?></span>
                                        </small>
                                    </td>
                                    <td><?php echo number_format($exercicio['total_realizacoes']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $exercicio['pontuacao_media'] >= 80 ? 'success' : ($exercicio['pontuacao_media'] >= 60 ? 'warning' : 'danger'); ?>">
                                            <?php echo round($exercicio['pontuacao_media'], 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Usuários por Nível
        const niveisCtx = document.getElementById('niveisChart').getContext('2d');
        const niveisChart = new Chart(niveisCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($n) { return '"' . $n['nivel'] . '"'; }, $usuarios_por_nivel)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($n) { return $n['quantidade']; }, $usuarios_por_nivel)); ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Idiomas Populares
        const idiomasCtx = document.getElementById('idiomasChart').getContext('2d');
        const idiomasChart = new Chart(idiomasCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($i) { return '"' . $i['idioma'] . '"'; }, $idiomas_populares)); ?>],
                datasets: [{
                    label: 'Usuários Únicos',
                    data: [<?php echo implode(',', array_map(function($i) { return $i['usuarios_unicos']; }, $idiomas_populares)); ?>],
                    backgroundColor: '#36A2EB'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Registros Mensais
        const registrosCtx = document.getElementById('registrosChart').getContext('2d');
        const registrosChart = new Chart(registrosCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($r) { return '"' . $r['mes'] . '"'; }, array_reverse($registros_mensais))); ?>],
                datasets: [{
                    label: 'Novos Usuários',
                    data: [<?php echo implode(',', array_map(function($r) { return $r['novos_usuarios']; }, array_reverse($registros_mensais))); ?>],
                    borderColor: '#4BC0C0',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
