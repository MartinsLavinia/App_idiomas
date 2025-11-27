<?php
session_start();
// Inclua o arquivo de conexão em POO
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Pega o idioma e o nível da URL
$idioma_quiz = $_GET['idioma'] ?? 'Ingles';
$nivel_determinado = $_GET['nivel'] ?? 'A1';

// Mapeamento dos níveis para uma ordem
$niveis = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

// Conteúdo detalhado para cada nível
$conteudo_niveis = [
    'A1' => [
        'titulo' => 'Nível A1: Básico',
        'descricao' => 'Conteúdos do Nível A1: Vocabulário básico, cumprimentos, verbo "to be".'
    ],
    'A2' => [
        'titulo' => 'Nível A2: Pré-Intermediário',
        'descricao' => 'Conteúdos do Nível A2: Presente simples, preposições de lugar, rotinas.'
    ],
    'B1' => [
        'titulo' => 'Nível B1: Intermediário',
        'descricao' => 'Conteúdos do Nível B1: Tempos verbais do passado, futuro, conversas sobre viagens.'
    ],
    'B2' => [
        'titulo' => 'Nível B2: Intermediário Avançado',
        'descricao' => 'Conteúdos do Nível B2: Compreensão de textos complexos e conversas fluentes.'
    ],
    'C1' => [
        'titulo' => 'Nível C1: Avançado',
        'descricao' => 'Conteúdos do Nível C1: Uso avançado da língua, expressões idiomáticas.'
    ],
    'C2' => [
        'titulo' => 'Nível C2: Proficiência',
        'descricao' => 'Conteúdos do Nível C2: Proficiência, fluência e precisão em qualquer contexto.'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="modal fade" id="resultadoModal" tabindex="-1" aria-labelledby="resultadoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultadoModalLabel">Seu Nível é: <span class="badge bg-success" id="nivelExibido"><?php echo htmlspecialchars($nivel_determinado); ?></span></h5>
                </div>
                <div class="modal-body">
                    <div id="secaoNivelDeterminado">
                        <h4 id="tituloNivel"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['titulo'] ?? 'Nível ' . $nivel_determinado); ?></h4>
                        <p id="conteudoNivel"><?php echo htmlspecialchars($conteudo_niveis[$nivel_determinado]['descricao'] ?? ''); ?></p>
                        <hr>
                        <p>Você gostaria de ficar neste nível?</p>
                        <button type="button" class="btn btn-success me-2" id="btnSimNivel" onclick="confirmarNivelFinal('<?php echo htmlspecialchars($nivel_determinado); ?>')">Sim</button>
                        <button type="button" class="btn btn-warning" id="btnNao">Não</button>
                    </div>

                    <div id="secaoMudarNivel" style="display: none;">
                        <p>Você quer um nível mais fácil ou mais difícil?</p>
                        <button type="button" class="btn btn-outline-secondary me-2" id="btnNivelAbaixo">Nível Abaixo</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnNivelAcima">Nível Acima</button>
                        <div id="opcoesNiveisDinamicas" class="mt-3"></div>
                        <button type="button" class="btn btn-outline-secondary mt-3" id="btnVoltar">Voltar</button>
                    </div>

                    <div id="secaoConfirmacao" style="display: none;">
                        <h4 id="tituloNivelConfirmar"></h4>
                        <p id="conteudoNivelConfirmar"></p>
                        <hr>
                        <p>Você tem certeza que quer este nível?</p>
                        <button type="button" class="btn btn-success me-2" id="btnConfirmarFinal">Sim, tenho certeza</button>
                        <button type="button" class="btn btn-danger" id="btnCancelarConfirmacao">Não, cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var resultadoModal = new bootstrap.Modal(document.getElementById('resultadoModal'));
            resultadoModal.show();

            const niveis = <?php echo json_encode($niveis); ?>;
            const conteudo_niveis = <?php echo json_encode($conteudo_niveis); ?>;
            const nivelAtualIndex = niveis.indexOf('<?php echo htmlspecialchars($nivel_determinado); ?>');
            let nivelSelecionado = '<?php echo htmlspecialchars($nivel_determinado); ?>';

            // Mapeamento dos elementos do DOM
            const secaoNivelDeterminado = document.getElementById('secaoNivelDeterminado');
            const secaoMudarNivel = document.getElementById('secaoMudarNivel');
            const secaoConfirmacao = document.getElementById('secaoConfirmacao');
            const opcoesNiveisDinamicas = document.getElementById('opcoesNiveisDinamicas');
            const btnNao = document.getElementById('btnNao');
            const btnNivelAbaixo = document.getElementById('btnNivelAbaixo');
            const btnNivelAcima = document.getElementById('btnNivelAcima');
            const btnVoltar = document.getElementById('btnVoltar');
            const btnConfirmarFinal = document.getElementById('btnConfirmarFinal');
            const btnCancelarConfirmacao = document.getElementById('btnCancelarConfirmacao');

            // Eventos de clique
            btnNao.addEventListener('click', () => {
                secaoNivelDeterminado.style.display = 'none';
                secaoMudarNivel.style.display = 'block';
            });
            btnVoltar.addEventListener('click', () => {
                secaoMudarNivel.style.display = 'none';
                secaoNivelDeterminado.style.display = 'block';
            });
            
            // Lógica para mostrar níveis abaixo (não mostra se já for A1)
            btnNivelAbaixo.addEventListener('click', () => {
                let opcoesHtml = '';
                for (let i = 0; i < nivelAtualIndex; i++) {
                    const nivel = niveis[i];
                    opcoesHtml += `<button type="button" class="btn btn-info me-2 mt-2" onclick="mostrarConfirmacao('${nivel}')">${conteudo_niveis[nivel].titulo}</button>`;
                }
                opcoesNiveisDinamicas.innerHTML = opcoesHtml || '<p>Não há níveis mais baixos.</p>';
            });

            // Lógica para mostrar níveis acima (não mostra se já for C2)
            btnNivelAcima.addEventListener('click', () => {
                let opcoesHtml = '';
                for (let i = nivelAtualIndex + 1; i < niveis.length; i++) {
                    const nivel = niveis[i];
                    opcoesHtml += `<button type="button" class="btn btn-info me-2 mt-2" onclick="mostrarConfirmacao('${nivel}')">${conteudo_niveis[nivel].titulo}</button>`;
                }
                opcoesNiveisDinamicas.innerHTML = opcoesHtml || '<p>Não há níveis mais avançados.</p>';
            });

            btnConfirmarFinal.addEventListener('click', () => {
                confirmarNivelFinal(nivelSelecionado);
            });
            btnCancelarConfirmacao.addEventListener('click', () => {
                secaoConfirmacao.style.display = 'none';
                secaoMudarNivel.style.display = 'block';
            });

            window.mostrarConfirmacao = function(nivel) {
                nivelSelecionado = nivel;
                document.getElementById('tituloNivelConfirmar').innerText = conteudo_niveis[nivel].titulo;
                document.getElementById('conteudoNivelConfirmar').innerText = conteudo_niveis[nivel].descricao;
                secaoMudarNivel.style.display = 'none';
                secaoConfirmacao.style.display = 'block';
            };
            
            window.confirmarNivelFinal = function(nivel) {
                window.location.href = `public/views/painel.php?idioma=<?php echo htmlspecialchars($idioma_quiz); ?>&nivel_escolhido=${nivel}`;
            };
        });
    </script>
</body>
</html>