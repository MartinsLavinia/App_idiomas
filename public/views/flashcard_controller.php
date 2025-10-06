<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$database = new Database();
$conn = $database->conn;

header('Content-Type: application/json');

try {
    switch ($action) {
        // Ações para Decks
        case 'criar_deck':
            criarDeck($conn, $id_usuario);
            break;
            
        case 'atualizar_deck':
            atualizarDeck($conn, $id_usuario);
            break;
            
        case 'listar_decks':
            listarDecks($conn, $id_usuario);
            break;
            
        case 'listar_decks_publicos':
            listarDecksPublicos($conn, $id_usuario);
            break;
            
        case 'obter_deck':
            obterDeck($conn, $id_usuario);
            break;
            
        // Ações para Flashcards
        case 'criar_flashcard':
            criarFlashcard($conn, $id_usuario);
            break;
            
        case 'atualizar_flashcard':
            atualizarFlashcard($conn, $id_usuario);
            break;
            
        case 'excluir_flashcard':
            excluirFlashcard($conn, $id_usuario);
            break;
            
        case 'listar_flashcards':
            listarFlashcards($conn, $id_usuario);
            break;
            
        case 'obter_flashcard':
            obterFlashcard($conn, $id_usuario);
            break;
            
        case 'obter_flashcards_para_revisar':
            obterFlashcardsParaRevisar($conn, $id_usuario);
            break;
            
        case 'registrar_resposta':
            registrarResposta($conn, $id_usuario);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

$database->closeConnection();

// ========== FUNÇÕES PARA DECKS ==========

function criarDeck($conn, $id_usuario) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $idioma = $_POST['idioma'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $publico = isset($_POST['publico']) ? 1 : 0;

    // Validação
    if (empty($nome) || empty($idioma) || empty($nivel)) {
        echo json_encode(['success' => false, 'message' => 'Nome, idioma e nível são obrigatórios']);
        return;
    }

    $sql = "INSERT INTO decks (id_usuario, nome, descricao, idioma, nivel, publico, data_criacao) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $id_usuario, $nome, $descricao, $idioma, $nivel, $publico);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Deck criado com sucesso', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar deck: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function atualizarDeck($conn, $id_usuario) {
    $id_deck = intval($_POST['id_deck'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $idioma = $_POST['idioma'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $publico = isset($_POST['publico']) ? 1 : 0;

    // Verifica se o deck pertence ao usuário
    if (!verificarDonoDeck($conn, $id_deck, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
        return;
    }

    // Validação
    if (empty($nome) || empty($idioma) || empty($nivel)) {
        echo json_encode(['success' => false, 'message' => 'Nome, idioma e nível são obrigatórios']);
        return;
    }

    $sql = "UPDATE decks SET nome = ?, descricao = ?, idioma = ?, nivel = ?, publico = ? 
            WHERE id = ? AND id_usuario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", $nome, $descricao, $idioma, $nivel, $publico, $id_deck, $id_usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Deck atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar deck: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function listarDecks($conn, $id_usuario) {
    $filtroIdioma = $_GET['idioma'] ?? '';
    $filtroNivel = $_GET['nivel'] ?? '';

    $sql = "SELECT d.*, 
                   (SELECT COUNT(*) FROM flashcards f WHERE f.id_deck = d.id) as total_flashcards,
                   (SELECT COUNT(DISTINCT fr.id_flashcard) 
                    FROM flashcard_respostas fr 
                    JOIN flashcards f ON fr.id_flashcard = f.id 
                    WHERE f.id_deck = d.id AND fr.id_usuario = ?) as flashcards_estudados
            FROM decks d 
            WHERE d.id_usuario = ?";
    
    $params = [$id_usuario, $id_usuario];
    $types = "ii";

    if (!empty($filtroIdioma)) {
        $sql .= " AND d.idioma = ?";
        $params[] = $filtroIdioma;
        $types .= "s";
    }

    if (!empty($filtroNivel)) {
        $sql .= " AND d.nivel = ?";
        $params[] = $filtroNivel;
        $types .= "s";
    }

    $sql .= " ORDER BY d.data_criacao DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $decks = [];
    while ($row = $result->fetch_assoc()) {
        $decks[] = $row;
    }

    echo json_encode(['success' => true, 'decks' => $decks]);
    $stmt->close();
}

function listarDecksPublicos($conn, $id_usuario) {
    $filtroIdioma = $_GET['idioma'] ?? '';
    $filtroNivel = $_GET['nivel'] ?? '';

    $sql = "SELECT d.*, u.nome as nome_criador,
                   (SELECT COUNT(*) FROM flashcards f WHERE f.id_deck = d.id) as total_flashcards,
                   (SELECT COUNT(DISTINCT fr.id_flashcard) 
                    FROM flashcard_respostas fr 
                    JOIN flashcards f ON fr.id_flashcard = f.id 
                    WHERE f.id_deck = d.id AND fr.id_usuario = ?) as flashcards_estudados
            FROM decks d 
            LEFT JOIN usuarios u ON d.id_usuario = u.id 
            WHERE d.publico = 1 AND d.id_usuario != ?";
    
    $params = [$id_usuario, $id_usuario];
    $types = "ii";

    if (!empty($filtroIdioma)) {
        $sql .= " AND d.idioma = ?";
        $params[] = $filtroIdioma;
        $types .= "s";
    }

    if (!empty($filtroNivel)) {
        $sql .= " AND d.nivel = ?";
        $params[] = $filtroNivel;
        $types .= "s";
    }

    $sql .= " ORDER BY d.data_criacao DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $decks = [];
    while ($row = $result->fetch_assoc()) {
        $decks[] = $row;
    }

    echo json_encode(['success' => true, 'decks' => $decks]);
    $stmt->close();
}

// ========== FUNÇÕES PARA FLASHCARDS ==========

function criarFlashcard($conn, $id_usuario) {
    $id_deck = intval($_POST['id_deck']);
    $frente = trim($_POST['frente']);
    $verso = trim($_POST['verso']);
    $dica = trim($_POST['dica'] ?? '');
    $dificuldade = $_POST['dificuldade'] ?? 'medio';
    $ordem_no_deck = intval($_POST['ordem_no_deck'] ?? 0);

    // Verifica se o deck pertence ao usuário
    if (!verificarDonoDeck($conn, $id_deck, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
        return;
    }

    // Validação
    if (empty($frente) || empty($verso)) {
        echo json_encode(['success' => false, 'message' => 'Frente e verso são obrigatórios']);
        return;
    }

    $sql = "INSERT INTO flashcards (id_deck, frente, verso, dica, dificuldade, ordem_no_deck, data_criacao) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $id_deck, $frente, $verso, $dica, $dificuldade, $ordem_no_deck);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Flashcard criado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar flashcard: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function atualizarFlashcard($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard']);
    $frente = trim($_POST['frente']);
    $verso = trim($_POST['verso']);
    $dica = trim($_POST['dica'] ?? '');
    $dificuldade = $_POST['dificuldade'] ?? 'medio';
    $ordem_no_deck = intval($_POST['ordem_no_deck'] ?? 0);

    // Verifica se o flashcard pertence ao usuário
    if (!verificarDonoFlashcard($conn, $id_flashcard, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao flashcard']);
        return;
    }

    // Validação
    if (empty($frente) || empty($verso)) {
        echo json_encode(['success' => false, 'message' => 'Frente e verso são obrigatórios']);
        return;
    }

    $sql = "UPDATE flashcards SET frente = ?, verso = ?, dica = ?, dificuldade = ?, ordem_no_deck = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $frente, $verso, $dica, $dificuldade, $ordem_no_deck, $id_flashcard);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Flashcard atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar flashcard: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function excluirFlashcard($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard']);

    // Verifica se o flashcard pertence ao usuário
    if (!verificarDonoFlashcard($conn, $id_flashcard, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao flashcard']);
        return;
    }

    $sql = "DELETE FROM flashcards WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_flashcard);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Flashcard excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir flashcard: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function listarFlashcards($conn, $id_usuario) {
    $id_deck = intval($_GET['id_deck']);

    // Verifica se o deck pertence ao usuário ou é público
    if (!verificarAcessoDeck($conn, $id_deck, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
        return;
    }

    $sql = "SELECT f.*, 
                   (SELECT COUNT(*) FROM flashcard_respostas fr WHERE fr.id_flashcard = f.id AND fr.acertou = 1) as acertos,
                   (SELECT COUNT(*) FROM flashcard_respostas fr WHERE fr.id_flashcard = f.id AND fr.acertou = 0) as erros
            FROM flashcards f 
            WHERE f.id_deck = ? 
            ORDER BY f.ordem_no_deck ASC, f.id ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_deck);
    $stmt->execute();
    $result = $stmt->get_result();

    $flashcards = [];
    while ($row = $result->fetch_assoc()) {
        $flashcards[] = $row;
    }

    echo json_encode(['success' => true, 'flashcards' => $flashcards]);
    $stmt->close();
}

function obterFlashcard($conn, $id_usuario) {
    $id_flashcard = intval($_GET['id_flashcard']);

    // Verifica se o flashcard pertence ao usuário
    if (!verificarDonoFlashcard($conn, $id_flashcard, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao flashcard']);
        return;
    }

    $sql = "SELECT * FROM flashcards WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_flashcard);
    $stmt->execute();
    $result = $stmt->get_result();
    $flashcard = $result->fetch_assoc();

    if ($flashcard) {
        echo json_encode(['success' => true, 'flashcard' => $flashcard]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Flashcard não encontrado']);
    }
    
    $stmt->close();
}

function obterDeck($conn, $id_usuario) {
    $id_deck = intval($_GET['id_deck']);

    // Verifica se o deck pertence ao usuário ou é público
    if (!verificarAcessoDeck($conn, $id_deck, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
        return;
    }

    $sql = "SELECT d.*, u.nome as nome_criador,
                   (SELECT COUNT(*) FROM flashcards f WHERE f.id_deck = d.id) as total_flashcards,
                   (SELECT COUNT(DISTINCT fr.id_flashcard) 
                    FROM flashcard_respostas fr 
                    JOIN flashcards f ON fr.id_flashcard = f.id 
                    WHERE f.id_deck = d.id AND fr.id_usuario = ?) as flashcards_estudados
            FROM decks d 
            LEFT JOIN usuarios u ON d.id_usuario = u.id 
            WHERE d.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_deck);
    $stmt->execute();
    $result = $stmt->get_result();
    $deck = $result->fetch_assoc();

    if ($deck) {
        echo json_encode(['success' => true, 'deck' => $deck]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Deck não encontrado']);
    }
    
    $stmt->close();
}

function obterFlashcardsParaRevisar($conn, $id_usuario) {
    $id_deck = isset($_GET['id_deck']) ? intval($_GET['id_deck']) : null;
    $limite = intval($_GET['limite'] ?? 50);

    if ($id_deck) {
        // Verifica acesso ao deck específico
        if (!verificarAcessoDeck($conn, $id_deck, $id_usuario)) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
            return;
        }
        
        $sql = "SELECT f.* FROM flashcards f WHERE f.id_deck = ? 
                ORDER BY RAND() LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_deck, $limite);
    } else {
        // Todos os decks do usuário
        $sql = "SELECT f.* FROM flashcards f 
                JOIN decks d ON f.id_deck = d.id 
                WHERE d.id_usuario = ? OR d.publico = 1 
                ORDER BY RAND() LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_usuario, $limite);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $flashcards = [];
    while ($row = $result->fetch_assoc()) {
        $flashcards[] = $row;
    }

    echo json_encode(['success' => true, 'flashcards' => $flashcards]);
    $stmt->close();
}

function registrarResposta($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard']);
    $acertou = intval($_POST['acertou']);
    $facilidade_resposta = intval($_POST['facilidade_resposta']);

    // Verifica se o flashcard existe e usuário tem acesso
    if (!verificarAcessoFlashcard($conn, $id_flashcard, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao flashcard']);
        return;
    }

    $sql = "INSERT INTO flashcard_respostas (id_flashcard, id_usuario, acertou, facilidade_resposta, data_resposta) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            acertou = VALUES(acertou), 
            facilidade_resposta = VALUES(facilidade_resposta), 
            data_resposta = VALUES(data_resposta)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $id_flashcard, $id_usuario, $acertou, $facilidade_resposta);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resposta registrada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar resposta: ' . $stmt->error]);
    }
    
    $stmt->close();
}

// ========== FUNÇÕES AUXILIARES ==========

function verificarDonoDeck($conn, $id_deck, $id_usuario) {
    $sql = "SELECT id FROM decks WHERE id = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_deck, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function verificarDonoFlashcard($conn, $id_flashcard, $id_usuario) {
    $sql = "SELECT f.id FROM flashcards f 
            JOIN decks d ON f.id_deck = d.id 
            WHERE f.id = ? AND d.id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_flashcard, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function verificarAcessoDeck($conn, $id_deck, $id_usuario) {
    $sql = "SELECT id FROM decks WHERE id = ? AND (id_usuario = ? OR publico = 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_deck, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function verificarAcessoFlashcard($conn, $id_flashcard, $id_usuario) {
    $sql = "SELECT f.id FROM flashcards f 
            JOIN decks d ON f.id_deck = d.id 
            WHERE f.id = ? AND (d.id_usuario = ? OR d.publico = 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_flashcard, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>