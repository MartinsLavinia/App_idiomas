<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID da teoria foi passado via URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_teorias.php");
    exit();
}

$teoria_id = $_GET['id'];
$mensagem = '';

$database = new Database();
$conn = $database->conn;

//Foto de perfil do admin
$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

// Buscar idiomas disponíveis
$idiomas_disponiveis = [];
$sql_idiomas = "SELECT nome FROM idiomas ORDER BY nome";
$result_idiomas = $conn->query($sql_idiomas);
if ($result_idiomas) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas_disponiveis[] = $row['nome'];
    }
}

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (se o método for POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $nivel = $_POST['nivel'];
    $idioma = $_POST['idioma'];
    $ordem = $_POST['ordem'];
    $conteudo = $_POST['conteudo'];
    $resumo = $_POST['resumo'] ?? '';
    $palavras_chave = $_POST['palavras_chave'] ?? '';

    // Validação simples
    if (empty($titulo) || empty($nivel) || empty($idioma) || empty($ordem) || empty($conteudo)) {
        $mensagem = '<div class="alert alert-danger">Por favor, preencha todos os campos obrigatórios.</div>';
    } else {
        // Atualiza a teoria na tabela
        $sql_update = "UPDATE teorias SET titulo = ?, nivel = ?, idioma = ?, ordem = ?, conteudo = ?, resumo = ?, palavras_chave = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("ssissssi", $titulo, $nivel, $idioma, $ordem, $conteudo, $resumo, $palavras_chave, $teoria_id);
            
            if ($stmt_update->execute()) {
                $mensagem = '<div class="alert alert-success" id="mensagemSucesso">Teoria atualizada com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar teoria: ' . $stmt_update->error . '</div>';
            }
            $stmt_update->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }
    }
}

// Busca os dados da teoria para edição
$sql_teoria = "SELECT titulo, nivel, idioma, ordem, conteudo, resumo, palavras_chave FROM teorias WHERE id = ?";
$stmt_teoria = $conn->prepare($sql_teoria);
$stmt_teoria->bind_param("i", $teoria_id);
$stmt_teoria->execute();
$teoria = $stmt_teoria->get_result()->fetch_assoc();
$stmt_teoria->close();

$database->closeConnection();

