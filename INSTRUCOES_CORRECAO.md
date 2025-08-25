# Instruções para Corrigir o Erro "Erro ao carregar atividades"

## Problema Identificado
O erro ocorre porque a tabela `atividades` não existe no banco de dados. O código está tentando buscar atividades nesta tabela, mas ela não foi criada.

## Solução

### Passo 1: Executar o Script SQL
1. Abra o phpMyAdmin (http://localhost/phpmyadmin)
2. Selecione o banco de dados `site_idiomas`
3. Vá na aba "SQL"
4. Copie e cole o conteúdo do arquivo `criar_tabela_atividades.sql`
5. Execute o script

### Passo 2: Verificar se funcionou
1. Faça login no sistema
2. Clique em uma unidade
3. As atividades devem aparecer agora

## O que foi corrigido

### 1. Tabela `atividades` criada
- Criada a tabela que estava faltando
- Adicionadas atividades de exemplo para todas as unidades existentes
- Cada unidade agora tem 2-3 atividades diferentes (vocabulário, gramática, conversação, etc.)

### 2. Tabela `progresso_detalhado` criada
- Necessária para rastrear o progresso dos exercícios
- Permite calcular a porcentagem de conclusão das atividades

### 3. Melhorias no código PHP
- Adicionada verificação se a tabela existe antes de fazer consultas
- Melhor tratamento de erros
- Fallback para os caminhos antigos se não houver atividades

### 4. Exercícios de exemplo adicionados
- Criados exercícios básicos para algumas atividades
- Formato correto de múltipla escolha
- Ligação correta entre atividades e exercícios

## Estrutura das Atividades Criadas

### Inglês A1
- **Unidade 1**: Vocabulário Básico, Conversação, Pronúncia
- **Unidade 2**: Gramática, Exercícios Práticos, Conversação

### Inglês A2
- **Unidade 1**: Vocabulário de Viagem, Situações Práticas
- **Unidade 2**: Vocabulário da Rotina, Presente Simples

### Inglês B1
- **Unidade 1**: Gramática do Passado, Narrativas
- **Unidade 2**: Lista de Verbos, Exercícios Práticos

### Japonês A1
- **Unidade 1**: Alfabeto Hiragana, Cumprimentos Básicos
- **Unidade 2**: Alfabeto Katakana, Palavras Estrangeiras

### Japonês A2
- **Unidade 1**: Primeiros Kanji, Leitura de Kanji
- **Unidade 2**: Vocabulário de Comida, No Restaurante

## Próximos Passos (Opcional)
1. Adicionar mais exercícios para cada atividade
2. Criar conteúdo teórico para as explicações
3. Implementar sistema de progresso mais detalhado
4. Adicionar exercícios de fala e escrita