<?php
session_start();

// segurança: só admin logado
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login_admin.php");
    exit();
}

// aceita GET e POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: gerenciar_caminho.php?status=error&message=" . urlencode("Método inválido."));
    exit();
}

// calcula o caminho absoluto até o conexao.php (2 níveis acima de admin/views)
$pathConexao = dirname(__DIR__, 2) . '/conexao.php';

// debug amigável se arquivo faltar
if (!file_exists($pathConexao)) {
    header("Location: gerenciar_caminho.php?status=error&message=" . urlencode("Arquivo de conexão não encontrado em: $pathConexao"));
    exit();
}

// carrega conexão
require_once $pathConexao;

// verifica se a classe existe
if (!class_exists('Database')) {
    header("Location: gerenciar_caminho.php?status=error&message=" . urlencode("Classe Database não encontrada em conexao.php."));
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: gerenciar_caminho.php?status=error&message=" . urlencode("ID inválido."));
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;

    $stmt = $conn->prepare('DELETE FROM caminhos_aprendizagem WHERE id = ?');
    if (!$stmt) {
        throw new Exception("Erro no prepare(): " . $conn->error);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $affected = $stmt->affected_rows;
    $stmt->close();
    $database->closeConnection();

    if ($affected > 0) {
        $_SESSION['success'] = "Caminho eliminado com sucesso!";
        header("Location: gerenciar_caminho.php");
    } else {
        $_SESSION['error'] = "Caminho não encontrado ou já excluído.";
        header("Location: gerenciar_caminho.php");
    }
    exit();
} catch (Throwable $e) {
    $_SESSION['error'] = "Erro ao eliminar: " . $e->getMessage();
    header("Location: gerenciar_caminho.php");
    exit();
}
