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
    // Mapear tipo_exercicio para o ENUM da coluna 'tipo'
    $tipoEnum = 'normal'; // padrão
    if ($tipo_exercicio === 'especial') {
        $tipoEnum = 'especial';
    } elseif ($tipo_exercicio === 'quiz') {
        $tipoEnum = 'quiz';
    }
    
    // Definir categoria baseada no tipo_exercicio
    $categoria = 'gramatica'; // padrão
    switch ($tipo_exercicio) {
        case 'listening':
            $categoria = 'audicao';
            break;
        case 'fala':
            $categoria = 'fala';
            break;
        case 'texto_livre':
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
            $stmt->close();
            return true;
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

// CAMPOS ESPECÍFICOS PARA FALA
$post_frase_esperada = $_POST["frase_esperada"] ?? '';
$post_idioma_fala = $_POST["idioma_fala"] ?? 'en-US';
$post_pronuncia_fonetica = $_POST["pronuncia_fonetica"] ?? '';
$post_palavras_chave = $_POST["palavras_chave"] ?? '';
$post_explicacao_fala = $_POST["explicacao_fala"] ?? '';
$post_tolerancia_erro = $_POST["tolerancia_erro"] ?? '0.8';
$post_max_tentativas = $_POST["max_tentativas"] ?? '3';

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
$post_link_video = $_POST["link_video"] ?? '';
$post_pergunta_extra = $_POST["pergunta_extra"] ?? '';
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

            case 'texto_livre':
                if (empty($_POST['resposta_esperada'])) {
                    $mensagem = '<div class="alert alert-danger">Resposta esperada é obrigatória.</div>';
                } else {
                    $alternativas_aceitas = !empty($_POST['alternativas_aceitas']) ? 
                        array_map('trim', explode(',', $_POST['alternativas_aceitas'])) : 
                        [$_POST['resposta_esperada']];
                    $conteudo = json_encode([
                        'resposta_correta' => $_POST['resposta_esperada'],
                        'alternativas_aceitas' => $alternativas_aceitas,
                        'dica' => $_POST['dica_texto'] ?? ''
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

            case 'fala':
                if (empty($_POST['frase_esperada']) || empty($_POST['idioma_fala'])) {
                    $mensagem = '<div class="alert alert-danger">Frase esperada e idioma são obrigatórios para exercícios de fala.</div>';
                } else {
                    $palavras_chave = !empty($_POST['palavras_chave']) ? 
                        array_map('trim', explode(',', $_POST['palavras_chave'])) : 
                        [];
                    
                    // Estrutura corrigida para exercícios de fala
                    $conteudo = json_encode([
                        'frase_esperada' => $_POST['frase_esperada'],
                        'texto_para_falar' => $_POST['frase_esperada'],
                        'idioma' => $_POST['idioma_fala'],
                        'dicas_pronuncia' => $_POST['explicacao_fala'] ?? '',
                        'palavras_chave' => $palavras_chave,
                        'contexto' => 'Exercício de pronúncia',
                        'pronuncia_fonetica' => $_POST['pronuncia_fonetica'] ?? '',
                        'tolerancia_erro' => floatval($_POST['tolerancia_erro'] ?? 0.8),
                        'max_tentativas' => intval($_POST['max_tentativas'] ?? 3),
                        'tipo_exercicio' => 'fala'
                    ], JSON_UNESCAPED_UNICODE);
                    
                    $sucesso_insercao = true;
                }
                break;

            case 'listening':
                if (empty($_POST['frase_listening']) || empty($_POST['listening_opcao1']) || empty($_POST['listening_opcao2']) || !isset($_POST['listening_alt_correta'])) {
                    $mensagem = '<div class="alert alert-danger">Frase, pelo menos 2 opções e a indicação da resposta correta são obrigatórios para listening.</div>';
                } else {
                    try {
                        // Usar sistema de áudio simplificado para evitar conflitos
                        $audio_url = '';
                        
                        // Tentar usar o sistema novo se disponível
                        if (file_exists(__DIR__ . '/../../src/Services/AudioService.php')) {
                            try {
                                require_once __DIR__ . '/../../src/Services/AudioService.php';
                                
                                // Autoload simplificado
                                if (!class_exists('\App\Services\AudioService')) {
                                    spl_autoload_register(function ($class) {
                                        $file = __DIR__ . '/../../src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
                                        if (file_exists($file)) {
                                            require $file;
                                        }
                                    });
                                }
                                
                                $audioService = new \App\Services\AudioService();
                                $audio_url = $audioService->gerarAudio(
                                    $_POST['frase_listening'], 
                                    $_POST['idioma_audio'] ?? 'en-us'
                                );
                            } catch (Exception $audioError) {
                                // Fallback: usar sistema antigo ou URL placeholder
                                $audio_url = '/App_idiomas/audios/placeholder_' . md5($_POST['frase_listening']) . '.mp3';
                                error_log('Erro no AudioService: ' . $audioError->getMessage());
                            }
                        } else {
                            // Fallback: gerar URL placeholder
                            $audio_url = '/App_idiomas/audios/placeholder_' . md5($_POST['frase_listening']) . '.mp3';
                        }
                        
                        $opcoes = [
                            trim($_POST['listening_opcao1']),
                            trim($_POST['listening_opcao2'])
                        ];
                        
                        if (!empty($_POST['listening_opcao3'])) $opcoes[] = trim($_POST['listening_opcao3']);
                        if (!empty($_POST['listening_opcao4'])) $opcoes[] = trim($_POST['listening_opcao4']);
                        
                        $resposta_correta_index = (int)($_POST['listening_alt_correta'] ?? 0);
                        
                        // Estrutura corrigida para listening
                        $conteudo = json_encode([
                            'frase_original' => $_POST['frase_listening'],
                            'audio_url' => $audio_url,
                            'opcoes' => $opcoes,
                            'resposta_correta' => $resposta_correta_index,
                            'explicacao' => $_POST['explicacao_listening'] ?? '',
                            'transcricao' => $_POST['frase_listening'],
                            'dicas_compreensao' => 'Ouça com atenção e foque nas palavras-chave.',
                            'idioma' => $_POST['idioma_audio'] ?? 'en-us',
                            'tipo_exercicio' => 'listening'
                        ], JSON_UNESCAPED_UNICODE);
                        
                        $sucesso_insercao = true;
                        
                    } catch (Exception $e) {
                        $mensagem = '<div class="alert alert-danger">Erro ao gerar áudio: ' . $e->getMessage() . '</div>';
                    }
                }
                break;



            case 'especial':
                if (empty($_POST['link_video']) || empty($_POST['pergunta_extra'])) {
                    $mensagem = '<div class="alert alert-danger">O Link do Vídeo/Áudio e a Pergunta Extra são obrigatórios para este tipo de exercício.</div>';
                } else {
                    $conteudo = json_encode([
                        'link_video' => $_POST['link_video'],
                        'pergunta_extra' => $_POST['pergunta_extra']
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
                $mensagem = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Exercício de ' . $tipo_exercicio . ' adicionado com sucesso! ID: ' . $exercicio_id . '</div>';
                
                // Limpar apenas os campos do formulário, não todos os POST
                $_POST['pergunta'] = '';
                $_POST['frase_esperada'] = '';
                $_POST['frase_listening'] = '';
                $_POST['resposta_esperada'] = '';
                $_POST['frase_completar'] = '';
                $_POST['resposta_completar'] = '';
                $_POST['explicacao'] = '';
                $_POST['explicacao_fala'] = '';
                $_POST['explicacao_listening'] = '';
                
                // Manter os selects preenchidos
                $post_caminho_id = $_POST["caminho_id"];
                $post_bloco_id = $_POST["bloco_id"];
                $post_ordem = $_POST["ordem"];
                $post_tipo = $_POST["tipo"];
                $post_tipo_exercicio = $_POST["tipo_exercicio"];
            } else {
                $mensagem = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao adicionar exercício no banco de dados. Verifique os logs para mais detalhes.</div>';
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
    :root {
        --roxo-principal: #6a0dad;
        --roxo-escuro: #4c087c;
        --roxo-claro: #8b5cf6;
        --amarelo-detalhe: #ffd700;
        --amarelo-botao: #ffd700;
        --amarelo-hover: #e6c200;
        --branco: #ffffff;
        --preto-texto: #212529;
        --cinza-claro: #f8f9fa;
        --cinza-medio: #dee2e6;
        --gradiente-roxo: linear-gradient(135deg, #6a0dad 0%, #4c087c 100%);
        --gradiente-amarelo: linear-gradient(135deg, #ffd700 0%, #e6c200 100%);
        --gradiente-verde: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        --gradiente-azul: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
        --amarelo-acoes: #cfa90dff;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: var(--preto-texto);
        animation: fadeIn 1s ease-in-out;
        min-height: 100vh;
    }

    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(20px);
        }
        to { 
            opacity: 1; 
            transform: translateY(0);
        }
    }

    .settings-icon {
        color: var(--roxo-principal) !important;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 1.2rem;
    }

    .settings-icon:hover {
        color: var(--roxo-escuro) !important;
        transform: rotate(90deg) scale(1.1);
    }

    .table-container {
        background: var(--branco);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 30px rgba(106, 13, 173, 0.15);
        border: 2px solid rgba(106, 13, 173, 0.1);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        backdrop-filter: blur(10px);
    }

    .table-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(106, 13, 173, 0.25);
        border-color: rgba(106, 13, 173, 0.2);
    }

    .card-header {
        background: var(--gradiente-roxo);
        color: white;
        padding: 20px 25px;
        border-radius: 15px 15px 0 0 !important;
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
;
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: shine 3s infinite;
    }

    @keyframes shine {
        0% { left: -100%; }
        50% { left: 100%; }
        100% { left: 100%; }
    }

    .card-header h5 {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 2;
    }

    .card-header h5 i {
        color: var(--amarelo-detalhe);
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .stats-card {
        background: var(--branco);
        color: var(--preto-texto);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        border: 2px solid rgba(106, 13, 173, 0.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        animation: statsCardAnimation 0.8s ease-out;
        position: relative;
        overflow: hidden;
        text-align: center;
        backdrop-filter: blur(10px);
    }

    @keyframes statsCardAnimation {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(106, 13, 173, 0.08), transparent);
        transition: left 0.6s ease;
    }

    .stats-card:hover::before {
        left: 100%;
    }

    .stats-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px rgba(106, 13, 173, 0.3);
        border-color: rgba(106, 13, 173, 0.3);
    }

    .stats-card h3 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 8px;
        background: var(--gradiente-roxo);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 4px 8px rgba(106, 13, 173, 0.2);
    }

    .stats-card p {
        margin-bottom: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--roxo-escuro);
    }

    .stats-card i {
        font-size: 2.5rem;
        color: var(--amarelo-detalhe);
        margin-bottom: 1.2rem;
        text-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
    }

    .navbar {
        background: transparent !important;
        border-bottom: 3px solid var(--amarelo-detalhe);
        box-shadow: 0 6px 20px rgba(255, 238, 0, 0.25);
        backdrop-filter: blur(10px);
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
        height: 75px;
        width: auto;
        display: block;
        transition: transform 0.3s ease;
    }

    .navbar-brand .logo-header:hover {
        transform: scale(1.05);
    }

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
    }

    .sidebar .profile {
        text-align: center;
        margin-bottom: 30px;
        padding: 0 15px;
    }

    .profile-avatar-sidebar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid var(--amarelo-detalhe);
        background: linear-gradient(135deg, var(--roxo-claro), var(--roxo-principal));
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

    .profile-avatar-sidebar:has(img) i {
        display: none;
    }

    .admin-name {
        font-weight: 600;
        margin-bottom: 0;
        color: var(--branco);
        word-wrap: break-word;
        max-width: 200px;
        text-align: center;
        line-height: 1.3;
        font-size: 1rem;
    }

    .admin-email {
        color: var(--cinza-claro);
        word-wrap: break-word;
        max-width: 200px;
        text-align: center;
        font-size: 0.75rem;
        line-height: 1.2;
        margin-top: 5px;
        opacity: 0.9;
    }

    .sidebar .profile i {
        font-size: 4rem;
        color: var(--amarelo-detalhe);
        margin-bottom: 10px;
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
        margin-left: 250px;
        padding: 20px;
    }

    .btn-warning {
        background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
        color: var(--preto-texto);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        min-width: 180px;
        border: none;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
        color: var(--preto-texto);
    }

    @media (max-width: 768px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
        }
        .main-content {
            margin-left: 0;
        }
        .stats-card h3 {
            font-size: 2rem;
        }
    }

    .btn {
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 700;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        border: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -150%;
        width: 150%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transform: skewX(-25deg);
        transition: left 0.8s ease;
    }

    .btn:hover::before {
        left: 150%;
    }

    .btn-warning {
        background: var(--gradiente-amarelo);
        color: var(--preto-texto);
        box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        border: none;
        min-width: 200px;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #e6c200 0%, #cc9900 100%);
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px var(--roxo-principal);
        color: var(--preto-texto);
    }

    .btn-primary {
        background: var(--gradiente-roxo);
        border: none;
        color: white;
        box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(106, 13, 173, 0.6);
        color: var(--amarelo-detalhe);
    }

    .btn-outline-warning {
        background: var(--branco);
        color: var(--roxo-principal);
        border-color: var(--roxo-principal);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
    }

    .btn-outline-warning:hover {
        background: var(--roxo-principal);
        color: white;
        border-color: var(--roxo-principal);
        box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
        transform: translateY(-2px);
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
        background-color:var(--roxo-escuro);
        border-color: var(--branco); 
        color: var(--branco);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
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

    .microphone-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
        transition: all 0.3s ease;
    }

    .microphone-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }

    .microphone-btn.listening {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .speech-status {
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        text-align: center;
    }

    .speech-listening {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
    }

    .speech-success {
        background: #d1edff;
        border: 1px solid #b3d9ff;
        color: #004085;
    }

    .speech-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .microphone-permission {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
    }

    .microphone-permission h5 {
        color: #856404;
        margin-bottom: 10px;
    }

    .microphone-permission ol {
        text-align: left;
        margin-bottom: 10px;
    }

    .microphone-permission li {
        margin-bottom: 5px;
    }

    .permission-granted {
        background: #d1edff;
        border: 1px solid #b3d9ff;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        color: #004085;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

   <div class="sidebar">
    <div class="profile">
        <?php if ($foto_admin): ?>
            <div class="profile-avatar-sidebar">
                <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
            </div>
        <?php else: ?>
            <i class="fas fa-user-circle"></i>
        <?php endif; ?>
        <h5 class="admin-name"><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
        <small class="admin-email"><?php echo htmlspecialchars($_SESSION['email_admin']); ?></small>
    </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item">
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
            <a href="logout.php" class="list-group-item mt-auto">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content">
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

                        <!-- Campo Ordem -->
                        <div class="mb-3">
                            <label for="ordem" class="form-label">Ordem do Exercício no Bloco</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($post_ordem); ?>" required>
                            <div class="form-text">Define a sequência em que este exercício aparecerá dentro do bloco selecionado</div>
                        </div>

                        <!-- Campo Tipo -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Exercício</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="normal" <?php echo ($post_tipo == "normal") ? "selected" : ""; ?>>Normal</option>
                                <option value="especial" <?php echo ($post_tipo == "especial") ? "selected" : ""; ?>>Especial (Vídeo/Áudio)</option>
                                <option value="quiz" <?php echo ($post_tipo == "quiz") ? "selected" : ""; ?>>Quiz</option>
                            </select>
                        </div>
                        
                        <!-- Campo Subtipo -->
                        <div class="mb-3">
                            <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                            <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                                <option value="multipla_escolha" <?php echo ($post_tipo_exercicio == "multipla_escolha") ? "selected" : ""; ?>>Múltipla Escolha</option>
                                <option value="texto_livre" <?php echo ($post_tipo_exercicio == "texto_livre") ? "selected" : ""; ?>>Texto Livre (Completar)</option>
                                <option value="completar" <?php echo ($post_tipo_exercicio == "completar") ? "selected" : ""; ?>>Completar Frase</option>
                                <option value="fala" <?php echo ($post_tipo_exercicio == "fala") ? "selected" : ""; ?>>Exercício de Fala (Speaking)</option>
                                <option value="listening" <?php echo ($post_tipo_exercicio == "listening") ? "selected" : ""; ?>>Exercício de Listening</option>

                            </select>
                        </div>

                        <!-- Campo Pergunta -->
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($post_pergunta); ?></textarea>
                        </div>

                        <!-- Campos Dinâmicos -->
                        <div id="conteudo-campos">
                            <div id="campos-normal">
                                <!-- Campos para Múltipla Escolha -->
                                <div id="campos-multipla" class="subtipo-campos">
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

                                <!-- Campos para Texto Livre -->
                                <div id="campos-texto" class="subtipo-campos">
                                    <h5>Configuração - Texto Livre</h5>
                                    <div class="mb-3">
                                        <label for="resposta_esperada" class="form-label">Resposta Esperada *</label>
                                        <input type="text" class="form-control" id="resposta_esperada" name="resposta_esperada" value="<?php echo htmlspecialchars($post_resposta_esperada); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="alternativas_aceitas" class="form-label">Alternativas Aceitas (separadas por vírgula)</label>
                                        <input type="text" class="form-control" id="alternativas_aceitas" name="alternativas_aceitas" value="<?php echo htmlspecialchars($post_alternativas_aceitas); ?>">
                                        <div class="form-text">Ex: resposta um, resposta dois. Inclua a resposta esperada aqui também.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dica_texto" class="form-label">Dica (Opcional)</label>
                                        <input type="text" class="form-control" id="dica_texto" name="dica_texto" value="<?php echo htmlspecialchars($post_dica_texto); ?>">
                                    </div>
                                </div>

                                <!-- Campos para Completar Frase -->
                                <div id="campos-completar" class="subtipo-campos">
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

                                <!-- Campos para Exercício de Fala -->
                                <div id="campos-fala" class="subtipo-campos">
                                    <h5>Configuração - Exercício de Fala (Speaking)</h5>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Exercício de Pronúncia:</strong> Os alunos praticarão falando a frase e o sistema avaliará a pronúncia.
                                    </div>

                                    <!-- Status da Permissão do Microfone -->
                                    <div id="microfone-status" class="mb-3">
                                        <div class="permission-granted">
                                            <i class="fas fa-microphone me-2"></i>
                                            <strong>Microfone Permitido!</strong> O sistema está pronto para testar exercícios de fala.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="frase_esperada" class="form-label">Frase Esperada para Pronúncia *</label>
                                        <textarea class="form-control" id="frase_esperada" name="frase_esperada" rows="3" 
                                                  placeholder="Digite a frase que o aluno deve pronunciar"><?php echo htmlspecialchars($post_frase_esperada); ?></textarea>
                                        <div class="form-text">A frase exata que o aluno deve falar para ser considerada correta.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="idioma_fala" class="form-label">Idioma da Pronúncia *</label>
                                        <select class="form-select" id="idioma_fala" name="idioma_fala">
                                            <option value="en-US" <?php echo ($post_idioma_fala == 'en-US') ? 'selected' : ''; ?>>Inglês Americano</option>
                                            <option value="en-GB" <?php echo ($post_idioma_fala == 'en-GB') ? 'selected' : ''; ?>>Inglês Britânico</option>
                                            <option value="es-ES" <?php echo ($post_idioma_fala == 'es-ES') ? 'selected' : ''; ?>>Espanhol</option>
                                            <option value="fr-FR" <?php echo ($post_idioma_fala == 'fr-FR') ? 'selected' : ''; ?>>Francês</option>
                                            <option value="de-DE" <?php echo ($post_idioma_fala == 'de-DE') ? 'selected' : ''; ?>>Alemão</option>
                                            <option value="pt-BR" <?php echo ($post_idioma_fala == 'pt-BR') ? 'selected' : ''; ?>>Português Brasileiro</option>
                                        </select>
                                        <div class="form-text">Selecione o idioma para o reconhecimento de voz.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética (Opcional)</label>
                                        <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" 
                                               placeholder="Ex: /hɛˈloʊ/ para 'Hello'" value="<?php echo htmlspecialchars($post_pronuncia_fonetica); ?>">
                                        <div class="form-text">Transcrição fonética para referência (IPA).</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="palavras_chave" class="form-label">Palavras-Chave Importantes (Opcional)</label>
                                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" 
                                               placeholder="palavra1, palavra2, palavra3" value="<?php echo htmlspecialchars($post_palavras_chave); ?>">
                                        <div class="form-text">Separe por vírgula as palavras mais importantes para a avaliação.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="explicacao_fala" class="form-label">Explicação e Dicas de Pronúncia</label>
                                        <textarea class="form-control" id="explicacao_fala" name="explicacao_fala" rows="3" 
                                                  placeholder="Dicas para melhorar a pronúncia..."><?php echo htmlspecialchars($post_explicacao_fala); ?></textarea>
                                        <div class="form-text">Explicação que aparecerá após o aluno completar o exercício.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="tolerancia_erro" class="form-label">Tolerância de Erro</label>
                                                <select class="form-select" id="tolerancia_erro" name="tolerancia_erro">
                                                    <option value="0.9" <?php echo ($post_tolerancia_erro == '0.9') ? 'selected' : ''; ?>>Alta (90%) - Mais fácil</option>
                                                    <option value="0.8" <?php echo ($post_tolerancia_erro == '0.8') ? 'selected' : ''; ?>>Média (80%)</option>
                                                    <option value="0.7" <?php echo ($post_tolerancia_erro == '0.7') ? 'selected' : ''; ?>>Baixa (70%) - Mais difícil</option>
                                                </select>
                                                <div class="form-text">Quão precisa a pronúncia precisa ser.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="max_tentativas" class="form-label">Máximo de Tentativas</label>
                                                <select class="form-select" id="max_tentativas" name="max_tentativas">
                                                    <option value="1" <?php echo ($post_max_tentativas == '1') ? 'selected' : ''; ?>>1 tentativa</option>
                                                    <option value="3" <?php echo ($post_max_tentativas == '3') ? 'selected' : ''; ?>>3 tentativas</option>
                                                    <option value="5" <?php echo ($post_max_tentativas == '5') ? 'selected' : ''; ?>>5 tentativas</option>
                                                    <option value="999" <?php echo ($post_max_tentativas == '999') ? 'selected' : ''; ?>>Ilimitado</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview do Exercício de Fala -->
                                    <div class="mb-3">
                                        <label class="form-label">Prévia do Exercício de Fala</label>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div id="previewFala" class="text-center">
                                                    <p class="text-muted">Digite uma frase acima para ver a prévia</p>
                                                </div>
                                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="testarReconhecimentoFala()">
                                                    <i class="fas fa-microphone me-1"></i>Testar Reconhecimento de Voz
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos para Listening -->
                                <div id="campos-listening" class="subtipo-campos">
                                    <h5>Configuração - Exercício de Listening</h5>
                                    
                                    <div class="mb-3">
                                        <label for="frase_listening" class="form-label">Frase para Gerar Áudio *</label>
                                        <textarea class="form-control" id="frase_listening" name="frase_listening" rows="3" 
                                                placeholder="Digite a frase que será convertida em áudio automaticamente"><?php echo htmlspecialchars($post_frase_listening); ?></textarea>
                                        <div class="form-text">Esta frase será convertida em áudio automaticamente usando API de text-to-speech</div>
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
                                            <p class="text-muted">O áudio será gerado automaticamente após digitar a frase</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testarAudio()">
                                            <i class="fas fa-play me-1"></i>Testar Áudio
                                        </button>
                                    </div>
                                </div>

                                <!-- Campos para Exercício de Audição -->
                                <div id="campos-audicao" class="subtipo-campos">
                                    <h5>Configuração - Exercício de Audição</h5>
                                    <div class="mb-3">
                                        <label for="audio_url" class="form-label">URL do Áudio *</label>
                                        <input type="url" class="form-control" id="audio_url" name="audio_url" value="<?php echo htmlspecialchars($post_audio_url); ?>">
                                        <div class="form-text">Link para o arquivo de áudio (MP3, WAV, etc.).</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="transcricao" class="form-label">Transcrição do Áudio (Opcional)</label>
                                        <textarea class="form-control" id="transcricao" name="transcricao" rows="2"><?php echo htmlspecialchars($post_transcricao); ?></textarea>
                                        <div class="form-text">Texto do áudio para referência.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="resposta_audio_correta" class="form-label">Resposta Correta *</label>
                                        <input type="text" class="form-control" id="resposta_audio_correta" name="resposta_audio_correta" value="<?php echo htmlspecialchars($post_resposta_audio_correta); ?>">
                                        <div class="form-text">A resposta esperada do usuário após ouvir o áudio.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para Tipo Especial -->
                            <div id="campos-especial" class="subtipo-campos">
                                <h5>Configuração - Exercício Especial (Vídeo/Áudio)</h5>
                                <div class="mb-3">
                                    <label for="link_video" class="form-label">Link do Vídeo/Áudio (YouTube, Vimeo, etc.) *</label>
                                    <input type="url" class="form-control" id="link_video" name="link_video" value="<?php echo htmlspecialchars($post_link_video); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="pergunta_extra" class="form-label">Pergunta Extra *</label>
                                    <textarea class="form-control" id="pergunta_extra" name="pergunta_extra" rows="3"><?php echo htmlspecialchars($post_pergunta_extra); ?></textarea>
                                </div>
                            </div>

                            <!-- Campos para Tipo Quiz -->
                            <div id="campos-quiz" class="subtipo-campos">
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
        const camposEspecial = document.getElementById("campos-especial");
        const camposQuiz = document.getElementById("campos-quiz");
        
        const camposMultipla = document.getElementById("campos-multipla");
        const camposTexto = document.getElementById("campos-texto");
        const camposCompletar = document.getElementById("campos-completar");
        const camposFala = document.getElementById("campos-fala");
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
            // Esconder todos os campos principais
            camposNormal.style.display = "none";
            camposEspecial.style.display = "none";
            camposQuiz.style.display = "none";
            
            // Esconder todos os subcampos
            camposMultipla.style.display = "none";
            camposTexto.style.display = "none";
            camposCompletar.style.display = "none";
            camposFala.style.display = "none";
            camposAudicao.style.display = "none";
            camposListening.style.display = "none";

            // Resetar todos os 'required'
            setRequired(document.getElementById('resposta_esperada'), false);
            setRequired(document.getElementById('frase_completar'), false);
            setRequired(document.getElementById('resposta_completar'), false);
            setRequired(document.getElementById('frase_esperada'), false);
            setRequired(document.getElementById('idioma_fala'), false);
            setRequired(document.getElementById('frase_listening'), false);
            setRequired(document.querySelector('input[name="listening_opcao1"]'), false);
            setRequired(document.querySelector('input[name="listening_opcao2"]'), false);
            setRequired(document.getElementById('audio_url'), false);
            setRequired(document.getElementById('resposta_audio_correta'), false);
            setRequired(document.getElementById('link_video'), false);
            setRequired(document.getElementById('pergunta_extra'), false);

            if (tipoSelect.value === "normal") {
                camposNormal.style.display = "block";
                
                switch (tipoExercicioSelect.value) {
                    case "multipla_escolha":
                        camposMultipla.style.display = "block";
                        break;
                    case "texto_livre":
                        camposTexto.style.display = "block";
                        setRequired(document.getElementById('resposta_esperada'), true);
                        break;
                    case "completar":
                        camposCompletar.style.display = "block";
                        setRequired(document.getElementById('frase_completar'), true);
                        setRequired(document.getElementById('resposta_completar'), true);
                        break;
                    case "fala":
                        camposFala.style.display = "block";
                        setRequired(document.getElementById('frase_esperada'), true);
                        setRequired(document.getElementById('idioma_fala'), true);
                        // Verificar permissão quando mostrar campos de fala
                        setTimeout(() => {
                            verificarPermissaoMicrofoneAuto();
                        }, 500);
                        break;
                    case "listening":
                        camposListening.style.display = "block";
                        setRequired(document.getElementById('frase_listening'), true);
                        setRequired(document.querySelector('input[name="listening_opcao1"]'), true);
                        setRequired(document.querySelector('input[name="listening_opcao2"]'), true);
                        break;
                    case "audicao":
                        camposAudicao.style.display = "block";
                        setRequired(document.getElementById('audio_url'), true);
                        setRequired(document.getElementById('resposta_audio_correta'), true);
                        break;
                }
            } else if (tipoSelect.value === "especial") {
                camposEspecial.style.display = "block";
                setRequired(document.getElementById('link_video'), true);
                setRequired(document.getElementById('pergunta_extra'), true);
            } else if (tipoSelect.value === "quiz") {
                camposQuiz.style.display = "block";
            }
        }

        tipoSelect.addEventListener("change", atualizarCampos);
        tipoExercicioSelect.addEventListener("change", atualizarCampos);
        
        atualizarCampos();

        const initialCaminhoId = document.getElementById('caminho_id').value;
        if (initialCaminhoId) {
            carregarBlocos(initialCaminhoId, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
        }

        // Preview automático para fala
        const fraseFalaInput = document.getElementById('frase_esperada');
        if (fraseFalaInput) {
            fraseFalaInput.addEventListener('input', function() {
                const preview = document.getElementById('previewFala');
                if (this.value) {
                    preview.innerHTML = `
                        <div class="alert alert-light">
                            <strong>Exercício:</strong> Pronuncie a frase abaixo
                            <br><em>"${this.value}"</em>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="testarReconhecimentoFala()">
                            <i class="fas fa-microphone me-1"></i>Testar Reconhecimento de Voz
                        </button>
                    `;
                } else {
                    preview.innerHTML = '<p class="text-muted">Digite uma frase acima para ver a prévia</p>';
                }
            });
        }

        // Verificar permissão automaticamente ao carregar a página
        setTimeout(() => {
            if (document.getElementById('campos-fala').style.display !== 'none') {
                verificarPermissaoMicrofoneAuto();
            }
        }, 1000);
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

    // Função para verificar e solicitar permissão do microfone automaticamente
    async function verificarPermissaoMicrofoneAuto() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.warn('Navegador não suporta acesso ao microfone');
                atualizarStatusMicrofone('not-supported');
                return false;
            }
            
            // Tenta acessar o microfone silenciosamente
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            // Libera o stream imediatamente
            stream.getTracks().forEach(track => track.stop());
            
            console.log('Permissão do microfone concedida');
            atualizarStatusMicrofone('granted');
            return true;
            
        } catch (error) {
            console.log('Permissão do microfone necessária:', error.name);
            
            if (error.name === 'NotAllowedError') {
                atualizarStatusMicrofone('denied');
            } else {
                atualizarStatusMicrofone('error', error.message);
            }
            return false;
        }
    }

    // Função para atualizar o status visual da permissão
    function atualizarStatusMicrofone(status, mensagem = '') {
        const statusElement = document.getElementById('microfone-status');
        if (!statusElement) return;

        switch(status) {
            case 'granted':
                statusElement.innerHTML = `
                    <div class="permission-granted">
                        <i class="fas fa-microphone me-2"></i>
                        <strong>Microfone Permitido!</strong> O sistema está pronto para testar exercícios de fala.
                    </div>
                `;
                break;
            case 'denied':
                statusElement.innerHTML = `
                    <div class="microphone-permission">
                        <h5><i class="fas fa-microphone-slash me-2"></i>Permissão do Microfone Necessária</h5>
                        <p>Para usar exercícios de fala, você precisa permitir o acesso ao microfone:</p>
                        <ol>
                            <li>Clique no ícone de <strong>cadeado</strong> 🔒 ou <strong>câmera</strong> 📷 na barra de endereços</li>
                            <li>Procure por "Microfone" ou "Microphone"</li>
                            <li>Mude para <strong>"Permitir"</strong> ou <strong>"Allow"</strong></li>
                            <li>Recarregue a página ou <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">Clique aqui para recarregar</button></li>
                        </ol>
                        <div class="mt-3">
                            <button type="button" class="btn btn-warning btn-sm" onclick="solicitarPermissaoMicrofone()">
                                <i class="fas fa-microphone me-1"></i>Tentar Novamente
                            </button>
                        </div>
                    </div>
                `;
                break;
            case 'not-supported':
                statusElement.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Navegador Não Suportado</strong>
                        <p>Seu navegador não suporta acesso ao microfone. Use Chrome, Edge ou Safari.</p>
                    </div>
                `;
                break;
            case 'error':
                statusElement.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times me-2"></i>
                        <strong>Erro no Microfone</strong>
                        <p>${mensagem}</p>
                    </div>
                `;
                break;
        }
    }

    // Função para solicitar permissão explicitamente
    async function solicitarPermissaoMicrofone() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            stream.getTracks().forEach(track => track.stop());
            
            // Sucesso - recarrega a página
            window.location.reload();
        } catch (error) {
            console.error('Falha ao obter permissão:', error);
            atualizarStatusMicrofone('denied');
        }
    }

    // Função corrigida para testar reconhecimento de fala
    async function testarReconhecimentoFala() {
        const frase = document.getElementById('frase_esperada').value;
        const idioma = document.getElementById('idioma_fala').value;
        
        if (!frase) {
            alert('Digite uma frase primeiro para testar');
            return;
        }
        
        // Verifica permissão primeiro
        const temPermissao = await verificarPermissaoMicrofoneAuto();
        if (!temPermissao) {
            return;
        }
        
        const preview = document.getElementById('previewFala');
        if (!preview) return;
        
        preview.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-microphone me-2"></i>
                <strong>Microfone Permitido!</strong> Teste de reconhecimento pronto.
                <br><small>Frase: "${frase}" | Idioma: ${idioma}</small>
            </div>
            <p class="text-muted">Clique no botão abaixo e tente falar a frase</p>
            <button type="button" class="btn btn-success btn-sm" onclick="iniciarReconhecimentoTeste()">
                <i class="fas fa-play me-1"></i>Iniciar Teste de Voz
            </button>
            <div id="resultadoTeste" class="mt-2"></div>
        `;
    }

    let recognitionTeste = null;

    function iniciarReconhecimentoTeste() {
        const fraseEsperada = document.getElementById('frase_esperada').value;
        const idioma = document.getElementById('idioma_fala').value;
        const resultadoDiv = document.getElementById('resultadoTeste');
        
        if (!resultadoDiv) return;
        
        // Verificar suporte do navegador
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            resultadoDiv.innerHTML = '<div class="alert alert-warning">Seu navegador não suporta reconhecimento de voz. Use Chrome, Edge ou Safari.</div>';
            return;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognitionTeste = new SpeechRecognition();
        
        recognitionTeste.lang = idioma;
        recognitionTeste.continuous = false;
        recognitionTeste.interimResults = false;
        recognitionTeste.maxAlternatives = 1;
        
        resultadoDiv.innerHTML = `
            <div class="speech-status speech-listening">
                <i class="fas fa-microphone me-2"></i>Ouvindo... Fale agora!
                <br><small>Frase esperada: "${fraseEsperada}"</small>
            </div>
            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="pararReconhecimentoTeste()">
                <i class="fas fa-stop me-1"></i>Parar
            </button>
        `;
        
        recognitionTeste.start();
        
        recognitionTeste.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            const confidence = event.results[0][0].confidence;
            
            const isCorrect = transcript.toLowerCase().trim() === fraseEsperada.toLowerCase().trim();
            const similarity = calcularSimilaridade(transcript.toLowerCase(), fraseEsperada.toLowerCase());
            
            resultadoDiv.innerHTML = `
                <div class="speech-status ${isCorrect ? 'speech-success' : 'speech-error'}">
                    <i class="fas ${isCorrect ? 'fa-check' : 'fa-times'} me-2"></i>
                    <strong>${isCorrect ? 'Correto!' : 'Precisa melhorar'}</strong>
                    <br>Você disse: "${transcript}"
                    <br>Confiança: ${(confidence * 100).toFixed(1)}%
                    <br>Similaridade: ${(similarity * 100).toFixed(1)}%
                    ${!isCorrect ? `<br>Esperado: "${fraseEsperada}"` : ''}
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="testarReconhecimentoFala()">
                    <i class="fas fa-redo me-1"></i>Testar Novamente
                </button>
            `;
        };
        
        recognitionTeste.onerror = function(event) {
            let errorMessage = 'Erro desconhecido';
            switch(event.error) {
                case 'not-allowed':
                    errorMessage = 'Permissão de microfone negada. Permita o acesso ao microfone.';
                    break;
                case 'no-speech':
                    errorMessage = 'Nenhuma fala detectada. Tente novamente.';
                    break;
                case 'audio-capture':
                    errorMessage = 'Nenhum microfone detectado.';
                    break;
                case 'network':
                    errorMessage = 'Erro de rede. Tente novamente.';
                    break;
                default:
                    errorMessage = `Erro: ${event.error}`;
            }
            
            resultadoDiv.innerHTML = `
                <div class="speech-status speech-error">${errorMessage}</div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="testarReconhecimentoFala()">
                    <i class="fas fa-redo me-1"></i>Tentar Novamente
                </button>
            `;
        };
        
        recognitionTeste.onend = function() {
            console.log('Reconhecimento finalizado');
        };
    }

    function pararReconhecimentoTeste() {
        if (recognitionTeste) {
            recognitionTeste.stop();
        }
    }

    // Função auxiliar para calcular similaridade entre strings
    function calcularSimilaridade(str1, str2) {
        const longer = str1.length > str2.length ? str1 : str2;
        const shorter = str1.length > str2.length ? str2 : str1;
        
        if (longer.length === 0) return 1.0;
        
        return (longer.length - calcularDistancia(longer, shorter)) / parseFloat(longer.length);
    }

    function calcularDistancia(s1, s2) {
        s1 = s1.toLowerCase();
        s2 = s2.toLowerCase();

        const costs = [];
        for (let i = 0; i <= s1.length; i++) {
            let lastValue = i;
            for (let j = 0; j <= s2.length; j++) {
                if (i === 0) {
                    costs[j] = j;
                } else {
                    if (j > 0) {
                        let newValue = costs[j - 1];
                        if (s1.charAt(i - 1) !== s2.charAt(j - 1)) {
                            newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
                        }
                        costs[j - 1] = lastValue;
                        lastValue = newValue;
                    }
                }
            }
            if (i > 0) costs[s2.length] = lastValue;
        }
        return costs[s2.length];
    }

    // Função para testar áudio do listening
    async function testarAudio() {
        const frase = document.getElementById('frase_listening').value;
        const idioma = document.getElementById('idioma_audio').value;
        
        if (!frase) {
            alert('Digite uma frase primeiro');
            return;
        }
        
        const preview = document.getElementById('audioPreview');
        if (!preview) return;
        
        preview.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p>Gerando áudio...</p>';
        
        try {
            const formData = new FormData();
            formData.append('frase', frase.trim());
            formData.append('idioma', idioma.trim());
            
            const response = await fetch('../controller/gerar_audio_api.php', { // Corrigido para usar fetch
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                preview.innerHTML = `
                    <audio controls autoplay class="w-100" oncanplay="this.volume=0.5">
                        <source src="${data.audio_url}?t=${new Date().getTime()}" type="audio/mpeg">
                        Seu navegador não suporta o elemento de áudio.
                    </audio>
                    <p class="mt-2 text-success"><small><i class="fas fa-check me-1"></i>Áudio gerado com sucesso!</small></p>
                `;
            } else {
                preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Não foi possível gerar o áudio. Tente novamente.</p>`;
                console.error('API Error:', data.message);
            }
        } catch (error) {
            console.error('Erro ao testar áudio:', error);
            preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Erro de comunicação ao gerar áudio.</p>`;
        }
    }

    // Validação do formulário antes do envio
    document.querySelector('form').addEventListener('submit', function(e) {
        console.log('Form submission initiated.'); // Linha de depuração: verifica se o evento de submit está sendo acionado
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

            case 'texto_livre':
                const respostaEsperada = document.getElementById('resposta_esperada').value;
                if (!respostaEsperada.trim()) {
                    isValid = false;
                    errorMessage = 'A resposta esperada é obrigatória para texto livre.';
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

            case 'fala':
                const fraseEsperada = document.getElementById('frase_esperada').value;
                const idiomaFala = document.getElementById('idioma_fala').value; // Adiciona a verificação do idioma
                if (!fraseEsperada.trim() || !idiomaFala.trim()) {
                    isValid = false;
                    errorMessage = 'A frase esperada e o idioma são obrigatórios para exercícios de fala.'; // Mensagem de erro mais específica
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

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>