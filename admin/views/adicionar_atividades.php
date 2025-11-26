<?php
session_start();
include_once __DIR__ . '/../../conexao.php';
include_once __DIR__ . '/../models/listening_model.php';
include_once __DIR__ . '/../controller/listening_controller.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['unidade_id']) || !is_numeric($_GET['unidade_id'])) {
    header("Location: gerenciar_unidades.php");
    exit();
}

$unidade_id = $_GET['unidade_id'];
$mensagem = '';

// Exibir mensagem de sucesso se existir
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['mensagem_sucesso'] . '</div>';
    unset($_SESSION['mensagem_sucesso']);
}

$database = new Database();
$conn = $database->conn;
$listeningModel = new ListeningModel($database);

// BUSCAR DADOS DO ADMINISTRADOR PARA O SIDEBAR
$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// --- Funções de Acesso a Dados ---
function getUnidadeInfo($conn, $unidadeId) {
    $sql = "SELECT u.*, c.nome_caminho, c.nivel 
            FROM unidades u 
            LEFT JOIN caminhos_aprendizagem c ON u.id = c.id_unidade 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidadeId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

function getCaminhosByUnidade($conn, $unidadeId) {
    $sql = "SELECT id, nome_caminho FROM caminhos_aprendizagem WHERE id_unidade = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidadeId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

function getBlocosByCaminhos($conn, array $caminhoIds) {
    if (empty($caminhoIds)) {
        return [];
    }
    $placeholders = str_repeat('?,' , count($caminhoIds) - 1) . '?';
    $sql = "SELECT id, caminho_id, nome_bloco, ordem 
            FROM blocos 
            WHERE caminho_id IN ($placeholders) 
            ORDER BY caminho_id, ordem ASC";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($caminhoIds));
    $stmt->bind_param($types, ...$caminhoIds);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $blocosPorCaminho = [];
    foreach ($result as $bloco) {
        $blocosPorCaminho[$bloco["caminho_id"]][] = $bloco;
    }
    return $blocosPorCaminho;
}

function adicionarExercicio($conn, $caminhoId, $blocoId, $ordem, $tipo_exercicio, $pergunta, $conteudo) {
    // Verificar quantas atividades já existem no bloco (apenas normais)
    $sql_count = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ? AND tipo = 'normal'";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $blocoId);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result()->fetch_assoc();
    $total_atividades_normais = $result_count['total'];
    $stmt_count->close();
    
    // Mapear tipo_exercicio para o ENUM da coluna 'tipo'
    $tipoEnum = 'normal'; // padrão
    if ($tipo_exercicio === 'quiz') {
        $tipoEnum = 'quiz';
    } else {
        // Para atividades normais, verificar se já tem 12
        if ($total_atividades_normais >= 12) {
            error_log("Bloco já possui o máximo de 12 atividades normais");
            return false;
        }
    }
    
    // Definir categoria baseada no tipo_exercicio
    $categoria = 'gramatica'; // padrão
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
    
    $sql = "INSERT INTO exercicios (caminho_id, bloco_id, ordem, tipo, pergunta, conteudo, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiissss", $caminhoId, $blocoId, $ordem, $tipoEnum, $pergunta, $conteudo, $categoria);
        if ($stmt->execute()) {
            $exercicio_id = $conn->insert_id;
            $stmt->close();
            return $exercicio_id;
        } else {
            error_log("Erro ao adicionar exercício: " . $stmt->error);
            $stmt->close();
            return false;
        }
    } else {
        error_log("Erro na preparação da consulta: " . $conn->error);
        return false;
    }
}

// --- Lógica de Processamento CORRIGIDA ---
$unidade_info = getUnidadeInfo($conn, $unidade_id);
$caminhos = getCaminhosByUnidade($conn, $unidade_id);

$blocos_por_caminho = [];
if (!empty($caminhos)) {
    $caminho_ids = array_column($caminhos, 'id');
    $blocos_por_caminho = getBlocosByCaminhos($conn, $caminho_ids);
}

// Variáveis para pré-preencher o formulário
$post_caminho_id = $_POST["caminho_id"] ?? '';
$post_bloco_id = $_POST["bloco_id"] ?? '';
$post_ordem = $_POST["ordem"] ?? '';
$post_tipo = $_POST["tipo"] ?? "normal";
$post_tipo_exercicio = $_POST["tipo_exercicio"] ?? "multipla_escolha";
$post_pergunta = $_POST["pergunta"] ?? '';



// CAMPOS ESPECÍFICOS PARA LISTENING
$post_frase_listening = $_POST["frase_listening"] ?? '';
$post_idioma_audio = $_POST["idioma_audio"] ?? 'en-us';
$post_listening_opcao1 = $_POST["listening_opcao1"] ?? '';
$post_listening_opcao2 = $_POST["listening_opcao2"] ?? '';
$post_listening_opcao3 = $_POST["listening_opcao3"] ?? '';
$post_listening_opcao4 = $_POST["listening_opcao4"] ?? '';
$post_listening_alt_correta = $_POST['listening_alt_correta'] ?? '0';
$post_explicacao_listening = $_POST["explicacao_listening"] ?? '';

