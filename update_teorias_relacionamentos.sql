-- Script para atualizar tabela teorias com relacionamentos
-- Execute este script no seu banco de dados MySQL

USE site_idiomas;

-- 1. Adicionar coluna idioma_id (se não existir)
ALTER TABLE teorias ADD COLUMN IF NOT EXISTS idioma_id INT NULL AFTER nivel;

-- 2. Adicionar coluna caminho_id (se não existir)  
ALTER TABLE teorias ADD COLUMN IF NOT EXISTS caminho_id INT NULL AFTER idioma_id;

-- 3. Preencher idioma_id baseado na coluna idioma existente
UPDATE teorias t 
SET idioma_id = (
    SELECT i.id 
    FROM idiomas i 
    WHERE i.idioma = t.idioma 
    LIMIT 1
) 
WHERE t.idioma_id IS NULL AND t.idioma IS NOT NULL;

-- 4. Adicionar chaves estrangeiras
ALTER TABLE teorias 
ADD CONSTRAINT fk_teorias_idioma 
FOREIGN KEY (idioma_id) REFERENCES idiomas(id) ON DELETE SET NULL;

ALTER TABLE teorias 
ADD CONSTRAINT fk_teorias_caminho 
FOREIGN KEY (caminho_id) REFERENCES caminhos_aprendizagem(id) ON DELETE SET NULL;

-- 5. Criar índices para melhor performance
CREATE INDEX idx_teorias_idioma ON teorias(idioma_id);
CREATE INDEX idx_teorias_caminho ON teorias(caminho_id);
CREATE INDEX idx_teorias_idioma_nivel ON teorias(idioma_id, nivel);

-- 6. Remover a coluna idioma antiga (OPCIONAL - descomente se quiser remover)
-- ALTER TABLE teorias DROP COLUMN idioma;

-- 7. Verificar a estrutura final
DESCRIBE teorias;

-- 8. Mostrar dados de exemplo
SELECT t.id, t.titulo, t.nivel, t.idioma_id, t.caminho_id, i.idioma as idioma_nome, c.nome as caminho_nome
FROM teorias t 
LEFT JOIN idiomas i ON t.idioma_id = i.id 
LEFT JOIN caminhos_aprendizagem c ON t.caminho_id = c.id 
ORDER BY i.idioma, c.nome, t.nivel, t.ordem
LIMIT 10;