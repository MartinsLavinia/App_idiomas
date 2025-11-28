-- Tabela para controlar o progresso dos blocos pelos usuários
CREATE TABLE IF NOT EXISTS progresso_blocos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bloco_id INT NOT NULL,
    usuario_id INT NOT NULL,
    liberado TINYINT(1) DEFAULT 0,
    concluido TINYINT(1) DEFAULT 0,
    progresso_percentual INT DEFAULT 0,
    data_liberacao DATETIME NULL,
    data_conclusao DATETIME NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bloco_id) REFERENCES blocos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_bloco (usuario_id, bloco_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_bloco (bloco_id),
    INDEX idx_concluido (concluido),
    INDEX idx_liberado (liberado)
);

-- Adicionar coluna de progresso da unidade na tabela progresso_usuario se não existir
ALTER TABLE progresso_usuario 
ADD COLUMN IF NOT EXISTS progresso_unidade INT DEFAULT 0 AFTER nivel;

-- Adicionar coluna de ordem nos blocos se não existir
ALTER TABLE blocos 
ADD COLUMN IF NOT EXISTS ordem INT DEFAULT 1 AFTER unidade_id;

-- Atualizar ordem dos blocos existentes baseado no ID
UPDATE blocos SET ordem = id WHERE ordem IS NULL OR ordem = 0;

-- Inserir progresso inicial para o primeiro bloco de cada unidade para usuários existentes
INSERT IGNORE INTO progresso_blocos (bloco_id, usuario_id, liberado, data_liberacao)
SELECT 
    b.id as bloco_id,
    pu.id_usuario as usuario_id,
    1 as liberado,
    NOW() as data_liberacao
FROM blocos b
INNER JOIN progresso_usuario pu ON b.idioma = pu.idioma AND b.nivel = pu.nivel
WHERE b.ordem = 1 AND b.tipo != 'especial'
GROUP BY b.unidade_id, pu.id_usuario;