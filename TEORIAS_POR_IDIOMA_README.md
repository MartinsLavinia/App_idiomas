# Implementação: Teorias por Idioma e Caminho

## Resumo das Alterações

Este documento descreve as alterações implementadas para que as teorias apareçam apenas para o idioma específico e caminho de aprendizagem em que foram registradas.

## Arquivos Modificados

### 1. Banco de Dados
- **Arquivo**: `update_teorias_idioma.sql` - Campo idioma
- **Arquivo**: `update_teorias_caminho.sql` - Campo caminho
- **Alterações**: 
  - Adicionado campo `idioma VARCHAR(50) NOT NULL DEFAULT 'Ingles'`
  - Adicionado campo `caminho_id INT NULL` com chave estrangeira
- **Índices**: 
  - `idx_teorias_idioma_nivel` para idioma e nível
  - `idx_teorias_caminho` para caminho
  - Chave estrangeira `fk_teorias_caminho`

### 2. Controladores Admin

#### `admin/controller/get_teoria.php`
- Adicionado parâmetro `idioma` na consulta
- Incluído campo `idioma` no SELECT
- Ordenação atualizada para `idioma, nivel, ordem`

#### `admin/views/adicionar_teoria.php`
- Adicionada busca de idiomas e caminhos disponíveis
- Incluído campo de seleção de idioma no formulário
- Incluído campo de seleção de caminho (opcional)
- JavaScript para filtrar caminhos por idioma
- Layout ajustado para 4 colunas (nível, idioma, caminho, ordem)

#### `admin/views/gerenciar_teorias.php`
- Adicionado filtro por idioma no cabeçalho
- Incluída coluna "Idioma" na tabela
- JavaScript para filtrar teorias por idioma
- Consulta atualizada para incluir campo idioma

#### `admin/views/editar_teoria.php`
- Adicionada busca de idiomas disponíveis
- Incluído campo de seleção de idioma no formulário
- Consulta de busca e atualização incluem campo idioma
- Layout ajustado para 3 colunas

### 3. Controladores Públicos

#### `public/controller/get_teorias.php`
- Filtro por idioma baseado na sessão do usuário
- **NOVO**: Filtro por caminho_id (parâmetro opcional)
- Busca teorias do caminho específico + teorias gerais (caminho_id NULL)
- Consulta filtra teorias por nível, idioma E caminho

#### `public/controller/get_teoria_bloco.php`
- Filtro por idioma do bloco/caminho
- **NOVO**: Prioriza teorias do caminho específico
- Consulta filtra teoria por nível, ordem, idioma E caminho
- Fallback para teorias gerais se não encontrar específica

## Funcionalidades Implementadas

### 1. Administração
- ✅ Campo idioma obrigatório ao criar teoria
- ✅ Campo caminho opcional ao criar teoria
- ✅ Filtro dinâmico de caminhos por idioma
- ✅ Campo idioma e caminho editáveis ao modificar teoria
- ✅ Filtro por idioma e caminho na listagem de teorias
- ✅ Exibição do idioma e caminho na tabela de teorias

### 2. Usuário Final
- ✅ Teorias filtradas por idioma do usuário
- ✅ Teorias filtradas por caminho específico + teorias gerais
- ✅ Teorias de bloco priorizadas por caminho
- ✅ Compatibilidade com sistema de progressão existente

## Como Executar

### 1. Atualizar Banco de Dados
```sql
-- Execute os arquivos SQL no seu banco
mysql -u seu_usuario -p devgom44_aims-sub1 < update_teorias_idioma.sql
mysql -u seu_usuario -p devgom44_aims-sub1 < update_teorias_caminho.sql
```

### 2. Verificar Funcionamento
1. Acesse o painel admin
2. Vá em "Gerenciar Teorias"
3. Teste o filtro por idioma e caminho
4. Crie uma nova teoria selecionando idioma e caminho
5. Verifique se aparece apenas para o idioma/caminho selecionado
6. Teste teorias gerais (sem caminho) aparecendo em todos os caminhos

## Compatibilidade

### Teorias Existentes
- Teorias sem idioma definido recebem valor padrão "Ingles"
- Sistema mantém compatibilidade com dados existentes
- Não há perda de dados ou funcionalidades

### Sistema de Usuários
- Mantém funcionamento com `$_SESSION['idioma_atual']`
- Fallback para progresso do usuário funciona normalmente
- Não afeta sistema de autenticação ou progressão

## Estrutura Final da Tabela `teorias`

```sql
CREATE TABLE `teorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `nivel` varchar(10) NOT NULL,
  `idioma` varchar(50) NOT NULL DEFAULT 'Ingles',
  `caminho_id` int DEFAULT NULL,
  `ordem` int NOT NULL,
  `conteudo` text NOT NULL,
  `resumo` text,
  `palavras_chave` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teorias_idioma_nivel` (`idioma`,`nivel`),
  KEY `idx_teorias_caminho` (`caminho_id`),
  CONSTRAINT `fk_teorias_caminho` FOREIGN KEY (`caminho_id`) REFERENCES `caminhos_aprendizagem` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

## Fluxo de Funcionamento

### Para Administradores
1. Admin cria teoria selecionando idioma específico
2. Admin pode associar teoria a um caminho específico (opcional)
3. Teorias sem caminho aparecem em todos os caminhos do idioma
4. Admin pode filtrar teorias por idioma e caminho na listagem
5. Admin pode editar idioma e caminho de teorias existentes

### Para Usuários
1. Sistema identifica idioma e caminho atual do usuário
2. Busca teorias do idioma + teorias do caminho específico
3. Teorias gerais (sem caminho) aparecem em todos os caminhos
4. Teorias de bloco priorizadas por caminho específico
5. Mantém progressão normal do aprendizado

## Benefícios

- ✅ **Organização**: Teorias organizadas por idioma e caminho
- ✅ **Flexibilidade**: Teorias gerais + teorias específicas por caminho
- ✅ **Escalabilidade**: Suporte a múltiplos idiomas e caminhos
- ✅ **Performance**: Índices otimizados para consultas
- ✅ **Usabilidade**: Interface clara com filtros dinâmicos
- ✅ **Manutenibilidade**: Código limpo e bem estruturado
- ✅ **Compatibilidade**: Mantém funcionamento com dados existentes