<?php
session_start();
// Inclua o arquivo de conexão em POO
include_once __DIR__ . "/../../conexao.php";

// Crie uma instância da classe Database para obter a conexão
$database = new Database();
$conn = $database->conn;

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id_usuario"])) {
    // Feche a conexão antes de redirecionar
    $database->closeConnection();
    header("Location: ../../index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$idioma_escolhido = null;
$nivel_usuario = null;
$nome_usuario = $_SESSION["nome_usuario"] ?? "usuário";
$mostrar_selecao_idioma = false;

// Buscar foto do usuário
$sql_foto_usuario = "SELECT foto_perfil FROM usuarios WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto_usuario);
$stmt_foto->bind_param("i", $id_usuario);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result()->fetch_assoc();
$stmt_foto->close();

$foto_usuario = $resultado_foto['foto_perfil'] ?? null;

// Buscar idiomas disponíveis do banco de dados
$sql_idiomas_disponiveis = "SELECT nome_idioma FROM idiomas ORDER BY nome_idioma ASC";
$result_idiomas = $conn->query($sql_idiomas_disponiveis);
$idiomas_disponiveis = [];
$idiomas_display = []; // Para exibição com acentos
if ($result_idiomas && $result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $nome_original = $row['nome_idioma'];
        $nome_normalizado = str_replace(['ê', 'ã'], ['e', 'a'], $nome_original);
        $idiomas_disponiveis[] = $nome_normalizado;
        $idiomas_display[$nome_normalizado] = $nome_original; // Mapear para exibição
    }
}

// Buscar todos os idiomas que o usuário já estudou
$sql_idiomas_usuario = "SELECT idioma, nivel, data_inicio, ultima_atividade FROM progresso_usuario WHERE id_usuario = ? ORDER BY ultima_atividade DESC";
$stmt_idiomas_usuario = $conn->prepare($sql_idiomas_usuario);
$stmt_idiomas_usuario->bind_param("i", $id_usuario);
$stmt_idiomas_usuario->execute();
$idiomas_usuario = $stmt_idiomas_usuario->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas_usuario->close();

// Processa seleção de idioma para usuários sem progresso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["idioma_inicial"])) {
    $idioma_inicial = $_POST["idioma_inicial"];
    $nivel_inicial = "A1";
   
    // Insere progresso inicial para o usuário
    $sql_insert_progresso = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, data_inicio, ultima_atividade) VALUES (?, ?, ?, NOW(), NOW())";
    $stmt_insert = $conn->prepare($sql_insert_progresso);
    $stmt_insert->bind_param("iss", $id_usuario, $idioma_inicial, $nivel_inicial);
   
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        // Redireciona para o quiz de nivelamento
        $database->closeConnection();
        header("Location: ../../quiz.php?idioma=$idioma_inicial");
        exit();
    } else {
        $erro_selecao = "Erro ao registrar idioma. Tente novamente.";
    }
    $stmt_insert->close();
}

// Processa troca de idioma (mantém para compatibilidade com formulários antigos)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["trocar_idioma"])) {
    $novo_idioma = $_POST["novo_idioma"];
    
    // Verifica se o usuário já tem progresso neste idioma
    $sql_check_progresso = "SELECT COUNT(*) as count FROM progresso_usuario WHERE id_usuario = ? AND idioma = ?";
    $stmt_check = $conn->prepare($sql_check_progresso);
    $stmt_check->bind_param("is", $id_usuario, $novo_idioma);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($result_check['count'] > 0) {
        // Usuário já tem progresso, apenas atualiza última atividade
        $sql_update_atividade = "UPDATE progresso_usuario SET ultima_atividade = NOW() WHERE id_usuario = ? AND idioma = ?";
        $stmt_update = $conn->prepare($sql_update_atividade);
        $stmt_update->bind_param("is", $id_usuario, $novo_idioma);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Redireciona para o painel com o novo idioma
        $database->closeConnection();
        header("Location: painel.php");
        exit();
    } else {
        // Usuário não tem progresso, criar entrada inicial e redirecionar para quiz
        $sql_insert_novo = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, data_inicio, ultima_atividade) VALUES (?, ?, 'A1', NOW(), NOW())";
        $stmt_insert_novo = $conn->prepare($sql_insert_novo);
        $stmt_insert_novo->bind_param("is", $id_usuario, $novo_idioma);
        
        if ($stmt_insert_novo->execute()) {
            $stmt_insert_novo->close();
            $database->closeConnection();
            header("Location: ../../quiz.php?idioma=$novo_idioma");
            exit();
        } else {
            $erro_troca = "Erro ao adicionar novo idioma. Tente novamente.";
        }
        $stmt_insert_novo->close();
    }
}

// Tenta obter o idioma e o nível da URL (se veio do pop-up de resultados)
if (isset($_GET["idioma"]) && isset($_GET["nivel_escolhido"])) {
    $idioma_escolhido = $_GET["idioma"];
    $nivel_usuario = $_GET["nivel_escolhido"];
   
    // Atualiza o nível do usuário no banco de dados com a escolha final
    $sql_update_nivel = "UPDATE progresso_usuario SET nivel = ? WHERE id_usuario = ? AND idioma = ?";
    $stmt_update_nivel = $conn->prepare($sql_update_nivel);
    $stmt_update_nivel->bind_param("sis", $nivel_usuario, $id_usuario, $idioma_escolhido);
    $stmt_update_nivel->execute();
    $stmt_update_nivel->close();

} else {
    // Limpar TODOS os registros inválidos
    $sql_clean = "DELETE FROM progresso_usuario WHERE id_usuario = ? AND (idioma IS NULL OR idioma = '' OR idioma LIKE '%object%' OR idioma LIKE '%PointerEvent%' OR idioma LIKE '%[%' OR LENGTH(idioma) > 50)";
    $stmt_clean = $conn->prepare($sql_clean);
    $stmt_clean->bind_param("i", $id_usuario);
    $stmt_clean->execute();
    $stmt_clean->close();
    
    // Busca idioma válido
    $sql_progresso = "SELECT idioma, nivel FROM progresso_usuario WHERE id_usuario = ? AND idioma REGEXP '^[a-zA-Z]+$' ORDER BY ultima_atividade DESC LIMIT 1";
    $stmt_progresso = $conn->prepare($sql_progresso);
    $stmt_progresso->bind_param("i", $id_usuario);
    $stmt_progresso->execute();
    $resultado = $stmt_progresso->get_result()->fetch_assoc();
    $stmt_progresso->close();

    if ($resultado && preg_match('/^[a-zA-Z]+$/', $resultado["idioma"]) && !empty($resultado["idioma"])) {
        $idioma_escolhido = $resultado["idioma"];
        $nivel_usuario = $resultado["nivel"];
    } else {
        $mostrar_selecao_idioma = true;
    }
}

// Busca apenas a primeira unidade se o usuário tem progresso
if (!$mostrar_selecao_idioma) {
    $sql_unidades = "SELECT * FROM unidades WHERE idioma = ? AND nivel = ? ORDER BY numero_unidade ASC LIMIT 1";
    $stmt_unidades = $conn->prepare($sql_unidades);
    $stmt_unidades->bind_param("ss", $idioma_escolhido, $nivel_usuario);
    $stmt_unidades->execute();
    $unidades = $stmt_unidades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_unidades->close();
}

