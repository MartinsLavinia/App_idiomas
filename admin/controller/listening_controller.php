<?php

// listening_controller.php
class ListeningController {
    
    private $apiConfig = [
        'voicemaker' => [
            'url' => 'https://developer.voicemaker.in/voice/api',
            'key' => '5dd58e0377msh1c7c13f5e8a5c8ap1a1a1ajsn2b2b2b2b2b2b'
        ],
        'voicerss' => [
            'url' => 'https://api.voicerss.org/',
            'key' => 'ef95565896msh31ca5b246a8c2fbp14e4f0jsn9c937cd661a6' // Substitua pela sua chave VoiceRSS real se for usar
        ],
        'google_cloud_tts' => [
            'key_file_path' => __DIR__ . '/../../google-cloud-key.json' // Caminho para o arquivo JSON da chave de serviço
        ]
    ];
    
    public function gerarAudio($texto, $idioma = 'en-us', $velocidade = 0) {
        // Validar texto
        if (empty($texto)) {
            throw new Exception("Texto não pode estar vazio.");
        }
        
        // Limitar tamanho do texto
        if (strlen($texto) > 300) {
            throw new Exception("Texto muito longo. Máximo 300 caracteres.");
        }
        
        // Criar diretório de audios se não existir
        $audioDir = __DIR__ . '/../../audios';
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }
        
        $hash = md5($texto . $idioma . $velocidade);
        $caminhoAudio = $audioDir . "/{$hash}.mp3";
        // CORREÇÃO CRÍTICA: O caminho deve ser absoluto a partir da raiz do site.
        // Assumindo que seu projeto está em /App_idiomas/
        $caminhoRelativo = "/App_idiomas/audios/{$hash}.mp3";
        
        // Verificar se áudio já existe
        error_log("Verificando se o arquivo de áudio já existe em: " . $caminhoAudio);
        
        // Recria o áudio se ele não existe ou se o arquivo está corrompido (tamanho muito pequeno)
        if (!file_exists($caminhoAudio) || filesize($caminhoAudio) < 1024) {
        
            // TENTAR MÚLTIPLAS APIS
            $audioData = false;
            
            // Tentativa 1: Google Cloud TTS (mais robusta e recomendada)
           // $audioData = $this->googleCloudTTS($texto, $idioma);
        
            // Tentativa 2: Google TTS (fallback - não oficial e instável)
            error_log("Tentando Google TTS (fallback)...");
            if (!$audioData) {
                error_log("Texto para Google TTS: " . $texto);
                error_log("Idioma para Google TTS: " . $idioma);
        
        
                $audioData = $this->googleTTS($texto, $idioma);
            }
            
            if ($audioData) {
                // Salvar arquivo de áudio
                if (file_put_contents($caminhoAudio, $audioData) === false) {
                    throw new Exception("Erro ao salvar arquivo de áudio em: " . $caminhoAudio);
                }
                if (filesize($caminhoAudio) == 0) {
                    error_log("Arquivo de áudio criado com tamanho zero: " . $caminhoAudio);
                    throw new Exception("Falha ao gerar conteúdo do áudio. O arquivo está vazio.");
                 } else {
                       error_log("Tamanho do arquivo de áudio gerado: " . filesize($caminhoAudio) . " bytes");
                }
                
                // Verificar se o arquivo foi criado
                if (!file_exists($caminhoAudio)) {
                    throw new Exception("Falha ao criar arquivo de áudio");
                }
                 error_log("Áudio gerado e salvo com sucesso em: " . $caminhoAudio);
            } else {
                throw new Exception("Não foi possível gerar o áudio. A API de TTS falhou.");
            }
        } else {
        
            error_log("Arquivo de áudio já existe em: " . $caminhoAudio);
        }
        
