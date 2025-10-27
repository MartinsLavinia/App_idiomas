<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: apenas administradores logados podem acessar.
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o método é POST e se o ID (nome do idioma) foi enviado.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    $_SESSION['error'] = "Requisição inválida para excluir idioma.";
    header("Location: pagina_adicionar_idiomas.php");
    exit();
}

$idioma_nome = trim(urldecode($_POST['id']));

if (empty($idioma_nome)) {
    $_SESSION['error'] = "Nome do idioma a ser excluído não pode ser vazio.";
    header("Location: pagina_adicionar_idiomas.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

try {
    // Inicia uma transação para garantir que todas as exclusões ocorram ou nenhuma.
    $conn->begin_transaction();

    // 1. Encontrar o ID do idioma pelo nome.
    $sql_get_id = "SELECT id FROM idiomas WHERE nome_idioma = ?";
    $stmt_get_id = $conn->prepare($sql_get_id);
    $stmt_get_id->bind_param("s", $idioma_nome);
    $stmt_get_id->execute();
    $result_id = $stmt_get_id->get_result();
    if ($result_id->num_rows === 0) {
        throw new Exception("Idioma não encontrado para exclusão.");
    }
    $idioma_id = $result_id->fetch_assoc()['id'];
    $stmt_get_id->close();

    // 2. Excluir perguntas do quiz de nivelamento associadas ao idioma.
    $sql_delete_quiz = "DELETE FROM quiz_nivelamento WHERE idioma = ?";
    $stmt_delete_quiz = $conn->prepare($sql_delete_quiz);
    $stmt_delete_quiz->bind_param("s", $idioma_nome);
    $stmt_delete_quiz->execute();
    $stmt_delete_quiz->close();

    // 3. Excluir caminhos de aprendizagem associados ao idioma.
    // A exclusão em cascata (ON DELETE CASCADE) no banco de dados cuidaria de blocos e exercícios,
    // mas vamos fazer manualmente para garantir.
    $sql_delete_caminhos = "DELETE FROM caminhos_aprendizagem WHERE idioma = ?";
    $stmt_delete_caminhos = $conn->prepare($sql_delete_caminhos);
    $stmt_delete_caminhos->bind_param("s", $idioma_nome);
    $stmt_delete_caminhos->execute();
    $stmt_delete_caminhos->close();

    // 4. Excluir unidades associadas ao idioma.
    $sql_delete_unidades = "DELETE FROM unidades WHERE id_idioma = ?";
    $stmt_delete_unidades = $conn->prepare($sql_delete_unidades);
    $stmt_delete_unidades->bind_param("i", $idioma_id);
    $stmt_delete_unidades->execute();
    $stmt_delete_unidades->close();

    // 5. Finalmente, excluir o idioma da tabela 'idiomas'.
    $sql_delete_idioma = "DELETE FROM idiomas WHERE id = ?";
    $stmt_delete_idioma = $conn->prepare($sql_delete_idioma);
    $stmt_delete_idioma->bind_param("i", $idioma_id);
    $stmt_delete_idioma->execute();
    $stmt_delete_idioma->close();

    // Se tudo deu certo, confirma a transação.
    $conn->commit();
    $_SESSION['success'] = "Idioma '$idioma_nome' e todos os seus dados associados foram excluídos com sucesso!";

} catch (Exception $e) {
    // Se algo deu errado, desfaz todas as operações.
    $conn->rollback();
    $_SESSION['error'] = "Erro ao excluir o idioma: " . $e->getMessage();
} finally {
    $database->closeConnection();
}

// Redireciona de volta para a página de gerenciamento.
header("Location: pagina_adicionar_idiomas.php");
exit();
