<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Apenas admin logado pode acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do exercício foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

$exercicio_id = $_GET['id'];
$mensagem = '';

$database = new Database();
$conn = $database->conn;

// Buscar dados do admin para o sidebar
$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ordem = $_POST['ordem'];
    $tipo = $_POST['tipo'];
    $tipo_exercicio = $_POST['tipo_exercicio'] ?? 'multipla_escolha';
    $pergunta = $_POST['pergunta'];
    $conteudo = null;
    $categoria = 'gramatica'; // padrão

    // Definir categoria baseada no tipo_exercicio
    switch ($tipo_exercicio) {
        case 'listening':
            $categoria = 'audicao';
            break;
        case 'completar':
            $categoria = 'escrita';
            break;
        case 'multipla_escolha':
        default:
            $categoria = 'gramatica';
            break;
    }

    // Constrói o conteúdo JSON com base no tipo de exercício
    switch ($tipo) {
        case 'normal':
            if ($tipo_exercicio === 'multipla_escolha') {
                if (!empty($_POST['alt_texto'])) {
                    $alternativas = [];
                    foreach ($_POST['alt_texto'] as $index => $texto) {
                        if (!empty($texto)) {
                            $alternativas[] = [
                                'id' => chr(97 + $index),
                                'texto' => $texto,
                                'correta' => (isset($_POST['alt_correta']) && $_POST['alt_correta'] == $index)
                            ];
                        }
                    }
                    $conteudo = json_encode([
                        'alternativas' => $alternativas,
                        'explicacao' => $_POST['explicacao'] ?? ''
                    ], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($tipo_exercicio === 'completar') {
                $alternativas_aceitas = !empty($_POST['alternativas_completar']) ? 
                    array_map('trim', explode(',', $_POST['alternativas_completar'])) : 
                    [$_POST['resposta_completar']];
                $conteudo = json_encode([
                    'frase_completar' => $_POST['frase_completar'],
                    'resposta_correta' => $_POST['resposta_completar'],
                    'alternativas_aceitas' => $alternativas_aceitas,
                    'dica' => $_POST['dica_completar'] ?? '',
                    'placeholder' => $_POST['placeholder_completar'] ?? 'Digite sua resposta...'
                ], JSON_UNESCAPED_UNICODE);
            } elseif ($tipo_exercicio === 'listening') {
                $opcoes = [
                    trim($_POST['listening_opcao1']),
                    trim($_POST['listening_opcao2'])
                ];
                if (!empty($_POST['listening_opcao3'])) $opcoes[] = trim($_POST['listening_opcao3']);
                if (!empty($_POST['listening_opcao4'])) $opcoes[] = trim($_POST['listening_opcao4']);
                
                $conteudo = json_encode([
                    'frase_original' => $_POST['frase_listening'],
                    'opcoes' => $opcoes,
                    'resposta_correta' => (int)($_POST['listening_alt_correta'] ?? 0),
                    'explicacao' => $_POST['explicacao_listening'] ?? '',
                    'transcricao' => $_POST['frase_listening'],
                    'dicas_compreensao' => 'Ouça com atenção e foque nas palavras-chave.',
                    'idioma' => $_POST['idioma_audio'] ?? 'en-us',
                    'tipo_exercicio' => 'listening'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'especial':
            $conteudo = json_encode([
                'link_video' => $_POST['link_video'],
                'pergunta_extra' => $_POST['pergunta_extra']
            ], JSON_UNESCAPED_UNICODE);
            break;
        case 'quiz':
            $conteudo = json_encode([
                'quiz_id' => $_POST['quiz_id']
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

    // Atualiza o exercício na tabela, incluindo categoria
    $sql_update = "UPDATE exercicios SET ordem = ?, tipo = ?, pergunta = ?, conteudo = ?, categoria = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("issssi", $ordem, $tipo, $pergunta, $conteudo, $categoria, $exercicio_id);
    
    if ($stmt_update->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Exercício atualizado com sucesso!';
        header("Location: editar_exercicio.php?id=" . $exercicio_id);
        exit();
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar exercício: ' . $stmt_update->error . '</div>';
    }
    $stmt_update->close();
}

// Exibir mensagem de sucesso se existir
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem = '<div class="alert alert-success alert-dismissible" id="alertSucesso"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['mensagem_sucesso'] . '</div>';
    unset($_SESSION['mensagem_sucesso']);
}

// BUSCA AS INFORMAÇÕES DO EXERCÍCIO EXISTENTE PARA PREENCHER O FORMULÁRIO
$sql_exercicio = "SELECT caminho_id, ordem, tipo, pergunta, conteudo FROM exercicios WHERE id = ?";
$stmt_exercicio = $conn->prepare($sql_exercicio);
$stmt_exercicio->bind_param("i", $exercicio_id);
$stmt_exercicio->execute();
$exercicio = $stmt_exercicio->get_result()->fetch_assoc();
$stmt_exercicio->close();

if (!$exercicio) {
    header("Location: gerenciar_caminhos.php");
    exit();
}

// Decodifica o JSON para que os campos do formulário possam ser preenchidos
$conteudo_array = json_decode($exercicio['conteudo'], true);
$caminho_id = $exercicio['caminho_id'];

// DEBUG: Mostrar o conteúdo para verificar a estrutura
error_log("Conteúdo do exercício: " . print_r($conteudo_array, true));

// Determinar o tipo de exercício baseado no conteúdo
$tipo_exercicio_detectado = 'multipla_escolha'; // padrão

if ($exercicio['tipo'] === 'normal' && $conteudo_array) {
    if (isset($conteudo_array['frase_completar'])) {
        $tipo_exercicio_detectado = 'completar';
    } elseif (isset($conteudo_array['opcoes']) && isset($conteudo_array['frase_original'])) {
        $tipo_exercicio_detectado = 'listening';
    } elseif (isset($conteudo_array['tipo_exercicio']) && $conteudo_array['tipo_exercicio'] === 'listening') {
        $tipo_exercicio_detectado = 'listening';
    } elseif (isset($conteudo_array['alternativas'])) {
        $tipo_exercicio_detectado = 'multipla_escolha';
    }
}

// BUSCA AS INFORMAÇÕES DO CAMINHO PARA EXIBIÇÃO NO TÍTULO
$sql_caminho = "SELECT nome_caminho, nivel, id_unidade FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho_info = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

$database->closeConnection();

// Função auxiliar para converter arrays em string para exibição
function arrayToString($array) {
    if (is_array($array)) {
        return implode(', ', $array);
    }
    return $array;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Exercício - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
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
    --verde-sucesso: #28a745;
    --azul-info: #17a2b8;
    --cinza-borda: #e0e0e0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: var(--preto-texto);
    min-height: 100vh;
    line-height: 1.6;
}

/* Navbar */
.navbar {
    background: var(--branco) !important;
    border-bottom: 3px solid var(--amarelo-detalhe);
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    padding: 12px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.navbar-brand {
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


.navbar-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

.settings-icon, .logout-icon {
    color: var(--roxo-principal);
    font-size: 1.3rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.settings-icon:hover, .logout-icon:hover {
    color: var(--roxo-escuro);
    transform: scale(1.1);
}

 /* Menu Lateral */
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

.sidebar .list-group-item.sair {
    background-color: transparent;
    color: var(--branco);
    border: none;
    padding: 15px 25px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 40px !important;
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
}

.main-content {
    margin-left: 220px; /* ou 200px se quiser mais perto */
    padding: 20px;
    margin-right: 300px;
    color: var(--preto-texto);
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
}

.header-content {
    flex: 1;
    margin-right: 20px;
}

.header-content h2 {
    font-size: 2.0rem;
    font-family: 'Poppins', sans-serif;
    color: var(--preto-texto);
    margin-bottom: 15px;
}

.btn-back {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid var(--roxo-principal);
    color: var(--roxo-principal);
    padding: 0.6rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-back:hover {
    background-color: var(--roxo-escuro);
    border-color: var(--branco); 
    color: var(--branco);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}

/* Alertas */
.alert {
    border-radius: 12px;
    border: none;
    padding: 20px 25px;
    font-weight: 500;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    color: #155724;
    border-left: 4px solid var(--verde-sucesso);
}

.alert-danger {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(200, 35, 51, 0.1));
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-info {
    background: linear-gradient(135deg, rgba(106, 13, 173, 0.1), rgba(76, 8, 124, 0.1));
    color: var(--roxo-principal);
    border-left: 4px solid var(--roxo-principal);
}

/* Cards */
.card {
    background: var(--branco);
    border-radius: 16px;
    border: none;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: var(--branco);
    border-radius: 16px 16px 0 0 !important;
    padding: 25px 30px;
    border-bottom: 3px solid var(--amarelo-detalhe);
}

.card-header h3 {
    font-weight: 700;
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-header i {
    color: var(--amarelo-detalhe);
}

.card-body {
    padding: 30px;
}

/* Botões */
.btn {
    border-radius: 12px;
    padding: 12px 25px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: var(--branco);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(106, 13, 173, 0.3);
    color: var(--branco);
}

.btn-warning {
    background: linear-gradient(135deg, var(--amarelo-botao), #f39c12);
    color: var(--preto-texto);
    border: none;
}

.btn-warning:hover {
    background: linear-gradient(135deg, var(--amarelo-hover), var(--amarelo-botao));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
    color: var(--preto-texto);
}

.btn-success {
    background: linear-gradient(135deg, var(--verde-sucesso), #20c997);
    color: var(--branco);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, var(--verde-sucesso));
    transform: translateY(-2px);
    color: var(--branco);
}

.btn-outline-danger {
    border: 2px solid #dc3545;
    color: #dc3545;
    background: transparent;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: var(--branco);
    transform: translateY(-2px);
}

/* Formulários */
.form-label {
    font-weight: 600;
    color: var(--roxo-principal);
    margin-bottom: 8px;
}

.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid var(--cinza-borda);
    padding: 12px 18px;
    transition: all 0.3s ease;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-control:focus, .form-select:focus {
    border-color: var(--roxo-principal);
    box-shadow: 0 0 0 0.3rem rgba(106, 13, 173, 0.15);
    transform: translateY(-2px);
}

/* Campos de Subtipo */
.subtipo-campos {
    border: 2px solid var(--cinza-borda);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    position: relative;
}

.subtipo-campos::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(135deg, var(--roxo-principal), var(--amarelo-detalhe));
    border-radius: 12px 0 0 12px;
}

.subtipo-campos h5 {
    color: var(--roxo-principal);
    font-weight: 700;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--amarelo-detalhe);
    padding-bottom: 10px;
}

.subtipo-campos:hover {
    border-color: var(--roxo-principal);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(106, 13, 173, 0.1);
}

/* Input Groups */
.input-group {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.input-group-text {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: var(--branco);
    border: none;
    font-weight: 600;
    min-width: 50px;
    justify-content: center;
}

.input-group .form-control {
    border-radius: 0;
    box-shadow: none;
}

.input-group .form-control:focus {
    transform: none;
}

/* Bottom Navigation */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: var(--branco);
    box-shadow: 0 -3px 15px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 10px 0;
    border-top: 3px solid var(--amarelo-detalhe);
    display: none;
}

.bottom-nav-item {
    flex: 1;
    text-align: center;
    color: var(--roxo-principal);
    text-decoration: none;
    padding: 8px 0;
    transition: all 0.3s ease;
    border-radius: 8px;
    font-size: 0.8rem;
}

.bottom-nav-item i {
    font-size: 1.3rem;
    display: block;
    margin: 0 auto 5px;
    color: var(--roxo-principal);
    transition: all 0.3s ease;
}

.bottom-nav-item.active {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: var(--branco);
}

.bottom-nav-item.active i {
    color: var(--amarelo-detalhe);
    transform: scale(1.1);
}

.bottom-nav-item:hover {
    background: rgba(106, 13, 173, 0.1);
}

.bottom-nav-item small {
    display: block;
    font-size: 0.75rem;
    margin-top: 2px;
}

/* Responsividade */
@media (max-width: 1199.98px) {
    .sidebar {
        width: 260px;
    }
    .main-content {
        margin-left: 260px;
        padding: 25px;
    }
}

@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        padding: 20px 20px 80px 20px;
    }
    
    .bottom-nav {
        display: flex;
    }
    
    .menu-toggle {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: var(--roxo-principal);
        color: var(--branco);
        border: none;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 1.3rem;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
    }
}

@media (max-width: 768px) {
    .page-header .d-flex {
        flex-direction: column;
        gap: 15px;
    }
    
    .header-content {
        margin-right: 0;
        order: 2;
    }
    
    .btn-back {
        align-self: flex-end;
        order: 1;
    }
    
    .main-content {
        padding: 15px 15px 80px 15px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .subtipo-campos {
        padding: 20px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .navbar-brand .logo-header {
        height: 35px;
    }
    
    .input-group {
        flex-direction: column;
    }
    
    .input-group-text {
        border-radius: 12px 12px 0 0 !important;
    }
    
    .input-group .form-control {
        border-radius: 0 0 12px 12px !important;
    }
}

/* Animações */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card, .alert {
    animation: fadeInUp 0.6s ease-out;
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: var(--roxo-principal);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--roxo-escuro);
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="navbar-container">
                <button class="menu-toggle d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand" href="#">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <div class="navbar-actions">
                    <a href="editar_perfil.php" class="settings-icon">
                        <i class="fas fa-cog"></i>
                    </a>
                    <a href="logout.php" class="logout-icon" title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

  <div class="sidebar" id="sidebar">
    <div class="profile">
        <?php if ($foto_admin): ?>
            <div class="profile-avatar-sidebar">
                <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
            </div>
        <?php else: ?>
            <div class="profile-avatar-sidebar">
                <i class="fa-solid fa-user" style="color: var(--amarelo-detalhe); font-size: 3.5rem;"></i>
            </div>
        <?php endif; ?>
        <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
        <small>Administrador(a)</small>
    </div>

    <div class="list-group">
        <a href="gerenciar_caminho.php" class="list-group-item active">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item">
            <i class="fas fa-language"></i> Gerenciar Idiomas
        </a>
        <a href="gerenciar_teorias.php" class="list-group-item">
            <i class="fas fa-book-open"></i> Gerenciar Teorias
        </a>
        <a href="gerenciar_unidades.php" class="list-group-item">
            <i class="fas fa-cubes"></i> Gerenciar Unidades
        </a>
        <a href="gerenciar_usuarios.php" class="list-group-item">
            <i class="fas fa-users"></i> Gerenciar Usuários
        </a>
        <a href="estatisticas_usuarios.php" class="list-group-item">
            <i class="fas fa-chart-bar"></i> Estatísticas
        </a>
    </div>
</div>
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5">
            <div class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="header-content">
                        <h2 class="mb-2">Editar Exercício - Vinculado ao Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?></h2>
                        <div class="alert alert-info mb-0">
                            <strong>📌 Localização do Exercício:</strong><br>
                            • <strong>Unidade:</strong> <?php echo htmlspecialchars($caminho_info['id_unidade'] ?? 'Não especificada'); ?><br>
                            • <strong>Caminho:</strong> <?php echo htmlspecialchars($caminho_info['nome_caminho']); ?> (<?php echo htmlspecialchars($caminho_info['nivel']); ?>)<br>
                            • <strong>ID do Caminho:</strong> <?php echo htmlspecialchars($caminho_id); ?><br>
                            <small class="text-muted">Este exercício está vinculado exclusivamente a este caminho e não aparecerá em outras unidades.</small>
                        </div>
                    </div>
                    <a href="gerenciar_exercicios.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i>Voltar para Caminhos
                    </a>
                </div>
            </div>
            
            <?php echo $mensagem; ?>

            <div class="card">
                <div class="card-body">
                    <form action="editar_exercicio.php?id=<?php echo htmlspecialchars($exercicio_id); ?>" method="POST">
                        <div class="mb-3">
                            <label for="ordem" class="form-label">Ordem do Exercício</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($exercicio['ordem']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Exercício</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="normal" <?php if ($exercicio['tipo'] == 'normal') echo 'selected'; ?>>Normal</option>
                                <option value="especial" <?php if ($exercicio['tipo'] == 'especial') echo 'selected'; ?>>Especial (Vídeo/Áudio)</option>
                                <option value="quiz" <?php if ($exercicio['tipo'] == 'quiz') echo 'selected'; ?>>Quiz</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                            <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                                <option value="multipla_escolha" <?php echo ($tipo_exercicio_detectado == 'multipla_escolha') ? 'selected' : ''; ?>>Múltipla Escolha</option>
                                <option value="completar" <?php echo ($tipo_exercicio_detectado == 'completar') ? 'selected' : ''; ?>>Completar Frase</option>
                                <option value="listening" <?php echo ($tipo_exercicio_detectado == 'listening') ? 'selected' : ''; ?>>Exercício de Listening</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($exercicio['pergunta']); ?></textarea>
                        </div>
                        
                        <div id="conteudo-campos">
                            <div id="campos-normal">
                                <!-- Campos para Múltipla Escolha -->
                                <div id="campos-multipla" class="subtipo-campos">
                                    <h5>Configuração - Múltipla Escolha</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Alternativas</label>
                                        <div id="alternativas-container">
                                            <?php
                                            if (isset($conteudo_array['alternativas']) && is_array($conteudo_array['alternativas'])) {
                                                foreach ($conteudo_array['alternativas'] as $index => $alt) {
                                                    $letra = chr(65 + $index);
                                                    $texto = is_array($alt) ? ($alt['texto'] ?? '') : $alt;
                                                    $checked = (is_array($alt) && isset($alt['correta']) && $alt['correta']) ? 'checked' : '';
                                                    echo '<div class="input-group mb-2">';
                                                    echo '<span class="input-group-text">' . $letra . '</span>';
                                                    echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa" value="' . htmlspecialchars($texto) . '">';
                                                    echo '<div class="input-group-text">';
                                                    echo '<input type="radio" name="alt_correta" value="' . $index . '" ' . $checked . ' title="Marcar como correta">';
                                                    echo '</div>';
                                                    echo '<button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">×</button>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                // Alternativas padrão
                                                echo '<div class="input-group mb-2">';
                                                echo '<span class="input-group-text">A</span>';
                                                echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">';
                                                echo '<div class="input-group-text">';
                                                echo '<input type="radio" name="alt_correta" value="0" title="Marcar como correta">';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '<div class="input-group mb-2">';
                                                echo '<span class="input-group-text">B</span>';
                                                echo '<input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">';
                                                echo '<div class="input-group-text">';
                                                echo '<input type="radio" name="alt_correta" value="1" title="Marcar como correta">';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                            ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="adicionarAlternativa()">+ Adicionar Alternativa</button>
                                    </div>
                                    <div class="mb-3">
                                        <label for="explicacao" class="form-label">Explicação</label>
                                        <textarea class="form-control" id="explicacao" name="explicacao" placeholder="Explicação da resposta correta"><?php 
                                        if (isset($conteudo_array['explicacao'])) {
                                            echo htmlspecialchars($conteudo_array['explicacao']);
                                        } elseif (isset($conteudo_array['dica'])) {
                                            echo htmlspecialchars($conteudo_array['dica']);
                                        }
                                        ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Campos para Texto Livre -->
                                <div id="campos-texto" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Texto Livre</h5>
                                    <div class="mb-3">
                                        <label for="resposta_esperada" class="form-label">Resposta Esperada</label>
                                        <input type="text" class="form-control" id="resposta_esperada" name="resposta_esperada" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="alternativas_aceitas" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                        <input type="text" class="form-control" id="alternativas_aceitas" name="alternativas_aceitas" value="<?php 
                                        if (isset($conteudo_array['alternativas_aceitas']) && is_array($conteudo_array['alternativas_aceitas'])) {
                                            echo htmlspecialchars(implode(', ', $conteudo_array['alternativas_aceitas']));
                                        } elseif (isset($conteudo_array['alternativas_aceitas'])) {
                                            echo htmlspecialchars($conteudo_array['alternativas_aceitas']);
                                        }
                                        ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="dica_texto" class="form-label">Dica</label>
                                        <textarea class="form-control" id="dica_texto" name="dica_texto"><?php echo isset($conteudo_array['dica']) ? htmlspecialchars($conteudo_array['dica']) : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Campos para Completar -->
                                <div id="campos-completar" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Completar Frase</h5>
                                    <div class="mb-3">
                                        <label for="frase_completar" class="form-label">Frase para Completar</label>
                                        <input type="text" class="form-control" id="frase_completar" name="frase_completar" value="<?php echo isset($conteudo_array['frase_completar']) ? htmlspecialchars($conteudo_array['frase_completar']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="resposta_completar" class="form-label">Resposta Correta</label>
                                        <input type="text" class="form-control" id="resposta_completar" name="resposta_completar" value="<?php echo isset($conteudo_array['resposta_correta']) ? htmlspecialchars($conteudo_array['resposta_correta']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="alternativas_completar" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                        <input type="text" class="form-control" id="alternativas_completar" name="alternativas_completar" value="<?php 
                                        if (isset($conteudo_array['alternativas_aceitas']) && is_array($conteudo_array['alternativas_aceitas'])) {
                                            echo htmlspecialchars(implode(', ', $conteudo_array['alternativas_aceitas']));
                                        } elseif (isset($conteudo_array['alternativas_aceitas'])) {
                                            echo htmlspecialchars($conteudo_array['alternativas_aceitas']);
                                        }
                                        ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="placeholder_completar" class="form-label">Placeholder</label>
                                        <input type="text" class="form-control" id="placeholder_completar" name="placeholder_completar" value="<?php echo isset($conteudo_array['placeholder']) ? htmlspecialchars($conteudo_array['placeholder']) : 'Digite sua resposta...'; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="dica_completar" class="form-label">Dica</label>
                                        <textarea class="form-control" id="dica_completar" name="dica_completar"><?php echo isset($conteudo_array['dica']) ? htmlspecialchars($conteudo_array['dica']) : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Campos para Fala -->
                                <div id="campos-fala" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Exercício de Fala</h5>
                                    <div class="mb-3">
                                        <label for="frase_esperada" class="form-label">Frase para Pronunciar</label>
                                        <input type="text" class="form-control" id="frase_esperada" name="frase_esperada" value="<?php echo isset($conteudo_array['frase_esperada']) ? htmlspecialchars($conteudo_array['frase_esperada']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética</label>
                                        <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" value="<?php echo isset($conteudo_array['pronuncia_fonetica']) ? htmlspecialchars($conteudo_array['pronuncia_fonetica']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="palavras_chave" class="form-label">Palavras-chave (separadas por vírgula)</label>
                                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php 
                                        if (isset($conteudo_array['palavras_chave']) && is_array($conteudo_array['palavras_chave'])) {
                                            echo htmlspecialchars(implode(', ', $conteudo_array['palavras_chave']));
                                        } elseif (isset($conteudo_array['palavras_chave'])) {
                                            echo htmlspecialchars($conteudo_array['palavras_chave']);
                                        }
                                        ?>">
                                    </div>
                                </div>
                                
                                <!-- Campos para Listening -->
                                <div id="campos-listening" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Exercício de Listening</h5>
                                    <div class="mb-3">
                                        <label for="frase_listening" class="form-label">Frase para Gerar Áudio *</label>
                                        <textarea class="form-control" id="frase_listening" name="frase_listening" rows="3"><?php echo isset($conteudo_array['frase_original']) ? htmlspecialchars($conteudo_array['frase_original']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="idioma_audio" class="form-label">Idioma do Áudio *</label>
                                        <select class="form-select" id="idioma_audio" name="idioma_audio">
                                            <option value="en-us" <?php echo (isset($conteudo_array['idioma']) && $conteudo_array['idioma'] == 'en-us') ? 'selected' : ''; ?>>Inglês (EUA)</option>
                                            <option value="en-gb" <?php echo (isset($conteudo_array['idioma']) && $conteudo_array['idioma'] == 'en-gb') ? 'selected' : ''; ?>>Inglês (UK)</option>
                                            <option value="es-es" <?php echo (isset($conteudo_array['idioma']) && $conteudo_array['idioma'] == 'es-es') ? 'selected' : ''; ?>>Espanhol</option>
                                            <option value="fr-fr" <?php echo (isset($conteudo_array['idioma']) && $conteudo_array['idioma'] == 'fr-fr') ? 'selected' : ''; ?>>Francês</option>
                                            <option value="de-de" <?php echo (isset($conteudo_array['idioma']) && $conteudo_array['idioma'] == 'de-de') ? 'selected' : ''; ?>>Alemão</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Opções de Resposta *</label>
                                        <?php
                                        $opcoes_listening = isset($conteudo_array['opcoes']) ? $conteudo_array['opcoes'] : ['', '', '', ''];
                                        $resposta_correta = isset($conteudo_array['resposta_correta']) ? (int)$conteudo_array['resposta_correta'] : 0;
                                        for ($i = 0; $i < 4; $i++):
                                        ?>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="listening_alt_correta" value="<?php echo $i; ?>" <?php echo ($resposta_correta == $i) ? 'checked' : ''; ?> title="Marcar como correta">
                                            </div>
                                            <input type="text" class="form-control" name="listening_opcao<?php echo $i+1; ?>" placeholder="Opção <?php echo $i+1; ?><?php echo $i > 1 ? ' (Opcional)' : ''; ?>" value="<?php echo isset($opcoes_listening[$i]) ? htmlspecialchars($opcoes_listening[$i]) : ''; ?>" <?php echo $i < 2 ? 'required' : ''; ?>>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="explicacao_listening" class="form-label">Explicação (Opcional)</label>
                                        <textarea class="form-control" id="explicacao_listening" name="explicacao_listening" rows="2"><?php echo isset($conteudo_array['explicacao']) ? htmlspecialchars($conteudo_array['explicacao']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div id="campos-especial" style="display: none;">
                                <div class="mb-3">
                                    <label for="link_video" class="form-label">Link do Vídeo/Áudio</label>
                                    <input type="text" class="form-control" id="link_video" name="link_video" value="<?php echo isset($conteudo_array['link_video']) ? htmlspecialchars($conteudo_array['link_video']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="pergunta_extra" class="form-label">Pergunta Extra</label>
                                    <textarea class="form-control" id="pergunta_extra" name="pergunta_extra"><?php echo isset($conteudo_array['pergunta_extra']) ? htmlspecialchars($conteudo_array['pergunta_extra']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div id="campos-quiz" style="display: none;">
                                <div class="mb-3">
                                    <label for="quiz_id" class="form-label">ID do Quiz</label>
                                    <input type="number" class="form-control" id="quiz_id" name="quiz_id" value="<?php echo isset($conteudo_array['quiz_id']) ? htmlspecialchars($conteudo_array['quiz_id']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation para Mobile -->
    <nav class="bottom-nav">
        <a href="gerenciar_caminho.php" class="bottom-nav-item">
            <i class="fas fa-plus-circle"></i>
            <small>Caminhos</small>
        </a>
        <a href="pagina_adicionar_idiomas.php" class="bottom-nav-item">
            <i class="fas fa-language"></i>
            <small>Idiomas</small>
        </a>
        <a href="gerenciar_teorias.php" class="bottom-nav-item">
            <i class="fas fa-book-open"></i>
            <small>Teorias</small>
        </a>
        <a href="gerenciar_unidades.php" class="bottom-nav-item">
            <i class="fas fa-cubes"></i>
            <small>Unidades</small>
        </a>
        <a href="gerenciar_usuarios.php" class="bottom-nav-item">
            <i class="fas fa-users"></i>
            <small>Usuários</small>
        </a>
        <a href="estatisticas_usuarios.php" class="bottom-nav-item">
            <i class="fas fa-chart-bar"></i>
            <small>Estatísticas</small>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        // Menu toggle para mobile
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('mobile-open');
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('mobile-open');
            });
        }
        
        // Fechar sidebar ao clicar em um link (mobile)
        const sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('mobile-open');
                }
            });
        });

        // Lógica original dos campos de exercício
        const tipoSelect = document.getElementById('tipo');
        const tipoExercicioSelect = document.getElementById('tipo_exercicio');
        const camposNormal = document.getElementById('campos-normal');
        const camposEspecial = document.getElementById('campos-especial');
        const camposQuiz = document.getElementById('campos-quiz');
        
        const camposMultipla = document.getElementById('campos-multipla');
        const camposTexto = document.getElementById('campos-texto');
        const camposCompletar = document.getElementById('campos-completar');
        const camposListening = document.getElementById('campos-listening');

        function mostrarCampos() {
            // Esconder todos os campos principais
            camposNormal.style.display = 'none';
            camposEspecial.style.display = 'none';
            camposQuiz.style.display = 'none';
            
            // Esconder todos os subcampos
            camposMultipla.style.display = 'none';
            camposTexto.style.display = 'none';
            camposCompletar.style.display = 'none';
            camposListening.style.display = 'none';

            if (tipoSelect.value === 'normal') {
                camposNormal.style.display = 'block';
                
                // Mostrar subcampo baseado no tipo de exercício
                switch (tipoExercicioSelect.value) {
                    case 'multipla_escolha':
                        camposMultipla.style.display = 'block';
                        break;
                    case 'completar':
                        camposCompletar.style.display = 'block';
                        break;
                    case 'listening':
                        camposListening.style.display = 'block';
                        break;
                }
            } else if (tipoSelect.value === 'especial') {
                camposEspecial.style.display = 'block';
            } else if (tipoSelect.value === 'quiz') {
                camposQuiz.style.display = 'block';
            }
        }
        
        // Inicializar
        mostrarCampos();

        // Listeners
        tipoSelect.addEventListener('change', mostrarCampos);
        tipoExercicioSelect.addEventListener('change', mostrarCampos);
        
        // Auto-hide success message
        const alertSucesso = document.getElementById('alertSucesso');
        if (alertSucesso) {
            setTimeout(() => {
                alertSucesso.style.transition = 'opacity 0.5s';
                alertSucesso.style.opacity = '0';
                setTimeout(() => alertSucesso.remove(), 500);
            }, 5000);
        }
    });

    function adicionarAlternativa() {
        const container = document.getElementById("alternativas-container");
        const index = container.children.length;
        const novaAlternativa = document.createElement("div");
        novaAlternativa.className = "input-group mb-2";
        const letra = String.fromCharCode(65 + index);
        novaAlternativa.innerHTML = `
            <span class="input-group-text">${letra}</span>
            <input type="text" class="form-control" name="alt_texto[]" placeholder="Texto da alternativa">
            <div class="input-group-text">
                <input type="radio" name="alt_correta" value="${index}" title="Marcar como correta">
            </div>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">×</button>
        `;
        container.appendChild(novaAlternativa);
    }
    </script>
</body>
</html>