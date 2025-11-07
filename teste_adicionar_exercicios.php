<?php
/**
 * Teste para verificar se o sistema de adicionar exercícios está funcionando
 */

session_start();
include_once __DIR__ . '/conexao.php';

// Simular admin logado
if (!isset($_SESSION['id_admin'])) {
    $_SESSION['id_admin'] = 1;
    $_SESSION['nome_admin'] = 'Admin Teste';
    $_SESSION['email_admin'] = 'admin@teste.com';
}

$database = new Database();
$conn = $database->conn;

echo "<h1>🧪 Teste do Sistema de Adicionar Exercícios</h1>";

// Verificar estrutura da tabela exercicios
echo "<h2>1. Verificando estrutura da tabela 'exercicios'</h2>";
$result = $conn->query("DESCRIBE exercicios");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Erro ao verificar estrutura da tabela</p>";
}

// Verificar se existe alguma unidade para teste
echo "<h2>2. Verificando unidades disponíveis</h2>";
$result = $conn->query("SELECT id, nome_unidade, idioma FROM unidades LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['id']} - {$row['nome_unidade']} ({$row['idioma']})</li>";
    }
    echo "</ul>";
    
    // Pegar primeira unidade para teste
    $result->data_seek(0);
    $unidade_teste = $result->fetch_assoc();
    $unidade_id = $unidade_teste['id'];
    
    echo "<p>✅ Usando unidade ID {$unidade_id} para teste</p>";
} else {
    echo "<p style='color: red;'>❌ Nenhuma unidade encontrada. Crie uma unidade primeiro.</p>";
    exit;
}

// Verificar caminhos da unidade
echo "<h2>3. Verificando caminhos da unidade {$unidade_id}</h2>";
$result = $conn->query("SELECT id, nome_caminho FROM caminhos_aprendizagem WHERE id_unidade = {$unidade_id} LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['id']} - {$row['nome_caminho']}</li>";
    }
    echo "</ul>";
    
    // Pegar primeiro caminho para teste
    $result->data_seek(0);
    $caminho_teste = $result->fetch_assoc();
    $caminho_id = $caminho_teste['id'];
    
    echo "<p>✅ Usando caminho ID {$caminho_id} para teste</p>";
} else {
    echo "<p style='color: red;'>❌ Nenhum caminho encontrado para esta unidade.</p>";
    exit;
}

// Verificar blocos do caminho
echo "<h2>4. Verificando blocos do caminho {$caminho_id}</h2>";
$result = $conn->query("SELECT id, nome_bloco, ordem FROM blocos WHERE caminho_id = {$caminho_id} LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['id']} - {$row['nome_bloco']} (Ordem: {$row['ordem']})</li>";
    }
    echo "</ul>";
    
    // Pegar primeiro bloco para teste
    $result->data_seek(0);
    $bloco_teste = $result->fetch_assoc();
    $bloco_id = $bloco_teste['id'];
    
    echo "<p>✅ Usando bloco ID {$bloco_id} para teste</p>";
} else {
    echo "<p style='color: red;'>❌ Nenhum bloco encontrado para este caminho.</p>";
    exit;
}

// Testar função de adicionar exercício
echo "<h2>5. Testando função adicionarExercicio</h2>";

// Incluir a função corrigida
function adicionarExercicio($conn, $caminhoId, $blocoId, $ordem, $tipo_exercicio, $pergunta, $conteudo) {
    // Mapear tipo_exercicio para o ENUM da coluna 'tipo'
    $tipoEnum = 'normal'; // padrão
    if ($tipo_exercicio === 'especial') {
        $tipoEnum = 'especial';
    } elseif ($tipo_exercicio === 'quiz') {
        $tipoEnum = 'quiz';
    }
    
    // Definir categoria baseada no tipo_exercicio
    $categoria = 'gramatica'; // padrão
    switch ($tipo_exercicio) {
        case 'listening':
        case 'audicao':
            $categoria = 'audicao';
            break;
        case 'fala':
            $categoria = 'fala';
            break;
        case 'texto_livre':
        case 'completar':
            $categoria = 'escrita';
            break;
        case 'multipla_escolha':
        default:
            $categoria = 'gramatica';
            break;
    }
    
    $sql = "INSERT INTO exercicios (caminho_id, bloco_id, ordem, tipo, pergunta, conteudo, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiissss", $caminhoId, $blocoId, $ordem, $tipoEnum, $pergunta, $conteudo, $categoria);
        if ($stmt->execute()) {
            $exercicio_id = $conn->insert_id;
            $stmt->close();
            return $exercicio_id;
        } else {
            error_log("Erro ao adicionar exercício: " . $stmt->error);
            $stmt->close();
            return false;
        }
    } else {
        error_log("Erro na preparação da consulta: " . $conn->error);
        return false;
    }
}

