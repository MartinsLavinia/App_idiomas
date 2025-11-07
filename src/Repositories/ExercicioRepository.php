<?php
/**
 * Repository para acesso aos dados de exercícios
 * Implementa padrão Repository e separação de responsabilidades
 */

namespace App\Repositories;

class ExercicioRepository
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../../conexao.php';
        $database = new \Database();
        $this->conn = $database->conn;
    }

    /**
     * Busca exercício por ID (tabela principal)
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT e.*, b.nome_bloco, c.nome_caminho, u.nome_unidade, u.idioma as idioma_unidade
                FROM exercicios e
                LEFT JOIN blocos b ON e.bloco_id = b.id
                LEFT JOIN caminhos_aprendizagem c ON e.caminho_id = c.id
                LEFT JOIN unidades u ON c.id_unidade = u.id
                WHERE e.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['conteudo'])) {
            $result['conteudo'] = json_decode($result['conteudo'], true);
        }
        
        return $result;
    }

    /**
     * Busca exercício de listening por ID
     */
    public function buscarListeningPorId(int $id): ?array
    {
        $sql = "SELECT el.*, b.nome_bloco, c.nome_caminho, u.nome_unidade
                FROM exercicios_listening el
                LEFT JOIN blocos b ON el.bloco_id = b.id
                LEFT JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
                LEFT JOIN unidades u ON c.id_unidade = u.id
                WHERE el.id = ? AND el.ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['opcoes'])) {
            $result['opcoes'] = json_decode($result['opcoes'], true);
        }
        
        return $result;
    }

    /**
     * Busca exercício de fala por ID
     */
    public function buscarFalaPorId(int $id): ?array
    {
        $sql = "SELECT ef.*, b.nome_bloco, c.nome_caminho, u.nome_unidade
                FROM exercicios_fala ef
                LEFT JOIN blocos b ON ef.bloco_id = b.id
                LEFT JOIN caminhos_aprendizagem c ON b.caminho_id = c.id
                LEFT JOIN unidades u ON c.id_unidade = u.id
                WHERE ef.id = ? AND ef.ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['palavras_chave'])) {
            $result['palavras_chave'] = json_decode($result['palavras_chave'], true);
        }
        
        return $result;
    }

    /**
     * Busca exercícios por bloco
     */
    public function buscarPorBloco(int $blocoId): array
    {
        $sql = "SELECT * FROM exercicios WHERE bloco_id = ? ORDER BY ordem ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $blocoId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($result as &$row) {
            if (!empty($row['conteudo'])) {
                $row['conteudo'] = json_decode($row['conteudo'], true);
            }
        }
        
        return $result;
    }

    /**
     * Busca exercícios de listening por bloco
     */
    public function buscarListeningPorBloco(int $blocoId): array
    {
        $sql = "SELECT * FROM exercicios_listening WHERE bloco_id = ? AND ativo = 1 ORDER BY ordem ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $blocoId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($result as &$row) {
            if (!empty($row['opcoes'])) {
                $row['opcoes'] = json_decode($row['opcoes'], true);
            }
        }
        
        return $result;
    }

    /**
     * Busca exercícios de fala por bloco
     */
    public function buscarFalaPorBloco(int $blocoId): array
    {
        $sql = "SELECT * FROM exercicios_fala WHERE bloco_id = ? AND ativo = 1 ORDER BY ordem ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $blocoId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($result as &$row) {
            if (!empty($row['palavras_chave'])) {
                $row['palavras_chave'] = json_decode($row['palavras_chave'], true);
            }
        }
        
        return $result;
    }

    /**
     * Salva exercício de listening
     */
    public function salvarListening(array $dados): int
    {
        $sql = "INSERT INTO exercicios_listening 
                (bloco_id, frase_original, audio_url, opcoes, resposta_correta, 
                 explicacao, dicas_compreensao, transcricao, idioma, nivel, ordem) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        $opcoes = json_encode($dados['opcoes'], JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param("isssississi", 
            $dados['bloco_id'],
            $dados['frase_original'],
            $dados['audio_url'],
            $opcoes,
            $dados['resposta_correta'],
            $dados['explicacao'],
            $dados['dicas_compreensao'],
            $dados['transcricao'],
            $dados['idioma'],
            $dados['nivel'],
            $dados['ordem']
        );
        
        $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();
        
        return $id;
    }

    /**
     * Salva exercício de fala
     */
    public function salvarFala(array $dados): int
    {
        $sql = "INSERT INTO exercicios_fala 
                (bloco_id, frase_esperada, frase_exemplo_audio, dicas_pronuncia, 
                 palavras_chave, contexto, idioma, nivel, ordem) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        $palavrasChave = json_encode($dados['palavras_chave'] ?? [], JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param("isssssssi", 
            $dados['bloco_id'],
            $dados['frase_esperada'],
            $dados['frase_exemplo_audio'],
            $dados['dicas_pronuncia'],
            $palavrasChave,
            $dados['contexto'],
            $dados['idioma'],
            $dados['nivel'],
            $dados['ordem']
        );
        
        $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();
        
        return $id;
    }

    /**
     * Atualiza URL do áudio
     */
    public function atualizarAudioUrl(int $exercicioId, string $audioUrl): bool
    {
        $sql = "UPDATE exercicios_listening SET audio_url = ? WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $audioUrl, $exercicioId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Registra resposta do usuário
     */
    public function registrarResposta(array $dados): bool
    {
        $sql = "INSERT INTO respostas_exercicios 
                (id_usuario, exercicio_id, tipo_exercicio, resposta_usuario, 
                 resposta_transcrita, acertou, pontuacao, feedback_detalhado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisssiis", 
            $dados['id_usuario'],
            $dados['exercicio_id'],
            $dados['tipo_exercicio'],
            $dados['resposta_usuario'],
            $dados['resposta_transcrita'],
            $dados['acertou'],
            $dados['pontuacao'],
            $dados['feedback_detalhado']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Busca estatísticas do usuário
     */
    public function buscarEstatisticasUsuario(int $usuarioId, int $blocoId = null): array
    {
        $whereClause = "WHERE re.id_usuario = ?";
        $params = [$usuarioId];
        $types = "i";
        
        if ($blocoId) {
            $whereClause .= " AND (e.bloco_id = ? OR el.bloco_id = ? OR ef.bloco_id = ?)";
            $params = array_merge($params, [$blocoId, $blocoId, $blocoId]);
            $types .= "iii";
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_respostas,
                    SUM(re.acertou) as total_acertos,
                    AVG(re.pontuacao) as pontuacao_media,
                    re.tipo_exercicio,
                    COUNT(CASE WHEN re.acertou = 1 THEN 1 END) as acertos_por_tipo
                FROM respostas_exercicios re
                LEFT JOIN exercicios e ON re.exercicio_id = e.id AND re.tipo_exercicio != 'listening' AND re.tipo_exercicio != 'fala'
                LEFT JOIN exercicios_listening el ON re.exercicio_id = el.id AND re.tipo_exercicio = 'listening'
                LEFT JOIN exercicios_fala ef ON re.exercicio_id = ef.id AND re.tipo_exercicio = 'fala'
                $whereClause
                GROUP BY re.tipo_exercicio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }

    /**
     * Busca próxima ordem disponível para um bloco
     */
    public function buscarProximaOrdem(int $blocoId, string $tipoTabela = 'exercicios'): int
    {
        $tabelas = [
            'exercicios' => 'exercicios',
            'listening' => 'exercicios_listening',
            'fala' => 'exercicios_fala'
        ];
        
        $tabela = $tabelas[$tipoTabela] ?? 'exercicios';
        
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem FROM $tabela WHERE bloco_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $blocoId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['proxima_ordem'] ?? 1;
    }

    /**
     * Verifica se ordem já existe
     */
    public function verificarOrdemExistente(int $blocoId, int $ordem, string $tipoTabela = 'exercicios', int $excluirId = null): bool
    {
        $tabelas = [
            'exercicios' => 'exercicios',
            'listening' => 'exercicios_listening',
            'fala' => 'exercicios_fala'
        ];
        
        $tabela = $tabelas[$tipoTabela] ?? 'exercicios';
        
        $sql = "SELECT id FROM $tabela WHERE bloco_id = ? AND ordem = ?";
        $params = [$blocoId, $ordem];
        $types = "ii";
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        
        $stmt->close();
        return $existe;
    }
}