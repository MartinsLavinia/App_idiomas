<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Ativar exibição de erros (apenas para desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do caminho foi passado via URL
if (!isset($_GET['caminho_id']) || !is_numeric($_GET['caminho_id'])) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$caminho_id = $_GET['caminho_id'];
$mensagem = '';

// LÓGICA PARA ADICIONAR NOVO BLOCO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_bloco'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $nome_bloco = trim($_POST['nome_bloco'] ?? '');
    $ordem_bloco = intval($_POST['ordem_bloco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($titulo) || empty($nome_bloco) || $ordem_bloco <= 0) {
        $mensagem = '<div class="alert alert-danger">Título, nome do bloco e ordem são obrigatórios.</div>';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Verifica se já existe um bloco com essa ordem no mesmo caminho
        $sql_verifica = "SELECT id FROM blocos WHERE caminho_id = ? AND ordem = ?";
        $stmt_verifica = $conn->prepare($sql_verifica);
        $stmt_verifica->bind_param("ii", $caminho_id, $ordem_bloco);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();
        
        if ($result_verifica->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Já existe um bloco com esta ordem neste caminho.</div>';
        } else {
            // Insere o novo bloco
            $sql_insert = "INSERT INTO blocos (caminho_id, titulo, nome_bloco, ordem, descricao, data_criacao) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            
            if ($stmt_insert) {
                $stmt_insert->bind_param("issis", $caminho_id, $titulo, $nome_bloco, $ordem_bloco, $descricao);
                
                if ($stmt_insert->execute()) {
                    $mensagem = '<div class="alert alert-success">Bloco adicionado com sucesso!</div>';
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro ao adicionar bloco: ' . $stmt_insert->error . '</div>';
                }
                $stmt_insert->close();
            } else {
                $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
            }
        }
        $stmt_verifica->close();
        $database->closeConnection();
    }
}

// LÓGICA PARA EDITAR BLOCO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_bloco'])) {
    $bloco_id = intval($_POST['bloco_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $nome_bloco = trim($_POST['nome_bloco'] ?? '');
    $ordem_bloco = intval($_POST['ordem_bloco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($titulo) || empty($nome_bloco) || $ordem_bloco <= 0) {
        $mensagem = '<div class="alert alert-danger">Título, nome do bloco e ordem são obrigatórios.</div>';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Verifica se já existe outro bloco com essa ordem no mesmo caminho
        $sql_verifica = "SELECT id FROM blocos WHERE caminho_id = ? AND ordem = ? AND id != ?";
        $stmt_verifica = $conn->prepare($sql_verifica);
        $stmt_verifica->bind_param("iii", $caminho_id, $ordem_bloco, $bloco_id);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();
        
        if ($result_verifica->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Já existe outro bloco com esta ordem neste caminho.</div>';
        } else {
            // Atualiza o bloco
            $sql_update = "UPDATE blocos SET titulo = ?, nome_bloco = ?, ordem = ?, descricao = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if ($stmt_update) {
                $stmt_update->bind_param("ssisi", $titulo, $nome_bloco, $ordem_bloco, $descricao, $bloco_id);
                
                if ($stmt_update->execute()) {
                    $mensagem = '<div class="alert alert-success">Bloco atualizado com sucesso!</div>';
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro ao atualizar bloco: ' . $stmt_update->error . '</div>';
                }
                $stmt_update->close();
            } else {
                $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
            }
        }
        $stmt_verifica->close();
        $database->closeConnection();
    }
}

// LÓGICA PARA EXCLUIR BLOCO
if (isset($_GET['excluir_bloco'])) {
    $bloco_id = intval($_GET['excluir_bloco']);
    
    $database = new Database();
    $conn = $database->conn;
    
    // Verifica se existem atividades neste bloco
    // PRIMEIRO VERIFICA SE A COLUNA EXISTE
    $coluna_existe = false;
    $sql_verifica_coluna = "SHOW COLUMNS FROM exercicios LIKE 'bloco_id'";
    $result_coluna = $conn->query($sql_verifica_coluna);
    if ($result_coluna && $result_coluna->num_rows > 0) {
        $coluna_existe = true;
    }
    
    if ($coluna_existe) {
        $sql_verifica_atividades = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
        $stmt_verifica = $conn->prepare($sql_verifica_atividades);
        
        if ($stmt_verifica) {
            $stmt_verifica->bind_param("i", $bloco_id);
            $stmt_verifica->execute();
            $result_verifica = $stmt_verifica->get_result();
            $row_verifica = $result_verifica->fetch_assoc();
            
            if ($row_verifica['total'] > 0) {
                $mensagem = '<div class="alert alert-danger">Não é possível excluir este bloco pois existem atividades vinculadas a ele.</div>';
            } else {
                // Exclui o bloco
                $sql_delete = "DELETE FROM blocos WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $bloco_id);
                    
                    if ($stmt_delete->execute()) {
                        $mensagem = '<div class="alert alert-success">Bloco excluído com sucesso!</div>';
                    } else {
                        $mensagem = '<div class="alert alert-danger">Erro ao excluir bloco: ' . $stmt_delete->error . '</div>';
                    }
                    $stmt_delete->close();
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
                }
            }
            $stmt_verifica->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na verificação de atividades: ' . $conn->error . '</div>';
        }
    } else {
        // Se a coluna não existe, pode excluir o bloco sem verificar atividades
        $sql_delete = "DELETE FROM blocos WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $bloco_id);
            
            if ($stmt_delete->execute()) {
                $mensagem = '<div class="alert alert-success">Bloco excluído com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao excluir bloco: ' . $stmt_delete->error . '</div>';
            }
            $stmt_delete->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }
    }
    
    $database->closeConnection();
}

