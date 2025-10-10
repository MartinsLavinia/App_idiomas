<?php
// pagina_adicionar_idiomas.php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}


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
    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --cinza-claro: #f8f9fa;
            --cinza-medio: #dee2e6;
            --cinza-escuro: #b0b3b8;
            --preto-texto: #212529;
            --branco: #fff;
            --branco-azulado: #f6f8fc;
            --sombra-suave: 0 4px 24px rgba(106,13,173,0.07);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: var(--cinza-claro);
            color: var(--preto-texto);
            min-height: 100vh;
        }

        .card {
            border-radius: 1.2rem;
            box-shadow: var(--sombra-suave);
            border: none;
            background: var(--branco);
            transition: box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 8px 32px rgba(106,13,173,0.12);
        }

        .card-header {
            /* MANTIDO - GRADIENTE ROXO PARA O CARD PRINCIPAL */
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
            /* MANTIDO - COR AMARELO DETALHE */
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

        /* ALTERAÇÃO: Labels mais escuros e com mais contraste */
        label {
            font-weight: 600;
            color: var(--roxo-escuro); /* Mudança para roxo mais escuro */
            margin-bottom: 0.5rem; /* Aumenta o espaçamento abaixo */
            letter-spacing: 0.5px;
            text-transform: uppercase; /* Mais destaque */
            font-size: 0.85rem;
        }

        h5.card-title {
            color: var(--branco);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* ALTERAÇÃO: Inputs/Selects com borda inferior de foco (sublinhado) */
        .form-control, .form-select {
            border-radius: 0.5rem; /* Levemente menos arredondado */
            border: 1px solid var(--cinza-medio);
            border-bottom: 2px solid var(--cinza-escuro); /* Borda inferior mais marcada */
            background: var(--branco);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s, border-bottom 0.3s;
            padding: 0.6rem 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal); /* Borda lateral em roxo */
            border-bottom: 3px solid var(--roxo-principal); /* Destaque na borda inferior em roxo */
            box-shadow: none; /* Remove a sombra do focus para um visual mais flat */
            background: var(--branco-azulado); /* Fundo levemente azulado no focus */
        }
        
        /* Ajuste para o textarea (pergunta) */
        textarea.form-control {
            min-height: 80px;
        }

        .mb-3 {
            margin-bottom: 1.5rem !important; /* Aumenta o espaçamento entre campos */
        }

        .gap-2 {
            gap: .7rem !important;
        }

        .card-body {
            background: rgba(255,255,255,0.97);
            border-radius: 0 0 1.2rem 1.2rem;
            padding: 1.8rem; /* Aumenta o padding do body principal */
        }

        /* ALTERAÇÃO: Card da Pergunta (Card Interno) - Minimalista e Focado */
        .card.mb-3 {
            border-radius: 0.5rem; /* Menos arredondado */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Sombra mais sutil e definida */
            border: 1px solid var(--cinza-medio);
            background: var(--branco); /* Fundo branco para contraste com o cinza-claro do body */
            overflow: hidden; /* Garantir que a borda de cima seja bem definida */
        }

        /* ALTERAÇÃO: Header do Card da Pergunta - Cor Sólida Roxo Principal */
        .card.mb-3 .card-header {
            background: var(--roxo-principal); /* Cor sólida principal */
            color: var(--branco);
            border-radius: 0.5rem 0.5rem 0 0; /* Menos arredondado */
            font-size: 1.15rem; /* Levemente maior */
            font-weight: 700; /* Mais negrito */
            border-bottom: 3px solid var(--amarelo-detalhe); /* Borda amarela de destaque */
            padding: 0.8rem 1.25rem;
        }

        .text-muted {
            color: var(--roxo-escuro) !important; /* Text-muted mais escuro para melhor legibilidade */
            font-size: 0.95rem;
            font-style: italic;
        }

        /* PAGINAÇÃO ESTILIZADA (Levemente ajustada para consistência) */
        .pagination {
            --bs-pagination-padding-x: 0.8rem;
            --bs-pagination-padding-y: 0.5rem;
            --bs-pagination-font-size: 1rem;
        }

        .pagination .page-item .page-link {
            color: var(--roxo-principal);
            border: 1px solid var(--cinza-medio);
            border-radius: 0.5rem; /* Menos arredondado */
            margin: 0 0.2rem;
            background: var(--branco);
            font-weight: 500;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            box-shadow: none; /* Remove a sombra da paginação */
        }

        .pagination .page-item.active .page-link {
            z-index: 3;
            color: var(--branco);
            background: var(--roxo-principal); /* Cor sólida no ativo */
            border-color: var(--roxo-escuro);
            font-weight: 600;
            box-shadow: none;
        }

        .pagination .page-item .page-link:hover:not(.disabled) {
            color: var(--roxo-principal);
            background: var(--branco-azulado); /* Usa o azulado para hover */
            border-color: var(--roxo-principal);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background: var(--cinza-claro);
            border-color: var(--cinza-medio);
            cursor: not-allowed;
            opacity: 0.7;
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
                <i class="fas fa-user-circle"></i>
                <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
                <small>Administrador(a)</small>
            </div>

            <div class="list-group">
                <a href="gerenciar_caminho.php" class="list-group-item">
                    <i class="fas fa-plus-circle"></i> Adicionar Caminho
                </a>
             
                <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                    <i class="fas fa-language "></i> Gerenciar Idiomas
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>