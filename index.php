<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeakNut - Plataforma de Aprendizado de Idiomas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
         <link rel="icon" type="image/png" href="imagens/mini-esquilo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    /* Reset e configurações base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        line-height: 1.6;
        color: #ffffff;
        overflow-x: hidden;
    }

    /* Container principal */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Header */
    .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        animation: slideDown 0.6s ease-out;
    }

    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        transition: transform 0.3s ease;
    }

    .logo h1:hover {
        transform: scale(1.05);
    }

    .nav-desktop {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .nav-btn {
        background: none;
        border: none;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.0rem;
    }

    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .btn-primary {
        background: linear-gradient(135deg, #7c3aed, #a855f7);
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 0.5rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(124, 58, 237, 0.4);
    }

    .btn-outline {
        background: transparent;
        color: white;
        border: 2px solid white;
        padding: 0.5rem 1.5rem;
        border-radius: 0.5rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-outline:hover {
        background: white;
        color: #7c3aed;
    }

    /* Menu mobile */
    .nav-mobile {
        display: none;
    }

    .menu-toggle {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .hamburger {
        width: 25px;
        height: 3px;
        background: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .menu-toggle.active .hamburger:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .menu-toggle.active .hamburger:nth-child(2) {
        opacity: 0;
    }

    .menu-toggle.active .hamburger:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    .mobile-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        transform: translateY(-100%);
        opacity: 0;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .mobile-menu.active {
        transform: translateY(0);
        opacity: 1;
        pointer-events: all;
    }

    .mobile-menu-content {
        padding: 1rem 20px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .nav-btn-mobile {
        background: none;
        border: none;
        color: white;
        padding: 0.75rem;
        text-align: left;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .nav-btn-mobile:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .mobile-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .mobile-buttons .btn-primary,
    .mobile-buttons .btn-outline {
        flex: 1;
        text-align: center;
    }

    /* Seção Welcome */
    .section-welcome {
        min-height: 100vh;
        background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .particles-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .particle:nth-child(1) {
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .particle:nth-child(2) {
        top: 80%;
        left: 20%;
        animation-delay: 1s;
    }

    .particle:nth-child(3) {
        top: 40%;
        left: 80%;
        animation-delay: 2s;
    }

    .particle:nth-child(4) {
        top: 60%;
        left: 70%;
        animation-delay: 3s;
    }

    .particle:nth-child(5) {
        top: 10%;
        left: 50%;
        animation-delay: 4s;
    }

    .particle:nth-child(6) {
        top: 90%;
        left: 60%;
        animation-delay: 5s;
    }

    .particle:nth-child(7) {
        top: 30%;
        left: 30%;
        animation-delay: 0.5s;
    }

    .particle:nth-child(8) {
        top: 70%;
        left: 90%;
        animation-delay: 1.5s;
    }

    .particle:nth-child(9) {
        top: 50%;
        left: 15%;
        animation-delay: 2.5s;
    }

    .particle:nth-child(10) {
        top: 25%;
        left: 85%;
        animation-delay: 3.5s;
    }

    .particle:nth-child(11) {
        top: 75%;
        left: 45%;
        animation-delay: 4.5s;
    }

    .particle:nth-child(12) {
        top: 15%;
        left: 75%;
        animation-delay: 5.5s;
    }

    .particle:nth-child(13) {
        top: 85%;
        left: 35%;
        animation-delay: 0.8s;
    }

    .particle:nth-child(14) {
        top: 35%;
        left: 65%;
        animation-delay: 1.8s;
    }

    .particle:nth-child(15) {
        top: 65%;
        left: 25%;
        animation-delay: 2.8s;
    }

    .particle:nth-child(16) {
        top: 45%;
        left: 95%;
        animation-delay: 3.8s;
    }

    .particle:nth-child(17) {
        top: 55%;
        left: 5%;
        animation-delay: 4.8s;
    }

    .particle:nth-child(18) {
        top: 5%;
        left: 40%;
        animation-delay: 5.8s;
    }

    .particle:nth-child(19) {
        top: 95%;
        left: 80%;
        animation-delay: 1.2s;
    }

    .particle:nth-child(20) {
        top: 40%;
        left: 55%;
        animation-delay: 2.2s;
    }

    .welcome-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .welcome-content {
        text-align: left;
    }

    .welcome-title {
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1.5rem;
    }

    .highlight-text {
        color: #fbbf24;
        text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        animation: glow 2s ease-in-out infinite alternate;
    }

    .welcome-subtitle {
        font-size: 1.25rem;
        color: #c4b5fd;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .btn-cta {
        background: linear-gradient(135deg, #7c3aed, #ec4899);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
    }

    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(124, 58, 237, 0.4);
    }

    .play-icon {
        font-size: 0.9rem;
    }

    .welcome-mascot {
        display: flex;
        justify-content: center;
    }

    .mascot-circle {
        width: 300px;
        height: 300px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: float 4s ease-in-out infinite;
    }

    .mascot-emoji {
        font-size: 6rem;
        animation: rotate 6s ease-in-out infinite;
    }

    .scroll-indicator {
        position: absolute;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
    }

    .scroll-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid white;
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        animation: bounce 2s infinite;
    }

    .scroll-btn:hover {
        background: white;
        color: #7c3aed;
    }

    .arrow-down {
        font-size: 1.2rem;
    }

    /* Seção Main */
    .section-main {
        min-height: 100vh;
        background: linear-gradient(135deg, #581c87, #3730a3);
        padding: 5rem 0;
        position: relative;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
        margin-bottom: 4rem;
    }

    .carousel-container {
        position: relative;
    }

    .carousel-wrapper {
        position: relative;
        width: 100%;
        height: 400px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #fbbf24, #e6950aff);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(1.1);
        transition: all 0.5s ease;
    }

    .carousel-slide.active {
        opacity: 1;
        transform: scale(1);
    }

    .slide-content {
        text-align: center;
        padding: 2rem;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #1e1b4b;
    }

    .slide-image-container {
        width: 100%;
        height: 70%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .slide-image {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .slide-text {
        text-align: center;
        padding: 1rem;
        height: 30%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .slide-text h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .slide-text p {
        font-size: 1rem;
        opacity: 0.9;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.5rem;
        transition: all 0.3s ease;
        z-index: 10;
    }

    .carousel-btn:hover {
        background: rgba(0, 0, 0, 0.7);
        transform: translateY(-50%) scale(1.1);
    }

    .carousel-prev {
        left: 1rem;
    }

    .carousel-next {
        right: 1rem;
    }

    .carousel-indicators {
        position: absolute;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 0.5rem;
        z-index: 10;

    }

    .indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: none;
        background: #1e1b4ba6;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .indicator.active {
        background-color: #1e1b4b;
        transform: scale(1.2);
    }

    .main-text {
        text-align: left;
    }

    .main-title {
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1rem;
    }

    .main-subtitle {
        font-size: 2.5rem;
        font-weight: 900;
        color: #fbbf24;
        text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
    }

    .benefits-section {
        max-width: 1000px;
        margin: 0 auto;
    }

    .benefits-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        padding: 3rem;
        text-align: center;
    }

    .benefits-title {
        font-size: 2rem;
        font-weight: 700;
        color: #fbbf24;
        margin-bottom: 1.5rem;
    }

    .benefits-description {
        font-size: 1.1rem;
        line-height: 1.7;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-align: left;
    }

    .benefit-icon {
        font-size: 1.5rem;
    }

    .benefit-text {
        font-size: 1rem;
        line-height: 1.5;
    }

    .btn-cta-yellow {
        background: #fbbf24;
        color: #1f2937;
        border: none;
        padding: 1rem 2rem;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(251, 191, 36, 0.3);
    }

    .btn-cta-yellow:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(251, 191, 36, 0.4);
    }

    .floating-mascot {
        position: absolute;
        bottom: 2rem;
        right: 2rem;
        animation: float 3s ease-in-out infinite;
    }

    .floating-mascot-circle {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
    }

    .floating-mascot .mascot-emoji {
        font-size: 3rem;
    }

    /* Seção Units */
    .section-units {
        min-height: 100vh;
        background: linear-gradient(135deg, #3730a3, #581c87);
        padding: 5rem 0;
        position: relative;
        overflow: hidden;
    }

    .decorative-elements {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: floatShape 8s ease-in-out infinite;
    }

    .shape-1 {
        width: 100px;
        height: 100px;
        top: 15%;
        left: 8%;
        animation-delay: 0s;
    }

    .shape-2 {
        width: 60px;
        height: 60px;
        top: 25%;
        right: 12%;
        animation-delay: 2s;
    }

    .shape-3 {
        width: 80px;
        height: 80px;
        bottom: 20%;
        left: 15%;
        animation-delay: 4s;
    }

    .shape-4 {
        width: 120px;
        height: 120px;
        bottom: 30%;
        right: 20%;
        animation-delay: 6s;
    }

    .corner-mascot {
        position: absolute;
        top: 2rem;
        left: 2rem;
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        animation: float 3s ease-in-out infinite;
        z-index: 10;
    }

    .corner-mascot .mascot-emoji {
        font-size: 2rem;
    }

    .units-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .units-title {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 1rem;
    }

    .units-subtitle {
        font-size: 1.2rem;
        color: #c4b5fd;
        transition: all 0.5s ease;
    }

    .content-indicators {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        margin-bottom: 3rem;
    }

    .content-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.4);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .content-dot.active {
        background: #fbbf24;
        transform: scale(1.25);
    }

    .content-card {
        max-width: 1000px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }

    .content-header {
        height: 5px;
        background: linear-gradient(90deg, #7c3aed, #ec4899, #fbbf24);
    }

    .content-carousel {
        position: relative;
        min-height: 400px;
    }

    .content-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 3rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        align-items: center;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.5s ease;
        color: #1f2937;
    }

    .content-slide.active {
        opacity: 1;
        transform: translateX(0);
        position: relative;
    }

    .content-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .content-icon {
        display: flex;
        justify-content: flex-start;
    }

    .icon-wrapper {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .icon-wrapper:hover {
        transform: scale(1.1) rotate(5deg);
    }

    .content-emoji {
        font-size: 2.5rem;
    }

    .content-text h3 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
    }

    .content-text p {
        font-size: 1rem;
        line-height: 1.6;
        color: #4b5563;
    }

    .content-visual {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .visual-placeholder {
        width: 250px;
        height: 250px;
        background: linear-gradient(135deg, #f3e8ff, #fce7f3);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .visual-placeholder:hover {
        transform: scale(1.05);
    }

    .visual-emoji {
        font-size: 4rem;
        animation: rotate 4s ease-in-out infinite;
    }

    .back-to-top {
        text-align: center;
        margin-top: 3rem;
    }

    .btn-back {
        background: transparent;
        color: white;
        border: 2px solid white;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: white;
        color: #7c3aed;
    }

    /* Footer */
    .footer {
        background: #1e1b4b;
        padding: 4rem 0 2rem;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-column h3,
    .footer-column h4 {
        margin-bottom: 1rem;
    }

    .footer-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fbbf24;
    }

    .footer-title {
        color: #fbbf24;
        font-weight: 600;
    }

    .footer-description {
        color: #c4b5fd;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .social-icons {
        display: flex;
        gap: 1rem;
    }

    .social-btn {
        width: 40px;
        height: 40px;
        background: #581c87;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .social-btn:hover {
        background: #7c3aed;
        transform: translateY(-3px);
    }

    .footer-links {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: 0.5rem;
    }

    .footer-links button {
        background: none;
        border: none;
        color: #c4b5fd;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .footer-links button:hover {
        color: white;
    }

    .contact-info {
        color: #c4b5fd;
    }

    .contact-info p {
        margin-bottom: 0.5rem;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid #581c87;
        color: #c4b5fd;
    }

    /* Animações */
    @keyframes slideDown {
        from {
            transform: translateY(-100%);
        }

        to {
            transform: translateY(0);
        }
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

    @keyframes floatShape {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }

        50% {
            transform: translateY(-30px) rotate(180deg);
            opacity: 0.6;
        }
    }

    @keyframes rotate {

        0%,
        100% {
            transform: rotate(0deg);
        }

        25% {
            transform: rotate(10deg);
        }

        75% {
            transform: rotate(-10deg);
        }
    }

    @keyframes glow {
        0% {
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }

        100% {
            text-shadow: 0 0 30px rgba(251, 191, 36, 0.8);
        }
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

    /* Animações de entrada */
    .animate-slide-in-left {
        animation: slideInLeft 0.8s ease-out;
    }

    .animate-slide-in-right {
        animation: slideInRight 0.8s ease-out;
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.8s ease-out;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .nav-desktop {
            display: none;
        }

        .nav-mobile {
            display: block;
        }

        .welcome-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 2.5rem;
        }

        .main-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .main-title {
            font-size: 2.5rem;
            text-align: center;
        }

        .main-subtitle {
            font-size: 2rem;
            text-align: center;
        }

        .benefits-card {
            padding: 2rem 1.5rem;
        }

        .benefits-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .benefit-item {
            justify-content: center;
            text-align: center;
        }

        .units-title {
            font-size: 2rem;
        }

        .content-slide {
            grid-template-columns: 1fr;
            text-align: center;
            padding: 2rem 1.5rem;
        }

        .content-grid {
            align-items: center;
        }

        .content-icon {
            justify-content: center;
        }

        .floating-mascot {
            display: none;
        }

        .corner-mascot {
            top: 1rem;
            left: 1rem;
            width: 50px;
            height: 50px;
        }

        .corner-mascot .mascot-emoji {
            font-size: 1.5rem;
        }

        .mascot-circle {
            width: 200px;
            height: 200px;
        }

        .mascot-emoji {
            font-size: 4rem;
        }

        .slide-text h3 {
            font-size: 1.2rem;

        }

        .slide-text p {
            font-size: 0.9rem;

        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 0 15px;
        }

        .welcome-title {
            font-size: 2rem;
        }

        .main-title {
            font-size: 2rem;
        }

        .main-subtitle {
            font-size: 1.5rem;
        }

        .units-title {
            font-size: 1.5rem;
        }

        .benefits-card {
            padding: 1.5rem 1rem;
        }

        .content-slide {
            padding: 1.5rem 1rem;
        }
    }

    /* Scroll suave customizado */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #1e1b4b;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #7c3aedff, #a855f7);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #6d28d9, #9333ea);
    }

    /* Acessibilidade */
    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* Estados de foco */
    button:focus-visible,
    .nav-btn:focus-visible,
    .btn-primary:focus-visible,
    .btn-outline:focus-visible {
        outline: 2px solid #fbbf24;
        outline-offset: 2px;
    }
    </style>
</head>

<body>
    <script>
    // Variáveis globais
    let currentSlide = 0;
    let currentContentSlide = 0;
    let carouselInterval;
    let contentInterval;

    // Dados do carrossel - AGORA COM IMAGENS
    const carouselSlides = [{
            title: "Aprenda no seu ritmo",
            description: "Aulas personalizadas para seu nível!",
            image: "Research paper-pana.png"
        },
        {
            title: "Atividades dinâmicas",
            description: "Exercícios para praticar sempre que puder!",
            image: "Online document-bro.png"
        },
        {
            title: "Gamificação",
            description: "Torne o aprendizado divertido!",
            image: "Game analytics-pana.png"
        }
    ];

    // Dados do conteúdo
    const contentSlides = [{
            title: "Estrutura de Aprendizado Personalizada",
            description: "Cada unidade foi cuidadosamente desenvolvida para proporcionar uma experiência de aprendizado completa e eficaz. Nossa metodologia garante que você absorva o conhecimento de forma gradual e consistente.",
            subtitle: "Conteúdo teórico para você estudar no seu ritmo",
            emoji: "📚"
        },
        {
            title: "Minigames Interativos",
            description: "Transforme seu aprendizado em diversão com nossos minigames educativos! Pratique vocabulário, gramática e pronúncia de forma lúdica. Cada minigame é projetado para reforçar conceitos específicos.",
            subtitle: "Minigames para praticar e testar seus conhecimentos",
            emoji: "🎮"
        },
        {
            title: "Perguntas e Respostas Interativas",
            description: "Desenvolva suas habilidades através de sessões interativas de perguntas e respostas com feedback instantâneo. Nosso sistema adapta as questões ao seu nível, proporcionando desafios adequados.",
            subtitle: "Perguntas e respostas interativas",
            emoji: "💬"
        }
    ];

    // Inicialização quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        initializeCarousel();
        initializeContentCarousel();
        initializeMobileMenu();
        initializeScrollAnimations();
        initializeParallaxEffects();
    });

    // Função para scroll suave para seções
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    // Inicializar carrossel principal
    function initializeCarousel() {
        startCarouselAutoplay();
        updateCarouselSlide();
    }

    // Inicializar carrossel de conteúdo
    function initializeContentCarousel() {
        startContentAutoplay();
        updateContentSlide();
    }

    // Carrossel principal - mudar slide
    function changeSlide(direction) {
        stopCarouselAutoplay();

        if (direction === 1) {
            currentSlide = (currentSlide + 1) % carouselSlides.length;
        } else {
            currentSlide = (currentSlide - 1 + carouselSlides.length) % carouselSlides.length;
        }

        updateCarouselSlide();

        // Reiniciar autoplay após 5 segundos
        setTimeout(() => {
            startCarouselAutoplay();
        }, 5000);
    }

    // Ir para slide específico
    function goToSlide(slideIndex) {
        stopCarouselAutoplay();
        currentSlide = slideIndex;
        updateCarouselSlide();

        // Reiniciar autoplay após 5 segundos
        setTimeout(() => {
            startCarouselAutoplay();
        }, 5000);
    }

    // Atualizar slide do carrossel - AGORA COM IMAGENS
    function updateCarouselSlide() {
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.carousel-indicators .indicator');

        // Remover classe active de todos os slides
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));

        // Adicionar classe active ao slide atual
        if (slides[currentSlide]) {
            slides[currentSlide].classList.add('active');
        }
        if (indicators[currentSlide]) {
            indicators[currentSlide].classList.add('active');
        }

        // Atualizar conteúdo do slide
        const activeSlide = slides[currentSlide];
        if (activeSlide) {
            const slideContent = activeSlide.querySelector('.slide-content');
            if (slideContent) {
                slideContent.innerHTML = `
                    <div class="slide-image-container">
                        <img src="${carouselSlides[currentSlide].image}" alt="${carouselSlides[currentSlide].title}" class="slide-image">
                    </div>
                    <div class="slide-text">
                        <h3>${carouselSlides[currentSlide].title}</h3>
                        <p>${carouselSlides[currentSlide].description}</p>
                    </div>
                `;
            }
        }
    }

    // Iniciar autoplay do carrossel
    function startCarouselAutoplay() {
        carouselInterval = setInterval(() => {
            currentSlide = (currentSlide + 1) % carouselSlides.length;
            updateCarouselSlide();
        }, 5000);
    }

    // Parar autoplay do carrossel
    function stopCarouselAutoplay() {
        if (carouselInterval) {
            clearInterval(carouselInterval);
        }
    }

    // Mostrar slide de conteúdo específico
    function showContentSlide(slideIndex) {
        stopContentAutoplay();
        currentContentSlide = slideIndex;
        updateContentSlide();

        // Reiniciar autoplay após 8 segundos
        setTimeout(() => {
            startContentAutoplay();
        }, 8000);
    }

    // Atualizar slide de conteúdo
    function updateContentSlide() {
        const slides = document.querySelectorAll('.content-slide');
        const dots = document.querySelectorAll('.content-dot');
        const subtitle = document.getElementById('dynamicSubtitle');

        // Remover classe active de todos os slides e dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        // Adicionar classe active ao slide и dot atual
        if (slides[currentContentSlide]) {
            slides[currentContentSlide].classList.add('active');
        }
        if (dots[currentContentSlide]) {
            dots[currentContentSlide].classList.add('active');
        }

        // Atualizar subtitle dinâmico
        if (subtitle && contentSlides[currentContentSlide]) {
            subtitle.textContent = contentSlides[currentContentSlide].subtitle;
        }

        // Atualizar conteúdo do slide
        const activeSlide = slides[currentContentSlide];
        if (activeSlide && contentSlides[currentContentSlide]) {
            const contentData = contentSlides[currentContentSlide];

            // Atualizar ícone
            const emoji = activeSlide.querySelector('.content-emoji');
            if (emoji) {
                emoji.textContent = contentData.emoji;
            }

            const visualEmoji = activeSlide.querySelector('.visual-emoji');
            if (visualEmoji) {
                visualEmoji.textContent = contentData.emoji;
            }

            // Atualizar texto
            const title = activeSlide.querySelector('h3');
            if (title) {
                title.textContent = contentData.title;
            }

            const description = activeSlide.querySelector('p');
            if (description) {
                description.textContent = contentData.description;
            }
        }
    }

    // Iniciar autoplay do conteúdo
    function startContentAutoplay() {
        contentInterval = setInterval(() => {
            currentContentSlide = (currentContentSlide + 1) % contentSlides.length;
            updateContentSlide();
        }, 4000);
    }

    // Parar autoplay do conteúdo
    function stopContentAutoplay() {
        if (contentInterval) {
            clearInterval(contentInterval);
        }
    }

    // Menu mobile
    function initializeMobileMenu() {
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', toggleMobileMenu);
        }
    }

    function toggleMobileMenu() {
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        if (menuToggle && mobileMenu) {
            menuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        }
    }

    function closeMobileMenu() {
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        if (menuToggle && mobileMenu) {
            menuToggle.classList.remove('active');
            mobileMenu.classList.remove('active');
        }
    }

    // Animações de scroll
    function initializeScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observar elementos com animações
        const animatedElements = document.querySelectorAll(
            '.animate-fade-in-up, .animate-slide-in-left, .animate-slide-in-right'
        );

        animatedElements.forEach(el => {
            // Definir estado inicial
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.8s ease-out';

            observer.observe(el);
        });
    }

    // Efeitos parallax
    function initializeParallaxEffects() {
        let ticking = false;

        function updateParallax() {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.particles-container');

            parallaxElements.forEach(element => {
                const speed = 0.5;
                const yPos = -(scrolled * speed);
                element.style.transform = `translateY(${yPos}px)`;
            });

            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick);
    }

    // Efeitos de hover para botões
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('button');

        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });

    // Controle de scroll do header
    window.addEventListener('scroll', function() {
        const header = document.getElementById('header');
        const scrolled = window.pageYOffset;

        if (scrolled > 100) {
            header.style.background = 'rgba(30, 27, 75, 0.95)';
            header.style.backdropFilter = 'blur(20px)';
        } else {
            header.style.background = 'rgba(255, 255, 255, 0.1)';
            header.style.backdropFilter = 'blur(20px)';
        }
    });

    // Função para adicionar efeitos de partículas dinâmicas
    function createDynamicParticles() {
        const particlesContainer = document.querySelector('.particles-container');
        if (!particlesContainer) return;

        // Criar partículas adicionais dinamicamente
        for (let i = 0; i < 10; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (3 + Math.random() * 3) + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // Inicializar partículas dinâmicas
    document.addEventListener('DOMContentLoaded', createDynamicParticles);

    // Função para smooth scroll personalizado
    function smoothScrollTo(target, duration = 1000) {
        const targetElement = document.getElementById(target);
        if (!targetElement) return;

        const targetPosition = targetElement.offsetTop - 80; // Offset para o header fixo
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const run = ease(timeElapsed, startPosition, distance, duration);
            window.scrollTo(0, run);
            if (timeElapsed < duration) requestAnimationFrame(animation);
        }

        function ease(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t + b;
            t--;
            return -c / 2 * (t * (t - 2) - 1) + b;
        }

        requestAnimationFrame(animation);
    }

    // Função para detectar dispositivo móvel
    function isMobile() {
        return window.innerWidth <= 768;
    }

    // Ajustar animações para dispositivos móveis
    function adjustAnimationsForMobile() {
        if (isMobile()) {
            // Reduzir duração das animações em dispositivos móveis
            const style = document.createElement('style');
            style.textContent = `
            * {
                animation-duration: 0.5s !important;
                transition-duration: 0.3s !important;
            }
        `;
            document.head.appendChild(style);
        }
    }

    // Inicializar ajustes para mobile
    document.addEventListener('DOMContentLoaded', adjustAnimationsForMobile);

    // Função para preload de imagens (se houver)
    function preloadImages() {
        const images = [
            // Adicionar URLs de imagens aqui se necessário
        ];

        images.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }

    // Função para lazy loading de elementos
    function initializeLazyLoading() {
        const lazyElements = document.querySelectorAll('[data-lazy]');

        const lazyObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const src = element.getAttribute('data-lazy');
                    if (src) {
                        element.src = src;
                        element.removeAttribute('data-lazy');
                    }
                    lazyObserver.unobserve(element);
                }
            });
        });

        lazyElements.forEach(element => {
            lazyObserver.observe(element);
        });
    }

    // Função para otimizar performance
    function optimizePerformance() {
        // Debounce para eventos de scroll
        let scrollTimeout;
        const originalScrollHandler = window.onscroll;

        window.onscroll = function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(() => {
                if (originalScrollHandler) {
                    originalScrollHandler();
                }
            }, 16); // ~60fps
        };
    }

    // Inicializar otimizações
    document.addEventListener('DOMContentLoaded', function() {
        optimizePerformance();
        initializeLazyLoading();
        preloadImages();
    });

    // Função para adicionar efeitos de typing
    function typeWriter(element, text, speed = 50) {
        let i = 0;
        element.innerHTML = '';

        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }

        type();
    }

    // Função para efeitos de contador animado
    function animateCounter(element, target, duration = 2000) {
        let start = 0;
        const increment = target / (duration / 16);

        function updateCounter() {
            start += increment;
            if (start < target) {
                element.textContent = Math.floor(start);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        }

        updateCounter();
    }

    // Função para adicionar efeitos de shake
    function shakeElement(element, duration = 500) {
        element.style.animation = `shake ${duration}ms ease-in-out`;
        setTimeout(() => {
            element.style.animation = '';
        }, duration);
    }

    // Keyframes para shake (adicionar ao CSS se necessário)
    const shakeKeyframes = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;

    // Função para controle de tema (se necessário no futuro)
    function toggleTheme() {
        document.body.classList.toggle('dark-theme');
        localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
    }

    // Carregar tema salvo
    function loadSavedTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    }

    // Função para analytics simples (se necessário)
    function trackEvent(eventName, eventData = {}) {
        console.log('Event tracked:', eventName, eventData);
        // Implementar analytics aqui se necessário
    }

    // Event listeners para tracking
    document.addEventListener('DOMContentLoaded', function() {
        // Track page load
        trackEvent('page_load', {
            page: 'home'
        });

        // Track button clicks
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function() {
                trackEvent('button_click', {
                    button_text: this.textContent.trim(),
                    button_class: this.className
                });
            });
        });
    });

    // Função para feedback visual
    function showFeedback(message, type = 'success') {
        const feedback = document.createElement('div');
        feedback.className = `feedback feedback-${type}`;
        feedback.textContent = message;
        feedback.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    `;

        document.body.appendChild(feedback);

        // Animar entrada
        setTimeout(() => {
            feedback.style.opacity = '1';
            feedback.style.transform = 'translateX(0)';
        }, 100);

        // Remover após 3 segundos
        setTimeout(() => {
            feedback.style.opacity = '0';
            feedback.style.transform = 'translateX(100px)';
            setTimeout(() => {
                document.body.removeChild(feedback);
            }, 300);
        }, 3000);
    }

    // Função para validação de formulário (se necessário no futuro)
    function validateForm(formData) {
        const errors = [];

        if (!formData.email || !formData.email.includes('@')) {
            errors.push('Email inválido');
        }

        if (!formData.name || formData.name.length < 2) {
            errors.push('Nome deve ter pelo menos 2 caracteres');
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    // Função para formatação de texto
    function formatText(text, type) {
        switch (type) {
            case 'capitalize':
                return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
            case 'uppercase':
                return text.toUpperCase();
            case 'lowercase':
                return text.toLowerCase();
            case 'title':
                return text.replace(/\w\S*/g, (txt) =>
                    txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
                );
            default:
                return text;
        }
    }

    // Função para debounce
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    // Função para throttle
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // Exportar funções principais para uso global
    window.SpeakNutApp = {
        scrollToSection,
        changeSlide,
        goToSlide,
        showContentSlide,
        toggleMobileMenu,
        closeMobileMenu,
        showFeedback,
        trackEvent
    };
    </script>

    <!-- Header fixo -->
    <header class="header" id="header">
        <div class="header-container">
            <div class="logo">
                <img src="imagens/logo-idiomas.png" style="width: 200px; height: auto; display: block;">
            </div>

            <!-- Menu desktop -->
            <nav class="nav-desktop">
                <button class="nav-btn" onclick="scrollToSection('welcome')">Início</button>
                <button class="nav-btn" onclick="scrollToSection('main')">Sobre</button>
                <button class="nav-btn" onclick="scrollToSection('units')">Unidades</button>
                <button class="btn-primary" style="font-size: 1.0rem;">Entrar</button>
                <a href="public/views/login.php" class="btn-outline"
                    style="text-decoration: none; font-size: 14px; padding: 6px 12px;">Cadastre-se</a>
            </nav>

            <!-- Menu mobile -->
            <div class="nav-mobile">
                <button class="menu-toggle" id="menuToggle">
                    <span class="hamburger"></span>
                    <span class="hamburger"></span>
                    <span class="hamburger"></span>
                </button>
            </div>
        </div>

        <!-- Menu mobile expandido -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-content">
                <button class="nav-btn-mobile" onclick="scrollToSection('welcome'); closeMobileMenu()">Início</button>
                <button class="nav-btn-mobile" onclick="scrollToSection('main'); closeMobileMenu()">Sobre</button>
                <button class="nav-btn-mobile" onclick="scrollToSection('units'); closeMobileMenu()">Unidades</button>
                <div class="mobile-buttons">
                    <button class="btn-primary">Entrar</button>
                    <button class="btn-outline">Cadastre-se</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Seção 1: Boas-vindas -->
    <section id="welcome" class="section-welcome">
        <!-- Partículas animadas -->
        <div class="particles-container">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>

        <div class="container">
            <div class="welcome-grid">
                <!-- Lado esquerdo - Conteúdo -->
                <div class="welcome-content animate-slide-in-left">
                    <h1 class="welcome-title">
                        Seja bem-vindo ao
                        <span class="highlight-text">SpeakNut</span>
                    </h1>
                    <p class="welcome-subtitle">
                        Sua nova plataforma de aprendizado de idiomas
                    </p>
                    <button class="btn-cta" onclick="scrollToSection('main')">
                        <span class="play-icon">▶</span>
                        Descubra mais
                    </button>
                </div>

                <!-- Lado direito - Mascote animado -->
                <div class="welcome-mascot animate-slide-in-right">
                    <div class="mascot-circle">
                        <span class="mascot-emoji">🐿️</span>
                    </div>
                </div>
            </div>

            <!-- Botão de scroll animado -->
            <div class="scroll-indicator">
                <button class="scroll-btn" onclick="scrollToSection('main')">
                    <span class="arrow-down">↓</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Seção 2: Conteúdo principal -->
    <section id="main" class="section-main">
        <div class="container">
            <div class="main-grid">
                <!-- Carrossel de imagens - MODIFICADO PARA MOSTRAR IMAGENS -->
                <div class="carousel-container animate-fade-in-up">
                    <div class="carousel-wrapper">
                        <div class="carousel-slide active" data-slide="0">
                            <div class="slide-content">
                                <!-- Conteúdo será preenchido pelo JavaScript -->
                            </div>
                        </div>
                        <div class="carousel-slide" data-slide="1">
                            <div class="slide-content">
                                <!-- Conteúdo será preenchido pelo JavaScript -->
                            </div>
                        </div>
                        <div class="carousel-slide" data-slide="2">
                            <div class="slide-content">
                                <!-- Conteúdo será preenchido pelo JavaScript -->
                            </div>
                        </div>

                        <!-- Controles do carrossel -->
                        <button class="carousel-btn carousel-prev" onclick="changeSlide(-1)">‹</button>
                        <button class="carousel-btn carousel-next" onclick="changeSlide(1)">›</button>

                        <!-- Indicadores -->
                        <div class="carousel-indicators">
                            <button class="indicator active" onclick="goToSlide(0)"></button>
                            <button class="indicator" onclick="goToSlide(1)"></button>
                            <button class="indicator" onclick="goToSlide(2)"></button>
                        </div>
                    </div>
                </div>

                <!-- Texto principal -->
                <div class="main-text animate-fade-in-up">
                    <h2 class="main-title">Aprenda com o SpeakNut</h2>
                    <p class="main-subtitle">ONDE ESTIVER</p>
                </div>
            </div>

            <!-- Seção de benefícios -->
            <div class="benefits-section animate-fade-in-up">
                <div class="benefits-card">
                    <h3 class="benefits-title">Por que escolher o SpeakNut?</h3>
                    <p class="benefits-description">
                        Nossa plataforma de aprendizado de idiomas foi criada para ajudar você a estudar de forma
                        prática e divertida.
                        Com aulas interativas, exercícios personalizados e suporte em tempo real, você aprende no seu
                        ritmo, de qualquer lugar do mundo.
                    </p>

                    <div class="benefits-grid">
                        <div class="benefit-item animate-slide-in-left">
                            <span class="benefit-icon">📚</span>
                            <span class="benefit-text">Aulas dinâmicas e personalizadas</span>
                        </div>
                        <div class="benefit-item animate-slide-in-left">
                            <span class="benefit-icon">👥</span>
                            <span class="benefit-text">Professores nativos disponíveis</span>
                        </div>
                        <div class="benefit-item animate-slide-in-left">
                            <span class="benefit-icon">🎮</span>
                            <span class="benefit-text">Exercícios interativos e gamificação</span>
                        </div>
                        <div class="benefit-item animate-slide-in-left">
                            <span class="benefit-icon">🌐</span>
                            <span class="benefit-text">Estude no computador, celular ou tablet</span>
                        </div>
                    </div>

                    <button class="btn-cta-yellow" onclick="window.location.href='public/views/login.php'">
                        Comece agora gratuitamente 🚀
                    </button>
                </div>
            </div>
        </div>

        <!-- Mascote flutuante -->
        <div class="floating-mascot">
            <div class="floating-mascot-circle">
                <span class="mascot-emoji">🐿️</span>
            </div>
        </div>
    </section>

    <!-- Seção 3: Unidades de Aprendizado -->
    <section id="units" class="section-units">
        <!-- Elementos decorativos -->
        <div class="decorative-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>

        <div class="container">
            <!-- Mascote pequeno no canto -->
            <div class="corner-mascot">
                <span class="mascot-emoji">🐿️</span>
            </div>

            <div class="units-header animate-fade-in-up">
                <h2 class="units-title">
                    O aprendizado é <span class="highlight-text">dividido em unidades</span>, cada uma com:
                </h2>
                <p class="units-subtitle" id="dynamicSubtitle">
                    Conteúdo teórico para você estudar no seu ritmo
                </p>
            </div>

            <!-- Indicadores -->
            <div class="content-indicators">
                <button class="content-dot active" onclick="showContentSlide(0)"></button>
                <button class="content-dot" onclick="showContentSlide(1)"></button>
                <button class="content-dot" onclick="showContentSlide(2)"></button>
            </div>

            <!-- Conteúdo principal -->
            <div class="content-card animate-fade-in-up">
                <div class="content-header"></div>
                <div class="content-carousel">
                    <div class="content-slide active" data-content="0">
                        <div class="content-grid">
                            <div class="content-icon">
                                <div class="icon-wrapper">
                                    <span class="content-emoji">📚</span>
                                </div>
                            </div>
                            <div class="content-text">
                                <h3>Estrutura de Aprendizado Personalizada</h3>
                                <p>
                                    Cada unidade foi cuidadosamente desenvolvida para proporcionar uma experiência de
                                    aprendizado completa e eficaz.
                                    Nossa metodologia garante que você absorva o conhecimento de forma gradual e
                                    consistente.
                                </p>
                            </div>
                        </div>
                        <div class="content-visual">
                            <div class="visual-placeholder">
                                <span class="visual-emoji">📚</span>
                            </div>
                        </div>
                    </div>

                    <div class="content-slide" data-content="1">
                        <div class="content-grid">
                            <div class="content-icon">
                                <div class="icon-wrapper">
                                    <span class="content-emoji">🎮</span>
                                </div>
                            </div>
                            <div class="content-text">
                                <h3>Minigames Interativos</h3>
                                <p>
                                    Transforme seu aprendizado em diversão com nossos minigames educativos!
                                    Pratique vocabulário, gramática e pronúncia de forma lúdica.
                                    Cada minigame é projetado para reforçar conceitos específicos.
                                </p>
                            </div>
                        </div>
                        <div class="content-visual">
                            <div class="visual-placeholder">
                                <span class="visual-emoji">🎮</span>
                            </div>
                        </div>
                    </div>

                    <div class="content-slide" data-content="2">
                        <div class="content-grid">
                            <div class="content-icon">
                                <div class="icon-wrapper">
                                    <span class="content-emoji">💬</span>
                                </div>
                            </div>
                            <div class="content-text">
                                <h3>Perguntas e Respostas Interativas</h3>
                                <p>
                                    Desenvolva suas habilidades através de sessões interativas de perguntas e respostas
                                    com feedback instantâneo.
                                    Nosso sistema adapta as questões ao seu nível, proporcionando desafios adequados.
                                </p>
                            </div>
                        </div>
                        <div class="content-visual">
                            <div class="visual-placeholder">
                                <span class="visual-emoji">💬</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botão de voltar ao início -->
            <div class="back-to-top">
                <button class="btn-back" onclick="scrollToSection('welcome')">
                    ↑ Voltar ao início
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Logo e descrição -->
                <div class="footer-column">
                    <h3 class="footer-logo">SpeakNut</h3>
                    <p class="footer-description">
                        Sua jornada para a fluência começa aqui. Aprenda idiomas de forma divertida e eficaz, onde quer
                        que esteja.
                    </p>
                    <div class="social-icons">
                        <button class="social-btn" aria-label="Facebook"
                            style="background-color: transparent; border: none; color: white;"><i
                                class="fab fa-facebook-f"></i></button>
                        <button class="social-btn" aria-label="Twitter"
                            style="background-color: transparent; border: none; color: white;"><i
                                class="fab fa-twitter"></i></button>
                        <button class="social-btn" aria-label="Instagram"
                            style="background-color: transparent; border: none; color: white;"><i
                                class="fab fa-instagram"></i></button>
                        <button class="social-btn" aria-label="LinkedIn"
                            style="background-color: transparent; border: none; color: white;"><i
                                class="fab fa-linkedin-in"></i></button>
                    </div>
                </div>

                <!-- Links rápidos -->
                <div class="footer-column">
                    <h4 class="footer-title" style="font-size: 20px;">Links Rápidos</h4>
                    <ul class="footer-links">
                        <li><button onclick="scrollToSection('welcome')" style="font-size: 16px;">Início</button></li>
                        <li><button onclick="scrollToSection('main')" style="font-size: 16px;">Sobre Nós</button></li>
                        <li><button style="font-size: 16px;">Termos de Serviço</button></li>
                        <li><button style="font-size: 16px;">Política de Privacidade</button></li>
                    </ul>
                </div>

                <!-- Contato -->
                <div class="footer-column">
                    <h4 class="footer-title" style="font-size: 20px;">Contato</h4>
                    <div class="contact-info">
                        <p>Email: contato@speaknut.com</p>
                        <p>Telefone: (12) 3954-33001</p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 SpeakNut. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

</body>

</html>