// Outros campos
$post_alt_texto = $_POST["alt_texto"] ?? [];
$post_alt_correta = $_POST["alt_correta"] ?? null;
$post_explicacao = $_POST["explicacao"] ?? '';
$post_resposta_esperada = $_POST["resposta_esperada"] ?? '';
$post_alternativas_aceitas = $_POST["alternativas_aceitas"] ?? '';
$post_dica_texto = $_POST["dica_texto"] ?? '';
$post_frase_completar = $_POST["frase_completar"] ?? '';
$post_resposta_completar = $_POST["resposta_completar"] ?? '';
$post_alternativas_completar = $_POST["alternativas_completar"] ?? '';
$post_dica_completar = $_POST["dica_completar"] ?? '';
$post_placeholder_completar = $_POST["placeholder_completar"] ?? 'Digite sua resposta...';
$post_audio_url = $_POST["audio_url"] ?? '';
$post_transcricao = $_POST["transcricao"] ?? '';
$post_resposta_audio_correta = $_POST["resposta_audio_correta"] ?? '';

$post_quiz_id = $_POST["quiz_id"] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caminho_id = $_POST["caminho_id"] ?? null;
    $bloco_id = $_POST["bloco_id"] ?? null;
    $ordem = $_POST["ordem"] ?? null;
    $tipo = $_POST["tipo"] ?? null;
    $pergunta = $_POST["pergunta"] ?? null;
    $tipo_exercicio = $_POST["tipo_exercicio"] ?? null;
    $conteudo = null;
    $sucesso_insercao = false;

    if (empty($caminho_id) || empty($bloco_id) || empty($ordem) || empty($pergunta) || empty($tipo_exercicio)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        switch ($tipo_exercicio) {
            case 'multipla_escolha':
                if (empty($_POST['alt_texto']) || !isset($_POST['alt_correta'])) {
                    $mensagem = '<div class="alert alert-danger">Alternativas e resposta correta são obrigatórias.</div>';
                } else {
                    $alternativas = [];
                    foreach ($_POST['alt_texto'] as $index => $texto) {
                        if (!empty($texto)) {
                            $alternativas[] = [
                                'id' => chr(97 + $index),
                                'texto' => $texto,
                                'correta' => ($index == $_POST['alt_correta']),
                            ];
                        }
                    }
                    $conteudo = json_encode([
                        'alternativas' => $alternativas,
                        'explicacao' => $_POST['explicacao'] ?? ''
                    ], JSON_UNESCAPED_UNICODE);
                    $sucesso_insercao = true;
                }
                break;



            case 'completar':
                if (empty($_POST['frase_completar']) || empty($_POST['resposta_completar'])) {
                    $mensagem = '<div class="alert alert-danger">Frase para completar e resposta são obrigatórias.</div>';
                } else {
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
                    $sucesso_insercao = true;
                }
                break;



            case 'listening':
                if (empty($_POST['frase_listening']) || empty($_POST['listening_opcao1']) || empty($_POST['listening_opcao2']) || !isset($_POST['listening_alt_correta'])) {
                    $mensagem = '<div class="alert alert-danger">Frase, pelo menos 2 opções e a indicação da resposta correta são obrigatórios para listening.</div>';
                } else {
                    $frase = trim($_POST['frase_listening']);
                    $idioma = $_POST['idioma_audio'] ?? 'en-us';
                    
                    // Coletar opções (incluindo vazias para manter índices corretos)
                    $opcoes = [
                        trim($_POST['listening_opcao1']),
                        trim($_POST['listening_opcao2']),
                        !empty($_POST['listening_opcao3']) ? trim($_POST['listening_opcao3']) : '',
                        !empty($_POST['listening_opcao4']) ? trim($_POST['listening_opcao4']) : ''
                    ];
                    
                    // Remover opções vazias do final
                    while (count($opcoes) > 2 && end($opcoes) === '') {
                        array_pop($opcoes);
                    }
                    
                    $resposta_correta_index = (int)($_POST['listening_alt_correta'] ?? 0);
                    
                    // Validar se o índice da resposta correta é válido
                    if ($resposta_correta_index >= count($opcoes)) {
                        $resposta_correta_index = 0; // Fallback para primeira opção
                    }
                    
                    $conteudo = json_encode([
                        'frase_original' => $frase,
                        'opcoes' => $opcoes,
                        'resposta_correta' => $resposta_correta_index,
                        'explicacao' => $_POST['explicacao_listening'] ?? '',
                        'transcricao' => $frase,
                        'dicas_compreensao' => 'Ouça com atenção e foque nas palavras-chave.',
                        'idioma' => $idioma,
                        'tipo_exercicio' => 'listening'
                    ], JSON_UNESCAPED_UNICODE);
                    
                    $sucesso_insercao = true;
                }
                break;




                
            case 'quiz':
                if (empty($_POST['quiz_id'])) {
                    $mensagem = '<div class="alert alert-danger">O ID do Quiz é obrigatório para este tipo de exercício.</div>';
                } else {
                    $conteudo = json_encode([
                        'quiz_id' => $_POST['quiz_id']
                    ], JSON_UNESCAPED_UNICODE);
                    $sucesso_insercao = true;
                }
                break;
        }

        // INSERIR NO BANCO DE DADOS - LÓGICA CORRIGIDA
        if ($sucesso_insercao && $conteudo) {
            $exercicio_id = adicionarExercicio($conn, $caminho_id, $bloco_id, $ordem, $tipo_exercicio, $pergunta, $conteudo);
            if ($exercicio_id) {
                $tipo_exercicio_display = str_replace('_', ' ', ucfirst($tipo_exercicio));
                $_SESSION['mensagem_sucesso'] = 'Exercício de ' . $tipo_exercicio_display . ' adicionado com sucesso!';
                header("Location: adicionar_atividades.php?unidade_id=" . $unidade_id);
                exit();
            } else {
                // Verificar se o erro foi por limite de atividades normais
                $sql_check = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ? AND tipo = 'normal'";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $bloco_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();
                
                if ($result_check['total'] >= 12) {
                    $mensagem = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Este bloco já possui o máximo de 12 atividades normais. Escolha outro bloco.</div>';
                } else {
                    $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao adicionar exercício no banco de dados. Verifique os logs para mais detalhes.</div>';
                }
            }
        }
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Exercício - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
    /* Reset e configurações básicas */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        user-select: none;
    }

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
        --cinza-escuro: #6c757d;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--cinza-claro);
        color: var(--preto-texto);
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* Animações */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

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

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    /* Layout Principal */
    .main-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        transition: margin-left 0.3s ease-in-out;
        animation: fadeIn 0.5s ease-in-out;
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

