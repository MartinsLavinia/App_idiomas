<?php
//Controlador para funcionalidades de Flash Cards//
  //Gerencia todas as operações relacionadas aos flashcards//
 

session_start();

// Inclui as dependências necessárias
include_once __DIR__ . '/../../conexao.php';
include_once __DIR__ . '/FlashcardModel.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Cria conexão com o banco de dados
$database = new Database();
$conn = $database->conn;
$flashcardModel = new FlashcardModel($conn);

$id_usuario = $_SESSION['id_usuario'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Roteamento das ações
switch ($action) {
    
    // ==================== OPERAÇÕES COM DECKS ====================
    
    case 'listar_decks':
        $idioma = $_GET['idioma'] ?? null;
        $nivel = $_GET['nivel'] ?? null;
        
        $decks = $flashcardModel->listarDecks($id_usuario, $idioma, $nivel);
        
        echo json_encode([
            'success' => true,
            'decks' => $decks
        ]);
        break;
    
    case 'listar_decks_publicos':
        $idioma = $_GET['idioma'] ?? null;
        $nivel = $_GET['nivel'] ?? null;
        $limite = intval($_GET['limite'] ?? 20);
        
        $decks = $flashcardModel->listarDecksPublicos($idioma, $nivel, $limite);
        
        echo json_encode([
            'success' => true,
            'decks' => $decks
        ]);
        break;
    
    case 'obter_deck':
        $id_deck = intval($_GET['id_deck'] ?? 0);
        
        if (!$id_deck) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do deck é obrigatório']);
            break;
        }
        
        $deck = $flashcardModel->obterDeck($id_deck, $id_usuario);
        
        if (!$deck) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Deck não encontrado']);
            break;
        }
        
        echo json_encode([
            'success' => true,
            'deck' => $deck
        ]);
        break;
    
    case 'criar_deck':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $dados = [
            'id_usuario' => $id_usuario,
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'idioma' => $_POST['idioma'] ?? '',
            'nivel' => $_POST['nivel'] ?? '',
            'publico' => isset($_POST['publico']) && $_POST['publico'] === '1'
        ];
        
        // Validações
        if (empty($dados['nome'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome do deck é obrigatório']);
            break;
        }
        
        if (empty($dados['idioma'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Idioma é obrigatório']);
            break;
        }
        
        if (empty($dados['nivel'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nível é obrigatório']);
            break;
        }
        
        $id_deck = $flashcardModel->criarDeck($dados);
        
        if ($id_deck) {
            echo json_encode([
                'success' => true,
                'message' => 'Deck criado com sucesso',
                'id_deck' => $id_deck
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar deck']);
        }
        break;
    
    case 'atualizar_deck':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_deck = intval($_POST['id_deck'] ?? 0);
        
        if (!$id_deck) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do deck é obrigatório']);
            break;
        }
        
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'idioma' => $_POST['idioma'] ?? '',
            'nivel' => $_POST['nivel'] ?? '',
            'publico' => isset($_POST['publico']) && $_POST['publico'] === '1'
        ];
        
        // Validações
        if (empty($dados['nome'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome do deck é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->atualizarDeck($id_deck, $dados, $id_usuario);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Deck atualizado com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar deck']);
        }
        break;
    
    case 'excluir_deck':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_deck = intval($_POST['id_deck'] ?? 0);
        
        if (!$id_deck) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do deck é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->excluirDeck($id_deck, $id_usuario);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Deck excluído com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir deck']);
        }
        break;
    
    // ==================== OPERAÇÕES COM FLASHCARDS ====================
    
    case 'listar_flashcards':
        $id_deck = intval($_GET['id_deck'] ?? 0);
        
        if (!$id_deck) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do deck é obrigatório']);
            break;
        }
        
        $flashcards = $flashcardModel->listarFlashcards($id_deck, $id_usuario);
        
        echo json_encode([
            'success' => true,
            'flashcards' => $flashcards
        ]);
        break;
    
    case 'obter_flashcard':
        $id_flashcard = intval($_GET['id_flashcard'] ?? 0);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        $flashcard = $flashcardModel->obterFlashcard($id_flashcard, $id_usuario);
        
        if (!$flashcard) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Flashcard não encontrado']);
            break;
        }
        
        echo json_encode([
            'success' => true,
            'flashcard' => $flashcard
        ]);
        break;
    
    case 'criar_flashcard':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $dados = [
            'id_deck' => intval($_POST['id_deck'] ?? 0),
            'frente' => trim($_POST['frente'] ?? ''),
            'verso' => trim($_POST['verso'] ?? ''),
            'dica' => trim($_POST['dica'] ?? '') ?: null,
            'imagem_frente' => trim($_POST['imagem_frente'] ?? '') ?: null,
            'imagem_verso' => trim($_POST['imagem_verso'] ?? '') ?: null,
            'audio_frente' => trim($_POST['audio_frente'] ?? '') ?: null,
            'audio_verso' => trim($_POST['audio_verso'] ?? '') ?: null,
            'dificuldade' => $_POST['dificuldade'] ?? 'medio',
            'ordem_no_deck' => intval($_POST['ordem_no_deck'] ?? 0)
        ];
        
        // Validações
        if (!$dados['id_deck']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do deck é obrigatório']);
            break;
        }
        
        if (empty($dados['frente'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Frente do flashcard é obrigatória']);
            break;
        }
        
        if (empty($dados['verso'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Verso do flashcard é obrigatório']);
            break;
        }
        
        $id_flashcard = $flashcardModel->criarFlashcard($dados);
        
        if ($id_flashcard) {
            echo json_encode([
                'success' => true,
                'message' => 'Flashcard criado com sucesso',
                'id_flashcard' => $id_flashcard
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar flashcard']);
        }
        break;
    
    case 'atualizar_flashcard':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_flashcard = intval($_POST['id_flashcard'] ?? 0);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        $dados = [
            'frente' => trim($_POST['frente'] ?? ''),
            'verso' => trim($_POST['verso'] ?? ''),
            'dica' => trim($_POST['dica'] ?? '') ?: null,
            'imagem_frente' => trim($_POST['imagem_frente'] ?? '') ?: null,
            'imagem_verso' => trim($_POST['imagem_verso'] ?? '') ?: null,
            'audio_frente' => trim($_POST['audio_frente'] ?? '') ?: null,
            'audio_verso' => trim($_POST['audio_verso'] ?? '') ?: null,
            'dificuldade' => $_POST['dificuldade'] ?? 'medio',
            'ordem_no_deck' => intval($_POST['ordem_no_deck'] ?? 0)
        ];
        
        // Validações
        if (empty($dados['frente'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Frente do flashcard é obrigatória']);
            break;
        }
        
        if (empty($dados['verso'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Verso do flashcard é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->atualizarFlashcard($id_flashcard, $dados);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Flashcard atualizado com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar flashcard']);
        }
        break;
    
    case 'excluir_flashcard':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_flashcard = intval($_POST['id_flashcard'] ?? 0);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->excluirFlashcard($id_flashcard);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Flashcard excluído com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir flashcard']);
        }
        break;
    
    // ==================== SISTEMA DE ESTUDO ====================
    
    case 'obter_flashcards_para_revisar':
        $id_deck = intval($_GET['id_deck'] ?? 0);
        $limite = intval($_GET['limite'] ?? 20);
        
        $flashcards = $flashcardModel->obterFlashcardsParaRevisar($id_usuario, $id_deck ?: null, $limite);
        
        echo json_encode([
            'success' => true,
            'flashcards' => $flashcards,
            'total' => count($flashcards)
        ]);
        break;
    
    case 'registrar_resposta':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_flashcard = intval($_POST['id_flashcard'] ?? 0);
        $acertou = isset($_POST['acertou']) && $_POST['acertou'] === '1';
        $facilidade_resposta = intval($_POST['facilidade_resposta'] ?? 3);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        // Valida facilidade_resposta (1-5)
        if ($facilidade_resposta < 1 || $facilidade_resposta > 5) {
            $facilidade_resposta = 3;
        }
        
        $sucesso = $flashcardModel->registrarResposta($id_flashcard, $id_usuario, $acertou, $facilidade_resposta);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Resposta registrada com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar resposta']);
        }
        break;
    
    case 'obter_estatisticas':
        $id_deck = intval($_GET['id_deck'] ?? 0);
        
        $estatisticas = $flashcardModel->obterEstatisticas($id_usuario, $id_deck ?: null);
        
        echo json_encode([
            'success' => true,
            'estatisticas' => $estatisticas
        ]);
        break;
    
    // ==================== NOVAS FUNCIONALIDADES PARA O PAINEL ====================
    
    case 'marcar_como_aprendido':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_flashcard = intval($_POST['id_flashcard'] ?? 0);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->marcarComoAprendido($id_flashcard, $id_usuario);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Palavra marcada como aprendida'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao marcar como aprendida']);
        }
        break;
    
    case 'desmarcar_como_aprendido':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $id_flashcard = intval($_POST['id_flashcard'] ?? 0);
        
        if (!$id_flashcard) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do flashcard é obrigatório']);
            break;
        }
        
        $sucesso = $flashcardModel->desmarcarComoAprendido($id_flashcard, $id_usuario);
        
        if ($sucesso) {
            echo json_encode([
                'success' => true,
                'message' => 'Palavra desmarcada como aprendida'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao desmarcar como aprendida']);
        }
        break;
    
    case 'criar_flashcard_rapido':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $palavra_frente = trim($_POST['palavra_frente'] ?? '');
        $palavra_verso = trim($_POST['palavra_verso'] ?? '');
        $idioma = $_POST['idioma'] ?? '';
        $nivel = $_POST['nivel'] ?? '';
        $id_deck = intval($_POST['id_deck'] ?? 0);
        
        // Validações
        if (empty($palavra_frente)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Palavra/frase é obrigatória']);
            break;
        }
        
        if (empty($palavra_verso)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tradução é obrigatória']);
            break;
        }
        
        // Se não especificou deck, busca ou cria o deck padrão
        if (!$id_deck) {
            if (empty($idioma) || empty($nivel)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Idioma e nível são obrigatórios']);
                break;
            }
            
            $deck_padrao = $flashcardModel->obterOuCriarDeckPadrao($id_usuario, $idioma, $nivel);
            if (!$deck_padrao) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar deck padrão']);
                break;
            }
            $id_deck = $deck_padrao['id'];
        }
        
        $id_flashcard = $flashcardModel->criarFlashcardRapido($id_deck, $palavra_frente, $palavra_verso, $id_usuario);
        
        if ($id_flashcard) {
            echo json_encode([
                'success' => true,
                'message' => 'Palavra adicionada com sucesso',
                'id_flashcard' => $id_flashcard,
                'id_deck' => $id_deck
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar palavra']);
        }
        break;
    
    case 'listar_palavras_usuario':
        $idioma = $_GET['idioma'] ?? null;
        $nivel = $_GET['nivel'] ?? null;
        $aprendidas = isset($_GET['aprendidas']) ? ($_GET['aprendidas'] === '1') : null;
        $limite = intval($_GET['limite'] ?? 50);
        
        $palavras = $flashcardModel->listarPalavrasUsuario($id_usuario, $idioma, $nivel, $aprendidas, $limite);
        
        echo json_encode([
            'success' => true,
            'palavras' => $palavras,
            'total' => count($palavras)
        ]);
        break;
    
    case 'obter_deck_padrao':
        $idioma = $_GET['idioma'] ?? '';
        $nivel = $_GET['nivel'] ?? '';
        
        if (empty($idioma) || empty($nivel)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Idioma e nível são obrigatórios']);
            break;
        }
        
        $deck = $flashcardModel->obterOuCriarDeckPadrao($id_usuario, $idioma, $nivel);
        
        if ($deck) {
            echo json_encode([
                'success' => true,
                'deck' => $deck
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao obter deck padrão']);
        }
        break;
    
    // ==================== AÇÃO PADRÃO ====================
    
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Ação não reconhecida',
            'actions_available' => [
                'listar_decks',
                'listar_decks_publicos',
                'obter_deck',
                'criar_deck',
                'atualizar_deck',
                'excluir_deck',
                'listar_flashcards',
                'obter_flashcard',
                'criar_flashcard',
                'atualizar_flashcard',
                'excluir_flashcard',
                'obter_flashcards_para_revisar',
                'registrar_resposta',
                'obter_estatisticas',
                'marcar_como_aprendido',
                'desmarcar_como_aprendido',
                'criar_flashcard_rapido',
                'listar_palavras_usuario',
                'obter_deck_padrao'
            ]
        ]);
        break;
}

// Fecha a conexão com o banco de dados
$database->closeConnection();
?>