if (!$teoria) {
    header("Location: gerenciar_teorias.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Teoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">

    <style>
    <?php include __DIR__ . '/gerenciamento.css'; ?>
    
    /* Estilos específicos para a página de edição */
    .form-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(106, 13, 173, 0.1);
        transition: all 0.3s ease;
        animation: slideInUp 0.5s ease-out;
    }

    .form-container:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .form-label {
        font-weight: 500;
        color: var(--roxo-principal);
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        border: 1px solid var(--cinza-medio);
        border-radius: 10px;
        padding: 10px 15px;
        transition: all 0.3s ease;
        background-color: var(--branco);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--roxo-principal);
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.15);
        transform: translateY(-2px);
    }

    .form-text {
        color: var(--cinza-escuro);
        font-size: 0.875rem;
        margin-top: 5px;
    }

    /* Header da página */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(106, 13, 173, 0.1);
        animation: slideInDown 0.5s ease-out;
    }

    .page-header-icon {
        /* deixar o ícone no mesmo lugar, mas sem cor de fundo e sem borda/sombra */
        background: transparent;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        box-shadow: none;
        border: none;
    }

    .page-header-icon i {
        /* ícone em preto para contraste e legibilidade */
        color: var(--preto-texto) !important;
        font-size: 1.5rem;
    }

    .page-header h1 {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        color: var(--preto-texto);
        font-weight: 700;
        font-size: 1.8rem;
    }

    /* Botões específicos */
    .btn-primary {
        background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        border: none;
        color: var(--branco);
        font-weight: 600;
        padding: 12px 30px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
        color: var(--branco);
    }

    .btn-secondary {
        background: var(--cinza-escuro);
        border: none;
        color: var(--branco);
        font-weight: 600;
        padding: 12px 30px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: var(--branco);
    }

    /* Área do editor TinyMCE */
    .tox-tinymce {
        border-radius: 10px !important;
        border: 1px solid var(--cinza-medio) !important;
        transition: all 0.3s ease;
    }

    .tox-tinymce:focus-within {
        border-color: var(--roxo-principal) !important;
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.15) !important;
    }

    /* Grupo de botões de ação */
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: flex-start;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    /* Campos obrigatórios */
    .required-field::after {
        content: " *";
        color: #dc3545;
    }

    /* Ajustes para campos específicos */
    #resumo {
        resize: vertical;
        min-height: 100px;
    }

    #palavras_chave {
        background-color: var(--cinza-claro);
    }

    /* Responsividade específica */
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
            margin: 10px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        
        .action-buttons .btn {
            width: 100%;
            justify-content: center;
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
        }
    }

    @media (max-width: 576px) {
        .form-container {
            padding: 15px;
            margin: 5px;
        }
        
        .page-header-icon {
            width: 40px;
            height: 40px;
        }
        
        .page-header-icon i {
            font-size: 1.2rem;
        }
        
        .page-header h1 {
            font-size: 1.3rem;
        }
    }

    /* Estados de loading */
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
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .btn-back {
            /* Fundo translúcido suave com tom roxo e texto roxo visível */
            background: rgba(106, 13, 173, 0.06);
            border: 2px solid rgba(106, 13, 173, 0.15);
            color: var(--roxo-principal);
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            transition: all 0.25s ease;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(6px); /* deixa aspecto translúcido mais agradável sobre imagens */
        }

        .btn-back:hover {
            background: rgba(106, 13, 173, 0.12);
            color: var(--roxo-escuro);
            border-color: rgba(106, 13, 173, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(106, 13, 173, 0.12);
        }

        .btn-secondary {
            background: rgba(33, 37, 41, 0.08);
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

        /* Estilos para Tópicos */
        .topico-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .topico-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .topico-numero {
            background: var(--roxo-principal);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .btn-remover-topico {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .tabela-container {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .tabela-editor {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-editor th, .tabela-editor td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }

        .tabela-editor th {
            background: #e9ecef;
            font-weight: 600;
        }

        .tabela-editor input {
            border: none;
            width: 100%;
            padding: 4px;
            background: transparent;
        }

        .tabela-controls {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }

    </style>
</head>
<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu Hamburguer Functionality
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

    // Prevenir envio duplo do formulário
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('btn-loading')) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            }
        });
    }
});
</script>

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
            <a href="gerenciar_caminho.php" class="list-group-item">
                <i class="fas fa-plus-circle"></i> Adicionar Caminho
            </a>
            <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                <i class="fas fa-language"></i> Gerenciar Idiomas
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
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="page-header">
                <h1>
                    <div class="page-header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    Editar Teoria
                </h1>
                <a href="gerenciar_teorias.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar para Teorias
                </a>
            </div>

            <?php echo $mensagem; ?>

            <div class="form-container">
                <form action="editar_teoria.php?id=<?php echo htmlspecialchars($teoria_id); ?>" method="POST">
                    <!-- Campo Título -->
                    <div class="mb-4">
                        <label for="titulo" class="form-label required-field">Título da Teoria</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" 
                               value="<?php echo htmlspecialchars($teoria['titulo']); ?>" 
                               placeholder="Digite o título da teoria" required>
                    </div>

                    <!-- Campos em linha para Nível, Idioma e Ordem -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="nivel" class="form-label required-field">Nível</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="">Selecione o nível</option>
                                <option value="A1" <?php echo ($teoria['nivel'] == 'A1') ? 'selected' : ''; ?>>A1 - Iniciante</option>
                                <option value="A2" <?php echo ($teoria['nivel'] == 'A2') ? 'selected' : ''; ?>>A2 - Básico</option>
                                <option value="B1" <?php echo ($teoria['nivel'] == 'B1') ? 'selected' : ''; ?>>B1 - Intermediário</option>
                                <option value="B2" <?php echo ($teoria['nivel'] == 'B2') ? 'selected' : ''; ?>>B2 - Intermediário Avançado</option>
                                <option value="C1" <?php echo ($teoria['nivel'] == 'C1') ? 'selected' : ''; ?>>C1 - Avançado</option>
                                <option value="C2" <?php echo ($teoria['nivel'] == 'C2') ? 'selected' : ''; ?>>C2 - Proficiente</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="idioma" class="form-label required-field">Idioma</label>
                            <select class="form-select" id="idioma" name="idioma" required>
                                <option value="">Selecione o idioma</option>
                                <?php foreach ($idiomas_disponiveis as $idioma_opcao): ?>
                                    <option value="<?php echo htmlspecialchars($idioma_opcao); ?>" <?php echo (isset($teoria['idioma']) && $teoria['idioma'] == $idioma_opcao) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($idioma_opcao); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ordem" class="form-label required-field">Ordem de Exibição</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" 
                                   value="<?php echo htmlspecialchars($teoria['ordem']); ?>" 
                                   min="1" placeholder="1" required>
                            <div class="form-text">Ordem em que a teoria aparecerá na lista</div>
                        </div>
                    </div>

                    <!-- Campo Resumo -->
                    <div class="mb-4">
                        <label for="resumo" class="form-label">Resumo</label>
                        <textarea class="form-control" id="resumo" name="resumo" rows="3" 
                                  placeholder="Breve resumo da teoria (opcional)"><?php echo htmlspecialchars($teoria['resumo']); ?></textarea>
                        <div class="form-text">Resumo que aparecerá na lista de teorias</div>
                    </div>

                    <!-- Campo Palavras-chave -->
                    <div class="mb-4">
                        <label for="palavras_chave" class="form-label">Palavras-chave</label>
                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" 
                               value="<?php echo htmlspecialchars($teoria['palavras_chave']); ?>" 
                               placeholder="gramática, verbos, presente simples (opcional)">
                        <div class="form-text">Palavras-chave separadas por vírgula para facilitar a busca</div>
                    </div>

                    <!-- Tipo de Conteúdo -->
                    <div class="mb-3">
                        <label class="form-label">Tipo de Conteúdo</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_conteudo" id="textoLivre" value="texto" checked onchange="alterarTipoConteudo()">
                                <label class="form-check-label" for="textoLivre">
                                    <i class="fas fa-align-left me-2"></i>Texto Livre
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_conteudo" id="topicos" value="topicos" onchange="alterarTipoConteudo()">
                                <label class="form-check-label" for="topicos">
                                    <i class="fas fa-list me-2"></i>Tópicos
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Conteúdo Texto Livre -->
                    <div id="conteudoTexto" class="mb-4">
                        <label for="conteudo" class="form-label required-field">Conteúdo Completo</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="20" style="min-height: 400px;"><?php echo htmlspecialchars($teoria['conteudo']); ?></textarea>
                        <div class="form-text">Conteúdo completo da teoria.</div>
                    </div>

                    <!-- Conteúdo Tópicos -->
                    <div id="conteudoTopicos" class="mb-4" style="display: none;">
                        <label class="form-label required-field">Tópicos da Teoria</label>
                        <div id="listaTopicos">
                            <!-- Tópicos serão adicionados aqui -->
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="adicionarTopico()">
                                <i class="fas fa-plus me-2"></i>Adicionar Tópico
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="adicionarTabela()">
                                <i class="fas fa-table me-2"></i>Adicionar Tabela
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="adicionarTabelaEmTopico()">
                                <i class="fas fa-plus-square me-2"></i>Tabela em Tópico
                            </button>
                        </div>
                        <input type="hidden" id="topicosData" name="topicos_data">
                    </div>

                    <!-- Botões de Ação -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Atualizar Teoria
                        </button>
                        <a href="gerenciar_teorias.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let contadorTopicos = 0;
        let topicos = [];
        let contadorTabelas = 0;

        // Detectar tipo de conteúdo ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const conteudo = document.getElementById('conteudo').value;
            if (conteudo && /^\d+\..+/m.test(conteudo)) {
                document.getElementById('topicos').checked = true;
                alterarTipoConteudo();
                carregarTopicosExistentes(conteudo);
            }
        });

        // Alterar tipo de conteúdo
        function alterarTipoConteudo() {
            const tipoTexto = document.getElementById('textoLivre').checked;
            const conteudoTexto = document.getElementById('conteudoTexto');
            const conteudoTopicos = document.getElementById('conteudoTopicos');
            const campoConteudo = document.getElementById('conteudo');
            
            if (tipoTexto) {
                conteudoTexto.style.display = 'block';
                conteudoTopicos.style.display = 'none';
                campoConteudo.required = true;
            } else {
                conteudoTexto.style.display = 'none';
                conteudoTopicos.style.display = 'block';
                campoConteudo.required = false;
                if (topicos.length === 0) {
                    adicionarTopico();
                }
            }
        }

        // Carregar tópicos existentes
        function carregarTopicosExistentes(conteudo) {
            const linhas = conteudo.split('\n');
            let topicoAtual = null;
            
            linhas.forEach(linha => {
                linha = linha.trim();
                if (!linha) return;
                
                if (/^\d+\..+/.test(linha)) {
                    if (topicoAtual) {
                        topicos.push(topicoAtual);
                    }
                    const numero = linha.match(/^(\d+)\./)[1];
                    const titulo = linha.replace(/^\d+\.\s*/, '');
                    topicoAtual = { id: ++contadorTopicos, titulo, conteudo: '', tipo: 'texto' };
                } else {
                    if (topicoAtual) {
                        topicoAtual.conteudo += (topicoAtual.conteudo ? '\n' : '') + linha;
                    }
                }
            });
            
            if (topicoAtual) {
                topicos.push(topicoAtual);
            }
            
            renderizarTopicos();
        }

        // Adicionar novo tópico
        function adicionarTopico() {
            const novoTopico = {
                id: ++contadorTopicos,
                titulo: '',
                conteudo: '',
                tipo: 'texto'
            };
            topicos.push(novoTopico);
            renderizarTopicos();
        }

        // Adicionar tabela
        function adicionarTabela() {
            const novaTabela = {
                id: ++contadorTopicos,
                titulo: 'Tabela',
                tipo: 'tabela',
                linhas: 3,
                colunas: 3,
                dados: Array(3).fill().map(() => Array(3).fill('')),
                standalone: false,
                posicao: 'lado'
            };
            topicos.push(novaTabela);
            renderizarTopicos();
        }

        // Adicionar tabela em tópico existente
        function adicionarTabelaEmTopico() {
            const topicosTexto = topicos.filter(t => t.tipo === 'texto');
            if (topicosTexto.length === 0) {
                alert('Crie pelo menos um tópico antes de adicionar uma tabela.');
                return;
            }
            
            let opcoes = 'Escolha o tópico:\n';
            topicosTexto.forEach((topico, index) => {
                opcoes += `${index + 1}. ${topico.titulo || 'Tópico sem título'}\n`;
            });
            
            const escolha = prompt(opcoes + '\nDigite o número do tópico:');
            const indice = parseInt(escolha) - 1;
            
            if (indice >= 0 && indice < topicosTexto.length) {
                const topicoEscolhido = topicosTexto[indice];
                if (!topicoEscolhido.tabelas) {
                    topicoEscolhido.tabelas = [];
                }
                
                const novaTabela = {
                    id: Date.now(),
                    titulo: 'Tabela',
                    linhas: 3,
                    colunas: 3,
                    dados: Array(3).fill().map(() => Array(3).fill('')),
                    posicao: 'lado'
                };
                
                topicoEscolhido.tabelas.push(novaTabela);
                renderizarTopicos();
            }
        }

        // Remover tópico
        function removerTopico(id) {
            topicos = topicos.filter(t => t.id !== id);
            renderizarTopicos();
        }

        // Renderizar lista de tópicos
        function renderizarTopicos() {
            const container = document.getElementById('listaTopicos');
            container.innerHTML = '';
            
            topicos.forEach((item, index) => {
                if (item.tipo === 'texto') {
                    const topicoHTML = `
                        <div class="topico-item">
                            <button type="button" class="btn-remover-topico" onclick="removerTopico(${item.id})">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="topico-header">
                                <div class="topico-numero">${index + 1}</div>
                                <input type="text" class="form-control" placeholder="Título do tópico" 
                                       value="${item.titulo}" onchange="atualizarTopico(${item.id}, 'titulo', this.value)">
                            </div>
                            <textarea class="form-control" rows="4" placeholder="Conteúdo do tópico" 
                                      onchange="atualizarTopico(${item.id}, 'conteudo', this.value)">${item.conteudo}</textarea>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="adicionarTabelaNoTopico(${item.id})">
                                    <i class="fas fa-table me-1"></i>Adicionar Tabela
                                </button>
                            </div>
                            ${item.tabelas ? item.tabelas.map(tabela => `
                                <div class="mt-3 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <input type="text" class="form-control form-control-sm" value="${tabela.titulo}" 
                                               onchange="atualizarTabelaNoTopico(${item.id}, ${tabela.id}, 'titulo', this.value)" 
                                               placeholder="Título da tabela">
                                        <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removerTabelaDoTopico(${item.id}, ${tabela.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Posição da tabela:</label>
                                        <select class="form-select form-select-sm" onchange="atualizarTabelaNoTopico(${item.id}, ${tabela.id}, 'posicao', this.value)">
                                            <option value="antes" ${tabela.posicao === 'antes' ? 'selected' : ''}>Antes das informações</option>
                                            <option value="lado" ${tabela.posicao === 'lado' ? 'selected' : ''}>Ao lado das informações</option>
                                            <option value="depois" ${tabela.posicao === 'depois' ? 'selected' : ''}>Depois das informações</option>
                                        </select>
                                    </div>
                                    ${gerarTabelaEditor(tabela)}
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarLinhaTabela(${tabela.id})">
                                            <i class="fas fa-plus"></i> Linha
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarColunaTabela(${tabela.id})">
                                            <i class="fas fa-plus"></i> Coluna
                                        </button>
                                    </div>
                                </div>
                            `).join('') : ''}
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', topicoHTML);
                } else if (item.tipo === 'tabela') {
                    const tabelaHTML = `
                        <div class="topico-item">
                            <button type="button" class="btn-remover-topico" onclick="removerTopico(${item.id})">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="topico-header">
                                <div class="topico-numero"><i class="fas fa-table"></i></div>
                                <input type="text" class="form-control" placeholder="Título da tabela" 
                                       value="${item.titulo}" onchange="atualizarTopico(${item.id}, 'titulo', this.value)">
                            </div>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="standalone-${item.id}" 
                                           ${item.standalone ? 'checked' : ''} 
                                           onchange="atualizarTopico(${item.id}, 'standalone', this.checked)">
                                    <label class="form-check-label" for="standalone-${item.id}">
                                        Tabela independente (não dentro de tópico)
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label">Posição da tabela:</label>
                                    <select class="form-select form-select-sm" onchange="atualizarTopico(${item.id}, 'posicao', this.value)">
                                        <option value="antes" ${item.posicao === 'antes' ? 'selected' : ''}>Antes das informações</option>
                                        <option value="lado" ${item.posicao === 'lado' ? 'selected' : ''}>Ao lado das informações</option>
                                        <option value="depois" ${item.posicao === 'depois' ? 'selected' : ''}>Depois das informações</option>
                                    </select>
                                </div>
                            </div>
                            <div class="tabela-container">
                                ${gerarTabelaEditor(item)}
                                <div class="tabela-controls">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="adicionarLinha(${item.id})">
                                        <i class="fas fa-plus"></i> Linha
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="adicionarColuna(${item.id})">
                                        <i class="fas fa-plus"></i> Coluna
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', tabelaHTML);
                }
            });
            
            atualizarDadosTopicos();
        }

        // Gerar editor de tabela
        function gerarTabelaEditor(tabela) {
            let html = '<table class="tabela-editor">';
            
            for (let i = 0; i < tabela.linhas; i++) {
                html += '<tr>';
                for (let j = 0; j < tabela.colunas; j++) {
                    const valor = tabela.dados[i] && tabela.dados[i][j] ? tabela.dados[i][j] : '';
                    const tag = i === 0 ? 'th' : 'td';
                    html += `<${tag}><input type="text" value="${valor}" oninput="atualizarCelula(${tabela.id}, ${i}, ${j}, this.value)"></${tag}>`;
                }
                html += '</tr>';
            }
            
            html += '</table>';
            return html;
        }

        // Atualizar dados do tópico
        function atualizarTopico(id, campo, valor) {
            const item = topicos.find(t => t.id === id);
            if (item) {
                item[campo] = valor;
                atualizarDadosTopicos();
            }
        }

        // Atualizar célula da tabela
        function atualizarCelula(id, linha, coluna, valor) {
            // Procurar em tabelas dentro de tópicos
            for (let topico of topicos) {
                if (topico.tabelas) {
                    const tabela = topico.tabelas.find(t => t.id === id);
                    if (tabela) {
                        if (!tabela.dados) tabela.dados = [];
                        if (!tabela.dados[linha]) tabela.dados[linha] = [];
                        tabela.dados[linha][coluna] = valor;
                        atualizarDadosTopicos();
                        return;
                    }
                }
            }
            
            // Procurar em tabelas independentes
            const tabela = topicos.find(t => t.id === id && t.tipo === 'tabela');
            if (tabela) {
                if (!tabela.dados) tabela.dados = [];
                if (!tabela.dados[linha]) tabela.dados[linha] = [];
                tabela.dados[linha][coluna] = valor;
                atualizarDadosTopicos();
            }
        }

        // Adicionar linha à tabela
        function adicionarLinha(id) {
            const tabela = topicos.find(t => t.id === id);
            if (tabela && tabela.tipo === 'tabela') {
                tabela.linhas++;
                tabela.dados.push(Array(tabela.colunas).fill(''));
                renderizarTopicos();
            }
        }

        // Adicionar coluna à tabela
        function adicionarColuna(id) {
            const tabela = topicos.find(t => t.id === id);
            if (tabela && tabela.tipo === 'tabela') {
                tabela.colunas++;
                tabela.dados.forEach(linha => linha.push(''));
                renderizarTopicos();
            }
        }

        // Adicionar tabela diretamente no tópico
        function adicionarTabelaNoTopico(topicoId) {
            const topico = topicos.find(t => t.id === topicoId);
            if (topico) {
                if (!topico.tabelas) {
                    topico.tabelas = [];
                }
                
                const novaTabela = {
                    id: Date.now(),
                    titulo: 'Tabela',
                    linhas: 3,
                    colunas: 3,
                    dados: Array(3).fill().map(() => Array(3).fill('')),
                    posicao: 'lado'
                };
                
                topico.tabelas.push(novaTabela);
                renderizarTopicos();
            }
        }

        // Adicionar linha à tabela dentro do tópico
        function adicionarLinhaTabela(tabelaId) {
            for (let topico of topicos) {
                if (topico.tabelas) {
                    const tabela = topico.tabelas.find(t => t.id === tabelaId);
                    if (tabela) {
                        tabela.linhas++;
                        if (!tabela.dados) tabela.dados = [];
                        tabela.dados.push(Array(tabela.colunas).fill(''));
                        renderizarTopicos();
                        return;
                    }
                }
            }
            // Fallback para tabelas independentes
            const tabela = topicos.find(t => t.id === tabelaId);
            if (tabela && tabela.tipo === 'tabela') {
                tabela.linhas++;
                if (!tabela.dados) tabela.dados = [];
                tabela.dados.push(Array(tabela.colunas).fill(''));
                renderizarTopicos();
            }
        }

        // Adicionar coluna à tabela dentro do tópico
        function adicionarColunaTabela(tabelaId) {
            for (let topico of topicos) {
                if (topico.tabelas) {
                    const tabela = topico.tabelas.find(t => t.id === tabelaId);
                    if (tabela) {
                        tabela.colunas++;
                        if (!tabela.dados) tabela.dados = [];
                        for (let i = 0; i < tabela.linhas; i++) {
                            if (!tabela.dados[i]) tabela.dados[i] = [];
                            tabela.dados[i].push('');
                        }
                        renderizarTopicos();
                        return;
                    }
                }
            }
            // Fallback para tabelas independentes
            const tabela = topicos.find(t => t.id === tabelaId);
            if (tabela && tabela.tipo === 'tabela') {
                tabela.colunas++;
                if (!tabela.dados) tabela.dados = [];
                for (let i = 0; i < tabela.linhas; i++) {
                    if (!tabela.dados[i]) tabela.dados[i] = [];
                    tabela.dados[i].push('');
                }
                renderizarTopicos();
            }
        }

        // Remover tabela de um tópico
        function removerTabelaDoTopico(topicoId, tabelaId) {
            const topico = topicos.find(t => t.id === topicoId);
            if (topico && topico.tabelas) {
                topico.tabelas = topico.tabelas.filter(t => t.id !== tabelaId);
                renderizarTopicos();
            }
        }

        // Atualizar propriedades de tabela dentro de tópico
        function atualizarTabelaNoTopico(topicoId, tabelaId, campo, valor) {
            const topico = topicos.find(t => t.id === topicoId);
            if (topico && topico.tabelas) {
                const tabela = topico.tabelas.find(t => t.id === tabelaId);
                if (tabela) {
                    tabela[campo] = valor;
                    atualizarDadosTopicos();
                }
            }
        }

        // Atualizar campo hidden com dados dos tópicos
        function atualizarDadosTopicos() {
            document.getElementById('topicosData').value = JSON.stringify(topicos);
        }

        // Validar formulário antes do envio
        function validarFormulario(event) {
            if (document.getElementById('topicos').checked) {
                if (topicos.length === 0) {
                    event.preventDefault();
                    alert('Adicione pelo menos um tópico.');
                    return false;
                }
                
                // Gerar conteúdo final dos tópicos
                let conteudoFinal = '';
                topicos.forEach((item, index) => {
                    if (item.tipo === 'texto') {
                        conteudoFinal += `${index + 1}. ${item.titulo}\n${item.conteudo}\n`;
                        // Adicionar tabelas do tópico
                        if (item.tabelas && item.tabelas.length > 0) {
                            item.tabelas.forEach(tabela => {
                                conteudoFinal += `\n${tabela.titulo}:\n`;
                                for (let i = 0; i < tabela.linhas; i++) {
                                    let linha = '';
                                    for (let j = 0; j < tabela.colunas; j++) {
                                        const valor = tabela.dados[i] && tabela.dados[i][j] ? tabela.dados[i][j] : '';
                                        linha += valor + (j < tabela.colunas - 1 ? ' | ' : '');
                                    }
                                    conteudoFinal += linha + '\n';
                                }
                            });
                        }
                        conteudoFinal += '\n';
                    } else if (item.tipo === 'tabela') {
                        if (item.standalone) {
                            conteudoFinal += `TABELA: ${item.titulo}\n`;
                        } else {
                            conteudoFinal += `${index + 1}. ${item.titulo}\n`;
                        }
                        // Converter tabela para texto
                        for (let i = 0; i < item.linhas; i++) {
                            let linha = '';
                            for (let j = 0; j < item.colunas; j++) {
                                const valor = item.dados[i] && item.dados[i][j] ? item.dados[i][j] : '';
                                linha += valor + (j < item.colunas - 1 ? ' | ' : '');
                            }
                            conteudoFinal += linha + '\n';
                        }
                        conteudoFinal += '\n';
                    }
                });
                document.getElementById('conteudo').value = conteudoFinal;
            }
        }

        // Validação do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', validarFormulario);
            
            // Auto-hide success message after 5 seconds
            const mensagemSucesso = document.getElementById('mensagemSucesso');
            if (mensagemSucesso) {
                setTimeout(() => {
                    mensagemSucesso.style.transition = 'opacity 0.5s ease';
                    mensagemSucesso.style.opacity = '0';
                    setTimeout(() => {
                        mensagemSucesso.remove();
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>