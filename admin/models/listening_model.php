<?php
class ListeningModel {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database->conn;
    }
    
    // Salvar exercício de listening
    public function salvarExercicioListening($dados) {
        $sql = "INSERT INTO exercicios_listening 
                (bloco_id, frase, audio_url, opcoes, resposta_correta, idioma, nivel, ordem, tipo_exercicio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'listening')";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $opcoesJson = json_encode($dados['opcoes'], JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param("isssissi", 
            $dados['bloco_id'],
            $dados['frase'],
            $dados['audio_url'],
            $opcoesJson,
            $dados['resposta_correta'],
            $dados['idioma'],
            $dados['nivel'],
            $dados['ordem']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    // Buscar exercícios por bloco
    public function buscarExerciciosPorBloco($bloco_id) {
        $sql = "SELECT * FROM exercicios_listening WHERE bloco_id = ? ORDER BY ordem ASC";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $bloco_id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($result as &$row) {
            $row['opcoes'] = json_decode($row['opcoes'], true);
        }
        
        return $result;
    }
    
    // Buscar exercício por ID
    public function buscarExercicioPorId($id) {
        $sql = "SELECT el.*, b.caminho_id, c.id_unidade, c.nivel as nivel_caminho,
                       u.nome_unidade, u.idioma as idioma_unidade
                FROM exercicios_listening el
                JOIN blocos b ON el.bloco_id = b.id
                JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
                JOIN unidades u ON c.id_unidade = u.id
                WHERE el.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            $result['opcoes'] = json_decode($result['opcoes'], true);
        }
        
        return $result;
    }
    
    // Excluir exercício
    public function excluirExercicio($id) {
        $sql = "DELETE FROM exercicios_listening WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    // Atualizar exercício
    public function atualizarExercicio($id, $dados) {
        $sql = "UPDATE exercicios_listening 
                SET frase = ?, audio_url = ?, opcoes = ?, resposta_correta = ?, 
                    idioma = ?, ordem = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $opcoesJson = json_encode($dados['opcoes'], JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param("sssissi",
            $dados['frase'],
            $dados['audio_url'],
            $opcoesJson,
            $dados['resposta_correta'],
            $dados['idioma'],
            $dados['ordem'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    // Buscar informações do bloco
    public function buscarInfoBloco($bloco_id) {
        $sql = "SELECT b.*, c.nome_caminho, c.id_unidade, c.nivel as nivel_caminho,
                       u.nome_unidade, u.idioma as idioma_unidade
                FROM blocos b
                JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
                JOIN unidades u ON c.id_unidade = u.id
                WHERE b.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $bloco_id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    // Verificar se já existe exercício com mesma ordem no bloco
    public function verificarOrdemExistente($bloco_id, $ordem, $excluir_id = null) {
        $sql = "SELECT id FROM exercicios_listening WHERE bloco_id = ? AND ordem = ?";
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        if ($excluir_id) {
            $stmt->bind_param("iii", $bloco_id, $ordem, $excluir_id);
        } else {
            $stmt->bind_param("ii", $bloco_id, $ordem);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        
        $stmt->close();
        return $existe;
    }
    
    // Buscar próximo número de ordem disponível
    public function buscarProximaOrdem($bloco_id) {
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem 
                FROM exercicios_listening 
                WHERE bloco_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $bloco_id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['proxima_ordem'] ?? 1;
    }
    
    // Buscar estatísticas do bloco
    public function buscarEstatisticasBloco($bloco_id) {
        $sql = "SELECT 
                COUNT(*) as total_exercicios,
                SUM(CASE WHEN tipo_exercicio = 'listening' THEN 1 ELSE 0 END) as total_listening
                FROM exercicios_listening 
                WHERE bloco_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $bloco_id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    // Buscar todos os exercícios de listening com informações do bloco
    public function listarTodosExercicios($filtros = []) {
        $where = "1=1";
        $params = [];
        $types = "";
        
        if (!empty($filtros['bloco_id'])) {
            $where .= " AND el.bloco_id = ?";
            $params[] = $filtros['bloco_id'];
            $types .= "i";
        }
        
        if (!empty($filtros['caminho_id'])) {
            $where .= " AND b.caminho_id = ?";
            $params[] = $filtros['caminho_id'];
            $types .= "i";
        }
        
        if (!empty($filtros['unidade_id'])) {
            $where .= " AND c.id_unidade = ?";
            $params[] = $filtros['unidade_id'];
            $types .= "i";
        }
        
        if (!empty($filtros['idioma'])) {
            $where .= " AND el.idioma = ?";
            $params[] = $filtros['idioma'];
            $types .= "s";
        }
        
        if (!empty($filtros['nivel'])) {
            $where .= " AND el.nivel = ?";
            $params[] = $filtros['nivel'];
            $types .= "s";
        }
        
        $sql = "SELECT el.*, b.titulo as titulo_bloco, b.nome_bloco, 
                       c.nome_caminho, c.nivel as nivel_caminho,
                       u.nome_unidade, u.idioma as idioma_unidade
                FROM exercicios_listening el
                JOIN blocos b ON el.bloco_id = b.id
                JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
                JOIN unidades u ON c.id_unidade = u.id
                WHERE $where 
                ORDER BY u.nome_unidade, c.nome_caminho, b.ordem, el.ordem";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($result as &$row) {
            $row['opcoes'] = json_decode($row['opcoes'], true);
        }
        
        return $result;
    }
}
?>