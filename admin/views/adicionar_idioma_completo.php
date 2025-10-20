<?php
// adicionar_idioma_completo.php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// 1. Configurações de Paginação (deve ser a mesma do pagina_adicionar_idiomas.php)
$limit = 5; // Limite de perguntas por página
$total_perguntas = 20; // Total de perguntas a serem exibidas
$total_paginas = ceil($total_perguntas / $limit); // 20 / 5 = 4 páginas

// Pega o número da próxima página da URL (GET)
$proxima_pagina = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// A página que acabou de ser processada (a página atual que enviou o formulário)
$pagina_atual = $proxima_pagina - 1;

// Se a página atual for 0 (erro ou primeira entrada), redireciona.
if ($pagina_atual < 1) {
    header("Location: pagina_adicionar_idiomas.php");
    exit();
}

// 2. Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializa a sessão para o quiz se for a primeira página
    if ($pagina_atual === 1) {
        $_SESSION['quiz_data'] = [];
        $_SESSION['idioma_novo'] = trim($_POST['idioma'] ?? '');

        if (empty($_SESSION['idioma_novo'])) {
             $_SESSION['error'] = "O nome do idioma não pode ser vazio.";
             header("Location: pagina_adicionar_idiomas.php");
             exit();
        }
    }

    // Garante que o idioma está salvo para as próximas páginas
    $nome_idioma = $_SESSION['idioma_novo'] ?? '';

    // 3. Salvar os dados da página atual na sessão
    $offset_inicial = ($pagina_atual - 1) * $limit + 1;
    $offset_final = $offset_inicial + $limit;

    for ($i = $offset_inicial; $i < $offset_final && $i <= $total_perguntas; $i++) {
        $pergunta_key = "pergunta_$i";
        
        // Verifica se a pergunta foi enviada (evita salvar dados incompletos ou em submissão errada)
        if (isset($_POST[$pergunta_key])) {
             $_SESSION['quiz_data'][$i] = [
                'pergunta' => trim($_POST[$pergunta_key]),
                'opcao_a' => trim($_POST["opcao_a_$i"]),
                'opcao_b' => trim($_POST["opcao_b_$i"]),
                'opcao_c' => trim($_POST["opcao_c_$i"]),
                'resposta_correta' => trim($_POST["resposta_correta_$i"]),
            ];

            // Validação simples
            if (empty($_SESSION['quiz_data'][$i]['pergunta']) || 
                empty($_SESSION['quiz_data'][$i]['resposta_correta']) || 
                !in_array($_SESSION['quiz_data'][$i]['resposta_correta'], ['A', 'B', 'C'])) 
            {
                $_SESSION['error'] = "Pergunta #$i ou resposta correta inválida.";
                header("Location: pagina_adicionar_idiomas.php?page=$pagina_atual");
                exit();
            }
        }
    }

    // 4. Se não for a última página, redireciona para a próxima página do formulário
    if ($proxima_pagina <= $total_paginas) {
        // Redireciona de volta para a página de idiomas, que agora exibirá a próxima página do quiz
        header("Location: pagina_adicionar_idiomas.php?page=$proxima_pagina");
        exit();
    }
    
    // 5. Lógica de Submissão Final (após a última página do formulário)
    if ($proxima_pagina > $total_paginas) {
        if (count($_SESSION['quiz_data']) !== $total_perguntas) {
             $_SESSION['error'] = "Erro: Faltam perguntas no quiz. Total esperado: $total_perguntas, Encontrado: " . count($_SESSION['quiz_data']);
             header("Location: pagina_adicionar_idiomas.php");
             exit();
        }

        try {
            $conn->begin_transaction();

            // A. Verificar e Inserir o Idioma
            $sql_check = "SELECT COUNT(*) as count FROM idiomas WHERE nome_idioma = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $nome_idioma);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt_check->close();

            if ($count > 0) {
                $_SESSION['error'] = "O idioma '$nome_idioma' já existe no sistema.";
                $conn->rollback();
                header("Location: pagina_adicionar_idiomas.php");
                exit();
            }

            $sql_insert_idioma = "INSERT INTO idiomas (nome_idioma) VALUES (?)";
            $stmt_idioma = $conn->prepare($sql_insert_idioma);
            $stmt_idioma->bind_param("s", $nome_idioma);
            $stmt_idioma->execute();
            $id_idioma = $conn->insert_id;
            $stmt_idioma->close();

            // B. Inserir as Perguntas do Quiz
            // Assumindo a tabela 'quiz_nivelamento' com colunas: 
            // idioma, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta
            $sql_insert_quiz = "INSERT INTO quiz_nivelamento (idioma, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_quiz = $conn->prepare($sql_insert_quiz);

            if ($stmt_quiz === false) {
                throw new Exception("Erro ao preparar a consulta do quiz: " . $conn->error);
            }

            foreach ($_SESSION['quiz_data'] as $data) {
                $stmt_quiz->bind_param("sssssss", $nome_idioma, $data['pergunta'], $data['opcao_a'], $data['opcao_b'], $data['opcao_c'], $alternativa_d_vazia, $data['resposta_correta']);
                $stmt_quiz->execute();
            }

            $stmt_quiz->close();

            $conn->commit();

            $_SESSION['success'] = "Idioma '$nome_idioma' e Quiz de Nivelamento (20 perguntas) adicionados com sucesso!";
            
            // 6. Limpar dados temporários da sessão
            unset($_SESSION['quiz_data']);
            unset($_SESSION['idioma_novo']);
            
            header("Location: pagina_adicionar_idiomas.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Erro ao adicionar o Idioma e Quiz. Detalhes: " . $e->getMessage();
            header("Location: pagina_adicionar_idiomas.php?page=" . ($pagina_atual > 0 ? $pagina_atual : 1));
            exit();
        }
    }
} else {
    // Se a página for acessada via GET sem um propósito, redireciona para a primeira página.
    header("Location: pagina_adicionar_idiomas.php");
    exit();
}

$database->closeConnection();
?>