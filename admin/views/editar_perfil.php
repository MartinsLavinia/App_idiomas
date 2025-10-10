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
// Removido: $update_sucesso = false; // Flag para controlar a exibição dos botões

// 2. Verifica se o formulário foi enviado (após confirmação do modal).
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_update'])) {
    $nome_usuario_novo = $_POST['nome_usuario'];

    // 3. LÓGICA ANTI-DUPLICAÇÃO:
    $sql_check = "SELECT id FROM administradores WHERE nome_usuario = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $nome_usuario_novo, $id_admin);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $mensagem = "Erro: O nome de usuário '{$nome_usuario_novo}' já está em uso.";
        $tipo_mensagem = 'danger';
    } else {
        // 4. Atualiza o nome de usuário.
        $sql_update = "UPDATE administradores SET nome_usuario = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nome_usuario_novo, $id_admin);

        if ($stmt_update->execute()) {
            $mensagem = "Nome de usuário atualizado com sucesso!";
            $tipo_mensagem = 'success';
            $_SESSION['nome_admin'] = $nome_usuario_novo;
            // Removido: $update_sucesso = true; // Define a flag de sucesso
        } else {
            $mensagem = "Ocorreu um erro inesperado ao atualizar o perfil.";
            $tipo_mensagem = 'danger';
        }
        $stmt_update->close();
    }
    $stmt_check->close();
}

