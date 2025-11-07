<?php
/**
 * Controller principal para exercícios
 * Implementa padrão MVC e SOLID
 */

namespace App\Controllers;

use App\Services\ExercicioService;
use App\Services\AudioService;
use App\Services\ProgressoService;

class ExercicioController
{
    private $exercicioService;
    private $audioService;
    private $progressoService;

    public function __construct()
    {
        $this->exercicioService = new ExercicioService();
        $this->audioService = new AudioService();
        $this->progressoService = new ProgressoService();
    }

    /**
     * Busca exercício por ID com validação de tipo
     */
    public function buscarExercicio(): void
    {
        try {
            $exercicioId = $this->validarParametro('exercicio_id', 'int');
            $usuarioId = $this->obterUsuarioLogado();

            $exercicio = $this->exercicioService->buscarPorId($exercicioId);
            
            if (!$exercicio) {
                $this->responderErro('Exercício não encontrado', 404);
                return;
            }

            // Buscar progresso do usuário
            $progresso = $this->progressoService->buscarProgresso($usuarioId, $exercicioId);

            $this->responderSucesso([
                'exercicio' => $exercicio->toArray(),
                'progresso' => $progresso
            ]);

        } catch (Exception $e) {
            $this->responderErro('Erro ao buscar exercício: ' . $e->getMessage());
        }
    }

    /**
     * Processa resposta de exercício com validação de tipo
     */
    public function processarResposta(): void
    {
        try {
            $dados = $this->obterDadosJson();
            $this->validarDadosResposta($dados);

            $exercicioId = $dados['exercicio_id'];
            $respostaUsuario = $dados['resposta'];
            $tipoExercicio = $dados['tipo_exercicio'] ?? null;
            $usuarioId = $this->obterUsuarioLogado();

            // Buscar exercício
            $exercicio = $this->exercicioService->buscarPorId($exercicioId);
            if (!$exercicio) {
                $this->responderErro('Exercício não encontrado', 404);
                return;
            }

            // Processar resposta baseado no tipo
            $resultado = $exercicio->processarResposta($respostaUsuario);

            // Registrar resposta e progresso
            $this->exercicioService->registrarResposta($usuarioId, $exercicioId, $resultado);
            $this->progressoService->atualizarProgresso($usuarioId, $exercicioId, $resultado);

            $this->responderSucesso($resultado);

        } catch (Exception $e) {
            $this->responderErro('Erro ao processar resposta: ' . $e->getMessage());
        }
    }

    /**
     * Processa exercício de listening com áudio
     */
    public function processarListening(): void
    {
        try {
            $dados = $this->obterDadosJson();
            $exercicioId = $dados['exercicio_id'];
            $respostaUsuario = $dados['resposta'];
            $usuarioId = $this->obterUsuarioLogado();

            $exercicio = $this->exercicioService->buscarListeningPorId($exercicioId);
            if (!$exercicio) {
                $this->responderErro('Exercício de listening não encontrado', 404);
                return;
            }

            // Gerar áudio se necessário
            if (empty($exercicio->getAudioUrl())) {
                $audioUrl = $this->audioService->gerarAudio(
                    $exercicio->getFraseOriginal(),
                    $exercicio->getIdioma()
                );
                $exercicio->setAudioUrl($audioUrl);
                $this->exercicioService->atualizarAudioUrl($exercicioId, $audioUrl);
            }

            $resultado = $exercicio->processarResposta($respostaUsuario);

            // Registrar resposta
            $this->exercicioService->registrarResposta($usuarioId, $exercicioId, $resultado);
            $this->progressoService->atualizarProgresso($usuarioId, $exercicioId, $resultado);

            $this->responderSucesso($resultado);

        } catch (Exception $e) {
            $this->responderErro('Erro ao processar listening: ' . $e->getMessage());
        }
    }

    /**
     * Processa exercício de fala com transcrição
     */
    public function processarFala(): void
    {
        try {
            $dados = $this->obterDadosJson();
            $exercicioId = $dados['exercicio_id'];
            $fraseTranscrita = $dados['frase_transcrita'];
            $usuarioId = $this->obterUsuarioLogado();

            $exercicio = $this->exercicioService->buscarFalaPorId($exercicioId);
            if (!$exercicio) {
                $this->responderErro('Exercício de fala não encontrado', 404);
                return;
            }

            $resultado = $exercicio->processarResposta($fraseTranscrita);

            // Registrar resposta
            $this->exercicioService->registrarResposta($usuarioId, $exercicioId, $resultado);
            $this->progressoService->atualizarProgresso($usuarioId, $exercicioId, $resultado);

            $this->responderSucesso($resultado);

        } catch (Exception $e) {
            $this->responderErro('Erro ao processar fala: ' . $e->getMessage());
        }
    }

    /**
     * Gera áudio para exercício
     */
    public function gerarAudio(): void
    {
        try {
            $dados = $this->obterDadosJson();
            $texto = $dados['texto'] ?? '';
            $idioma = $dados['idioma'] ?? 'en-us';

            if (empty($texto)) {
                $this->responderErro('Texto é obrigatório');
                return;
            }

            $audioUrl = $this->audioService->gerarAudio($texto, $idioma);

            $this->responderSucesso([
                'audio_url' => $audioUrl,
                'texto' => $texto,
                'idioma' => $idioma
            ]);

        } catch (Exception $e) {
            $this->responderErro('Erro ao gerar áudio: ' . $e->getMessage());
        }
    }

    // Métodos auxiliares
    private function validarParametro(string $nome, string $tipo = 'string')
    {
        $valor = $_GET[$nome] ?? $_POST[$nome] ?? null;
        
        if ($valor === null) {
            throw new InvalidArgumentException("Parâmetro '$nome' é obrigatório");
        }

        switch ($tipo) {
            case 'int':
                if (!is_numeric($valor)) {
                    throw new InvalidArgumentException("Parâmetro '$nome' deve ser um número");
                }
                return intval($valor);
            case 'string':
                return trim($valor);
            default:
                return $valor;
        }
    }

    private function obterDadosJson(): array
    {
        $input = file_get_contents('php://input');
        $dados = json_decode($input, true);
        
        if (!$dados) {
            throw new InvalidArgumentException('Dados JSON inválidos');
        }
        
        return $dados;
    }

    private function validarDadosResposta(array $dados): void
    {
        $camposObrigatorios = ['exercicio_id', 'resposta'];
        
        foreach ($camposObrigatorios as $campo) {
            if (!isset($dados[$campo])) {
                throw new InvalidArgumentException("Campo '$campo' é obrigatório");
            }
        }
    }

    private function obterUsuarioLogado(): int
    {
        session_start();
        
        if (!isset($_SESSION['id_usuario'])) {
            throw new UnauthorizedException('Usuário não autenticado');
        }
        
        return intval($_SESSION['id_usuario']);
    }

    private function responderSucesso(array $dados): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $dados
        ]);
    }

    private function responderErro(string $mensagem, int $codigo = 400): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $mensagem
        ]);
    }
}

// Exceções customizadas
class UnauthorizedException extends Exception {}
class InvalidArgumentException extends Exception {}