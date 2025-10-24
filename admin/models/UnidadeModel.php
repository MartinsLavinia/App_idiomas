<?php

class UnidadeModel {
    private $conn;

    public function __construct(Database $database) {
        $this->conn = $database->conn;
    }

    public function getUnidadeInfo($unidadeId) {
        $sql = "SELECT u.*, c.nome_caminho, c.nivel 
                FROM unidades u 
                LEFT JOIN caminhos_aprendizagem c ON u.id = c.id_unidade 
                WHERE u.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $unidadeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getCaminhosByUnidade($unidadeId) {
        $sql = "SELECT id, nome_caminho FROM caminhos_aprendizagem WHERE id_unidade = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $unidadeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getBlocosByCaminhos(array $caminhoIds) {
        if (empty($caminhoIds)) {
            return [];
        }
        $placeholders = str_repeat('?,' , count($caminhoIds) - 1) . '?';
        $sql = "SELECT id, caminho_id, nome_bloco, ordem 
                FROM blocos 
                WHERE caminho_id IN ($placeholders) 
                ORDER BY caminho_id, ordem ASC";
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('i', count($caminhoIds));
        $stmt->bind_param($types, ...$caminhoIds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $blocosPorCaminho = [];
        foreach ($result as $bloco) {
            $blocosPorCaminho[$bloco["caminho_id"]][] = $bloco;
        }
        return $blocosPorCaminho;
    }
}
?>