/* CORREÇÃO ESPECÍFICA PARA OS ÍCONES DO SIDEBAR */
.sidebar .fas,
.sidebar .fa-solid,
.sidebar .fa-regular,
.sidebar .fa-brands,
.sidebar [class*="fa-"] {
    color: var(--amarelo-detalhe) !important;
}

.sidebar .list-group-item .fas,
.sidebar .list-group-item .fa-solid,
.sidebar .list-group-item .fa-regular,
.sidebar .list-group-item .fa-brands,
.sidebar .list-group-item [class*="fa-"] {
    color: var(--amarelo-detalhe) !important;
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
        padding: 10px;
        border-radius: 5px;
        background: rgba(106, 13, 173, 0.1);
    }

    .menu-toggle:hover {
        color: var(--roxo-escuro) !important;
        transform: scale(1.1);
        background: rgba(106, 13, 173, 0.2);
    }

    /* CORREÇÃO: Quando a sidebar está ativa */
    body:has(.sidebar.active) .menu-toggle,
    .sidebar.active ~ .menu-toggle {
        color: var(--amarelo-detalhe) !important;
        background: rgba(255, 215, 0, 0.1);
    }

    body:has(.sidebar.active) .menu-toggle:hover,
    .sidebar.active ~ .menu-toggle:hover {
        color: var(--amarelo-hover) !important;
        background: rgba(255, 215, 0, 0.2);
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
        backdrop-filter: blur(3px);
    }

    /* Navbar */
    .navbar {
        background-color: transparent !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
        box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
        animation: slideInDown 0.5s ease-out;
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
        transition: transform 0.3s ease;
    }

    .navbar-brand .logo-header:hover {
        transform: scale(1.05);
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

    /* Botões */
    .btn-warning {
        background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
        color: var(--preto-texto);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        min-width: 180px;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 217, 0, 0.66);
        color: var(--preto-texto);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        border: none;
        color: white;
        box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(106, 13, 173, 0.4);
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

    /* Cards e Containers */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        animation: slideInUp 0.5s ease-out;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
        color: var(--branco);
        border-radius: 15px 15px 0 0 !important;
        border: none;
        padding: 15px 20px;
        position: relative;
        overflow: hidden;
    }

    .card-header h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: white;
        margin: 0;
    }

    .card-header h5 i {
        color: var(--amarelo-detalhe);
    }

    /* Formulários */
    .form-control, .form-select {
        border: 2px solid var(--cinza-medio);
        border-radius: 10px;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
        transform: translateY(-2px);
    }

    .form-label {
        font-weight: 500;
        color: var(--roxo-principal);
        margin-bottom: 8px;
    }

    .subtipo-campos {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f8f9fa;
    }

    .input-group-text {
        min-width: 40px;
        justify-content: center;
    }

    .audio-preview {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        border: 1px dashed #dee2e6;
    }

    /* Alertas */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 20px;
        animation: slideInDown 0.3s ease-out;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

