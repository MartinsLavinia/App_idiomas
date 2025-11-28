-- Script para corrigir idiomas duplicados
USE site_idiomas;

-- 1. Verificar idiomas duplicados
SELECT idioma, COUNT(*) as total 
FROM idiomas 
GROUP BY idioma 
HAVING COUNT(*) > 1;

-- 2. Criar tabela temporária com idiomas únicos
CREATE TEMPORARY TABLE idiomas_unicos AS
SELECT MIN(id) as id, idioma
FROM idiomas
GROUP BY idioma;

-- 3. Atualizar teorias para usar o ID correto (menor ID de cada idioma)
UPDATE teorias t
SET idioma_id = (
    SELECT iu.id 
    FROM idiomas_unicos iu 
    WHERE iu.idioma = t.idioma
)
WHERE t.idioma IS NOT NULL;

-- 4. Remover idiomas duplicados (manter apenas o com menor ID)
DELETE i1 FROM idiomas i1
INNER JOIN idiomas i2 
WHERE i1.id > i2.id AND i1.idioma = i2.idioma;

-- 5. Verificar se ainda há duplicados
SELECT idioma, COUNT(*) as total 
FROM idiomas 
GROUP BY idioma 
HAVING COUNT(*) > 1;

-- 6. Adicionar chaves estrangeiras
ALTER TABLE teorias 
ADD CONSTRAINT fk_teorias_idioma 
FOREIGN KEY (idioma_id) REFERENCES idiomas(id) ON DELETE SET NULL;

-- 7. Verificar resultado
SELECT * FROM idiomas ORDER BY idioma;