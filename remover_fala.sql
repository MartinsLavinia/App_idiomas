-- Script para remover funcionalidade de fala/speech do sistema
-- Execute este script no MySQL para remover completamente a categoria 'fala'

-- 1. Atualizar exercícios que têm categoria 'fala' para 'gramatica'
UPDATE exercicios SET categoria = 'gramatica' WHERE categoria = 'fala';

-- 2. Alterar a estrutura da tabela para remover 'fala' das opções do ENUM
ALTER TABLE exercicios MODIFY COLUMN categoria enum('gramatica','escrita','leitura','audicao') DEFAULT 'gramatica';

-- 3. Verificar se há registros na tabela de respostas relacionados a exercícios de fala
-- (Opcional: limpar dados antigos se necessário)
-- DELETE FROM site_idiomas_respostas_exercicios WHERE exercicio_id IN (
--     SELECT id FROM exercicios WHERE categoria = 'fala'
-- );

-- Confirmar as alterações
SELECT 'Categoria fala removida com sucesso!' as status;