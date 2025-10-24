<?php
session_start();
include_once __DIR__ . '/../../conexao.php';
include_once __DIR__ . '/../models/listening_model.php'; // Model de Listening
include_once __DIR__ . '/../controller/listening_controller.php'; // Controller de Listening
// Ativar exibição de erros (apenas para desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID da UNIDADE foi passado via URL
if (!isset($_GET['unidade_id']) || !is_numeric($_GET['unidade_id'])) {
    header("Location: gerenciar_unidades.php");
    exit();
}

$unidade_id = $_GET['unidade_id'];
$mensagem = '';

// Instancia a conexão com o banco de dados
$database = new Database();
$conn = $database->conn;

if (!$conn) {
    // Se a conexão falhar, exibe um erro e interrompe
    die('<div class="alert alert-danger">Erro ao conectar com o banco de dados. Verifique o arquivo conexao.php.</div>');
}
$listeningModel = new ListeningModel($database); // Instancia o modelo de listening, passando a conexão

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

// Incluir os Models necessários
include_once __DIR__ . '/../models/UnidadeModel.php';
include_once __DIR__ . '/../models/ExercicioModel.php';
include_once __DIR__ . '/../models/SpeechExerciseModel.php';

$unidadeModel = new UnidadeModel($database);
$exercicioModel = new ExercicioModel($database);

// Buscar informações da unidade, caminhos e blocos
$unidade_info = $unidadeModel->getUnidadeInfo($unidade_id);
$caminhos = $unidadeModel->getCaminhosByUnidade($unidade_id);

$blocos_por_caminho = [];
if (!empty($caminhos)) {
    $caminho_ids = array_column($caminhos, 'id');
    $blocos_por_caminho = $unidadeModel->getBlocosByCaminhos($caminho_ids);
}

