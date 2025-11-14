<?php
session_start();
include_once __DIR__ . 
'/../../conexao.php';

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit();
}

if (isset($_GET["id"])) {
    $id_unidade = $_GET["id"];

    $database = new Database();
    $conn = $database->conn;

    try {
        // Verificar se existem caminhos de aprendizagem associados a esta unidade
        $sql_check_caminhos = "SELECT COUNT(*) as count FROM caminhos_aprendizagem WHERE id_unidade = ?";
        $stmt_check_caminhos = $conn->prepare($sql_check_caminhos);
        $stmt_check_caminhos->bind_param("i", $id_unidade);
        $stmt_check_caminhos->execute();
        $result_check_caminhos = $stmt_check_caminhos->get_result();
        $row_check_caminhos = $result_check_caminhos->fetch_assoc();
        $stmt_check_caminhos->close();

        if ($row_check_caminhos["count"] > 0) {
            $_SESSION["error"] = "Não é possível excluir a unidade porque existem caminhos de aprendizagem associados a ela.";
        } else {
            $sql_delete = "DELETE FROM unidades WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id_unidade);

            if ($stmt_delete->execute()) {
                $_SESSION["success"] = "Unidade excluída com sucesso!";
            } else {
                $_SESSION["error"] = "Erro ao excluir a unidade: " . $conn->error;
            }
            $stmt_delete->close();
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "Erro ao processar a solicitação: " . $e->getMessage();
    }

    $database->closeConnection();
}

header("Location: gerenciar_unidades.php");
exit();
?>