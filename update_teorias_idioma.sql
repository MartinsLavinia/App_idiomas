-- Script para atualizar a tabela teorias com campo idioma
-- Execute este script no seu banco de dados MySQL

USE devgom44_aims-sub1;

-- Verificar se a coluna idioma já existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'devgom44_aims-sub1' 
AND TABLE_NAME = 'teorias' 
AND COLUMN_NAME = 'idioma';

-- Adicionar coluna idioma apenas se não existir
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE teorias ADD COLUMN idioma VARCHAR(50) NOT NULL DEFAULT "Ingles" AFTER nivel',
    'SELECT "Coluna idioma já existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar índice para melhor performance (apenas se a coluna foi criada)
SET @sql = IF(@col_exists = 0,
    'CREATE INDEX idx_teorias_idioma_nivel ON teorias(idioma, nivel)',
    'SELECT "Índice não criado - coluna já existia" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar a estrutura da tabela
DESCRIBE teorias;

-- Mostrar teorias existentes
SELECT id, titulo, nivel, idioma, ordem FROM teorias ORDER BY idioma, nivel, ordem;