// Lógica para lidar com a submissão do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caminho_id = $_POST["caminho_id"] ?? null;
    $bloco_id = $_POST["bloco_id"] ?? null;
    $ordem = $_POST["ordem"] ?? null;
    $tipo = $_POST["tipo"] ?? null;
    $pergunta = $_POST["pergunta"] ?? null;
    $tipo_exercicio = $_POST["tipo_exercicio"] ?? null;
    $conteudo = null;
    $sucesso = false;

    if (empty($caminho_id) || empty($bloco_id) || empty($ordem) || empty($pergunta) || empty($tipo)) {
        $mensagem = '<div class="alert alert-danger">Erro: Todos os campos principais (Caminho, Bloco, Ordem, Pergunta, Tipo) são obrigatórios.</div>';
    } else {
        switch ($tipo) {
            case 'normal':
                if ($tipo_exercicio === 'multipla_escolha') {
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
                        $conteudo_array = [
                            'alternativas' => $alternativas,
                            'explicacao' => $_POST['explicacao'] ?? ''
                        ];
                    }
                } elseif ($tipo_exercicio === 'texto_livre') {
                    if (empty($_POST['resposta_esperada'])) {
                        $mensagem = '<div class="alert alert-danger">Resposta esperada é obrigatória.</div>';
                    } else {
                        $alternativas_aceitas = !empty($_POST['alternativas_aceitas']) ? 
                            array_map('trim', explode(',', $_POST['alternativas_aceitas'])) : 
                            [$_POST['resposta_esperada']];
                        $conteudo_array = [
                            'resposta_correta' => $_POST['resposta_esperada'],
                            'alternativas_aceitas' => $alternativas_aceitas,
                            'dica' => $_POST['dica_texto'] ?? ''
                        ];
                    }
                } elseif ($tipo_exercicio === 'fala') {
                    if (empty($_POST['frase_esperada'])) {
                        $mensagem = '<div class="alert alert-danger">Frase esperada é obrigatória.</div>';
                    } else {
                        $palavras_chave = !empty($_POST['palavras_chave']) ? 
                            array_map('trim', explode(',', $_POST['palavras_chave'])) : 
                            [];
                        
                        $conteudo_array = [
                            'frase_esperada' => $_POST['frase_esperada'],
                            'pronuncia_fonetica' => $_POST['pronuncia_fonetica'] ?? '',
                            'palavras_chave' => $palavras_chave,
                            'tolerancia_erro' => 0.8
                        ];
                    }
                } elseif ($tipo_exercicio === 'audicao') {
                    $conteudo_array = [
                        'audio_url' => $_POST['audio_url'] ?? '',
                        'transcricao' => $_POST['transcricao'] ?? '',
                        'resposta_correta' => $_POST['resposta_audio_correta'] ?? ''
                    ];
                } elseif ($tipo_exercicio === 'listening') {
                    if (empty($_POST['frase_listening']) || empty($_POST['listening_opcao1']) || empty($_POST['listening_opcao2']) || !isset($_POST['listening_alt_correta'])) {
                        $mensagem = '<div class="alert alert-danger">Frase, pelo menos 2 opções e a indicação da resposta correta são obrigatórios para listening.</div>';
                    } else {
                        try {
                            $listeningController = new ListeningController();
                            
                            // Gerar áudio automaticamente
                            $audio_url = $listeningController->gerarAudio(
                                $_POST['frase_listening'], 
                                $_POST['idioma_audio'] ?? 'en-us'
                            );
                            
                            // Preparar opções
                            $opcoes = [
                                trim($_POST['listening_opcao1']),
                                trim($_POST['listening_opcao2'])
                            ];
                            
                            if (!empty($_POST['listening_opcao3'])) $opcoes[] = trim($_POST['listening_opcao3']);
                            if (!empty($_POST['listening_opcao4'])) $opcoes[] = trim($_POST['listening_opcao4']);
                            
                            $resposta_correta_index = (int)($_POST['listening_alt_correta'] ?? 0);
                            
                            $conteudo_array = [
                                'frase_original' => $_POST['frase_listening'],
                                'audio_url' => $audio_url,
                                'opcoes' => $opcoes,
                                'resposta_correta' => $resposta_correta_index,
                                'explicacao' => $_POST['explicacao_listening'] ?? ''
                            ];
                            
                        } catch (Exception $e) {
                            $mensagem = '<div class="alert alert-danger">Erro ao gerar áudio ou salvar: ' . $e->getMessage() . '</div>';
                        }
                    }
                }
                
                if (isset($conteudo_array)) {
                    $conteudo = json_encode($conteudo_array, JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'especial':
                if (empty($_POST['link_video']) || empty($_POST['pergunta_extra'])) {
                    $mensagem = '<div class="alert alert-danger">O Link do Vídeo/Áudio e a Pergunta Extra são obrigatórios para este tipo de exercício.</div>';
                } else {
                    $conteudo_array = [
                        'link_video' => $_POST['link_video'],
                        'pergunta_extra' => $_POST['pergunta_extra']
                    ];
                    $conteudo = json_encode($conteudo_array, JSON_UNESCAPED_UNICODE);
                }
                break;
                
            case 'quiz':
                if (empty($_POST['quiz_id'])) {
                    $mensagem = '<div class="alert alert-danger">O ID do Quiz é obrigatório para este tipo de exercício.</div>';
                } else {
                    $conteudo_array = [
                        'quiz_id' => $_POST['quiz_id']
                    ];
                    $conteudo = json_encode($conteudo_array, JSON_UNESCAPED_UNICODE);
                }
                break;
        }

        // LÓGICA DE INSERÇÃO CORRIGIDA - USANDO OS MODELS ESPECÍFICOS
        if (empty($mensagem) && isset($conteudo)) {
            switch ($tipo_exercicio) {
                case 'fala':
                    // Usar SpeechExerciseModel para exercícios de fala
                    $speechModel = new SpeechExerciseModel($database);
                    if ($speechModel->create($caminho_id, $bloco_id, $ordem, $pergunta, $conteudo)) {
                        $mensagem = '<div class="alert alert-success">Exercício de Fala adicionado com sucesso!</div>';
                        $sucesso = true;
                    } else {
                        $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício de fala.</div>';
                    }
                    break;
                    
                case 'listening':
                    // Usar ListeningModel para exercícios de listening
                    if ($listeningModel->create($caminho_id, $bloco_id, $ordem, $pergunta, $conteudo)) {
                        $mensagem = '<div class="alert alert-success">Exercício de Listening adicionado com sucesso!</div>';
                        $sucesso = true;
                    } else {
                        $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício de listening.</div>';
                    }
                    break;
                    
                default:
                    // Para outros tipos, usar ExercicioModel
                    if ($exercicioModel->create($caminho_id, $bloco_id, $ordem, $tipo_exercicio, $pergunta, $conteudo)) {
                        $mensagem = '<div class="alert alert-success">Exercício adicionado com sucesso!</div>';
                        $sucesso = true;
                    } else {
                        $mensagem = '<div class="alert alert-danger">Erro ao adicionar exercício.</div>';
                    }
                    break;
            }
        }
    }
}