// Feche a conexão usando o método da classe
$database->closeConnection();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - <?php echo htmlspecialchars($idioma_escolhido && preg_match('/^[a-zA-Z]+$/', $idioma_escolhido) ? $idioma_escolhido : 'Idioma'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="painel.css" rel="stylesheet">
    <link href="exercicios.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Paleta de Cores - MESMAS DO ADMIN */
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --amarelo-botao: #ffd700;
            --amarelo-hover: #e7c500;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --cinza-medio: #dee2e6;
        }

        /* Estilos Gerais do Corpo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
        }

        /* SIDEBAR FIXO */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .profile-avatar-sidebar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid var(--amarelo-detalhe);
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-avatar-sidebar:has(.profile-avatar-img) i {
            display: none;
        }

        .profile-avatar-sidebar i {
            font-size: 3.5rem;
            color: var(--amarelo-detalhe);
        }

        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--branco);
            font-size: 1.1rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.3;
        }

        .sidebar .profile small {
            color: var(--cinza-claro);
            font-size: 0.9rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.2;
            margin-top: 5px;
        }

        .sidebar .list-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .sidebar .list-group-item {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .list-group-item:hover {
            background-color: var(--roxo-escuro);
            cursor: pointer;
            color: var(--branco);
        }

        .sidebar .list-group-item.active {
            background-color: var(--roxo-escuro) !important;
            color: var(--branco) !important;
            font-weight: 600;
            border-left: 4px solid var(--amarelo-detalhe);
        }

        .sidebar .list-group-item i {
            color: var(--amarelo-detalhe);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            position: relative;
        }

        /* Menu Hamburguer */
        .menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--roxo-principal);
            color: var(--roxo-principal) !important;
            font-size: 1.5rem;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-toggle:hover {
            color: var(--roxo-escuro) !important;
            transform: scale(1.1);
        }

        /* Quando a sidebar está ativa */
        body:has(.sidebar.active) .menu-toggle,
        .sidebar.active ~ .menu-toggle {
            color: var(--amarelo-detalhe) !important;
        }

        body:has(.sidebar.active) .menu-toggle:hover,
        .sidebar.active ~ .menu-toggle:hover {
            color: var(--amarelo-hover) !important;
        }

        /* Overlay para mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: flex !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 1050;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding-top: 80px;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
            
            .main-content {
                padding: 15px 10px;
            }
            
            .card-header .row {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .card-header .col-md-8 {
                flex: 1;
                min-width: 200px;
            }
            
            .card-header .col-md-4 {
                flex: 0 0 auto;
            }
            
            .card-body .row {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .card-body .col-md-8 {
                flex: 1;
                min-width: 200px;
            }
            
            .card-body .col-md-4 {
                flex: 0 0 auto;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 10px 8px;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header .row,
            .card-body .row {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .card-header .col-md-8,
            .card-header .col-md-4,
            .card-body .col-md-8,
            .card-body .col-md-4 {
                flex: none;
                width: 100%;
            }
            
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .d-flex.gap-2 .btn {
                width: 100%;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group .btn {
                width: 100%;
            }
        }

        .sidebar .profile i {
            font-size: 4rem;
            color: var(--amarelo-detalhe);
            margin-bottom: 10px;
        }

        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 0;
            color: var(--branco);
        }

        .sidebar .profile small {
            color: var(--cinza-claro);
        }

        .sidebar .list-group {
            width: 100%;
        }

        .sidebar .list-group-item {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .list-group-item:hover {
            background-color: var(--roxo-escuro);
            cursor: pointer;
        }

        .sidebar .list-group-item.active {
            background-color: var(--roxo-escuro) !important;
            color: var(--branco) !important;
            font-weight: 600;
            border-left: 4px solid var(--amarelo-detalhe);
        }

        .sidebar .list-group-item i {
            color: var(--amarelo-detalhe);
            width: 20px; /* Alinhamento dos ícones */
            text-align: center;
        }

        /* Melhorias para mobile pequeno */
        @media (max-width: 480px) {
            .sidebar {
                width: 100vw;
            }
            
            .main-content {
                padding: 8px 5px;
            }
            
            .card {
                margin-bottom: 0.75rem;
            }
            
            .card-header {
                padding: 1rem 0.75rem;
            }
            
            .card-header h2 {
                font-size: 1.3rem;
                line-height: 1.2;
            }
            
            .card-header p {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .bloco-card {
                margin-bottom: 0.75rem;
            }
            
            .bloco-card .card-body {
                padding: 0.75rem;
            }
            
            .bloco-card h5 {
                font-size: 1rem;
                line-height: 1.2;
            }
            
            .bloco-card .card-text {
                font-size: 0.85rem;
                line-height: 1.3;
            }
            
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .btn-sm {
                padding: 0.375rem 0.5rem;
                font-size: 0.8rem;
            }
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            position: relative; /* Necessário para z-index funcionar */
            z-index: 1; /* Garante que o conteúdo principal não se sobreponha a modais ou outros elementos */
        }

        /* Estilos para exercícios de listening */

/* INÍCIO DOS NOVOS ESTILOS PARA TEORIAS */
/* Estilos para o Modal de Teorias */
.modal-teorias .modal-header {
    background-color: var(--roxo-principal);
    color: var(--branco);
    border-bottom: none;
}

.modal-teorias {
    z-index: 1060; /* z-index maior que o da sidebar */
}

.modal-teorias .modal-title {
    font-weight: 600;
}

.modal-teorias .modal-body {
    padding: 20px;
    background-color: var(--cinza-claro);
}

/* Estilos para o Card de Teoria */
.teoria-card {
    border-radius: 15px;
    padding: 20px;
    color: var(--branco); /* Texto branco para contraste com o gradiente */
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%; /* Garante que todos os cards tenham a mesma altura */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.teoria-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.teoria-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.teoria-numero {
    font-size: 1.5rem;
    font-weight: 700;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 5px 12px;
    border-radius: 10px;
    line-height: 1;
}

.teoria-nivel {
    font-size: 1rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 5px;
    background-color: var(--amarelo-detalhe);
    color: var(--preto-texto);
}

.teoria-card-body {
    flex-grow: 1;
}

.teoria-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 5px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.teoria-resumo {
    font-size: 0.9rem;
    opacity: 0.9;
}

.teoria-card-footer {
    text-align: right;
    font-size: 1.2rem;
}

.teoria-card-footer i {
    color: rgba(255, 255, 255, 0.8);
}

/* Estilos para o Modal de Conteúdo da Teoria */
.modal-teoria-conteudo .modal-header {
    background-color: var(--roxo-escuro);
    color: var(--branco);
    border-bottom: none;
}

.modal-teoria-conteudo .modal-body {
    font-size: 1.1rem;
    line-height: 1.8;
    color: var(--preto-texto);
}

/* Estilização do Conteúdo (para o formatarConteudoTeoria) */
.conteudo-teoria h1, .conteudo-teoria h2, .conteudo-teoria h3 {
    color: var(--roxo-principal);
    border-bottom: 2px solid var(--amarelo-detalhe);
    padding-bottom: 5px;
    margin-top: 20px;
    margin-bottom: 15px;
}

.conteudo-teoria p {
    margin-bottom: 15px;
}

.conteudo-teoria ul, .conteudo-teoria ol {
    padding-left: 25px;
    margin-bottom: 15px;
}

.conteudo-teoria code {
    background-color: #f0f0f0;
    padding: 2px 5px;
    border-radius: 5px;
    font-family: monospace;
}

.conteudo-teoria pre {
    background-color: #2d2d2d;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 20px;
}
/* FIM DOS NOVOS ESTILOS PARA TEORIAS */

        .audio-player-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
        }
        
        /* Garantir que inputs funcionem corretamente */
        #respostaCompletar, #respostaTextoLivre {
            pointer-events: auto !important;
            cursor: text !important;
            user-select: text !important;
        }
        
        .form-control {
            pointer-events: auto !important;
            cursor: text !important;
        }

        .audio-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
        }
        
        .audio-controls .btn {
            pointer-events: auto;
            cursor: pointer;
        }

        .listening-options {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-option {
            text-align: left;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
            pointer-events: auto;
        }

        .btn-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .btn-option.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        
        /* Garantir que botões de resposta sejam clicáveis */
        .btn-resposta {
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: none;
        }
        
        .btn-resposta:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-resposta.selected {
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        
        audio {
            pointer-events: auto;
            cursor: pointer;
        }
        
        audio::-webkit-media-controls {
            pointer-events: auto;
        }
        
        audio::-webkit-media-controls-panel {
            pointer-events: auto;
        }
        
        .tts-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .tts-container .btn {
            min-width: 200px;
            font-size: 1.1rem;
            padding: 12px 24px;
        }

        /* Cards de blocos com estados - Layout quadrado organizado */
        .bloco-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            min-height: 220px;
            max-height: 220px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* Container dos blocos em grid organizado */
        .blocos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 10px;
        }
        
        /* Ajustes para diferentes tamanhos de tela */
        @media (min-width: 768px) {
            .blocos-container {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 25px;
            }
        }
        
        @media (min-width: 1200px) {
            .blocos-container {
                grid-template-columns: repeat(3, 1fr);
                max-width: 1000px;
                margin: 0 auto;
            }
        }
        
        .bloco-card-disponivel {
            cursor: pointer;
            border-color: #007bff;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
        }
        
        .bloco-card-disponivel:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
            border-color: #0056b3;
        }
        
        .bloco-card-concluido {
            cursor: pointer;
            border-color: #28a745;
            background: linear-gradient(135deg, #ffffff 0%, #f8fff8 100%);
        }
        
        .bloco-card-concluido:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
        }
        
        .bloco-card-bloqueado {
            cursor: not-allowed;
            border-color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            opacity: 0.6;
        }
        
        .bloco-card-bloqueado .card-title,
        .bloco-card-bloqueado .card-text {
            color: #6c757d !important;
        }
        
        /* Melhorias visuais para os cards */
        .bloco-card .card-body {
            padding: 1.25rem;
            height: 100%;
        }
        
        .bloco-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }
        
        .bloco-card .card-text {
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        /* Área de descrição com scroll */
        .bloco-descricao {
            max-height: 60px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
            margin-bottom: 1rem;
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }
        
        .bloco-descricao::-webkit-scrollbar {
            width: 4px;
        }
        
        .bloco-descricao::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .bloco-descricao::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 2px;
        }
        
        .bloco-descricao::-webkit-scrollbar-thumb:hover {
            background-color: #999;
        }
        
        /* Indicador de scroll */
        .scroll-indicator {
            position: absolute;
            bottom: 5px;
            right: 8px;
            font-size: 0.7rem;
            color: #999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .bloco-descricao:hover + .scroll-indicator,
        .bloco-descricao.has-scroll + .scroll-indicator {
            opacity: 1;
        }
        
        .bloco-card .bloco-icon {
            transition: transform 0.3s ease;
        }
        
        .bloco-card:hover .bloco-icon {
            transform: scale(1.1);
        }
        
        /* Espaçamento uniforme */
        .bloco-item {
            display: flex;
            flex-direction: column;
        }
        
        .bloco-item .card {
            flex: 1;
        }
        
        /* Responsividade para cards de blocos */
        @media (max-width: 768px) {
            .bloco-card {
                min-height: 200px;
                max-height: 200px;
            }
            
            .blocos-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                padding: 5px;
            }
            
            .bloco-card .bloco-icon {
                font-size: 1.5rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .bloco-card {
                min-height: 180px;
                max-height: 180px;
            }
            
            .blocos-container {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .bloco-card .card-body {
                text-align: center;
                padding: 1rem;
            }
            
            .bloco-card .card-title {
                font-size: 0.9rem;
            }
            
            .bloco-card .card-text {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 400px) {
            .blocos-container {
                grid-template-columns: 1fr;
            }
            
            .bloco-card {
                min-height: 160px;
                max-height: 160px;
            }
        }
        
        /* Container de unidades */
        .unidade-container {
            border-left: 4px solid var(--roxo-principal);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .unidade-container .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 2px solid var(--amarelo-detalhe);
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        
        /* Badges de status dos blocos */
        .badge {
            font-size: 0.7rem;
            padding: 0.35rem 0.6rem;
        }
        
        /* Progress bar menor */
        .bloco-card .progress {
            height: 6px;
        }
        
        /* Texto menor nos blocos */
        .bloco-card small {
            font-size: 0.75rem;
        }

        /* Estilos para cards de teoria */
        .teoria-card {
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .teoria-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .teoria-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .teoria-card:hover::before {
            left: 100%;
        }
        
        .teoria-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .teoria-numero {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .teoria-nivel {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .teoria-card-body {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .teoria-titulo {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        .teoria-resumo {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.4;
            margin: 0;
        }
        
        .teoria-card-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .teoria-card-footer i {
            font-size: 1.2rem;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }
        
        .teoria-card:hover .teoria-card-footer i {
            transform: translateX(5px);
            opacity: 1;
        }
        
        /* Estilos para conteúdo da teoria - Design limpo */
        .teoria-conteudo {
            max-height: 70vh;
            overflow-y: auto;
            padding: 2rem;
            background: #ffffff;
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: #333;
            font-size: 1rem;
        }
        
        /* Estilos para tópicos numerados - 2 colunas */
        .topicos-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin: 1rem 0;
        }
        
        .topico-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            min-height: fit-content;
        }
        
        .topico-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        /* Tópicos com tabelas ocupam largura total */
        .topico-item.com-tabela {
            grid-column: 1 / -1;
            max-width: none;
        }
        
        .topico-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .topico-numero {
            background: var(--roxo-principal);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .topico-titulo {
            color: var(--roxo-principal);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .topico-conteudo {
            color: #555;
            line-height: 1.6;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .topicos-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Texto simples sem tópicos */
        .teoria-texto-simples {
            white-space: pre-wrap;
            line-height: 1.7;
            color: #333;
        }

        /* Estilos para tabelas nas teorias */
        .teoria-tabela {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .teoria-tabela h5 {
            color: var(--roxo-principal);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .teoria-tabela table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 0.9rem;
        }

        .teoria-tabela th {
            background: var(--roxo-principal);
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            border: none;
            font-size: 0.85rem;
        }

        .teoria-tabela td {
            padding: 8px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 0.85rem;
        }

        .teoria-tabela tr:last-child td {
            border-bottom: none;
        }

        .teoria-tabela tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .teoria-tabela tr:hover {
            background-color: #e3f2fd;
        }

        /* Layout melhorado para tópicos com tabelas */
        .topico-content-wrapper {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .topico-texto {
            flex: 1;
            min-width: 0;
        }

        .topico-tabelas {
            flex: 1;
            min-width: 0;
        }

        @media (max-width: 768px) {
            .topico-content-wrapper {
                flex-direction: column;
            }
        }

        /* Estilos para feedback de progresso */
        .progresso-atualizado {
            animation: pulse-success 2s ease-in-out;
        }

        @keyframes pulse-success {
            0% { background-color: transparent; }
            50% { background-color: rgba(40, 167, 69, 0.1); }
            100% { background-color: transparent; }
        }

        .bloco-card-concluido {
            border-left: 4px solid #28a745 !important;
        }

        .bloco-card-disponivel {
            border-left: 4px solid #007bff !important;
        }

        .bloco-card-bloqueado {
            border-left: 4px solid #6c757d !important;
        }

        /* Navbar igual ao admin */
        .navbar {
            background-color: transparent !important;
            border-bottom: 3px solid var(--amarelo-detalhe);
            box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
        }

        .navbar-brand {
            margin-left: auto;
            margin-right: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
        }
        
        .navbar-brand .logo-header {
            height: 70px;
            width: auto;
            display: block;
        }

        .settings-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
            padding: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .settings-icon:hover {
            color: var(--roxo-escuro) !important;
            transform: rotate(90deg);
            background: rgba(255, 255, 255, 0.2);
        }

        .logout-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .logout-icon:hover {
            color: var(--roxo-escuro) !important;
            transform: translateY(-2px);
        }

        /* SIDEBAR FIXO */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .sidebar .profile .profile-avatar-sidebar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid var(--amarelo-detalhe);
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar .profile .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .sidebar .profile .profile-avatar-sidebar i {
            font-size: 3.5rem;
            color: var(--amarelo-detalhe);
        }

        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--branco);
            font-size: 1.1rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.3;
        }

        .sidebar .profile small {
            color: var(--cinza-claro);
            font-size: 0.9rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.2;
            margin-top: 5px;
        }

        .sidebar .list-group {
            width: 100%;
        }

        .sidebar .list-group-item {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .list-group-item:hover {
            background-color: var(--roxo-escuro);
            cursor: pointer;
        }

        .sidebar .list-group-item.active {
            background-color: var(--roxo-escuro) !important;
            color: var(--branco) !important;
            font-weight: 600;
            border-left: 4px solid var(--amarelo-detalhe);
        }

        .sidebar .list-group-item i {
            color: var(--amarelo-detalhe);
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Menu Hamburguer */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--roxo-principal) !important;
            font-size: 1.5rem;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            color: var(--roxo-escuro) !important;
            transform: scale(1.1);
        }

        /* Quando a sidebar está ativa */
        body:has(.sidebar.active) .menu-toggle,
        .sidebar.active ~ .menu-toggle {
            color: var(--amarelo-detalhe) !important;
        }

        body:has(.sidebar.active) .menu-toggle:hover,
        .sidebar.active ~ .menu-toggle:hover {
            color: var(--amarelo-hover) !important;
        }

        /* Overlay para mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f2e9f9;
            color: var(--roxo-escuro);
            transform: translateX(4px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fceaea;
            color: #b02a37;
            transform: translateX(4px);
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
            }
        }

        /* Botão de pesquisa customizado */
        .btn-search-custom {
            background: transparent;
            border: 1px solid white;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-search-custom:hover {
            background: transparent !important;
            border-color: white;
            color: white;
            transform: scale(1.05);
        }

        /* Estilos para preview de flashcards */
        .flashcard-preview {
            width: 100%;
            height: 200px;
            perspective: 1000px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flashcard-preview.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .flashcard-front {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .flashcard-back {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            transform: rotateY(180deg);
            font-weight: bold;
            font-size: 1.1rem;
        }

    </style>
    
    <script>
        // Verificar se está em HTTPS ou localhost
        function verificarHTTPS() {
            const isHTTPS = location.protocol === 'https:';
            const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            
            if (!isHTTPS && !isLocalhost) {
                const warning = document.getElementById('https-warning');
                if (warning) {
                    warning.style.display = 'block';
                }
                console.warn('Microfone pode não funcionar sem HTTPS');
            }
        }
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            verificarHTTPS();
            
            // Menu Hamburguer Functionality - VERSÃO CORRIGIDA
            initializeHamburgerMenu();
            
            // O sistema integrado corrigido já inicializa automaticamente
            console.log('Painel carregado com sistema de exercícios corrigido');
            
            // Inicializar indicadores de scroll após carregar blocos
            setTimeout(initScrollIndicators, 1000);
        });
        
        // Função para inicializar menu hambúrguer
        function initializeHamburgerMenu() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            console.log('Inicializando menu hambúrguer:', {
                menuToggle: !!menuToggle,
                sidebar: !!sidebar,
                sidebarOverlay: !!sidebarOverlay
            });
            
            if (menuToggle && sidebar) {
                // Remover listeners existentes
                menuToggle.replaceWith(menuToggle.cloneNode(true));
                const newMenuToggle = document.getElementById('menuToggle');
                
                newMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Menu toggle clicado');
                    
                    sidebar.classList.toggle('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('active');
                    }
                    
                    console.log('Sidebar ativo:', sidebar.classList.contains('active'));
                });
                
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        console.log('Overlay clicado - fechando menu');
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                    });
                }
                
                // Fechar menu ao clicar em um link (mobile)
                const sidebarLinks = sidebar.querySelectorAll('.list-group-item');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            console.log('Link clicado - fechando menu mobile');
                            sidebar.classList.remove('active');
                            if (sidebarOverlay) {
                                sidebarOverlay.classList.remove('active');
                            }
                        }
                    });
                });
                
                // Fechar menu com ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('active');
                        }
                    }
                });
                
                console.log('Menu hambúrguer inicializado com sucesso');
            } else {
                console.error('Elementos do menu não encontrados:', {
                    menuToggle: !!menuToggle,
                    sidebar: !!sidebar
                });
            }
        }
        
        // Função para inicializar indicadores de scroll
        function initScrollIndicators() {
            document.querySelectorAll('.bloco-descricao').forEach(descricao => {
                if (descricao.scrollHeight > descricao.clientHeight) {
                    descricao.classList.add('has-scroll');
                }
            });
        }
    </script>