.alert-info {
    background: linear-gradient(135deg, rgba(106, 13, 173, 0.1), rgba(76, 8, 124, 0.1));
    color: var(--roxo-principal);
    border-left: 4px solid var(--roxo-principal);
}

    /* Responsividade */
    @media (max-width: 1200px) {
        .sidebar {
            width: 220px;
        }
        
        .main-content {
            margin-left: 220px;
        }
    }

    @media (max-width: 992px) {
        .menu-toggle {
            display: block;
        }
        
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
            padding: 80px 15px 20px;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .navbar-brand .logo-header {
            height: 60px;
        }
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 70px 10px 15px;
        }
        
        .card-body {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .menu-toggle {
            top: 10px;
            left: 10px;
            font-size: 1.3rem;
            padding: 8px;
        }
        
        .sidebar {
            width: 100%;
        }
        
        .main-content {
            padding: 60px 5px 10px;
        }
        
        .navbar-brand .logo-header {
            height: 50px;
        }
        
        .settings-icon {
            font-size: 1rem;
            padding: 6px;
        }
        
        .card-header h5 {
            font-size: 1.1rem;
        }
        
        .form-control, .form-select {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 380px) {
        .sidebar .profile h5 {
            font-size: 1rem;
            max-width: 150px;
        }
        
        .sidebar .profile small {
            font-size: 0.8rem;
            max-width: 150px;
        }
        
        .profile-avatar-sidebar {
            width: 80px;
            height: 80px;
        }
        
        .sidebar .list-group-item {
            padding: 12px 20px;
            font-size: 0.9rem;
        }
    }

    /* Scroll personalizado */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--cinza-claro);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--roxo-principal);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--roxo-escuro);
    }

    a.logout-icon,
    button.logout-icon {
      background: transparent !important;
      color: var(--roxo-principal) !important;
      border: none !important;
    }
    </style>
