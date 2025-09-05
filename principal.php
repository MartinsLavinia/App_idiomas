<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeakNut - Plataforma de Aprendizado de Idiomas</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body,
    html {
        height: 100%;
        width: 100%;
        font-family: 'Arial', sans-serif;
        overflow-x: hidden;
    }

    /* Header */
    .header {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 100;
    }

    .header a {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 20px;
        font-size: 16px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-left: 10px;
        font-family: Arial, Helvetica, sans-serif;
        cursor: pointer;
    }

    .header a.cadastre-btn {
        background-color: #260e50;
        color: #ffffff;
    }

    .header a.cadastre-btn:hover {
        background-color: #1b063f;
        color: #ffffff;
    }

    .header a.login-btn {
        background-color: transparent;
        color: #260e50;

    }

    .header a.login-btn:hover {
        color: #ffffff;
    }

    /* Logo fixa */
    .logo {
        position: fixed;
        top: 20px;
        left: 30px;
        z-index: 100;
    }

    .logo img {
        width: 200px;
        height: auto;
    }

    /* Seção 1 - Página de boas-vindas */
    .section-welcome {
        display: flex;
        height: 100vh;
        width: 100%;
    }

    .metade-logo {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        padding: 40px;
    }

    .logo-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .logo-section img {
        max-width: 500px;
        height: auto;
        margin-bottom: 20px;
    }

    .welcome-text {
        font-size: 36px;
        font-weight: bold;
        color: #1f2937;
        margin-bottom: 20px;
        text-align: center;
        line-height: 1.2;
    }

    .subtitle {
        font-size: 18px;
        color: #6b7280;
        margin-bottom: 40px;
        text-align: center;
    }

    .cta-button {
        background: linear-gradient(135deg, #7c3aed, #a855f7);
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 25px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
    }

    .cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
    }

    .metade-fundo {
        flex: 1;
        background: linear-gradient(135deg, #7c3aed, #e385ec);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .mascot {
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, #ffe9a8, #f7c840);
        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
        position: relative;
        animation: float 3s ease-in-out infinite;
    }

    .mascot::before {
        content: "🐿️";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 120px;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    /* Seção 2 - Página principal com carrossel */
    .section-main {
        min-height: 100vh;
        background: linear-gradient(135deg, #7c3aed, #e385ec);
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px 60px;
    }

    .conteudo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 80%;
        max-width: 1200px;
        gap: 80px;
        z-index: 1;
        margin-bottom: 60px;
    }

    .carousel-container {
        width: 400px;
        overflow: hidden;
        border-radius: 15px;
        position: relative;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .carousel {
        display: flex;
        transition: transform 0.7s ease-in-out;
    }

    .carousel img {
        width: 100%;
        height: auto;
        flex-shrink: 0;
        border-radius: 15px;
    }

    .arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        font-size: 2rem;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 5px;
        z-index: 2;
    }

    .arrow:hover {
        background: rgba(0, 0, 0, 0.7);
    }

    .arrow-left {
        left: 10px;
    }

    .arrow-right {
        right: 10px;
    }

    .texto-principal {
        flex: 1;
        color: white;
        font-family: 'Montserrat', sans-serif;
        align-self: flex-end;
        margin-right: auto;
        margin-left: 50px;
    }

    .titulo-principal {
        font-size: 60px;
        font-weight: 700;
        margin-bottom: 20px;
        line-height: 1.2;
    }

    .titulo-destaque {
        color: yellow;
        font-weight: 900;
    }

    /* Benefícios integrados na seção 2 */
    .benefits-container {
        max-width: 1200px;
        width: 80%;
        text-align: center;
        color: white;
        font-family: 'Montserrat', sans-serif;
        z-index: 1;
    }

    .benefits-box {
        background: rgba(255, 255, 255, 0.12);
        padding: 40px 60px;
        border-radius: 20px;
        margin-bottom: 30px;
    }

    .benefits-title {
        font-size: 2rem;
        margin-bottom: 15px;
        color: yellow;
    }

    .benefits-description {
        font-size: 1.2rem;
        line-height: 1.7;
        margin-bottom: 20px;
    }

    .benefits-list {
        list-style: none;
        padding: 0;
        font-size: 1.1rem;
        line-height: 1.8;
        text-align: left;
        max-width: 700px;
        margin: auto;
    }

    .benefits-list li {
        margin-bottom: 10px;
    }

    .cta-final {
        background: yellow;
        padding: 15px 40px;
        border-radius: 35px;
        font-weight: bold;
        border: none;
        color: black;
        font-size: 1.2rem;
        cursor: pointer;
        margin-top: 20px;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        transition: all 0.3s ease;
    }

    .cta-final:hover {
        transform: translateY(-2px);
        box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.4);
    }

    /* Mascote na seção principal */
    .mascote {
        position: absolute;
        bottom: 40px;
        right: 160px;
        width: 250px;
        height: auto;
        z-index: 10;
    }

    /* Nova Seção 3 - Unidades de Aprendizado */
    .section-units {
        min-height: 100vh;
        background: linear-gradient(135deg, #4a5fdc, #b794f6);
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px;
        overflow: hidden;
    }

    /* Mascote da seção units */
    .units-mascot {
        position: absolute;
        top: 60px;
        left: 60px;
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        z-index: 10;
        animation: float 3s ease-in-out infinite;
    }

    .units-mascot::after {
        content: "🐿️";
        font-size: 40px;
    }

    /* Elementos decorativos ao redor do mascote */
    .mascot-decoration {
        position: absolute;
        top: 50px;
        left: 150px;
        z-index: 9;
    }

    .decoration-line {
        width: 30px;
        height: 3px;
        background: #ffd700;
        margin: 5px 0;
        border-radius: 2px;
        animation: decorationPulse 2s ease-in-out infinite;
    }

    .decoration-line:nth-child(2) {
        animation-delay: 0.3s;
    }

    .decoration-line:nth-child(3) {
        animation-delay: 0.6s;
    }

    @keyframes decorationPulse {

        0%,
        100% {
            opacity: 0.5;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.1);
        }
    }

    .section-units::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
            radial-gradient(circle at 40% 60%, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 50px 50px, 30px 30px, 70px 70px;
        animation: sparkle 4s ease-in-out infinite;
    }

    @keyframes sparkle {

        0%,
        100% {
            opacity: 0.3;
        }

        50% {
            opacity: 0.8;
        }
    }

    .units-content {
        max-width: 1000px;
        width: 90%;
        text-align: center;
        z-index: 1;
    }

    .units-title {
        font-size: 2.5rem;
        color: white;
        font-family: 'Montserrat', sans-serif;
        font-weight: 400;
        margin-bottom: 15px;
        line-height: 1.3;

    }

    .units-title-highlight {
        color: #ffd700;
        font-weight: 700;
    }

    .units-subtitle {
        font-size: 1.3rem;
        color: white;
        font-family: 'Montserrat', sans-serif;
        font-weight: 300;
        margin-bottom: 50px;
        opacity: 0.9;
    }

    .units-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 40px;
    }

    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
    }

    .dot.active {
        background: #ffd700;
    }

    .content-box {
        background: white;
        border-radius: 25px;
        padding: 60px 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        margin: 0 auto;
        max-width: 800px;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .content-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #4a5fdc, #b794f6, #ffd700);
    }

    .content-carousel {
        width: 100%;
        position: relative;
    }

    .content-slide {
        display: none;
        text-align: center;
        font-family: 'Montserrat', sans-serif;
        animation: fadeIn 0.5s ease-in-out;
    }

    .content-slide.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .content-slide h3 {
        color: #333;
        font-size: 1.5rem;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .content-slide p {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .content-placeholder {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.6;
        text-align: center;
        font-family: 'Montserrat', sans-serif;
    }

    .content-placeholder h3 {
        color: #333;
        font-size: 1.5rem;
        margin-bottom: 20px;
        font-weight: 600;
    }

    /* Elementos decorativos para a nova seção */
    .units-decorative {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        animation: floatShape 6s ease-in-out infinite;
    }

    .shape1 {
        width: 120px;
        height: 120px;
        top: 15%;
        left: 8%;
        animation-delay: 0s;
    }

    .shape2 {
        width: 80px;
        height: 80px;
        top: 25%;
        right: 12%;
        animation-delay: 2s;
    }

    .shape3 {
        width: 100px;
        height: 100px;
        bottom: 20%;
        left: 15%;
        animation-delay: 4s;
    }

    .shape4 {
        width: 60px;
        height: 60px;
        bottom: 30%;
        right: 20%;
        animation-delay: 1s;
    }

    @keyframes floatShape {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.6;
        }
    }

    /* Elementos decorativos */
    .decorative-elements {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }

    .circle1 {
        width: 100px;
        height: 100px;
        top: 20%;
        left: 10%;
        animation: pulse 4s ease-in-out infinite;
    }

    .circle2 {
        width: 60px;
        height: 60px;
        top: 70%;
        right: 20%;
        animation: pulse 3s ease-in-out infinite reverse;
    }

    .circle3 {
        width: 80px;
        height: 80px;
        bottom: 15%;
        left: 15%;
        animation: pulse 5s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 0.3;
            transform: scale(1);
        }

        50% {
            opacity: 0.6;
            transform: scale(1.1);
        }
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .section-welcome {
            flex-direction: column;
        }

        .metade-logo,
        .metade-fundo {
            flex: none;
            height: 50vh;
        }

        .welcome-text {
            font-size: 28px;
        }

        .mascot {
            width: 200px;
            height: 200px;
        }

        .mascot::before {
            font-size: 80px;
        }

        .conteudo {
            flex-direction: column;
            gap: 40px;
            width: 95%;
            margin-bottom: 40px;
        }

        .carousel-container {
            width: 100%;
            max-width: 350px;
        }

        .titulo-principal {
            font-size: 36px;
            text-align: left;
            padding-left: 50px;


        }

        .benefits-box {
            padding: 30px 20px;
        }

        .mascote {
            display: none;
        }

        .header {
            top: 10px;
            right: 10px;
        }

        .header a {
            padding: 8px 16px;
            font-size: 14px;
        }

        .logo {
            top: 10px;
            left: 10px;
        }

        .logo img {
            width: 150px;
        }

        /* Responsividade para nova seção */
        .units-title {
            font-size: 1.8rem;
        }

        .units-subtitle {
            font-size: 1.1rem;
        }

        .content-box {
            padding: 40px 20px;
            margin: 0 20px;
        }

        .content-placeholder h3 {
            font-size: 1.3rem;
        }

        .content-placeholder {
            font-size: 1rem;
        }

        .units-mascot {
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
        }

        .units-mascot::after {
            font-size: 30px;
        }

        .mascot-decoration {
            top: 15px;
            left: 90px;
        }

        .decoration-line {
            width: 20px;
            height: 2px;
        }
    }

    /* Scroll suave */
    html {
        scroll-behavior: smooth;
    }

    /* Botão de scroll */
    .scroll-button {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid white;
        border-radius: 50px;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        animation: bounce 2s infinite;
    }

    .scroll-button:hover {
        background: white;
        color: #7c3aed;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateX(-50%) translateY(0);
        }

        40% {
            transform: translateX(-50%) translateY(-10px);
        }

        60% {
            transform: translateX(-50%) translateY(-5px);
        }
    }
    </style>
