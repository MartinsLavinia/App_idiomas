<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include_once __DIR__ . '/../../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o ID da atividade foi fornecido
if (!isset($_GET['atividade_id']) || !is_numeric($_GET['atividade_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da atividade inválido']);
    exit();
}

$atividade_id = (int)$_GET['atividade_id'];
$id_usuario = $_SESSION['id_usuario'];

try {
    $database = new Database();
    $conn = $database->conn;

    $exercicios = [];

    // Buscar exercícios do caminho de aprendizagem (atividade)
    $caminho_id = $atividade_id;
    
    $sql = "
        SELECT 
            e.id,
            e.ordem,
            CASE 
                WHEN e.tipo = 'normal' THEN 'multipla_escolha'
                WHEN e.tipo = 'especial' THEN 'cena_final'
                WHEN e.tipo = 'quiz' THEN 'multipla_escolha'
                ELSE 'multipla_escolha'
            END as tipo_exercicio,
            e.tipo,
            e.pergunta,
            e.conteudo,
            NULL as tentativas,
            NULL as acertos,
            NULL as data_conclusao,
            NULL as pontuacao
        FROM exercicios e
        WHERE e.caminho_id = ?
        ORDER BY e.ordem ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $caminho_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $exercicios[] = $row;
    }
    
    $stmt->close();

    // Se não há exercícios, criar alguns exemplos
    if (empty($exercicios)) {
        $exercicios = [
            [
                'id' => 'demo_1',
                'ordem' => 1,
                'tipo_exercicio' => 'multipla_escolha',
                'tipo' => 'normal',
                'pergunta' => 'Como você diz "Olá" em inglês?',
                'conteudo' => json_encode([
                    'alternativas' => [
                        ['id' => 'a', 'texto' => 'Hello', 'correta' => true],
                        ['id' => 'b', 'texto' => 'Goodbye', 'correta' => false],
                        ['id' => 'c', 'texto' => 'Please', 'correta' => false],
                        ['id' => 'd', 'texto' => 'Thank you', 'correta' => false]
                    ],
                    'explicacao' => 'Hello é a forma mais comum de cumprimentar alguém em inglês.',
                    'dica' => 'É a primeira palavra que aprendemos em qualquer idioma.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_2',
                'ordem' => 2,
                'tipo_exercicio' => 'texto_livre',
                'tipo' => 'normal',
                'pergunta' => 'Complete a frase: My name ___ John.',
                'conteudo' => json_encode([
                    'resposta_correta' => 'is',
                    'alternativas_aceitas' => ['is'],
                    'explicacao' => 'Usamos o verbo to be (is) com a terceira pessoa do singular.',
                    'dica' => 'Lembre-se da conjugação do verbo to be.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_3',
                'ordem' => 3,
                'tipo_exercicio' => 'fala',
                'tipo' => 'normal',
                'pergunta' => 'Fale a seguinte frase:',
                'conteudo' => json_encode([
                    'frase_esperada' => 'Hello, my name is Maria',
                    'pronuncia_fonetica' => '/həˈloʊ maɪ neɪm ɪz məˈriə/',
                    'tolerancia_erro' => 0.8,
                    'palavras_chave' => ['hello', 'name', 'maria'],
                    'feedback_audio' => 'audio/hello_name_maria.mp3'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_4',
                'ordem' => 4,
                'tipo_exercicio' => 'multipla_escolha',
                'tipo' => 'normal',
                'pergunta' => 'Qual é a tradução de "Bom dia"?',
                'conteudo' => json_encode([
                    'alternativas' => [
                        ['id' => 'a', 'texto' => 'Good morning', 'correta' => true],
                        ['id' => 'b', 'texto' => 'Good night', 'correta' => false],
                        ['id' => 'c', 'texto' => 'Good afternoon', 'correta' => false],
                        ['id' => 'd', 'texto' => 'Good evening', 'correta' => false]
                    ],
                    'explicacao' => 'Good morning é usado para cumprimentar alguém pela manhã.',
                    'dica' => 'Morning significa manhã em inglês.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_5',
                'ordem' => 5,
                'tipo_exercicio' => 'texto_livre',
                'tipo' => 'normal',
                'pergunta' => 'Como você se apresentaria em inglês? (Use: My name is...)',
                'conteudo' => json_encode([
                    'resposta_correta' => 'My name is',
                    'alternativas_aceitas' => ['My name is', 'my name is'],
                    'explicacao' => 'Esta é a forma mais comum de se apresentar em inglês.',
                    'dica' => 'Comece com "My name is" seguido do seu nome.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_6',
                'ordem' => 6,
                'tipo_exercicio' => 'multipla_escolha',
                'tipo' => 'normal',
                'pergunta' => 'Como você pergunta "Como você está?" em inglês?',
                'conteudo' => json_encode([
                    'alternativas' => [
                        ['id' => 'a', 'texto' => 'How are you?', 'correta' => true],
                        ['id' => 'b', 'texto' => 'What are you?', 'correta' => false],
                        ['id' => 'c', 'texto' => 'Where are you?', 'correta' => false],
                        ['id' => 'd', 'texto' => 'Who are you?', 'correta' => false]
                    ],
                    'explicacao' => 'How are you? é a pergunta padrão para saber como alguém está.',
                    'dica' => 'How significa "como" em inglês.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_7',
                'ordem' => 7,
                'tipo_exercicio' => 'fala',
                'tipo' => 'normal',
                'pergunta' => 'Pratique a pronúncia:',
                'conteudo' => json_encode([
                    'frase_esperada' => 'How are you today?',
                    'pronuncia_fonetica' => '/haʊ ɑr ju təˈdeɪ/',
                    'tolerancia_erro' => 0.8,
                    'palavras_chave' => ['how', 'are', 'you', 'today'],
                    'feedback_audio' => 'audio/how_are_you_today.mp3'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_8',
                'ordem' => 8,
                'tipo_exercicio' => 'texto_livre',
                'tipo' => 'normal',
                'pergunta' => 'Complete o diálogo: A: How are you? B: I ___ fine, thank you.',
                'conteudo' => json_encode([
                    'resposta_correta' => 'am',
                    'alternativas_aceitas' => ['am'],
                    'explicacao' => 'Usamos "am" com o pronome "I" (eu).',
                    'dica' => 'I + am = I am (ou I\'m)'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_9',
                'ordem' => 9,
                'tipo_exercicio' => 'multipla_escolha',
                'tipo' => 'normal',
                'pergunta' => 'Qual é a resposta apropriada para "Thank you"?',
                'conteudo' => json_encode([
                    'alternativas' => [
                        ['id' => 'a', 'texto' => 'You\'re welcome', 'correta' => true],
                        ['id' => 'b', 'texto' => 'Thank you too', 'correta' => false],
                        ['id' => 'c', 'texto' => 'Hello', 'correta' => false],
                        ['id' => 'd', 'texto' => 'Goodbye', 'correta' => false]
                    ],
                    'explicacao' => 'You\'re welcome é a resposta padrão para "Thank you".',
                    'dica' => 'É uma expressão de cortesia para aceitar agradecimentos.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_10',
                'ordem' => 10,
                'tipo_exercicio' => 'fala',
                'tipo' => 'normal',
                'pergunta' => 'Pratique uma apresentação completa:',
                'conteudo' => json_encode([
                    'frase_esperada' => 'Hello, my name is John. How are you?',
                    'pronuncia_fonetica' => '/həˈloʊ maɪ neɪm ɪz ʤɑn haʊ ɑr ju/',
                    'tolerancia_erro' => 0.7,
                    'palavras_chave' => ['hello', 'name', 'john', 'how', 'are', 'you'],
                    'feedback_audio' => 'audio/complete_introduction.mp3'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_11',
                'ordem' => 11,
                'tipo_exercicio' => 'texto_livre',
                'tipo' => 'normal',
                'pergunta' => 'Escreva uma apresentação completa em inglês (use: Hello, my name is..., I am from..., How are you?)',
                'conteudo' => json_encode([
                    'resposta_correta' => 'Hello, my name is',
                    'alternativas_aceitas' => ['Hello', 'my name is', 'I am from', 'How are you'],
                    'explicacao' => 'Uma apresentação completa inclui saudação, nome, origem e pergunta sobre o outro.',
                    'dica' => 'Combine as frases que você aprendeu nos exercícios anteriores.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ],
            [
                'id' => 'demo_12',
                'ordem' => 12,
                'tipo_exercicio' => 'multipla_escolha',
                'tipo' => 'normal',
                'pergunta' => 'Revisão: Qual frase está INCORRETA?',
                'conteudo' => json_encode([
                    'alternativas' => [
                        ['id' => 'a', 'texto' => 'My name am John', 'correta' => true],
                        ['id' => 'b', 'texto' => 'Hello, how are you?', 'correta' => false],
                        ['id' => 'c', 'texto' => 'I am fine, thank you', 'correta' => false],
                        ['id' => 'd', 'texto' => 'You\'re welcome', 'correta' => false]
                    ],
                    'explicacao' => 'A frase correta seria "My name IS John", não "My name AM John".',
                    'dica' => 'Lembre-se: I am, You are, He/She/It is, My name is.'
                ]),
                'tentativas' => null,
                'acertos' => null,
                'data_conclusao' => null,
                'pontuacao' => null
            ]
        ];
    }

    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'exercicios' => $exercicios
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
