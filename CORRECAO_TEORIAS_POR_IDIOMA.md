# Correção: Teorias por Idioma

## Problema Identificado
As teorias estavam sendo exibidas sem filtro por idioma, mostrando teorias de inglês mesmo quando o usuário estava estudando japonês.

## Correções Aplicadas

### 1. Banco de Dados
Execute o script SQL `adicionar_campo_idioma_teorias.sql` no seu banco de dados MySQL:

```sql
-- Script para adicionar campo idioma na tabela teorias
USE site_idiomas;

-- Adicionar coluna idioma na tabela teorias
ALTER TABLE teorias ADD COLUMN idioma VARCHAR(50) NOT NULL DEFAULT 'Ingles' AFTER nivel;

-- Criar índice para melhor performance
CREATE INDEX idx_teorias_idioma_nivel ON teorias(idioma, nivel);

-- Atualizar teorias existentes (se houver) para ter um idioma padrão
UPDATE teorias SET idioma = 'Ingles' WHERE idioma = 'Ingles';

-- Verificar a estrutura da tabela
DESCRIBE teorias;
```

### 2. Arquivos Modificados

#### `public/controller/get_teorias.php`
- Adicionado filtro por idioma além do nível
- Busca o idioma atual do usuário na sessão ou no progresso

#### `public/controller/get_teoria_conteudo.php`
- Adicionado filtro por idioma ao buscar conteúdo específico da teoria
- Verifica se a teoria pertence ao idioma atual do usuário

#### `public/controller/get_teoria_bloco.php`
- Adicionado filtro por idioma ao buscar teorias relacionadas a blocos
- Considera o idioma do caminho de aprendizagem

## Como Testar

1. Execute o script SQL no seu banco de dados
2. Adicione teorias específicas para cada idioma no painel administrativo
3. Troque de idioma no painel do usuário
4. Verifique se as teorias exibidas correspondem ao idioma selecionado

## Estrutura da Tabela Teorias (Após Correção)

```sql
CREATE TABLE `teorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `nivel` varchar(10) NOT NULL,
  `idioma` varchar(50) NOT NULL DEFAULT 'Ingles',
  `ordem` int NOT NULL,
  `conteudo` text NOT NULL,
  `resumo` text,
  `palavras_chave` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teorias_idioma_nivel` (`idioma`,`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

## Observações

- As teorias existentes receberão o idioma padrão "Ingles"
- Você precisará criar teorias específicas para cada idioma no painel administrativo
- O sistema agora filtra automaticamente as teorias baseado no idioma atual do usuário
- Se não houver teorias para um idioma específico, será exibida uma mensagem informativa

## Próximos Passos

1. Execute o script SQL
2. Acesse o painel administrativo
3. Crie teorias específicas para cada idioma (Japones, Espanhol, etc.)
4. Teste a funcionalidade trocando de idioma no painel do usuário