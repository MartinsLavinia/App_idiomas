<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$mensagem = '';

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $nivel = $_POST['nivel'];
    $ordem = $_POST['ordem'];
    $conteudo = $_POST['conteudo'];
    $resumo = $_POST['resumo'] ?? '';
    $palavras_chave = $_POST['palavras_chave'] ?? '';

    // Validação simples
    if (empty($titulo) || empty($nivel) || empty($ordem) || empty($conteudo)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Insere a nova teoria na tabela
        $sql_insert = "INSERT INTO teorias (titulo, nivel, ordem, conteudo, resumo, palavras_chave, data_criacao) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        
        if ($stmt_insert) {
            $stmt_insert->bind_param("ssisss", $titulo, $nivel, $ordem, $conteudo, $resumo, $palavras_chave);
            
            if ($stmt_insert->execute()) {
                $mensagem = '<div class="alert alert-success">Teoria adicionada com sucesso!</div>';
                // Limpar campos após sucesso
                $titulo = $nivel = $ordem = $conteudo = $resumo = $palavras_chave = '';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao adicionar teoria: ' . $stmt_insert->error . '</div>';
            }
            $stmt_insert->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }

        $database->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Teoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos para validação - ADICIONAR NO FINAL DO <style> EXISTENTE */
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
}

.is-valid {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
}

/* Loading submit - ADICIONAR NO FINAL DO <style> EXISTENTE */
.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-right-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Melhorias para o modal de preview - ADICIONAR NO FINAL DO <style> EXISTENTE */
.modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: var(--branco);
    border-bottom: 3px solid var(--amarelo-detalhe);
}

/* Botão Visualizar (Preview) - Mais bonito e moderno */
.btn-info {
    background-color: #0dcaf0;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.6rem 1.5rem;
    font-weight: 600;
    transition: background 0.2s, color 0.2s, border 0.2s, transform 0.2s;
    box-shadow: none;
}

.btn-info:hover, .btn-info:focus {
    background-color: #31d2f2;
    color: #fff;
    transform: translateY(-2px) scale(1.03);
    outline: none;
}

.btn-info:active {
    background-color: #0dcaf0;
    transform: scale(0.98);
}

