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
        /* Paleta de Cores - MESMAS DO SITE */
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
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

        .btn-secondary {
            background-color: var(--cinza-medio);
            border-color: var(--cinza-medio);
            color: var(--preto-texto);
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #c8c9cb;
            border-color: #c8c9cb;
            transform: translateY(-2px);
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
        @media (max-width: 768px) {
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
    </style>
</head>
<body>
    <!-- Navbar Transparente -->
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
                        <button type="submit" class="btn btn-primary">
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
    </script>
</body>
</html>