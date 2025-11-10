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
        // Atualiza a teoria na tabela
        $sql_update = "UPDATE teorias SET titulo = ?, nivel = ?, ordem = ?, conteudo = ?, resumo = ?, palavras_chave = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("ssisssi", $titulo, $nivel, $ordem, $conteudo, $resumo, $palavras_chave, $teoria_id);
            
            if ($stmt_update->execute()) {
                $mensagem = '<div class="alert alert-success">Teoria atualizada com sucesso!</div>';
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
$sql_teoria = "SELECT titulo, nivel, ordem, conteudo, resumo, palavras_chave FROM teorias WHERE id = ?";
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
    <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- 
        ATENÇÃO, ADMINISTRADOR: 
        Para remover o aviso do TinyMCE, substitua 'YOUR_API_KEY' no link acima pela sua chave de API do TinyMCE.
        Você pode obter uma chave de API gratuita em https://www.tiny.cloud/auth/signup/
    -->
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

                    <!-- Campos em linha para Nível e Ordem -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="nivel" class="form-label required-field">Nível</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="">Selecione o nível</option>
                                <option value="Iniciante" <?php echo ($teoria['nivel'] == 'Iniciante') ? 'selected' : ''; ?>>Iniciante</option>
                                <option value="Intermediário" <?php echo ($teoria['nivel'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                                <option value="Avançado" <?php echo ($teoria['nivel'] == 'Avançado') ? 'selected' : ''; ?>>Avançado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
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

                    <!-- Campo Conteúdo -->
                    <div class="mb-4">
                        <label for="conteudo" class="form-label required-field">Conteúdo da Teoria</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="15" required><?php echo htmlspecialchars($teoria['conteudo']); ?></textarea>
                        <div class="form-text">Conteúdo completo da teoria. Você pode usar HTML para formatação.</div>
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
        // Inicializar TinyMCE para o campo de conteúdo
        tinymce.init({
            selector: '#conteudo',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            branding: false,
            statusbar: false
        });

        // Validação do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const titulo = document.getElementById('titulo').value.trim();
                const nivel = document.getElementById('nivel').value;
                const ordem = document.getElementById('ordem').value;
                const conteudo = tinymce.get('conteudo').getContent().trim();

                if (!titulo || !nivel || !ordem || !conteudo) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>