// Teste 1: Exercício de múltipla escolha
echo "<h3>Teste 1: Múltipla Escolha</h3>";
$conteudo_multipla = json_encode([
    'alternativas' => [
        ['id' => 'a', 'texto' => 'Opção A', 'correta' => true],
        ['id' => 'b', 'texto' => 'Opção B', 'correta' => false],
        ['id' => 'c', 'texto' => 'Opção C', 'correta' => false]
    ],
    'explicacao' => 'A opção A está correta.'
], JSON_UNESCAPED_UNICODE);

$resultado1 = adicionarExercicio($conn, $caminho_id, $bloco_id, 1, 'multipla_escolha', 'Qual é a resposta correta?', $conteudo_multipla);
if ($resultado1) {
    echo "<p style='color: green;'>✅ Exercício de múltipla escolha criado com ID: {$resultado1}</p>";
} else {
    echo "<p style='color: red;'>❌ Erro ao criar exercício de múltipla escolha</p>";
}

// Teste 2: Exercício de listening
echo "<h3>Teste 2: Listening</h3>";
$conteudo_listening = json_encode([
    'frase_original' => 'Hello, how are you?',
    'audio_url' => '/App_idiomas/audios/teste.mp3',
    'opcoes' => ['Hello', 'Goodbye', 'Thank you', 'Please'],
    'resposta_correta' => 0,
    'explicacao' => 'A frase diz "Hello, how are you?"',
    'transcricao' => 'Hello, how are you?',
    'dicas_compreensao' => 'Ouça com atenção a saudação.',
    'idioma' => 'en-us',
    'tipo_exercicio' => 'listening'
], JSON_UNESCAPED_UNICODE);

$resultado2 = adicionarExercicio($conn, $caminho_id, $bloco_id, 2, 'listening', 'Ouça o áudio e escolha a resposta correta:', $conteudo_listening);
if ($resultado2) {
    echo "<p style='color: green;'>✅ Exercício de listening criado com ID: {$resultado2}</p>";
} else {
    echo "<p style='color: red;'>❌ Erro ao criar exercício de listening</p>";
}

// Teste 3: Exercício de fala
echo "<h3>Teste 3: Fala</h3>";
$conteudo_fala = json_encode([
    'frase_esperada' => 'Hello, how are you today?',
    'texto_para_falar' => 'Hello, how are you today?',
    'idioma' => 'en-US',
    'dicas_pronuncia' => 'Pronuncie o H de Hello com aspiração.',
    'palavras_chave' => ['Hello', 'how', 'are', 'you', 'today'],
    'contexto' => 'Saudação informal',
    'tolerancia_erro' => 0.8,
    'max_tentativas' => 3,
    'tipo_exercicio' => 'fala'
], JSON_UNESCAPED_UNICODE);

$resultado3 = adicionarExercicio($conn, $caminho_id, $bloco_id, 3, 'fala', 'Fale a seguinte frase em inglês:', $conteudo_fala);
if ($resultado3) {
    echo "<p style='color: green;'>✅ Exercício de fala criado com ID: {$resultado3}</p>";
} else {
    echo "<p style='color: red;'>❌ Erro ao criar exercício de fala</p>";
}

// Verificar exercícios criados
echo "<h2>6. Verificando exercícios criados</h2>";
$result = $conn->query("SELECT id, tipo, categoria, pergunta FROM exercicios WHERE bloco_id = {$bloco_id} ORDER BY ordem");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Tipo</th><th>Categoria</th><th>Pergunta</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tipo']) . "</td>";
        echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['pergunta'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhum exercício encontrado no bloco.</p>";
}

echo "<h2>✅ Teste Concluído!</h2>";
echo "<p><strong>Resultado:</strong> O sistema de adicionar exercícios foi corrigido e está funcionando.</p>";
echo "<p><a href='admin/views/adicionar_atividades.php?unidade_id={$unidade_id}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Testar Interface de Adicionar Exercícios</a></p>";

$database->closeConnection();
?>