</head>
<body>
    <!-- Menu Hamburguer -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
                <a href="logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
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
        <a href="gerenciar_caminho.php" class="list-group-item  active">
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

    <div class="main-content" id="mainContent">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus-circle me-2"></i>Adicionar Exercício
                    </h2>
                    <p class="text-muted mb-0">
                        Unidade: <strong><?php echo htmlspecialchars($unidade_info['nome_unidade'] ?? 'N/A'); ?></strong>
                    </p>
                </div>
                <div>
                    <a href="gerenciar_exercicios.php?unidade_id=<?php echo htmlspecialchars($unidade_id); ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i>Voltar para Exercícios
                    </a>
                </div>
            </div>

            <?php echo $mensagem; ?>

            <div class="alert alert-info">
                <strong>📍 Adicionando Exercício para:</strong><br>
                • <strong>Unidade:</strong> <?php echo htmlspecialchars($unidade_info['nome_unidade'] ?? 'N/A'); ?><br>
                • <strong>Idioma:</strong> <?php echo htmlspecialchars($unidade_info['idioma'] ?? 'N/A'); ?><br>
                • <strong>Nível:</strong> <?php echo htmlspecialchars($unidade_info['nivel'] ?? 'N/A'); ?><br>
                <small class="text-muted">Este exercício ficará disponível APENAS nesta unidade específica.</small>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Limite de Atividades:</strong> Cada bloco pode ter no máximo 12 atividades normais. Apenas as primeiras 12 atividades de cada bloco serão exibidas aos usuários.
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-1"></i>Formulário de Exercício
                    </h5>
                </div>
                <div class="card-body">
                    <form action="adicionar_atividades.php?unidade_id=<?php echo htmlspecialchars($unidade_id); ?>" method="POST">
                        
                        <!-- Campo Caminho -->
                        <div class="mb-3">
                            <label for="caminho_id" class="form-label">Selecionar Caminho *</label>
                            <select class="form-select" id="caminho_id" name="caminho_id" required onchange="carregarBlocos(this.value)">
                                <option value="">-- Selecione um caminho --</option>
                                <?php foreach ($caminhos as $caminho): ?>
                                    <option value="<?php echo $caminho['id']; ?>" 
                                        <?php echo ($post_caminho_id == $caminho['id']) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($caminho['nome_caminho']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo Bloco -->
                        <div class="mb-3">
                            <label for="bloco_id" class="form-label">Selecionar Bloco *</label>
                            <select class="form-select" id="bloco_id" name="bloco_id" required>
                                <option value="">-- Primeiro selecione um caminho --</option>
                            </select>
                        </div>

                        <?php if (!empty($post_caminho_id)): ?>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            carregarBlocos(<?php echo $post_caminho_id; ?>, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
                        });
                        </script>
                        <?php endif; ?>

                        <!-- Campo Tipo -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Exercício</label>
                            <select class="form-select" id="tipo" name="tipo" required onchange="toggleOrdemField()">
                                <option value="normal" <?php echo ($post_tipo == "normal") ? "selected" : ""; ?>>Normal</option>
                                <option value="quiz" <?php echo ($post_tipo == "quiz") ? "selected" : ""; ?>>Quiz</option>
                            </select>
                        </div>
                        
                        <!-- Campo Subtipo -->
                        <div class="mb-3">
                            <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                            <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                                <option value="multipla_escolha" <?php echo ($post_tipo_exercicio == "multipla_escolha") ? "selected" : ""; ?>>Múltipla Escolha</option>
                                <option value="completar" <?php echo ($post_tipo_exercicio == "completar") ? "selected" : ""; ?>>Completar Frase</option>
                                <option value="listening" <?php echo ($post_tipo_exercicio == "listening") ? "selected" : ""; ?>>Exercício de Listening</option>
                            </select>
                        </div>

                        <!-- Campo Ordem -->
                        <div class="mb-3" id="campo-ordem">
                            <label for="ordem" class="form-label">Ordem do Exercício no Bloco</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($post_ordem); ?>" min="1" max="12">
                            <div class="form-text">Define a sequência em que este exercício aparecerá dentro do bloco (1-12).</div>
                        </div>

                        <!-- Campo Pergunta -->
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($post_pergunta); ?></textarea>
                        </div>

                        <!-- Campos Dinâmicos -->
                        <div id="conteudo-campos" style="display: none;">
                            <!-- Conteúdo dos campos dinâmicos permanece igual -->
                            <div id="campos-normal">
                                <!-- Campos para Múltipla Escolha -->
                                <div id="campos-multipla" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Múltipla Escolha</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Alternativas</label>
                                        <div id="alternativas-container">
                                            <?php
                                            if (!empty($post_alt_texto)) {
                                                foreach ($post_alt_texto as $index => $texto) {
                                                    $letra = chr(65 + $index);
                                                    echo "
                                                    <div class=\"input-group mb-2\">
                                                        <span class=\"input-group-text\">{$letra}</span>
                                                        <input type=\"text\" class=\"form-control\" name=\"alt_texto[]\" placeholder=\"Texto da alternativa\" value=\"" . htmlspecialchars($texto) . "\">
                                                        <div class=\"input-group-text\">
                                                            <input type=\"radio\" name=\"alt_correta\" value=\"{$index}\" " . (($post_alt_correta == $index) ? "checked" : "") . " title=\"Marcar como correta\">
                                                        </div>
                                                        <button type=\"button\" class=\"btn btn-outline-danger\" onclick=\"this.parentElement.remove()\">×</button>
                                                    </div>";
                                                }
                                            } else {
                                                echo "
                                                <div class=\"input-group mb-2\">
                                                    <span class=\"input-group-text\">A</span>
                                                    <input type=\"text\" class=\"form-control\" name=\"alt_texto[]\" placeholder=\"Texto da alternativa\">
                                                    <div class=\"input-group-text\">
                                                        <input type=\"radio\" name=\"alt_correta\" value=\"0\" title=\"Marcar como correta\">
                                                    </div>
                                                    <button type=\"button\" class=\"btn btn-outline-danger\" onclick=\"this.parentElement.remove()\">×</button>
                                                </div>";
                                            }
                                            ?>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="adicionarAlternativa()">Adicionar Alternativa</button>
                                    </div>
                                    <div class="mb-3">
                                        <label for="explicacao" class="form-label">Explicação (Opcional)</label>
                                        <textarea class="form-control" id="explicacao" name="explicacao" rows="2"><?php echo htmlspecialchars($post_explicacao); ?></textarea>
                                    </div>
                                </div>



                                <!-- Campos para Completar Frase -->
                                <div id="campos-completar" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Completar Frase</h5>
                                    <div class="mb-3">
                                        <label for="frase_completar" class="form-label">Frase para Completar *</label>
                                        <textarea class="form-control" id="frase_completar" name="frase_completar" rows="2"><?php echo htmlspecialchars($post_frase_completar); ?></textarea>
                                        <div class="form-text">Use <code>_____</code> para indicar o espaço a ser preenchido. Ex: "Eu gosto de ___ maçãs."</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="resposta_completar" class="form-label">Resposta Correta *</label>
                                        <input type="text" class="form-control" id="resposta_completar" name="resposta_completar" value="<?php echo htmlspecialchars($post_resposta_completar); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="alternativas_completar" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                        <input type="text" class="form-control" id="alternativas_completar" name="alternativas_completar" value="<?php echo htmlspecialchars($post_alternativas_completar); ?>">
                                        <div class="form-text">Ex: resposta um, resposta dois. Inclua a resposta correta aqui também.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dica_completar" class="form-label">Dica (Opcional)</label>
                                        <input type="text" class="form-control" id="dica_completar" name="dica_completar" value="<?php echo htmlspecialchars($post_dica_completar); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="placeholder_completar" class="form-label">Placeholder do Campo (Opcional)</label>
                                        <input type="text" class="form-control" id="placeholder_completar" name="placeholder_completar" value="<?php echo htmlspecialchars($post_placeholder_completar); ?>">
                                    </div>
                                </div>

                                <!-- Campos para Listening -->
                                <div id="campos-listening" class="subtipo-campos" style="display: none;">
                                    <h5>Configuração - Exercício de Listening</h5>
                                    
                                    <div class="mb-3">
                                        <label for="frase_listening" class="form-label">Frase para Gerar Áudio *</label>
                                        <textarea class="form-control" id="frase_listening" name="frase_listening" rows="3" 
                                                placeholder="Digite a frase que os alunos irão ouvir..."><?php echo htmlspecialchars($post_frase_listening); ?></textarea>
                                        <div class="form-text">Esta frase será convertida em áudio para o exercício de listening</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="idioma_audio" class="form-label">Idioma do Áudio *</label>
                                        <select class="form-select" id="idioma_audio" name="idioma_audio">
                                            <option value="en-us" <?php echo ($post_idioma_audio == 'en-us') ? 'selected' : ''; ?>>Inglês (EUA)</option>
                                            <option value="en-gb" <?php echo ($post_idioma_audio == 'en-gb') ? 'selected' : ''; ?>>Inglês (UK)</option>
                                            <option value="es-es" <?php echo ($post_idioma_audio == 'es-es') ? 'selected' : ''; ?>>Espanhol</option>
                                            <option value="fr-fr" <?php echo($post_idioma_audio == 'fr-fr') ? 'selected' : ''; ?>>Francês</option>
                                            <option value="de-de" <?php echo ($post_idioma_audio == 'de-de') ? 'selected' : ''; ?>>Alemão</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Opções de Resposta *</label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="listening_alt_correta" value="0" <?php echo ($post_listening_alt_correta == '0') ? 'checked' : ''; ?> title="Marcar como correta">
                                            </div>
                                            <input type="text" class="form-control" name="listening_opcao1" placeholder="Opção 1" required value="<?php echo htmlspecialchars($post_listening_opcao1); ?>">
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="listening_alt_correta" value="1" <?php echo ($post_listening_alt_correta == '1') ? 'checked' : ''; ?> title="Marcar como correta">
                                            </div>
                                            <input type="text" class="form-control" name="listening_opcao2" placeholder="Opção 2" required value="<?php echo htmlspecialchars($post_listening_opcao2); ?>">
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="listening_alt_correta" value="2" <?php echo ($post_listening_alt_correta == '2') ? 'checked' : ''; ?> title="Marcar como correta">
                                            </div>
                                            <input type="text" class="form-control" name="listening_opcao3" placeholder="Opção 3 (Opcional)" value="<?php echo htmlspecialchars($post_listening_opcao3); ?>">
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" name="listening_alt_correta" value="3" <?php echo ($post_listening_alt_correta == '3') ? 'checked' : ''; ?> title="Marcar como correta">
                                            </div>
                                            <input type="text" class="form-control" name="listening_opcao4" placeholder="Opção 4 (Opcional)" value="<?php echo htmlspecialchars($post_listening_opcao4); ?>">
                                        </div>
                                        <div class="form-text">Marque a bolinha ao lado da alternativa correta.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="explicacao_listening" class="form-label">Explicação (Opcional)</label>
                                        <textarea class="form-control" id="explicacao_listening" name="explicacao_listening" rows="2" 
                                                  placeholder="Explicação que aparecerá após a resposta"><?php echo htmlspecialchars($post_explicacao_listening); ?></textarea>
                                    </div>
                                    
                                    <!-- Preview do Áudio -->
                                    <div class="mb-3">
                                        <label class="form-label">Prévia do Áudio</label>
                                        <div id="audioPreview" class="audio-preview text-center">
                                            <p class="text-muted">Digite uma frase e clique em "Testar Áudio" para ouvir a pronúncia</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testarAudio()">
                                            <i class="fas fa-play me-1"></i>Testar Áudio
                                        </button>
                                        <small class="form-text text-muted d-block mt-1">
                                            <i class="fas fa-info-circle me-1"></i>Usa a síntese de voz do navegador para preview
                                        </small>
                                    </div>
                                </div>
                            </div>



                            <!-- Campos para Tipo Quiz -->
                            <div id="campos-quiz" class="subtipo-campos" style="display: none;">
                                <h5>Configuração - Quiz</h5>
                                <div class="mb-3">
                                    <label for="quiz_id" class="form-label">ID do Quiz *</label>
                                    <input type="number" class="form-control" id="quiz_id" name="quiz_id" value="<?php echo htmlspecialchars($post_quiz_id); ?>">
                                    <div class="form-text">Insira o ID do quiz existente.</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-1"></i>Adicionar Exercício
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Menu Hamburguer Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            });
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                });
            }
            
            // Fechar menu ao clicar em um link (mobile)
            const sidebarLinks = sidebar.querySelectorAll('.list-group-item');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('active');
                        }
                    }
                });
            });
        }
    });

    const blocosPorCaminho = <?php echo json_encode($blocos_por_caminho, JSON_UNESCAPED_UNICODE); ?>;

    function carregarBlocos(caminhoId, blocoSelecionado = null) {
        const selectBloco = document.getElementById("bloco_id");
        selectBloco.innerHTML = '<option value="">-- Selecione um bloco --</option>';

        const blocos = blocosPorCaminho[caminhoId] || [];

        if (blocos.length > 0) {
            blocos.forEach(bloco => {
                const option = document.createElement("option");
                option.value = bloco.id;
                option.textContent = `Bloco ${bloco.ordem}: ${bloco.nome_bloco}`;
                
                if (blocoSelecionado && bloco.id == blocoSelecionado) {
                    option.selected = true;
                }
                
                selectBloco.appendChild(option);
            });
        } else {
            selectBloco.innerHTML = '<option value="">-- Nenhum bloco encontrado --</option>';
        }
    }
    
    // JavaScript para gerenciar os campos dinâmicos
    document.addEventListener("DOMContentLoaded", function() {
        const tipoSelect = document.getElementById("tipo");
        const tipoExercicioSelect = document.getElementById("tipo_exercicio");
        const camposNormal = document.getElementById("campos-normal");
        const camposQuiz = document.getElementById("campos-quiz");
        
        const camposMultipla = document.getElementById("campos-multipla");
        const camposTexto = document.getElementById("campos-texto");
        const camposCompletar = document.getElementById("campos-completar");
        const camposAudicao = document.getElementById("campos-audicao");
        const camposListening = document.getElementById("campos-listening");

        // Função para gerenciar o atributo 'required'
        function setRequired(element, isRequired) {
            if (element) {
                element.required = isRequired;
            }
        }

        function setMultipleRequired(selector, isRequired) {
            document.querySelectorAll(selector).forEach(el => {
                if (el) {
                    el.required = isRequired;
                }
            });
        }

        function atualizarCampos() {
            const conteudoCampos = document.getElementById('conteudo-campos');
            
            // Esconder todos os campos (com verificação de null)
            if (camposNormal) camposNormal.style.display = "none";
            if (camposQuiz) camposQuiz.style.display = "none";
            if (camposMultipla) camposMultipla.style.display = "none";
            if (camposTexto) camposTexto.style.display = "none";
            if (camposCompletar) camposCompletar.style.display = "none";
            if (camposAudicao) camposAudicao.style.display = "none";
            if (camposListening) camposListening.style.display = "none";

            // Resetar todos os 'required'
            setRequired(document.getElementById('resposta_esperada'), false);
            setRequired(document.getElementById('frase_completar'), false);
            setRequired(document.getElementById('resposta_completar'), false);
            setRequired(document.getElementById('frase_listening'), false);
            setRequired(document.querySelector('input[name="listening_opcao1"]'), false);
            setRequired(document.querySelector('input[name="listening_opcao2"]'), false);
            setRequired(document.getElementById('audio_url'), false);
            setRequired(document.getElementById('resposta_audio_correta'), false);


            // Mostrar container principal sempre
            if (conteudoCampos) conteudoCampos.style.display = "block";
                
            if (tipoSelect.value === "normal") {
                if (camposNormal) camposNormal.style.display = "block";
                
                switch (tipoExercicioSelect.value) {
                    case "multipla_escolha":
                        if (camposMultipla) camposMultipla.style.display = "block";
                        break;

                    case "completar":
                        if (camposCompletar) camposCompletar.style.display = "block";
                        setRequired(document.getElementById('frase_completar'), true);
                        setRequired(document.getElementById('resposta_completar'), true);
                        break;
                    case "listening":
                        if (camposListening) camposListening.style.display = "block";
                        setRequired(document.getElementById('frase_listening'), true);
                        setRequired(document.querySelector('input[name="listening_opcao1"]'), true);
                        setRequired(document.querySelector('input[name="listening_opcao2"]'), true);
                        break;
                    case "audicao":
                        if (camposAudicao) camposAudicao.style.display = "block";
                        setRequired(document.getElementById('audio_url'), true);
                        setRequired(document.getElementById('resposta_audio_correta'), true);
                        break;
                }
            } else if (tipoSelect.value === "quiz") {
                if (camposQuiz) camposQuiz.style.display = "block";
            }
        }

        tipoSelect.addEventListener("change", atualizarCampos);
        tipoExercicioSelect.addEventListener("change", atualizarCampos);
        
        atualizarCampos();
        toggleOrdemField(); // Inicializar o campo ordem

        const initialCaminhoId = document.getElementById('caminho_id').value;
        if (initialCaminhoId) {
            carregarBlocos(initialCaminhoId, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
        }
        
        // Auto-preview para listening
        const fraseListening = document.getElementById('frase_listening');
        if (fraseListening) {
            let timeoutId;
            fraseListening.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const preview = document.getElementById('audioPreview');
                if (this.value.trim() && preview) {
                    timeoutId = setTimeout(() => {
                        preview.innerHTML = `
                            <p class="text-info"><i class="fas fa-volume-up me-1"></i>Frase pronta para teste: "${this.value.trim()}"</p>
                            <button type="button" class="btn btn-sm btn-success" onclick="testarAudio()">
                                <i class="fas fa-play me-1"></i>Reproduzir Agora
                            </button>
                        `;
                    }, 1000);
                } else if (preview) {
                    preview.innerHTML = '<p class="text-muted">Digite uma frase e clique em "Testar Áudio" para ouvir a pronúncia</p>';
                }
            });
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

    // Função para testar áudio do listening usando Web Speech API
    function testarAudio() {
        const frase = document.getElementById('frase_listening').value;
        const idioma = document.getElementById('idioma_audio').value;
        
        if (!frase) {
            alert('Digite uma frase primeiro');
            return;
        }
        
        const preview = document.getElementById('audioPreview');
        if (!preview) return;
        
        // Verificar se o navegador suporta Web Speech API
        if (!('speechSynthesis' in window)) {
            preview.innerHTML = '<p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Seu navegador não suporta síntese de voz.</p>';
            return;
        }
        
        preview.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p>Gerando áudio...</p>';
        
        try {
            // Parar qualquer fala anterior
            speechSynthesis.cancel();
            
            // Criar utterance
            const utterance = new SpeechSynthesisUtterance(frase);
            
            // Mapear idiomas
            const langMap = {
                'en-us': 'en-US',
                'en-gb': 'en-GB', 
                'es-es': 'es-ES',
                'fr-fr': 'fr-FR',
                'de-de': 'de-DE',
                'pt-br': 'pt-BR'
            };
            
            utterance.lang = langMap[idioma] || 'en-US';
            utterance.rate = 0.8;
            utterance.pitch = 1;
            utterance.volume = 0.7;
            
            utterance.onstart = function() {
                preview.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <button type="button" class="btn btn-danger btn-sm me-2" onclick="speechSynthesis.cancel()">
                            <i class="fas fa-stop"></i> Parar
                        </button>
                        <span class="text-primary"><i class="fas fa-volume-up me-1"></i>Reproduzindo áudio...</span>
                    </div>
                    <p class="text-muted small">Frase: "${frase}"</p>
                `;
            };
            
            utterance.onend = function() {
                preview.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <button type="button" class="btn btn-primary btn-sm me-2" onclick="testarAudio()">
                            <i class="fas fa-play"></i> Reproduzir Novamente
                        </button>
                    </div>
                    <p class="text-success small"><i class="fas fa-check me-1"></i>Áudio reproduzido com sucesso!</p>
                    <p class="text-muted small">Frase: "${frase}"</p>
                `;
            };
            
            utterance.onerror = function(event) {
                console.error('Erro na síntese de voz:', event.error);
                preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Erro ao reproduzir áudio: ${event.error}</p>`;
            };
            
            // Reproduzir
            speechSynthesis.speak(utterance);
            
        } catch (error) {
            console.error('Erro ao testar áudio:', error);
            preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Erro ao gerar áudio.</p>`;
        }
    }

    // Validação do formulário antes do envio
    document.querySelector('form').addEventListener('submit', function(e) {
        console.log('Form submission initiated.');
        const tipoExercicio = document.getElementById('tipo_exercicio').value;
        let isValid = true;
        let errorMessage = '';

        // Validações específicas por tipo de exercício
        switch(tipoExercicio) {
            case 'multipla_escolha':
                const alternativas = document.querySelectorAll('input[name="alt_texto[]"]');
                const temAlternativaCorreta = document.querySelector('input[name="alt_correta"]:checked');
                let temAlternativasPreenchidas = false;
                
                alternativas.forEach(alt => {
                    if (alt.value.trim() !== '') {
                        temAlternativasPreenchidas = true;
                    }
                });
                
                if (!temAlternativasPreenchidas) {
                    isValid = false;
                    errorMessage = 'É necessário preencher pelo menos uma alternativa para múltipla escolha.';
                } else if (!temAlternativaCorreta) {
                    isValid = false;
                    errorMessage = 'É necessário marcar uma alternativa como correta.';
                }
                break;



            case 'completar':
                const fraseCompletar = document.getElementById('frase_completar').value;
                const respostaCompletar = document.getElementById('resposta_completar').value;
                if (!fraseCompletar.trim() || !respostaCompletar.trim()) {
                    isValid = false;
                    errorMessage = 'A frase para completar e a resposta são obrigatórias.';
                }
                break;

            case 'listening':
                const fraseListening = document.getElementById('frase_listening').value;
                const opcao1 = document.querySelector('input[name="listening_opcao1"]').value;
                const opcao2 = document.querySelector('input[name="listening_opcao2"]').value;
                const temOpcaoCorreta = document.querySelector('input[name="listening_alt_correta"]:checked');
                
                if (!fraseListening.trim()) {
                    isValid = false;
                    errorMessage = 'A frase para gerar áudio é obrigatória para listening.';
                } else if (!opcao1.trim() || !opcao2.trim()) {
                    isValid = false;
                    errorMessage = 'É necessário preencher pelo menos duas opções para listening.';
                } else if (!temOpcaoCorreta) {
                    isValid = false;
                    errorMessage = 'É necessário marcar uma opção como correta para listening.';
                }
                break;
        }

        if (!isValid) {
            e.preventDefault();
            alert('Erro no formulário: ' + errorMessage);
            
            // Rolar até o campo com problema
            const camposDiv = document.getElementById('conteudo-campos');
            if (camposDiv) {
                camposDiv.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });

    // Função para controlar o campo ordem baseado no tipo
    function toggleOrdemField() {
        const tipoSelect = document.getElementById('tipo');
        const campoOrdem = document.getElementById('campo-ordem');
        const inputOrdem = document.getElementById('ordem');
        
        if (tipoSelect && campoOrdem && inputOrdem) {
            campoOrdem.style.display = 'block';
            inputOrdem.required = true;
        }
    }

    // Auto-hide success message
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 500);
        }, 5000);
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>