// Limpar campos do formulário em caso de sucesso
$limpar_campos = $sucesso ?? false;

// Variáveis para pré-preencher o formulário em caso de erro
$post_caminho_id = $limpar_campos ? '' : ($_POST["caminho_id"] ?? '');
$post_bloco_id = $limpar_campos ? '' : ($_POST["bloco_id"] ?? '');
$post_ordem = $limpar_campos ? '' : ($_POST["ordem"] ?? '');
$post_tipo = $limpar_campos ? 'normal' : ($_POST["tipo"] ?? "normal");
$post_tipo_exercicio = $limpar_campos ? 'multipla_escolha' : ($_POST["tipo_exercicio"] ?? "multipla_escolha");
$post_pergunta = $limpar_campos ? '' : ($_POST["pergunta"] ?? '');

// CAMPOS ESPECÍFICOS PARA LISTENING
$post_frase_listening = $limpar_campos ? '' : ($_POST["frase_listening"] ?? '');
$post_idioma_audio = $limpar_campos ? 'en-us' : ($_POST["idioma_audio"] ?? 'en-us');
$post_listening_opcao1 = $limpar_campos ? '' : ($_POST["listening_opcao1"] ?? '');
$post_listening_opcao2 = $limpar_campos ? '' : ($_POST["listening_opcao2"] ?? '');
$post_listening_opcao3 = $limpar_campos ? '' : ($_POST["listening_opcao3"] ?? '');
$post_listening_opcao4 = $limpar_campos ? '' : ($_POST["listening_opcao4"] ?? '');
$post_listening_alt_correta = $limpar_campos ? '0' : ($_POST['listening_alt_correta'] ?? '0');
$post_explicacao_listening = $limpar_campos ? '' : ($_POST["explicacao_listening"] ?? '');

