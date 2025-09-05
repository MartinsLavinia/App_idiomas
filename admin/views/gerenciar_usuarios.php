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

// Filtros de pesquisa
$filtro_nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$filtro_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';

// Query base para buscar usuários
$sql_usuarios = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.data_registro,
        u.ultimo_login,
        u.ativo,
        COALESCE(qr.nivel_resultado, 'Não avaliado') as nivel_atual,
        COUNT(DISTINCT pu.caminho_id) as caminhos_iniciados,
        AVG(pu.progresso) as progresso_medio
    FROM usuarios u
    LEFT JOIN (
        SELECT 
            id_usuario,
            nivel_resultado,
            ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY data_realizacao DESC) as rn
        FROM quiz_resultados
    ) qr ON u.id = qr.id_usuario AND qr.rn = 1
    LEFT JOIN progresso_usuario pu ON u.id = pu.id_usuario
    WHERE 1=1
";

$params = [];
$types = '';

if (!empty($filtro_nome)) {
    $sql_usuarios .= " AND u.nome LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= 's';
}

if (!empty($filtro_email)) {
    $sql_usuarios .= " AND u.email LIKE ?";
    $params[] = "%$filtro_email%";
    $types .= 's';
}

if ($filtro_status !== '') {
    $sql_usuarios .= " AND u.ativo = ?";
    $params[] = $filtro_status;
    $types .= 'i';
}

if (!empty($filtro_nivel)) {
    if ($filtro_nivel === 'Não avaliado') {
        $sql_usuarios .= " AND qr.nivel_resultado IS NULL";
    } else {
        $sql_usuarios .= " AND qr.nivel_resultado = ?";
        $params[] = $filtro_nivel;
        $types .= 's';
    }
}

$sql_usuarios .= " GROUP BY u.id, u.nome, u.email, u.data_registro, u.ultimo_login, u.ativo, qr.nivel_resultado";
$sql_usuarios .= " ORDER BY u.data_registro DESC";

$stmt_usuarios = $conn->prepare($sql_usuarios);
if (!empty($params)) {
    $stmt_usuarios->bind_param($types, ...$params);
}

$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_usuarios->close();

// Estatísticas rápidas
$sql_stats = "
    SELECT 
        COUNT(*) as total_usuarios,
        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as usuarios_ativos,
        SUM(CASE WHEN ultimo_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ativos_semana,
        SUM(CASE WHEN data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as novos_mes
    FROM usuarios
";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">👥 Gerenciar Usuários</h1>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_usuarios']); ?></h3>
                        <p class="mb-0">Total de Usuários</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['usuarios_ativos']); ?></h3>
                        <p class="mb-0">Contas Ativas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['ativos_semana']); ?></h3>
                        <p class="mb-0">Ativos esta Semana</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['novos_mes']); ?></h3>
                        <p class="mb-0">Novos este Mês</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros de Pesquisa -->
        <div class="table-container mb-4">
            <div class="card-header">
                <h5 class="mb-0">🔍 Filtros de Pesquisa</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Buscar por nome">
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="Buscar por email">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $filtro_status === '1' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="0" <?php echo $filtro_status === '0' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="nivel" class="form-label">Nível</label>
                            <select class="form-select" id="nivel" name="nivel">
                                <option value="">Todos</option>
                                <option value="Não avaliado" <?php echo $filtro_nivel === 'Não avaliado' ? 'selected' : ''; ?>>Não avaliado</option>
                                <option value="A1" <?php echo $filtro_nivel === 'A1' ? 'selected' : ''; ?>>A1</option>
                                <option value="A2" <?php echo $filtro_nivel === 'A2' ? 'selected' : ''; ?>>A2</option>
                                <option value="B1" <?php echo $filtro_nivel === 'B1' ? 'selected' : ''; ?>>B1</option>
                                <option value="B2" <?php echo $filtro_nivel === 'B2' ? 'selected' : ''; ?>>B2</option>
                                <option value="C1" <?php echo $filtro_nivel === 'C1' ? 'selected' : ''; ?>>C1</option>
                                <option value="C2" <?php echo $filtro_nivel === 'C2' ? 'selected' : ''; ?>>C2</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="table-container">
            <div class="card-header">
                <h5 class="mb-0">Lista de Usuários (<?php echo count($usuarios); ?> encontrados)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Nível Atual</th>
                                <th>Progresso</th>
                                <th>Registro</th>
                                <th>Último Login</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                                <br><small class="text-muted">ID: <?php echo $usuario['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $usuario['nivel_atual'] === 'Não avaliado' ? 'secondary' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($usuario['nivel_atual']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['caminhos_iniciados'] > 0): ?>
                                            <small><?php echo $usuario['caminhos_iniciados']; ?> caminhos</small><br>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" style="width: <?php echo round($usuario['progresso_medio'] ?? 0); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo round($usuario['progresso_medio'] ?? 0); ?>%</small>
                                        <?php else: ?>
                                            <small class="text-muted">Nenhum progresso</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($usuario['data_registro'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_login']): ?>
                                            <small><?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Nunca</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-badge bg-<?php echo $usuario['ativo'] ? 'success' : 'danger'; ?>">
                                            <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#userDetailsModal"
                                                    onclick="loadUserDetails(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-<?php echo $usuario['ativo'] ? 'warning' : 'success'; ?>"
                                                    onclick="toggleUserStatus(<?php echo $usuario['id']; ?>, <?php echo $usuario['ativo'] ? 0 : 1; ?>)">
                                                <i class="fas fa-<?php echo $usuario['ativo'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Nenhum usuário encontrado com os filtros aplicados.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Usuário -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">Detalhes do Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadUserDetails(userId) {
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;
            
            fetch(`detalhes_usuario.php?id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('userDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Erro ao carregar detalhes do usuário.
                        </div>
                    `;
                });
        }

        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'ativar' : 'desativar';
            if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
                fetch('toggle_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao alterar status do usuário: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação.');
                });
            }
        }
    </script>
</body>
</html>