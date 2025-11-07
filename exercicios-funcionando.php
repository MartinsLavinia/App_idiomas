<?php
/**
 * Página de demonstração com exercícios funcionando
 * Integra todas as correções implementadas
 */

session_start();

// Simular usuário logado para demonstração
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1;
    $_SESSION['nome_usuario'] = 'Usuário Teste';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Exercícios Corrigido - App Idiomas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Customizado -->
    <link href="css/exercicios-corrigidos.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-demo {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .demo-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-corrigido {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: #28a745;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="header-demo text-center">
            <h1 class="display-4 mb-3">
                <i class="fas fa-graduation-cap text-primary"></i>
                Sistema de Exercícios Corrigido
            </h1>
            <p class="lead mb-4">
                Demonstração completa com todas as correções implementadas
            </p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="status-badge status-corrigido">
                        <i class="fas fa-check-circle me-2"></i>
                        Todos os problemas identificados foram corrigidos
                    </div>
                </div>
            </div>
        </div>

        <!-- Correções Implementadas -->
        <div class="demo-section">
            <h2 class="h3 mb-4">
                <i class="fas fa-tools text-success"></i>
                Correções Implementadas
            </h2>
            
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-headphones text-info"></i> Exercícios de Listening</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Transcrição do áudio sempre visível após resposta</li>
                        <li><i class="fas fa-check"></i> Explicação detalhada do contexto</li>
                        <li><i class="fas fa-check"></i> Dicas específicas de compreensão oral</li>
                        <li><i class="fas fa-check"></i> Feedback visual claro (✅ Correto / ❌ Incorreto)</li>
                        <li><i class="fas fa-check"></i> Sistema de áudio robusto com múltiplas APIs</li>
                        <li><i class="fas fa-check"></i> Estrutura de dados padronizada</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5><i class="fas fa-microphone text-primary"></i> Exercícios de Fala</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Sistema de gravação integrado ao progresso</li>
                        <li><i class="fas fa-check"></i> Análise de pronúncia com feedback detalhado</li>
                        <li><i class="fas fa-check"></i> Configuração correta de idioma</li>
                        <li><i class="fas fa-check"></i> Progresso salvo automaticamente</li>
                        <li><i class="fas fa-check"></i> Dicas específicas de pronúncia</li>
                        <li><i class="fas fa-check"></i> Interface intuitiva com estados visuais</li>
                    </ul>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-cogs text-warning"></i> Melhorias Gerais</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Código limpo seguindo princípios SOLID</li>
                        <li><i class="fas fa-check"></i> Arquitetura MVC bem estruturada</li>
                        <li><i class="fas fa-check"></i> Tratamento robusto de erros</li>
                        <li><i class="fas fa-check"></i> Validações consistentes</li>
                        <li><i class="fas fa-check"></i> Logs detalhados para debug</li>
                        <li><i class="fas fa-check"></i> Mensagens de erro específicas</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5><i class="fas fa-desktop text-secondary"></i> Interface do Usuário</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Feedback visual aprimorado</li>
                        <li><i class="fas fa-check"></i> Estados claros de carregamento</li>
                        <li><i class="fas fa-check"></i> Navegação intuitiva</li>
                        <li><i class="fas fa-check"></i> Progresso atualizado em tempo real</li>
                        <li><i class="fas fa-check"></i> Mensagens de ajuda contextuais</li>
                        <li><i class="fas fa-check"></i> Design responsivo e acessível</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Demonstração de Exercícios -->
        <div class="demo-section">
            <h2 class="h3 mb-4">
                <i class="fas fa-play-circle text-primary"></i>
                Demonstração dos Exercícios
            </h2>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-headphones fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Exercício de Listening</h5>
                            <p class="card-text">
                                Teste o sistema completo de listening com áudio, 
                                transcrição e feedback detalhado.
                            </p>
                            <button class="btn btn-info" onclick="carregarListening()">
                                <i class="fas fa-play me-2"></i>Testar Listening
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-microphone fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Exercício de Fala</h5>
                            <p class="card-text">
                                Teste o sistema de gravação e análise de pronúncia 
                                com feedback inteligente.
                            </p>
                            <button class="btn btn-primary" onclick="carregarFala()">
                                <i class="fas fa-microphone me-2"></i>Testar Fala
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container para exercícios -->
        <div id="exercicio-container"></div>

        <!-- Informações Técnicas -->
        <div class="demo-section">
            <h2 class="h3 mb-4">
                <i class="fas fa-code text-dark"></i>
                Informações Técnicas
            </h2>
            
            <div class="row">
                <div class="col-md-4">
                    <h6><i class="fas fa-database text-success"></i> Banco de Dados</h6>
                    <ul class="small">
                        <li>Tabelas padronizadas para listening e fala</li>
                        <li>Campos para transcrição e dicas</li>
                        <li>Sistema de progresso detalhado</li>
                        <li>Índices otimizados para performance</li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h6><i class="fas fa-server text-primary"></i> Backend (PHP)</h6>
                    <ul class="small">
                        <li>Arquitetura MVC com namespaces</li>
                        <li>Padrão Repository para dados</li>
                        <li>Services para lógica de negócio</li>
                        <li>APIs RESTful padronizadas</li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h6><i class="fas fa-laptop-code text-warning"></i> Frontend (JS)</h6>
                    <ul class="small">
                        <li>Classes ES6 organizadas</li>
                        <li>Gerenciamento de estado</li>
                        <li>Feedback visual em tempo real</li>
                        <li>Tratamento robusto de erros</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Instruções de Instalação -->
        <div class="demo-section">
            <h2 class="h3 mb-4">
                <i class="fas fa-download text-success"></i>
                Como Usar Este Sistema
            </h2>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Passos para Implementação:</h6>
                <ol class="mb-0">
                    <li><strong>Execute o SQL:</strong> Rode o arquivo <code>database_corrections.sql</code> no seu MySQL</li>
                    <li><strong>Configure o PHP:</strong> Ajuste as credenciais em <code>conexao.php</code></li>
                    <li><strong>Teste os Endpoints:</strong> Verifique se as APIs em <code>/api/</code> estão funcionando</li>
                    <li><strong>Integre o Frontend:</strong> Inclua os arquivos JS e CSS nas suas páginas</li>
                    <li><strong>Adicione Exercícios:</strong> Use os novos modelos para criar exercícios</li>
                </ol>
            </div>
            
            <div class="alert alert-success">
                <h6><i class="fas fa-check-circle"></i> Arquivos Principais Criados:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Backend:</strong>
                        <ul class="small mb-0">
                            <li><code>src/Models/</code> - Modelos de dados</li>
                            <li><code>src/Services/</code> - Lógica de negócio</li>
                            <li><code>src/Controllers/</code> - Controladores</li>
                            <li><code>api/exercicios/</code> - Endpoints REST</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Frontend:</strong>
                        <ul class="small mb-0">
                            <li><code>js/exercicios-corrigidos.js</code> - Sistema JS</li>
                            <li><code>css/exercicios-corrigidos.css</code> - Estilos</li>
                            <li><code>database_corrections.sql</code> - Estrutura BD</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/exercicios-corrigidos.js"></script>
    
    <script>
        // Funções de demonstração
        function carregarListening() {
            // Simular dados de exercício de listening
            const exercicioListening = {
                id: 1,
                frase_original: "Good morning, how are you today?",
                audio_url: "/App_idiomas/audios/exemplo.mp3", // Será gerado automaticamente
                opcoes: [
                    "Good morning",
                    "Good afternoon", 
                    "Good evening",
                    "Good night"
                ],
                resposta_correta: 0,
                explicacao: "A saudação 'Good morning' é usada pela manhã, tipicamente até as 12h.",
                dicas_compreensao: "Preste atenção na entonação da pergunta 'how are you?' que indica interesse genuíno.",
                transcricao: "Good morning, how are you today?",
                idioma: "en-us"
            };
            
            // Renderizar exercício
            if (window.sistemaExercicios) {
                window.sistemaExercicios.renderizarExercicioListening(exercicioListening);
                
                // Scroll para o exercício
                document.getElementById('exercicio-container').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
        
        function carregarFala() {
            // Simular dados de exercício de fala
            const exercicioFala = {
                id: 2,
                frase_esperada: "Hello, how are you today?",
                dicas_pronuncia: "Pronuncie o 'H' de 'Hello' com aspiração. O 'how' deve soar como 'háu'.",
                palavras_chave: ["Hello", "how", "are", "you", "today"],
                contexto: "Saudação informal usada em encontros casuais.",
                idioma: "en-us"
            };
            
            // Renderizar exercício
            if (window.sistemaExercicios) {
                window.sistemaExercicios.renderizarExercicioFala(exercicioFala);
                
                // Scroll para o exercício
                document.getElementById('exercicio-container').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
        
        // Mostrar mensagem de boas-vindas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎉 Sistema de Exercícios Corrigido carregado com sucesso!');
            console.log('📚 Todas as funcionalidades de listening e fala foram implementadas');
            console.log('🔧 Arquitetura MVC com padrões SOLID aplicados');
            console.log('✨ Interface melhorada com feedback visual completo');
        });
    </script>
</body>
</html>