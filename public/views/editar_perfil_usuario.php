<?php
session_start();
include __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

$id_usuario = $_SESSION['id_usuario'];
$mensagem = '';
$tipo_mensagem = '';

// Buscar fotos disponíveis na pasta img_fotos
$fotos_disponiveis = [];
$pasta_fotos = __DIR__ . '/../img_fotos/';
if (is_dir($pasta_fotos)) {
    $arquivos = scandir($pasta_fotos);
    foreach ($arquivos as $arquivo) {
        if (in_array(strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
            $fotos_disponiveis[] = $arquivo;
        }
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Atualização do nome de usuário
    if (isset($_POST['confirmar_update'])) {
        $nome_usuario_novo = $_POST['nome_usuario'];
        $email_novo = $_POST['email'];

        // Verificar duplicação de nome de usuário
        $sql_check = "SELECT id FROM usuarios WHERE nome = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $nome_usuario_novo, $id_usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensagem = "Erro: O nome de usuário '{$nome_usuario_novo}' já está em uso.";
            $tipo_mensagem = 'danger';
        } else {
            // Atualiza o nome de usuário e email
            $sql_update = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssi", $nome_usuario_novo, $email_novo, $id_usuario);

            if ($stmt_update->execute()) {
                $mensagem = "Perfil atualizado com sucesso!";
                $tipo_mensagem = 'success';
                $_SESSION['nome_usuario'] = $nome_usuario_novo;
            } else {
                $mensagem = "Ocorreu um erro inesperado ao atualizar o perfil.";
                $tipo_mensagem = 'danger';
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
    
    // Seleção de foto predefinida
    if (isset($_POST['selecionar_foto_predefinida']) && !empty($_POST['foto_predefinida'])) {
        $foto_selecionada = $_POST['foto_predefinida'];
        
        // Verificar se a foto existe
        if (in_array($foto_selecionada, $fotos_disponiveis)) {
            $sql_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
            $stmt_foto = $conn->prepare($sql_foto);
            $caminhoRelativo = 'public/img_fotos/' . $foto_selecionada;
            $stmt_foto->bind_param("si", $caminhoRelativo, $id_usuario);
            
            if ($stmt_foto->execute()) {
                $mensagem = "Foto de perfil atualizada com sucesso!";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "Erro ao atualizar foto de perfil.";
                $tipo_mensagem = 'danger';
            }
            $stmt_foto->close();
        } else {
            $mensagem = "Foto selecionada não é válida.";
            $tipo_mensagem = 'danger';
        }
    }
    
    // Upload de foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto_perfil'];
        
        // Validações
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($foto['type'], $tiposPermitidos)) {
            $mensagem = "Erro: Formato de arquivo não permitido. Use JPG, PNG ou GIF.";
            $tipo_mensagem = 'danger';
        } elseif ($foto['size'] > $tamanhoMaximo) {
            $mensagem = "Erro: A imagem deve ter no máximo 2MB.";
            $tipo_mensagem = 'danger';
        } else {
            // Cria diretório se não existir
            $diretorioFotos = __DIR__ . '/../../uploads/perfis/';
            if (!is_dir($diretorioFotos)) {
                mkdir($diretorioFotos, 0777, true);
            }
            
            // Gera nome único para o arquivo
            $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $nomeArquivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extensao;
            $caminhoCompleto = $diretorioFotos . $nomeArquivo;
            
            if (move_uploaded_file($foto['tmp_name'], $caminhoCompleto)) {
                // Atualiza no banco de dados
                $sql_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
                $stmt_foto = $conn->prepare($sql_foto);
                $caminhoRelativo = 'uploads/perfis/' . $nomeArquivo;
                $stmt_foto->bind_param("si", $caminhoRelativo, $id_usuario);
                
                if ($stmt_foto->execute()) {
                    $mensagem = "Foto de perfil atualizada com sucesso!";
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = "Erro ao salvar informações da foto no banco de dados.";
                    $tipo_mensagem = 'danger';
                    unlink($caminhoCompleto);
                }
                $stmt_foto->close();
            } else {
                $mensagem = "Erro ao fazer upload da foto.";
                $tipo_mensagem = 'danger';
            }
        }
    }
    
    // Remover foto de perfil
    if (isset($_POST['remover_foto'])) {
        // Atualiza no banco de dados (apenas limpa o campo)
        $sql_remove_foto = "UPDATE usuarios SET foto_perfil = NULL WHERE id = ?";
        $stmt_remove_foto = $conn->prepare($sql_remove_foto);
        $stmt_remove_foto->bind_param("i", $id_usuario);
        
        if ($stmt_remove_foto->execute()) {
            $mensagem = "Foto de perfil removida com sucesso!";
            $tipo_mensagem = 'success';
        } else {
            $mensagem = "Erro ao remover foto do perfil.";
            $tipo_mensagem = 'danger';
        }
        $stmt_remove_foto->close();
    }
}

// Busca os dados do usuário para preencher o formulário
$sql_usuario = "SELECT nome, email, foto_perfil FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$usuario = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

// Define a foto atual (se existir)
$foto_atual = !empty($usuario['foto_perfil']) ? '../../' . $usuario['foto_perfil'] : null;

// Buscar estatísticas do usuário
$sql_idiomas_usuario = "SELECT COUNT(DISTINCT idioma) as total_idiomas FROM progresso_usuario WHERE id_usuario = ?";
$stmt_idiomas = $conn->prepare($sql_idiomas_usuario);
$stmt_idiomas->bind_param("i", $id_usuario);
$stmt_idiomas->execute();
$result_idiomas = $stmt_idiomas->get_result();
$stats_idiomas = $result_idiomas->fetch_assoc()['total_idiomas'];
$stmt_idiomas->close();

// Calcular dias ativos (simulação baseada na data de hoje)
$dias_ativos = rand(5, 30); // Simulação - em produção usar dados reais

// Data de registro fixa no código
$meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$data_atual = getdate();
$data_registro_codigo = $data_atual['mday'] . ' de ' . $meses[$data_atual['mon'] - 1] . ' de ' . $data_atual['year'];

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Plataforma de Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --roxo-claro: #8a2be2;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --cinza-claro: #f8f9fa;
            --cinza-medio: #e9ecef;
            --cinza-escuro: #6c757d;
            --preto-texto: #212529;
            --verde-sucesso: #28a745;
            --azul-info: #17a2b8;
            --laranja-alerta: #fd7e14;
            --shadow-light: 0 2px 10px rgba(106, 13, 173, 0.1);
            --shadow-medium: 0 8px 25px rgba(106, 13, 173, 0.15);
            --shadow-heavy: 0 15px 35px rgba(106, 13, 173, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, var(--cinza-claro) 0%, #e3f2fd 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Navbar igual ao admin */
        .navbar {
            background: transparent !important;
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
        }

        .settings-icon:hover {
            color: var(--roxo-principal) !important;
            transform: rotate(90deg);
        }

        /* Container principal */
        .profile-container { 
            margin: 40px auto; 
            padding: 0 20px;
            width: 80%;
        }

        /* Breadcrumb */
        .breadcrumb-container {
            background: var(--branco);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-light);
        }

        .breadcrumb {
            margin: 0;
            background: none;
        }

        .breadcrumb-item a {
            color: var(--roxo-principal);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-item a:hover {
            color: var(--roxo-escuro);
        }

        /* Card principal - LAYOUT HORIZONTAL */
        .main-card { 
            border: none; 
            border-radius: 20px; 
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            background: var(--branco);
            min-height: 600px;
        }

        .card-header { 
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%); 
            color: var(--branco); 
            font-size: 1.2rem; 
            font-weight: 600; 
            padding: 18px 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .card-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--amarelo-detalhe);
            border-radius: 2px;
        }

        .card-header .card-title-left {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--branco);
            font-size: 1.35rem;
            font-weight: 800;
        }

        .card-header .card-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-voltar-header {
            padding: 10px 18px;
            border-radius: 10px;
            background: rgba(255,255,255,0.12);
            color: var(--branco);
            border: 1px solid rgba(255,255,255,0.08);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            min-height: 44px;
        }

        .btn-voltar-header:hover {
            background: rgba(255,255,255,0.18);
            color: var(--branco);
        }

        /* LAYOUT HORIZONTAL - Container principal */
        .horizontal-layout {
            display: flex;
            min-height: 500px;
        }

        /* COLUNA ESQUERDA - Perfil e Estatísticas */
        .left-column {
            flex: 0 0 400px;
            background: linear-gradient(180deg, var(--branco) 0%, var(--cinza-claro) 100%);
            border-right: 2px solid var(--cinza-medio);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* COLUNA DIREITA - Formulário */
        .right-column {
            flex: 1;
            padding: 40px;
            background: var(--branco);
        }

        /* Seção do avatar - HORIZONTAL */
        .profile-avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 5px solid var(--roxo-principal);
            background: linear-gradient(135deg, var(--roxo-claro), var(--roxo-principal));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3.5rem;
            color: var(--branco);
            box-shadow: var(--shadow-medium);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            cursor: pointer;
            overflow: hidden;
        }

        .profile-avatar.has-photo {
            background: none;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-avatar:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: var(--shadow-heavy);
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-avatar:hover .avatar-overlay {
            opacity: 1;
        }

        .profile-info {
            margin-bottom: 20px;
        }

        .profile-info h4 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--preto-texto);
        }

        .profile-role {
            background: var(--roxo-principal);
            color: var(--branco);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 15px;
        }

        /* Botões de foto - ESTILIZADOS */
        .photo-buttons-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn-photo {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
        }

        .btn-alterar-foto {
            background: linear-gradient(135deg, var(--azul-info), #0dcaf0);
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.3);
        }

        .btn-alterar-foto:hover {
            background: linear-gradient(135deg, #0dcaf0, var(--azul-info));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 202, 240, 0.4);
            color: var(--branco);
        }

        .btn-remover-foto {
            background: linear-gradient(135deg, var(--laranja-alerta), #fd5643ff);
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);
        }

        .btn-remover-foto:hover {
            background: linear-gradient(135deg, #d54738ff, var(--laranja-alerta));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(253, 126, 20, 0.4);
            color: var(--branco);
        }

        /* Informações adicionais do perfil */
        .profile-details {
            background: var(--branco);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-light);
        }

        .profile-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .profile-detail-item:last-child {
            margin-bottom: 0;
        }

        .profile-detail-item i {
            width: 20px;
            color: var(--roxo-principal);
            margin-right: 10px;
        }

        /* Estatísticas do perfil - HORIZONTAL */
        .profile-stats {
            background: var(--branco);
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow-light);
        }

        .stats-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1rem;
            font-weight: 600;
            color: var(--preto-texto);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px 10px;
            background: var(--cinza-claro);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--roxo-principal);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--cinza-escuro);
        }

        /* Formulário - COLUNA DIREITA */
        .form-section {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .form-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--preto-texto);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-content {
            flex: 1;
        }

        .form-group-enhanced {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label-enhanced {
            font-weight: 600;
            color: var(--preto-texto);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .form-control-enhanced {
            border: 2px solid var(--cinza-medio);
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--branco);
            width: 100%;
        }

        .form-control-enhanced:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cinza-escuro);
            margin-top: 15px;
        }

        /* Botões aprimorados */
        .btn-enhanced {
            padding: 15px 35px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-enhanced {
            background-color: #4c087c;
            border: 2px solid #4c087c;
            color: #ffffffff;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            box-shadow: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: inherit;
        }

        .btn-primary-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transform: skewX(-25deg);
            transition: left 0.8s ease;
            pointer-events: none;
        }

        .btn-primary-enhanced:hover::before {
            left: 150%;
        }

        .btn-primary-enhanced:hover {
            background: linear-gradient(135deg, #4c087c 0%, #8a2be2 100%);
            border-color: #8a2be2;
            color: white;
            box-shadow: 0 6px 20px rgba(36, 31, 194, 0.4);
            transform: translateY(-3px) scale(1.05);
        }

        .btn-primary-enhanced:active {
            transform: translateY(-1px) scale(1.02);
            box-shadow: 0 4px 15px rgba(30, 26, 155, 0.5);
        }

        /* Container de navegação */
        .navigation-buttons-container {
            max-width: 1200px;
            margin: 25px auto 0;
            padding: 0 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            align-items: center;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }

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

        .btn-navigation {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            transition: background-color 0.18s ease, transform 0.12s ease, border-color 0.18s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            min-height: 48px;
            box-shadow: none;
            cursor: pointer;
            background: transparent;
            color: var(--roxo-principal);
            text-transform: none;
        }

        .btn-navigation:hover {
            transform: translateY(-1px);
            background: rgba(106, 13, 173, 0.06);
            box-shadow: none;
        }

        .btn-cancelar-alteracoes {
            background: transparent;
            border: 1px solid rgba(144, 144, 144, 0.12);
            color: var(--cinza-escuro);
            font-weight: 700;
            border-radius: 10px;
            text-transform: none;
            padding: 12px 20px;
            font-size: 1rem;
            min-height: 48px;
            width: 100%;
            border-color: rgba(17, 17, 17, 1);
        }

        .btn-cancelar-alteracoes:hover {
            background: rgba(144, 144, 144, 0.06);
            color: var(--cinza-escuro);
        }

        /* Estilos para fotos predefinidas */
        .foto-opcao-container {
            position: relative;
        }
        
        .foto-opcao {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .foto-opcao-label {
            border: 2px solid transparent;
            border-radius: 50%;
            padding: 2px;
            display: block;
            transition: all 0.3s ease;
        }
        
        .btn-check:checked + .foto-opcao-label {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.3);
            transform: scale(1.1);
        }
        
        .foto-opcao-label:hover {
            border-color: var(--roxo-claro);
            transform: scale(1.05);
        }
        
        /* Abas do modal */
        .nav-tabs .nav-link {
            color: var(--roxo-principal);
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--branco);
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .horizontal-layout {
                flex-direction: column;
            }
            .left-column {
                flex: none;
                border-right: none;
                border-bottom: 2px solid var(--cinza-medio);
            }
            .navigation-buttons-container {
                width: 100%;
                justify-content: center;
                gap: 12px;
            }
            .foto-opcao {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar igual ao admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil_usuario.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container fade-in-left">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="painel.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-user-edit me-1"></i>Editar Perfil
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Card Principal com Layout Horizontal -->
        <div class="card main-card fade-in-left">
            <div class="card-header">
                <div class="card-title-left">
                    <i class="fas fa-user-cog me-1"></i>
                    <span>Configurações do Perfil</span>
                </div>
                <div class="card-actions">
                    <a href="painel.php" class="btn btn-voltar-header">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel
                    </a>
                </div>
            </div>
            
            <div class="horizontal-layout">
                <!-- COLUNA ESQUERDA - Perfil e Estatísticas -->
                <div class="left-column fade-in-left">
                    <!-- Seção do Avatar e Info -->
                    <div class="profile-avatar-section">
                        <div class="profile-avatar <?= $foto_atual ? 'has-photo' : '' ?>" data-bs-toggle="modal" data-bs-target="#editPhotoModal">
                            <?php if ($foto_atual): ?>
                                <img src="<?= htmlspecialchars($foto_atual) ?>" alt="Foto de perfil">
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h4><?= htmlspecialchars($_SESSION['nome_usuario'] ?? $usuario['nome'] ?? 'Usuário') ?></h4>
                            <span class="profile-role">
                                <i class="fas fa-user me-1"></i>Estudante
                            </span>
                        </div>
                        
                        <!-- BOTÕES DE FOTO ESTILIZADOS -->
                        <div class="photo-buttons-container">
                            <button type="button" class="btn btn-photo btn-alterar-foto" data-bs-toggle="modal" data-bs-target="#editPhotoModal">
                                <i class="fas fa-camera me-1"></i>Alterar Foto
                            </button>
                            <?php if ($foto_atual): ?>
                            <button type="button" class="btn btn-photo btn-remover-foto" data-bs-toggle="modal" data-bs-target="#confirmRemovePhotoModal">
                                <i class="fas fa-trash me-1"></i>Remover Foto
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Detalhes do Perfil -->
                    <div class="profile-details">
                        <div class="profile-detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($usuario['email']) ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Membro desde <?= htmlspecialchars($data_registro_codigo) ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Brasil</span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-clock"></i>
                            <span>Último acesso: Hoje</span>
                        </div>
                    </div>

                    <!-- Estatísticas do Perfil -->
                    <div class="profile-stats">
                        <div class="stats-title">
                            <i class="fas fa-chart-line me-2"></i>Estatísticas
                        </div>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?= $stats_idiomas ?></div>
                                <div class="stat-label">Idiomas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $dias_ativos ?></div>
                                <div class="stat-label">Dias Ativo</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA - Formulário -->
                <div class="right-column fade-in-right">
                    <div class="form-section">
                        <div class="form-title">
                            <i class="fas fa-edit"></i>
                            Editar Informações
                        </div>

                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-enhanced">
                                <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= htmlspecialchars($mensagem) ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-content">
                            <!-- Formulário para atualizar dados -->
                            <form id="editForm" method="POST" action="editar_perfil_usuario.php">
                                <input type="hidden" name="confirmar_update" value="1">
                                
                                <div class="form-group-enhanced">
                                    <label for="nome_usuario" class="form-label-enhanced">
                                        <i class="fas fa-user"></i>Nome
                                    </label>
                                    <input type="text" class="form-control form-control-enhanced" id="nome_usuario" name="nome_usuario"
                                        value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required
                                        placeholder="Digite seu nome">
                                    <i class="fas fa-edit input-icon"></i>
                                </div>

                                <div class="form-group-enhanced">
                                    <label for="email" class="form-label-enhanced">
                                        <i class="fas fa-envelope"></i>Email
                                    </label>
                                    <input type="email" class="form-control form-control-enhanced" id="email" name="email" 
                                        value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required
                                        placeholder="Digite seu email">
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>

                                <!-- Botão de Atualizar -->
                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary-enhanced btn-enhanced w-100" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">
                                        <i class="fas fa-save me-2"></i>Salvar Alterações
                                    </button>
                                </div>

                                <!-- BOTÕES DE NAVEGAÇÃO -->
                                <div class="navigation-buttons-container">
                                    <button type="button" class="btn btn-navigation btn-cancelar-alteracoes" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">
                                        <i class="fas fa-times me-2"></i>Cancelar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modais iguais ao admin -->
    <!-- Modal de Confirmação para ATUALIZAR -->
    <div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmUpdateModalLabel">
                        <i class="fas fa-save me-2"></i>Confirmar Atualização
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center mb-3"><strong>Deseja atualizar alterações?</strong></p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nome será alterado para:</strong><br>
                        '<strong id="novoNomeUsuario"></strong>'
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta ação atualizará permanentemente suas informações de perfil.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmarUpdateBtn">
                        <i class="fas fa-check me-1"></i>Sim, Atualizar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação para CANCELAR -->
    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCancelModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancelar Edição
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center">Tem certeza de que deseja cancelar a edição?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        Todas as alterações não salvas serão perdidas permanentemente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-edit me-1"></i>Continuar Editando
                    </button>
                    <a href="painel.php" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Sim, Descartar Alterações
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Foto -->
    <div class="modal fade" id="editPhotoModal" tabindex="-1" aria-labelledby="editPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPhotoModalLabel">
                        <i class="fas fa-camera me-2"></i>Alterar Foto do Perfil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="profile-avatar mx-auto mb-3 <?= $foto_atual ? 'has-photo' : '' ?>" style="width: 120px; height: 120px; cursor: pointer;" id="currentAvatar">
                            <?php if ($foto_atual): ?>
                                <img src="<?= htmlspecialchars($foto_atual) ?>" alt="Foto atual" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="font-size: 2.5rem;"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted">Clique na imagem para visualizar</p>
                    </div>
                    
                    <!-- Abas para escolher tipo de foto -->
                    <ul class="nav nav-tabs" id="photoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="predefined-tab" data-bs-toggle="tab" data-bs-target="#predefined" type="button" role="tab">
                                <i class="fas fa-images me-2"></i>Fotos Predefinidas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                                <i class="fas fa-upload me-2"></i>Enviar Foto
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="photoTabContent">
                        <!-- Aba Fotos Predefinidas -->
                        <div class="tab-pane fade show active" id="predefined" role="tabpanel">
                            <form method="POST" action="editar_perfil_usuario.php">
                                <input type="hidden" name="selecionar_foto_predefinida" value="1">
                                <div class="mt-3">
                                    <?php if (!empty($fotos_disponiveis)): ?>
                                        <div class="row g-2">
                                            <?php foreach ($fotos_disponiveis as $foto): ?>
                                                <div class="col-4 col-md-3">
                                                    <div class="foto-opcao-container">
                                                        <input type="radio" class="btn-check" name="foto_predefinida" id="foto_<?= $foto; ?>" value="<?= $foto; ?>">
                                                        <label class="btn foto-opcao-label" for="foto_<?= $foto; ?>">
                                                            <img src="../img_fotos/<?= $foto; ?>" alt="Avatar" class="foto-opcao">
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <button type="submit" class="btn btn-primary-enhanced">
                                                <i class="fas fa-check me-1"></i>Usar Foto Selecionada
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Nenhuma foto predefinida disponível.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Aba Upload -->
                        <div class="tab-pane fade" id="upload" role="tabpanel">
                            <form id="photoUploadForm" method="POST" action="editar_perfil_usuario.php" enctype="multipart/form-data">
                                <div class="mt-3 mb-3">
                                    <label for="foto_perfil" class="form-label-enhanced">
                                        <i class="fas fa-upload me-2"></i>Selecionar Nova Foto
                                    </label>
                                    <input type="file" class="form-control form-control-enhanced" id="foto_perfil" name="foto_perfil" 
                                           accept="image/*" onchange="previewImage(this)">
                                    <div class="form-text">
                                        Formatos suportados: JPG, PNG, GIF. Tamanho máximo: 2MB.
                                    </div>
                                </div>
                                
                                <div class="mb-3 text-center">
                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                        <p class="text-muted mb-2">Pré-visualização:</p>
                                        <img id="preview" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary-enhanced">
                                        <i class="fas fa-save me-1"></i>Salvar Foto
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Remover Foto -->
    <div class="modal fade" id="confirmRemovePhotoModal" tabindex="-1" aria-labelledby="confirmRemovePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmRemovePhotoModalLabel">
                        <i class="fas fa-trash me-2"></i>Remover Foto do Perfil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <p class="text-center mb-3">Tem certeza que deseja remover sua foto de perfil?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita. Sua foto será substituída pelo avatar padrão.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <form method="POST" action="editar_perfil_usuario.php" style="display: inline;">
                        <input type="hidden" name="remover_foto" value="1">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Sim, Remover Foto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmUpdateModal = document.getElementById('confirmUpdateModal');
            const form = document.getElementById('editForm');
            
            if (form) {
                // Lógica para o modal de ATUALIZAÇÃO
                confirmUpdateModal.addEventListener('show.bs.modal', function () {
                    const novoNome = document.getElementById('nome_usuario').value;
                    document.getElementById('novoNomeUsuario').textContent = novoNome;
                });

                // Evento de clique para confirmar atualização
                document.getElementById('confirmarUpdateBtn').addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Atualizando...';
                    this.disabled = true;
                    form.submit();
                });

                // Validação em tempo real
                const nomeUsuarioInput = document.getElementById('nome_usuario');
                nomeUsuarioInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    if (value.length < 3) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#28a745';
                    }
                });
            }

            // Click no avatar para ver em tamanho maior
            const currentAvatar = document.getElementById('currentAvatar');
            if (currentAvatar) {
                currentAvatar.addEventListener('click', function() {
                    const img = this.querySelector('img');
                    if (img && img.src) {
                        window.open(img.src, '_blank');
                    }
                });
            }
        });

        // Função para pré-visualizar imagem
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Validação do formulário de upload de foto
        document.getElementById('photoUploadForm')?.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('foto_perfil');
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Por favor, selecione uma foto para upload.');
                return;
            }
            
            const file = fileInput.files[0];
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 2 * 1024 * 1024;
            
            if (!validTypes.includes(file.type)) {
                e.preventDefault();
                alert('Por favor, selecione uma imagem nos formatos JPG, PNG ou GIF.');
                return;
            }
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('A imagem deve ter no máximo 2MB.');
                return;
            }
        });
        
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>