        error_log("Retornando caminho relativo do áudio: " . $caminhoRelativo);
        error_log("Caminho absoluto do arquivo de áudio: " . realpath($caminhoAudio));
        return $caminhoRelativo;
    }

    private function googleCloudTTS($texto, $idioma) {
        try {
            /*
            // Verifica se o arquivo de chave de serviço existe
            $keyFilePath = $this->apiConfig["google_cloud_tts"]["key_file_path"];
            if (!file_exists($keyFilePath) || !is_readable($keyFilePath)) {
                error_log("Arquivo de chave do Google Cloud não encontrado ou não legível em: " . $keyFilePath);
                return false;
            }

            // Configura as credenciais do Google Cloud
            // O GOOGLE_APPLICATION_CREDENTIALS deve apontar para o arquivo JSON da chave de serviço.
            // É recomendado definir esta variável de ambiente no servidor ou usar o método `withCredentials` do cliente.
            // Para este exemplo, vamos definir via putenv, mas em produção, prefira a configuração do ambiente.
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyFilePath);

            $client = new \Google\Cloud\TextToSpeech\V1\TextToSpeechClient();

            $input = new \Google\Cloud\TextToSpeech\V1\SynthesisInput();
            $input->setText($texto);

            $voice = new \Google\Cloud\TextToSpeech\V1\VoiceSelectionParams();
            // Mapeamento de idiomas para vozes do Google Cloud TTS
            // Utilizando vozes WaveNet para maior qualidade, que consomem mais do Free Tier.
            // Pode-se usar vozes Standard (ex: 'en-US-Standard-D') para consumir menos do Free Tier.
            $voiceMap = [
                'en-us' => ['languageCode' => 'en-US', 'name' => 'en-US-Wavenet-D'],
                'en-gb' => ['languageCode' => 'en-GB', 'name' => 'en-GB-Wavenet-B'],
                'pt-br' => ['languageCode' => 'pt-BR', 'name' => 'pt-BR-Wavenet-B'],
                'es-es' => ['languageCode' => 'es-ES', 'name' => 'es-ES-Wavenet-B'],
                'fr-fr' => ['languageCode' => 'fr-FR', 'name' => 'fr-FR-Wavenet-B'],
                'de-de' => ['languageCode' => 'de-DE', 'name' => 'de-DE-Wavenet-B'],
                'ja-jp' => ['languageCode' => 'ja-JP', 'name' => 'ja-JP-Wavenet-B']
            ];

            $selectedVoice = $voiceMap[strtolower($idioma)] ?? $voiceMap['en-us'];
            $voice->setLanguageCode($selectedVoice['languageCode']);
            $voice->setName($selectedVoice['name']);
            $voice->setSsmlGender(\Google\Cloud\TextToSpeech\V1\SsmlVoiceGender::FEMALE); // Gênero feminino como padrão

            $audioConfig = new \Google\Cloud\TextToSpeech\V1\AudioConfig();
            $audioConfig->setAudioEncoding(\Google\Cloud\TextToSpeech\V1\AudioEncoding::MP3);
            $audioConfig->setSpeakingRate(1.0); // Velocidade normal (1.0)
            $audioConfig->setPitch(0.0); // Tom normal (0.0)

            $response = $client->synthesizeSpeech($input, $voice, $audioConfig);
            $audioContent = $response->getAudioContent();

            $client->close();

            if (!empty($audioContent)) {
                return $audioContent;
            }
            error_log("Google Cloud TTS falhou: Conteúdo de áudio vazio ou erro na API.");
            return false;

            */
           return false;
        } catch (\Exception $e) {
            error_log("Erro Google Cloud TTS: " . $e->getMessage());
            return false;
        }
    }

    private function voiceRSSAPI($texto, $idioma) {
        try {
            $params = [
                'key' => $this->apiConfig['voicerss']['key'],
                'hl' => $idioma,
                'src' => $texto,
                'c' => 'MP3',
                'f' => '44khz_16bit_stereo',
                'r' => 0
            ];
            
            $url = $this->apiConfig['voicerss']['url'] . '?' . http_build_query($params);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 20,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $audioData = @file_get_contents($url, false, $context);
            
            // Verificar se a resposta contém erro
            if ($audioData && strlen($audioData) > 100 && strpos($audioData, 'ERROR') === false) {
                return $audioData;
            }
            
            error_log("VoiceRSS API falhou: " . substr($audioData, 0, 100));
            return false;
            
        } catch (Exception $e) {
            error_log("Erro VoiceRSS: " . $e->getMessage());
            return false;
        }
    }
    
    private function googleTTS($texto, $idioma) {
        try {
            // Google TTS via API pública (limitações aplicam)
            // Este endpoint é não oficial e pode ser instável ou bloqueado a qualquer momento.
            $textoEncoded = urlencode($texto);
            $url = "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&q={$textoEncoded}&tl={$idioma}";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30, // Aumentar o timeout para 30 segundos
                    'ignore_errors' => true,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36\r\n" // User-Agent mais completo
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $audioData = @file_get_contents($url, false, $context);
            
            // Verificar se a resposta é um áudio válido (tamanho mínimo e não contém erro)
            // A API do Google costuma retornar HTML em caso de erro/bloqueio.
            if ($audioData && strlen($audioData) > 1024 && !str_starts_with(trim($audioData), '<!DOCTYPE html>')) {
                error_log("Google TTS retornou dados de áudio válidos. Tamanho: " . strlen($audioData));
                return $audioData;
            }
            error_log("Google TTS API falhou ou retornou dados inválidos (provavelmente HTML de erro). Tamanho: " . strlen($audioData));
            return false;
            
        } catch (Exception $e) {
            error_log("Erro Google TTS: " . $e->getMessage());
            return false;
        }
    }
    
    private function azureTTS($texto, $idioma) {
        try {
            // Azure Cognitive Services - versão simplificada
            // Nota: Requer chave de API real para funcionar plenamente
            $azureKey = getenv('AZURE_TTS_KEY');
            $azureRegion = 'eastus';
            
            if (!$azureKey) {
                return false; // Sem chave configurada
            }
            
            $url = "https://{$azureRegion}.tts.speech.microsoft.com/cognitiveservices/v1";
            
            $headers = [
                'Ocp-Apim-Subscription-Key: ' . $azureKey,
                'Content-Type: application/ssml+xml',
                'X-Microsoft-OutputFormat: audio-16khz-128kbitrate-mono-mp3',
                'User-Agent: curl'
            ];
            
            $ssml = $this->gerarSSML($texto, $idioma);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => $ssml,
                    'timeout' => 20,
                    'ignore_errors' => true
                ]
            ]);
            
            $audioData = @file_get_contents($url, false, $context);
            
            if ($audioData && strlen($audioData) > 1000) {
                return $audioData;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro Azure TTS: " . $e->getMessage());
            return false;
        }
    }

    private function gerarSSML($texto, $idioma) {
        $vozes = [
            'en-us' => 'en-US-AriaNeural',
            'en-gb' => 'en-GB-SoniaNeural', // Voz Neural para inglês britânico
            'es-es' => 'es-ES-ElviraNeural', // Voz Neural para espanhol
            'fr-fr' => 'fr-FR-DeniseNeural', // Voz Neural para francês
            'de-de' => 'de-DE-KatjaNeural', // Voz Neural para alemão
            'pt-br' => 'pt-BR-FranciscaNeural', // Voz Neural para português brasileiro
            'pt-pt' => 'pt-PT-RaquelNeural'
    ];
        
        $voz = $vozes[$idioma] ?? $vozes['en-us'];
        
        return '<?xml version="1.0" encoding="UTF-8"?>\n                <speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="' . $idioma . '">\n                    <voice name="' . $voz . '">\n                        ' . htmlspecialchars($texto) . '\n                    </voice>\n                </speak>';
    }
    
    private function gerarAudioPlaceholder($texto) {
        try {
            // Criar um áudio placeholder usando text-to-speech local se disponível
            // ou gerar um arquivo de áudio simples
            
            // Para desenvolvimento, podemos criar um arquivo MP3 vazio
            // Em produção, considere usar uma biblioteca como maryTTS local
            
            $placeholderAudio = __DIR__ . '/../../assets/placeholder-audio.mp3';
            
            if (file_exists($placeholderAudio)) {
                return file_get_contents($placeholderAudio);
            }
            
            // Se não existe placeholder, retorna false para usar modo dev
            return false;
            
        } catch (Exception $e) {
            error_log("Erro gerar placeholder: " . $e->getMessage());
            return false;
        }
    }
    
    private function gerarAudioFallback($texto) {
        $audioDir = __DIR__ . "/../../audios";
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }
        $hash = md5($texto . 'fallback');
        $caminhoAudio = $audioDir . "/{$hash}.mp3";
        file_put_contents($caminhoAudio, 'audio não encontrado'); // Cria um arquivo vazio
        return file_get_contents($caminhoAudio); // Retorna o conteúdo vazio
    }
    
    // MÉTODO ALTERNATIVO: Modo de desenvolvimento (sem API)
    public function gerarAudioDev($texto, $idioma = 'en-us') {
        try {
            // Modo desenvolvimento - usa serviço mais simples
            $audioDir = __DIR__ . '/../../audios';
            if (!is_dir($audioDir)) {
                mkdir($audioDir, 0777, true);
            }
            
            $hash = md5($texto . $idioma . 'dev');
            $caminhoAudio = $audioDir . "/{$hash}.mp3";
            $caminhoRelativo = "audios/{$hash}.mp3";
            
            // Se já existe, retorna
            if (file_exists($caminhoAudio)) {
                return $caminhoRelativo;
            }
            
            // Tenta Google TTS primeiro (mais simples)
            $audioData = $this->googleTTS($texto, $idioma);
            
            if (!$audioData) {
                // Cria arquivo vazio como fallback
                file_put_contents($caminhoAudio, '');
            } else {
                file_put_contents($caminhoAudio, $audioData);
            }
            
            return $caminhoRelativo;
            
        } catch (Exception $e) {
            error_log("Erro modo dev: " . $e->getMessage());
            
            // Fallback absoluto - retorna caminho mesmo sem arquivo
            $hash = md5($texto . $idioma . 'fallback');
            return "audios/{$hash}.mp3";
        }
    }
    
    public function processarRespostaLisstening($respostaUsuario, $conteudoExercicio) {
        if (!isset($conteudoExercicio['resposta_correta']) || !isset($conteudoExercicio['opcoes'])) {
            return [
                'correto' => false,
                'mensagem' => 'Exercício mal configurado'
            ];
        }
        
        $respostaCorretaIndex = $conteudoExercicio['resposta_correta'];
	        $respostaCorreta = $conteudoExercicio['opcoes'][$respostaCorretaIndex] ?? '';
	        
	        // CORREÇÃO: A resposta do usuário (string vinda do input) deve ser comparada com o índice correto (int).
	        // A conversão para int garante a comparação correta.
	        $correto = (intval($respostaUsuario) === intval($respostaCorretaIndex));
        
        return [
            'correto' => $correto,
            'resposta_correta' => $respostaCorreta,
            'resposta_correta_index' => $respostaCorretaIndex,
            'mensagem' => $correto ? 'Resposta correta! 🎉' : 'Resposta incorreta. Tente novamente.',
            'explicacao' => $correto ? 'Excelente! Você compreendeu o áudio perfeitamente.' : 'Ouça o áudio novamente com atenção.',
            'audio_url' => $conteudoExercicio['audio_url'] ?? '',
            'frase_original' => $conteudoExercicio['frase_original'] ?? ''
        ];
    }
    
    // Novo método: Verificar saúde dos serviços TTS
    public function verificarServicosTTS() {
        $servicos = [];
        
        // Teste VoiceRSS
        try {
            $testAudio = $this->voiceRSSAPI('test', 'en-us');
            $servicos['voicerss'] = (bool)$testAudio;
        } catch (Exception $e) {
            $servicos['voicerss'] = false;
        }
        
        // Teste Google TTS
        try {
            $testAudio = $this->googleTTS('test', 'en-us');
            $servicos['google_tts'] = (bool)$testAudio;
        } catch (Exception $e) {
            $servicos['google_tts'] = false;
        }
        
        return $servicos;
    }
}
