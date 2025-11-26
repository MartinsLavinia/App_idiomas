<?php
session_start();
require_once __DIR__ . '/../../conexao.php';

class IdiomaController {
    private $conn;
    private $database;

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->conn;
    }

    public function __destruct() {
        $this->database->closeConnection();
    }

    /**
     * Busca todos os idiomas disponíveis no sistema
     */
    public function getIdiomasDisponiveis() {
        $sql = "SELECT nome_idioma FROM idiomas ORDER BY nome_idioma ASC";
        $result = $this->conn->query($sql);
        
        $idiomas = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Normalizar nomes: remover acentos para compatibilidade
                $nome_normalizado = str_replace(['ê', 'ã'], ['e', 'a'], $row['nome_idioma']);
                $idiomas[] = $nome_normalizado;
            }
        }
        
        return $idiomas;
    }

    /**
     * Busca idiomas que o usuário já estudou
     */
    public function getIdiomasUsuario($idUsuario) {
        $sql = "SELECT DISTINCT idioma, nivel, data_inicio 
                FROM progresso_usuario 
                WHERE id_usuario = ? 
                ORDER BY data_inicio DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $idiomas = [];
        while ($row = $result->fetch_assoc()) {
            $idiomas[] = $row;
        }
        
        $stmt->close();
        return $idiomas;
    }

    /**
     * Verifica se o usuário já tem progresso em um idioma
     */
    public function temProgressoIdioma($idUsuario, $idioma) {
        $sql = "SELECT COUNT(*) as count FROM progresso_usuario WHERE id_usuario = ? AND idioma = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $idioma);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['count'] > 0;
    }

    /**
     * Adiciona novo idioma para o usuário (com nível inicial A1)
     */
    public function adicionarNovoIdioma($idUsuario, $idioma) {
        if ($this->temProgressoIdioma($idUsuario, $idioma)) {
            return ['success' => false, 'message' => 'Usuário já possui progresso neste idioma'];
        }

        $sql = "INSERT INTO progresso_usuario (id_usuario, idioma, nivel, data_inicio, ultima_atividade) 
                VALUES (?, ?, 'A1', NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $idioma);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Idioma adicionado com sucesso'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Erro ao adicionar idioma'];
        }
    }

    /**
     * Troca o idioma ativo do usuário
     */
    public function trocarIdioma($idUsuario, $idioma) {
        // Validar idioma
        if (empty($idioma) || $idioma === '[object PointerEvent]') {
            return ['success' => false, 'message' => 'Idioma inválido'];
        }
        
        // Verifica se o usuário tem progresso no idioma
        if (!$this->temProgressoIdioma($idUsuario, $idioma)) {
            // Adiciona o idioma com nível inicial A1 e redireciona para quiz
            $resultado = $this->adicionarNovoIdioma($idUsuario, $idioma);
            if ($resultado['success']) {
                return ['success' => false, 'message' => 'Novo idioma adicionado', 'redirect_quiz' => true];
            } else {
                return ['success' => false, 'message' => 'Erro ao adicionar novo idioma'];
            }
        }

        // Atualiza a última atividade do idioma selecionado
        $sql = "UPDATE progresso_usuario SET ultima_atividade = NOW() WHERE id_usuario = ? AND idioma = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $idioma);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Idioma alterado com sucesso'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Erro ao alterar idioma'];
        }
    }

    /**
     * Processa requisições AJAX
     */
    public function processarRequisicao() {
        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        $action = $_POST['action'] ?? '';
        $idUsuario = $_SESSION['id_usuario'];

        switch ($action) {
            case 'get_idiomas_disponiveis':
                $idiomas = $this->getIdiomasDisponiveis();
                echo json_encode(['success' => true, 'idiomas' => $idiomas]);
                break;

            case 'get_idiomas_usuario':
                $idiomas = $this->getIdiomasUsuario($idUsuario);
                echo json_encode(['success' => true, 'idiomas' => $idiomas]);
                break;

            case 'trocar_idioma':
                $idioma = $_POST['idioma'] ?? '';
                $idioma = trim($idioma);
                
                if (empty($idioma) || $idioma === '[object PointerEvent]' || !is_string($idioma)) {
                    echo json_encode(['success' => false, 'message' => 'Idioma inválido']);
                    return;
                }
                
                $resultado = $this->trocarIdioma($idUsuario, $idioma);
                echo json_encode($resultado);
                break;

            case 'adicionar_idioma':
                $idioma = $_POST['idioma'] ?? '';
                if (empty($idioma)) {
                    echo json_encode(['success' => false, 'message' => 'Idioma não informado']);
                    return;
                }
                
                $resultado = $this->adicionarNovoIdioma($idUsuario, $idioma);
                echo json_encode($resultado);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
                break;
        }
    }
}

// Processar requisição se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new IdiomaController();
    $controller->processarRequisicao();
}
?>