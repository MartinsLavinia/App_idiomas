<?php
// pagina_adicionar_idiomas.php

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
            font-weight: 500;
            color: var(--roxo-principal);
            margin-bottom: 0.3rem;
            letter-spacing: 0.2px;
        }

        h5.card-title {
            color: var(--branco);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border-radius: 0.7rem;
            border: 1px solid var(--cinza-medio);
            background: var(--branco); /* inputs agora são brancos */
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 2px rgba(106,13,173,0.08);
        }

        .mb-3 {
            margin-bottom: 1.2rem !important;
        }

        .gap-2 {
            gap: .7rem !important;
        }

        .card-body {
            background: rgba(255,255,255,0.97);
            border-radius: 0 0 1.2rem 1.2rem;
            padding: 1.5rem;
        }

        .card.mb-3 {
            border-radius: 0.7rem;
            box-shadow: none;
            border: 1px solid var(--cinza-medio);
            background: var(--branco-azulado); /* fundo levemente branco azulado para container da pergunta */
        }

        .card.mb-3 .card-header {
            background: var(--roxo-escuro);
            color: var(--branco);
            border-radius: 0.7rem 0.7rem 0 0;
            font-size: 1.05rem;
            font-weight: 600;
            border: none;
        }

        .text-muted {
            color: #6c757d !important;
            font-size: 0.97rem;
        }

        /* PAGINAÇÃO ESTILIZADA */
        .pagination {
            --bs-pagination-padding-x: 0.8rem;
            --bs-pagination-padding-y: 0.5rem;
            --bs-pagination-font-size: 1rem;
        }

        .pagination .page-item .page-link {
            color: var(--roxo-principal);
            border: 1px solid var(--cinza-medio);
            border-radius: 0.7rem;
            margin: 0 0.18rem;
            background: var(--branco);
            font-weight: 500;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            box-shadow: 0 1px 4px rgba(106,13,173,0.04);
        }

        .pagination .page-item.active .page-link {
            z-index: 3;
            color: var(--branco);
            background: linear-gradient(90deg, var(--roxo-principal) 70%, var(--roxo-escuro) 100%);
            border-color: var(--roxo-principal);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(106, 13, 173, 0.13);
        }

        .pagination .page-item .page-link:hover:not(.disabled) {
            color: var(--roxo-escuro);
            background: var(--cinza-medio);
            border-color: var(--roxo-escuro);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background: var(--cinza-claro);
            border-color: var(--cinza-medio);
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-xl-10">
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
                            <p class="text-muted">Mostrando perguntas **<?php echo $offset_inicial; ?>** a **<?php echo min($offset_inicial + $limit - 1, $total_perguntas); ?>**.</p>
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
                                    <button type="submit" class="btn btn-success">Salvar Idioma e Quiz (Fim)</button>
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