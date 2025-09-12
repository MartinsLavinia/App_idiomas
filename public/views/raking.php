<?php
// Simulação de dados de usuários
$users = [
    ["name" => "David", "experience" => 2000],
    ["name" => "Bob", "experience" => 1500],
    ["name" => "Alice", "experience" => 1200],
    ["name" => "Eve", "experience" => 1000],
    ["name" => "Charlie", "experience" => 900],
    ["name" => "Frank", "experience" => 850],
    ["name" => "Grace", "experience" => 780],
    ["name" => "Heidi", "experience" => 620],
    ["name" => "Ivan", "experience" => 550],
    ["name" => "Judy", "experience" => 490],
];

usort($users, function($a, $b) {
    return $b["experience"] - $a["experience"];
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Usuários</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">



    <style>
        /* Importação de fontes */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@300;400;500&display=swap');

:root {
    --primary-purple: #8a2be2;
    --accent-yellow: #ffd700;
    --dark-text: #333333;
    --light-background: #f8f8f8;
    --card-background: #ffffff;
    --border-light: #e0e0e0;
    --shadow-light: rgba(0, 0, 0, 0.08);
}

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--light-background);
    color: var(--dark-text);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
    box-sizing: border-box;
}

.container {
    background-color: var(--card-background);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 8px 20px var(--shadow-light);
    text-align: center;
    width: 100%;
    max-width: 700px;
    box-sizing: border-box;
}

.main-title {
    font-family: 'Poppins', sans-serif;
    color: var(--primary-purple);
    margin-bottom: 35px;
    font-size: 2.8em;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.ranking-list-wrapper {
    max-height: 600px; /* Altura máxima para rolagem */
    overflow-y: auto;
    padding-right: 10px; /* Espaço para a barra de rolagem */
}

.ranking-list-wrapper::-webkit-scrollbar {
    width: 8px;
}

.ranking-list-wrapper::-webkit-scrollbar-track {
    background: var(--light-background);
    border-radius: 10px;
}

.ranking-list-wrapper::-webkit-scrollbar-thumb {
    background: var(--primary-purple);
    border-radius: 10px;
}

#ranking-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ranking-item {
    background-color: var(--card-background);
    margin-bottom: 15px;
    padding: 18px 25px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    font-size: 1.1em;
    border: 1px solid var(--border-light);
    transition: all 0.3s ease-in-out;
    position: relative;
    overflow: hidden;
}

.ranking-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background-color: var(--primary-purple);
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
}

.ranking-item:hover {
    box-shadow: 0 6px 15px var(--shadow-light);
    transform: translateY(-3px);
}

.ranking-item:hover::before {
    transform: translateX(0);
}

.position-wrapper {
    width: 40px;
    text-align: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.position-number {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    color: var(--primary-purple);
    font-size: 1.3em;
}

.icon-medal {
    display: inline-block;
    width: 28px;
    height: 28px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
}

.icon-medal.gold {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffd700"><path d="M12 2L9.19 8.63L2 9.24L7.46 13.06L5.88 20.18L12 16.5L18.12 20.18L16.54 13.06L22 9.24L14.81 8.63L12 2Z"/></svg>');
}

.icon-medal.silver {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23c0c0c0"><path d="M12 2L9.19 8.63L2 9.24L7.46 13.06L5.88 20.18L12 16.5L18.12 20.18L16.54 13.06L22 9.24L14.81 8.63L12 2Z"/></svg>');
}

.icon-medal.bronze {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23cd7f32"><path d="M12 2L9.19 8.63L2 9.24L7.46 13.06L5.88 20.18L12 16.5L18.12 20.18L16.54 13.06L22 9.24L14.81 8.63L12 2Z"/></svg>');
}

.user-info {
    display: flex;
    align-items: center;
    flex-grow: 1;
    text-align: left;
}

.avatar {
    width: 45px;
    height: 45px;
    background-color: var(--border-light);
    border-radius: 50%;
    margin-right: 15px;
    border: 2px solid var(--primary-purple); /* Borda roxa para avatares */
    flex-shrink: 0;
}

.name {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    color: var(--dark-text);
    font-size: 1.2em;
}

.experience-info {
    display: flex;
    align-items: center; /* Alinha o ícone e o texto na mesma linha */
    margin-left: 20px;
    flex-shrink: 0;
}

.experience-value {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    color: var(--accent-yellow);
    font-size: 1.1em;
    display: flex;
    align-items: center;
}

.star-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffd700"><path d="M12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27Z"/></svg>');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    margin-right: 5px;
}

/* Remove the progress bar styles */
.progress-bar-container,
.progress-bar {
    display: none;
}

/* Responsividade */
@media (max-width: 768px) {
    .container {
        padding: 25px;
    }

    .main-title {
        font-size: 2em;
    }

    .ranking-item {
        flex-wrap: wrap;
        justify-content: center;
        text-align: center;
        padding: 15px;
    }

    .position-wrapper,
    .user-info,
    .experience-info {
        width: 100%;
        justify-content: center;
        margin: 5px 0;
    }

    .avatar {
        margin-right: 10px;
    }

    .name {
        font-size: 1.1em;
    }

    .experience-value {
        font-size: 1em;
    }
}


    </style>
</head>
<body>
    <div class="container">
        <h1 class="main-title">Ranking de Usuários</h1>
        <div class="ranking-list-wrapper">
            <ul id="ranking-list">
                <?php foreach ($users as $index => $user): ?>
                    <li class="ranking-item">
                        <div class="position-wrapper">
                            <?php if ($index == 0): ?>
                                <span class="material-icons" style="color: gold;">star</span>
                            <?php elseif ($index == 1): ?>
                                <span class="material-icons" style="color: silver;">star</span>
                            <?php elseif ($index == 2): ?>
                                <span class="material-icons" style="color: #cd7f32;">star</span>
                            <?php else: ?>
                                <span class="position-number"><?= $index + 1 ?>º</span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <div class="avatar"></div>
                            <span class="name"><?= $user["name"] ?></span>
                        </div>
                        <div class="experience-info">
                            <span class="experience-value"><span class="star-icon"></span><?= $user["experience"] ?> XP</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>