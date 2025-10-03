<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Verificar se a conexão foi bem sucedida
if (!$conn) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$idioma_selecionado = isset($_GET['idioma']) ? trim($_GET['idioma']) : null;

if (empty($idioma_selecionado)) {
    $_SESSION['error'] = "Idioma não especificado para o quiz.";
    header("Location: gerenciar_caminho.php");
    exit();
}

// Lógica para adicionar uma nova pergunta (via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $pergunta = $_POST['pergunta'];
    $alternativa_a = $_POST['alternativa_a'];
    $alternativa_b = $_POST['alternativa_b'];
    $alternativa_c = $_POST['alternativa_c'];
    $alternativa_d = $_POST['alternativa_d'];
    $resposta_correta = $_POST['resposta_correta'];

    // Verificar se a tabela existe, se não, criar
    $sql_check_table = "SHOW TABLES LIKE 'quiz_nivelamento'";
    $result = $conn->query($sql_check_table);
    
    if ($result->num_rows == 0) {
        // Criar a tabela se não existir
        $sql_create_table = "CREATE TABLE quiz_nivelamento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            idioma VARCHAR(50) NOT NULL,
            pergunta TEXT NOT NULL,
            alternativa_a VARCHAR(255) NOT NULL,
            alternativa_b VARCHAR(255) NOT NULL,
            alternativa_c VARCHAR(255) NOT NULL,
            alternativa_d VARCHAR(255) NOT NULL,
            resposta_correta ENUM('A', 'B', 'C', 'D') NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($sql_create_table)) {
            $_SESSION['error'] = "Erro ao criar tabela: " . $conn->error;
            header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado));
            exit();
        }
    }

    $sql_insert_quiz = "INSERT INTO quiz_nivelamento (idioma, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_quiz = $conn->prepare($sql_insert_quiz);
    
    if ($stmt_insert_quiz === false) {
        $_SESSION['error'] = "Erro ao preparar a consulta: " . $conn->error;
        header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado));
        exit();
    }
    
    $stmt_insert_quiz->bind_param('sssssss', $idioma_selecionado, $pergunta, $alternativa_a, $alternativa_b, $alternativa_c, $alternativa_d, $resposta_correta);
    
    if ($stmt_insert_quiz->execute()) {
        $_SESSION['success'] = "Pergunta adicionada com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar a pergunta: " . $stmt_insert_quiz->error;
    }
    $stmt_insert_quiz->close();

    header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado));
    exit();
}

// Lógica para excluir uma pergunta (via GET)
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id_pergunta = $_GET['id'];

    $sql_delete_quiz = "DELETE FROM quiz_nivelamento WHERE id = ? AND idioma = ?";
    $stmt_delete_quiz = $conn->prepare($sql_delete_quiz);
    
    if ($stmt_delete_quiz === false) {
        $_SESSION['error'] = "Erro ao preparar a consulta de exclusão: " . $conn->error;
        header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado));
        exit();
    }
    
    $stmt_delete_quiz->bind_param('is', $id_pergunta, $idioma_selecionado);

    if ($stmt_delete_quiz->execute()) {
        $_SESSION['success'] = "Pergunta excluída com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao excluir a pergunta: " . $stmt_delete_quiz->error;
    }
    $stmt_delete_quiz->close();

    header("Location: gerenciar_quiz_nivelamento.php?idioma=" . urlencode($idioma_selecionado));
    exit();
}

// Verificar se a tabela existe antes de tentar buscar dados
$sql_check_table = "SHOW TABLES LIKE 'quiz_nivelamento'";
$result = $conn->query($sql_check_table);
$tabela_existe = ($result->num_rows > 0);

// Inicializar variáveis
$quiz_perguntas = [];
$total_perguntas = 0;
$total_idiomas = 0;
$stats_respostas = [];
$ultimas_perguntas = [];
$atividade_mensal = 0;

