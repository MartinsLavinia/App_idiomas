<?php
// Script para criar exercícios especiais de exemplo
include_once 'conexao.php';

$database = new Database();
$conn = $database->conn;

// Verificar se a tabela existe e tem dados
$sql_check = "SELECT COUNT(*) as total FROM exercicios_especiais";
$result = $conn->query($sql_check);
$count = $result->fetch_assoc()['total'];

echo "Exercícios especiais existentes: " . $count . "\n";

if ($count == 0) {
    echo "Inserindo exercícios especiais de exemplo...\n";
    
    // Exercício 1: Música em inglês
    $exercicio1 = [
        'tipo_exercicio' => 'observar',
        'link_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'letra_musica' => "Never gonna give you up\nNever gonna let you down\nNever gonna run around and desert you\nNever gonna make you cry\nNever gonna say goodbye\nNever gonna tell a lie and hurt you"
    ];
    
    $sql1 = "INSERT INTO exercicios_especiais (id_bloco, tipo, titulo, descricao, url_media, transcricao, pergunta, tipo_exercicio, conteudo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("issssssss", 
        1, // id_bloco
        'musica', // tipo
        'Never Gonna Give You Up', // titulo
        'Aprenda inglês com música clássica', // descricao
        $exercicio1['link_video'], // url_media
        $exercicio1['letra_musica'], // transcricao
        'Assista ao vídeo e acompanhe a letra da música', // pergunta
        'observar', // tipo_exercicio
        json_encode($exercicio1) // conteudo
    );
    
    if ($stmt1->execute()) {
        echo "✓ Exercício 1 inserido com sucesso\n";
    } else {
        echo "✗ Erro ao inserir exercício 1: " . $stmt1->error . "\n";
    }
    
    // Exercício 2: Vídeo educativo
    $exercicio2 = [
        'tipo_exercicio' => 'alternativa',
        'link_video' => 'https://www.youtube.com/watch?v=YQHsXMglC9A',
        'letra_musica' => 'Hello, how are you?\nI am fine, thank you.\nWhat is your name?\nMy name is John.',
        'alternativas' => [
            'a' => 'Hello means goodbye',
            'b' => 'Hello means hi or greetings',
            'c' => 'Hello means thank you',
            'd' => 'Hello means sorry'
        ],
        'resposta_correta' => 'b'
    ];
    
    $sql2 = "INSERT INTO exercicios_especiais (id_bloco, tipo, titulo, descricao, url_media, transcricao, pergunta, tipo_exercicio, conteudo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("issssssss", 
        1, // id_bloco
        'video', // tipo
        'Basic English Greetings', // titulo
        'Aprenda cumprimentos básicos em inglês', // descricao
        $exercicio2['link_video'], // url_media
        $exercicio2['letra_musica'], // transcricao
        'O que significa "Hello" em inglês?', // pergunta
        'alternativa', // tipo_exercicio
        json_encode($exercicio2) // conteudo
    );
    
    if ($stmt2->execute()) {
        echo "✓ Exercício 2 inserido com sucesso\n";
    } else {
        echo "✗ Erro ao inserir exercício 2: " . $stmt2->error . "\n";
    }
    
    // Exercício 3: Completar lacunas
    $exercicio3 = [
        'tipo_exercicio' => 'completar',
        'link_video' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk',
        'letra_musica' => 'Despacito\nQuiero respirar tu cuello ______\nDeja que te diga cosas al oído\nPara que te acuerdes si no estás ______',
        'palavras_completar' => 'despacito, conmigo'
    ];
    
    $sql3 = "INSERT INTO exercicios_especiais (id_bloco, tipo, titulo, descricao, url_media, transcricao, pergunta, tipo_exercicio, conteudo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("issssssss", 
        1, // id_bloco
        'musica', // tipo
        'Despacito - Luis Fonsi', // titulo
        'Complete a letra da música em espanhol', // descricao
        $exercicio3['link_video'], // url_media
        $exercicio3['letra_musica'], // transcricao
        'Complete as palavras que faltam na música', // pergunta
        'completar', // tipo_exercicio
        json_encode($exercicio3) // conteudo
    );
    
    if ($stmt3->execute()) {
        echo "✓ Exercício 3 inserido com sucesso\n";
    } else {
        echo "✗ Erro ao inserir exercício 3: " . $stmt3->error . "\n";
    }
    
    echo "\nTodos os exercícios especiais foram inseridos!\n";
} else {
    echo "Exercícios especiais já existem na base de dados.\n";
}

// Verificar novamente
$result2 = $conn->query($sql_check);
$count2 = $result2->fetch_assoc()['total'];
echo "Total de exercícios especiais após inserção: " . $count2 . "\n";

$database->closeConnection();
?>