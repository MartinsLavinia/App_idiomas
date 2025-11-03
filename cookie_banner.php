<?php
// O banner só será exibido se o cookie 'cookie_consent' não estiver definido.
// A sintaxe alternativa (if (...) : ... endif;) é válida e mantida.
if (!isset($_COOKIE['cookie_consent'])) :
?>

<style>
/* --- Estilos CSS --- */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #2d3748; /* Um cinza escuro, combina com o tema */
    color: #ffffff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1050; /* Z-index alto para ficar sobre outros elementos */
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
    flex-wrap: wrap;
    gap: 15px;
}
.cookie-banner p {
    margin: 0;
    flex-grow: 1;
    font-size: 0.9rem;
}
.cookie-banner .cookie-buttons {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}
.cookie-banner a {
    color: #fbbf24; /* Amarelo para destaque */
    text-decoration: underline;
}
.cookie-banner button { /* Estilo base para botões */
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}
.cookie-banner .btn-aceitar {
    background-color: #7c3aed; /* Roxo do tema */
    color: #ffffff;
}
.cookie-banner .btn-aceitar:hover {
    background-color: #581c87; /* Roxo mais escuro */
}
.cookie-banner .btn-recusar {
    background-color: transparent;
    color: #a78bfa; /* Um roxo mais claro para diferenciar */
    border: 2px solid #581c87;
    padding: 8px 25px; /* Padding ajustado por causa da borda */
}
.cookie-banner .btn-recusar:hover {
    background-color: #581c87;
    color: #ffffff;
}
</style>

<div class="cookie-banner" id="cookieBanner">
    <p>Nós utilizamos cookies para garantir a melhor experiência em nosso site. Ao continuar, você concorda com nossa <a href="politica_de_privacidade.php" target="_blank">Política de Privacidade</a>.</p>
    <div class="cookie-buttons">
        <button class="btn-recusar" id="rejectCookie">Recusar</button>
        <button class="btn-aceitar" id="acceptCookie">Aceitar</button>
    </div>
</div>

<script>
    const cookieBanner = document.getElementById('cookieBanner');
    // 365 dias * 24 horas * 60 minutos * 60 segundos
    const oneYearInSeconds = 365 * 24 * 60 * 60;
    
    // Função para definir o cookie e esconder o banner
    function setCookieAndHide(value) {
        // Define o cookie com a chave 'cookie_consent', o valor (true ou false),
        // o caminho '/' (para todo o site) e o tempo de expiração em segundos.
        // Adicionando 'SameSite=Lax' e 'Secure' (se o site for HTTPS) para melhor segurança.
        // Como o JS não sabe se é HTTPS, usaremos apenas o SameSite.
        // Para adicionar o 'Secure' você precisaria de um gerador de cookies mais sofisticado ou PHP.
        document.cookie = `cookie_consent=${value}; path=/; max-age=${oneYearInSeconds}; SameSite=Lax`;
        
        // Esconde o banner
        cookieBanner.style.display = 'none';
    }

    // Listener para o botão 'Aceitar'
    document.getElementById('acceptCookie').addEventListener('click', function() {
        setCookieAndHide('true');
    });

    // Listener para o botão 'Recusar'
    document.getElementById('rejectCookie').addEventListener('click', function() {
        // Geralmente, "Recusar" ainda define um cookie para *lembrar* a recusa e não mostrar o banner novamente.
        setCookieAndHide('false');
    });
</script>

<?php endif; ?>