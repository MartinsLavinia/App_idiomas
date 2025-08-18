-- Execute este código no MySQL Workbench
-- Selecione o banco site_idiomas antes de executar

USE site_idiomas;

-- Criar tabela atividades
CREATE TABLE IF NOT EXISTS `atividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidade_id` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text,
  `icone` varchar(50) DEFAULT 'fa-graduation-cap',
  `tipo` varchar(50) DEFAULT 'geral',
  `ordem` int NOT NULL DEFAULT 1,
  `explicacao_teorica` text,
  PRIMARY KEY (`id`),
  KEY `unidade_id` (`unidade_id`),
  CONSTRAINT `atividades_ibfk_1` FOREIGN KEY (`unidade_id`) REFERENCES `unidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Criar tabela progresso_detalhado
CREATE TABLE IF NOT EXISTS `progresso_detalhado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `exercicio_id` int NOT NULL,
  `concluido` tinyint(1) DEFAULT 0,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `exercicio_id` (`exercicio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Adicionar colunas na tabela exercicios
ALTER TABLE `exercicios` ADD COLUMN IF NOT EXISTS `atividade_id` int DEFAULT NULL;
ALTER TABLE `exercicios` ADD COLUMN IF NOT EXISTS `tipo_exercicio` varchar(50) DEFAULT 'multipla_escolha';

-- Inserir atividades
INSERT INTO `atividades` (`unidade_id`, `nome`, `descricao`, `icone`, `tipo`, `ordem`) VALUES
(1, 'Vocabulário Básico', 'Aprenda palavras essenciais para cumprimentos', 'fa-book', 'vocabulario', 1),
(1, 'Conversação', 'Pratique diálogos de apresentação', 'fa-comments', 'conversacao', 2),
(2, 'Gramática', 'Estude as formas do verbo to be', 'fa-graduation-cap', 'gramatica', 1),
(2, 'Exercícios Práticos', 'Pratique o uso do verbo to be', 'fa-pen', 'escrita', 2),
(3, 'Vocabulário de Viagem', 'Palavras essenciais para viagens', 'fa-plane', 'vocabulario', 1),
(4, 'Vocabulário da Rotina', 'Palavras sobre atividades diárias', 'fa-clock', 'vocabulario', 1),
(5, 'Gramática do Passado', 'Formação do passado simples', 'fa-graduation-cap', 'gramatica', 1),
(6, 'Lista de Verbos', 'Memorize verbos irregulares', 'fa-list', 'vocabulario', 1),
(7, 'Alfabeto Hiragana', 'Aprenda a escrever Hiragana', 'fa-language', 'escrita', 1),
(8, 'Alfabeto Katakana', 'Aprenda a escrever Katakana', 'fa-language', 'escrita', 1),
(9, 'Primeiros Kanji', 'Aprenda Kanji básicos', 'fa-yin-yang', 'escrita', 1),
(10, 'Vocabulário de Comida', 'Nomes de pratos japoneses', 'fa-utensils', 'vocabulario', 1);

-- Inserir exercícios de exemplo
INSERT INTO `exercicios` (`atividade_id`, `caminho_id`, `ordem`, `tipo`, `pergunta`, `conteudo`, `tipo_exercicio`) VALUES
(1, 1, 1, 'normal', 'Como se diz "Olá" em inglês?', '{"alternativas": [{"id": "1", "texto": "Hello", "correta": true}, {"id": "2", "texto": "Goodbye", "correta": false}, {"id": "3", "texto": "Thank you", "correta": false}]}', 'multipla_escolha'),
(1, 1, 2, 'normal', 'Qual é a tradução de "Good morning"?', '{"alternativas": [{"id": "1", "texto": "Boa tarde", "correta": false}, {"id": "2", "texto": "Bom dia", "correta": true}, {"id": "3", "texto": "Boa noite", "correta": false}]}', 'multipla_escolha'),
(3, 2, 1, 'normal', 'Complete: "I _____ a student."', '{"alternativas": [{"id": "1", "texto": "am", "correta": true}, {"id": "2", "texto": "is", "correta": false}, {"id": "3", "texto": "are", "correta": false}]}', 'multipla_escolha');