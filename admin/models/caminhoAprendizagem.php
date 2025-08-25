<?php
class CaminhoAprendizagem {
    private $conn;
    private $table = "caminhos_aprendizagem";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function buscarPorId($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function atualizarCaminho($id, $idioma, $nome_caminho, $nivel) {
        $sql = "UPDATE {$this->table} SET idioma = ?, nome_caminho = ?, nivel = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $idioma, $nome_caminho, $nivel, $id);
        return $stmt->execute();
    }
}
?>
