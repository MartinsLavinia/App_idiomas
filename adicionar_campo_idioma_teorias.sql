-- Script para adicionar campo idioma na tabela teorias
-- Execute este script no seu banco de dados MySQL

USE site_idiomas;

-- Adicionar coluna idioma na tabela teorias
ALTER TABLE teorias ADD COLUMN idioma VARCHAR(50) NOT NULL DEFAULT 'Ingles' AFTER nivel;

-- Criar índice para melhor performance
CREATE INDEX idx_teorias_idioma_nivel ON teorias(idioma, nivel);

-- Atualizar teorias existentes (se houver) para ter um idioma padrão
-- Você pode modificar este comando conforme necessário
UPDATE teorias SET idioma = 'Ingles' WHERE idioma = 'Ingles';

-- Verificar a estrutura da tabela
DESCRIBE teorias;