<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$caminho_id = $_GET['caminho_id'] ?? null;
if (!$caminho_id) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Get path info
$sql_caminho = "SELECT nome_caminho, idioma, nivel FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

if (!$caminho) {
    header("Location: gerenciar_caminho.php");
    exit();
}

// Get special exercises for this path from exercicios table
$sql_especiais = "SELECT * FROM exercicios WHERE categoria = 'especial' AND bloco_id IN (SELECT id FROM blocos WHERE caminho_id = ?) ORDER BY id";
$stmt_especiais = $conn->prepare($sql_especiais);
$stmt_especiais->bind_param("i", $caminho_id);
$stmt_especiais->execute();
$exercicios_especiais = $stmt_especiais->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_especiais->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercícios Especiais - <?php echo htmlspecialchars($caminho['nome_caminho']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(106, 13, 173, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            border: none;
            color: var(--preto-texto);
            font-weight: 600;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e6c200, var(--amarelo-detalhe));
            color: var(--preto-texto);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--roxo-principal);
            color: var(--branco);
            border: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-star me-2"></i>Exercícios Especiais</h1>
                    <p class="mb-0">Caminho: <?php echo htmlspecialchars($caminho['nome_caminho']); ?> (<?php echo htmlspecialchars($caminho['nivel']); ?>)</p>
                </div>
                <a href="gerenciar_caminho.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Lista de Exercícios Especiais</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($exercicios_especiais) >= 5): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Limite máximo de 5 exercícios especiais atingido para este caminho.
                            </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($exercicios_especiais)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Nenhum exercício especial cadastrado.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($exercicios_especiais as $index => $exercicio): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($exercicio['pergunta']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($exercicio['tipo'] ?? 'Especial'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../../../admin/views/editar_exercicio.php?id=<?php echo $exercicio['id']; ?>" class="btn btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-danger" onclick="excluirExercicio(<?php echo $exercicio['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Adicionar Exercício</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($exercicios_especiais) < 5): ?>
                            <form id="formExercicio">
                                <input type="hidden" name="caminho_id" value="<?php echo $caminho_id; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Título</label>
                                    <input type="text" name="titulo" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <option value="multipla_escolha">Múltipla Escolha</option>
                                        <option value="texto_livre">Texto Livre</option>
                                        <option value="completar">Completar</option>
                                        <option value="listening">Listening</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Pergunta</label>
                                    <textarea name="pergunta" class="form-control" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Ordem</label>
                                    <input type="number" name="ordem" class="form-control" min="1" max="5" value="<?php echo count($exercicios_especiais) + 1; ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-plus me-2"></i>Adicionar
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Limite de 5 exercícios especiais atingido.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formExercicio')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('salvar_exercicio_especial.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao salvar exercício');
            });
        });
        
        function excluirExercicio(id) {
            if (confirm('Tem certeza que deseja excluir este exercício especial?')) {
                fetch('excluir_exercicio_especial.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>