// BUSCA AS INFORMAÇÕES DO CAMINHO E SEUS BLOCOS
$database = new Database();
$conn = $database->conn;

// Informações do caminho
$sql_caminho = "SELECT nome_caminho, nivel, id_unidade FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);

if ($stmt_caminho) {
    $stmt_caminho->bind_param("i", $caminho_id);
    $stmt_caminho->execute();
    $caminho_info = $stmt_caminho->get_result()->fetch_assoc();
    $stmt_caminho->close();
} else {
    $mensagem = '<div class="alert alert-danger">Erro ao buscar informações do caminho: ' . $conn->error . '</div>';
    $caminho_info = ['nome_caminho' => 'Erro', 'nivel' => 'Erro', 'id_unidade' => 'Erro'];
}

// Lista de blocos do caminho - COM VERIFICAÇÃO DA COLUNA bloco_id
$blocos = [];

// Primeiro verifica se a coluna bloco_id existe na tabela exercicios
$coluna_bloco_id_existe = false;
$sql_verifica_coluna = "SHOW COLUMNS FROM exercicios LIKE 'bloco_id'";
$result_coluna = $conn->query($sql_verifica_coluna);
if ($result_coluna && $result_coluna->num_rows > 0) {
    $coluna_bloco_id_existe = true;
}

// Query diferente baseada na existência da coluna
if ($coluna_bloco_id_existe) {
    $sql_blocos = "SELECT b.*, 
                   (SELECT COUNT(*) FROM exercicios e WHERE e.bloco_id = b.id) as total_atividades
                   FROM blocos b 
                   WHERE b.caminho_id = ? 
                   ORDER BY b.ordem ASC";
} else {
    $sql_blocos = "SELECT b.*, 0 as total_atividades
                   FROM blocos b 
                   WHERE b.caminho_id = ? 
                   ORDER BY b.ordem ASC";
}

$stmt_blocos = $conn->prepare($sql_blocos);

