-- Criação da tabela atividades que está faltando no banco de dados
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

-- Inserir atividades básicas para as unidades existentes
INSERT INTO `atividades` (`unidade_id`, `nome`, `descricao`, `icone`, `tipo`, `ordem`, `explicacao_teorica`) VALUES
-- Inglês A1 - Unidade 1: Cumprimentos e Apresentações
(1, 'Vocabulário Básico', 'Aprenda palavras essenciais para cumprimentos', 'fa-book', 'vocabulario', 1, 'Nesta seção você aprenderá as palavras mais importantes para se apresentar em inglês.'),
(1, 'Conversação', 'Pratique diálogos de apresentação', 'fa-comments', 'conversacao', 2, 'Pratique conversas reais de apresentação e cumprimentos.'),
(1, 'Pronúncia', 'Melhore sua pronúncia dos cumprimentos', 'fa-microphone', 'pronuncia', 3, 'Aprenda a pronunciar corretamente as saudações em inglês.'),

-- Inglês A1 - Unidade 2: O Verbo To Be
(2, 'Gramática', 'Estude as formas do verbo to be', 'fa-graduation-cap', 'gramatica', 1, 'O verbo to be é fundamental no inglês. Aprenda suas formas e usos.'),
(2, 'Exercícios Práticos', 'Pratique o uso do verbo to be', 'fa-pen', 'escrita', 2, 'Exercite o uso correto do verbo to be em diferentes contextos.'),
(2, 'Conversação', 'Use o verbo to be em conversas', 'fa-comments', 'conversacao', 3, 'Aplique o verbo to be em situações reais de conversação.'),

-- Inglês A2 - Unidade 1: Vocabulário de Viagem
(3, 'Vocabulário de Viagem', 'Palavras essenciais para viagens', 'fa-plane', 'vocabulario', 1, 'Aprenda o vocabulário necessário para viajar com confiança.'),
(3, 'Situações Práticas', 'Pratique situações de viagem', 'fa-map', 'conversacao', 2, 'Simule situações reais que você encontrará em viagens.'),

-- Inglês A2 - Unidade 2: Rotina Diária
(4, 'Vocabulário da Rotina', 'Palavras sobre atividades diárias', 'fa-clock', 'vocabulario', 1, 'Aprenda a falar sobre sua rotina diária em inglês.'),
(4, 'Presente Simples', 'Gramática do presente simples', 'fa-graduation-cap', 'gramatica', 2, 'Domine o presente simples para falar sobre rotinas.'),

-- Inglês B1 - Unidade 1: Passado Simples
(5, 'Gramática do Passado', 'Formação do passado simples', 'fa-graduation-cap', 'gramatica', 1, 'Aprenda a formar e usar o passado simples em inglês.'),
(5, 'Narrativas', 'Conte histórias no passado', 'fa-book-open', 'escrita', 2, 'Pratique contar histórias usando o passado simples.'),

-- Inglês B1 - Unidade 2: Verbos Irregulares
(6, 'Lista de Verbos', 'Memorize verbos irregulares', 'fa-list', 'vocabulario', 1, 'Estude os principais verbos irregulares do inglês.'),
(6, 'Exercícios Práticos', 'Pratique verbos irregulares', 'fa-pen', 'escrita', 2, 'Exercite o uso correto dos verbos irregulares.'),

-- Japonês A1 - Unidade 1: Hiragana e Cumprimentos
(7, 'Alfabeto Hiragana', 'Aprenda a escrever Hiragana', 'fa-language', 'escrita', 1, 'Domine o alfabeto Hiragana, base da escrita japonesa.'),
(7, 'Cumprimentos Básicos', 'Saudações em japonês', 'fa-bow', 'conversacao', 2, 'Aprenda as saudações essenciais em japonês.'),

-- Japonês A1 - Unidade 2: Introdução ao Katakana
(8, 'Alfabeto Katakana', 'Aprenda a escrever Katakana', 'fa-language', 'escrita', 1, 'Aprenda o Katakana para ler palavras estrangeiras.'),
(8, 'Palavras Estrangeiras', 'Vocabulário em Katakana', 'fa-globe', 'vocabulario', 2, 'Pratique palavras estrangeiras escritas em Katakana.'),

