<?php
session_start();
include __DIR__ . '/../../conexao.php';

// 1. Segurança: Garante que apenas administradores logados acessem.
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

$id_admin = $_SESSION['id_admin'];
$mensagem = '';
$tipo_mensagem = '';

// 2. Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Atualização do nome de usuário
    if (isset($_POST['confirmar_update'])) {
        $nome_usuario_novo = $_POST['nome_usuario'];

        // LÓGICA ANTI-DUPLICAÇÃO:
        $sql_check = "SELECT id FROM administradores WHERE nome_usuario = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $nome_usuario_novo, $id_admin);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensagem = "Erro: O nome de usuário '{$nome_usuario_novo}' já está em uso.";
            $tipo_mensagem = 'danger';
        } else {
            // Atualiza o nome de usuário.
            $sql_update = "UPDATE administradores SET nome_usuario = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $nome_usuario_novo, $id_admin);

            if ($stmt_update->execute()) {
                $mensagem = "Nome de usuário atualizado com sucesso!";
                $tipo_mensagem = 'success';
                $_SESSION['nome_admin'] = $nome_usuario_novo;
            } else {
                $mensagem = "Ocorreu um erro inesperado ao atualizar o perfil.";
                $tipo_mensagem = 'danger';
            }
            $stmt_update->close();
        }
        $stmt_check->close();
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
            $nomeArquivo = 'perfil_' . $id_admin . '_' . time() . '.' . $extensao;
            $caminhoCompleto = $diretorioFotos . $nomeArquivo;
            
            if (move_uploaded_file($foto['tmp_name'], $caminhoCompleto)) {
                // Atualiza no banco de dados
                $sql_foto = "UPDATE administradores SET foto_perfil = ? WHERE id = ?";
                $stmt_foto = $conn->prepare($sql_foto);
                $caminhoRelativo = 'uploads/perfis/' . $nomeArquivo;
                $stmt_foto->bind_param("si", $caminhoRelativo, $id_admin);
                
                if ($stmt_foto->execute()) {
                    $mensagem = "Foto de perfil atualizada com sucesso!";
                    $tipo_mensagem = 'success';
                    $_SESSION['foto_admin'] = $caminhoRelativo;
                } else {
                    $mensagem = "Erro ao salvar informações da foto no banco de dados.";
                    $tipo_mensagem = 'danger';
                    // Remove o arquivo se deu erro no banco
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
        // Busca a foto atual
        $sql_foto_atual = "SELECT foto_perfil FROM administradores WHERE id = ?";
        $stmt_foto_atual = $conn->prepare($sql_foto_atual);
        $stmt_foto_atual->bind_param("i", $id_admin);
        $stmt_foto_atual->execute();
        $resultado = $stmt_foto_atual->get_result();
        $admin_foto = $resultado->fetch_assoc();
        $stmt_foto_atual->close();
        
        // Remove o arquivo físico se existir
        if (!empty($admin_foto['foto_perfil']) && file_exists(__DIR__ . '/../../' . $admin_foto['foto_perfil'])) {
            unlink(__DIR__ . '/../../' . $admin_foto['foto_perfil']);
        }
        
        // Atualiza no banco de dados
        $sql_remove_foto = "UPDATE administradores SET foto_perfil = NULL WHERE id = ?";
        $stmt_remove_foto = $conn->prepare($sql_remove_foto);
        $stmt_remove_foto->bind_param("i", $id_admin);
        
        if ($stmt_remove_foto->execute()) {
            $mensagem = "Foto de perfil removida com sucesso!";
            $tipo_mensagem = 'success';
            unset($_SESSION['foto_admin']);
        } else {
            $mensagem = "Erro ao remover foto do perfil.";
            $tipo_mensagem = 'danger';
        }
        $stmt_remove_foto->close();
    }
}

// 5. Busca os dados do administrador para preencher o formulário.
$sql_admin = "SELECT nome_usuario, foto_perfil FROM administradores WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $id_admin);
$stmt_admin->execute();
$admin = $stmt_admin->get_result()->fetch_assoc();
$stmt_admin->close();

// Define a foto atual (se existir)
$foto_atual = !empty($admin['foto_perfil']) ? '../../' . $admin['foto_perfil'] : null;