if ($stmt_blocos) {
    $stmt_blocos->bind_param("i", $caminho_id);
    $stmt_blocos->execute();
    $result_blocos = $stmt_blocos->get_result();
    
    if ($result_blocos) {
        $blocos = $result_blocos->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao buscar blocos: ' . $conn->error . '</div>';
    }
    $stmt_blocos->close();
} else {
    $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta de blocos: ' . $conn->error . '</div>';
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Blocos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bloco-card {
            transition: transform 0.2s;
            border-left: 4px solid #007bff;
        }
        .bloco-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stats-badge {
            font-size: 0.8rem;
        }
        .bloco-actions {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .bloco-card:hover .bloco-actions {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-cubes me-2"></i>Gerenciar Blocos
                </h2>
                <p class="text-muted mb-0">
                    Caminho: <strong><?php echo htmlspecialchars($caminho_info['nome_caminho']); ?></strong> 
                    (<?php echo htmlspecialchars($caminho_info['nivel']); ?>)
                </p>
            </div>
            <div>
                <a href="gerenciar_caminho.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar para Caminhos
                </a>
            </div>
        </div>

        <?php echo $mensagem; ?>

        <!-- Alerta se a coluna bloco_id não existir -->
        <?php if (!$coluna_bloco_id_existe): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Atenção: Coluna bloco_id não encontrada</h5>
            <p>A coluna <strong>bloco_id</strong> não existe na tabela <strong>exercicios</strong>.</p>
            <p class="mb-2">Execute este comando SQL para adicionar a coluna:</p>
            <pre class="bg-dark text-light p-3 rounded small">ALTER TABLE exercicios ADD COLUMN bloco_id INT;
ALTER TABLE exercicios ADD CONSTRAINT fk_exercicios_bloco 
FOREIGN KEY (bloco_id) REFERENCES blocos(id) ON DELETE SET NULL;</pre>
            <p class="mt-2 mb-0"><small>Enquanto a coluna não for criada, as atividades não serão vinculadas aos blocos.</small></p>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulário para Adicionar/Editar Bloco -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-1"></i>
                            <?php echo isset($_GET['editar']) ? 'Editar Bloco' : 'Adicionar Novo Bloco'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $edit_mode = isset($_GET['editar']);
                        $bloco_edit = null;
                        
                        if ($edit_mode) {
                            $bloco_id_edit = intval($_GET['editar']);
                            foreach ($blocos as $bloco) {
                                if ($bloco['id'] == $bloco_id_edit) {
                                    $bloco_edit = $bloco;
                                    break;
                                }
                            }
                        }
                        ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título do Bloco *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['titulo']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="nome_bloco" class="form-label">Nome do Bloco *</label>
                                <input type="text" class="form-control" id="nome_bloco" name="nome_bloco" 
                                       value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['nome_bloco']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ordem_bloco" class="form-label">Ordem no Caminho *</label>
                                <input type="number" class="form-control" id="ordem_bloco" name="ordem_bloco" 
                                       value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['ordem']) : ''; ?>" 
                                       min="1" required>
                                <div class="form-text">Define a sequência deste bloco no caminho</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $bloco_edit ? htmlspecialchars($bloco_edit['descricao']) : ''; ?></textarea>
                            </div>
                            
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="bloco_id" value="<?php echo $bloco_edit['id']; ?>">
                                <button type="submit" name="editar_bloco" class="btn btn-warning w-100">
                                    <i class="fas fa-save me-1"></i>Atualizar Bloco
                                </button>
                                <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>" class="btn btn-secondary w-100 mt-2">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                            <?php else: ?>
                                <button type="submit" name="adicionar_bloco" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Adicionar Bloco
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-1"></i>Estatísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0"><?php echo count($blocos); ?></h4>
                                    <small class="text-muted">Total de Blocos</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div>
                                    <?php
                                    $total_atividades = 0;
                                    foreach ($blocos as $bloco) {
                                        $total_atividades += $bloco['total_atividades'];
                                    }
                                    ?>
                                    <h4 class="text-success mb-0"><?php echo $total_atividades; ?></h4>
                                    <small class="text-muted">Total de Atividades</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Blocos Existentes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-1"></i>Blocos do Caminho
                        </h5>
                        <span class="badge bg-primary"><?php echo count($blocos); ?> blocos</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($blocos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-cubes fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum bloco criado ainda.</p>
                                <p class="text-muted small">Use o formulário ao lado para adicionar o primeiro bloco.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($blocos as $bloco): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bloco-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">
                                                        <i class="fas fa-cube me-1 text-primary"></i>
                                                        <?php echo htmlspecialchars($bloco['titulo']); ?>
                                                    </h6>
                                                    <span class="badge bg-light text-dark stats-badge">
                                                        <?php echo $bloco['total_atividades']; ?> ativid.
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text small text-muted mb-2">
                                                    <strong>Nome:</strong> <?php echo htmlspecialchars($bloco['nome_bloco']); ?>
                                                </p>
                                                
                                                <p class="card-text small text-muted mb-2">
                                                    <?php echo !empty($bloco['descricao']) ? htmlspecialchars($bloco['descricao']) : '<em>Sem descrição</em>'; ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        Ordem: <strong><?php echo $bloco['ordem']; ?></strong> | 
                                                        Criado: <?php echo date('d/m/Y', strtotime($bloco['data_criacao'])); ?>
                                                    </small>
                                                    
                                                    <div class="bloco-actions">
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($coluna_bloco_id_existe): ?>
                                                            <a href="gerenciar_exercicios.php?bloco_id=<?php echo $bloco['id']; ?>" 
                                                               class="btn btn-outline-primary" title="Gerenciar Atividades">
                                                                <i class="fas fa-tasks"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>&editar=<?php echo $bloco['id']; ?>" 
                                                               class="btn btn-outline-warning" title="Editar Bloco">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>&excluir_bloco=<?php echo $bloco['id']; ?>" 
                                                               class="btn btn-outline-danger" 
                                                               title="Excluir Bloco"
                                                               onclick="return confirm('Tem certeza que deseja excluir este bloco?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações Rápidas CORRIGIDAS -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-bolt me-1"></i>Ações Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <?php if ($coluna_bloco_id_existe && !empty($blocos)): ?>
                                    <!-- Dropdown para escolher em qual bloco adicionar a atividade -->
                                    <div class="dropdown">
                                        <button class="btn btn-outline-success w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-plus me-1"></i>Adicionar Atividade
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            <?php foreach ($blocos as $bloco): ?>
                                                <li>
                                                    <a class="dropdown-item" href="adicionar_atividades.php?unidade_id=<?php echo $caminho_info['id_unidade']; ?>&bloco_id=<?php echo $bloco['id']; ?>">
                                                        <i class="fas fa-cube me-2"></i><?php echo htmlspecialchars($bloco['titulo']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary w-100" disabled 
                                            title="<?php echo !$coluna_bloco_id_existe ? 'Coluna bloco_id não existe' : 'Crie um bloco primeiro'; ?>">
                                        <i class="fas fa-plus me-1"></i>Adicionar Atividade
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="gerenciar_exercicios.php?caminho_id=<?php echo $caminho_id; ?>" 
                                   class="btn btn-outline-info w-100">
                                    <i class="fas fa-list me-1"></i>Ver Todas as Atividades
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>