</head>

<body>
    <!-- Menu Hamburguer -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Navbar igual ao admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil_usuario.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
                <a href="../../logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="profile">
            <?php if ($foto_usuario): ?>
                <div class="profile-avatar-sidebar">
                    <img src="../../<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto de perfil" class="profile-avatar-img">
                </div>
            <?php else: ?>
                <div class="profile-avatar-sidebar">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <h5><?php echo htmlspecialchars($nome_usuario); ?></h5>
            <small>Usuário</small>
        </div>

        <div class="list-group">
            <a href="painel.php" class="list-group-item active">
                <i class="fas fa-home"></i> Início
            </a>
            <a href="flashcards.php" class="list-group-item">
                <i class="fas fa-layer-group"></i> Flash Cards
            </a>
            <div class="list-group-item">
                <div class="dropdown">
                    <a href="#" class="text-decoration-none text-white d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-language me-2" style="color: var(--amarelo-detalhe); width: 20px; text-align: center;"></i> Trocar Idioma
                    </a>
                    <ul class="dropdown-menu">
                        <?php 
                        $idiomas_ja_estudados = array_column($idiomas_usuario, 'idioma');
                        $tem_outros_idiomas = false;
                        $tem_novos_idiomas = false;
                        
                        // Verificar se há outros idiomas já estudados
                        foreach ($idiomas_usuario as $idioma_user) {
                            if ($idioma_user['idioma'] !== $idioma_escolhido) {
                                $tem_outros_idiomas = true;
                                break;
                            }
                        }
                        
                        // Verificar se há novos idiomas disponíveis (excluindo todos os já estudados)
                        foreach ($idiomas_disponiveis as $idioma_disponivel) {
                            if (!in_array($idioma_disponivel, $idiomas_ja_estudados) && !empty($idioma_disponivel)) {
                                $tem_novos_idiomas = true;
                                break;
                            }
                        }
                        ?>
                        
                        <!-- Idioma Atual -->
                        <li><h6 class="dropdown-header">Idioma Atual</h6></li>
                        <li>
                            <span class="dropdown-item-text">
                                <i class="fas fa-check-circle me-2 text-success"></i><?php echo htmlspecialchars($idiomas_display[$idioma_escolhido] ?? $idioma_escolhido); ?> (<?php echo htmlspecialchars($nivel_usuario); ?>)
                            </span>
                        </li>
                        
                        <?php if ($tem_outros_idiomas || $tem_novos_idiomas): ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        
                        <?php if ($tem_outros_idiomas): ?>
                            <li><h6 class="dropdown-header">Meus Outros Idiomas</h6></li>
                            <?php foreach ($idiomas_usuario as $idioma_user): ?>
                                <?php if ($idioma_user['idioma'] !== $idioma_escolhido): ?>
                                    <li>
                                        <button type="button" class="dropdown-item" onclick="trocarIdioma('<?php echo htmlspecialchars($idioma_user['idioma']); ?>')">
                                            <i class="fas fa-exchange-alt me-2"></i><?php echo htmlspecialchars($idiomas_display[$idioma_user['idioma']] ?? $idioma_user['idioma']); ?> (<?php echo htmlspecialchars($idioma_user['nivel']); ?>)
                                        </button>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($tem_novos_idiomas): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($tem_novos_idiomas): ?>
                            <li><h6 class="dropdown-header">Adicionar Novo Idioma</h6></li>
                            <?php foreach ($idiomas_disponiveis as $idioma_disponivel): ?>
                                <?php 
                                $ja_estudado = in_array($idioma_disponivel, $idiomas_ja_estudados);
                                $nao_vazio = !empty($idioma_disponivel);
                                ?>
                                <?php if (!$ja_estudado && $nao_vazio): ?>
                                    <li>
                                        <button type="button" class="dropdown-item" onclick="trocarIdioma('<?php echo htmlspecialchars($idioma_disponivel); ?>')">
                                            <i class="fas fa-plus me-2"></i><?php echo htmlspecialchars($idiomas_display[$idioma_disponivel] ?? $idioma_disponivel); ?> (Novo)
                                        </button>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (!$tem_outros_idiomas && !$tem_novos_idiomas): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><span class="dropdown-item-text text-muted">Nenhum outro idioma disponível</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-md-11">
                <?php if ($mostrar_selecao_idioma): ?>
                    <!-- Seleção de idioma para usuários sem progresso -->
                    <div class="card">
                        <div class="card-header text-center">
                            <h2>Bem-vindo! Escolha seu primeiro idioma</h2>
                        </div>
                        <div class="card-body">
                            <?php if (isset($erro_selecao)): ?>
                                <div class="alert alert-danger"><?php echo $erro_selecao; ?></div>
                            <?php endif; ?>
                            <p class="text-center mb-4">Para começar sua jornada de aprendizado, selecione o idioma que deseja estudar:</p>
                            <form method="POST" action="painel.php">
                                <div class="mb-3">
                                    <label for="idioma_inicial" class="form-label">Escolha seu idioma</label>
                                    <select class="form-select" id="idioma_inicial" name="idioma_inicial" required>
                                        <option value="" disabled selected>Selecione um idioma</option>
                                        <?php foreach ($idiomas_disponiveis as $idioma_disp): ?>
                                            <option value="<?php echo htmlspecialchars($idioma_disp); ?>"><?php echo htmlspecialchars($idiomas_display[$idioma_disp] ?? $idioma_disp); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Começar Quiz de Nivelamento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Painel normal para usuários com progresso -->
                    <div class="card mb-4">
                        <div class="card-header text-center">
                            <?php if (count($unidades) > 0): ?>
                                <?php $unidade = $unidades[0]; ?>
                                <h2>Unidade <?php echo htmlspecialchars($unidade["numero_unidade"]); ?>: <?php echo htmlspecialchars($unidade["nome_unidade"]); ?></h2>
                                <p style="color: #fff;" class="mb-1"><?php echo htmlspecialchars($unidade["descricao"]); ?></p>
                                <p class="fs-5">Caminho: <?php 
                                    $idioma_valido = ($idioma_escolhido && preg_match('/^[a-zA-Z]+$/', $idioma_escolhido)) ? $idioma_escolhido : 'Idioma';
                                    echo htmlspecialchars($idiomas_display[$idioma_valido] ?? $idioma_valido); 
                                ?> - <span class="badge bg-success"><?php echo htmlspecialchars(($nivel_usuario && preg_match('/^[A-C][12]$/', $nivel_usuario)) ? $nivel_usuario : 'A1'); ?></span></p>
                            <?php else: ?>
                                <h2>Seu Caminho de Aprendizado em <?php 
                                    $idioma_valido = ($idioma_escolhido && preg_match('/^[a-zA-Z]+$/', $idioma_escolhido)) ? $idioma_escolhido : 'Idioma';
                                    echo htmlspecialchars($idiomas_display[$idioma_valido] ?? $idioma_valido); 
                                ?></h2>
                                <p class="fs-4">Seu nível atual é: <span class="badge bg-success"><?php echo htmlspecialchars(($nivel_usuario && preg_match('/^[A-C][12]$/', $nivel_usuario)) ? $nivel_usuario : 'A1'); ?></span></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (count($unidades) > 0): ?>
                                <div id="blocos-unidade-<?php echo $unidade['id']; ?>" class="row">
                                    <div class="col-12 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando blocos...</span>
                                        </div>
                                        <p class="mt-2" style="color: #fff;">Carregando blocos da unidade...</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info" role="alert">
                                    Nenhuma unidade encontrada para este nível e idioma.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seção Teorias -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title mb-2">
                                        <i class="fas fa-book-open me-2 text-primary"></i>
                                        Teorias e Conceitos
                                    </h5>
                                    <p class="card-text mb-0" style="color: #fff;">
                                        Acesse o conteúdo teórico do seu nível atual
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn btn-primary w-100 w-md-auto" onclick="abrirTeorias()">
                                        <i class="fas fa-book me-2"></i>Ver Teorias
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Seção Flash Cards -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Coluna de texto -->
                                <div class="col-md-8">
                                    <h5 class="card-title mb-2">
                                        <i class="fas fa-layer-group me-2 text-warning"></i>
                                        Flash Cards
                                    </h5>
                                    <p class="card-text text-muted mb-0">
                                        Estude com flashcards personalizados e melhore sua memorização
                                    </p>
                                </div>

                                <!-- Coluna dos botões -->
                                <div class="col-md-4 text-end">
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        <a href="flashcards.php" class="btn btn-warning flex-fill flex-md-grow-0">
                                            <i class="fas fa-layer-group me-2"></i>Meus Decks
                                        </a>
                                        <a href="flashcard_estudo.php" class="btn btn-outline-warning flex-fill flex-md-grow-0">
                                            <i class="fas fa-play me-2"></i>Estudar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Gerenciamento de Palavras -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-book me-2"></i>Minhas Palavras
                                </h5>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <select class="form-select form-select-sm" id="filtroPalavrasStatus" onchange="carregarPalavras()" style="width: auto; min-width: 120px;">
                                        <option value="">Todas</option>
                                        <option value="0">Não aprendidas</option>
                                        <option value="1">Aprendidas</option>
                                    </select>
                                    <div class="input-group" style="width: 200px;">
                                        <input type="text" class="form-control form-control-sm" id="filtroPalavrasBusca" placeholder="Buscar..." onkeyup="filtrarPalavrasLocal()">
                                        <button class="btn btn-sm btn-search-custom" type="button" onclick="carregarPalavras()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-sm" style="background: linear-gradient(135deg, #ffd700 0%, #e7c500 100%); color: #212529; font-weight: 600; border: none;" onclick="abrirModalAdicionarPalavra()">
                                        <i class="fas fa-plus me-1"></i>Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Lista de Palavras -->
                            <div id="listaPalavras" class="row">
                                <div class="col-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando suas palavras...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Palavra -->
    <div class="modal fade" id="modalAdicionarPalavra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Adicionar Nova Palavra/Flashcard
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form id="formAdicionarPalavra">
                                <input type="hidden" id="palavraId" name="id_flashcard">
                                
                                <div class="mb-3">
                                    <label for="palavraFrente" class="form-label">Frente do Card *</label>
                                    <textarea class="form-control" id="palavraFrente" name="palavra_frente" rows="3" required placeholder="Digite a palavra/frase na língua estrangeira"><?php echo htmlspecialchars($palavra_frente ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="palavraVerso" class="form-label">Verso do Card *</label>
                                    <textarea class="form-control" id="palavraVerso" name="palavra_verso" rows="3" required placeholder="Digite a tradução ou significado"><?php echo htmlspecialchars($palavra_verso ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="palavraDica" class="form-label">Dica (opcional)</label>
                                    <textarea class="form-control" id="palavraDica" name="dica" rows="2" placeholder="Digite uma dica para ajudar na memorização"><?php echo htmlspecialchars($dica ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="palavraDificuldade" class="form-label">Dificuldade</label>
                                            <select class="form-select" id="palavraDificuldade" name="dificuldade">
                                                <option value="facil">Fácil</option>
                                                <option value="medio" selected>Médio</option>
                                                <option value="dificil">Difícil</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="palavraOrdem" class="form-label">Ordem</label>
                                            <input type="number" class="form-control" id="palavraOrdem" name="ordem_no_deck" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="palavraIdioma" class="form-label">Idioma</label>
                                            <input type="text" class="form-control" id="palavraIdioma" name="idioma" value="<?php echo htmlspecialchars($idioma_valido ?? 'Idioma'); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="palavraNivel" class="form-label">Nível</label>
                                            <select class="form-select" id="palavraNivel" name="nivel">
                                                <option value="A1">A1</option>
                                                <option value="A2">A2</option>
                                                <option value="B1">B1</option>
                                                <option value="B2">B2</option>
                                                <option value="C1">C1</option>
                                                <option value="C2">C2</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="palavraCategoria" class="form-label">Categoria (opcional)</label>
                                    <input type="text" class="form-control" id="palavraCategoria" name="categoria" placeholder="Ex: Verbos, Substantivos, Cumprimentos">
                                </div>
                                
                                <!-- Campos para imagens e áudios (futuro) -->
                                <div class="mb-3">
                                    <label class="form-label">Mídia (em desenvolvimento)</label>
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Suporte para imagens e áudios será adicionado em breve.
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Preview do Flashcard</label>
                            <div class="flashcard-preview" id="palavraPreview" onclick="virarPreviewPalavra()">
                                <div class="flashcard-inner">
                                    <div class="flashcard-front">
                                        <div id="previewPalavraFrente">Digite o conteúdo da frente</div>
                                    </div>
                                    <div class="flashcard-back">
                                        <div id="previewPalavraVerso">Digite o conteúdo do verso</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">Clique no card para virar</small>
                            </div>
                            
                            <!-- Dica preview -->
                            <div class="mt-3" id="previewDicaContainer" style="display: none;">
                                <div class="alert alert-info py-2">
                                    <small>
                                        <i class="fas fa-lightbulb me-1"></i>
                                        <span id="previewPalavraDica">Dica aparecerá aqui</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarPalavra()">
                        <i class="fas fa-save me-2"></i>Salvar Palavra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Blocos da Unidade -->
    <div class="modal fade" id="modalBlocos" tabindex="-1" aria-labelledby="modalBlocosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBlocosLabel">
                        <i class="fas fa-cubes me-2"></i>
                        <span id="tituloBlocos">Blocos da Unidade</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaBlocos" class="row">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando blocos...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exercícios -->
    <div class="modal fade" id="modalExercicios" tabindex="-1" aria-labelledby="modalExerciciosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExerciciosLabel">
                        <i class="fas fa-pencil-alt me-2"></i>
                        <span id="tituloExercicios">Exercícios</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Barra de progresso dos exercícios -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Progresso:</span>
                            <span id="contadorExercicios" class="badge bg-primary">1/5</span>
                        </div>
                        <div class="progress">
                            <div id="progressoExercicios" class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <!-- Conteúdo do exercício -->
                    <div id="conteudoExercicio">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando exercício...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="voltarParaBlocos()">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEnviarResposta" onclick="enviarResposta()">
                        <i class="fas fa-check me-2"></i>Enviar Resposta
                    </button>
                    <button type="button" class="btn btn-success" id="btnProximoExercicio" onclick="proximoExercicio()" style="display: none;">
                        <i class="fas fa-arrow-right me-2"></i>Próximo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Teorias -->
    <div class="modal fade" id="modalTeorias" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book-open me-2"></i>Teorias - <?php echo htmlspecialchars($idiomas_display[$idioma_escolhido] ?? $idioma_escolhido); ?> (<?php echo htmlspecialchars($nivel_usuario); ?>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaTeorias">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando teorias...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Teoria Individual -->
    <div class="modal fade" id="modalTeoriaConteudo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloTeoriaConteudo">
                        <i class="fas fa-book me-2"></i>Teoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body teoria-conteudo" id="conteudoTeoria">
                    <!-- Conteúdo da teoria será carregado aqui -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="btnIniciarExercicios" style="display: none;" onclick="iniciarExerciciosAposTeoria()">
                        <i class="fas fa-play me-2"></i>Iniciar Exercícios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalExclusao"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="mensagemModalExclusao">Tem certeza que deseja excluir esta palavra? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarExclusao"><i class="fas fa-trash me-2"></i>Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ==================== VARIÁVEIS GLOBAIS ====================
    let modalBlocos = null;
    let modalExercicios = null;
    let modalAdicionarPalavra = null;
    let modalConfirmarExclusao = null;
    let modalTeorias = null;
    let modalTeoriaConteudo = null;
    let blocoParaIniciar = null;
    let unidadeAtual = null;
    let blocoAtual = null;
    let exercicioAtual = null;
    let exerciciosLista = [];
    let exercicioIndex = 0;
    let respostaSelecionada = null;
    let palavrasCarregadas = [];
    let exerciciosEspeciais = [];
    let exerciciosEspeciaisAdicionados = false;
    let caminhoAtual = 1; // Default path ID

    // ==================== INICIALIZAÇÃO ====================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== INICIALIZANDO PAINEL ===');
        
        // Inicialização dos modais
        modalBlocos = new bootstrap.Modal(document.getElementById('modalBlocos'));
        modalExercicios = new bootstrap.Modal(document.getElementById('modalExercicios'));
        modalAdicionarPalavra = new bootstrap.Modal(document.getElementById('modalAdicionarPalavra'));
        modalConfirmarExclusao = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
        modalTeorias = new bootstrap.Modal(document.getElementById('modalTeorias'));
        modalTeoriaConteudo = new bootstrap.Modal(document.getElementById('modalTeoriaConteudo'));

        // Configurar event listeners para preview de flashcards
        configurarPreviewFlashcards();
        
        // Configurar event listeners para troca de idioma
        document.querySelectorAll('button[data-idioma]').forEach(button => {
            button.addEventListener('click', function() {
                const idioma = this.getAttribute('data-idioma');
                if (idioma) {
                    trocarIdiomaAjax(idioma);
                }
            });
        });

        // Carregar blocos de todas as unidades
        carregarBlocosTodasUnidades();

        // Carrega palavras do usuário ao inicializar
        if (typeof carregarPalavras === 'function') {
            carregarPalavras();
        }

        console.log('Painel inicializado com sucesso');
    });

    // ==================== FUNÇÕES DE CARREGAMENTO DE BLOCOS ====================
    
    // Carregar blocos da unidade atual
    function carregarBlocosTodasUnidades() {
        const container = document.querySelector('[id^="blocos-unidade-"]');
        if (container) {
            const unidadeId = container.id.replace('blocos-unidade-', '');
            carregarBlocosUnidade(unidadeId);
        }
    }
    
    // Carregar blocos de uma unidade específica
    function carregarBlocosUnidade(unidadeId) {
        const container = document.getElementById(`blocos-unidade-${unidadeId}`);
        
        console.log('=== CARREGAR BLOCOS UNIDADE ===');
        console.log('Unidade ID:', unidadeId);
        console.log('Container:', container);
        
        fetch(`../../admin/controller/get_blocos.php?unidade_id=${unidadeId}`)
            .then(response => {
                console.log('Status da resposta HTTP:', response.status);
                console.log('Resposta OK:', response.ok);
                return response.json();
            })
            .then(data => {
                console.log('=== DADOS RECEBIDOS DO SERVIDOR ===');
                console.log('Success:', data.success);
                console.log('Total de blocos:', data.blocos ? data.blocos.length : 0);
                console.log('Exercícios especiais:', data.total_especiais);
                console.log('Debug info:', data.debug_info);
                console.log('Blocos completos:', data.blocos);
                
                if (data.success) {
                    exibirBlocosUnidade(data.blocos, container, unidadeId);
                } else {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum bloco encontrado para esta unidade.
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar blocos:', error);
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar blocos desta unidade.
                        </div>
                    </div>
                `;
            });
    }
    
    // Exibir blocos de uma unidade com sistema de desbloqueio - VERSÃO CORRIGIDA COM LAYOUT QUADRADO
    function exibirBlocosUnidade(blocos, container, unidadeId) {
        if (!blocos || blocos.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum bloco encontrado para esta unidade.
                    </div>
                </div>
            `;
            return;
        }
        
        // Criar container em grid organizado
        container.innerHTML = '';
        container.className = 'blocos-container';
        
        console.log('Blocos recebidos:', blocos);
        
        console.log('=== EXIBIR BLOCOS UNIDADE ===');
        console.log('Total de blocos recebidos:', blocos.length);
        console.log('Blocos completos:', blocos);
        
        // Separar blocos normais e especiais
        const blocosNormais = blocos.filter(b => b.tipo !== 'especial');
        const blocosEspeciais = blocos.filter(b => b.tipo === 'especial');
        
        console.log('Blocos normais encontrados:', blocosNormais.length);
        console.log('Blocos especiais encontrados:', blocosEspeciais.length);
        console.log('Detalhes dos blocos especiais:', blocosEspeciais);
        
        // Determinar qual bloco está disponível (primeiro não concluído)
        let blocoDisponivel = 0;
        let todosConcluidos = true;
        
        for (let i = 0; i < blocosNormais.length; i++) {
            const concluido = blocosNormais[i].progresso?.concluido || false;
            
            if (!concluido) {
                blocoDisponivel = i;
                todosConcluidos = false;
                break;
            }
        }
        
        if (todosConcluidos) {
            blocoDisponivel = blocosNormais.length;
        }
        
        // Exibir blocos normais primeiro
        blocosNormais.forEach((bloco, index) => {
            const progresso = bloco.progresso?.progresso_percentual || 0;
            const concluido = bloco.progresso?.concluido || false;
            const atividadesConcluidas = bloco.progresso?.atividades_concluidas || 0;
            const totalAtividades = bloco.progresso?.total_atividades || bloco.total_atividades || 0;
            
            const disponivel = index <= blocoDisponivel;
            const bloqueado = !disponivel;
            
            const blocoElement = document.createElement('div');
            blocoElement.className = 'bloco-item';
            
            const cardClass = bloqueado ? 'bloco-card-bloqueado' : (concluido ? 'bloco-card-concluido' : 'bloco-card-disponivel');
            const clickHandler = bloqueado ? '' : `onclick="abrirExercicios(${bloco.id}, '${bloco.nome_bloco.replace(/'/g, "\\'")}')" style="cursor: pointer;"`;
            
            blocoElement.innerHTML = `
                <div class="card bloco-card h-100 ${cardClass}" ${clickHandler}>
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <div>
                            ${bloqueado ? 
                                '<i class="fas fa-lock bloco-icon mb-3" style="font-size: 2rem; color: #6c757d;"></i>' :
                                (concluido ? 
                                    '<i class="fas fa-check-circle bloco-icon mb-3" style="font-size: 2rem; color: #28a745;"></i>' :
                                    '<i class="fas fa-play-circle bloco-icon mb-3" style="font-size: 2rem; color: #007bff;"></i>'
                                )
                            }
                            <h6 class="card-title mb-2">${bloco.nome_bloco}</h6>
                            <div class="bloco-descricao position-relative">
                                <p class="card-text text-muted small mb-0">${bloco.descricao || 'Descrição não disponível'}</p>
                                <div class="scroll-indicator">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-auto">
                            ${bloqueado ? 
                                '<div class="alert alert-warning py-1 mb-2"><small><i class="fas fa-lock me-1"></i>Bloqueado</small></div>' :
                                `<div class="progress progress-bar-custom mb-2" style="height: 6px;">
                                    <div class="progress-bar ${concluido ? 'bg-success' : 'bg-primary'}" role="progressbar" 
                                         style="width: ${progresso}%" aria-valuenow="${progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">${atividadesConcluidas}/${totalAtividades} (${progresso}%)</small>`
                            }
                            
                            ${concluido ? '<div class="mt-2"><span class="badge bg-success"><i class="fas fa-check me-1"></i>Concluído</span></div>' : ''}
                            ${!bloqueado && !concluido && index === blocoDisponivel ? '<div class="mt-2"><span class="badge bg-primary"><i class="fas fa-play me-1"></i>Disponível</span></div>' : ''}
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(blocoElement);
        });
        
        // Exibir blocos especiais (sempre disponíveis)
        console.log('=== PROCESSANDO BLOCOS ESPECIAIS ===');
        console.log('Número de blocos especiais para processar:', blocosEspeciais.length);
        
        blocosEspeciais.forEach((bloco, index) => {
            console.log(`=== BLOCO ESPECIAL ${index + 1} ===`);
            console.log('Dados do bloco:', bloco);
            console.log('ID:', bloco.id);
            console.log('Nome:', bloco.nome_bloco);
            console.log('Tipo:', bloco.tipo);
            
            const blocoElement = document.createElement('div');
            blocoElement.className = 'bloco-item';
            
            const tipoIcon = {
                'observar': 'fa-eye',
                'completar': 'fa-edit',
                'alternativa': 'fa-list-ul'
            };
            
            const exercicio = bloco.exercicio_especial;
            const icon = tipoIcon[exercicio?.tipo_exercicio] || 'fa-star';
            
            blocoElement.innerHTML = `
                <div class="card bloco-card h-100 bloco-card-disponivel border-warning" 
                     onclick="abrirExercicioEspecial('${bloco.id}', '${bloco.nome_bloco.replace(/'/g, "\\'")}')" 
                     style="cursor: pointer; border: 3px solid #ffc107 !important;">
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <div>
                            <i class="fas ${icon} bloco-icon mb-3" style="font-size: 2rem; color: #ffc107;"></i>
                            <h6 class="card-title mb-2">${bloco.nome_bloco}</h6>
                            <div class="bloco-descricao position-relative">
                                <p class="card-text text-muted small mb-0">${bloco.descricao || 'Descrição não disponível'}</p>
                                <div class="scroll-indicator">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="progress progress-bar-custom mb-2" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">Exercício especial</small>
                            
                            <div class="mt-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star me-1"></i>Especial
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(blocoElement);
            console.log(`Bloco especial ${index + 1} adicionado ao DOM com sucesso`);
            console.log('HTML gerado:', blocoElement.outerHTML.substring(0, 200) + '...');
        });
        
        console.log('=== RESULTADO FINAL ===');
        console.log('Total de elementos no container:', container.children.length);
        console.log('Container HTML:', container.innerHTML.length > 0 ? 'Contém elementos' : 'Vazio');
        
        // Inicializar indicadores de scroll após renderizar
        setTimeout(initScrollIndicators, 100);
    }

    // ==================== CONFIGURAÇÃO DOS EVENT LISTENERS ====================
    function configurarPreviewFlashcards() {
        // Atualizar preview quando os campos mudarem
        const frenteInput = document.getElementById('palavraFrente');
        const versoInput = document.getElementById('palavraVerso');
        const dicaInput = document.getElementById('palavraDica');

        if (frenteInput) frenteInput.addEventListener('input', atualizarPreviewFlashcard);
        if (versoInput) versoInput.addEventListener('input', atualizarPreviewFlashcard);
        if (dicaInput) dicaInput.addEventListener('input', atualizarPreviewFlashcard);
    }

    // ==================== FUNÇÕES PRINCIPAIS DE NAVEGAÇÃO ====================

    // Função para abrir modal de blocos da unidade
    window.abrirUnidade = function(unidadeId, tituloUnidade, numeroUnidade) {
        console.log('Abrindo unidade:', unidadeId, tituloUnidade, numeroUnidade);
        
        unidadeAtual = unidadeId;
        document.getElementById("tituloBlocos").textContent = `Blocos da Unidade ${numeroUnidade}: ${tituloUnidade}`;
   
        // CORREÇÃO: URL corrigida
        fetch(`../../admin/controller/get_blocos.php?unidade_id=${unidadeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                if (data.success) {
                    exibirBlocos(data.blocos);
                    modalBlocos.show();
                } else {
                    alert("Erro ao carregar blocos: " + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro ao carregar blocos:", error);
                alert("Erro de rede ao carregar blocos. Verifique o console para detalhes.");
            });
    };

    // Função para exibir blocos no modal com layout organizado
    function exibirBlocos(blocos) {
        const container = document.getElementById("listaBlocos");
        container.innerHTML = "";
        container.className = "blocos-container";

        if (!blocos || blocos.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum bloco encontrado para esta unidade.
                    </div>
                </div>
            `;
            container.className = "row";
            return;
        }

        blocos.forEach(bloco => {
            const progresso = bloco.progresso?.progresso_percentual || 0;
            const concluido = bloco.progresso?.concluido || false;
            const atividadesConcluidas = bloco.progresso?.atividades_concluidas || 0;
            const totalAtividades = bloco.progresso?.total_atividades || bloco.total_atividades || 0;
           
            const blocoElement = document.createElement("div");
            blocoElement.className = "bloco-item";
            blocoElement.innerHTML = `
                <div class="card bloco-card h-100 bloco-card-disponivel" onclick="abrirExercicios(${bloco.id}, '${bloco.nome_bloco.replace(/'/g, "\\'")}')" style="cursor: pointer;">
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <div>
                            <i class="fas fa-cube bloco-icon mb-3" style="font-size: 2rem; color: #007bff;"></i>
                            <h6 class="card-title mb-2">${bloco.nome_bloco}</h6>
                            <div class="bloco-descricao position-relative">
                                <p class="card-text text-muted small mb-0">${bloco.descricao || 'Descrição não disponível'}</p>
                                <div class="scroll-indicator">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <div class="progress progress-bar-custom mb-2" style="height: 6px;">
                                <div class="progress-bar ${concluido ? 'bg-success' : 'bg-primary'}" role="progressbar" 
                                     style="width: ${progresso}%" aria-valuenow="${progresso}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">${atividadesConcluidas}/${totalAtividades} (${progresso}%)</small>
                            ${concluido ? '<div class="mt-2"><span class="badge bg-success"><i class="fas fa-check me-1"></i>Concluído</span></div>' : ''}
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(blocoElement);
        });
    }

    // Função para abrir modal de exercícios
    window.abrirExercicios = function(blocoId, tituloBloco) {
        console.log('Abrindo exercícios para bloco:', blocoId, tituloBloco);
        
        blocoAtual = blocoId;
        blocoParaIniciar = { id: blocoId, titulo: tituloBloco };
        
        // Primeiro verificar se há teoria para este bloco
        verificarTeoriaDoBloco(blocoId, tituloBloco);
    };
    
    // Função para recarregar blocos após completar um exercício
    window.recarregarBlocosAposCompletar = function() {
        setTimeout(() => {
            carregarBlocosTodasUnidades();
        }, 1000);
    };
    
    // Função para verificar se há teoria para o bloco
    function verificarTeoriaDoBloco(blocoId, tituloBloco) {
        // CORREÇÃO: URL corrigida
        fetch(`../../admin/controller/get_teoria_bloco.php?bloco_id=${blocoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.teoria) {
                    // Há teoria, mostrar primeiro
                    mostrarTeoriaDoBloco(data.teoria, tituloBloco);
                } else {
                    // Não há teoria, ir direto para exercícios
                    iniciarExerciciosAposTeoria();
                }
            })
            .catch(error => {
                console.log('Nenhuma teoria encontrada, iniciando exercícios:', error);
                iniciarExerciciosAposTeoria();
            });
    }
    
    // Função para formatar conteúdo da teoria
    function formatarConteudoTeoria(conteudo) {
        if (!conteudo) return '<p class="text-muted">Nenhum conteúdo disponível.</p>';
        
        // Detectar se é formato de tópicos (numerado)
        const linhas = conteudo.split('\n');
        let temTopicos = false;
        let conteudoFormatado = '';
        let topicoAtual = null;
        
        // Verificar se tem tópicos numerados
        for (let linha of linhas) {
            if (/^\d+\..+/.test(linha.trim())) {
                temTopicos = true;
                break;
            }
        }
        
        if (temTopicos) {
            // Processar como tópicos em grid
            let topicos = [];
            
            linhas.forEach(linha => {
                linha = linha.trim();
                if (!linha) return;
                
                if (/^\d+\..+/.test(linha)) {
                    // Finalizar tópico anterior
                    if (topicoAtual) {
                        topicos.push(topicoAtual);
                    }
                    
                    // Novo tópico
                    const numero = linha.match(/^(\d+)\./)[1];
                    const titulo = linha.replace(/^\d+\.\s*/, '');
                    topicoAtual = { numero, titulo, conteudo: '', tabelas: [] };
                } else if (linha.includes('|') && topicoAtual) {
                    // Detectar tabela (formato: col1 | col2 | col3)
                    if (!topicoAtual.tabelaAtual) {
                        topicoAtual.tabelaAtual = [];
                    }
                    const colunas = linha.split('|').map(col => col.trim());
                    topicoAtual.tabelaAtual.push(colunas);
                } else {
                    // Finalizar tabela se existir
                    if (topicoAtual && topicoAtual.tabelaAtual) {
                        topicoAtual.tabelas.push(topicoAtual.tabelaAtual);
                        delete topicoAtual.tabelaAtual;
                    }
                    
                    // Conteúdo do tópico
                    if (topicoAtual) {
                        topicoAtual.conteudo += (topicoAtual.conteudo ? '\n' : '') + linha;
                    }
                }
            });
            
            // Finalizar última tabela se existir
            if (topicoAtual && topicoAtual.tabelaAtual) {
                topicoAtual.tabelas.push(topicoAtual.tabelaAtual);
                delete topicoAtual.tabelaAtual;
            }
            
            // Adicionar último tópico
            if (topicoAtual) {
                topicos.push(topicoAtual);
            }
            
            // Gerar HTML em grid
            conteudoFormatado = '<div class="topicos-container">';
            topicos.forEach((topico, index) => {
                const temTabelas = topico.tabelas && topico.tabelas.length > 0;
                conteudoFormatado += `
                    <div class="topico-item ${temTabelas ? 'com-tabela' : ''}">
                        <div class="topico-header">
                            <div class="topico-numero">${topico.numero}</div>
                            <h4 class="topico-titulo">${topico.titulo}</h4>
                        </div>
                `;
                
                if (temTabelas) {
                    // Tabelas antes do texto
                    const tabelasAntes = topico.tabelas.filter(t => t.posicao === 'antes');
                    if (tabelasAntes.length > 0) {
                        conteudoFormatado += '<div class="topico-tabelas mb-3">';
                        tabelasAntes.forEach((tabela, tabelaIndex) => {
                            conteudoFormatado += gerarHTMLTabela(tabela, `${index}-antes-${tabelaIndex}`);
                        });
                        conteudoFormatado += '</div>';
                    }
                    
                    // Conteúdo com tabelas ao lado
                    const tabelasLado = topico.tabelas.filter(t => !t.posicao || t.posicao === 'lado');
                    if (tabelasLado.length > 0) {
                        conteudoFormatado += '<div class="topico-content-wrapper">';
                        conteudoFormatado += '<div class="topico-texto">';
                        conteudoFormatado += `<p class="topico-conteudo">${topico.conteudo.replace(/\n/g, '<br>')}</p>`;
                        conteudoFormatado += '</div>';
                        conteudoFormatado += '<div class="topico-tabelas">';
                        tabelasLado.forEach((tabela, tabelaIndex) => {
                            conteudoFormatado += gerarHTMLTabela(tabela, `${index}-lado-${tabelaIndex}`);
                        });
                        conteudoFormatado += '</div></div>';
                    } else {
                        conteudoFormatado += `<p class="topico-conteudo">${topico.conteudo.replace(/\n/g, '<br>')}</p>`;
                    }
                    
                    // Tabelas depois do texto
                    const tabelasDepois = topico.tabelas.filter(t => t.posicao === 'depois');
                    if (tabelasDepois.length > 0) {
                        conteudoFormatado += '<div class="topico-tabelas mt-3">';
                        tabelasDepois.forEach((tabela, tabelaIndex) => {
                            conteudoFormatado += gerarHTMLTabela(tabela, `${index}-depois-${tabelaIndex}`);
                        });
                        conteudoFormatado += '</div>';
                    }
                } else {
                    conteudoFormatado += `<p class="topico-conteudo">${topico.conteudo.replace(/\n/g, '<br>')}</p>`;
                }
                
                conteudoFormatado += '</div>';
            });
            conteudoFormatado += '</div>';
        } else {
            // Texto simples - verificar se tem tabelas independentes
            if (conteudo.includes('|')) {
                conteudoFormatado = processarTabelasIndependentes(conteudo);
            } else {
                conteudoFormatado = `<div class="teoria-texto-simples">${conteudo}</div>`;
            }
        }
        
        return conteudoFormatado;
    }
    
    // Função para gerar HTML da tabela
    function gerarHTMLTabela(tabela, tabelaId) {
        let html = `<div class="teoria-tabela"><table id="tabela-${tabelaId}">`;
        
        tabela.forEach((linha, linhaIndex) => {
            const tag = linhaIndex === 0 ? 'th' : 'td';
            html += '<tr>';
            linha.forEach(celula => {
                html += `<${tag}>${celula}</${tag}>`;
            });
            html += '</tr>';
        });
        
        html += '</table></div>';
        return html;
    }
    
    // Função para processar tabelas independentes
    function processarTabelasIndependentes(conteudo) {
        const linhas = conteudo.split('\n');
        let html = '';
        let tabelaAtual = [];
        let tabelaIndex = 0;
        
        linhas.forEach(linha => {
            linha = linha.trim();
            if (linha.includes('|')) {
                const colunas = linha.split('|').map(col => col.trim());
                tabelaAtual.push(colunas);
            } else {
                // Finalizar tabela se existir
                if (tabelaAtual.length > 0) {
                    const tabelaId = `tabela-independente-${tabelaIndex++}`;
                    html += `
                        <div class="teoria-tabela">
                            <table id="${tabelaId}">
                    `;
                    
                    tabelaAtual.forEach((linhaTabela, linhaIndex) => {
                        const tag = linhaIndex === 0 ? 'th' : 'td';
                        html += '<tr>';
                        linhaTabela.forEach(celula => {
                            html += `<${tag}>${celula}</${tag}>`;
                        });
                        html += '</tr>';
                    });
                    
                    html += `
                            </table>
                            <button class="btn-tabela-expand" onclick="expandirTabela('${tabelaId}')">
                                <i class="fas fa-expand me-1"></i>Expandir
                            </button>
                        </div>
                    `;
                    
                    tabelaAtual = [];
                }
                
                // Adicionar texto normal
                if (linha) {
                    html += `<p>${linha}</p>`;
                }
            }
        });
        
        // Finalizar última tabela se existir
        if (tabelaAtual.length > 0) {
            const tabelaId = `tabela-independente-${tabelaIndex}`;
            html += `
                <div class="teoria-tabela">
                    <table id="${tabelaId}">
            `;
            
            tabelaAtual.forEach((linhaTabela, linhaIndex) => {
                const tag = linhaIndex === 0 ? 'th' : 'td';
                html += '<tr>';
                linhaTabela.forEach(celula => {
                    html += `<${tag}>${celula}</${tag}>`;
                });
                html += '</tr>';
            });
            
            html += `
                    </table>
                </div>
            `;
        }
        
        return html || `<div class="teoria-texto-simples">${conteudo}</div>`;
    }
    
    // Função para mostrar teoria do bloco
    function mostrarTeoriaDoBloco(teoria, tituloBloco) {
        document.getElementById('tituloTeoriaConteudo').innerHTML = `
            <i class="fas fa-book me-2"></i>${teoria.titulo} - ${tituloBloco}
        `;
        document.getElementById('conteudoTeoria').innerHTML = formatarConteudoTeoria(teoria.conteudo);
        document.getElementById('btnIniciarExercicios').style.display = 'block';
        modalTeoriaConteudo.show();
    }
    
    // Função para iniciar exercícios após teoria
    window.iniciarExerciciosAposTeoria = function() {
        if (modalTeoriaConteudo) modalTeoriaConteudo.hide();
        
        if (!blocoParaIniciar) return;
        
        const blocoId = blocoParaIniciar.id;
        const tituloBloco = blocoParaIniciar.titulo;
        
        document.getElementById("tituloExercicios").textContent = `Exercícios: ${tituloBloco}`;
   
        // CORREÇÃO: URL corrigida
        fetch(`../../admin/controller/get_exercicio.php?bloco_id=${blocoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Exercícios recebidos:', data);
                if (data.success) {
                    if (data.exercicios && data.exercicios.length > 0) {
                        exerciciosLista = data.exercicios;
                        exercicioIndex = 0;
                        carregarExercicio(exercicioIndex);
                        modalBlocos.hide();
                        modalExercicios.show();
                    } else {
                        alert("Nenhum exercício encontrado para este bloco.");
                    }
                } else {
                    alert("Erro ao carregar exercícios: " + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro ao carregar exercícios:", error);
                alert("Erro de rede ao carregar exercícios. Verifique o console.");
            });
    };

    // ==================== FUNÇÃO PRINCIPAL PARA CARREGAR EXERCÍCIOS ====================
    function carregarExercicio(index) {
        if (!exerciciosLista || exerciciosLista.length === 0) return;

        exercicioAtual = exerciciosLista[index];
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        conteudoExercicioDiv.innerHTML = "";
        respostaSelecionada = null;

        // Atualiza contador de progresso do exercício
        document.getElementById("contadorExercicios").textContent = `${index + 1}/${exerciciosLista.length}`;
        const progresso = ((index + 1) / exerciciosLista.length) * 100;
        const progressoBar = document.getElementById("progressoExercicios");
        if (progressoBar) {
            progressoBar.style.width = `${progresso}%`;
            progressoBar.setAttribute("aria-valuenow", progresso);
        }

        let htmlConteudo = `
            <div class="mb-4">
                <h6 class="text-muted">Pergunta ${index + 1}:</h6>
                <p class="fs-5 mb-4">${exercicioAtual.pergunta}</p>
            </div>
        `;

        // CORREÇÃO: PROCESSAR CONTEÚDO DO EXERCÍCIO DE FORMA CORRETA
        let conteudo = exercicioAtual.conteudo || {};
        
        // Se o conteúdo ainda é string, tentar parsear JSON
        if (typeof conteudo === 'string' && conteudo.trim().startsWith('{')) {
            try {
                conteudo = JSON.parse(conteudo);
                exercicioAtual.conteudo = conteudo; // Atualizar o objeto
            } catch (e) {
                console.error("Erro ao fazer parse do conteúdo:", e);
                conteudo = {};
            }
        }

        // CORREÇÃO: DETERMINAR TIPO DE EXERCÍCIO USANDO O CAMPO CORRETO
        const tipoExercicio = determinarTipoExercicioCorrigido(exercicioAtual, conteudo);
        exercicioAtual.tipoExercicioDeterminado = tipoExercicio;

        console.log('Exercício carregado:', {
            id: exercicioAtual.id,
            tipoDeterminado: tipoExercicio,
            tipoOriginal: exercicioAtual.tipo,
            categoria: exercicioAtual.categoria,
            conteudo_tipo_exercicio: conteudo.tipo_exercicio,
            conteudo: conteudo,
            opcoes: conteudo.opcoes,
            opcoes_length: conteudo.opcoes ? conteudo.opcoes.length : 'undefined',
            resposta_correta: conteudo.resposta_correta,
            frase_original: conteudo.frase_original
        });
        
        // Log específico para listening
        if (tipoExercicio === 'listening' || tipoExercicio === 'audicao') {
            console.log('LISTENING DEBUG:', {
                tem_opcoes: !!(conteudo.opcoes),
                opcoes_array: Array.isArray(conteudo.opcoes),
                opcoes_content: conteudo.opcoes,
                opcoes_filtradas: conteudo.opcoes ? conteudo.opcoes.filter(o => o && o.trim() !== '') : []
            });
        }

        // RENDERIZAR CONTEÚDO BASEADO NO TIPO CORRETO
        if (tipoExercicio === "especial") {
            htmlConteudo += renderizarExercicioEspecial(conteudo);
        } else if (tipoExercicio === "multipla_escolha") {
            htmlConteudo += renderizarMultiplaEscolha(conteudo);
        } else if (tipoExercicio === "texto_livre") {
            htmlConteudo += renderizarTextoLivre(conteudo);
        } else if (tipoExercicio === "completar") {
            htmlConteudo += renderizarCompletar(conteudo);
        } else if (tipoExercicio === "observar") {
            htmlConteudo += renderizarObservar(conteudo);
        } else if (tipoExercicio === "alternativa") {
            htmlConteudo += renderizarAlternativaEspecial(conteudo);
        } else if (tipoExercicio === "listening" || tipoExercicio === "audicao") {
            // Para listening, sempre usar renderizarListeningOpcoes se tiver opcoes
            if (conteudo.opcoes && Array.isArray(conteudo.opcoes) && conteudo.opcoes.length > 0) {
                htmlConteudo += renderizarListeningOpcoes(conteudo);
            } else if (conteudo.alternativas && Array.isArray(conteudo.alternativas)) {
                htmlConteudo += renderizarMultiplaEscolha(conteudo);
            } else {
                // Fallback para exercícios mal configurados
                htmlConteudo += `<div class="alert alert-warning">Exercício de listening mal configurado. Estrutura de opções não encontrada.</div>`;
                console.error('Exercício de listening sem opções válidas:', conteudo);
            }
        } else {
            // Fallback - usar múltipla escolha se tiver alternativas
            if (conteudo.alternativas && Array.isArray(conteudo.alternativas)) {
                htmlConteudo += renderizarMultiplaEscolha(conteudo);
            } else {
                htmlConteudo += renderizarTextoLivre(conteudo);
            }
        }
        
        console.log('HTML gerado para exercício:', htmlConteudo.substring(0, 200) + '...');

        conteudoExercicioDiv.innerHTML = htmlConteudo;
        document.getElementById("btnEnviarResposta").style.display = "block";
        document.getElementById("btnProximoExercicio").style.display = "none";
       
        const feedbackDiv = document.getElementById("feedbackExercicio");
        if (feedbackDiv) feedbackDiv.remove();
        
        // Verificar se os botões de áudio foram criados corretamente
        const audioButtons = conteudoExercicioDiv.querySelectorAll('button[onclick*="speakText"]');
        console.log('Botões de áudio encontrados:', audioButtons.length);
        audioButtons.forEach((btn, i) => {
            console.log(`Botão ${i}:`, btn.onclick ? btn.onclick.toString() : 'sem onclick');
        });
    }

    function determinarTipoExercicioCorrigido(exercicio, conteudo) {
        // Verificar se é exercício especial
        if (exercicio.tipo === 'especial' || exercicio.categoria === 'especial') {
            return 'especial';
        }
        
        // Verificar tipo_exercicio no conteúdo
        if (conteudo?.tipo_exercicio) {
            const tipo = conteudo.tipo_exercicio.toLowerCase();
            if (['listening', 'multipla_escolha', 'texto_livre', 'completar', 'observar', 'alternativa'].includes(tipo)) {
                return tipo;
            }
        }
        
        // Verificar categoria
        if (exercicio.categoria === 'audicao') return 'listening';
        
        // Verificar se tem áudio e opções (listening)
        if (conteudo?.audio_url && conteudo?.opcoes && Array.isArray(conteudo.opcoes)) {
            return 'listening';
        }
        
        // Verificar se tem frase_original e opcoes (listening novo formato)
        if (conteudo?.frase_original && conteudo?.opcoes) {
            return 'listening';
        }
        
        // Verificar múltipla escolha
        if (conteudo?.alternativas && Array.isArray(conteudo.alternativas)) {
            return 'multipla_escolha';
        }
        
        // Verificar texto livre
        if (conteudo?.resposta_correta && !conteudo?.frase_completar) {
            return 'texto_livre';
        }
        
        // Verificar completar
        if (conteudo?.frase_completar) {
            return 'completar';
        }
        
        return 'multipla_escolha';
    }

    // ==================== FUNÇÕES DE RENDERIZAÇÃO DE EXERCÍCIOS ====================

    function renderizarListeningOpcoes(conteudo) {
        const opcoes = conteudo.opcoes || [];
        const text = conteudo.frase_original || conteudo.transcricao || '';
        const lang = conteudo.idioma || 'en-us';
        
        console.log('Renderizando listening com:', { text, lang, opcoes });
        
        let html = `
            <div class="audio-player-container">
                <h6 class="text-center mb-3">🎧 Exercício de Listening</h6>
                <div class="text-center mb-4">
                    <p class="mb-3">Ouça o áudio e selecione a opção correta:</p>
                    <div class="tts-container">
                        <button type="button" class="btn btn-primary btn-lg" onclick="speakText('${text.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', '${lang}')">
                            <i class="fas fa-volume-up me-2"></i>Ouvir Áudio
                        </button>
                    </div>
                </div>
                <div class="listening-options">
        `;
        
        // Garantir que as opções sejam renderizadas mesmo se algumas estiverem vazias
        if (opcoes && opcoes.length > 0) {
            opcoes.forEach((opcao, index) => {
                if (opcao && opcao.trim() !== '') {
                    html += `
                        <button type="button" class="btn btn-option btn-resposta" 
                                data-id="${index}" onclick="selecionarResposta(this)">
                            ${opcao.trim()}
                        </button>
                    `;
                }
            });
        } else {
            html += '<p class="text-danger">Erro: Nenhuma opção encontrada para este exercício.</p>';
        }
        
        html += `</div></div>`;
        return html;
    }

    function renderizarMultiplaEscolha(conteudo) {
        let html = '<div class="d-grid gap-2">';
        if (conteudo.alternativas && Array.isArray(conteudo.alternativas)) {
            conteudo.alternativas.forEach((alt, index) => {
                html += `
                    <button type="button" class="btn btn-outline-primary btn-resposta text-start" 
                            data-id="${index}" onclick="selecionarResposta(this)">
                        ${alt.texto}
                    </button>
                `;
            });
        }
        html += '</div>';
        return html;
    }

    function renderizarTextoLivre(conteudo) {
        const dica = conteudo.dica || '';
        
        return `
            <div class="mb-3">
                <label for="respostaTextoLivre" class="form-label">Sua resposta:</label>
                <textarea id="respostaTextoLivre" class="form-control" rows="4" placeholder="Digite sua resposta aqui..."></textarea>
                ${dica ? `<div class="form-text text-muted"><i class="fas fa-lightbulb me-1"></i>${dica}</div>` : ''}
            </div>
        `;
    }

    function renderizarCompletar(conteudo) {
        const fraseCompletar = conteudo.frase_completar || '';
        const placeholderCompletar = conteudo.placeholder || 'Digite sua resposta...';
        const dica = conteudo.dica || '';
        
        return `
            <div class="mb-3">
                <label for="respostaCompletar" class="form-label">Complete a frase:</label>
                <div class="mb-3">
                    <p class="fs-5 mb-3">${fraseCompletar}</p>
                    <input type="text" class="form-control" id="respostaCompletar" placeholder="${placeholderCompletar}" style="pointer-events: auto; cursor: text;">
                </div>
                ${dica ? `<div class="form-text text-muted"><i class="fas fa-lightbulb me-1"></i>${dica}</div>` : ''}
            </div>
        `;
    }
    
    function renderizarExercicioEspecial(conteudo) {
        const tipoEspecial = conteudo.tipo_exercicio || 'observar';
        
        if (tipoEspecial === 'observar') {
            return renderizarObservar(conteudo);
        } else if (tipoEspecial === 'completar') {
            return renderizarCompletarEspecial(conteudo);
        } else if (tipoEspecial === 'alternativa') {
            return renderizarAlternativaEspecial(conteudo);
        }
        
        return renderizarObservar(conteudo);
    }
    
    function renderizarObservar(conteudo) {
        const linkVideo = conteudo.link_video || '';
        const letraMusica = conteudo.letra_musica || '';
        
        return `
            <div class="exercicio-especial-container">
                <div class="mb-4">
                    <h6 class="text-center mb-3">🎥 Vídeo/Áudio</h6>
                    <div class="text-center mb-4">
                        <div class="video-container">
                            <iframe width="100%" height="315" src="${convertYouTubeUrl(linkVideo)}" 
                                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <h6>Letra da Música:</h6>
                    <div class="letra-container p-3 bg-light rounded">
                        <pre style="white-space: pre-wrap; font-family: inherit;">${letraMusica}</pre>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Assista ao vídeo e acompanhe a letra. Clique em "Enviar Resposta" quando terminar.
                </div>
            </div>
        `;
    }
    
    function renderizarCompletarEspecial(conteudo) {
        const linkVideo = conteudo.link_video || '';
        const letraMusica = conteudo.letra_musica || '';
        const palavrasCompletar = conteudo.palavras_completar || '';
        
        // Processar letra removendo palavras
        let letraProcessada = letraMusica;
        if (palavrasCompletar) {
            const palavras = palavrasCompletar.split(',').map(p => p.trim());
            palavras.forEach(palavra => {
                const regex = new RegExp(`\\b${palavra}\\b`, 'gi');
                letraProcessada = letraProcessada.replace(regex, '______');
            });
        }
        
        return `
            <div class="exercicio-especial-container">
                <div class="mb-4">
                    <h6 class="text-center mb-3">🎥 Vídeo/Áudio</h6>
                    <div class="text-center mb-4">
                        <div class="video-container">
                            <iframe width="100%" height="315" src="${convertYouTubeUrl(linkVideo)}" 
                                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <h6>Complete a letra:</h6>
                    <div class="letra-container p-3 bg-light rounded">
                        <pre style="white-space: pre-wrap; font-family: inherit;">${letraProcessada}</pre>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="respostaEspecial" class="form-label">Palavras que faltam (separadas por vírgula):</label>
                    <textarea class="form-control" id="respostaEspecial" rows="3" 
                              placeholder="Digite as palavras que faltam, separadas por vírgula"></textarea>
                </div>
            </div>
        `;
    }
    
    function renderizarAlternativaEspecial(conteudo) {
        const linkVideo = conteudo.link_video || '';
        const letraMusica = conteudo.letra_musica || '';
        const alternativas = conteudo.alternativas || {};
        
        let htmlAlternativas = '';
        if (alternativas.a) {
            ['a', 'b', 'c', 'd'].forEach(letra => {
                if (alternativas[letra]) {
                    htmlAlternativas += `
                        <button type="button" class="btn btn-outline-primary btn-resposta text-start mb-2" 
                                data-id="${letra}" onclick="selecionarResposta(this)">
                            ${letra.toUpperCase()}) ${alternativas[letra]}
                        </button>
                    `;
                }
            });
        }
        
        return `
            <div class="exercicio-especial-container">
                <div class="mb-4">
                    <h6 class="text-center mb-3">🎥 Vídeo/Áudio</h6>
                    <div class="text-center mb-4">
                        <div class="video-container">
                            <iframe width="100%" height="315" src="${convertYouTubeUrl(linkVideo)}" 
                                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <h6>Letra da Música:</h6>
                    <div class="letra-container p-3 bg-light rounded">
                        <pre style="white-space: pre-wrap; font-family: inherit;">${letraMusica}</pre>
                    </div>
                </div>
                <div class="mb-4">
                    <h6>Escolha a alternativa correta:</h6>
                    <div class="d-grid gap-2">
                        ${htmlAlternativas}
                    </div>
                </div>
            </div>
        `;
    }

    // ==================== FUNÇÕES DE RESPOSTA ====================

    // Função para selecionar resposta (botão de múltipla escolha)
    window.selecionarResposta = function(button) {
        console.log('Resposta selecionada:', button.dataset.id, button.textContent);
        
        // Remover mensagens de erro existentes
        document.querySelectorAll('.alert-warning').forEach(alert => {
            if (alert.textContent.includes('Selecione') || alert.textContent.includes('resposta')) {
                alert.remove();
            }
        });
        
        // Remover seleção de todos os botões
        document.querySelectorAll(".btn-resposta").forEach(btn => {
            btn.classList.remove("selected", "btn-primary", "btn-success", "selecionada", "active");
            btn.classList.add("btn-outline-primary");
            btn.style.pointerEvents = 'auto';
            btn.style.cursor = 'pointer';
            btn.removeAttribute('data-selected');
        });
        
        // Selecionar o botão clicado
        button.classList.remove("btn-outline-primary");
        button.classList.add("selected", "btn-primary", "selecionada", "active");
        button.setAttribute('data-selected', 'true');
        
        // Garantir que o botão seja clicável
        button.style.pointerEvents = 'auto';
        button.style.cursor = 'pointer';
        
        // Para múltipla escolha, usar o índice numérico
        if (button.dataset.id && !isNaN(button.dataset.id)) {
            respostaSelecionada = parseInt(button.dataset.id);
        } else {
            respostaSelecionada = button.dataset.id;
        }
        
        console.log('Resposta processada:', respostaSelecionada);
    };

    window.enviarResposta = function() {
        if (!exercicioAtual) {
            alert("Exercício não carregado");
            return;
        }
        
        // Para exercícios de texto livre, pegar valor do textarea
        if (!respostaSelecionada && respostaSelecionada !== 0) {
            const textareaResposta = document.getElementById('respostaTextoLivre');
            if (textareaResposta) {
                respostaSelecionada = textareaResposta.value.trim();
            }
        }
        
        // Para exercícios de completar, pegar valor do input
        if (!respostaSelecionada && respostaSelecionada !== 0) {
            const inputCompletar = document.getElementById('respostaCompletar');
            if (inputCompletar) {
                respostaSelecionada = inputCompletar.value.trim();
            }
        }
        
        // Para exercícios especiais, pegar valor do textarea especial
        if (!respostaSelecionada && respostaSelecionada !== 0) {
            const textareaEspecial = document.getElementById('respostaEspecial');
            if (textareaEspecial) {
                respostaSelecionada = textareaEspecial.value.trim();
            }
        }
        
        // Para exercícios especiais de observar, sempre permitir avançar
        if (exercicioAtual.tipoExercicioDeterminado === 'especial' && 
            exercicioAtual.conteudo?.tipo_exercicio === 'observar') {
            respostaSelecionada = 'observado';
        }
        
        // Verificar se há botão selecionado
        if (!respostaSelecionada && respostaSelecionada !== 0) {
            const botaoSelecionado = document.querySelector('.btn-resposta.selected, .btn-resposta[data-selected="true"]');
            if (botaoSelecionado && botaoSelecionado.dataset.id !== undefined) {
                respostaSelecionada = !isNaN(botaoSelecionado.dataset.id) ? parseInt(botaoSelecionado.dataset.id) : botaoSelecionado.dataset.id;
            }
        }
        
        if (!respostaSelecionada && respostaSelecionada !== 0) {
            // Remover mensagens de erro existentes
            document.querySelectorAll('.alert-warning').forEach(alert => {
                if (alert.textContent.includes('Selecione') || alert.textContent.includes('resposta')) {
                    alert.remove();
                }
            });
            return;
        }

        const tipoExercicio = exercicioAtual.tipoExercicioDeterminado || 'multipla_escolha';
        let apiUrl = '/App_idiomas/api/processar_exercicio.php';
        
        // Usar API específica para listening
        if (tipoExercicio === 'listening' || tipoExercicio === 'audicao') {
            apiUrl = '/App_idiomas/api/exercicios/listening.php';
        }
        
        // Para exercícios especiais, simular resposta correta
        if (tipoExercicio === 'especial') {
            const data = { success: true, correto: true, explicacao: 'Exercício especial concluído!' };
            exibirFeedback(data);
            document.getElementById("btnEnviarResposta").style.display = "none";
            document.getElementById("btnProximoExercicio").style.display = "block";
            return;
        }
        
        console.log('Enviando resposta:', {
            exercicio_id: exercicioAtual.id,
            resposta: respostaSelecionada,
            tipo_exercicio: tipoExercicio,
            api_url: apiUrl
        });

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                exercicio_id: exercicioAtual.id,
                resposta: respostaSelecionada,
                tipo_exercicio: tipoExercicio
            })
        })
        .then(response => {
            console.log('Status da resposta:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Resposta recebida:', text);
            try {
                const data = JSON.parse(text);
                if (data?.success !== undefined) {
                    exibirFeedback(data);
                    document.getElementById("btnEnviarResposta").style.display = "none";
                    document.getElementById("btnProximoExercicio").style.display = "block";
                } else {
                    alert("Erro: " + (data?.message || 'Resposta inválida'));
                }
            } catch (e) {
                console.error('Erro ao fazer parse do JSON:', e);
                console.error('Texto recebido:', text);
                alert("Erro: Resposta inválida do servidor");
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert("Erro de conexão: " + error.message);
        });
    };

    window.exibirFeedback = function(data) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.className = `alert ${data.correto ? 'alert-success' : 'alert-danger'} mt-3`;
        feedbackDiv.innerHTML = `
            <h6><i class="fas ${data.correto ? 'fa-check-circle' : 'fa-times-circle'} me-2"></i>
            ${data.correto ? '✅ Correto!' : '❌ Incorreto!'}</h6>
            <p>${data.explicacao || 'Sem explicação'}</p>
            ${data.transcricao ? `<p><strong>Transcrição:</strong> "${data.transcricao}"</p>` : ''}
            ${data.dicas_compreensao ? `<p><strong>Dica:</strong> ${data.dicas_compreensao}</p>` : ''}
        `;
        document.getElementById("conteudoExercicio").appendChild(feedbackDiv);

        document.querySelectorAll(".btn-resposta").forEach(btn => {
            btn.disabled = true;
            const isCorrect = btn.dataset.id == (data.alternativa_correta_id || 0);
            const isSelected = btn.classList.contains("selected");
            
            if (isCorrect) {
                btn.className = "btn btn-success";
            } else if (isSelected) {
                btn.className = "btn btn-danger";
            } else {
                btn.className = "btn btn-secondary";
            }
        });
        
        // Atualizar progresso do bloco após cada exercício
        if (blocoAtual) {
            atualizarProgressoBloco(blocoAtual);
        }
    };

    // ==================== FUNÇÃO PARA ATUALIZAR PROGRESSO ====================
    
    // Função para atualizar progresso quando exercício é concluído
    function atualizarProgressoBloco(blocoId) {
        const formData = new FormData();
        formData.append('bloco_id', blocoId);
        
        fetch('../controller/update_progress.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Progresso atualizado:', data);
                // Recarregar os blocos para mostrar o novo estado
                carregarBlocosTodasUnidades();
                
                // Mostrar feedback visual se concluído
                if (data.concluido) {
                    mostrarToast('🎉 Bloco concluído! Parabéns!', 'success');
                }
            } else {
                console.error('Erro ao atualizar progresso:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição de progresso:', error);
        });
    }

    // Função para avançar para o próximo exercício - VERSÃO CORRIGIDA
    window.proximoExercicio = function() {
        exercicioIndex++;
        
        if (exercicioIndex < exerciciosLista.length) {
            carregarExercicio(exercicioIndex);
        } else {
            // TODOS OS EXERCÍCIOS FORAM CONCLUÍDOS
            console.log('Todos os exercícios do bloco concluídos');
            
            mostrarMensagemSucessoBloco();
            
            // Esconder modal após delay
            setTimeout(() => {
                modalExercicios.hide();
                recarregarBlocosAposCompletar();
            }, 3000);
        }
    };

    // Função para voltar para blocos
    window.voltarParaBlocos = function() {
        modalExercicios.hide();
        recarregarBlocosAposCompletar();
    };

    // ==================== FUNCIONALIDADES DE TROCA DE IDIOMAS ====================
    
    // Função para trocar idioma via AJAX
    window.trocarIdiomaAjax = function(novoIdioma) {
        if (!novoIdioma || typeof novoIdioma !== 'string' || novoIdioma.trim() === '') {
            console.error('Idioma inválido:', novoIdioma);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'trocar_idioma');
        formData.append('idioma', novoIdioma.trim());
        
        fetch('../controller/IdiomaController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else if (data.redirect_quiz) {
                window.location.href = `../../quiz.php?idioma=${encodeURIComponent(novoIdioma)}`;
            } else {
                console.error('Erro do servidor:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
        });
    };
    
    // ==================== FUNÇÕES DE ÁUDIO ====================
    
    window.speakText = function(text, lang) {
        console.log('speakText chamado:', text, lang);
        
        if (!text || text.trim() === '') {
            alert('Nenhum texto para reproduzir');
            return;
        }
        
        if ('speechSynthesis' in window) {
            // Cancelar qualquer fala anterior
            window.speechSynthesis.cancel();
            
            // Aguardar um pouco para garantir que cancelou
            setTimeout(() => {
                const utterance = new SpeechSynthesisUtterance(text.trim());
                
                const langMap = {
                    'en-us': 'en-US',
                    'en-gb': 'en-GB', 
                    'pt-br': 'pt-BR',
                    'es-es': 'es-ES',
                    'fr-fr': 'fr-FR',
                    'de-de': 'de-DE',
                    'ja-jp': 'ja-JP',
                    'ko-kr': 'ko-KR'
                };
                
                utterance.lang = langMap[lang] || langMap['en-us'] || 'en-US';
                utterance.rate = 0.8;
                utterance.pitch = 1;
                utterance.volume = 1;
                
                utterance.onstart = function() {
                    console.log('Áudio iniciado');
                };
                
                utterance.onend = function() {
                    console.log('Áudio finalizado');
                };
                
                utterance.onerror = function(event) {
                    console.error('Erro no áudio:', event);
                    alert('Erro ao reproduzir áudio. Tente novamente.');
                };
                
                console.log('Iniciando síntese de voz...');
                window.speechSynthesis.speak(utterance);
            }, 100);
        } else {
            alert('Seu navegador não suporta síntese de voz.');
        }
    };
    
    // ==================== FUNCIONALIDADES DE FLASHCARDS ====================
       
    // Função para abrir modal de adicionar palavra
    window.abrirModalAdicionarPalavra = function() {
        document.getElementById('formAdicionarPalavra').reset();
        document.getElementById('palavraIdioma').value = '<?php echo htmlspecialchars($idioma_escolhido ?? "Inglês"); ?>';
        document.getElementById('palavraNivel').value = '<?php echo htmlspecialchars($nivel_usuario ?? "A1"); ?>';
        modalAdicionarPalavra.show();
    };

    // Função para virar preview do flashcard
    window.virarPreviewPalavra = function() {
        const preview = document.getElementById('palavraPreview');
        preview.classList.toggle('flipped');
    };

    // Função para atualizar preview do flashcard
    function atualizarPreviewFlashcard() {
        const frente = document.getElementById('palavraFrente').value || 'Digite o conteúdo da frente';
        const verso = document.getElementById('palavraVerso').value || 'Digite o conteúdo do verso';
        const dica = document.getElementById('palavraDica').value;

        document.getElementById('previewPalavraFrente').textContent = frente;
        document.getElementById('previewPalavraVerso').textContent = verso;

        const dicaContainer = document.getElementById('previewDicaContainer');
        const dicaText = document.getElementById('previewPalavraDica');
        
        if (dica && dica.trim() !== '') {
            dicaText.textContent = dica;
            dicaContainer.style.display = 'block';
        } else {
            dicaContainer.style.display = 'none';
        }
    }
       
    // Função para salvar palavra
    window.salvarPalavra = function() {
        const form = document.getElementById('formAdicionarPalavra');
        const formData = new FormData(form);
        formData.append('action', 'adicionar_flashcard');
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalAdicionarPalavra.hide();
                carregarPalavras();
                mostrarToast('Palavra adicionada com sucesso!', 'success');
            } else {
                mostrarToast('Erro: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro de conexão. Tente novamente.', 'danger');
        });
    };
       
    // Função para carregar palavras do usuário
    window.carregarPalavras = function() {
        const status = document.getElementById('filtroPalavrasStatus').value;
        const container = document.getElementById('listaPalavras');
        
        console.log('=== CARREGAR PALAVRAS INICIADO ===');
        console.log('Status:', status);
        console.log('URL do controller:', 'flashcard_controller.php');
        
        // Mostra loading
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2 text-muted">Carregando suas palavras...</p>
            </div>
        `;
        
        const formData = new FormData();
        formData.append('action', 'listar_flashcards_painel');
        formData.append('idioma', '<?php echo htmlspecialchars($idioma_escolhido ?? ''); ?>');
        if (status !== '') {
            formData.append('status', status);
        }
        
        console.log('Enviando requisição para flashcard_controller.php');
        
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Resposta recebida - Status:', response.status);
            console.log('Resposta OK:', response.ok);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            if (data.success) {
                palavrasCarregadas = data.flashcards;
                exibirPalavras(data.flashcards);
            } else {
                console.error('Erro do servidor:', data.message);
                exibirErroPalavras(data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            console.error('Detalhes do erro:', error.message);
            exibirErroPalavras('Erro de conexão. Tente novamente. Detalhes: ' + error.message);
        });
    };

    // Função para exibir palavras na interface
    window.exibirPalavras = function(palavras) {
        const container = document.getElementById('listaPalavras');
       
        if (!palavras || palavras.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma palavra encontrada. Adicione suas primeiras palavras!
                    </div>
                </div>
            `;
            return;
        }
       
        let html = '';
        palavras.forEach(palavra => {
            const statusClass = palavra.aprendido == 1 ? 'success' : 'warning';
            const statusText = palavra.aprendido == 1 ? 'Aprendida' : 'Estudando';
            const statusIcon = palavra.aprendido == 1 ? 'fa-check-circle' : 'fa-clock';
           
            html += `
                <div class="col-md-6 mb-3 palavra-item" data-palavra="${palavra.palavra_frente.toLowerCase()}" data-traducao="${palavra.palavra_verso.toLowerCase()}">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">${palavra.palavra_frente}</h6>
                                <span class="badge bg-${statusClass}">
                                    <i class="fas ${statusIcon} me-1"></i>${statusText}
                                </span>
                            </div>
                            <p class="card-text text-muted mb-2">${palavra.palavra_verso}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    ${palavra.idioma} • ${palavra.nivel}
                                    ${palavra.categoria ? ' • ' + palavra.categoria : ''}
                                </small>
                                <div class="btn-group" role="group">
                                    ${palavra.aprendido == 1 ? 
                                        `<button class="btn btn-outline-warning btn-sm" onclick="alterarStatusPalavra(${palavra.id}, false)">
                                            <i class="fas fa-undo me-1"></i>Estudar
                                        </button>` :
                                        `<button class="btn btn-outline-success btn-sm" onclick="alterarStatusPalavra(${palavra.id}, true)">
                                            <i class="fas fa-check me-1"></i>Aprendi
                                        </button>`
                                    }
                                    <button class="btn btn-outline-danger btn-sm" onclick="excluirPalavra(${palavra.id})">
                                        <i class="fas fa-trash me-1"></i>Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
       
        container.innerHTML = html;
    };
       
    // Função para filtrar palavras localmente
    window.filtrarPalavrasLocal = function() {
        const busca = document.getElementById('filtroPalavrasBusca').value.toLowerCase();
        const items = document.querySelectorAll('.palavra-item');
       
        items.forEach(item => {
            const palavra = item.dataset.palavra;
            const traducao = item.dataset.traducao;
           
            if (palavra.includes(busca) || traducao.includes(busca)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    };
       
    // Função para alterar status de palavra (aprendida/não aprendida)
    window.alterarStatusPalavra = function(idFlashcard, marcarComoAprendida) {
        const action = marcarComoAprendida ? 'marcar_como_aprendido' : 'desmarcar_como_aprendido';
       
        const formData = new FormData();
        formData.append('action', action);
        formData.append('id_flashcard', idFlashcard);
       
        fetch('flashcard_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarPalavras();
                mostrarToast('Status atualizado com sucesso!', 'success');
            } else {
                mostrarToast('Erro: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro de conexão. Tente novamente.', 'danger');
        });
    };
       
    // Função para excluir palavra
    window.excluirPalavra = function(idFlashcard) {
        // Prepara e exibe o modal de confirmação
        document.getElementById('mensagemModalExclusao').innerHTML = "Tem certeza que deseja excluir esta palavra? Esta ação não pode ser desfeita.";
        
        const btnConfirmar = document.getElementById('btnConfirmarExclusao');
        
        // Remove listeners antigos para evitar múltiplas execuções
        const novoBtn = btnConfirmar.cloneNode(true);
        btnConfirmar.parentNode.replaceChild(novoBtn, btnConfirmar);

        novoBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'excluir_flashcard');
            formData.append('id_flashcard', idFlashcard);
        
            fetch('flashcard_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                modalConfirmarExclusao.hide();
                if (data.success) {
                    carregarPalavras();
                    mostrarToast('Palavra excluída com sucesso!', 'success');
                } else {
                    mostrarToast('Erro: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                modalConfirmarExclusao.hide();
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'danger');
            });
        });
        modalConfirmarExclusao.show();
    };
       
    // Função para exibir erro ao carregar palavras
    window.exibirErroPalavras = function(mensagem) {
        const container = document.getElementById('listaPalavras');
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${mensagem}
                </div>
            </div>
        `;
    };
    
    // Função para mostrar mensagem de sucesso do bloco
    window.mostrarMensagemSucessoBloco = function() {
        const conteudoExercicioDiv = document.getElementById("conteudoExercicio");
        conteudoExercicioDiv.innerHTML = `
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-trophy" style="font-size: 4rem; color: #ffd700;"></i>
                </div>
                <h3 class="text-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>Parabéns!
                </h3>
                <p class="fs-5 mb-4">Você completou todos os exercícios deste bloco com sucesso!</p>
                <div class="alert alert-success d-inline-block">
                    <i class="fas fa-star me-2"></i>
                    Bloco concluído! Continue sua jornada de aprendizado.
                </div>
                <div class="mt-4">
                    <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <small class="text-muted">Retornando aos blocos...</small>
                </div>
            </div>
        `;
        
        document.getElementById("btnEnviarResposta").style.display = "none";
        document.getElementById("btnProximoExercicio").style.display = "none";
        
        // Mostrar toast adicional
        mostrarToast('Bloco completado! Parabéns pelo seu progresso!', 'success');
        
        // Atualizar progresso do caminho
        fetch('../controller/update_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `caminho_id=${caminhoAtual}&tipo=bloco`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Progresso atualizado:', data.progresso + '%');
                if (data.concluido) {
                    mostrarToast('🎉 Caminho concluído! Parabéns!', 'success');
                }
            }
        })
        .catch(error => console.error('Erro ao atualizar progresso:', error));
    };

    // CORREÇÃO: Função exibirTeorias completa
    function exibirTeorias(teorias) {
        const container = document.getElementById('listaTeorias');
        
        if (!teorias || teorias.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhuma teoria encontrada para seu nível atual.
                </div>
            `;
            return;
        }

        let html = '<div class="row">';
        teorias.forEach((teoria, index) => {
            const gradientColors = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
            ];
            const gradient = gradientColors[index % gradientColors.length];
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="teoria-card" onclick="abrirTeoriaConteudo(${teoria.id}, '${teoria.titulo.replace(/'/g, "\\'")}')"
                         style="background: ${gradient};">
                        <div class="teoria-card-header">
                            <div class="teoria-numero">${index + 1}</div>
                            <span class="teoria-nivel">${teoria.nivel || '<?php echo htmlspecialchars($nivel_usuario); ?>'}</span>
                        </div>
                        <div class="teoria-card-body">
                            <h5 class="teoria-titulo">${teoria.titulo}</h5>
                            <p class="teoria-resumo">${teoria.resumo || 'Clique para ver o conteúdo completo'}</p>
                        </div>
                        <div class="teoria-card-footer">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // Função para abrir modal de teorias
    window.abrirTeorias = function() {
        const container = document.getElementById('listaTeorias');
        container.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2 text-muted">Carregando teorias...</p>
            </div>
        `;
        
        modalTeorias.show();
        
        // Carregar teorias do nível atual
        fetch(`../controller/get_teorias.php?nivel=<?php echo htmlspecialchars($nivel_usuario ?? 'A1'); ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exibirTeorias(data.teorias);
                } else {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma teoria encontrada para seu nível atual.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar teorias:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar teorias. Tente novamente.
                    </div>
                `;
            });
    };
    
    // Função para abrir conteúdo de uma teoria
    window.abrirTeoriaConteudo = function(teoriaId, titulo) {
        document.getElementById('tituloTeoriaConteudo').innerHTML = `
            <i class="fas fa-book me-2"></i>${titulo}
        `;
        document.getElementById('conteudoTeoria').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2 text-muted">Carregando conteúdo...</p>
            </div>
        `;
        document.getElementById('btnIniciarExercicios').style.display = 'none';
        
        modalTeorias.hide();
        modalTeoriaConteudo.show();
        
        // Carregar conteúdo da teoria
        fetch(`../controller/get_teoria_conteudo.php?id=${teoriaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('conteudoTeoria').innerHTML = formatarConteudoTeoria(data.teoria.conteudo);
                } else {
                    document.getElementById('conteudoTeoria').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar conteúdo da teoria.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar teoria:', error);
                document.getElementById('conteudoTeoria').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar conteúdo da teoria.
                    </div>
                `;
            });
    };


    
    // Função para abrir exercício especial
    window.abrirExercicioEspecial = function(exercicioId, titulo) {
        console.log('Abrindo exercício especial:', exercicioId, titulo);
        
        // Extrair o ID numérico
        const idNumerico = exercicioId.replace('especial_', '');
        
        document.getElementById("tituloExercicios").textContent = `Exercício Especial: ${titulo}`;
        
        fetch(`../../admin/controller/get_exercicios_especiais.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const exercicio = data.exercicios.find(e => e.id == idNumerico);
                    if (exercicio) {
                        const exercicioFormatado = {
                            id: exercicioId,
                            tipo: 'especial',
                            pergunta: exercicio.titulo,
                            conteudo: exercicio.conteudo_completo,
                            categoria: 'especial'
                        };
                        
                        exerciciosLista = [exercicioFormatado];
                        exercicioIndex = 0;
                        carregarExercicio(0);
                        modalExercicios.show();
                    }
                } else {
                    alert('Erro ao carregar exercício especial');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar exercício especial');
            });
    };

    // Função para converter URLs do YouTube
    function convertYouTubeUrl(url) {
        if (!url) return '';
        
        // Diferentes formatos de URL do YouTube
        const patterns = [
            /(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/,
            /(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)/,
            /(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)/
        ];
        
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                return `https://www.youtube.com/embed/${match[1]}?rel=0&modestbranding=1`;
            }
        }
        
        return url; // Retorna a URL original se não for YouTube
    }
    
    // Função global para converter URLs do YouTube (para uso nos templates)
    window.convertYouTubeUrl = convertYouTubeUrl;
    
    // Função para mostrar toast
    window.mostrarToast = function(mensagem, tipo = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();
        
        const iconMap = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-triangle',
            'warning': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${iconMap[tipo]} me-2"></i>${mensagem}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Remove o toast do DOM após ser ocultado
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    };

    </script>
    
    <!-- Container de Toasts -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer">
        <!-- Os toasts serão inseridos aqui dinamicamente -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function trocarIdioma(idioma) {
        if (!idioma || idioma.trim() === '') {
            console.error('Idioma inválido');
            return;
        }
        
        const formData = new FormData();
        formData.append('trocar_idioma', '1');
        formData.append('novo_idioma', idioma.trim());
        
        fetch('painel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            window.location.reload();
        });
    }
    
    // Função duplicada removida - já está sendo tratada acima
    </script>
</body>
</html>