// Lógica para buscar as perguntas do quiz de nivelamento (apenas se a tabela existir)
if ($tabela_existe) {
    $sql_quiz = "SELECT id, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta FROM quiz_nivelamento WHERE idioma = ?";
    $stmt_quiz = $conn->prepare($sql_quiz);
    
    if ($stmt_quiz !== false) {
        $stmt_quiz->bind_param('s', $idioma_selecionado);
        $stmt_quiz->execute();
        $result_quiz = $stmt_quiz->get_result();
        $quiz_perguntas = $result_quiz->fetch_all(MYSQLI_ASSOC);
        $total_perguntas = count($quiz_perguntas);
        $stmt_quiz->close();
    }

    // Lógica para contar total de idiomas
    $sql_total_idiomas = "SELECT COUNT(DISTINCT idioma) as total FROM quiz_nivelamento";
    $result_total_idiomas = $conn->query($sql_total_idiomas);
    if ($result_total_idiomas) {
        $row = $result_total_idiomas->fetch_assoc();
        $total_idiomas = $row['total'];
    }

    // ========== FUNCIONALIDADES ADMINISTRATIVAS ==========

    // 1. Estatísticas por resposta correta
    $sql_stats_respostas = "SELECT resposta_correta, COUNT(*) as quantidade FROM quiz_nivelamento WHERE idioma = ? GROUP BY resposta_correta";
    $stmt_stats_respostas = $conn->prepare($sql_stats_respostas);
    if ($stmt_stats_respostas !== false) {
        $stmt_stats_respostas->bind_param('s', $idioma_selecionado);
        $stmt_stats_respostas->execute();
        $result_stats = $stmt_stats_respostas->get_result();
        $stats_respostas = $result_stats->fetch_all(MYSQLI_ASSOC);
        $stmt_stats_respostas->close();
    }

    // 2. Últimas perguntas adicionadas (top 5)
    $sql_ultimas_perguntas = "SELECT id, pergunta, DATE_FORMAT(data_criacao, '%d/%m/%Y') as data_adicao FROM quiz_nivelamento WHERE idioma = ? ORDER BY id DESC LIMIT 5";
    $stmt_ultimas_perguntas = $conn->prepare($sql_ultimas_perguntas);
    if ($stmt_ultimas_perguntas !== false) {
        $stmt_ultimas_perguntas->bind_param('s', $idioma_selecionado);
        $stmt_ultimas_perguntas->execute();
        $result_ultimas = $stmt_ultimas_perguntas->get_result();
        $ultimas_perguntas = $result_ultimas->fetch_all(MYSQLI_ASSOC);
        $stmt_ultimas_perguntas->close();
    }

    // 3. Busca/Filtro de perguntas
    $termo_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
    if (!empty($termo_busca)) {
        $sql_quiz_filtrado = "SELECT id, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta FROM quiz_nivelamento WHERE idioma = ? AND pergunta LIKE ?";
        $stmt_quiz_filtrado = $conn->prepare($sql_quiz_filtrado);
        if ($stmt_quiz_filtrado !== false) {
            $termo_like = '%' . $termo_busca . '%';
            $stmt_quiz_filtrado->bind_param('ss', $idioma_selecionado, $termo_like);
            $stmt_quiz_filtrado->execute();
            $result_filtrado = $stmt_quiz_filtrado->get_result();
            $quiz_perguntas = $result_filtrado->fetch_all(MYSQLI_ASSOC);
            $stmt_quiz_filtrado->close();
        }
    }

    // 4. Estatísticas de atividade
    $sql_atividade_mensal = "SELECT COUNT(*) as total FROM quiz_nivelamento WHERE idioma = ? AND MONTH(data_criacao) = MONTH(NOW()) AND YEAR(data_criacao) = YEAR(NOW())";
    $stmt_atividade_mensal = $conn->prepare($sql_atividade_mensal);
    if ($stmt_atividade_mensal !== false) {
        $stmt_atividade_mensal->bind_param('s', $idioma_selecionado);
        $stmt_atividade_mensal->execute();
        $result_atividade = $stmt_atividade_mensal->get_result();
        $row_atividade = $result_atividade->fetch_assoc();
        $atividade_mensal = $row_atividade['total'];
        $stmt_atividade_mensal->close();
    }
}

