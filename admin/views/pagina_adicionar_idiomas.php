<?php
// pagina_adicionar_idiomas.php
session_start();
include_once __DIR__ . '/../../conexao.php';

$database = new Database();
$conn = $database->conn;
// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;
// 1. Configurações de Paginação
$limit = 5; // Limite de perguntas por página
$total_perguntas = 20; // Total de perguntas a serem exibidas
$total_paginas = ceil($total_perguntas / $limit); // 20 / 5 = 4 páginas

// Pega o número da página atual da URL (GET), padrão é 1
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Garante que a página atual esteja dentro dos limites válidos
if ($pagina_atual < 1) {
    $pagina_atual = 1;
} elseif ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

// Calcula o índice inicial (offset) para o loop (ex: Pág 1 começa em 1, Pág 2 em 6)
$offset_inicial = ($pagina_atual - 1) * $limit + 1;

// Se necessário, inclua arquivos de configuração, autenticação, etc.
// include_once '../../config.php';
// include_once '../../auth.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Idioma Completo - Página <?php echo $pagina_atual; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
    <style>
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

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: var(--cinza-claro);
            color: var(--preto-texto);
            min-height: 100vh;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* Barra de Navegação - MODIFICADA PARA TRANSPARENTE COM DECORAÇÃO AMARELA */
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

        .btn-outline-warning {
            color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-warning:hover {
            background-color: var(--amarelo-detalhe);
            box-shadow: 0 4px 8px rgba(235, 183, 14, 0.77);
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


/*-- */

.main-content {
    margin-left: 250px;
    padding: 20px;
}

@media (max-width: 992px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
    }
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

        /* ESTILOS EXISTENTES DO SEGUNDO CÓDIGO (MANTIDOS) */
        .card {
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px rgba(106,13,173,0.07);
            border: none;
            background: var(--branco);
            transition: box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 8px 32px rgba(106,13,173,0.12);
        }

        .card-header {
            background: linear-gradient(90deg, var(--roxo-principal) 70%, var(--roxo-escuro) 100%);
            color: var(--branco);
            border-radius: 1.2rem 1.2rem 0 0;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: none;
        }

        .btn-secondary {
            background: var(--cinza-medio);
            color: var(--preto-texto);
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-secondary:hover {
            background: var(--cinza-escuro);
        }

        .btn-success {
            background: var(--amarelo-detalhe);
            color: var(--preto-texto);
            border: none;
            font-weight: 600;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(255,215,0,0.08);
            transition: background 0.2s;
        }

        .btn-success:hover {
            background: #e6c200;
            color: var(--preto-texto);
        }

        label {
            font-weight: 600;
            color: var(--roxo-escuro);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        h5.card-title {
            color: var(--branco);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid var(--cinza-medio);
            border-bottom: 2px solid var(--cinza-escuro);
            background: var(--branco);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s, border-bottom 0.3s;
            padding: 0.6rem 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal);
            border-bottom: 3px solid var(--roxo-principal);
            box-shadow: none;
            background: #f6f8fc;
        }
        
        textarea.form-control {
            min-height: 80px;
        }

        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        .gap-2 {
            gap: .7rem !important;
        }

        .card-body {
            background: rgba(255,255,255,0.97);
            border-radius: 0 0 1.2rem 1.2rem;
            padding: 1.8rem;
        }

        .card.mb-3 {
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--cinza-medio);
            background: var(--branco);
            overflow: hidden;
        }

        .card.mb-3 .card-header {
            background: var(--roxo-principal);
            color: var(--branco);
            border-radius: 0.5rem 0.5rem 0 0;
            font-size: 1.15rem;
            font-weight: 700;
            border-bottom: 3px solid var(--amarelo-detalhe);
            padding: 0.8rem 1.25rem;
        }

        .text-muted {
            color: var(--roxo-escuro) !important;
            font-size: 0.95rem;
            font-style: italic;
        }

        .pagination {
            --bs-pagination-padding-x: 0.8rem;
            --bs-pagination-padding-y: 0.5rem;
            --bs-pagination-font-size: 1rem;
        }

        .pagination .page-item .page-link {
            color: var(--roxo-principal);
            border: 1px solid var(--cinza-medio);
            border-radius: 0.5rem;
            margin: 0 0.2rem;
            background: var(--branco);
            font-weight: 500;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            box-shadow: none;
        }

        .pagination .page-item.active .page-link {
            z-index: 3;
            color: var(--branco);
            background: var(--roxo-principal);
            border-color: var(--roxo-escuro);
            font-weight: 600;
            box-shadow: none;
        }

        .pagination .page-item .page-link:hover:not(.disabled) {
            color: var(--roxo-principal);
            background: #f6f8fc;
            border-color: var(--roxo-principal);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background: var(--cinza-claro);
            border-color: var(--cinza-medio);
            cursor: not-allowed;
            opacity: 0.7;
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
        <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
        <small>Administrador(a)</small>
    </div>

    <div class="list-group">
        <a href="gerenciar_caminho.php" class="list-group-item">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item active">
            <i class="fas fa-globe"></i> Gerenciar Idiomas
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
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <h2 class="mb-4">Gerenciar idiomas simples</h2>

                        <a href="#" class="btn btn-warning mb-4" data-bs-toggle="modal" data-bs-target="#gerenciarIdiomasModal">
                            <i class="fas fa-plus-circle me-2"></i>Gerenciar idiomas
                        </a>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Adicionar Novo Idioma com Quiz (Página <?php echo $pagina_atual . ' de ' . $total_paginas; ?>)</h5>
                        </div>
                        <div class="card-body">
                            <form action="adicionar_idioma_completo.php?page=<?php echo $pagina_atual + 1; ?>" method="POST">
                                <?php if ($pagina_atual === 1): ?>
                                <div class="mb-3">
                                    <label for="idioma_novo_completo" class="form-label">Nome do Idioma</label>
                                    <input type="text" class="form-control" id="idioma_novo_completo" name="idioma" placeholder="Ex: Espanhol" required>
                                </div>
                                <hr>
                                <?php endif; ?>

                                <h5>Perguntas do Quiz de Nivelamento (Total: 20 perguntas)</h5>
                                
                                <p class="text-muted">A resposta correta para cada pergunta deve ser "A", "B" ou "C".</p>
                                
                                <?php for ($i = $offset_inicial; $i < $offset_inicial + $limit && $i <= $total_perguntas; $i++): ?>
                                <div class="card mb-3">
                                    <div class="card-header">Pergunta #<?php echo $i; ?></div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="pergunta_<?php echo $i; ?>" class="form-label">Pergunta</label>
                                            <textarea class="form-control" id="pergunta_<?php echo $i; ?>" name="pergunta_<?php echo $i; ?>" rows="2" required></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="opcao_a_<?php echo $i; ?>" class="form-label">Opção A</label>
                                                <input type="text" class="form-control" id="opcao_a_<?php echo $i; ?>" name="opcao_a_<?php echo $i; ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="opcao_b_<?php echo $i; ?>" class="form-label">Opção B</label>
                                                <input type="text" class="form-control" id="opcao_b_<?php echo $i; ?>" name="opcao_b_<?php echo $i; ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="opcao_c_<?php echo $i; ?>" class="form-label">Opção C</label>
                                                <input type="text" class="form-control" id="opcao_c_<?php echo $i; ?>" name="opcao_c_<?php echo $i; ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="resposta_correta_<?php echo $i; ?>" class="form-label">Resposta Correta</label>
                                            <select id="resposta_correta_<?php echo $i; ?>" name="resposta_correta_<?php echo $i; ?>" class="form-select" required>
                                                <option value="">Selecione a resposta correta</option>
                                                <option value="A">Opção A</option>
                                                <option value="B">Opção B</option>
                                                <option value="C">Opção C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                                
                                <hr>
                                
                                <nav aria-label="Navegação de Páginas">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagina_atual - 1; ?>">Anterior</a>
                                        </li>
                                        
                                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                                        <li class="page-item <?php echo ($p == $pagina_atual) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $pagina_atual + 1; ?>">Próximo</a>
                                        </li>
                                    </ul>
                                </nav>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="submit" class="btn btn-warning">Salvar Idioma e Quiz (Fim)</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Gerenciar Idiomas -->
        <div class="modal fade" id="gerenciarIdiomasModal" tabindex="-1" aria-labelledby="gerenciarIdiomasModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="gerenciarIdiomasModalLabel">Gerenciar Idiomas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Formulário para adicionar idioma simples -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">➕ Adicionar Novo Idioma (Simples)</h6>
                            </div>
                            <div class="card-body">
                                <form action="adicionar_idioma_simples.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="nome_idioma" placeholder="Nome do idioma (ex: Alemão)" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-success w-100">Adicionar</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Adiciona apenas o idioma. Você pode criar o quiz depois.</small>
                                </form>
                            </div>
                        </div>

                        <p class="text-muted">Use o botão "Adicionar Novo Idioma com Quiz" para criar um novo idioma completo com quiz de nivelamento.</p>

                        <h5>Idiomas Existentes</h5>
                        <ul class="list-group">
                            <?php if (!empty($idiomas_db)): ?>
                            <?php foreach ($idiomas_db as $idioma): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($idioma['idioma']); ?></span>
                                <div>
                                    <a href="gerenciador_quiz_nivelamento.php?idioma=<?php echo urlencode($idioma['idioma']); ?>" class="btn btn-info btn-sm me-2">Gerenciar Quiz</a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                        data-id="<?php echo urlencode($idioma['idioma']); ?>" data-nome="<?php echo htmlspecialchars($idioma['idioma']); ?>" data-tipo="idioma" data-action="excluir_idioma.php">
                                        Excluir
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <li class="list-group-item text-center">Nenhum idioma encontrado.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmação de Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="confirmDeleteModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form id="deleteForm" method="POST" action="">
                            <input type="hidden" name="id" id="deleteItemId">
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Notificação -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notificationModalLabel">Notificação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="notificationModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>