// 5. Busca os dados do administrador para preencher o formulário.
$sql_admin = "SELECT nome_usuario FROM administradores WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $id_admin);
$stmt_admin->execute();
$admin = $stmt_admin->get_result()->fetch_assoc();
$stmt_admin->close();

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

        /* Navbar aprimorada */
        .navbar { 
            background: linear-gradient(135deg, var(--roxo-principal) 0%, var(--roxo-escuro) 100%) !important; 
            border-bottom: 3px solid var(--amarelo-detalhe);
            box-shadow: var(--shadow-medium);
            padding: 1rem 0;
        }
        
        .navbar-brand img { 
            height: 65px; 
            transition: transform 0.3s ease;
        }
        
        .navbar-brand img:hover {
            transform: scale(1.05);
        }

        /* Container principal - LAYOUT HORIZONTAL */
        .profile-container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px;
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
            font-size: 1.4rem; 
            font-weight: 600; 
            text-align: center; 
            padding: 25px 20px;
            position: relative;
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
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-claro));
            color: var(--branco);
        }

        .btn-primary-enhanced:hover {
            background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-success-enhanced {
            background: linear-gradient(135deg, var(--verde-sucesso), #20c997);
            color: var(--branco);
        }

        .btn-danger-enhanced {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: var(--branco);
        }

        /* Container de navegação - ATUALIZADO */
.navigation-buttons-container {
    max-width: 1200px;
    margin: 25px auto 0;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
.btn-navigation {
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    min-height: 54px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
}

.btn-navigation:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn-voltar-dashboard {
    background: #241fc2ff;
    color: white;
    border: 2px solid #241fc2ff;
}

.btn-voltar-dashboard:hover {
    background: #1e1a9bff;
}

.btn-cancelar-alteracoes {
    background: #A0A0A0;
    color: white;
    border: 2px solid #909090;
}

.btn-cancelar-alteracoes:hover {
    background: #909090;
}

/* Ícones dos botões */
.btn-navigation i {
    font-size: 1.1em;
    transition: transform 0.3s ease;
}

.btn-voltar-dashboard:hover i {
    transform: translateX(-2px);
}

/* Responsividade */
@media (max-width: 768px) {
    .navigation-buttons-container {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .btn-navigation {
        padding: 12px 24px;
        min-height: 50px;
        font-size: 0.95rem;
    }
}

@media (max-width: 480px) {
    .btn-navigation {
        padding: 10px 20px;
        min-height: 48px;
        font-size: 0.9rem;
    }
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
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="gerenciar_caminho.php">
                <img src="../../imagens/logo-idiomas.png" alt="Logo da Plataforma de Cursos">
            </a>
            <div class="navbar-text text-white">
                <i class="fas fa-user-circle me-2"></i>
                Bem-vindo, <?= htmlspecialchars($_SESSION['nome_admin'] ?? 'Administrador') ?>
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
                            <i class="fas fa-home me-1"></i>Dashboard
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
                <i class="fas fa-user-cog me-3"></i>Configurações do Perfil
            </div>
            
            <div class="horizontal-layout">
                <!-- COLUNA ESQUERDA - Perfil e Estatísticas -->
                <div class="left-column fade-in-left">
                    <!-- Seção do Avatar e Info - COM BOTÕES ESTILIZADOS -->
                    <div class="profile-avatar-section">
                        <div class="profile-avatar" data-bs-toggle="modal" data-bs-target="#editPhotoModal">
                            <i class="fas fa-user-graduate"></i>
                            <div class="avatar-overlay">
                                <i class="fas fa-camera text-white" style="font-size: 1.5rem;"></i>
                            </div>
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
                            <button type="button" class="btn btn-photo btn-remover-foto" data-bs-toggle="modal" data-bs-target="#confirmRemovePhotoModal">
                                <i class="fas fa-trash me-1"></i>Remover Foto
                            </button>
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
                            <span>Membro desde Janeiro 2024</span>
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
                            <!-- Formulário sempre visível -->
                            <form id="editForm" method="POST" action="editar_perfil.php">
                                <div class="form-group-enhanced">
                                    <label for="nome_usuario" class="form-label-enhanced">
                                        <i class="fas fa-user"></i>Nome de Usuário
                                    </label>
                                    <input type="text" class="form-control form-control-enhanced" id="nome_usuario" name="nome_usuario"
                                        value="<?= htmlspecialchars($admin['nome_usuario'] ?? '') ?>" required
                                        placeholder="Digite seu nome de usuário">
                                    <i class="fas fa-edit input-icon"></i>
                                </div>

                                <div class="form-group-enhanced">
                                    <label for="email_display" class="form-label-enhanced">
                                        <i class="fas fa-envelope"></i>Email (Somente leitura)
                                    </label>
                                    <input type="email" class="form-control form-control-enhanced" id="email_display"
                                        value="admin@cursosidiomas.com" readonly
                                        style="background-color: #f8f9fa;">
                                    <i class="fas fa-lock input-icon"></i>
                                </div>

                                <div class="form-group-enhanced">
                                    <label for="cargo_display" class="form-label-enhanced">
                                        <i class="fas fa-briefcase"></i>Cargo
                                    </label>
                                    <input type="text" class="form-control form-control-enhanced" id="cargo_display"
                                        value="Administrador Principal" readonly
                                        style="background-color: #f8f9fa;">
                                    <i class="fas fa-crown input-icon"></i>
                                </div>

                                <div class="form-group-enhanced">
                                    <label for="bio_display" class="form-label-enhanced">
                                        <i class="fas fa-quote-left"></i>Biografia Profissional
                                    </label>
                                    <textarea class="form-control form-control-enhanced" id="bio_display" rows="4" readonly
                                        style="background-color: #f8f9fa;">Administrador experiente com mais de 5 anos na área de educação online. Especialista em gestão de plataformas de ensino e desenvolvimento de cursos de idiomas.</textarea>
                                </div>
                                
                                <input type="hidden" name="confirmar_update" value="1">

                                <!-- Botão de Atualizar -->
                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary-enhanced btn-enhanced w-100" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">
                                        <i class="fas fa-save me-2"></i>Salvar Alterações
                                    </button>
                                </div>

                                <!-- BOTÕES DE NAVEGAÇÃO - ESTILO ATUALIZADO -->
<div class="navigation-buttons-container">
    <a href="gerenciar_caminho.php" class="btn btn-navigation btn-voltar-dashboard">
        <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
    </a>
    <button type="button" class="btn btn-navigation btn-cancelar-alteracoes" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">
        <i class="fas fa-times me-2"></i>Cancelar Alterações
    </button>
</div>s
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
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title" id="confirmUpdateModalLabel">
                        <i class="fas fa-save me-2"></i>Confirmar Alterações
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center mb-3">Você deseja salvar as alterações no seu perfil?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nome de usuário será alterado para:</strong><br>
                        '<strong id="novoNomeUsuario"></strong>'
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmarUpdateBtn">
                        <i class="fas fa-check me-1"></i>Sim, Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação para CANCELAR - CORRIGIDO -->
    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title" id="confirmCancelModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancelar Edição
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Modal para Editar Foto - NOVO -->
    <div class="modal fade" id="editPhotoModal" tabindex="-1" aria-labelledby="editPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title" id="editPhotoModalLabel">
                        <i class="fas fa-camera me-2"></i>Alterar Foto do Perfil
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="profile-avatar mx-auto mb-3" style="width: 120px; height: 120px; cursor: pointer;" id="currentAvatar">
                            <i class="fas fa-user-graduate" style="font-size: 2.5rem;"></i>
                        </div>
                        <p class="text-muted">Clique na imagem para visualizar</p>
                    </div>
                    
                    <form id="photoUploadForm" enctype="multipart/form-data">
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
                                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success w-100" onclick="uploadPhoto()">
                                    <i class="fas fa-save me-1"></i>Salvar Foto
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Remover Foto - NOVO -->
    <div class="modal fade" id="confirmRemovePhotoModal" tabindex="-1" aria-labelledby="confirmRemovePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-enhanced">
                <div class="modal-header modal-header-enhanced">
                    <h5 class="modal-title" id="confirmRemovePhotoModalLabel">
                        <i class="fas fa-trash me-2"></i>Remover Foto do Perfil
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="button" class="btn btn-danger" onclick="removePhoto()">
                        <i class="fas fa-trash me-1"></i>Sim, Remover Foto
                    </button>
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

                document.getElementById('confirmarUpdateBtn').addEventListener('click', function() {
                    // Mostra loading
                    loadingOverlay.style.display = 'flex';
                    
                    // Simula um pequeno delay para melhor UX
                    setTimeout(() => {
                        form.submit();
                    }, 500);
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
                                    stat.textContent = finalText; // Mantém formato original
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
            currentAvatar.addEventListener('click', function() {
                const preview = document.getElementById('preview');
                if (preview.src) {
                    window.open(preview.src, '_blank');
                }
            });
        });

        // Funções para edição de foto
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

        function uploadPhoto() {
            const fileInput = document.getElementById('foto_perfil');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            if (!fileInput.files[0]) {
                alert('Por favor, selecione uma foto para upload.');
                return;
            }
            
            // Validação do arquivo
            const file = fileInput.files[0];
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!validTypes.includes(file.type)) {
                alert('Por favor, selecione uma imagem nos formatos JPG, PNG ou GIF.');
                return;
            }
            
            if (file.size > maxSize) {
                alert('A imagem deve ter no máximo 2MB.');
                return;
            }
            
            // Mostra loading
            loadingOverlay.style.display = 'flex';
            
            // Simula upload (substitua por AJAX real)
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                alert('Foto atualizada com sucesso!');
                bootstrap.Modal.getInstance(document.getElementById('editPhotoModal')).hide();
                
                // Atualiza a pré-visualização principal
                const preview = document.getElementById('preview');
                const currentAvatar = document.querySelector('.profile-avatar i');
                currentAvatar.style.display = 'none';
                document.querySelector('.profile-avatar').style.backgroundImage = `url(${preview.src})`;
                document.querySelector('.profile-avatar').style.backgroundSize = 'cover';
                document.querySelector('.profile-avatar').style.backgroundPosition = 'center';
            }, 1500);
        }

        // Função para remover foto
        function removePhoto() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Mostra loading
            loadingOverlay.style.display = 'flex';
            
            // Simula remoção (substitua por AJAX real)
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                alert('Foto removida com sucesso!');
                bootstrap.Modal.getInstance(document.getElementById('confirmRemovePhotoModal')).hide();
                
                // Restaura o avatar padrão
                const profileAvatar = document.querySelector('.profile-avatar');
                const icon = profileAvatar.querySelector('i');
                
                profileAvatar.style.backgroundImage = 'none';
                profileAvatar.style.background = 'linear-gradient(135deg, var(--roxo-claro), var(--roxo-principal))';
                icon.style.display = 'flex';
            }, 1500);
        }
    </script>
</body>
</html>