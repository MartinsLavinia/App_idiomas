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
     <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
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

        /* Menu Hamburguer - CORREÇÃO APLICADA AQUI */
        .menu-toggle {
            display: none; /* Escondido por padrão */
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

        /* CORREÇÃO PRINCIPAL: Menu hamburger só aparece no mobile */
        @media (max-width: 992px) {
            .menu-toggle {
                display: flex !important; /* Aparece apenas no mobile */
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

        /* ===== ESTILOS PARA SEÇÃO DE UNIDADES ===== */

        /* Container principal das unidades */
        .unidades-container {
            margin: 2rem 0;
        }

        /* Card da unidade */
        .unidade-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border: 2px solid #e3e6ff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(106, 13, 173, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            margin-bottom: 2rem;
            position: relative;
        }

        .unidade-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--roxo-principal), var(--amarelo-detalhe));
        }

        .unidade-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(106, 13, 173, 0.2);
            border-color: var(--roxo-principal);
        }

        /* Header da unidade */
        .unidade-header {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            padding: 2rem;
            border-bottom: 1px solid #f0f2ff;
            position: relative;
        }

        .unidade-numero {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        }

        .unidade-titulo {
            color: var(--roxo-principal);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .unidade-descricao {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* Body da unidade */
        .unidade-body {
            padding: 2rem;
        }

        /* Informações da unidade */
        .unidade-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9ff;
            border-radius: 12px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .info-item i {
            color: var(--roxo-principal);
            font-size: 1rem;
        }

        /* Progresso da unidade */
        .unidade-progresso {
            margin-bottom: 1.5rem;
        }

        .progresso-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progresso-texto {
            font-weight: 600;
            color: var(--roxo-principal);
            font-size: 0.9rem;
        }

        .progresso-porcentagem {
            font-weight: 700;
            color: var(--roxo-escuro);
            font-size: 0.9rem;
        }

        .progresso-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .progresso-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--roxo-principal), var(--amarelo-detalhe));
            border-radius: 10px;
            transition: width 0.8s ease;
            position: relative;
        }

        .progresso-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Container dos blocos */
        .blocos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        /* Cards dos blocos - Versão Melhorada */
        .bloco-card {
            background: white;
            border: 2px solid #f0f2ff;
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .bloco-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--roxo-principal), var(--amarelo-detalhe));
            transition: width 0.3s ease;
        }

        .bloco-card:hover::before {
            width: 6px;
        }

        /* Estados dos blocos */
        .bloco-card-disponivel {
            cursor: pointer;
            border-color: #d1e7ff;
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
        }

        .bloco-card-disponivel:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 123, 255, 0.15);
            border-color: var(--roxo-principal);
        }

        .bloco-card-concluido {
            cursor: pointer;
            border-color: #d4edda;
            background: linear-gradient(135deg, #ffffff 0%, #f8fff8 100%);
        }

        .bloco-card-concluido:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(40, 167, 69, 0.15);
            border-color: #28a745;
        }

        .bloco-card-bloqueado {
            cursor: not-allowed;
            border-color: #e9ecef;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            opacity: 0.7;
        }

        /* Header do bloco */
        .bloco-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .bloco-icon-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .bloco-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .bloco-card-disponivel .bloco-icon {
            background: linear-gradient(135deg, var(--roxo-principal), #8b5cf6);
            color: white;
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        }

        .bloco-card-concluido .bloco-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .bloco-card-bloqueado .bloco-icon {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .bloco-titulo {
            font-weight: 600;
            color: var(--preto-texto);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .bloco-subtitulo {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 0;
        }

        /* Body do bloco */
        .bloco-body {
            flex-grow: 1;
            margin-bottom: 1rem;
        }

        .bloco-descricao {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }

        .bloco-descricao::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20px;
            background: linear-gradient(transparent, white);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bloco-card:hover .bloco-descricao::after {
            opacity: 1;
        }

        /* Footer do bloco */
        .bloco-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bloco-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .bloco-stats {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Badges de status */
        .bloco-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-disponivel {
            background: linear-gradient(135deg, var(--roxo-principal), #8b5cf6);
            color: white;
        }

        .badge-concluido {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .badge-bloqueado {
            background: #6c757d;
            color: white;
        }

        /* Progresso do bloco */
        .bloco-progresso {
            width: 100%;
            margin-top: 0.75rem;
        }

        .bloco-progresso-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
            font-size: 0.8rem;
        }

        .bloco-progresso-texto {
            color: #6c757d;
        }

        .bloco-progresso-porcentagem {
            color: var(--roxo-escuro);
            font-weight: 600;
        }

        .bloco-progresso-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .bloco-progresso-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .bloco-card-disponivel .bloco-progresso-fill {
            background: linear-gradient(90deg, var(--roxo-principal), #8b5cf6);
        }

        .bloco-card-concluido .bloco-progresso-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .bloco-card-bloqueado .bloco-progresso-fill {
            background: #6c757d;
        }

        /* Indicador de novo conteúdo */
        .novo-indicator {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, var(--amarelo-detalhe), #ffed4a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--preto-texto);
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mensagem quando não há unidades */
        .sem-unidades {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .sem-unidades i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .sem-unidades h3 {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .unidade-header {
                padding: 1.5rem;
            }
            
            .unidade-body {
                padding: 1.5rem;
            }
            
            .blocos-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .bloco-card {
                padding: 1.25rem;
                min-height: 160px;
            }
            
            .unidade-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            .info-item {
                justify-content: flex-start;
            }
        }

        @media (max-width: 576px) {
            .unidade-card {
                margin-bottom: 1.5rem;
            }
            
            .unidade-header {
                padding: 1.25rem;
            }
            
            .unidade-body {
                padding: 1.25rem;
            }
            
            .bloco-header {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .bloco-icon-container {
                width: 100%;
            }
            
            .bloco-footer {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }
        }

        /* Animações especiais */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .unidade-card {
            animation: slideInUp 0.6s ease forwards;
        }

        .unidade-card:nth-child(odd) {
            animation-delay: 0.1s;
        }

        .unidade-card:nth-child(even) {
            animation-delay: 0.2s;
        }

        /* Efeito de brilho hover */
        .bloco-card-disponivel::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(106, 13, 173, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .bloco-card-disponivel:hover::after {
            opacity: 1;
        }

        /* Header do card principal */
        .card-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
            color: white !important;
            border-bottom: none !important;
            padding: 2rem !important;
        }

        .card-header h2 {
            color: white !important;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.9) !important;
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        /* Estilos para exercícios de listening */
        .audio-player-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
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
        
        .tts-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .tts-container .btn {
            min-width: 200px;
            font-size: 1.1rem;
            padding: 12px 24px;
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

        /* ===== ESTILOS DO FLASHCARD IGUAL AO PRIMEIRO CÓDIGO ===== */
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
            flex-direction: column;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--cinza-medio);
            overflow: hidden;
        }

        .flashcard-front {
            background: var(--branco);
        }

        .flashcard-back {
            background: var(--branco);
            transform: rotateY(180deg);
        }

        .flashcard-header {
            padding: 1rem 1.5rem;
            color: var(--branco);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .flashcard-front .flashcard-header {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        }

        .flashcard-back .flashcard-header {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
        }

        .flashcard-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-size: 1.4rem;
            font-weight: 600;
            line-height: 1.4;
            text-align: center;
        }

        .flashcard-hint {
            font-size: 1rem;
            opacity: 0.8;
            font-style: italic;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .flashcard-footer {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Container do Flashcard para estudo */
        .flashcard-container {
            perspective: 1000px;
            margin-bottom: 2rem;
            min-height: 420px;
        }

        .flashcard {
            position: relative;
            width: 100%;
            height: 400px;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--cinza-medio);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Botões de Resposta */
        .response-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-response {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-response:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-again {
            background: linear-gradient(135deg, #e55353, #c82333);
            color: white;
        }

        .btn-hard {
            background: linear-gradient(135deg, #8a2be2, #6a0dad);
            color: white;
        }

        .btn-good {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
        }

        .btn-easy {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
        }

        /* Botões com estilo unificado */
        .btn-action {
            padding: 0.65rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
            color: var(--branco);
        }

        .btn-action-warning {
            background: linear-gradient(135deg, var(--amarelo-detalhe), #f39c12);
            color: var(--preto-texto);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-action-warning:hover {
            background: linear-gradient(135deg, #f39c12, var(--amarelo-detalhe));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            color: var(--preto-texto);
        }

        /* Animações */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .flashcard-container.slide-in {
            animation: slideInUp 0.5s ease-out;
        }

        /* Responsive para flashcards */
        @media (max-width: 768px) {
            .flashcard {
                height: 300px;
            }
            
            .flashcard-content {
                font-size: 1.2rem;
            }

            .response-buttons {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .btn-response {
                padding: 0.75rem 1rem;
                font-size: 1rem;
                min-width: 100px;
            }
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
    <!-- Menu Hamburguer - AGORA SÓ APARECE NO MOBILE -->
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
                                <!-- Seção Unidades -->
                                <div class="unidades-container">
                                    <?php foreach ($unidades as $unidade): ?>
                                        <div class="unidade-card">
                                            <div class="unidade-header">
                                                <div class="unidade-numero"><?php echo htmlspecialchars($unidade["numero_unidade"]); ?></div>
                                                <h3 class="unidade-titulo"><?php echo htmlspecialchars($unidade["nome_unidade"]); ?></h3>
                                                <p class="unidade-descricao"><?php echo htmlspecialchars($unidade["descricao"]); ?></p>
                                            </div>
                                            
                                            <div class="unidade-body">
                                                <div class="unidade-info">
                                                    <div class="info-item">
                                                        <i class="fas fa-language"></i>
                                                        <span><?php echo htmlspecialchars($idiomas_display[$idioma_escolhido] ?? $idioma_escolhido); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <i class="fas fa-chart-line"></i>
                                                        <span>Nível <?php echo htmlspecialchars($nivel_usuario); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <i class="fas fa-cubes"></i>
                                                        <span>Blocos de aprendizado</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="unidade-progresso">
                                                    <div class="progresso-info">
                                                        <span class="progresso-texto">Progresso da Unidade</span>
                                                        <span class="progresso-porcentagem">0%</span>
                                                    </div>
                                                    <div class="progresso-bar">
                                                        <div class="progresso-fill" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="blocos-container" id="blocos-unidade-<?php echo $unidade['id']; ?>">
                                                    <div class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Carregando blocos...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted">Carregando blocos...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="sem-unidades">
                                    <i class="fas fa-inbox"></i>
                                    <h3>Nenhuma unidade disponível</h3>
                                    <p>Comece sua jornada de aprendizado selecionando um idioma acima.</p>
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
                                        <i class="fas fa-book-open me-2" style="color:#ffd700;"></i>
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
            <div class="col-md-7">
                <h5 class="card-title mb-2">
                    <i class="fas fa-layer-group me-2 text-warning"></i>
                    Flash Cards
                </h5>
                <p class="card-text text-muted mb-0">
                    Estude com flashcards personalizados e melhore sua memorização
                </p>
            </div>

            <!-- Coluna dos botões -->
            <div class="col-md-5 text-end">
                <div class="d-flex gap-2 justify-content-end flex-nowrap">
                    <a href="flashcards.php" class="btn btn-warning px-3">
                        <i class="fas fa-layer-group me-2"></i>Meus Decks
                    </a>
                    <a href="flashcard_estudo.php" class="btn btn-outline-warning px-3">
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
                                    <div class="flashcard-side flashcard-front">
                                        <div class="flashcard-header">
                                            <span>Pergunta</span>
                                            <span class="badge bg-white bg-opacity-25 text-white">Médio</span>
                                        </div>
                                        <div class="flashcard-content">
                                            <span id="previewPalavraFrente">Digite o conteúdo da frente</span>
                                        </div>
                                        <div class="flashcard-footer">
                                            Clique no card para ver a resposta
                                        </div>
                                    </div>
                                    <div class="flashcard-side flashcard-back">
                                        <div class="flashcard-header">
                                            <span>Resposta</span>
                                            <span class="badge bg-black bg-opacity-25 text-black">Médio</span>
                                        </div>
                                        <div class="flashcard-content">
                                            <span id="previewPalavraVerso">Digite o conteúdo do verso</span>
                                        </div>
                                        <div class="flashcard-footer">
                                            Como você se saiu?
                                        </div>
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
    
    // Exibir blocos de uma unidade com sistema de desbloqueio - VERSÃO ATUALIZADA
    function exibirBlocosUnidade(blocos, container, unidadeId) {
        if (!blocos || blocos.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum bloco encontrado para esta unidade.
                    </div>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        
        // Separar blocos normais e especiais
        const blocosNormais = blocos.filter(b => b.tipo !== 'especial');
        const blocosEspeciais = blocos.filter(b => b.tipo === 'especial');
        
        // Determinar estado dos blocos
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
        
        // Exibir blocos normais
        blocosNormais.forEach((bloco, index) => {
            const progresso = bloco.progresso?.progresso_percentual || 0;
            const concluido = bloco.progresso?.concluido || false;
            const atividadesConcluidas = bloco.progresso?.atividades_concluidas || 0;
            const totalAtividades = bloco.progresso?.total_atividades || bloco.total_atividades || 0;
            
            const disponivel = index <= blocoDisponivel;
            const bloqueado = !disponivel;
            const isNovo = index === blocoDisponivel && !concluido;
            
            const cardClass = bloqueado ? 'bloco-card-bloqueado' : 
                              concluido ? 'bloco-card-concluido' : 'bloco-card-disponivel';
            
            const clickHandler = bloqueado ? '' : `onclick="abrirExercicios(${bloco.id}, '${bloco.nome_bloco.replace(/'/g, "\\'")}')"`;
            
            const badgeClass = bloqueado ? 'badge-bloqueado' : 
                              concluido ? 'badge-concluido' : 'badge-disponivel';
            
            const badgeText = bloqueado ? 'Bloqueado' : 
                             concluido ? 'Concluído' : 'Disponível';
            
            const blocoHTML = `
                <div class="bloco-card ${cardClass}" ${clickHandler}>
                    ${isNovo ? '<div class="novo-indicator" title="Novo conteúdo!"><i class="fas fa-star"></i></div>' : ''}
                    
                    <div class="bloco-header">
                        <div class="bloco-icon-container">
                            <div class="bloco-icon">
                                <i class="fas fa-${concluido ? 'check-circle' : (bloqueado ? 'lock' : 'play-circle')}"></i>
                            </div>
                            <div>
                                <h4 class="bloco-titulo">${bloco.nome_bloco}</h4>
                                <p class="bloco-subtitulo">${bloco.tipo || 'Exercícios práticos'}</p>
                            </div>
                        </div>
                        <span class="bloco-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    
                    <div class="bloco-body">
                        <p class="bloco-descricao">${bloco.descricao || 'Pratique suas habilidades com exercícios interativos.'}</p>
                    </div>
                    
                    <div class="bloco-footer">
                        <div class="bloco-meta">
                            <span class="bloco-stats">
                                <i class="fas fa-tasks"></i>
                                ${atividadesConcluidas}/${totalAtividades} atividades
                            </span>
                        </div>
                        ${!bloqueado ? `
                            <div class="bloco-progresso">
                                <div class="bloco-progresso-info">
                                    <span class="bloco-progresso-texto">Progresso</span>
                                    <span class="bloco-progresso-porcentagem">${progresso}%</span>
                                </div>
                                <div class="bloco-progresso-bar">
                                    <div class="bloco-progresso-fill" style="width: ${progresso}%"></div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', blocoHTML);
        });
        
        // Exibir blocos especiais
        if (blocosEspeciais.length > 0) {
            const especiaisHeader = document.createElement('div');
            especiaisHeader.className = 'blocos-especiais-header';
            especiaisHeader.innerHTML = `
                <h4 class="especiais-titulo">
                    <i class="fas fa-star me-2" style="color: var(--amarelo-detalhe);"></i>
                    Conteúdos Especiais
                </h4>
                <p class="especiais-descricao">Atividades extras para reforçar seu aprendizado</p>
            `;
            container.appendChild(especiaisHeader);
            
            blocosEspeciais.forEach(bloco => {
                const blocoHTML = `
                    <div class="bloco-card bloco-card-disponivel border-warning" 
                         onclick="abrirExercicioEspecial('${bloco.id}', '${bloco.nome_bloco.replace(/'/g, "\\'")}')">
                        <div class="bloco-header">
                            <div class="bloco-icon-container">
                                <div class="bloco-icon" style="background: linear-gradient(135deg, var(--amarelo-detalhe), #f59e0b);">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h4 class="bloco-titulo">${bloco.nome_bloco}</h4>
                                    <p class="bloco-subtitulo">Conteúdo especial</p>
                                </div>
                            </div>
                            <span class="bloco-badge" style="background: linear-gradient(135deg, var(--amarelo-detalhe), #f59e0b);">Especial</span>
                        </div>
                        
                        <div class="bloco-body">
                            <p class="bloco-descricao">${bloco.descricao || 'Atividade especial para praticar de forma divertida.'}</p>
                        </div>
                        
                        <div class="bloco-footer">
                            <div class="bloco-meta">
                                <span class="bloco-stats">
                                    <i class="fas fa-gamepad"></i>
                                    Exercício interativo
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', blocoHTML);
            });
        }
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

    // Função para exibir blocos no modal
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
    
    // Função para mostrar teoria do bloco
    function mostrarTeoriaDoBloco(teoria, tituloBloco) {
        document.getElementById('tituloTeoriaConteudo').innerHTML = `
            <i class="fas fa-book me-2"></i>${teoria.titulo} - ${tituloBloco}
        `;
        document.getElementById('conteudoTeoria').innerHTML = formatarConteudoTeoria(teoria.conteudo);
        document.getElementById('btnIniciarExercicios').style.display = 'block';
        modalTeoriaConteudo.show();
    }
    
    // Função para formatar conteúdo da teoria
    function formatarConteudoTeoria(conteudo) {
        if (!conteudo) return '<p class="text-muted">Nenhum conteúdo disponível.</p>';
        
        // Detectar se é formato de tópicos (numerado)
        const linhas = conteudo.split('\n');
        let temTopicos = false;
        
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
            let topicoAtual = null;
            
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
                    topicoAtual = { numero, titulo, conteudo: '' };
                } else if (topicoAtual) {
                    // Conteúdo do tópico
                    topicoAtual.conteudo += (topicoAtual.conteudo ? '\n' : '') + linha;
                }
            });
            
            // Adicionar último tópico
            if (topicoAtual) {
                topicos.push(topicoAtual);
            }
            
            // Gerar HTML em grid
            let conteudoFormatado = '<div class="topicos-container">';
            topicos.forEach((topico, index) => {
                conteudoFormatado += `
                    <div class="topico-item">
                        <div class="topico-header">
                            <div class="topico-numero">${topico.numero}</div>
                            <h4 class="topico-titulo">${topico.titulo}</h4>
                        </div>
                        <p class="topico-conteudo">${topico.conteudo.replace(/\n/g, '<br>')}</p>
                    </div>
                `;
            });
            conteudoFormatado += '</div>';
            return conteudoFormatado;
        } else {
            // Texto simples
            return `<div class="teoria-texto-simples">${conteudo.replace(/\n/g, '<br>')}</div>`;
        }
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
    
    // Função para exibir teorias
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
    </script>

    
  
  <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
      <div class="vw-plugin-top-wrapper"></div>
    </div>
  </div>
  <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>


  
 <style>
        /* Botão de Acessibilidade */
        .accessibility-widget {
            position: fixed;
            bottom: 50px;
            right: 5px;
            z-index: 10000;
            font-family: 'arial';
        }

        .accessibility-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .accessibility-toggle:hover, .accessibility-toggle:focus-visible {
            outline: none;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(45, 62, 143, 1);
        }

        .accessibility-panel {
            position: absolute;
            bottom: 60px;
            right: 0;
            width: 320px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            padding: 10px 15px 15px 15px;
            font-size: 14px;
            z-index: 10001;
            color: #222;
        }

        .accessibility-header {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            padding: 12px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        .accessibility-header h3 {
            margin: 0;
            color: white;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover, .close-btn:focus-visible {
            background: rgba(255, 255, 255, 0.25);
            outline: none;
        }

        /* GRID DOS BOTÕES - TAMANHO CONSISTENTE */
        .accessibility-options {
            padding: 10px 5px 0 5px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: 95px;
            gap: 10px;
            justify-items: stretch;
        }

        .option-btn {
            background: white;
            border: 2px solid #d5d9db;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            padding: 8px 6px;
            font-size: 13px;
            color: #2d3e8f;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            user-select: none;
            box-shadow: 0 1px 1px rgb(0 0 0 / 0.05);
            font-weight: 600;
            height: 95px;
            min-height: 95px;
            max-height: 95px;
            width: 100%;
            box-sizing: border-box;
            gap: 0;
        }

        .option-btn i {
            font-size: 28px;
            margin-bottom: 0;
            color: #2d3e8f;
            flex-shrink: 0;
            line-height: 1;
        }

        .option-btn:hover, .option-btn:focus-visible {
            background: #e1e8f8;
            border-color: #1a2980;
            box-shadow: 0 2px 6px rgb(26 41 128 / 0.25);
            outline: none;
            transform: translateY(-2px);
        }

        .option-btn[aria-pressed="true"] {
            background: #3952a3;
            color: white;
            border-color: #1a2980;
        }

        .option-btn[aria-pressed="true"] i {
            color: white;
        }

        .reset-btn {
            background: #f5f5f7;
            border-color: #c9c9d7;
            color: #71717a;
        }

        .reset-btn:hover, .reset-btn:focus-visible {
            background: #d6d6e1;
            border-color: #71717a;
            color: #1a1a28;
        }

        /* CONTAINERS E SUBMENUS */
        .option-btn-container {
            position: relative;
            height: 95px;
        }

        /* SUBMENUS ESTILIZADOS */
        .submenu {
            display: none;
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            z-index: 10002;
            width: 280px;
            top: -150px;
            left: 0;
            border: 2px solid #e1e8f8;
        }

        .submenu.active {
            display: block;
        }

        .submenu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #2d3e8f;
            margin-bottom: 12px;
            font-size: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e1e8f8;
        }

        .submenu-close {
            background: none;
            border: none;
            color: #2d3e8f;
            font-size: 14px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .submenu-close:hover {
            background: #e1e8f8;
            color: #3952a3;
        }

        /* CONTROLES DESLIZANTES NOS SUBMENUS */
        .slider-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            margin: 15px 0;
        }

        .slider-btn {
            background: #e1e8f8;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #2d3e8f;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .slider-btn:hover {
            background: #3952a3;
            color: white;
            border-color: #2d3e8f;
        }

        .slider-wrapper {
            flex: 1;
            position: relative;
        }

        .slider-track {
            position: relative;
            height: 8px;
            background: #e1e8f8;
            border-radius: 4px;
            overflow: visible;
        }

        .slider-fill {
            position: absolute;
            height: 100%;
            background: linear-gradient(90deg, #2d3e8f, #3952a3);
            border-radius: 4px;
            width: 0%;
            transition: width 0.2s ease;
        }

        /* SLIDER COM BOLINHA VISÍVEL */
        .slider {
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
            opacity: 1;
            -webkit-appearance: none;
            background: transparent;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-webkit-slider-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2d3e8f;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }

        .slider::-moz-range-thumb:hover {
            background: #3952a3;
            transform: scale(1.1);
        }

        .slider-value {
            font-size: 12px;
            font-weight: 600;
            color: #2d3e8f;
            text-align: center;
            margin-top: 8px;
        }

        /* BOTÕES DO SUBMENU DE ALINHAMENTO */
        .submenu-btn {
            width: 100%;
            padding: 10px 12px;
            margin: 6px 0;
            background: white;
            border: 1px solid #d5d9db;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2d3e8f;
        }

        .submenu-btn:hover {
            background: #e1e8f8;
            border-color: #2d3e8f;
        }

        .submenu-btn i {
            font-size: 14px;
            width: 16px;
        }

        /* CLASSES PARA FUNCIONALIDADES */
        /* MODO DE ALTO CONTRASTE APENAS COM AMARELO/PRETO */
        .contrast-mode {
            background-color: #000000 !important;
            color: #ffff00 !important;
        }

        .contrast-mode * {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border-color: #ffff00 !important;
        }

        .contrast-mode a {
            color: #ffff00 !important;
            text-decoration: underline !important;
        }

        .contrast-mode button,
        .contrast-mode input,
        .contrast-mode select,
        .contrast-mode textarea {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode img {
            filter: grayscale(100%) contrast(150%) !important;
        }

        .contrast-mode .accessibility-panel {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn {
            background-color: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }

        .contrast-mode .option-btn:hover,
        .contrast-mode .option-btn:focus-visible {
            background-color: #ffff00 !important;
            color: #000000 !important;
        }

        .highlight-links a, .highlight-links button {
            outline: 2px solid #00ffff !important;
            box-shadow: 0 0 8px #00ffff !important;
            position: relative;
        }

        .pause-animations * {
            animation-play-state: paused !important;
            transition: none !important;
        }

        @import url('https://fonts.googleapis.com/css2?family=Open+Dyslexic&display=swap');

        .dyslexia-friendly {
            font-family: 'Open Dyslexic', Arial, sans-serif !important;
            letter-spacing: 0.12em !important;
            word-spacing: 0.2em !important;
        }

        .text-spacing {
            letter-spacing: 0.12em !important;
            word-spacing: 0.3em !important;
        }

        .text-align-left * {
            text-align: left !important;
        }

        .text-align-center * {
            text-align: center !important;
        }

        .text-align-justify * {
            text-align: justify !important;
        }

        .tooltip-enabled a[title], .tooltip-enabled button[title] {
            position: relative;
            outline: none;
        }

        .tooltip-enabled a[title]:hover::after,
        .tooltip-enabled button[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3e8f;
            color: white;
            padding: 5px 8px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 11px;
            z-index: 2000;
            opacity: 0.95;
            pointer-events: none;
            font-weight: 600;
        }

        .accessibility-widget.moved {
            right: auto !important;
            left: 20px !important;
            top: 20px !important;
            bottom: auto !important;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 400px) {
            .accessibility-widget {
                right: 5px;
                width: 300px;
            }
            
            .accessibility-panel {
                width: 300px;
            }
            
            .submenu {
                width: 260px;
                left: -130px;
            }
        }

        /* Estilo para o botão de parar leitura */
        #stop-reading-btn {
            background: #dc3545 !important;
            color: white !important;
            border-color: #dc3545 !important;
        }

        #stop-reading-btn:hover {
            background: #c82333 !important;
            border-color: #bd2130 !important;
        }

        #stop-reading-btn i {
            color: white !important;
        }

        /* Feedback visual para leitura ativa */
        .reading-active {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
</head>
<body>
  

    <!-- Botão de Acessibilidade -->
    <div id="accessibility-widget" class="accessibility-widget" aria-label="Menu de acessibilidade">
        <button id="accessibility-toggle" class="accessibility-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="accessibility-panel" aria-label="Abrir menu de acessibilidade">
            <i class="fas fa-universal-access" aria-hidden="true"></i>
        </button>
        <div id="accessibility-panel" class="accessibility-panel" role="dialog" aria-modal="true" aria-labelledby="accessibility-title" tabindex="-1" hidden>
            <div class="accessibility-header">
                <h3 id="accessibility-title">Menu de Acessibilidade (CTRL+U)</h3>
                <button id="close-panel" class="close-btn" aria-label="Fechar menu de acessibilidade">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="accessibility-options grid">
                <button class="option-btn" data-action="contrast" aria-pressed="false" title="Contraste + (Alt+1)">
                    <i class="fas fa-adjust" aria-hidden="true"></i><br> Contraste +
                </button>
                <button class="option-btn" data-action="highlight-links" aria-pressed="false" title="Destacar links (Alt+2)">
                    <i class="fas fa-link" aria-hidden="true"></i><br> Destacar links
                </button>
                
                <!-- Botão de fonte com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="font-size-btn" title="Tamanho da fonte (Alt+3)">
                        <i class="fas fa-text-height" aria-hidden="true"></i><br> Tamanho da fonte
                    </button>
                    <div class="font-submenu submenu" id="font-submenu">
                        <div class="submenu-header">
                            <span>Tamanho da Fonte</span>
                            <button class="submenu-close" id="font-close" aria-label="Fechar menu de fonte">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-controls">
                            <button class="slider-btn" id="font-decrease" title="Diminuir fonte">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                            <div class="slider-wrapper">
                                <div class="slider-track">
                                    <div class="slider-fill" id="font-fill"></div>
                                    <input type="range" id="font-slider" class="slider" min="0" max="32" value="0" step="2">
                                </div>
                            </div>
                            <button class="slider-btn" id="font-increase" title="Aumentar fonte">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="slider-value" id="font-value">Original</div>
                    </div>
                </div>
                
                <button class="option-btn" data-action="text-spacing" aria-pressed="false" title="Espaçamento texto (Alt+4)">
                    <i class="fas fa-arrows-alt-h" aria-hidden="true"></i><br> Espaçamento texto
                </button>
                <button class="option-btn" data-action="pause-animations" aria-pressed="false" title="Pausar animações (Alt+5)">
                    <i class="fas fa-pause-circle" aria-hidden="true"></i><br> Pausar animações
                </button>
                <button class="option-btn" data-action="dyslexia-friendly" aria-pressed="false" title="Modo dislexia (Alt+6)">
                    <i class="fas fa-font" aria-hidden="true"></i><br> Modo dislexia
                </button>
                
                <!-- Botão de leitura de página -->
                <button class="option-btn" id="read-page-btn" title="Ler página (Alt+7)">
                    <i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página
                </button>
                
                <button class="option-btn" data-action="tooltips" aria-pressed="false" title="Tooltips (Alt+8)">
                    <i class="fas fa-info-circle" aria-hidden="true"></i><br> Tooltips
                </button>
                
                <!-- Botão de alinhamento com submenu -->
                <div class="option-btn-container">
                    <button class="option-btn" id="align-btn" title="Alinhar texto (Alt+0)">
                        <i class="fas fa-align-left" aria-hidden="true"></i><br> Alinhar texto
                    </button>
                    <div class="align-submenu submenu" id="align-submenu">
                        <div class="submenu-header">
                            <span>Alinhar Texto</span>
                            <button class="submenu-close" id="align-close" aria-label="Fechar menu de alinhamento">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <button class="submenu-btn" data-action="text-align-original">
                            <i class="fas fa-undo"></i> Original
                        </button>
                        <button class="submenu-btn" data-action="text-align-left">
                            <i class="fas fa-align-left"></i> Alinhar à esquerda
                        </button>
                        <button class="submenu-btn" data-action="text-align-center">
                            <i class="fas fa-align-center"></i> Alinhar ao centro
                        </button>
                        <button class="submenu-btn" data-action="text-align-justify">
                            <i class="fas fa-align-justify"></i> Justificar
                        </button>
                    </div>
                </div>
                
                <button class="option-btn reset-btn" data-action="reset-all" title="Redefinir tudo">
                    <i class="fas fa-undo" aria-hidden="true"></i><br> Redefinir tudo
                </button>
                <button class="option-btn" data-action="move-hide" title="Mover/Ocultar menu">
                    <i class="fas fa-arrows-alt" aria-hidden="true"></i><br> Mover/Ocultar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const widget = document.getElementById('accessibility-widget');
            const toggleBtn = document.getElementById('accessibility-toggle');
            const panel = document.getElementById('accessibility-panel');
            const closeBtn = document.getElementById('close-panel');
            const optionBtns = document.querySelectorAll('.option-btn');
            const submenuBtns = document.querySelectorAll('.submenu-btn');
            
            // Elementos dos controles deslizantes
            const fontSlider = document.getElementById('font-slider');
            const fontFill = document.getElementById('font-fill');
            const fontValue = document.getElementById('font-value');
            const fontDecrease = document.getElementById('font-decrease');
            const fontIncrease = document.getElementById('font-increase');
            const fontBtn = document.getElementById('font-size-btn');
            const fontSubmenu = document.getElementById('font-submenu');
            const fontClose = document.getElementById('font-close');
            
            const alignBtn = document.getElementById('align-btn');
            const alignSubmenu = document.getElementById('align-submenu');
            const alignClose = document.getElementById('align-close');

            // Botões de leitura
            const readPageBtn = document.getElementById('read-page-btn');
            let speechSynthesis = window.speechSynthesis;
            let isReading = false;
            let currentUtterance = null;
            let userStopped = false;

            // Estado para fonte (0 = tamanho original)
            let fontSize = parseInt(localStorage.getItem('fontSize')) || 0;

            // Estado dos botões com toggle
            let states = {
                contrast: false,
                highlightLinks: false,
                textSpacing: false,
                pauseAnimations: false,
                dyslexiaFriendly: false,
                tooltips: false,
                textAlign: 'original'
            };

            // Função para atualizar o preenchimento do slider
            function updateSliderFill(slider, fill) {
                const value = slider.value;
                const min = slider.min;
                const max = slider.max;
                const percentage = ((value - min) / (max - min)) * 100;
                fill.style.width = percentage + '%';
            }

            // Inicializar sliders
            function initializeSliders() {
                updateSliderFill(fontSlider, fontFill);
                updateFontValue();
            }

            // Atualizar valor exibido da fonte
            function updateFontValue() {
                if (fontSize === 0) {
                    fontValue.textContent = 'Original';
                } else {
                    fontValue.textContent = fontSize + 'px';
                }
            }

            // Função para garantir tamanho consistente dos botões
            function enforceConsistentButtonSizes() {
                const optionBtns = document.querySelectorAll('.option-btn');
                const containers = document.querySelectorAll('.option-btn-container');
                
                optionBtns.forEach(btn => {
                    btn.style.height = '95px';
                    btn.style.minHeight = '95px';
                    btn.style.maxHeight = '95px';
                });
                
                containers.forEach(container => {
                    container.style.height = '95px';
                    container.style.minHeight = '95px';
                });
            }

            // Mostra ou esconde painel e atualiza aria-expanded
            function togglePanel(show) {
                if (show) {
                    panel.hidden = false;
                    panel.classList.add('active');
                    toggleBtn.setAttribute('aria-expanded', 'true');
                    panel.focus();
                    setTimeout(enforceConsistentButtonSizes, 10);
                } else {
                    panel.hidden = true;
                    panel.classList.remove('active');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    closeAllSubmenus();
                }
            }

            toggleBtn.addEventListener('click', () => {
                const isActive = !panel.hidden;
                togglePanel(!isActive);
            });
            
            closeBtn.addEventListener('click', () => togglePanel(false));

            // Fecha painel clicando fora
            document.addEventListener('click', e => {
                if (!widget.contains(e.target) && !panel.hidden) {
                    togglePanel(false);
                }
            });

            // Navegação pelo teclado no painel: ESC para fechar
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !panel.hidden) {
                    togglePanel(false);
                    toggleBtn.focus();
                }
            });

            // Eventos para os botões principais
            optionBtns.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const action = this.getAttribute('data-action');
                    
                    // Verificar se é um botão com submenu
                    if (this.id === 'font-size-btn') {
                        toggleSubmenu(fontSubmenu);
                    } else if (this.id === 'align-btn') {
                        toggleSubmenu(alignSubmenu);
                    } else {
                        handleAccessibilityAction(action, this);
                    }
                });
            });

            // Evento para o botão de ler página
            readPageBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!isReading) {
                    startReading();
                } else {
                    userStopped = true;
                    stopReading();
                }
            });

            // Função para iniciar leitura da página
            function startReading() {
                if (!speechSynthesis) {
                    console.log('Seu navegador não suporta leitura de texto.');
                    return;
                }

                // Parar qualquer leitura anterior
                stopReading();

                // Obter todo o texto da página
                const pageText = getPageText();
                
                if (!pageText.trim()) {
                    console.log('Nenhum texto encontrado para ler.');
                    return;
                }

                // Criar utterance
                currentUtterance = new SpeechSynthesisUtterance(pageText);
                currentUtterance.lang = 'pt-BR';
                currentUtterance.rate = 0.8;
                currentUtterance.pitch = 1;
                currentUtterance.volume = 1;

                // Resetar flag
                userStopped = false;

                // Atualizar interface
                isReading = true;
                readPageBtn.innerHTML = '<i class="fas fa-stop" aria-hidden="true"></i><br> Parar leitura';
                readPageBtn.id = 'stop-reading-btn';
                readPageBtn.classList.add('reading-active');

                // Evento quando a leitura terminar
                currentUtterance.onend = function() {
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Evento quando ocorrer erro - apenas log, sem alert
                currentUtterance.onerror = function(event) {
                    console.log('Erro na leitura:', event.error);
                    if (!userStopped) {
                        stopReading();
                    }
                };

                // Iniciar leitura
                speechSynthesis.speak(currentUtterance);
            }

            // Função para parar leitura
            function stopReading() {
                if (speechSynthesis && isReading) {
                    speechSynthesis.cancel();
                }
                
                isReading = false;
                currentUtterance = null;
                readPageBtn.innerHTML = '<i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página';
                readPageBtn.id = 'read-page-btn';
                readPageBtn.classList.remove('reading-active');
            }

            // Função para obter texto da página (excluindo elementos irrelevantes)
            function getPageText() {
                // Clonar o body para não modificar o DOM original
                const clone = document.body.cloneNode(true);
                
                // Remover elementos que não devem ser lidos
                const elementsToRemove = clone.querySelectorAll(
                    'script, style, nav, header, footer, .accessibility-widget, [aria-hidden="true"]'
                );
                elementsToRemove.forEach(el => el.remove());
                
                // Obter texto limpo
                return clone.textContent.replace(/\s+/g, ' ').trim();
            }

            // Eventos para os botões dos submenus
            submenuBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const action = this.getAttribute('data-action');
                    handleAccessibilityAction(action, this);
                    closeAllSubmenus();
                });
            });

            // Botões de fechar nos submenus
            fontClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            alignClose.addEventListener('click', function() {
                closeAllSubmenus();
            });

            // Funções para controlar submenus
            function toggleSubmenu(submenu) {
                closeAllSubmenus();
                submenu.classList.add('active');
            }

            function closeAllSubmenus() {
                fontSubmenu.classList.remove('active');
                alignSubmenu.classList.remove('active');
            }

            // Fechar submenus ao clicar fora deles
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.option-btn-container')) {
                    closeAllSubmenus();
                }
            });

            // Controle deslizante de fonte
            fontSlider.value = fontSize;
            
            fontSlider.addEventListener('input', function() {
                fontSize = parseInt(this.value);
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontDecrease.addEventListener('click', function() {
                fontSize = Math.max(parseInt(fontSlider.min), fontSize - 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            fontIncrease.addEventListener('click', function() {
                fontSize = Math.min(parseInt(fontSlider.max), fontSize + 2);
                fontSlider.value = fontSize;
                updateFontValue();
                updateSliderFill(fontSlider, fontFill);
                applyFontSize();
            });

            function applyFontSize() {
                const elements = document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, a, span, li, label, button, div');
                
                if (fontSize === 0) {
                    // Volta ao tamanho original
                    elements.forEach(el => {
                        el.style.fontSize = '';
                    });
                } else {
                    // Aplica o tamanho personalizado
                    elements.forEach(el => {
                        el.style.fontSize = fontSize + 'px';
                    });
                }
                localStorage.setItem('fontSize', fontSize);
            }

            function applyTextAlign() {
                // Remove todas as classes de alinhamento
                document.body.classList.remove('text-align-left', 'text-align-center', 'text-align-justify');
                
                if (states.textAlign !== 'original') {
                    document.body.classList.add(states.textAlign);
                }
            }

            function handleAccessibilityAction(action, btn) {
                const body = document.body;
                switch (action) {
                    case 'contrast':
                        states.contrast = !states.contrast;
                        body.classList.toggle('contrast-mode', states.contrast);
                        btn.setAttribute('aria-pressed', states.contrast);
                        break;

                    case 'highlight-links':
                        states.highlightLinks = !states.highlightLinks;
                        body.classList.toggle('highlight-links', states.highlightLinks);
                        btn.setAttribute('aria-pressed', states.highlightLinks);
                        break;

                    case 'text-spacing':
                        states.textSpacing = !states.textSpacing;
                        body.classList.toggle('text-spacing', states.textSpacing);
                        btn.setAttribute('aria-pressed', states.textSpacing);
                        break;

                    case 'pause-animations':
                        states.pauseAnimations = !states.pauseAnimations;
                        body.classList.toggle('pause-animations', states.pauseAnimations);
                        btn.setAttribute('aria-pressed', states.pauseAnimations);
                        break;

                    case 'dyslexia-friendly':
                        states.dyslexiaFriendly = !states.dyslexiaFriendly;
                        body.classList.toggle('dyslexia-friendly', states.dyslexiaFriendly);
                        btn.setAttribute('aria-pressed', states.dyslexiaFriendly);
                        break;

                    case 'tooltips':
                        states.tooltips = !states.tooltips;
                        body.classList.toggle('tooltip-enabled', states.tooltips);
                        btn.setAttribute('aria-pressed', states.tooltips);
                        break;

                    case 'text-align-original':
                        states.textAlign = 'original';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-left':
                        states.textAlign = 'text-align-left';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-center':
                        states.textAlign = 'text-align-center';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;
                        
                    case 'text-align-justify':
                        states.textAlign = 'text-align-justify';
                        applyTextAlign();
                        closeAllSubmenus();
                        break;

                    case 'reset-all':
                        resetAll();
                        break;

                    case 'move-hide':
                        const moved = widget.classList.toggle('moved');
                        if (moved) {
                            btn.style.backgroundColor = '#fbbf24';
                        } else {
                            btn.style.backgroundColor = '';
                        }
                        break;
                }
            }

            function resetAll() {
                // Parar leitura se estiver ativa
                userStopped = true;
                stopReading();
                
                // Remove todas as classes de acessibilidade
                document.body.className = '';
                
                // Remove todos os estilos inline
                document.querySelectorAll('*').forEach(el => {
                    el.style.fontSize = '';
                    el.style.lineHeight = '';
                    el.style.letterSpacing = '';
                    el.style.wordSpacing = '';
                    el.style.textAlign = '';
                    el.style.fontFamily = '';
                });
                
                // Reseta estados
                fontSize = 0;
                fontSlider.value = fontSize;
                
                states = {
                    contrast: false,
                    highlightLinks: false,
                    textSpacing: false,
                    pauseAnimations: false,
                    dyslexiaFriendly: false,
                    tooltips: false,
                    textAlign: 'original'
                };

                initializeSliders();
                applyFontSize();

                // Reseta botões
                optionBtns.forEach(btn => {
                    btn.setAttribute('aria-pressed', false);
                    btn.style.backgroundColor = '';
                });

                // Limpa localStorage
                localStorage.removeItem('fontSize');
                closeAllSubmenus();
            }

            // Inicialização
            enforceConsistentButtonSizes();
            window.addEventListener('resize', enforceConsistentButtonSizes);
            initializeSliders();

            // Aplica configurações salvas ao carregar
            if (localStorage.getItem('fontSize')) {
                applyFontSize();
            }

            // Atalhos: Alt+1 até Alt+0 para facilitar uso rápido
            document.addEventListener('keydown', e => {
                if (e.altKey && !e.ctrlKey && !e.metaKey) {
                    switch (e.key) {
                        case '1': document.querySelector('[data-action="contrast"]').click(); break;
                        case '2': document.querySelector('[data-action="highlight-links"]').click(); break;
                        case '3': fontBtn.click(); break;
                        case '4': document.querySelector('[data-action="text-spacing"]').click(); break;
                        case '5': document.querySelector('[data-action="pause-animations"]').click(); break;
                        case '6': document.querySelector('[data-action="dyslexia-friendly"]').click(); break;
                        case '7': readPageBtn.click(); break;
                        case '8': document.querySelector('[data-action="tooltips"]').click(); break;
                        case '0': alignBtn.click(); break;
                        default: break;
                    }
                }

                // CTRL+U alterna painel
                if (e.ctrlKey && e.key.toLowerCase() === 'u') {
                    e.preventDefault();
                    togglePanel(panel.hidden);
                }

                // ESC para parar leitura
                if (e.key === 'Escape' && isReading) {
                    userStopped = true;
                    stopReading();
                }
            });

            // Parar leitura quando a página for fechada
            window.addEventListener('beforeunload', function() {
                userStopped = true;
                stopReading();
            });
        });
    </script>
</body>
</html>