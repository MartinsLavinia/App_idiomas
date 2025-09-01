<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_idioma = trim($_POST['nome_idioma']);
    
    if (empty($nome_idioma)) {
        $_SESSION['error'] = "Nome do idioma é obrigatório.";
        header("Location: gerenciar_caminho.php");
        exit();
    }
    
    $database = new Database();
    $conn = $database->conn;
    
    try {
        // Verificar se o idioma já existe
        $sql_check = "SELECT COUNT(*) as count FROM (
            SELECT idioma FROM caminhos_aprendizagem WHERE idioma = ?
            UNION
            SELECT idioma FROM quiz_nivelamento WHERE idioma = ?
        ) as idiomas_existentes";
        
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $nome_idioma, $nome_idioma);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt_check->close();
        
        if ($count > 0) {
            $_SESSION['error'] = "O idioma '$nome_idioma' já existe no sistema.";
            header("Location: gerenciar_caminho.php");
            exit();
        }
        
        // Criar uma entrada básica na tabela quiz_nivelamento para o idioma
        // Isso garante que o idioma apareça na lista de idiomas disponíveis
        $sql_insert = "INSERT INTO quiz_nivelamento (idioma, pergunta, opcao_a, opcao_b, opcao_c, resposta_correta) 
                       VALUES (?, 'Pergunta temporária - Configure o quiz', 'A', 'B', 'C', 'A')";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("s", $nome_idioma);
        
        if ($stmt_insert->execute()) {
            $_SESSION['success'] = "Idioma '$nome_idioma' adicionado com sucesso! Configure o quiz de nivelamento.";
        } else {
            $_SESSION['error'] = "Erro ao adicionar o idioma: " . $conn->error;
        }
        
        $stmt_insert->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao processar a solicitação: " . $e->getMessage();
    }
    
    $database->closeConnection();
}

header("Location: gerenciar_caminho.php");
exit();
?>