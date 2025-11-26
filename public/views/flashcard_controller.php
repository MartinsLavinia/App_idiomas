<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// DEBUG: Verificar se está acessando o arquivo
error_log("=== FLASHCARD_CONTROLLER ACESSADO ===");
error_log("Action: " . ($_POST['action'] ?? $_GET['action'] ?? 'Nenhuma'));
error_log("Usuário logado: " . ($_SESSION["id_usuario"] ?? 'Não logado'));

if (!isset($_SESSION["id_usuario"])) {
    error_log("USUÁRIO NÃO AUTENTICADO - Redirecionando");
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// DEBUG: Log da ação
error_log("Processando ação: " . $action . " para usuário: " . $id_usuario);

$database = new Database();
$conn = $database->conn;

// DEBUG: Verificar conexão
if (!$conn) {
    error_log("ERRO: Conexão com banco falhou");
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco']);
    exit();
}

error_log("Conexão com banco estabelecida com sucesso");

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
            
        case 'excluir_deck':
            excluirDeck($conn, $id_usuario);
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
            
        // Ações para Minhas Palavras (compatibilidade com painel.php)
        case 'adicionar_flashcard':
            adicionarFlashcardPainel($conn, $id_usuario);
            break;
            
        case 'listar_flashcards_painel':
            listarFlashcardsPainel($conn, $id_usuario);
            break;
            
        case 'marcar_como_aprendido':
            marcarComoAprendido($conn, $id_usuario);
            break;
            
        case 'desmarcar_como_aprendido':
            desmarcarComoAprendido($conn, $id_usuario);
            break;
            
        case 'excluir_flashcard_painel':
            excluirFlashcardPainel($conn, $id_usuario);
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

    $sql = "INSERT INTO flashcard_decks (id_usuario, nome, descricao, idioma, nivel, publico, data_criacao) 
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

    $sql = "UPDATE flashcard_decks SET nome = ?, descricao = ?, idioma = ?, nivel = ?, publico = ? 
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

function excluirDeck($conn, $id_usuario) {
    $id_deck = intval($_POST['id_deck'] ?? 0);

    // Verifica se o deck pertence ao usuário
    if (!verificarDonoDeck($conn, $id_deck, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao deck']);
        return;
    }

    // Iniciar transação para garantir consistência
    $conn->begin_transaction();

    try {
        // Primeiro excluir os flashcards do deck
        $sql_flashcards = "DELETE FROM flashcards WHERE id_deck = ?";
        $stmt_flashcards = $conn->prepare($sql_flashcards);
        $stmt_flashcards->bind_param("i", $id_deck);
        $stmt_flashcards->execute();
        $stmt_flashcards->close();

        // Depois excluir o deck
        $sql_deck = "DELETE FROM flashcard_decks WHERE id = ? AND id_usuario = ?";
        $stmt_deck = $conn->prepare($sql_deck);
        $stmt_deck->bind_param("ii", $id_deck, $id_usuario);
        
        if ($stmt_deck->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Deck excluído com sucesso']);
        } else {
            throw new Exception('Erro ao excluir deck: ' . $stmt_deck->error);
        }
        
        $stmt_deck->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir deck: ' . $e->getMessage()]);
    }
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
            FROM flashcard_decks d 
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
            FROM flashcard_decks d 
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
            FROM flashcard_decks d 
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
                JOIN flashcard_decks d ON f.id_deck = d.id 
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

// ========== FUNÇÕES PARA MINHAS PALAVRAS (PAINEL) ==========

function adicionarFlashcardPainel($conn, $id_usuario) {
    $palavra_frente = trim($_POST['palavra_frente'] ?? '');
    $palavra_verso = trim($_POST['palavra_verso'] ?? '');
    $idioma = $_POST['idioma'] ?? 'Ingles';
    $nivel = $_POST['nivel'] ?? 'A1';
    $categoria = trim($_POST['categoria'] ?? '');

    // Validação
    if (empty($palavra_frente) || empty($palavra_verso)) {
        echo json_encode(['success' => false, 'message' => 'Palavra e tradução são obrigatórias']);
        return;
    }

    // 1. Primeiro, verifica ou cria um deck pessoal para o usuário
    $deck_nome = "Meu Deck " . $idioma . " " . $nivel;
    $sql_deck = "SELECT id FROM flashcard_decks WHERE id_usuario = ? AND idioma = ? AND nivel = ? AND nome LIKE 'Meu Deck%' LIMIT 1";
    $stmt_deck = $conn->prepare($sql_deck);
    $stmt_deck->bind_param("iss", $id_usuario, $idioma, $nivel);
    $stmt_deck->execute();
    $result_deck = $stmt_deck->get_result();
    
    if ($result_deck->num_rows > 0) {
        $deck = $result_deck->fetch_assoc();
        $id_deck = $deck['id'];
    } else {
        // Cria novo deck pessoal
        $sql_novo_deck = "INSERT INTO flashcard_decks (id_usuario, nome, descricao, idioma, nivel, publico) 
                         VALUES (?, ?, ?, ?, ?, 0)";
        $descricao = "Deck pessoal para palavras em " . $idioma . " nível " . $nivel;
        $stmt_novo_deck = $conn->prepare($sql_novo_deck);
        $stmt_novo_deck->bind_param("issss", $id_usuario, $deck_nome, $descricao, $idioma, $nivel);
        
        if ($stmt_novo_deck->execute()) {
            $id_deck = $conn->insert_id;
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar deck pessoal']);
            $stmt_novo_deck->close();
            $stmt_deck->close();
            return;
        }
        $stmt_novo_deck->close();
    }
    $stmt_deck->close();

    // 2. Agora insere o flashcard no deck
    $sql_flashcard = "INSERT INTO flashcards (id_deck, frente, verso, dica, dificuldade, ordem_no_deck) 
                     VALUES (?, ?, ?, ?, 'medio', 0)";
    $stmt_flashcard = $conn->prepare($sql_flashcard);
    $dica = $categoria ? "Categoria: " . $categoria : null;
    $stmt_flashcard->bind_param("isss", $id_deck, $palavra_frente, $palavra_verso, $dica);
    
    if ($stmt_flashcard->execute()) {
        echo json_encode(['success' => true, 'message' => 'Palavra adicionada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar flashcard: ' . $stmt_flashcard->error]);
    }
    
    $stmt_flashcard->close();
}

function listarFlashcardsPainel($conn, $id_usuario) {
    $status = $_POST['status'] ?? '';
    $idioma = $_POST['idioma'] ?? '';

    $sql = "SELECT 
            f.id,
            f.frente as palavra_frente,
            f.verso as palavra_verso,
            d.idioma,
            d.nivel,
            f.dica as categoria,
            COALESCE(fp.acertos, 0) as acertos,
            COALESCE(fp.erros, 0) as erros,
            CASE 
                WHEN COALESCE(fp.acertos, 0) >= 3 THEN 1 
                ELSE 0 
            END as aprendido
        FROM flashcards f
        INNER JOIN flashcard_decks d ON f.id_deck = d.id
        LEFT JOIN flashcard_progresso fp ON f.id = fp.id_flashcard AND fp.id_usuario = ?
        WHERE d.id_usuario = ?";
    
    $params = [$id_usuario, $id_usuario];
    $types = "ii";

    if (!empty($idioma)) {
        $sql .= " AND d.idioma = ?";
        $params[] = $idioma;
        $types .= "s";
    }

    if ($status !== '') {
        $sql .= " AND (CASE WHEN COALESCE(fp.acertos, 0) >= 3 THEN 1 ELSE 0 END) = ?";
        $params[] = $status;
        $types .= "i";
    }

    $sql .= " ORDER BY d.idioma, d.nivel, f.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $flashcards = [];
    while ($row = $result->fetch_assoc()) {
        $flashcards[] = $row;
    }

    echo json_encode(['success' => true, 'flashcards' => $flashcards]);
    $stmt->close();
}

function marcarComoAprendido($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard'] ?? 0);

    // Incrementa acertos no progresso
    $sql = "INSERT INTO flashcard_progresso (id_usuario, id_flashcard, acertos, erros, ultima_revisao, proxima_revisao, intervalo_dias, facilidade)
            VALUES (?, ?, 1, 0, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 2.5)
            ON DUPLICATE KEY UPDATE 
            acertos = acertos + 1,
            ultima_revisao = NOW(),
            proxima_revisao = DATE_ADD(NOW(), INTERVAL intervalo_dias DAY)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_flashcard);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Palavra marcada como aprendida!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function desmarcarComoAprendido($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard'] ?? 0);

    // Incrementa erros no progresso
    $sql = "INSERT INTO flashcard_progresso (id_usuario, id_flashcard, acertos, erros, ultima_revisao, proxima_revisao, intervalo_dias, facilidade)
            VALUES (?, ?, 0, 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 2.0)
            ON DUPLICATE KEY UPDATE 
            erros = erros + 1,
            ultima_revisao = NOW(),
            proxima_revisao = DATE_ADD(NOW(), INTERVAL 1 DAY),
            intervalo_dias = 1,
            facilidade = 2.0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_flashcard);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Palavra marcada para estudar novamente!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function excluirFlashcardPainel($conn, $id_usuario) {
    $id_flashcard = intval($_POST['id_flashcard'] ?? 0);

    // Verifica se o usuário é dono do flashcard
    $sql_verifica = "SELECT f.id 
                    FROM flashcards f 
                    INNER JOIN flashcard_decks d ON f.id_deck = d.id 
                    WHERE f.id = ? AND d.id_usuario = ?";
    $stmt_verifica = $conn->prepare($sql_verifica);
    $stmt_verifica->bind_param("ii", $id_flashcard, $id_usuario);
    $stmt_verifica->execute();
    $result_verifica = $stmt_verifica->get_result();
    
    if ($result_verifica->num_rows > 0) {
        // Usuário é dono, pode excluir
        $sql_excluir = "DELETE FROM flashcards WHERE id = ?";
        $stmt_excluir = $conn->prepare($sql_excluir);
        $stmt_excluir->bind_param("i", $id_flashcard);
        
        if ($stmt_excluir->execute()) {
            echo json_encode(['success' => true, 'message' => 'Palavra excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir palavra']);
        }
        $stmt_excluir->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir este flashcard']);
    }
    $stmt_verifica->close();
}

// ========== FUNÇÕES AUXILIARES ==========

function verificarDonoDeck($conn, $id_deck, $id_usuario) {
    $sql = "SELECT id FROM flashcard_decks WHERE id = ? AND id_usuario = ?";
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
            JOIN flashcard_decks d ON f.id_deck = d.id 
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
    $sql = "SELECT id FROM flashcard_decks WHERE id = ? AND (id_usuario = ? OR publico = 1)";
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
            JOIN flashcard_decks d ON f.id_deck = d.id 
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