<?php
// Configuração para evitar exibição de erros na tela
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se é uma requisição AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Para requisições AJAX, definir header JSON imediatamente
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

if (!isset($_SESSION['id_admin'])) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado. Faça login novamente.']);
        exit();
    } else {
        header("Location: login_admin.php");
        exit();
    }
}

$database = new Database();
$conn = $database->conn;

// Se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campos do modal: idioma, nivel, nome_caminho, unidade_id
    $idioma = trim($_POST['idioma'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $nome_caminho = trim($_POST['nome_caminho'] ?? '');
    $unidade_id = intval($_POST['unidade_id'] ?? 0);

    // Validação dos campos
    if (empty($idioma) || empty($nivel) || empty($nome_caminho) || $unidade_id <= 0) {
        $response = ['success' => false, 'message' => "⚠️ Todos os campos são obrigatórios!"];
        
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            $_SESSION['error'] = $response['message'];
            header("Location: adicionar_caminho.php");
            exit();
        }
    }

    try {
        // Verificar se a unidade existe
        $sql_check_unidade = "SELECT id FROM unidades WHERE id = ?";
        $stmt_check_unidade = $conn->prepare($sql_check_unidade);
        $stmt_check_unidade->bind_param("i", $unidade_id);
        $stmt_check_unidade->execute();
        $result_check_unidade = $stmt_check_unidade->get_result();
        
        if ($result_check_unidade->num_rows === 0) {
            throw new Exception("Unidade selecionada não existe!");
        }
        $stmt_check_unidade->close();

        // Verificar se já existe um caminho com o mesmo nome para o mesmo idioma e nível
        $sql_check = "SELECT id FROM caminhos_aprendizagem WHERE idioma = ? AND nivel = ? AND nome_caminho = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("sss", $idioma, $nivel, $nome_caminho);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $response = ['success' => false, 'message' => "❌ Já existe um caminho com este nome para o idioma e nível selecionados!"];
            
            if ($isAjax) {
                echo json_encode($response);
                exit();
            } else {
                $_SESSION['error'] = $response['message'];
                header("Location: adicionar_caminho.php");
                exit();
            }
        }
        $stmt_check->close();

        // Inserir no banco
        $sql_insert = "INSERT INTO caminhos_aprendizagem (idioma, nivel, nome_caminho, id_unidade) 
                       VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar a query: " . $conn->error);
        }
        
        $stmt->bind_param("sssi", $idioma, $nivel, $nome_caminho, $unidade_id);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => "✅ Caminho '$nome_caminho' adicionado com sucesso!"];
            
            if ($isAjax) {
                echo json_encode($response);
                exit();
            } else {
                $_SESSION['success'] = $response['message'];
            }
        } else {
            throw new Exception("Erro ao executar a query: " . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => "❌ Erro ao adicionar caminho: " . $e->getMessage()];
        
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            $_SESSION['error'] = $response['message'];
        }
    }

    // Redirecionamento para requisições não-AJAX
    if (!$isAjax) {
        header("Location: adicionar_caminho.php");
        exit();
    }
}

// Se chegou até aqui em uma requisição AJAX POST, algo deu errado
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode(['success' => false, 'message' => 'Erro: Nenhum dado processado']);
    exit();
}

// Código abaixo só é executado para requisições não-AJAX (acesso direto à página)
// Buscar unidades existentes no banco para exibir no formulário
$sql_unidades = "SELECT id, nome_unidade FROM unidades ORDER BY nome_unidade";
$result_unidades = $conn->query($sql_unidades);

$unidades = [];
if ($result_unidades && $result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Buscar idiomas existentes
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$result_idiomas = $conn->query($sql_idiomas);

$idiomas = [];
if ($result_idiomas && $result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas[] = $row['idioma'];
    }
}

$database->closeConnection();

// Se for AJAX, não exibe o HTML
if ($isAjax) {
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Caminho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">➕ Adicionar Caminho</h1>
            <a href="gerenciar_caminho.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para acesso direto (não-AJAX) -->
        <div class="card shadow">
            <div class="card-body">
                <form method="post" action="adicionar_caminho.php">
                    <div class="mb-3">
                        <label for="nome_caminho" class="form-label">Nome do Caminho</label>
                        <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" required>
                    </div>

                    <div class="mb-3">
                        <label for="idioma" class="form-label">Idioma</label>
                        <select class="form-select" id="idioma" name="idioma" required>
                            <option value="">-- Selecione o idioma --</option>
                            <?php foreach ($idiomas as $idioma): ?>
                                <option value="<?= htmlspecialchars($idioma); ?>"><?= htmlspecialchars($idioma); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="">-- Selecione --</option>
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="B1">B1</option>
                            <option value="B2">B2</option>
                            <option value="C1">C1</option>
                            <option value="C2">C2</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="unidade_id" class="form-label">Unidade</label>
                        <select class="form-select" id="unidade_id" name="unidade_id" required>
                            <option value="">-- Selecione a unidade --</option>
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= $u['id']; ?>">
                                    <?= htmlspecialchars($u['nome_unidade']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Caminho</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>