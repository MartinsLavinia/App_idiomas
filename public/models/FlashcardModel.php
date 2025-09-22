<?php
/**
 * Classe FlashcardModel
 * Responsável por todas as operações de banco de dados relacionadas aos flashcards
 */
class FlashcardModel {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    // ==================== OPERAÇÕES COM DECKS ====================
    
    /**
     * Lista todos os decks de um usuário
     */
    public function listarDecks($id_usuario, $idioma = null, $nivel = null) {
        $sql = "SELECT d.*, 
                       COUNT(f.id) as total_flashcards,
                       COUNT(p.id) as flashcards_estudados
                FROM flashcard_decks d 
                LEFT JOIN flashcards f ON d.id = f.id_deck
                LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?
                WHERE d.id_usuario = ?";
        
        $params = [$id_usuario, $id_usuario];
        $types = "ii";
        
        if ($idioma) {
            $sql .= " AND d.idioma = ?";
            $params[] = $idioma;
            $types .= "s";
        }
        
        if ($nivel) {
            $sql .= " AND d.nivel = ?";
            $params[] = $nivel;
            $types .= "s";
        }
        
        $sql .= " GROUP BY d.id ORDER BY d.data_atualizacao DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Busca decks públicos para estudo
     */
    public function listarDecksPublicos($idioma = null, $nivel = null, $limite = 20) {
        $sql = "SELECT d.*, u.nome as nome_criador,
                       COUNT(f.id) as total_flashcards
                FROM flashcard_decks d 
                JOIN usuarios u ON d.id_usuario = u.id
                LEFT JOIN flashcards f ON d.id = f.id_deck
                WHERE d.publico = TRUE";
        
        $params = [];
        $types = "";
        
        if ($idioma) {
            $sql .= " AND d.idioma = ?";
            $params[] = $idioma;
            $types .= "s";
        }
        
        if ($nivel) {
            $sql .= " AND d.nivel = ?";
            $params[] = $nivel;
            $types .= "s";
        }
        
        $sql .= " GROUP BY d.id ORDER BY d.data_criacao DESC LIMIT ?";
        $params[] = $limite;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtém um deck específico
     */
    public function obterDeck($id_deck, $id_usuario = null) {
        $sql = "SELECT d.*, u.nome as nome_criador,
                       COUNT(f.id) as total_flashcards
                FROM flashcard_decks d 
                JOIN usuarios u ON d.id_usuario = u.id
                LEFT JOIN flashcards f ON d.id = f.id_deck
                WHERE d.id = ?";
        
        $params = [$id_deck];
        $types = "i";
        
        // Se especificado um usuário, verifica se é o dono ou se é público
        if ($id_usuario) {
            $sql .= " AND (d.id_usuario = ? OR d.publico = TRUE)";
            $params[] = $id_usuario;
            $types .= "i";
        }
        
        $sql .= " GROUP BY d.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Cria um novo deck
     */
    public function criarDeck($dados) {
        $sql = "INSERT INTO flashcard_decks (id_usuario, nome, descricao, idioma, nivel, publico) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssi", 
            $dados['id_usuario'],
            $dados['nome'],
            $dados['descricao'],
            $dados['idioma'],
            $dados['nivel'],
            $dados['publico'] ? 1 : 0
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
   /**
 * Atualiza um deck existente
 */
public function atualizarDeck($id_deck, $dados, $id_usuario) {
    $sql = "UPDATE flashcard_decks 
            SET nome = ?, descricao = ?, idioma = ?, nivel = ?, publico = ?, data_atualizacao = NOW()
            WHERE id = ? AND id_usuario = ?";
    
    $stmt = $this->conn->prepare($sql);
    // CORREÇÃO: Remover um "i" extra - eram 7 parâmetros mas 8 "i"
    $stmt->bind_param("ssssiii", 
        $dados['nome'],
        $dados['descricao'],
        $dados['idioma'],
        $dados['nivel'],
        $dados['publico'] ? 1 : 0,
        $id_deck,
        $id_usuario
    );
    
    return $stmt->execute();
}
    
    /**
     * Exclui um deck e todos os seus flashcards
     */
    public function excluirDeck($id_deck, $id_usuario) {
        $sql = "DELETE FROM flashcard_decks WHERE id = ? AND id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_deck, $id_usuario);
        
        return $stmt->execute();
    }
    
    // ==================== OPERAÇÕES COM FLASHCARDS ====================
    
    /**
     * Lista flashcards de um deck
     */
    public function listarFlashcards($id_deck, $id_usuario = null) {
        $sql = "SELECT f.*";
        
        if ($id_usuario) {
            $sql .= ", p.acertos, p.erros, p.ultima_revisao, p.proxima_revisao, p.facilidade";
        }
        
        $sql .= " FROM flashcards f";
        
        if ($id_usuario) {
            $sql .= " LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?";
        }
        
        $sql .= " WHERE f.id_deck = ? ORDER BY f.ordem_no_deck ASC, f.id ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($id_usuario) {
            $stmt->bind_param("ii", $id_usuario, $id_deck);
        } else {
            $stmt->bind_param("i", $id_deck);
        }
        
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtém um flashcard específico
     */
    public function obterFlashcard($id_flashcard, $id_usuario = null) {
        $sql = "SELECT f.*";
        
        if ($id_usuario) {
            $sql .= ", p.acertos, p.erros, p.ultima_revisao, p.proxima_revisao, p.facilidade";
        }
        
        $sql .= " FROM flashcards f";
        
        if ($id_usuario) {
            $sql .= " LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?";
        }
        
        $sql .= " WHERE f.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($id_usuario) {
            $stmt->bind_param("ii", $id_usuario, $id_flashcard);
        } else {
            $stmt->bind_param("i", $id_flashcard);
        }
        
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Cria um novo flashcard
     */
    public function criarFlashcard($dados) {
        $sql = "INSERT INTO flashcards (id_deck, frente, verso, dica, imagem_frente, imagem_verso, 
                                       audio_frente, audio_verso, dificuldade, ordem_no_deck) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssssssi", 
            $dados['id_deck'],
            $dados['frente'],
            $dados['verso'],
            $dados['dica'] ?? null,
            $dados['imagem_frente'] ?? null,
            $dados['imagem_verso'] ?? null,
            $dados['audio_frente'] ?? null,
            $dados['audio_verso'] ?? null,
            $dados['dificuldade'] ?? 'medio',
            $dados['ordem_no_deck'] ?? 0
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Atualiza um flashcard existente
     */
    public function atualizarFlashcard($id_flashcard, $dados) {
        $sql = "UPDATE flashcards 
                SET frente = ?, verso = ?, dica = ?, imagem_frente = ?, imagem_verso = ?,
                    audio_frente = ?, audio_verso = ?, dificuldade = ?, ordem_no_deck = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssssii", 
            $dados['frente'],
            $dados['verso'],
            $dados['dica'] ?? null,
            $dados['imagem_frente'] ?? null,
            $dados['imagem_verso'] ?? null,
            $dados['audio_frente'] ?? null,
            $dados['audio_verso'] ?? null,
            $dados['dificuldade'] ?? 'medio',
            $dados['ordem_no_deck'] ?? 0,
            $id_flashcard
        );
        
        return $stmt->execute();
    }
    
    /**
     * Exclui um flashcard
     */
    public function excluirFlashcard($id_flashcard) {
        $sql = "DELETE FROM flashcards WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_flashcard);
        
        return $stmt->execute();
    }
    
    // ==================== SISTEMA DE PROGRESSO E REPETIÇÃO ESPAÇADA ====================
    
    /**
     * Obtém flashcards que precisam ser revisados
     */
    public function obterFlashcardsParaRevisar($id_usuario, $id_deck = null, $limite = 20) {
        $sql = "SELECT f.*, p.acertos, p.erros, p.ultima_revisao, p.proxima_revisao, p.facilidade,
                       d.nome as nome_deck
                FROM flashcards f
                JOIN flashcard_decks d ON f.id_deck = d.id
                LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?
                WHERE (p.proxima_revisao IS NULL OR p.proxima_revisao <= NOW())
                  AND (d.id_usuario = ? OR d.publico = TRUE)";
        
        $params = [$id_usuario, $id_usuario];
        $types = "ii";
        
        if ($id_deck) {
            $sql .= " AND f.id_deck = ?";
            $params[] = $id_deck;
            $types .= "i";
        }
        
        $sql .= " ORDER BY p.proxima_revisao ASC, f.id ASC LIMIT ?";
        $params[] = $limite;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Registra uma resposta do usuário e atualiza o progresso
     */
    public function registrarResposta($id_flashcard, $id_usuario, $acertou, $facilidade_resposta = 3) {
        // Primeiro, verifica se já existe progresso para este flashcard
        $sql_check = "SELECT * FROM flashcard_progresso WHERE id_flashcard = ? AND id_usuario = ?";
        $stmt_check = $this->conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_flashcard, $id_usuario);
        $stmt_check->execute();
        $progresso_atual = $stmt_check->get_result()->fetch_assoc();
        
        if ($progresso_atual) {
            // Atualiza progresso existente
            $acertos = $progresso_atual['acertos'];
            $erros = $progresso_atual['erros'];
            $facilidade = $progresso_atual['facilidade'];
            $intervalo_atual = $progresso_atual['intervalo_dias'];
            
            if ($acertou) {
                $acertos++;
                // Algoritmo SM-2 simplificado
                $facilidade = max(1.3, $facilidade + (0.1 - (5 - $facilidade_resposta) * (0.08 + (5 - $facilidade_resposta) * 0.02)));
                $novo_intervalo = max(1, round($intervalo_atual * $facilidade));
            } else {
                $erros++;
                $facilidade = max(1.3, $facilidade - 0.2);
                $novo_intervalo = 1; // Volta para 1 dia se errou
            }
            
            $proxima_revisao = date('Y-m-d H:i:s', strtotime("+{$novo_intervalo} days"));
            
            $sql_update = "UPDATE flashcard_progresso 
                          SET acertos = ?, erros = ?, ultima_revisao = NOW(), 
                              proxima_revisao = ?, intervalo_dias = ?, facilidade = ?
                          WHERE id_flashcard = ? AND id_usuario = ?";
            
            $stmt_update = $this->conn->prepare($sql_update);
            $stmt_update->bind_param("iisidii", $acertos, $erros, $proxima_revisao, $novo_intervalo, $facilidade, $id_flashcard, $id_usuario);
            
            return $stmt_update->execute();
            
        } else {
            // Cria novo progresso
            $acertos = $acertou ? 1 : 0;
            $erros = $acertou ? 0 : 1;
            $facilidade = 2.5;
            $intervalo = $acertou ? 6 : 1; // Se acertou na primeira, próxima revisão em 6 dias
            $proxima_revisao = date('Y-m-d H:i:s', strtotime("+{$intervalo} days"));
            
            $sql_insert = "INSERT INTO flashcard_progresso 
                          (id_flashcard, id_usuario, acertos, erros, ultima_revisao, 
                           proxima_revisao, intervalo_dias, facilidade)
                          VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
            
            $stmt_insert = $this->conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiiisid", $id_flashcard, $id_usuario, $acertos, $erros, $proxima_revisao, $intervalo, $facilidade);
            
            return $stmt_insert->execute();
        }
    }
    
    /**
     * Obtém estatísticas de progresso do usuário
     */
    public function obterEstatisticas($id_usuario, $id_deck = null) {
        $sql = "SELECT 
                    COUNT(DISTINCT f.id) as total_flashcards,
                    COUNT(DISTINCT p.id) as flashcards_estudados,
                    SUM(p.acertos) as total_acertos,
                    SUM(p.erros) as total_erros,
                    COUNT(CASE WHEN p.proxima_revisao <= NOW() THEN 1 END) as para_revisar,
                    AVG(p.facilidade) as facilidade_media
                FROM flashcards f
                JOIN flashcard_decks d ON f.id_deck = d.id
                LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?
                WHERE (d.id_usuario = ? OR d.publico = TRUE)";
        
        $params = [$id_usuario, $id_usuario];
        $types = "ii";
        
        if ($id_deck) {
            $sql .= " AND f.id_deck = ?";
            $params[] = $id_deck;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    /**
     * Marca um flashcard como aprendido (não aparecerá mais para revisão)
     */
    public function marcarComoAprendido($id_flashcard, $id_usuario) {
        // Define uma data muito distante no futuro para a próxima revisão
        $proxima_revisao = date('Y-m-d H:i:s', strtotime('+10 years'));
        
        $sql_check = "SELECT * FROM flashcard_progresso WHERE id_flashcard = ? AND id_usuario = ?";
        $stmt_check = $this->conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_flashcard, $id_usuario);
        $stmt_check->execute();
        $progresso_atual = $stmt_check->get_result()->fetch_assoc();
        
        if ($progresso_atual) {
            // Atualiza progresso existente
            $sql_update = "UPDATE flashcard_progresso 
                          SET proxima_revisao = ?, aprendido = 1, ultima_revisao = NOW()
                          WHERE id_flashcard = ? AND id_usuario = ?";
            
            $stmt_update = $this->conn->prepare($sql_update);
            $stmt_update->bind_param("sii", $proxima_revisao, $id_flashcard, $id_usuario);
            
            return $stmt_update->execute();
        } else {
            // Cria novo progresso marcado como aprendido
            $sql_insert = "INSERT INTO flashcard_progresso 
                          (id_flashcard, id_usuario, acertos, erros, ultima_revisao, 
                           proxima_revisao, intervalo_dias, facilidade, aprendido)
                          VALUES (?, ?, 1, 0, NOW(), ?, 365, 3.0, 1)";
            
            $stmt_insert = $this->conn->prepare($sql_insert);
            $stmt_insert->bind_param("iis", $id_flashcard, $id_usuario, $proxima_revisao);
            
            return $stmt_insert->execute();
        }
    }
    
    /**
     * Desmarca um flashcard como aprendido (volta para o ciclo de revisão)
     */
    public function desmarcarComoAprendido($id_flashcard, $id_usuario) {
        $sql = "UPDATE flashcard_progresso 
                SET aprendido = 0, proxima_revisao = NOW(), intervalo_dias = 1
                WHERE id_flashcard = ? AND id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_flashcard, $id_usuario);
        
        return $stmt->execute();
    }
    
    /**
     * Cria um flashcard rápido a partir de uma palavra/frase
     */
    public function criarFlashcardRapido($id_deck, $palavra_frente, $palavra_verso, $id_usuario) {
        // Verifica se o usuário tem permissão para adicionar ao deck
        $deck = $this->obterDeck($id_deck, $id_usuario);
        if (!$deck || ($deck['id_usuario'] != $id_usuario && !$deck['publico'])) {
            return false;
        }
        
        // Obtém a próxima ordem no deck
        $sql_ordem = "SELECT COALESCE(MAX(ordem_no_deck), 0) + 1 as proxima_ordem FROM flashcards WHERE id_deck = ?";
        $stmt_ordem = $this->conn->prepare($sql_ordem);
        $stmt_ordem->bind_param("i", $id_deck);
        $stmt_ordem->execute();
        $resultado_ordem = $stmt_ordem->get_result()->fetch_assoc();
        $ordem = $resultado_ordem['proxima_ordem'];
        
        $dados = [
            'id_deck' => $id_deck,
            'frente' => trim($palavra_frente),
            'verso' => trim($palavra_verso),
            'dica' => null,
            'imagem_frente' => null,
            'imagem_verso' => null,
            'audio_frente' => null,
            'audio_verso' => null,
            'dificuldade' => 'medio',
            'ordem_no_deck' => $ordem
        ];
        
        return $this->criarFlashcard($dados);
    }
    
    /**
     * Lista palavras/flashcards do usuário com filtros
     */
    public function listarPalavrasUsuario($id_usuario, $idioma = null, $nivel = null, $aprendidas = null, $limite = 50) {
        $sql = "SELECT f.*, d.nome as nome_deck, d.idioma, d.nivel,
                       p.acertos, p.erros, p.aprendido, p.ultima_revisao, p.proxima_revisao
                FROM flashcards f
                JOIN flashcard_decks d ON f.id_deck = d.id
                LEFT JOIN flashcard_progresso p ON f.id = p.id_flashcard AND p.id_usuario = ?
                WHERE d.id_usuario = ?";
        
        $params = [$id_usuario, $id_usuario];
        $types = "ii";
        
        if ($idioma) {
            $sql .= " AND d.idioma = ?";
            $params[] = $idioma;
            $types .= "s";
        }
        
        if ($nivel) {
            $sql .= " AND d.nivel = ?";
            $params[] = $nivel;
            $types .= "s";
        }
        
        if ($aprendidas !== null) {
            if ($aprendidas) {
                $sql .= " AND p.aprendido = 1";
            } else {
                $sql .= " AND (p.aprendido IS NULL OR p.aprendido = 0)";
            }
        }
        
        $sql .= " ORDER BY f.frente ASC LIMIT ?";
        $params[] = $limite;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Busca um deck padrão do usuário ou cria um se não existir
     */
    public function obterOuCriarDeckPadrao($id_usuario, $idioma, $nivel) {
        // Busca deck padrão existente
        $sql = "SELECT * FROM flashcard_decks 
                WHERE id_usuario = ? AND idioma = ? AND nivel = ? AND nome LIKE 'Minhas Palavras%'
                ORDER BY data_criacao ASC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $id_usuario, $idioma, $nivel);
        $stmt->execute();
        $deck = $stmt->get_result()->fetch_assoc();
        
        if ($deck) {
            return $deck;
        }
        
        // Cria deck padrão se não existir
        $nome_deck = "Minhas Palavras - " . $idioma . " " . $nivel;
        $dados_deck = [
            'id_usuario' => $id_usuario,
            'nome' => $nome_deck,
            'descricao' => 'Deck criado automaticamente para suas palavras personalizadas',
            'idioma' => $idioma,
            'nivel' => $nivel,
            'publico' => false
        ];
        
        $id_deck = $this->criarDeck($dados_deck);
        
        if ($id_deck) {
            return $this->obterDeck($id_deck, $id_usuario);
        }
        
        return false;
    }
}
?>
