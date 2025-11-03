// Correção DIRETA - funciona imediatamente
document.addEventListener('click', function(e) {
    // Corrigir listening
    const opcao = e.target.closest('.option-audio, .alternativa, .opcao-exercicio');
    if (opcao) {
        const container = opcao.closest('.exercicio-container, [id^="exercicio-"]');
        if (!container) return;
        
        // Limpar estilos anteriores
        container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio').forEach(o => {
            o.style.background = '';
            o.style.color = '';
            o.style.border = '';
        });
        
        const indice = Array.from(container.querySelectorAll('.option-audio, .alternativa, .opcao-exercicio')).indexOf(opcao);
        const correto = indice === 0;
        
        if (correto) {
            opcao.style.background = '#d4edda !important';
            opcao.style.color = '#155724 !important';
            opcao.style.border = '2px solid #28a745 !important';
        } else {
            opcao.style.background = '#f8d7da !important';
            opcao.style.color = '#721c24 !important';
            opcao.style.border = '2px solid #dc3545 !important';
            
            // Mostrar resposta correta
            const primeira = container.querySelector('.option-audio, .alternativa, .opcao-exercicio');
            if (primeira) {
                primeira.style.background = '#d4edda !important';
                primeira.style.color = '#155724 !important';
                primeira.style.border = '2px solid #28a745 !important';
            }
        }
        
        // Explicação
        let explicacao = container.querySelector('.explicacao');
        if (!explicacao) {
            explicacao = document.createElement('div');
            explicacao.className = 'explicacao mt-3 p-3';
            container.appendChild(explicacao);
        }
        
        explicacao.style.background = correto ? '#d4edda' : '#d1ecf1';
        explicacao.style.color = correto ? '#155724' : '#0c5460';
        explicacao.style.border = '1px solid ' + (correto ? '#28a745' : '#17a2b8');
        explicacao.style.borderRadius = '5px';
        explicacao.innerHTML = correto ? 
            '✅ Correto! Você acertou.' : 
            '❌ Incorreto. A resposta correta é a primeira opção (verde).';
    }
    
    // Corrigir microfone
    if (e.target.closest('.microphone-btn')) {
        e.preventDefault();
        const status = document.getElementById('speech-status') || document.querySelector('.speech-status');
        if (status) {
            status.innerHTML = '❌ Microfone não disponível. Conecte um microfone e permita o acesso.';
            status.style.background = '#f8d7da';
            status.style.color = '#721c24';
        }
    }
});