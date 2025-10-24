<?php

class SpeechExerciseModel {
    private $conn;
    private $table = 'exercicios';

    public function __construct(Database $database) {
        $this->conn = $database->conn;
    }

    /**
     * Adiciona um novo exercício de fala ao banco de dados.
     * 
     * @param int $caminhoId ID do caminho de aprendizagem
     * @param int $blocoId ID do bloco
     * @param int $ordem Ordem de exibição
     * @param string $pergunta Pergunta ou instrução do exercício
     * @param string $conteudo JSON com os detalhes do exercício (frase_esperada, etc.)
     * @return bool Sucesso ou falha na inserção
     */
    public function create($caminhoId, $blocoId, $ordem, $pergunta, $conteudo) {
        $sql = "INSERT INTO " . $this->table . " (caminho_id, bloco_id, ordem, tipo, pergunta, conteudo) VALUES (?, ?, ?, 'fala', ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Erro na preparação da consulta: " . $this->conn->error);
            return false;
        }

        // Debug: log dos dados que serão inseridos
        error_log("Tentando inserir exercício de fala:");
        error_log("Caminho ID: " . $caminhoId);
        error_log("Bloco ID: " . $blocoId);
        error_log("Ordem: " . $ordem);
        error_log("Pergunta: " . $pergunta);
        error_log("Conteúdo: " . $conteudo);

        $stmt->bind_param("iiiss", $caminhoId, $blocoId, $ordem, $pergunta, $conteudo);
        
        if ($stmt->execute()) {
            error_log("Exercício de fala inserido com sucesso!");
            $stmt->close();
            return true;
        } else {
            error_log("Erro ao adicionar exercício de fala: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Busca um exercício por ID.
     * 
     * @param int $exercicioId
     * @return array|null Dados do exercício ou null se não encontrado
     */
    public function findById($exercicioId) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ? AND tipo = 'fala'";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Erro na preparação da consulta: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("i", $exercicioId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
}
?>