// 5. Perguntas por dificuldade (simulado - você pode adicionar uma coluna de dificuldade na tabela)
$perguntas_faceis = floor($total_perguntas * 0.6); // 60% fáceis
$perguntas_medias = floor($total_perguntas * 0.3); // 30% médias
$perguntas_dificeis = $total_perguntas - $perguntas_faceis - $perguntas_medias; // 10% difíceis

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Quiz - <?php echo htmlspecialchars($idioma_selecionado); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #343a40;
            --cinza-fundo: #f8f9fa;
            --cinza-borda: #e9ecef;
            --cinza-texto: #6c757d;
            
            --sombra-leve: 0 2px 10px rgba(0, 0, 0, 0.05);
            --sombra-media: 0 5px 20px rgba(0, 0, 0, 0.08);
            --sombra-forte: 0 10px 30px rgba(0, 0, 0, 0.15);
            --borda-radius: 1rem;
            --transicao-padrao: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--preto-texto);
            min-height: 100vh;
            position: relative;
        }

        /* Elementos decorativos de fundo */
        .background-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.05), rgba(251, 191, 36, 0.05));
            animation: floatShape 8s ease-in-out infinite;
        }

        .shape-1 {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 8%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
        }

        .shape-4 {
            width: 120px;
            height: 120px;
            top: 30%;
            right: 20%;
            animation-delay: 6s;
        }

        .main-content {
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }

        /* Divisórias decorativas */
        .section-divider {
            height: 3px;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            border-radius: 10px;
            margin: 2rem 0;
            opacity: 0.7;
        }

        .mini-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #7c3aed, transparent);
            margin: 1.5rem 0;
            opacity: 0.4;
        }

        /* Cards com gradientes sutis */
        .card {
            border: none;
            border-radius: var(--borda-radius);
            box-shadow: var(--sombra-leve);
            transition: var(--transicao-padrao);
            animation: fadeInUp 0.8s ease-out;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248, 250, 252, 0.95));
            backdrop-filter: blur(10px);
        }

        .card:hover {
            box-shadow: var(--sombra-media);
            transform: translateY(-2px);
        }

        /* ========== ANIMAÇÕES ========== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shine {
            0% {
                background-position: -200%;
            }
            100% {
                background-position: 200%;
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes floatShape {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.6;
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.5s ease-out;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248, 250, 252, 0.9));
            border-radius: var(--borda-radius);
            box-shadow: var(--sombra-leve);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(124, 58, 237, 0.1);
        }
        .page-header .logo-container img {
            height: 70px;
            transition: var(--transicao-padrao);
        }
        .page-header .logo-container img:hover {
            transform: scale(1.05) rotate(5deg);
        }
        .page-header .header-title h1 {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .breadcrumb {
            margin-bottom: 0;
            --bs-breadcrumb-divider-color: var(--cinza-texto);
        }
       .breadcrumb-item a {
    text-decoration: none;
    color: var(--roxo-principal);
    font-weight: 500;
    transition: var(--transicao-padrao);
}
.breadcrumb-item a:hover {
    color: var(--roxo-principal);
    transform: translateX(5px);
    text-shadow: 0 0 10px #5b21b6;
}
        .page-header .profile-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .page-header .profile-actions .admin-info {
            text-align: right;
        }
        .page-header .profile-actions .admin-info span {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .page-header .profile-actions .admin-info small {
            color: var(--cinza-texto);
            font-size: 0.8rem;
        }
        .page-header .profile-actions .fa-user-circle {
            font-size: 2.8rem;
            color: var(--roxo-principal);
            transition: var(--transicao-padrao);
        }
        .page-header .profile-actions .fa-user-circle:hover {
            animation: pulse 0.6s ease-in-out;
        }

        /* ========== CARDS ADMINISTRATIVOS ========== */
        .admin-section {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--branco);
            border-radius: var(--borda-radius);
            padding: 1.5rem;
            box-shadow: var(--sombra-leve);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: var(--transicao-padrao);
            border: 1px solid var(--cinza-borda);
            animation: fadeInUp 0.6s ease-out;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248, 250, 252, 0.95));
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--sombra-forte);
            border-color: var(--roxo-principal);
        }
        .stat-card .icon-container {
            font-size: 1.75rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--branco);
            position: relative;
        }
        .stat-card .icon-container::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }
        .stat-card .stat-info h6 {
            margin: 0;
            font-size: 0.9rem;
            color: var(--cinza-texto);
            font-weight: 500;
        }
        .stat-card .stat-info .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--preto-texto);
        }

        /* Card de Progresso */
        .progress-card {
            background: linear-gradient(135deg, #7c3aed, #581c87);
            border-radius: var(--borda-radius);
            padding: 2rem;
            color: var(--branco);
            box-shadow: var(--sombra-forte);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .progress-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }

        .progress-card .progress-info {
            position: relative;
            z-index: 2;
        }

        .progress-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .progress-bar-custom {
            background: rgba(255,255,255,0.2);
            border-radius: 50px;
            height: 20px;
            overflow: hidden;
            margin-top: 1rem;
            position: relative;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, var(--amarelo-detalhe), #ffed4e);
            height: 100%;
            border-radius: 50px;
            transition: width 1s ease-out;
            box-shadow: 0 0 20px rgba(255,215,0,0.5);
        }

        .progress-text {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        /* Ajuste para o layout principal com Grid */
        .grid-layout {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 1.5rem;
            align-items: start;
        }

        .card-header.custom-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-radius: var(--borda-radius) var(--borda-radius) 0 0;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }
        .card-header.custom-header h5 {
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header.custom-header i {
            color: var(--amarelo-detalhe);
        }

        .form-label { 
            font-weight: 500; 
            color: var(--cinza-texto);
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: var(--transicao-padrao);
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(124, 58, 237, 0.2);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 0.25rem rgba(106, 13, 173, 0.1);
            transform: translateY(-2px);
            background: white;
        }

        .table thead th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cinza-texto);
            border-bottom-width: 2px;
            background: var(--cinza-fundo);
        }
        .table tbody tr {
            transition: var(--transicao-padrao);
        }
        .table tbody tr:hover { 
            background-color: #f8f5fc;
            transform: scale(1.01);
        }
        .table .badge-answer {
            background-color: #48c048ff;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            transition: var(--transicao-padrao);
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--sombra-media);
        }
        .btn-primary { 
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            border: none;
        }
        .btn-primary:hover { 
            background: linear-gradient(135deg, var(--roxo-escuro), #3a0660);
        }
        .btn-warning { 
            background: linear-gradient(135deg, var(--amarelo-detalhe), #ffed4e);
            border: none;
            color: var(--preto-texto);
        }
        .btn-warning:hover { 
            background: linear-gradient(135deg, #ffca2c, var(--amarelo-detalhe));
            color: var(--preto-texto);
        }

        .modal-content { 
            border-radius: var(--borda-radius);
            border: none;
            box-shadow: var(--sombra-forte);
        }
        .modal-header { 
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            border-radius: var(--borda-radius) var(--borda-radius) 0 0;
        }
        .modal-header .btn-close { 
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .empty-state { 
            text-align: center; 
            padding: 3rem 2rem; 
            color: var(--cinza-texto);
        }
        .empty-state i { 
            font-size: 3rem; 
            color: var(--cinza-borda); 
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        /* Barra de busca */
        .search-bar {
            margin-bottom: 1rem;
        }
        .search-bar .input-group {
            box-shadow: var(--sombra-leve);
            transition: var(--transicao-padrao);
        }
        .search-bar .input-group:focus-within {
            box-shadow: var(--sombra-media);
            transform: translateY(-2px);
        }
        .search-bar .form-control {
            border-right: none;
        }
        .search-bar .btn-outline-secondary {
            border-left: none;
            background-color: var(--branco);
            color: var(--roxo-principal);
            border-color: #dee2e6;
        }
        .search-bar .btn-outline-secondary:hover {
            background-color: var(--roxo-principal);
            color: var(--branco);
        }

        /* Card de estatísticas de respostas */
        .stats-chart-card {
            background: var(--branco);
            border-radius: var(--borda-radius);
            padding: 1.5rem;
            box-shadow: var(--sombra-leve);
            margin-bottom: 1.5rem;
            transition: var(--transicao-padrao);
            animation: fadeInUp 0.9s ease-out;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248, 250, 252, 0.95));
            backdrop-filter: blur(10px);
        }
        .stats-chart-card:hover {
            box-shadow: var(--sombra-media);
        }
        .stats-chart-card h6 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--roxo-principal);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .progress-item {
            margin-bottom: 1rem;
        }
        .progress-item label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
        }
        .progress {
            height: 10px;
            border-radius: 10px;
            background: rgba(0,0,0,0.05);
        }
        .progress-bar {
            border-radius: 10px;
            transition: width 1s ease-out;
        }

        /* Lista de últimas perguntas */
        .recent-questions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .recent-questions-list li {
            padding: 0.75rem;
            border-bottom: 1px solid var(--cinza-borda);
            font-size: 0.85rem;
            transition: var(--transicao-padrao);
        }
        .recent-questions-list li:hover {
            background: var(--cinza-fundo);
            padding-left: 1rem;
        }
        .recent-questions-list li:last-child {
            border-bottom: none;
        }
        .recent-questions-list .question-id {
            color: var(--roxo-principal);
            font-weight: 600;
        }

        /* Alertas animados */
        .alert {
            border-radius: var(--borda-radius);
            border: none;
            box-shadow: var(--sombra-leve);
            animation: fadeInUp 0.5s ease-out;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
            .admin-section .row > div {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Elementos decorativos de fundo -->
    <div class="background-elements">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
    </div>

    

    <div class="main-content">
        <!-- CABEÇALHO INTEGRADO -->
        <header class="page-header">
            <div class="logo-container">
                <a href="gerenciar_caminho.php"><img src="../../imagens/logo-idiomas.png" alt="Logo SpeakNut"></a>
            </div>
            <div class="header-title">
                <h1>Gerenciar Quiz</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="gerenciar_caminho.php">Painel</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quiz de Nivelamento</li>
                    </ol>
                </nav>
            </div>
            <div class="profile-actions">
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></span>
                    <small>Administrador</small>
                </div>
                <i class="fas fa-user-circle"></i>
            </div>
        </header>

     

        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon-container" style="background: linear-gradient(135deg, #7c3aed, #a855f7);"><i class="fas fa-list-ol"></i></div>
                    <div class="stat-info">
                        <h6>Total de Perguntas</h6>
                        <div class="stat-number"><?php echo $total_perguntas; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon-container" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);"><i class="fas fa-language"></i></div>
                    <div class="stat-info">
                        <h6>Idioma do Quiz</h6>
                        <div class="stat-number"><?php echo htmlspecialchars($idioma_selecionado); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="icon-container" style="background: linear-gradient(135deg, #3730a3, #7c3aed);"><i class="fas fa-globe"></i></div>
                    <div class="stat-info">
                        <h6>Total de Idiomas</h6>
                        <div class="stat-number"><?php echo $total_idiomas; ?></div>
                    </div>
                </div>
            </div>
        </div>

        

             <!-- Mini divisória -->
        <div class="mini-divider"></div>

        

        <!-- ========== SEÇÃO ADMINISTRATIVA ========== -->
        <div class="admin-section">
            <div class="row">
                <!-- Card de Progresso Geral -->
                <div class="col-lg-6 mb-4">
                    <div class="progress-card">
                        <div class="progress-info">
                            <div class="progress-badge">
                                <i class="fas fa-chart-line me-2"></i>Progresso do Quiz
                            </div>
                            <h4 class="mb-3">
                                <i class="fas fa-tasks me-2"></i><?php echo $total_perguntas; ?> Perguntas
                            </h4>
                            <p class="mb-2">Meta: 50 perguntas para um quiz completo</p>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo min(($total_perguntas / 50) * 100, 100); ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <?php echo number_format(min(($total_perguntas / 50) * 100, 100), 1); ?>% completo
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Atividade Mensal -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="icon-container" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h6>Atividade Mensal</h6>
                            <div class="stat-number"><?php echo $atividade_mensal; ?></div>
                            <small>Perguntas este mês</small>
                        </div>
                    </div>
                </div>

                <!-- Card de Qualidade -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="icon-container" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h6>Qualidade</h6>
                            <div class="stat-number"><?php echo ($total_perguntas >= 20) ? 'Alta' : (($total_perguntas >= 10) ? 'Média' : 'Básica'); ?></div>
                            <small>Nível do Quiz</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

   

        <!-- Divisória decorativa -->
        <div class="section-divider"></div>

        <!-- ========== ESTATÍSTICAS DETALHADAS ========== -->
        <div class="row mb-4">
            <!-- Card: Distribuição de Respostas Corretas -->
            <div class="col-lg-6 mb-4">
                <div class="stats-chart-card">
                    <h6><i class="fas fa-chart-bar"></i>Distribuição de Respostas Corretas</h6>
                    <?php if (!empty($stats_respostas)): ?>
                        <?php 
                        $cores = ['A' => '#7c3aed', 'B' => '#fbbf24', 'C' => '#28a745', 'D' => '#dc3545'];
                        foreach ($stats_respostas as $stat): 
                            $percentual = ($total_perguntas > 0) ? round(($stat['quantidade'] / $total_perguntas) * 100, 1) : 0;
                        ?>
                        <div class="progress-item">
                            <label>
                                <span><i class="fas fa-check-circle me-1"></i>Alternativa <?php echo htmlspecialchars($stat['resposta_correta']); ?></span>
                                <span><strong><?php echo $stat['quantidade']; ?></strong> perguntas (<?php echo $percentual; ?>%)</span>
                            </label>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentual; ?>%; background: <?php echo $cores[$stat['resposta_correta']] ?? '#6c757d'; ?>;" aria-valuenow="<?php echo $percentual; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Nenhuma estatística disponível.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card: Distribuição por Dificuldade -->
            <div class="col-lg-6 mb-4">
                <div class="stats-chart-card">
                    <h6><i class="fas fa-signal"></i>Distribuição por Dificuldade</h6>
                    <div class="progress-item">
                        <label>
                            <span><i class="fas fa-circle text-success me-1"></i>Fácil</span>
                            <span><strong><?php echo $perguntas_faceis; ?></strong> perguntas</span>
                        </label>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($total_perguntas > 0) ? ($perguntas_faceis / $total_perguntas) * 100 : 0; ?>%" aria-valuenow="<?php echo $perguntas_faceis; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_perguntas; ?>"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <label>
                            <span><i class="fas fa-circle text-warning me-1"></i>Médio</span>
                            <span><strong><?php echo $perguntas_medias; ?></strong> perguntas</span>
                        </label>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($total_perguntas > 0) ? ($perguntas_medias / $total_perguntas) * 100 : 0; ?>%" aria-valuenow="<?php echo $perguntas_medias; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_perguntas; ?>"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <label>
                            <span><i class="fas fa-circle text-danger me-1"></i>Difícil</span>
                            <span><strong><?php echo $perguntas_dificeis; ?></strong> perguntas</span>
                        </label>
                        <div class="progress">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($total_perguntas > 0) ? ($perguntas_dificeis / $total_perguntas) * 100 : 0; ?>%" aria-valuenow="<?php echo $perguntas_dificeis; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_perguntas; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divisória decorativa -->
        <div class="section-divider"></div>

        <!-- ALERTAS -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- LAYOUT PRINCIPAL COM GRID -->
        <div class="grid-layout">
            <!-- COLUNA DA TABELA DE PERGUNTAS -->
            <div class="card">
                <div class="card-header custom-header">
                    <h5><i class="fas fa-list-ul"></i> Perguntas Existentes</h5>
                </div>
                
                <!-- ========== BARRA DE BUSCA ========== -->
                <div class="card-body pb-0">
                    <div class="search-bar">
                        <form method="GET" action="gerenciar_quiz_nivelamento.php">
                            <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_selecionado); ?>">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" name="busca" placeholder="Buscar perguntas..." value="<?php echo htmlspecialchars($termo_busca); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <?php if (!empty($termo_busca)): ?>
                                <a href="gerenciar_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma_selecionado); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Limpar
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pergunta</th>
                                <th>Resposta</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($quiz_perguntas)): ?>
                                <?php foreach ($quiz_perguntas as $pergunta): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($pergunta['id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pergunta['pergunta']); ?></td>
                                        <td><span class="badge rounded-pill badge-answer"><?php echo htmlspecialchars($pergunta['resposta_correta']); ?></span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editQuizModal_<?php echo $pergunta['id']; ?>" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?php echo htmlspecialchars($pergunta['id']); ?>" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4"><div class="empty-state"><i class="fas fa-question-circle"></i><h6>Nenhuma pergunta encontrada.</h6><p class="text-muted">Adicione sua primeira pergunta para começar!</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COLUNA DO FORMULÁRIO DE ADIÇÃO -->
            <div class="card">
                <div class="card-header custom-header">
                    <h5><i class="fas fa-plus-circle"></i> Adicionar Nova Pergunta</h5>
                </div>
                <div class="card-body p-4">
                    <form action="gerenciar_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma_selecionado); ?>" method="POST" id="formAdicionarPergunta">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="mb-3">
                            <label for="pergunta" class="form-label"><i class="fas fa-question-circle me-1"></i>Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" rows="3" required placeholder="Digite a pergunta aqui..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_a" class="form-label"><i class="fas fa-a me-1"></i>Alternativa A</label>
                            <input type="text" class="form-control" id="alternativa_a" name="alternativa_a" required placeholder="Digite a alternativa A">
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_b" class="form-label"><i class="fas fa-b me-1"></i>Alternativa B</label>
                            <input type="text" class="form-control" id="alternativa_b" name="alternativa_b" required placeholder="Digite a alternativa B">
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_c" class="form-label"><i class="fas fa-c me-1"></i>Alternativa C</label>
                            <input type="text" class="form-control" id="alternativa_c" name="alternativa_c" required placeholder="Digite a alternativa C">
                        </div>
                        <div class="mb-3">
                            <label for="alternativa_d" class="form-label"><i class="fas fa-d me-1"></i>Alternativa D</label>
                            <input type="text" class="form-control" id="alternativa_d" name="alternativa_d" required placeholder="Digite a alternativa D">
                        </div>
                        <div class="mb-4">
                            <label for="resposta_correta" class="form-label"><i class="fas fa-check-double me-1"></i>Resposta Correta</label>
                            <select id="resposta_correta" name="resposta_correta" class="form-select" required>
                                <option value="" disabled selected>Selecione a resposta correta...</option>
                                <option value="A">✓ Alternativa A</option>
                                <option value="B">✓ Alternativa B</option>
                                <option value="C">✓ Alternativa C</option>
                                <option value="D">✓ Alternativa D</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-save me-2"></i> Salvar Pergunta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAIS -->
    <?php foreach ($quiz_perguntas as $pergunta): ?>
    <div class="modal fade" id="editQuizModal_<?php echo $pergunta['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="editar_quiz.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($pergunta['id']); ?>">
                    <input type="hidden" name="idioma" value="<?php echo htmlspecialchars($idioma_selecionado); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Editar Pergunta #<?php echo $pergunta['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pergunta</label>
                            <textarea class="form-control" name="pergunta" rows="3" required><?php echo htmlspecialchars($pergunta['pergunta']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alternativa A</label>
                                <input type="text" class="form-control" name="alternativa_a" value="<?php echo htmlspecialchars($pergunta['alternativa_a']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alternativa B</label>
                                <input type="text" class="form-control" name="alternativa_b" value="<?php echo htmlspecialchars($pergunta['alternativa_b']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alternativa C</label>
                                <input type="text" class="form-control" name="alternativa_c" value="<?php echo htmlspecialchars($pergunta['alternativa_c']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alternativa D</label>
                                <input type="text" class="form-control" name="alternativa_d" value="<?php echo htmlspecialchars($pergunta['alternativa_d']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resposta Correta</label>
                            <select name="resposta_correta" class="form-select" required>
                                <option value="A" <?php echo ($pergunta['resposta_correta'] == 'A') ? 'selected' : ''; ?>>Alternativa A</option>
                                <option value="B" <?php echo ($pergunta['resposta_correta'] == 'B') ? 'selected' : ''; ?>>Alternativa B</option>
                                <option value="C" <?php echo ($pergunta['resposta_correta'] == 'C') ? 'selected' : ''; ?>>Alternativa C</option>
                                <option value="D" <?php echo ($pergunta['resposta_correta'] == 'D') ? 'selected' : ''; ?>>Alternativa D</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a pergunta de ID <strong id="deleteItemIdText"></strong>?</p>
                    <p class="text-danger fw-bold mb-0"><i class="fas fa-info-circle me-1"></i>Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <a id="deleteConfirmBtn" href="#" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i> Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de confirmação de exclusão
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        if (confirmDeleteModal) {
            confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const itemId = button.getAttribute('data-id');
                const deleteItemIdText = confirmDeleteModal.querySelector('#deleteItemIdText');
                const deleteConfirmBtn = confirmDeleteModal.querySelector('#deleteConfirmBtn');
                deleteItemIdText.textContent = `#${itemId}`;
                const url = `gerenciar_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma_selecionado); ?>&acao=excluir&id=${itemId}`;
                deleteConfirmBtn.setAttribute('href', url);
            });
        }

        // Auto-dismiss de alertas
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                new bootstrap.Alert(alert).close();
            }, 5000);
        });

        // Animação de entrada dos cards
        const cards = document.querySelectorAll('.stat-card, .card, .progress-card, .stats-chart-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>
</html>