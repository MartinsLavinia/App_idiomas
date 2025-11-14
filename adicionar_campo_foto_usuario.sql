-- Adicionar campo foto_perfil na tabela usuarios se não existir
ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER email;