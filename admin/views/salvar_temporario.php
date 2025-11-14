<?php
// salvar_temporario.php
session_start();
header('Content-Type: application/json');

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}

try {
    // Configurações de paginação (mesmas do arquivo principal)
    $limit = 5;
    $total_perguntas = 20;
    
    // Inicializa a sessão se necessário
    if (!isset($_SESSION['quiz_data'])) {
        $_SESSION['quiz_data'] = [];
    }
    
    // Salva o nome do idioma se fornecido
    if (isset($_POST['idioma']) && !empty(trim($_POST['idioma']))) {
        $_SESSION['idioma_novo'] = trim($_POST['idioma']);
    }
    
    // Salva todas as perguntas enviadas no formulário
    for ($i = 1; $i <= $total_perguntas; $i++) {
        $pergunta_key = "pergunta_$i";
        
        if (isset($_POST[$pergunta_key])) {
            $_SESSION['quiz_data'][$i] = [
                'pergunta' => trim($_POST[$pergunta_key]),
                'opcao_a' => trim($_POST["opcao_a_$i"] ?? ''),
                'opcao_b' => trim($_POST["opcao_b_$i"] ?? ''),
                'opcao_c' => trim($_POST["opcao_c_$i"] ?? ''),
                'opcao_d' => trim($_POST["opcao_d_$i"] ?? ''),
                'resposta_correta' => trim($_POST["resposta_correta_$i"] ?? ''),
            ];
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Dados salvos temporariamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
}
?>