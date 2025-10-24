<?php
// [file name]: models/SpeechModel.php
class SpeechModel {
    private $database;
    
    public function __construct($database) {
        $this->database = $database;
    }
    
    public function salvarRespostaFala($id_usuario, $id_exercicio, $audio_data, $frase_esperada, $pontuacao, $feedback) {
        $sql = "INSERT INTO respostas_fala (id_usuario, id_exercicio, audio_data, frase_esperada, pontuacao, feedback, data_resposta) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->database->conn->prepare($sql);
        $stmt->bind_param("iissis", $id_usuario, $id_exercicio, $audio_data, $frase_esperada, $pontuacao, $feedback);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }
        
        $stmt->close();
        return false;
    }
    
    public function getExercicioFala($exercicio_id) {
        $sql = "SELECT * FROM exercicios WHERE id = ? AND tipo = 'normal'";
        $stmt = $this->database->conn->prepare($sql);
        $stmt->bind_param("i", $exercicio_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && $result['conteudo']) {
            $result['conteudo'] = json_decode($result['conteudo'], true);
        }
        
        return $result;
    }
}
?>