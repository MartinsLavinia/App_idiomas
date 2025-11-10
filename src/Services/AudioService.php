<?php

namespace App\Services;

class AudioService {
    
    public function gerarAudio($frase, $idioma = 'en-us') {
        // Return special URL that indicates client-side TTS should be used
        return 'tts://' . base64_encode(json_encode([
            'text' => $frase,
            'lang' => $idioma
        ]));
    }
}