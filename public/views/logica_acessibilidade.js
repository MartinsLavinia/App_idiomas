document.addEventListener('DOMContentLoaded', function () {
    const widget = document.getElementById('accessibility-widget');
    const toggleBtn = document.getElementById('accessibility-toggle');
    const panel = document.getElementById('accessibility-panel');
    const closeBtn = document.getElementById('close-panel');
    const optionBtns = document.querySelectorAll('.option-btn');
    const submenuBtns = document.querySelectorAll('.submenu-btn');
    
    // Elementos dos controles deslizantes
    const fontSlider = document.getElementById('font-slider');
    const fontFill = document.getElementById('font-fill');
    const fontValue = document.getElementById('font-value');
    const fontDecrease = document.getElementById('font-decrease');
    const fontIncrease = document.getElementById('font-increase');
    const fontBtn = document.getElementById('font-size-btn');
    const fontSubmenu = document.getElementById('font-submenu');
    const fontClose = document.getElementById('font-close');
    
    const alignBtn = document.getElementById('align-btn');
    const alignSubmenu = document.getElementById('align-submenu');
    const alignClose = document.getElementById('align-close');

    // Botões de leitura
    const readPageBtn = document.getElementById('read-page-btn');
    let speechSynthesis = window.speechSynthesis;
    let isReading = false;
    let currentUtterance = null;

    // Estado para fonte (0 = tamanho original)
    let fontSize = parseInt(localStorage.getItem('fontSize')) || 0;

    // Estado dos botões com toggle
    let states = {
        contrast: false,
        highlightLinks: false,
        textSpacing: false,
        pauseAnimations: false,
        dyslexiaFriendly: false,
        tooltips: false,
        textAlign: 'original'
    };

    // Função para atualizar o preenchimento do slider
    function updateSliderFill(slider, fill) {
        const value = slider.value;
        const min = slider.min;
        const max = slider.max;
        const percentage = ((value - min) / (max - min)) * 100;
        fill.style.width = percentage + '%';
    }

    // Inicializar sliders
    function initializeSliders() {
        updateSliderFill(fontSlider, fontFill);
        updateFontValue();
    }

    // Atualizar valor exibido da fonte
    function updateFontValue() {
        if (fontSize === 0) {
            fontValue.textContent = 'Original';
        } else {
            fontValue.textContent = fontSize + 'px';
        }
    }

    // Função para garantir tamanho consistente dos botões
    function enforceConsistentButtonSizes() {
        const optionBtns = document.querySelectorAll('.option-btn');
        const containers = document.querySelectorAll('.option-btn-container');
        
        optionBtns.forEach(btn => {
            btn.style.height = '95px';
            btn.style.minHeight = '95px';
            btn.style.maxHeight = '95px';
        });
        
        containers.forEach(container => {
            container.style.height = '95px';
            container.style.minHeight = '95px';
        });
    }

    // Mostra ou esconde painel e atualiza aria-expanded
    function togglePanel(show) {
        if (show) {
            panel.hidden = false;
            panel.classList.add('active');
            toggleBtn.setAttribute('aria-expanded', 'true');
            panel.focus();
            setTimeout(enforceConsistentButtonSizes, 10);
        } else {
            panel.hidden = true;
            panel.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
            closeAllSubmenus();
        }
    }

    toggleBtn.addEventListener('click', () => {
        const isActive = !panel.hidden;
        togglePanel(!isActive);
    });
    
    closeBtn.addEventListener('click', () => togglePanel(false));

    // Fecha painel clicando fora
    document.addEventListener('click', e => {
        if (!widget.contains(e.target) && !panel.hidden) {
            togglePanel(false);
        }
    });

    // Navegação pelo teclado no painel: ESC para fechar
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !panel.hidden) {
            togglePanel(false);
            toggleBtn.focus();
        }
    });

    // Eventos para os botões principais
    optionBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const action = this.getAttribute('data-action');
            
            // Verificar se é um botão com submenu
            if (this.id === 'font-size-btn') {
                toggleSubmenu(fontSubmenu);
            } else if (this.id === 'align-btn') {
                toggleSubmenu(alignSubmenu);
            } else {
                handleAccessibilityAction(action, this);
            }
        });
    });

    // Evento para o botão de ler página
    readPageBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!isReading) {
            startReading();
        } else {
            stopReading();
        }
    });

    // Função para iniciar leitura da página
    function startReading() {
        if (!speechSynthesis) {
            alert('Seu navegador não suporta leitura de texto.');
            return;
        }

        // Parar qualquer leitura anterior
        stopReading();

        // Obter todo o texto da página
        const pageText = getPageText();
        
        if (!pageText.trim()) {
            alert('Nenhum texto encontrado para ler.');
            return;
        }

        // Criar utterance
        currentUtterance = new SpeechSynthesisUtterance(pageText);
        currentUtterance.lang = 'pt-BR';
        currentUtterance.rate = 0.8;
        currentUtterance.pitch = 1;
        currentUtterance.volume = 1;

        // Atualizar interface
        isReading = true;
        readPageBtn.innerHTML = '<i class="fas fa-stop" aria-hidden="true"></i><br> Parar leitura';
        readPageBtn.id = 'stop-reading-btn';
        readPageBtn.classList.add('reading-active');

        // Evento quando a leitura terminar
        currentUtterance.onend = function() {
            stopReading();
        };

        // Evento quando ocorrer erro
        currentUtterance.onerror = function() {
            stopReading();
            alert('Erro ao tentar ler a página.');
        };

        // Iniciar leitura
        speechSynthesis.speak(currentUtterance);
    }

    // Função para parar leitura
    function stopReading() {
        if (speechSynthesis && isReading) {
            speechSynthesis.cancel();
        }
        
        isReading = false;
        currentUtterance = null;
        readPageBtn.innerHTML = '<i class="fas fa-volume-up" aria-hidden="true"></i><br> Ler página';
        readPageBtn.id = 'read-page-btn';
        readPageBtn.classList.remove('reading-active');
    }

    // Função para obter texto da página (excluindo elementos irrelevantes)
    function getPageText() {
        // Clonar o body para não modificar o DOM original
        const clone = document.body.cloneNode(true);
        
        // Remover elementos que não devem ser lidos
        const elementsToRemove = clone.querySelectorAll(
            'script, style, nav, header, footer, .accessibility-widget, [aria-hidden="true"]'
        );
        elementsToRemove.forEach(el => el.remove());
        
        // Obter texto limpo
        return clone.textContent.replace(/\s+/g, ' ').trim();
    }

    // Eventos para os botões dos submenus
    submenuBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.getAttribute('data-action');
            handleAccessibilityAction(action, this);
            closeAllSubmenus();
        });
    });

    // Botões de fechar nos submenus
    fontClose.addEventListener('click', function() {
        closeAllSubmenus();
    });

    alignClose.addEventListener('click', function() {
        closeAllSubmenus();
    });

    // Funções para controlar submenus
    function toggleSubmenu(submenu) {
        closeAllSubmenus();
        submenu.classList.add('active');
    }

    function closeAllSubmenus() {
        fontSubmenu.classList.remove('active');
        alignSubmenu.classList.remove('active');
    }

    // Fechar submenus ao clicar fora deles
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.option-btn-container')) {
            closeAllSubmenus();
        }
    });

    // Controle deslizante de fonte
    fontSlider.value = fontSize;
    
    fontSlider.addEventListener('input', function() {
        fontSize = parseInt(this.value);
        updateFontValue();
        updateSliderFill(fontSlider, fontFill);
        applyFontSize();
    });

    fontDecrease.addEventListener('click', function() {
        fontSize = Math.max(parseInt(fontSlider.min), fontSize - 2);
        fontSlider.value = fontSize;
        updateFontValue();
        updateSliderFill(fontSlider, fontFill);
        applyFontSize();
    });

    fontIncrease.addEventListener('click', function() {
        fontSize = Math.min(parseInt(fontSlider.max), fontSize + 2);
        fontSlider.value = fontSize;
        updateFontValue();
        updateSliderFill(fontSlider, fontFill);
        applyFontSize();
    });

    function applyFontSize() {
        const elements = document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, a, span, li, label, button, div');
        
        if (fontSize === 0) {
            // Volta ao tamanho original
            elements.forEach(el => {
                el.style.fontSize = '';
            });
        } else {
            // Aplica o tamanho personalizado
            elements.forEach(el => {
                el.style.fontSize = fontSize + 'px';
            });
        }
        localStorage.setItem('fontSize', fontSize);
    }

    function applyTextAlign() {
        // Remove todas as classes de alinhamento
        document.body.classList.remove('text-align-left', 'text-align-center', 'text-align-justify');
        
        if (states.textAlign !== 'original') {
            document.body.classList.add(states.textAlign);
        }
    }

    function handleAccessibilityAction(action, btn) {
        const body = document.body;
        switch (action) {
            case 'contrast':
                states.contrast = !states.contrast;
                body.classList.toggle('contrast-mode', states.contrast);
                btn.setAttribute('aria-pressed', states.contrast);
                break;

            case 'highlight-links':
                states.highlightLinks = !states.highlightLinks;
                body.classList.toggle('highlight-links', states.highlightLinks);
                btn.setAttribute('aria-pressed', states.highlightLinks);
                break;

            case 'text-spacing':
                states.textSpacing = !states.textSpacing;
                body.classList.toggle('text-spacing', states.textSpacing);
                btn.setAttribute('aria-pressed', states.textSpacing);
                break;

            case 'pause-animations':
                states.pauseAnimations = !states.pauseAnimations;
                body.classList.toggle('pause-animations', states.pauseAnimations);
                btn.setAttribute('aria-pressed', states.pauseAnimations);
                break;

            case 'dyslexia-friendly':
                states.dyslexiaFriendly = !states.dyslexiaFriendly;
                body.classList.toggle('dyslexia-friendly', states.dyslexiaFriendly);
                btn.setAttribute('aria-pressed', states.dyslexiaFriendly);
                break;

            case 'tooltips':
                states.tooltips = !states.tooltips;
                body.classList.toggle('tooltip-enabled', states.tooltips);
                btn.setAttribute('aria-pressed', states.tooltips);
                break;

            case 'text-align-original':
                states.textAlign = 'original';
                applyTextAlign();
                closeAllSubmenus();
                break;
                
            case 'text-align-left':
                states.textAlign = 'text-align-left';
                applyTextAlign();
                closeAllSubmenus();
                break;
                
            case 'text-align-center':
                states.textAlign = 'text-align-center';
                applyTextAlign();
                closeAllSubmenus();
                break;
                
            case 'text-align-justify':
                states.textAlign = 'text-align-justify';
                applyTextAlign();
                closeAllSubmenus();
                break;

            case 'reset-all':
                resetAll();
                break;

            case 'move-hide':
                const moved = widget.classList.toggle('moved');
                if (moved) {
                    btn.style.backgroundColor = '#fbbf24';
                } else {
                    btn.style.backgroundColor = '';
                }
                break;
        }
    }

    function resetAll() {
        // Parar leitura se estiver ativa
        stopReading();
        
        // Remove todas as classes de acessibilidade
        document.body.className = '';
        
        // Remove todos os estilos inline
        document.querySelectorAll('*').forEach(el => {
            el.style.fontSize = '';
            el.style.lineHeight = '';
            el.style.letterSpacing = '';
            el.style.wordSpacing = '';
            el.style.textAlign = '';
            el.style.fontFamily = '';
        });
        
        // Reseta estados
        fontSize = 0;
        fontSlider.value = fontSize;
        
        states = {
            contrast: false,
            highlightLinks: false,
            textSpacing: false,
            pauseAnimations: false,
            dyslexiaFriendly: false,
            tooltips: false,
            textAlign: 'original'
        };

        initializeSliders();
        applyFontSize();

        // Reseta botões
        optionBtns.forEach(btn => {
            btn.setAttribute('aria-pressed', false);
            btn.style.backgroundColor = '';
        });

        // Limpa localStorage
        localStorage.removeItem('fontSize');
        closeAllSubmenus();
    }

    // Inicialização
    enforceConsistentButtonSizes();
    window.addEventListener('resize', enforceConsistentButtonSizes);
    initializeSliders();

    // Aplica configurações salvas ao carregar
    if (localStorage.getItem('fontSize')) {
        applyFontSize();
    }

    // Atalhos: Alt+1 até Alt+0 para facilitar uso rápido
    document.addEventListener('keydown', e => {
        if (e.altKey && !e.ctrlKey && !e.metaKey) {
            switch (e.key) {
                case '1': document.querySelector('[data-action="contrast"]').click(); break;
                case '2': document.querySelector('[data-action="highlight-links"]').click(); break;
                case '3': fontBtn.click(); break;
                case '4': document.querySelector('[data-action="text-spacing"]').click(); break;
                case '5': document.querySelector('[data-action="pause-animations"]').click(); break;
                case '6': document.querySelector('[data-action="dyslexia-friendly"]').click(); break;
                case '7': readPageBtn.click(); break;
                case '8': document.querySelector('[data-action="tooltips"]').click(); break;
                case '0': alignBtn.click(); break;
                default: break;
            }
        }

        // CTRL+U alterna painel
        if (e.ctrlKey && e.key.toLowerCase() === 'u') {
            e.preventDefault();
            togglePanel(panel.hidden);
        }

        // ESC para parar leitura
        if (e.key === 'Escape' && isReading) {
            stopReading();
        }
    });

    // Parar leitura quando a página for fechada
    window.addEventListener('beforeunload', function() {
        stopReading();
    });
});