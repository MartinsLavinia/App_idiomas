-- Adicionar coluna tipo_exercicio na tabela exercicios se não existir
ALTER TABLE exercicios ADD COLUMN IF NOT EXISTS tipo_exercicio VARCHAR(50) DEFAULT 'multipla_escolha';

-- Atualizar exercícios existentes baseado no tipo atual
UPDATE exercicios SET tipo_exercicio = 'multipla_escolha' WHERE tipo = 'normal' AND tipo_exercicio IS NULL;
UPDATE exercicios SET tipo_exercicio = 'cena_final' WHERE tipo = 'especial' AND tipo_exercicio IS NULL;
UPDATE exercicios SET tipo_exercicio = 'multipla_escolha' WHERE tipo = 'quiz' AND tipo_exercicio IS NULL;