.btn-info i {
    margin-right: 0.5rem;
}
        /* Paleta de Cores - MESMAS DO SITE */
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
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            margin: 0;
            padding: 0;
        }

        /* Navbar Transparente */
        .navbar {
            background-color: transparent !important;
            padding: 1rem 0;
            border-bottom: 3px solid var(--amarelo-detalhe);
            box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
    }
     /* Ajuste da logo no header */
    .navbar-brand {
        margin-left: auto;
        margin-right: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end; /* Move para o canto direito */
        width: 100%;
    }
    .navbar-brand .logo-header {
        height: 70px;
        width: auto;
        display: block;
    }

        .navbar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            
        }

        .logo-header {
            height: 40px;
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

        /* SIDEBAR FIXO - CORREÇÃO APLICADA */
              /* Menu Lateral */
        .sidebar .profile {
    text-align: center;
    margin-bottom: 30px;
}

/* ADICIONE AQUI O NOVO CSS */
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
/* FIM DO NOVO CSS */

.sidebar .profile h5 {
    font-weight: 600;
    margin-bottom: 0;
    color: var(--branco);
}

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
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

        /* CORREÇÃO PARA O BACKDROP DO MODAL */
        .modal-backdrop {
            z-index: 1050; /* Backdrop abaixo do sidebar */
        }

        .modal {
            z-index: 1060; /* Modal acima do backdrop */
        }

        body.modal-open .sidebar {
            opacity: 1 !important;
            visibility: visible !important;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        /* REMOVER TODAS AS ANIMAÇÕES DO SIDEBAR */
        .sidebar,
        .sidebar *,
        .sidebar .list-group-item,
        .sidebar .list-group-item i {
            transition: none !important;
            animation: none !important;
        }

        /* Links normais - comportamento padrão sem animação */
        .sidebar .list-group-item:not([data-bs-toggle]) {
            transition: none !important;
        }

        /* Links de modal - manter funcionalidade mas sem animação */
        .sidebar .list-group-item[data-bs-toggle] {
            cursor: pointer;
        }

        /* Container Principal */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Cabeçalho da Página - EFEITO VIDRO ANIMADO */
        .page-header {
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(106, 13, 173, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: glassEffect 3s infinite;
        }

        @keyframes glassEffect {
            0% {
                left: -100%;
            }
            50% {
                left: 100%;
            }
            100% {
                left: 100%;
            }
        }

        .page-header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: var(--branco);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header .icon-wrapper {
            background: var(--amarelo-detalhe);
            color: var(--roxo-escuro);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: var(--branco);
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
            background: var(--branco);
            color: var(--roxo-principal);
            border-color: var(--branco);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
        }

        /* Card Principal */
        .main-card {
            background: var(--branco);
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            border-top: 3px solid var(--amarelo-detalhe);
        }

        .card-header-custom {
            background-color: var(--roxo-principal);
            color: var(--branco);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: glassEffect 3s infinite;
        }

        .card-header-custom h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--branco);
            position: relative;
            z-index: 1;
        }

        .card-body-custom {
            padding: 2rem;
        }

        /* Seções do Formulário */
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--cinza-medio);
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 1rem;
        }

        .form-section-title {
            color: var(--roxo-principal);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--amarelo-detalhe);
        }

        .form-section-title i {
            color: var(--amarelo-detalhe);
            font-size: 1.3rem;
        }

        /* Labels e Campos */
        .form-label {
            color: var(--roxo-principal);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .form-control, .form-select {
            border: 1px solid var(--cinza-medio);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.15);
            outline: none;
        }

        .form-text {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Botões */
        .btn-primary {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
            color: var(--branco);
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--roxo-escuro);
            border-color: var(--roxo-escuro);
            transform: translateY(-2px);
        }


        /* Botão Cancelar padrão (vermelho suave) */
        .btn-secondary {
            background: rgba(33, 37, 41, 0.08); /* preto-texto com transparência */
            border: 1.5px solid var(--preto-texto);
            color: var(--preto-texto);
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, border 0.2s, transform 0.2s;
            box-shadow: none;
        }

        .btn-secondary:hover, .btn-secondary:focus {
            background: rgba(33, 37, 41, 0.18);
            color: var(--preto-texto);

            transform: translateY(-2px) scale(1.03);
            outline: none;
        }

        .btn-secondary:active {
            background: rgba(33, 37, 41, 0.28);
            color: var(--preto-texto);
            border-color: var(--roxo-escuro);
            transform: scale(0.98);
        }

        .btn-group-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--cinza-medio);
        }

        /* Alertas */
        .alert {
            border: none;
            border-radius: 6px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
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

            .main-container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .page-header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .card-body-custom {
                padding: 1.5rem;
            }

            .btn-group-actions {
                flex-direction: column;
            }

            .btn-group-actions .btn {
                width: 100%;
            }
        }

            .btn-warning {
                background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
                color: var(--cinza-texto);
                box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
                min-width: 180px;
            }

            .btn-warning:hover {
                background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
                transform: translateY(-2px);
                box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
                color: var(--cinza-texto);
            }
    </style>

    
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark">
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

    <!-- SIDEBAR ADICIONADO AQUI -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item">
                <i class="fas fa-plus-circle"></i> Adicionar Caminho
            </a>
            <a href="pagina_adicionar_idiomas.php" class="list-group-item" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal">
                <i class="fas fa-globe"></i> Gerenciar Idiomas
            </a>
            <a href="gerenciar_teorias.php" class="list-group-item active">
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
        <div class="main-container">
            <!-- Cabeçalho com Efeito Vidro -->
            <div class="page-header">
                <div class="page-header-content">
                    <h1>
                        <span class="icon-wrapper">
                            <i class="fas fa-book-medical"></i>
                        </span>
                        Adicionar Nova Teoria
                    </h1>
                    <a href="gerenciar_teorias.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i>Voltar
                    </a>
                </div>
            </div>

            <!-- Mensagens -->
            <?php echo $mensagem; ?>

            <!-- Card Principal -->
            <div class="main-card">
                <div class="card-header-custom">
                    <h2><i class="fas fa-edit me-2"></i>Formulário de Teoria</h2>
                </div>
                <div class="card-body-custom">
                    <form action="adicionar_teoria.php" method="POST">
                        
                        <!-- Seção: Informações Básicas -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-info-circle"></i>
                                Informações Básicas
                            </div>

                            <!-- Campo Título -->
                            <div class="mb-3">
                                <label for="titulo" class="form-label required-field">Título da Teoria</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo ?? ''); ?>" placeholder="Ex: Presente Simples em Inglês" required>
                            </div>

                            <div class="row">
                                <!-- Campo Nível -->
                                <div class="col-md-6 mb-3">
                                    <label for="nivel" class="form-label required-field">Nível</label>
                                    <select class="form-select" id="nivel" name="nivel" required>
                                        <option value="">Selecione o nível</option>
                                        <option value="A1" <?php echo (isset($nivel) && $nivel == 'A1') ? 'selected' : ''; ?>>A1 - Iniciante</option>
                                        <option value="A2" <?php echo (isset($nivel) && $nivel == 'A2') ? 'selected' : ''; ?>>A2 - Básico</option>
                                        <option value="B1" <?php echo (isset($nivel) && $nivel == 'B1') ? 'selected' : ''; ?>>B1 - Intermediário</option>
                                        <option value="B2" <?php echo (isset($nivel) && $nivel == 'B2') ? 'selected' : ''; ?>>B2 - Intermediário Avançado</option>
                                        <option value="C1" <?php echo (isset($nivel) && $nivel == 'C1') ? 'selected' : ''; ?>>C1 - Avançado</option>
                                        <option value="C2" <?php echo (isset($nivel) && $nivel == 'C2') ? 'selected' : ''; ?>>C2 - Proficiente</option>
                                    </select>
                                </div>

                                <!-- Campo Ordem -->
                                <div class="col-md-6 mb-3">
                                    <label for="ordem" class="form-label required-field">Ordem de Exibição</label>
                                    <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($ordem ?? ''); ?>" min="1" placeholder="1" required>
                                    <div class="form-text">Ordem em que a teoria aparecerá na lista</div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Detalhes Adicionais -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-tags"></i>
                                Detalhes Adicionais
                            </div>

                            <!-- Campo Resumo -->
                            <div class="mb-3">
                                <label for="resumo" class="form-label">Resumo</label>
                                <textarea class="form-control" id="resumo" name="resumo" rows="3" placeholder="Breve resumo da teoria para exibição na lista"><?php echo htmlspecialchars($resumo ?? ''); ?></textarea>
                                <div class="form-text">Resumo que aparecerá na lista de teorias</div>
                            </div>

                            <!-- Campo Palavras-chave -->
                            <div class="mb-3">
                                <label for="palavras_chave" class="form-label">Palavras-chave</label>
                                <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" value="<?php echo htmlspecialchars($palavras_chave ?? ''); ?>" placeholder="gramática, verbos, presente simples">
                                <div class="form-text">Palavras-chave separadas por vírgula para facilitar a busca</div>
                            </div>
                        </div>

                        <!-- Seção: Conteúdo -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-file-alt"></i>
                                Conteúdo da Teoria
                            </div>

                            <!-- Campo Conteúdo -->
                            <div class="mb-3">
                                <label for="conteudo" class="form-label required-field">Conteúdo Completo</label>
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="15" required><?php echo htmlspecialchars($conteudo ?? ''); ?></textarea>
                                <div class="form-text">Conteúdo completo da teoria. Você pode usar HTML para formatação.</div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="btn-group-actions">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Adicionar Teoria
                            </button>
                            <a href="gerenciar_teorias.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Para usar TinyMCE, você precisa:
        // 1. Acessar https://www.tiny.cloud/ e criar uma conta
        // 2. Obter uma API key gratuita
        // 3. Substituir "no-api-key" pela sua chave no script do TinyMCE
        
        console.log('Para usar TinyMCE, você precisa:');
        console.log('1. Acessar https://www.tiny.cloud/ e criar uma conta');
        console.log('2. Obter uma API key gratuita');
        console.log('3. Substituir "no-api-key" pela sua chave no script do TinyMCE');

        // Preview do conteúdo - ADICIONAR NO FINAL DO <script> EXISTENTE
function adicionarBotaoPreview() {
    const grupoBotoes = document.querySelector('.btn-group-actions');
    const botaoPreview = document.createElement('button');
    botaoPreview.type = 'button';
    botaoPreview.className = 'btn btn-info';
    botaoPreview.innerHTML = '<i class="fas fa-eye me-2"></i>Visualizar';
    botaoPreview.onclick = mostrarPreview;
    
    grupoBotoes.insertBefore(botaoPreview, grupoBotoes.firstChild);
}

function mostrarPreview() {
    const titulo = document.getElementById('titulo').value || 'Sem título';
    const conteudo = tinymce.get('conteudo') ? tinymce.get('conteudo').getContent() : document.getElementById('conteudo').value;
    
    const previewHTML = `
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Preview: ${titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${conteudo || '<p class="text-muted">Nenhum conteúdo para visualizar.</p>'}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente
    const modalExistente = document.getElementById('previewModal');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', previewHTML);
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    previewModal.show();
}

document.addEventListener('DOMContentLoaded', adicionarBotaoPreview);


    </script>
</body>
</html>