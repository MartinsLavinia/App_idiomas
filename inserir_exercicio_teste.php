<?php
/**
 * Script para inserir exercício de teste no banco
 */

include_once __DIR__ . '/conexao.php';

$database = new Database();
$conn = $database->conn;

// Verificar se já existe exercício de teste
$check = $conn->query("SELECT id FROM exercicios WHERE id = 1");
if ($check && $check->num_rows > 0) {
    echo "✅ Exercício de teste já existe no banco.<br>";
} else {
    // Inserir exercício de listening de teste
    $conteudo_listening = json_encode([
        'frase_original' => 'Good morning, how are you?',
        'audio_url' => '/App_idiomas/audios/teste.mp3',
        'opcoes' => ['Good morning', 'Good afternoon', 'Good evening', 'Good night'],
        'resposta_correta' => 0,
        'explicacao' => 'O áudio diz "Good morning, how are you?" que é uma saudação matinal.',
        'transcricao' => 'Good morning, how are you?',
        'dicas_compreensao' => 'Ouça com atenção a saudação no início da frase.',
        'idioma' => 'en-us',
        'tipo_exercicio' => 'listening'
    ], JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO exercicios (id, caminho_id, bloco_id, ordem, tipo, pergunta, conteudo, categoria) 
            VALUES (1, 1, 1, 1, 'normal', 'Ouça o áudio e escolha a resposta correta:', ?, 'audicao')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $conteudo_listening);
    
    if ($stmt->execute()) {
        echo "✅ Exercício de listening de teste inserido com sucesso!<br>";
    } else {
        echo "❌ Erro ao inserir exercício: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

// Verificar estrutura do exercício
$result = $conn->query("SELECT id, tipo, categoria, conteudo FROM exercicios WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $exercicio = $result->fetch_assoc();
    echo "<h3>Exercício de Teste:</h3>";
    echo "<strong>ID:</strong> " . $exercicio['id'] . "<br>";
    echo "<strong>Tipo:</strong> " . $exercicio['tipo'] . "<br>";
    echo "<strong>Categoria:</strong> " . $exercicio['categoria'] . "<br>";
    
    $conteudo = json_decode($exercicio['conteudo'], true);
    echo "<strong>Opções:</strong> " . implode(', ', $conteudo['opcoes']) . "<br>";
    echo "<strong>Resposta Correta:</strong> " . $conteudo['opcoes'][$conteudo['resposta_correta']] . "<br>";
    echo "<strong>Explicação:</strong> " . $conteudo['explicacao'] . "<br>";
}

echo "<br><a href='exercicios-teste-corrigido.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Testar Exercícios Corrigidos</a>";

$database->closeConnection();
?>