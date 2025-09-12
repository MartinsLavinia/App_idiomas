<?php
// Simulação de dados de usuários e suas pontuações
// Em um projeto real, estes dados viriam de um banco de dados
$usuarios_ranking = [
    ['nome' => 'Alice', 'experiencia' => 1500, 'posicao' => 1],
    ['nome' => 'Bob', 'experiencia' => 1250, 'posicao' => 2],
    ['nome' => 'Carlos', 'experiencia' => 1100, 'posicao' => 3],
    ['nome' => 'Diana', 'experiencia' => 950, 'posicao' => 4],
    ['nome' => 'Eduardo', 'experiencia' => 800, 'posicao' => 5],
    ['nome' => 'Fernanda', 'experiencia' => 750, 'posicao' => 6],
    ['nome' => 'Gabriel', 'experiencia' => 600, 'posicao' => 7],
    ['nome' => 'Helena', 'experiencia' => 550, 'posicao' => 8],
    ['nome' => 'Igor', 'experiencia' => 400, 'posicao' => 9],
    ['nome' => 'Julia', 'experiencia' => 300, 'posicao' => 10],
];

// Se precisar obter o nome do usuário logado (similar ao seu header):
// $nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário'; // Exemplo, dependendo da sua autenticação
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Idiomas</title>
    <link rel="stylesheet" href="estilos_gerais.css"> <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        /* Variáveis de cor globais (poderiam estar no seu CSS geral) */
        :root {
            --primary-bg: #2c3e50; /* Preto/Cinza escuro */
            --secondary-bg: #34495e; /* Cinza um pouco mais claro */
            --accent-purple: #6a0dad; /* Roxo vibrante */
            --accent-yellow: #ffd700; /* Amarelo dourado */
            --text-light: #ecf0f1; /* Branco/Cinza claro */
            --text-dark: #333; /* Preto para textos secundários */
            --border-color: rgba(255, 255, 255, 0.1);
            --hover-transition: 0.3s ease-in-out;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-light);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .main-content {
            margin-left: 280px; /* Ajuste conforme a largura do seu header */
            padding: 30px;
            transition: margin-left 0.5s ease-in-out;
        }

        .main-content.shifted { /* Classe adicionada ao body ou main */
            margin-left: 90px; /* Ajuste conforme a largura do header fechado */
        }

        /* Estilos da Página de Ranking */
        .ranking-container {
            max-width: 900px;
            margin: 40px auto;
            background-color: var(--secondary-bg);
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.7);
        }

        .ranking-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: var(--border-color);
        }

        .ranking-header h1 {
            font-size: 2.8rem;
            color: var(--accent-yellow);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .ranking-header p {
            font-size: 1.1rem;
            color: rgba(236, 240, 241, 0.8);
        }

        .ranking-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 15px;
            background-color: var(--primary-bg); /* Fundo mais escuro para os itens */
            border-radius: 10px;
            transition: background-color var(--hover-transition), transform 0.3s ease;
            cursor: pointer; /* Indica que é clicável */
            border: 1px solid transparent; /* Para efeito de hover */
        }

        .ranking-item:hover {
            background-color: var(--secondary-bg);
            transform: translateY(-5px);
            border-color: var(--accent-yellow); /* Borda amarela no hover */
        }

        .ranking-item .position {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-yellow);
            width: 50px; /* Largura fixa para a posição */
            text-align: center;
            margin-right: 25px;
            flex-shrink: 0; /* Evita que o número de posição encolha */
        }

        .ranking-item .user-info {
            display: flex;
            align-items: center;
            flex-grow: 1; /* Ocupa o espaço restante */
        }

        .ranking-item .profile-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent-purple); /* Roxo para os ícones de perfil */
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
            box-shadow: 0 0 10px var(--accent-purple);
        }

        .ranking-item .username {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-light);
        }

        .ranking-item .experience {
            font-size: 1rem;
            color: rgba(236, 240, 241, 0.8);
            font-weight: 500;
            margin-left: 20px;
            flex-shrink: 0;
        }

        .ranking-item .experience span {
            color: var(--accent-yellow);
            font-weight: bold;
        }

        /* Estilos para o topo do ranking (Top 3) */
        .ranking-item.top-3 {
            border-left: 5px solid var(--accent-yellow); /* Borda lateral amarela */
            background-color: var(--primary-bg); /* Mantém o fundo escuro para destaque */
        }

        .ranking-item.top-3 .position {
            color: var(--accent-yellow);
        }

        .ranking-item.top-3 .profile-icon {
            background-color: var(--accent-purple);
            box-shadow: 0 0 15px var(--accent-purple);
        }

        /* Adaptações para o cabeçalho e main-content em telas pequenas */
        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 20px;
            }
            .ranking-container {
                padding: 25px;
            }
            .ranking-header h1 {
                font-size: 2rem;
            }
            .ranking-item .position {
                font-size: 1.5rem;
                width: 40px;
                margin-right: 15px;
            }
            .ranking-item .profile-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                margin-right: 15px;
            }
            .ranking-item .username {
                font-size: 1rem;
            }
            .ranking-item .experience {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header id="myHeader">
        <div class="header-content">
            <img src="..\..\imagens\logo-idiomas.png" alt="Logo SpeakNut" class="logo">
            <div class="user-profile">
                <div class="profile-image">
                    <?php
                        // Exemplo de como mostrar a inicial do nome do usuário
                        $inicial_usuario = strtoupper(substr($nome_usuario, 0, 1));
                        echo htmlspecialchars($inicial_usuario);
                    ?>
                </div>
                <div class="username">
                    Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!
                </div>
                <a href="../../logout.php" class="logout-btn">Sair</a>
            </div>
            <nav>
                <ul>
                    <li>
                        <a href="painel.php" class="nav-link-item">
                            <span class="material-symbols-outlined">home</span>
                            <span>Início</span>
                        </a>
                    </li>
                    <li>
                        <a href="flashcards.php" class="nav-link-item">
                            <span class="material-symbols-outlined">cards_star</span>
                            <span>Flash Cards</span>
                        </a>
                    </li>
                    <li>
                        <a href="ranking.php" class="nav-link-item active"> <span class="material-symbols-outlined">leaderboard</span>
                            <span>Ranking</span>
                        </a>
                    </li>
                    </ul>
            </nav>
        </div>
        <button id="toggleButton" class="toggle-button">
            <span class="material-symbols-outlined">menu_open</span>
        </button>
    </header>

    <main class="main-content">
        <div class="ranking-container">
            <div class="ranking-header">
                <h1><span class="material-symbols-outlined">leaderboard</span> Ranking Global</h1>
                <p>Descubra os alunos com mais experiência e dedicação!</p>
            </div>
            <ul class="ranking-list">
                <?php foreach ($usuarios_ranking as $usuario): ?>
                    <?php
                        // Adiciona uma classe especial para os 3 primeiros colocados
                        $item_class = 'ranking-item';
                        if ($usuario['posicao'] <= 3) {
                            $item_class .= ' top-3';
                        }
                    ?>
                    <li class="<?php echo $item_class; ?>">
                        <div class="position"><?php echo htmlspecialchars($usuario['posicao']); ?>º</div>
                        <div class="user-info">
                            <div class="profile-icon">
                                <?php
                                    // Exemplo de como mostrar a inicial do nome do usuário
                                    $inicial_usuario_rank = strtoupper(substr($usuario['nome'], 0, 1));
                                    echo htmlspecialchars($inicial_usuario_rank);
                                ?>
                            </div>
                            <div class="username"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                        </div>
                        <div class="experience">
                            Pontos: <span><?php echo number_format($usuario['experiencia'], 0, ',', '.'); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </main>

    <script>
        // Script para alternar a classe 'closed' na sidebar
        const header = document.getElementById('myHeader');
        const toggleButton = document.getElementById('toggleButton');
        const body = document.body;
        const mainContent = document.querySelector('.main-content');

        toggleButton.addEventListener('click', () => {
            header.classList.toggle('closed');
            body.classList.toggle('shifted'); // Aplica a classe no body para ajustar margem
            mainContent.classList.toggle('shifted'); // Aplica a classe no main-content para ajustar margem
        });

        // Adiciona ou remove classes de 'shifted' no load da página se o header já estiver fechado (exemplo)
        // Se você tiver uma lógica de persistência, aplique aqui.
        // if (header.classList.contains('closed')) {
        //     body.classList.add('shifted');
        //     mainContent.classList.add('shifted');
        // }
    </script>
</body>
</html>