</head>

<body>
    <!-- Header fixo -->
    <div class="header">
        <a href="public/views/index.php" class="cadastre-btn">Cadastre-se</a>
        <a href="public/views/login.php" class="login-btn">Entrar</a>

    </div>

    <!-- Logo fixa -->
    <div class="logo">
        <img src="menorteste.png" alt="Logo SpeakNut">
    </div>

    <!-- Seção 1: Boas-vindas -->
    <section class="section-welcome" id="welcome">
        <div class="metade-logo">
            <div class="logo-section">
                <img src="logo-idiomas.png" alt="Logo SpeakNut">
                <div class="welcome-text">Seja bem-vindo ao SpeakNut</div>
                <div class="subtitle">
                    Sua nova plataforma<br>
                    de aprendizado de idiomas
                </div>
                <button class="cta-button" onclick="scrollToSection('main')">Descubra mais</button>
            </div>
        </div>

        <div class="metade-fundo">
            <div class="decorative-elements">
                <div class="circle circle1"></div>
                <div class="circle circle2"></div>
            </div>
            <div class="mascot"></div>

        </div>
    </section>

    <!-- Seção 2: Conteúdo principal -->
    <section class="section-main" id="main">
        <div class="decorative-elements">
            <div class="circle circle1"></div>
            <div class="circle circle2"></div>
            <div class="circle circle3"></div>
        </div>


        <div class="texto-principal">
            <h1 class="titulo-principal">
                Aprenda com o SpeakNut<br>
                <span class="titulo-destaque">ONDE ESTIVER</span>
            </h1>
        </div>
        </div>

        <!-- Seção de benefícios integrada -->
        <div class="benefits-container">
            <div class="benefits-box">
                <h2 class="benefits-title">Por que escolher o SpeakNut?</h2>
                <p class="benefits-description">
                    Nossa plataforma de aprendizado de idiomas foi criada para ajudar você a estudar de forma prática e
                    divertida. Com aulas interativas, exercícios personalizados e suporte em tempo real, você aprende no
                    seu
                    ritmo, de qualquer lugar do mundo.
                </p>
                <ul class="benefits-list">
                    <li>✔️ Aulas dinâmicas e personalizadas</li>
                    <li>✔️ Professores nativos disponíveis</li>
                    <li>✔️ Exercícios interativos e gamificação</li>
                    <li>✔️ Estude no computador, celular ou tablet</li>
                </ul>
            </div>

            <button class="cta-final" onclick="scrollToSection('units')">
                Comece agora gratuitamente 🚀
            </button>
        </div>

        <img src="mascotee.png" alt="Mascote SpeakNut" class="mascote">
    </section>

    <!-- Nova Seção 3: Unidades de Aprendizado -->
    <section class="section-units" id="units">


        <div class="units-decorative">
            <div class="floating-shape shape1"></div>
            <div class="floating-shape shape2"></div>
            <div class="floating-shape shape3"></div>
            <div class="floating-shape shape4"></div>
        </div>

        <div class="units-content">
            <h2 class="units-title">
                O aprendizado é <span class="units-title-highlight">dividido em unidades</span>, cada uma com:
            </h2>
            <p class="units-subtitle" id="dynamic-subtitle">
                Conteúdo teórico para você estudar no seu ritmo
            </p>

            <div class="units-dots">
                <div class="dot active" onclick="showContentSlide(0)"></div>
                <div class="dot" onclick="showContentSlide(1)"></div>
                <div class="dot" onclick="showContentSlide(2)"></div>
            </div>

            <div class="content-box">
                <div class="content-carousel">
                    <div class="content-slide active">
                        <h3>Estrutura de Aprendizado Personalizada</h3>
                        <p>
                            Cada unidade foi cuidadosamente desenvolvida para proporcionar uma experiência de
                            aprendizado completa e eficaz.
                            Você terá acesso a conteúdo teórico estruturado, exercícios práticos e avaliações que se
                            adaptam ao seu ritmo de estudo.
                        </p>
                        <p>
                            Nossa metodologia garante que você absorva o conhecimento de forma gradual e consistente,
                            construindo uma base sólida para dominar o idioma escolhido.
                        </p>
                    </div>

                    <div class="content-slide">
                        <h3>Minigames Interativos</h3>
                        <p>
                            Transforme seu aprendizado em diversão com nossos minigames educativos!
                            Pratique vocabulário, gramática e pronúncia através de jogos envolventes que tornam o estudo
                            mais dinâmico e eficiente.
                        </p>
                        <p>
                            Cada minigame é projetado para reforçar conceitos específicos, permitindo que você teste
                            seus conhecimentos
                            de forma lúdica e memorável, garantindo melhor retenção do conteúdo aprendido.
                        </p>
                    </div>

                    <div class="content-slide">
                        <h3>Perguntas e Respostas Interativas</h3>
                        <p>
                            Desenvolva suas habilidades através de sessões interativas de perguntas e respostas.
                            Nosso sistema adapta as questões ao seu nível de conhecimento, proporcionando desafios
                            adequados ao seu progresso.
                        </p>
                        <p>
                            Com feedback instantâneo e explicações detalhadas, você compreende não apenas as respostas
                            corretas,
                            mas também o raciocínio por trás de cada conceito, acelerando seu aprendizado.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <button class="scroll-button" onclick="scrollToSection('welcome')">
            ↑ Voltar ao início
        </button>
    </section>

    <script>
    // Função para navegar entre páginas
    function irParaPagina(pagina) {
        window.location.href = pagina;
    }

    // Função para scroll suave entre seções
    function scrollToSection(sectionId) {
        document.getElementById(sectionId).scrollIntoView({
            behavior: 'smooth'
        });
    }

    // Carrossel de conteúdo da seção units
    let currentContentSlide = 0;
    const contentSlides = document.querySelectorAll('.content-slide');
    const contentDots = document.querySelectorAll('.units-dots .dot');
    const dynamicSubtitle = document.getElementById('dynamic-subtitle');

    const subtitles = [
        "Conteúdo teórico para você estudar no seu ritmo",
        "Minigames para praticar e testar seus conhecimentos",
        "Perguntas e respostas interativas"
    ];

    function showContentSlide(index) {
        // Remove active class from all slides and dots
        contentSlides.forEach(slide => slide.classList.remove('active'));
        contentDots.forEach(dot => dot.classList.remove('active'));

        // Add active class to current slide and dot
        contentSlides[index].classList.add('active');
        contentDots[index].classList.add('active');

        // Update subtitle
        dynamicSubtitle.textContent = subtitles[index];

        currentContentSlide = index;
    }

    // Auto-rotate content slides
    function autoRotateContent() {
        currentContentSlide = (currentContentSlide + 1) % contentSlides.length;
        showContentSlide(currentContentSlide);
    }

    // Start auto-rotation when page loads
    let contentAutoRotate;
    window.addEventListener('load', () => {
        contentAutoRotate = setInterval(autoRotateContent, 4000);
    });

    // Pause auto-rotation when user interacts with dots
    contentDots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            clearInterval(contentAutoRotate);
            showContentSlide(index);
            // Restart auto-rotation after 8 seconds
            setTimeout(() => {
                contentAutoRotate = setInterval(autoRotateContent, 4000);
            }, 8000);
        });
    });

    // Carrossel de imagens
    const carousel = document.getElementById('carousel');
    const images = document.querySelectorAll('.carousel img');
    const leftArrow = document.querySelector('.arrow-left');
    const rightArrow = document.querySelector('.arrow-right');
    const carouselContainer = document.getElementById('carousel-container');
    let index = 0;
    let autoSlideInterval;

    function showSlide(i) {
        if (i < 0) i = images.length - 1;
        if (i >= images.length) i = 0;
        index = i;
        const imageWidth = images[index].clientWidth;
        carousel.style.transform = `translateX(-${imageWidth * index}px)`;
    }

    if (leftArrow && rightArrow && carouselContainer) {
        leftArrow.addEventListener('click', () => showSlide(index - 1));
        rightArrow.addEventListener('click', () => showSlide(index + 1));

        function startAutoSlide() {
            autoSlideInterval = setInterval(() => showSlide(index + 1), 3000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        carouselContainer.addEventListener('mouseenter', stopAutoSlide);
        carouselContainer.addEventListener('mouseleave', startAutoSlide);

        // Iniciar carrossel automático quando a página carregar
        window.addEventListener('load', () => {
            startAutoSlide();
            showSlide(0); // Garantir que o primeiro slide seja mostrado
        });

        // Ajustar carrossel no redimensionamento da janela
        window.addEventListener('resize', () => {
            showSlide(index);
        });
    }
    </script>


</body>

</html>