-- Japonês A2 - Unidade 1: Kanji Básico
(9, 'Primeiros Kanji', 'Aprenda Kanji básicos', 'fa-yin-yang', 'escrita', 1, 'Introdução aos primeiros caracteres Kanji.'),
(9, 'Leitura de Kanji', 'Pratique a leitura', 'fa-eye', 'audicao', 2, 'Aprenda as diferentes leituras dos Kanji.'),

-- Japonês A2 - Unidade 2: Comidas e Restaurantes
(10, 'Vocabulário de Comida', 'Nomes de pratos japoneses', 'fa-utensils', 'vocabulario', 1, 'Aprenda os nomes dos principais pratos japoneses.'),
(10, 'No Restaurante', 'Conversação em restaurantes', 'fa-comments', 'conversacao', 2, 'Pratique como pedir comida em japonês.');

-- Também precisamos criar a tabela progresso_detalhado se não existir
CREATE TABLE IF NOT EXISTS `progresso_detalhado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `exercicio_id` int NOT NULL,
  `concluido` tinyint(1) DEFAULT 0,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `exercicio_id` (`exercicio_id`),
  CONSTRAINT `progresso_detalhado_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `progresso_detalhado_ibfk_2` FOREIGN KEY (`exercicio_id`) REFERENCES `exercicios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Atualizar a tabela exercicios para incluir atividade_id
ALTER TABLE `exercicios` ADD COLUMN `atividade_id` int DEFAULT NULL;
ALTER TABLE `exercicios` ADD COLUMN `tipo_exercicio` varchar(50) DEFAULT 'multipla_escolha';
ALTER TABLE `exercicios` ADD KEY `atividade_id` (`atividade_id`);
-- Não vamos adicionar a constraint foreign key ainda pois pode dar erro se já existir

-- Inserir alguns exercícios de exemplo para as atividades
INSERT INTO `exercicios` (`atividade_id`, `caminho_id`, `ordem`, `tipo`, `pergunta`, `conteudo`, `tipo_exercicio`) VALUES
-- Exercícios para Vocabulário Básico (atividade 1)
(1, 1, 1, 'normal', 'Como se diz "Olá" em inglês?', '{"alternativas": [{"id": "1", "texto": "Hello", "correta": true}, {"id": "2", "texto": "Goodbye", "correta": false}, {"id": "3", "texto": "Thank you", "correta": false}, {"id": "4", "texto": "Please", "correta": false}]}', 'multipla_escolha'),
(1, 1, 2, 'normal', 'Qual é a tradução de "Good morning"?', '{"alternativas": [{"id": "1", "texto": "Boa tarde", "correta": false}, {"id": "2", "texto": "Boa noite", "correta": false}, {"id": "3", "texto": "Bom dia", "correta": true}, {"id": "4", "texto": "Até logo", "correta": false}]}', 'multipla_escolha'),

-- Exercícios para Conversação (atividade 2)
(2, 1, 1, 'normal', 'Complete o diálogo: "Hi, my name is John. What\'s _____ name?"', '{"alternativas": [{"id": "1", "texto": "my", "correta": false}, {"id": "2", "texto": "your", "correta": true}, {"id": "3", "texto": "his", "correta": false}, {"id": "4", "texto": "her", "correta": false}]}', 'multipla_escolha'),

-- Exercícios para Gramática do verbo to be (atividade 4)
(4, 2, 1, 'normal', 'Complete: "I _____ a student."', '{"alternativas": [{"id": "1", "texto": "am", "correta": true}, {"id": "2", "texto": "is", "correta": false}, {"id": "3", "texto": "are", "correta": false}, {"id": "4", "texto": "be", "correta": false}]}', 'multipla_escolha'),
(4, 2, 2, 'normal', 'Complete: "She _____ very nice."', '{"alternativas": [{"id": "1", "texto": "am", "correta": false}, {"id": "2", "texto": "is", "correta": true}, {"id": "3", "texto": "are", "correta": false}, {"id": "4", "texto": "be", "correta": false}]}', 'multipla_escolha');