// 6. DATA DE REGISTRO FIXA NO CÓDIGO
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
            background: linear-gradient(135deg, var(--cinza-claro) 0%, #ffffffff 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

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

.navbar {
    display: flex;
    align-items: center;
}

        
.btn-outline-light {
    color: var(--amarelo-detalhe);
    border-color: var(--amarelo-detalhe);
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-light:hover {
    background-color: var(--amarelo-detalhe);
    color: var(--preto-texto);
}

.settings-icon {
    color: var(--roxo-principal) !important;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1.2rem;
}

.settings-icon:hover {
    color: var(--roxo-escuro) !important;
    transform: rotate(90deg);
}

.logout-icon {
    color: var(--roxo-principal) !important;
    transition: all 0.3s ease;
    text-decoration: none;
}

.logout-icon:hover {
    color: var(--roxo-escuro) !important;
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

        /* título alinhado à esquerda dentro do header (aumentado para ficar proporcional ao botão Salvar) */
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

        /* Botão voltar no header: tamanho aumentado para ficar proporcional ao botão Salvar */
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
        
        .btn-success-enhanced {
            background: linear-gradient(135deg, var(--verde-sucesso), #20c997);
            color: var(--branco);
        }

        .btn-danger-enhanced {
            background: linear-gradient(135deg, #e21a2eff, #dd0e23ff);
            color: var(--branco);
        }

        /* Container de navegação - ATUALIZADO */
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

         /* Botões de navegação - ESTILO SIMPLES E PROFISSIONAL */
        /* Estilos mais sutis e coerentes com o tema para os botões de navegação */
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

        /* Botão "Voltar ao Dashboard": tom roxo suave, sem gradientes fortes */
        .btn-voltar-dashboard {
            background: transparent;
            border: 1px solid rgba(76, 8, 124, 0.12);
            color: var(--roxo-principal);
            font-weight: 600;
            border-radius: 10px;
            text-transform: none;
        }

        .btn-voltar-dashboard::before {
            display: none; /* remove efeito de brilho */
        }

        .btn-voltar-dashboard:hover {
            background: rgba(76, 8, 124, 0.06);
            color: var(--roxo-principal);
        }

        .btn-voltar-dashboard:active {
            transform: translateY(0);
        }

        /* Botão "Cancelar Alterações": aparência neutra e proporcional */
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
            border-color: rgba(17, 17, 17, 1);
            width: 100%;
        }

        .btn-cancelar-alteracoes::before {
            display: none; /* remove efeito de brilho */
        }

        .btn-cancelar-alteracoes:hover {
            background: rgba(144, 144, 144, 0.12);
            color: var(--cinza-escuro);
            border-color:  var(--cinza-escuro);
        }

        .btn-cancelar-alteracoes:active {
            transform: translateY(0);
        }

        /* Ícones dos botões */
        .btn-navigation i {
            font-size: 1.15em;
            transition: transform 0.3s ease;
        }

        .btn-voltar-dashboard:hover i {
            transform: translateX(-2px);
        }

        /* Estilos customizados para o modal de alteração de foto */
        .custom-photo-modal {
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff, #fbfbff);
            box-shadow: 0 12px 40px rgba(16, 12, 48, 0.12);
        }

        .custom-photo-header {
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
            color: var(--branco);
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
        }

        .custom-photo-header .modal-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0;
        }

        .custom-photo-modal .modal-body {
            background: transparent;
            padding: 18px 22px;
        }

        /* Avatar dentro do modal */
        .custom-photo-modal .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(106,13,173,0.08);
            box-shadow: 0 10px 30px rgba(106,13,173,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--roxo-claro), var(--roxo-principal));
        }

        #imagePreview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        #preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 22px rgba(0,0,0,0.12);
            border: 3px solid #fff;
        }

        /* Estilo do input file para combinar com o design */
        input#foto_perfil.form-control.form-control-enhanced {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--cinza-medio);
            background: #fff;
            cursor: pointer;
        }

        /* Botões do modal (Salvar / Cancelar) com estilo personalizado */
        /* Unifica tamanho e espaçamento para ficar igual a .btn-cancelar-alteracoes */
        .btn-photo-save,
        .btn-photo-cancel {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: none;
            cursor: pointer;
        }

        /* Salvar Foto: roxo preenchido, mas com hover discreto semelhante ao cancelar */
        .btn-photo-save {
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-claro) 100%);
            color: var(--branco);
            border: 2px solid rgba(76,8,124,0.12);
            box-shadow: 0 8px 24px rgba(76,8,124,0.06);
            transition: background-color 0.12s ease, transform 0.12s ease, box-shadow 0.12s ease;
        }

        .btn-photo-save:hover {
            background: linear-gradient(135deg, rgba(76,8,124,0.95) 0%, rgba(138,43,226,0.95) 100%);
            transform: translateY(-1px);
            color: var(--branco);
            box-shadow: 0 6px 20px rgba(76,8,124,0.12);
        }

        /* Cancelar: outline roxo suave — hover com fundo translúcido (efeito parecido com .btn-cancelar-alteracoes) */
        .btn-photo-cancel {
            background: transparent;
            border: 2px solid rgba(76,8,124,0.12);
            color: var(--roxo-principal);
            transition: background-color 0.12s ease, color 0.12s ease, transform 0.12s ease;
        }

        .btn-photo-cancel:hover {
            background: rgba(76,8,124,0.06);
            color: var(--preto-texto);
            transform: translateY(-1px);
        }

        @media (max-width: 576px) {
            .custom-photo-modal .profile-avatar { width: 96px; height: 96px; }
            #preview { width: 88px; height: 88px; }
        }

        /* Efeitos de loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--cinza-medio);
            border-top: 5px solid var(--roxo-principal);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Estado de sucesso */
        .success-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--verde-sucesso);
            margin-bottom: 20px;
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
        }

        @media (max-width: 480px) {
            .btn-navigation {
                padding: 10px 20px;
                min-height: 48px;
                font-size: 0.9rem;
            }
        }

        /* Tooltip */
        .custom-tooltip {
            position: relative; 
        }

        .custom-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            z-index: 1000;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s; 
        }

        .custom-tooltip:focus-within::after {
            opacity: 1;
            visibility: visible;
        }

        .custom-tooltip:hover::after {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

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

  <div class="profile-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb-container fade-in-left">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="gerenciar_caminho.php">
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
                    <a href="gerenciar_caminho.php" class="btn btn-voltar-header">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
            
            <div class="horizontal-layout">
                <!-- COLUNA ESQUERDA - Perfil e Estatísticas -->
                <div class="left-column fade-in-left">
                    <!-- Seção do Avatar e Info - COM BOTÕES ESTILIZADOS -->
                    <div class="profile-avatar-section">
                        <div class="profile-avatar <?= $foto_atual ? 'has-photo' : '' ?>" data-bs-toggle="modal" data-bs-target="#editPhotoModal">
                            <?php if ($foto_atual): ?>
                                <img src="<?= htmlspecialchars($foto_atual) ?>" alt="Foto de perfil">
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-user-graduate"></i>
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h4><?= htmlspecialchars($_SESSION['nome_admin'] ?? $admin['nome_usuario'] ?? 'Usuário') ?></h4>
                            <span class="profile-role">
                                <i class="fas fa-crown me-1"></i>Administrador
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
                            <span>admin@cursosidiomas.com</span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Membro desde <?= htmlspecialchars($data_registro_codigo) ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>São Paulo, Brasil</span>
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
                                <div class="stat-number">127</div>
                                <div class="stat-label">Cursos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">1.2K</div>
                                <div class="stat-label">Alunos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">98%</div>
                                <div class="stat-label">Satisfação</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">45</div>
                                <div class="stat-label">Dias Online</div>
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
                            <!-- Formulário para atualizar nome de usuário -->
                            <form id="editForm" method="POST" action="editar_perfil.php">
                                <input type="hidden" name="confirmar_update" value="1">
                                
                                <div class="form-group-enhanced">
                                    <label for="nome_usuario" class="form-label-enhanced">
                                        <i class="fas fa-user"></i>Nome de Usuário
                                    </label>
                                    <input type="text" class="form-control form-control-enhanced" id="nome_usuario" name="nome_usuario"
                                        value="<?= htmlspecialchars($admin['nome_usuario'] ?? '') ?>" required
                                        placeholder="Digite seu nome de usuário">
                                    <i class="fas fa-edit input-icon"></i>
                                </div>

                                <div class="form-group-enhanced custom-tooltip" 
                                     data-tooltip="Apenas membros com autorização da administração geral podem modificar informações privadas.">
                                    <label for="email" class="form-label-enhanced">
                                        <i class="fas fa-envelope"></i>Email
                                    </label>
                                    <input type="email" class="form-control form-control-enhanced" id="email" name="email" readonly 
                                        value="admin@cursosidiomas.com" required
                                        placeholder="Digite seu email">
                                    <i class="fas fa-lock input-icon"></i>
                                </div>

                                <div class="form-group-enhanced custom-tooltip" 
                                     data-tooltip="Apenas membros com autorização da administração geral podem modificar informações privadas.">
                                    <label for="cargo_display" class="form-label-enhanced">
                                        <i class="fas fa-briefcase"></i>Cargo
                                    </label>
                                    <input type="text" class="form-control form-control-enhanced" id="cargo_display"
                                        value="Administrador Principal" readonly
                                        style="background-color: #f8f9fa;">
                                    <i class="fas fa-crown input-icon"></i>
                                </div>

                                <!-- Botão de Atualizar -->
                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary-enhanced btn-enhanced w-100" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">
                                        <i class="fas fa-save me-2"></i>Salvar Alterações
                                    </button>
                                </div>

                                <!-- BOTÕES DE NAVEGAÇÃO - ESTILO ATUALIZADO -->
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
                        <strong>Nome de usuário será alterado para:</strong><br>
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
                    <a href="gerenciar_caminho.php" class="btn btn-danger">
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
                                <i class="fas fa-user-graduate" style="font-size: 2.5rem;"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted">Clique na imagem para visualizar</p>
                    </div>
                    
                    <form id="photoUploadForm" method="POST" action="editar_perfil.php" enctype="multipart/form-data">
                        <div class="mb-3">
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
                        
                        <div class="row g-2">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-secondary btn-cancelar-alteracoes w-100" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-photo-save w-100">
                                    <i class="fas fa-save me-1"></i>Salvar Foto
                                </button>
                            </div>
                        </div>
                    </form>
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
                    <form method="POST" action="editar_perfil.php" style="display: inline;">
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
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Adiciona animações de entrada com delay
            const leftElements = document.querySelectorAll('.fade-in-left');
            const rightElements = document.querySelectorAll('.fade-in-right');
            
            leftElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
            
            rightElements.forEach((el, index) => {
                el.style.animationDelay = `${(index * 0.1) + 0.3}s`;
            });
            
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
                    loadingOverlay.style.display = 'flex';
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

                // Efeitos de hover nos botões
                const buttons = document.querySelectorAll('.btn-enhanced, .btn-photo');
                buttons.forEach(btn => {
                    btn.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                    });
                    
                    btn.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });
            }

            // Animação de contadores nas estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const stat = entry.target;
                        const finalText = stat.textContent;
                        const finalNumber = parseInt(finalText.replace(/[^\d]/g, ''));
                        
                        if (finalNumber > 0) {
                            let currentNumber = 0;
                            const increment = finalNumber / 30;
                            
                            const timer = setInterval(() => {
                                currentNumber += increment;
                                if (currentNumber >= finalNumber) {
                                    stat.textContent = finalText;
                                    clearInterval(timer);
                                } else {
                                    if (finalText.includes('K')) {
                                        stat.textContent = (currentNumber / 1000).toFixed(1) + 'K';
                                    } else if (finalText.includes('%')) {
                                        stat.textContent = Math.floor(currentNumber) + '%';
                                    } else {
                                        stat.textContent = Math.floor(currentNumber);
                                    }
                                }
                            }, 50);
                        }
                        
                        observer.unobserve(stat);
                    }
                });
            });

            statNumbers.forEach(stat => {
                observer.observe(stat);
            });

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
            const loadingOverlay = document.getElementById('loadingOverlay');
            
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
            
            // Mostra loading
            loadingOverlay.style.display = 'flex';
        });
    </script>

    
</body>
</html>