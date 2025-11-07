-- =====================================================
-- CORREÇÕES DA ESTRUTURA DO BANCO DE DADOS
-- =====================================================

-- 1. Criar tabela padronizada para exercícios de listening
CREATE TABLE IF NOT EXISTS `exercicios_listening` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bloco_id` int NOT NULL,
  `frase_original` text NOT NULL COMMENT 'Frase que será falada no áudio',
  `audio_url` varchar(500) DEFAULT NULL COMMENT 'Caminho para o arquivo de áudio',
  `opcoes` json NOT NULL COMMENT 'Array com as opções de resposta',
  `resposta_correta` int NOT NULL COMMENT 'Índice da resposta correta (0-based)',
  `explicacao` text DEFAULT NULL COMMENT 'Explicação detalhada da resposta',
  `dicas_compreensao` text DEFAULT NULL COMMENT 'Dicas para melhorar compreensão oral',
  `transcricao` text DEFAULT NULL COMMENT 'Transcrição completa do áudio',
  `idioma` varchar(10) NOT NULL DEFAULT 'en-us',
  `nivel` enum('facil','medio','dificil') DEFAULT 'medio',
  `ordem` int NOT NULL DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bloco_id` (`bloco_id`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_idioma` (`idioma`),
  CONSTRAINT `fk_exercicios_listening_bloco` FOREIGN KEY (`bloco_id`) REFERENCES `blocos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Criar tabela padronizada para exercícios de fala
CREATE TABLE IF NOT EXISTS `exercicios_fala` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bloco_id` int NOT NULL,
  `frase_esperada` text NOT NULL COMMENT 'Frase que o usuário deve falar',
  `frase_exemplo_audio` varchar(500) DEFAULT NULL COMMENT 'Áudio exemplo da pronúncia correta',
  `dicas_pronuncia` text DEFAULT NULL COMMENT 'Dicas específicas de pronúncia',
  `palavras_chave` json DEFAULT NULL COMMENT 'Palavras importantes para focar',
  `contexto` text DEFAULT NULL COMMENT 'Contexto da frase (situação de uso)',
  `idioma` varchar(10) NOT NULL DEFAULT 'en-us',
  `nivel` enum('facil','medio','dificil') DEFAULT 'medio',
  `ordem` int NOT NULL DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bloco_id` (`bloco_id`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_idioma` (`idioma`),
  CONSTRAINT `fk_exercicios_fala_bloco` FOREIGN KEY (`bloco_id`) REFERENCES `blocos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Padronizar tabela de respostas
CREATE TABLE IF NOT EXISTS `respostas_exercicios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `exercicio_id` int NOT NULL,
  `tipo_exercicio` enum('multipla_escolha','listening','fala','texto_livre','arrastar_soltar','completar') NOT NULL,
  `resposta_usuario` text NOT NULL,
  `resposta_transcrita` text DEFAULT NULL COMMENT 'Para exercícios de fala',
  `acertou` tinyint(1) NOT NULL DEFAULT 0,
  `pontuacao` int DEFAULT 0 COMMENT 'Pontuação de 0 a 100',
  `tempo_resposta` int DEFAULT NULL COMMENT 'Tempo em segundos',
  `tentativas` int DEFAULT 1,
  `feedback_detalhado` json DEFAULT NULL COMMENT 'Feedback específico do exercício',
  `data_resposta` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_exercicio` (`id_usuario`, `exercicio_id`),
  KEY `idx_tipo_exercicio` (`tipo_exercicio`),
  KEY `idx_data_resposta` (`data_resposta`),
  CONSTRAINT `fk_respostas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Atualizar tabela de exercícios existente
ALTER TABLE `exercicios` 
ADD COLUMN IF NOT EXISTS `categoria` enum('gramatica','fala','escrita','leitura','audicao','listening') DEFAULT 'gramatica',
ADD COLUMN IF NOT EXISTS `metadata` json DEFAULT NULL COMMENT 'Dados específicos do tipo de exercício',
ADD COLUMN IF NOT EXISTS `configuracao` json DEFAULT NULL COMMENT 'Configurações de correção e feedback';

-- 5. Criar índices para melhor performance
CREATE INDEX IF NOT EXISTS `idx_exercicios_categoria` ON `exercicios` (`categoria`);
CREATE INDEX IF NOT EXISTS `idx_exercicios_tipo` ON `exercicios` (`tipo`);
CREATE INDEX IF NOT EXISTS `idx_exercicios_bloco` ON `exercicios` (`bloco_id`);

-- 6. Criar tabela de progresso detalhado
CREATE TABLE IF NOT EXISTS `progresso_detalhado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `exercicio_id` int NOT NULL,
  `tipo_exercicio` varchar(50) NOT NULL,
  `status` enum('nao_iniciado','em_progresso','concluido','revisao') DEFAULT 'nao_iniciado',
  `pontuacao_maxima` int DEFAULT 0,
  `pontuacao_atual` int DEFAULT 0,
  `tentativas_total` int DEFAULT 0,
  `tempo_total` int DEFAULT 0 COMMENT 'Tempo total em segundos',
  `ultima_tentativa` timestamp NULL DEFAULT NULL,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_exercicio` (`id_usuario`, `exercicio_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tipo_exercicio` (`tipo_exercicio`),
  CONSTRAINT `fk_progresso_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Inserir dados de exemplo para teste
INSERT IGNORE INTO `exercicios_listening` 
(`bloco_id`, `frase_original`, `opcoes`, `resposta_correta`, `explicacao`, `dicas_compreensao`, `transcricao`, `idioma`, `nivel`, `ordem`) 
VALUES 
(1, 'Good morning, how are you today?', 
 '["Good morning", "Good afternoon", "Good evening", "Good night"]', 
 0, 
 'A saudação "Good morning" é usada pela manhã, tipicamente até as 12h.',
 'Preste atenção na entonação da pergunta "how are you?" que indica interesse genuíno.',
 'Good morning, how are you today?',
 'en-us', 'facil', 1),

(1, 'I would like to book a table for two people, please.',
 '["Book a table", "Book a room", "Book a flight", "Book a taxi"]',
 0,
 'Em restaurantes, usamos "book a table" para reservar uma mesa.',
 'A palavra "book" como verbo significa "reservar". Foque na diferença entre table/room/flight.',
 'I would like to book a table for two people, please.',
 'en-us', 'medio', 2);

INSERT IGNORE INTO `exercicios_fala`
(`bloco_id`, `frase_esperada`, `dicas_pronuncia`, `palavras_chave`, `contexto`, `idioma`, `nivel`, `ordem`)
VALUES
(1, 'Hello, how are you today?', 
 'Pronuncie o "H" de "Hello" com aspiração. O "how" deve soar como "háu".',
 '["Hello", "how", "are", "you", "today"]',
 'Saudação informal usada em encontros casuais.',
 'en-us', 'facil', 1),

(1, 'I am fine, thank you very much.',
 'O "th" de "thank" deve ser pronunciado com a língua entre os dentes.',
 '["fine", "thank", "you", "very", "much"]',
 'Resposta padrão para "How are you?"',
 'en-us', 'facil', 2);