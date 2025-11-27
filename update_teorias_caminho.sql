-- Script para adicionar campo caminho_id na tabela teorias
-- Execute este script no seu banco de dados MySQL

USE devgom44_aims-sub1;

-- Verificar se a coluna caminho_id já existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'devgom44_aims-sub1' 
AND TABLE_NAME = 'teorias' 
AND COLUMN_NAME = 'caminho_id';

-- Adicionar coluna caminho_id apenas se não existir
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE teorias ADD COLUMN caminho_id INT NULL AFTER idioma',
    'SELECT "Coluna caminho_id já existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar chave estrangeira (apenas se a coluna foi criada)
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE teorias ADD CONSTRAINT fk_teorias_caminho FOREIGN KEY (caminho_id) REFERENCES caminhos_aprendizagem(id) ON DELETE SET NULL',
    'SELECT "Chave estrangeira não criada - coluna já existia" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar índice para melhor performance (apenas se a coluna foi criada)
SET @sql = IF(@col_exists = 0,
    'CREATE INDEX idx_teorias_caminho ON teorias(caminho_id)',
    'SELECT "Índice não criado - coluna já existia" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar a estrutura da tabela
DESCRIBE teorias;

-- Mostrar teorias existentes
SELECT id, titulo, nivel, idioma, caminho_id, ordem FROM teorias ORDER BY idioma, caminho_id, nivel, ordem;