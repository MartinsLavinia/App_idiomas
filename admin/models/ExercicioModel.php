<?php

class ExercicioModel {
    private $conn;
    private $table = 'exercicios';

    public function __construct(Database $database) {
        $this->conn = $database->conn;
    }

    /**
     * Adiciona um novo exercício ao banco de dados.
     * 
     * @param int $caminhoId ID do caminho de aprendizagem
     * @param int $blocoId ID do bloco
     * @param int $ordem Ordem de exibição
     * @param string $tipo Tipo do exercício (multipla_escolha, texto_livre, etc.)
     * @param string $pergunta Pergunta ou instrução do exercício
     * @param string $conteudo JSON com os detalhes do exercício
     * @return bool Sucesso ou falha na inserção
     */
    public function create($caminhoId, $blocoId, $ordem, $tipo, $pergunta, $conteudo) {
        $sql = "INSERT INTO " . $this->table . " (caminho_id, bloco_id, ordem, tipo, pergunta, conteudo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Erro na preparação da consulta: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("iiisss", $caminhoId, $blocoId, $ordem, $tipo, $pergunta, $conteudo);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Erro ao adicionar exercício: " . $stmt->error);
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
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
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
