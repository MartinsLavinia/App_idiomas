<?php
// Inclua sua conexão com o banco de dados
include_once __DIR__ . "/../conexao.php"; // Ajuste o caminho se necessário

// Inicie a sessão (se você precisar de informações do usuário logado)
session_start();

// Verifique se o usuário está logado (se a API depende disso)
if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit();
}
$id_usuario = $_SESSION["id_usuario"];

// Prepare a resposta JSON
header('Content-Type: application/json');
$response = [];

// Obtenha os filtros da requisição GET (enviados pelo flashcard_script.js)
$idioma = $_GET['idioma'] ?? null;
$tipo = $_GET['tipo'] ?? 'meus'; // Padrão para 'meus decks'

try {
    // Crie uma instância da classe Database para obter a conexão
    $database = new Database();
    $conn = $database->conn;

    $sql = "SELECT id, nome, descricao, idioma, nivel, publico FROM decks WHERE id_usuario = ?";

    $params = [$id_usuario];

    if ($tipo === 'publicos') {
        // Se for buscar decks públicos, ajuste a query
        // (Você pode precisar de uma lógica mais complexa para mostrar decks públicos de outros usuários)
        // Neste exemplo simples, vamos mostrar todos os decks públicos se o tipo for 'publicos'
        $sql = "SELECT id, nome, descricao, idioma, nivel, publico FROM decks WHERE publico = 1";
        if ($idioma) {
            $sql .= " AND idioma = ?";
            $params = [$idioma];
        } else {
             // Se não houver idioma especificado, podemos incluir todos os públicos
            if ($tipo === 'publicos') {
                $sql = "SELECT id, nome, descricao, idioma, nivel, publico FROM decks WHERE publico = 1";
                if ($idioma) {
                    $sql .= " AND idioma = ?";
                    $params = [$idioma];
                } else {
                     // Sem idioma especificado, pega todos os públicos
                     $sql = "SELECT id, nome, descricao, idioma, nivel, publico FROM decks WHERE publico = 1";
                     $params = []; // Nenhum parâmetro adicional
                }
            }
        }

    } else { // Se for 'meus decks'
        if ($idioma) {
            $sql .= " AND idioma = ?";
            $params[] = $idioma;
        }
    }

    $sql .= " ORDER BY id DESC"; // Ou outra ordenação desejada

    $stmt = $conn->prepare($sql);

    // Se houver parâmetros, faça o bind
    if (!empty($params)) {
        $types = str_repeat('i', count($params)); // Assumindo que todos os IDs são inteiros
        // Se houver outros tipos de dados (como string para idioma), ajuste aqui.
        // Exemplo: se $idioma for string, seria 'si' se for id_usuario (i) e idioma (s).
        // Para este exemplo, vamos simplificar e assumir que os filtros são int ou não aplicados diretamente.
        // Uma lógica mais robusta seria necessária para outros tipos de filtro.

        // Adaptação para lidar com $idioma como string
        $bind_params = [];
        $bind_types = "";
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $bind_types .= "i";
            } elseif (is_string($value)) {
                $bind_types .= "s";
            }
            // Adicione mais tipos se necessário (d, etc.)
            $bind_params[] = &$params[$key]; // Passagem por referência
        }
        $stmt->bind_param($bind_types, ...$bind_params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $decks = [];

    while ($row = $result->fetch_assoc()) {
        $decks[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $decks;

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['success'] = false;
    $response['error'] = "Erro ao buscar decks: " . $e->getMessage();
} finally {
    // Feche a conexão do banco de dados
    if (isset($database)) {
        $database->closeConnection();
    }
}

echo json_encode($response);
?>