// Campos específicos para cada tipo de exercício
$post_alt_texto = $limpar_campos ? [] : ($_POST["alt_texto"] ?? []);
$post_alt_correta = $limpar_campos ? null : ($_POST["alt_correta"] ?? null);
$post_explicacao = $limpar_campos ? '' : ($_POST["explicacao"] ?? '');
$post_resposta_esperada = $limpar_campos ? '' : ($_POST["resposta_esperada"] ?? '');
$post_alternativas_aceitas = $limpar_campos ? '' : ($_POST["alternativas_aceitas"] ?? '');
$post_dica_texto = $limpar_campos ? '' : ($_POST["dica_texto"] ?? '');
$post_frase_completar = $limpar_campos ? '' : ($_POST["frase_completar"] ?? '');
$post_resposta_completar = $limpar_campos ? '' : ($_POST["resposta_completar"] ?? '');
$post_alternativas_completar = $limpar_campos ? '' : ($_POST["alternativas_completar"] ?? '');
$post_dica_completar = $limpar_campos ? '' : ($_POST["dica_completar"] ?? '');
$post_placeholder_completar = $limpar_campos ? 'Digite sua resposta...' : ($_POST["placeholder_completar"] ?? 'Digite sua resposta...');
$post_frase_esperada = $limpar_campos ? '' : ($_POST["frase_esperada"] ?? '');
$post_pronuncia_fonetica = $limpar_campos ? '' : ($_POST["pronuncia_fonetica"] ?? '');
$post_palavras_chave = $limpar_campos ? '' : ($_POST["palavras_chave"] ?? '');
$post_audio_url = $limpar_campos ? '' : ($_POST["audio_url"] ?? '');
$post_transcricao = $limpar_campos ? '' : ($_POST["transcricao"] ?? '');
$post_resposta_audio_correta = $limpar_campos ? '' : ($_POST["resposta_audio_correta"] ?? '');
$post_link_video = $limpar_campos ? '' : ($_POST["link_video"] ?? '');
$post_pergunta_extra = $limpar_campos ? '' : ($_POST["pergunta_extra"] ?? '');
$post_quiz_id = $limpar_campos ? '' : ($_POST["quiz_id"] ?? '');

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

    /* Cartões de Estatísticas Melhorados */
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

    /* Barra de Navegação */
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
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        /* Estilos para foto de perfil no sidebar */
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

        /* Remove o ícone padrão quando há foto */
        .profile-avatar-sidebar:has(img) i {
            display: none;
        }

        /* Estilos para nome e email com quebra de texto */
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

        /* Ajuste do conteúdo principal para não ficar por baixo do sidebar */
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

  /* Ajuste do conteúdo principal para não ficar por baixo do sidebar */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

    /* Botões Melhorados */
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

    /* BOTÕES DE AÇÕES RÁPIDAS - ESTILO MINIMALISTA */
    .acoes-rapidas-btn {
        background: rgba(255, 255, 255, 0.1);
        color: #6a0dad;
        border: 2px solid #6a0dad;
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-transform: none;
        font-size: 0.9rem;
        min-width: 200px;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        text-decoration: none;
        cursor: pointer;
    }

    /* Efeito hover sutil */
    .acoes-rapidas-btn:hover {
        background: #6a0dad;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
        border-color: #6a0dad;
    }

    .acoes-rapidas-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(106, 13, 173, 0.2);
    }

    /* Botão Adicionar - Verde sutil */
    .acoes-rapidas-btn.adicionar {
        color: #6a0dad;
        border-color: #6a0dad;
        background: #6a0dad22;
    }

    .acoes-rapidas-btn.adicionar:hover {
        background: #8212d3ff;
        color: var(--amarelo-detalhe);
        box-shadow: 0 4px 12px #6a0dad46;
    }

    /* Botão Ver - Azul sutil */
    .acoes-rapidas-btn.ver {
        color: #2563eb;
        border-color: #2563eb;
        background: rgba(37, 99, 235, 0.05);
    }

    .acoes-rapidas-btn.ver:hover {
        background: #2563eb;
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    /* Botão Gerenciar - Roxo sutil */
    .acoes-rapidas-btn.gerenciar {
        color: #7c3aed;
        border-color: #7c3aed;
        background: rgba(124, 58, 237, 0.05);
    }

    .acoes-rapidas-btn.gerenciar:hover {
        background: #7c3aed;
        color: white;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
    }

    /* Estados desabilitados */
    .acoes-rapidas-btn:disabled {
        color: #9ca3af;
        border-color: #d1d5db;
        background: rgba(156, 163, 175, 0.05);
        transform: none;
        box-shadow: none;
        cursor: not-allowed;
    }

    .acoes-rapidas-btn:disabled:hover {
        transform: none;
        box-shadow: none;
        background: rgba(156, 163, 175, 0.05);
        color: #9ca3af;
        border-color: #d1d5db;
    }

    /* Ícones menores */
    .acoes-rapidas-btn i {
        font-size: 1rem;
        transition: transform 0.3s ease;
    }

    .acoes-rapidas-btn:hover i {
        transform: scale(1.1);
    }

    /* Container minimalista */
    .acoes-rapidas-container {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(106, 13, 173, 0.1);
        backdrop-filter: blur(15px);
    }

    .acoes-rapidas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    /* Dropdown minimalista */
    .dropdown .acoes-rapidas-btn.dropdown-toggle::after {
        margin-left: 6px;
        transition: transform 0.3s ease;
    }

    .dropdown.show .acoes-rapidas-btn.dropdown-toggle::after {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        border-radius: 12px;
        border: 1px solid rgba(106, 13, 173, 0.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        background: rgba(255, 255, 255, 0.95);
        padding: 8px;
        margin-top: 5px !important;
    }

    .dropdown-item {
        border-radius: 8px;
        padding: 10px 12px;
        transition: all 0.2s ease;
        font-weight: 500;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 2px 0;
        font-size: 0.85rem;
    }

    .dropdown-item:hover {
        background: rgba(106, 13, 173, 0.08);
        color: #6a0dad;
        transform: none;
        box-shadow: none;
    }

    .dropdown-item i {
        font-size: 0.9rem;
        width: 16px;
        text-align: center;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .acoes-rapidas-btn {
            min-width: 100%;
            padding: 10px 16px;
            font-size: 0.85rem;
        }
        
        .acoes-rapidas-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .acoes-rapidas-container {
            padding: 16px;
        }
        
        .acoes-rapidas-btn i {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 576px) {
        .acoes-rapidas-btn {
            padding: 8px 14px;
        }
    }
    /* Efeitos de brilho adicionais */
    .glow-effect {
        position: relative;
    }

    .glow-effect::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, var(--roxo-principal), var(--amarelo-detalhe), var(--roxo-claro));
        border-radius: inherit;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .glow-effect:hover::after {
        opacity: 0.3;
    }

    /* Efeito de profundidade */
    .acoes-rapidas-btn {
        position: relative;
        z-index: 1;
    }

    .acoes-rapidas-btn:hover {
        z-index: 2;
    }
    .bloco-actions {
        opacity: 1 !important;
        transform: translateY(0) !important;
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .btn-acao {
        border-radius: 8px;
        padding: 6px 12px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-width: auto;
    }

    .btn-acao.btn-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
    }

    .btn-acao.primary {
        background: var(--gradiente-roxo);
        color: white;
    }

    .btn-acao.primary:hover {
        background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.4);
    }

    .btn-acao.warning {
        background: var(--gradiente-amarelo);
        color: var(--preto-texto);
    }

    .btn-acao.warning:hover {
        background: linear-gradient(135deg, #e6c200 0%, #cc9900 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
    }

    .btn-acao.danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .btn-acao.danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    /* Cartões de bloco melhorados */
    .bloco-card {
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        border-left: 5px solid var(--roxo-principal);
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        height: 100%;
        background: var(--branco);
        border: 2px solid rgba(106, 13, 173, 0.1);
    }

    .bloco-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 35px rgba(106, 13, 173, 0.25);
        border-left-color: var(--amarelo-detalhe);
        border-color: rgba(106, 13, 173, 0.2);
    }

    .stats-badge {
        font-size: 0.8rem;
        background: var(--gradiente-roxo);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Cards de formulário e listas */
    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        background: var(--branco);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    /* Formulários */
    .form-control, .form-select {
        border-radius: 12px;
        border: 2px solid var(--cinza-medio);
        padding: 12px 18px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.3rem rgba(106, 13, 173, 0.2);
        transform: translateY(-2px);
    }

    /* Alertas */
    .alert {
        border-radius: 15px;
        border: none;
        padding: 18px 22px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        border-left: 5px solid;
        backdrop-filter: blur(10px);
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(32, 201, 151, 0.1));
        color: #155724;
        border-left-color: #28a745;
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(232, 62, 140, 0.1));
        color: #721c24;
        border-left-color: #dc3545;
    }

    .alert-warning {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(253, 126, 20, 0.1));
        color: #856404;
        border-left-color: #ffc107;
    }

    /* Badges */
    .badge {
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 0.8rem;
    }

    /* Títulos e textos */
    h2 {
        color: var(--roxo-principal);
        font-weight: 800;
        margin-bottom: 15px;
        font-size: 2.2rem;
        text-shadow: 0 2px 4px rgba(106, 13, 173, 0.1);
    }

    .text-muted {
        color: #6c757d !important;
    }

    /* Dropdown */
    .dropdown-menu {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        padding: 10px 0;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }

    .dropdown-item {
        padding: 10px 20px;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        color: white;
        transform: translateX(5px);
    }

    /* Animações adicionais para stats-card */
    .stats-card:nth-child(1) { animation-delay: 0.1s; }
    .stats-card:nth-child(2) { animation-delay: 0.2s; }
    .stats-card:nth-child(3) { animation-delay: 0.3s; }
    .stats-card:nth-child(4) { animation-delay: 0.4s; }

    @media (max-width: 768px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
        }
        .main-content {
            margin-left: 0;
            padding: 15px;
        }
        .stats-card h3 {
            font-size: 2.2rem;
        }
        .navbar-brand .logo-header {
            height: 60px;
        }
        .btn {
            padding: 10px 20px;
            font-size: 0.85rem;
        }
        .acoes-rapidas-btn {
            min-width: 160px;
            padding: 12px 16px;
        }
        .bloco-actions {
            flex-direction: column;
            gap: 8px;
        }
        .btn-acao {
            width: 100%;
            justify-content: center;
        }
    }

    /* Container para ações rápidas */
    .acoes-rapidas-container {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.8));
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(106, 13, 173, 0.1);
        backdrop-filter: blur(10px);
    }

    .acoes-rapidas-container .card-header {
        background: linear-gradient(135deg, #17a2b8, #6f42c1);
    }

    .acoes-rapidas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    /* Efeitos de brilho adicionais */
    .glow-effect {
        position: relative;
    }

    .glow-effect::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, var(--roxo-principal), var(--amarelo-detalhe), var(--roxo-claro));
        border-radius: inherit;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .glow-effect:hover::after {
        opacity: 0.3;
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

    /* Estilos específicos para o formulário de exercícios */
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

                        <!-- Campo Bloco (será carregado via AJAX) -->
                        <div class="mb-3">
                            <label for="bloco_id" class="form-label">Selecionar Bloco *</label>
                            <select class="form-select" id="bloco_id" name="bloco_id" required>
                                <option value="">-- Primeiro selecione um caminho --</option>
                            </select>
                        </div>

                        <!-- Se houver um caminho selecionado no POST, pré-carregar os blocos -->
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
                        
                        <!-- Campo Subtipo - ATUALIZADO COM LISTENING -->
                        <div class="mb-3">
                            <label for="tipo_exercicio" class="form-label">Subtipo do Exercício</label>
                            <select class="form-select" id="tipo_exercicio" name="tipo_exercicio" required>
                                <option value="multipla_escolha" <?php echo ($post_tipo_exercicio == "multipla_escolha") ? "selected" : ""; ?>>Múltipla Escolha</option>
                                <option value="texto_livre" <?php echo ($post_tipo_exercicio == "texto_livre") ? "selected" : ""; ?>>Texto Livre (Completar)</option>
                                <option value="completar" <?php echo ($post_tipo_exercicio == "completar") ? "selected" : ""; ?>>Completar Frase</option>
                                <option value="fala" <?php echo ($post_tipo_exercicio == "fala") ? "selected" : ""; ?>>Exercício de Fala</option>
                                <option value="listening" <?php echo ($post_tipo_exercicio == "listening") ? "selected" : ""; ?>>Exercício de Listening</option>
                                <option value="audicao" <?php echo ($post_tipo_exercicio == "audicao") ? "selected" : ""; ?>>Exercício de Audição</option>
                            </select>
                        </div>

                        <!-- Campo Pergunta -->
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" required><?php echo htmlspecialchars($post_pergunta); ?></textarea>
                        </div>

                        <!-- Campos Dinâmicos - ADICIONADA SEÇÃO LISTENING -->
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
                                                // Adiciona uma alternativa vazia por padrão se não houver POST
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
                                    <h5>Configuração - Exercício de Fala</h5>
                                    <div class="mb-3">
                                        <label for="frase_esperada" class="form-label">Frase Esperada para Fala *</label>
                                        <textarea class="form-control" id="frase_esperada" name="frase_esperada" rows="2"><?php echo htmlspecialchars($post_frase_esperada); ?></textarea>
                                        <div class="form-text">A frase que o usuário deve falar.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="pronuncia_fonetica" class="form-label">Pronúncia Fonética (Opcional)</label>
                                        <input type="text" class="form-control" id="pronuncia_fonetica" name="pronuncia_fonetica" value="<?php echo htmlspecialchars($post_pronuncia_fonetica); ?>">
                                        <div class="form-text">Para ajudar na avaliação da pronúncia.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="palavras_chave" class="form-label">Palavras-chave (separadas por vírgula, opcional)</label>
                                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php echo htmlspecialchars($post_palavras_chave); ?>">
                                        <div class="form-text">Palavras importantes para a correção.</div>
                                    </div>
                                </div>

                                <!-- NOVO: Campos para Listening -->
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
        selectBloco.innerHTML = '<option value="">-- Selecione um bloco --</option>'; // Limpa as opções anteriores

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
    
    // JavaScript ATUALIZADO para incluir listening
    document.addEventListener("DOMContentLoaded", function() {
        const tipoSelect = document.getElementById("tipo");
        const tipoExercicioSelect = document.getElementById("tipo_exercicio");
        const camposNormal = document.getElementById("campos-normal");
        const camposEspecial = document.getElementById("campos-especial");
        const camposQuiz = document.getElementById("campos-quiz");
        
        // Adicionar referência aos campos de listening
        const camposMultipla = document.getElementById("campos-multipla");
        const camposTexto = document.getElementById("campos-texto");
        const camposCompletar = document.getElementById("campos-completar");
        const camposFala = document.getElementById("campos-fala");
        const camposAudicao = document.getElementById("campos-audicao");
        const camposListening = document.getElementById("campos-listening");

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

            if (tipoSelect.value === "normal") {
                camposNormal.style.display = "block";
                
                // Mostrar subcampo baseado no tipo de exercício
                switch (tipoExercicioSelect.value) {
                    case "multipla_escolha":
                        camposMultipla.style.display = "block";
                        break;
                    case "texto_livre":
                        camposTexto.style.display = "block";
                        break;
                    case "completar":
                        camposCompletar.style.display = "block";
                        break;
                    case "fala":
                        camposFala.style.display = "block";
                        break;
                    case "listening":
                        camposListening.style.display = "block";
                        break;
                    case "audicao":
                        camposAudicao.style.display = "block";
                        break;
                }
            } else if (tipoSelect.value === "especial") {
                camposEspecial.style.display = "block";
            } else if (tipoSelect.value === "quiz") {
                camposQuiz.style.display = "block";
            }
        }

        tipoSelect.addEventListener("change", atualizarCampos);
        tipoExercicioSelect.addEventListener("change", atualizarCampos);
        
        // Inicializar
        atualizarCampos();

        // Carregar blocos se um caminho já estiver selecionado (útil após POST com erro)
        const initialCaminhoId = document.getElementById('caminho_id').value;
        if (initialCaminhoId) {
            carregarBlocos(initialCaminhoId, <?php echo !empty($post_bloco_id) ? $post_bloco_id : 'null'; ?>);
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
            <div class=\"input-group-text\">
                <input type=\"radio\" name=\"alt_correta\" value=\"${index}\" title=\"Marcar como correta\">
            </div>
            <button type=\"button\" class=\"btn btn-outline-danger\" onclick=\"this.parentElement.remove()\">×</button>
        `;
        container.appendChild(novaAlternativa);
    }

    // Função para testar áudio
    async function testarAudio() {
        const frase = document.getElementById('frase_listening').value;
        const idioma = document.getElementById('idioma_audio').value;
        
        if (!frase) {
            alert('Digite uma frase primeiro');
            return;
        }
        
        const preview = document.getElementById('audioPreview');
        preview.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p>Gerando áudio...</p>';
        
        try {
            const formData = new FormData();
            formData.append('frase', frase);
            formData.append('idioma', idioma);
            formData.append('testar_audio', 'true');
            
            // CORREÇÃO: Chamar o novo endpoint dedicado
            const response = await fetch('../controller/gerar_audio_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Adiciona um timestamp para evitar problemas de cache do navegador
                preview.innerHTML = `
                    <audio controls autoplay class="w-100">
                        <source src="${data.audio_url}?t=${new Date().getTime()}" type="audio/mpeg">
                        Seu navegador não suporta o elemento de áudio.
                    </audio>
                    <p class="mt-2 text-success"><small><i class="fas fa-check me-1"></i>Áudio gerado com sucesso!</small></p>
                `;
            } else {
                preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Não foi possível gerar o áudio. Tente novamente. (${data.message})</p>`;
            }
        } catch (error) {
            preview.innerHTML = `<p class="text-danger"><i class="fas fa-times me-1"></i>Erro de comunicação ao gerar áudio.</p>`;
        }
    }

    // Gerar áudio automaticamente quando a frase for alterada
    let timeoutId;
    document.getElementById('frase_listening')?.addEventListener('input', function() {
         clearTimeout(timeoutId);
        timeoutId = setTimeout( () => {
            if (this.value.length > 5) {
                testarAudio();
            }
        }, 1500);
    });

    // Debug helper para verificar se os campos estão sendo preenchidos
    function debugListening() {
        const frase = document.getElementById('frase_listening').value;
        const opcao1 = document.querySelector('input[name="listening_opcao1"]').value;
        const opcao2 = document.querySelector('input[name="listening_opcao2"]').value;
        const respostaCorreta = document.querySelector('input[name="listening_alt_correta"]:checked');
        
        console.log('Debug Listening:');
        console.log('Frase:', frase);
        console.log('Opção 1:', opcao1);
        console.log('Opção 2:', opcao2);
        console.log('Resposta Correta selecionada:', respostaCorreta ? respostaCorreta.value : 'Nenhuma');
        
        // Verificar todas as opções de resposta
        const todasOpcoes = document.querySelectorAll('input[name="listening_alt_correta"]');
        todasOpcoes.forEach((opcao, index) => {
            console.log(`Opção ${index}:`, opcao.checked ? '✓' : '✗', '- Valor:', opcao.value);
        });
    }

    // Adicionar evento de debug ao formulário (opcional)
    document.querySelector('form').addEventListener('submit', function(e) {
        if (document.getElementById('tipo_exercicio').value === 'listening') {
            debugListening();
        }
    });

    // Garantir que pelo menos uma opção esteja selecionada
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="listening_alt_correta"]');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Opção selecionada:', this.value);
            });
        });
    });

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>