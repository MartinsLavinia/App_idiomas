-- Script para adicionar teorias de exemplo
-- Execute no seu banco de dados para testar o sistema

-- Verificar se a coluna idioma existe na tabela teorias
ALTER TABLE teorias ADD COLUMN IF NOT EXISTS idioma VARCHAR(50) DEFAULT 'Inglês';

-- Inserir teorias de exemplo para Inglês - Nível A1
INSERT INTO teorias (titulo, nivel, ordem, conteudo, resumo, idioma) VALUES
('Verb "To Be" - Present Tense', 'A1', 1, 
'1. Affirmative Form
I am (I''m) - Eu sou/estou
You are (You''re) - Você é/está
He/She/It is (He''s/She''s/It''s) - Ele/Ela é/está
We are (We''re) - Nós somos/estamos
You are (You''re) - Vocês são/estão
They are (They''re) - Eles/Elas são/estão

2. Negative Form
I am not (I''m not) - Eu não sou/estou
You are not (You aren''t) - Você não é/está
He/She/It is not (He/She/It isn''t) - Ele/Ela não é/está
We are not (We aren''t) - Nós não somos/estamos
You are not (You aren''t) - Vocês não são/estão
They are not (They aren''t) - Eles/Elas não são/estão

3. Question Form
Am I? - Eu sou/estou?
Are you? - Você é/está?
Is he/she/it? - Ele/Ela é/está?
Are we? - Nós somos/estamos?
Are you? - Vocês são/estão?
Are they? - Eles/Elas são/estão?', 
'Aprenda as formas do verbo "to be" no presente: afirmativa, negativa e interrogativa.', 'ingles'),

('Personal Pronouns', 'A1', 2,
'1. Subject Pronouns
I - Eu
You - Você
He - Ele
She - Ela
It - Ele/Ela (para coisas e animais)
We - Nós
You - Vocês
They - Eles/Elas

2. Object Pronouns
Me - Me, mim
You - Te, ti, você
Him - O, ele
Her - A, ela
It - O, a (para coisas e animais)
Us - Nos, nós
You - Vos, vocês
Them - Os, as, eles, elas

3. Examples
I love you. - Eu te amo.
She knows him. - Ela o conhece.
We see them. - Nós os vemos.',
'Conheça os pronomes pessoais em inglês: sujeito e objeto.', 'ingles'),

('Articles: A, An, The', 'A1', 3,
'1. Indefinite Articles (A/An)
Use "a" before consonant sounds:
- a book, a car, a house

Use "an" before vowel sounds:
- an apple, an hour, an umbrella

2. Definite Article (The)
Use "the" for specific things:
- The book on the table
- The sun is bright
- The first day of school

3. No Article
Some cases don''t need articles:
- I like music (general)
- She speaks English (languages)
- We eat breakfast (meals)',
'Aprenda quando usar os artigos a, an e the em inglês.', 'ingles');

-- Inserir teorias para Português - Nível A1
INSERT INTO teorias (titulo, nivel, ordem, conteudo, resumo, idioma) VALUES
('Verbo Ser e Estar - Presente', 'A1', 1,
'1. Verbo SER - Características permanentes
Eu sou
Tu és / Você é
Ele/Ela é
Nós somos
Vós sois / Vocês são
Eles/Elas são

Exemplos:
- Eu sou brasileiro.
- Ela é professora.
- Nós somos estudantes.

2. Verbo ESTAR - Estados temporários
Eu estou
Tu estás / Você está
Ele/Ela está
Nós estamos
Vós estais / Vocês estão
Eles/Elas estão

Exemplos:
- Eu estou cansado.
- Ela está feliz.
- Nós estamos em casa.',
'Diferenças entre os verbos ser e estar no presente do indicativo.', 'portugues'),

('Artigos Definidos e Indefinidos', 'A1', 2,
'1. Artigos Definidos
o (masculino singular) - o livro
a (feminino singular) - a casa
os (masculino plural) - os livros
as (feminino plural) - as casas

2. Artigos Indefinidos
um (masculino singular) - um livro
uma (feminino singular) - uma casa
uns (masculino plural) - uns livros
umas (feminino plural) - umas casas

3. Uso dos Artigos
- Definidos: referem-se a algo específico
- Indefinidos: referem-se a algo não específico',
'Aprenda o uso correto dos artigos em português.', 'portugues');

-- Inserir teorias para Espanhol - Nível A1
INSERT INTO teorias (titulo, nivel, ordem, conteudo, resumo, idioma) VALUES
('Verbo Ser y Estar - Presente', 'A1', 1,
'1. Verbo SER - Características permanentes
Yo soy
Tú eres
Él/Ella es
Nosotros/as somos
Vosotros/as sois
Ellos/Ellas son

2. Verbo ESTAR - Estados temporales
Yo estoy
Tú estás
Él/Ella está
Nosotros/as estamos
Vosotros/as estáis
Ellos/Ellas están

3. Diferencias principales
SER: nacionalidad, profesión, características físicas
ESTAR: ubicación, estados de ánimo, condiciones temporales',
'Diferencias entre ser y estar en español.', 'espanhol'),

('Artículos Definidos e Indefinidos', 'A1', 2,
'1. Artículos Definidos
el (masculino singular)
la (feminino singular)
los (masculino plural)
las (feminino plural)

2. Artículos Indefinidos
un (masculino singular)
una (feminino singular)
unos (masculino plural)
unas (feminino plural)

3. Contracciones
del = de + el
al = a + el',
'Los artículos en español y sus contracciones.', 'espanhol');