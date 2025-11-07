<?php
/**
 * Endpoint principal para processamento de exercícios
 * Corrige todos os problemas identificados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Autoload das classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\ExercicioController;

try {
    // Verificar autenticação
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Usuário não autenticado', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido', 405);
    }

    // Obter dados da requisição
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);

    if (!$dados) {
        throw new Exception('Dados JSON inválidos', 400);
    }

    // Validar dados obrigatórios
    if (!isset($dados['exercicio_id']) || !isset($dados['resposta'])) {
        throw new Exception('Campos obrigatórios: exercicio_id, resposta', 400);
    }

    $controller = new ExercicioController();

    // Determinar tipo de processamento baseado nos dados
    if (isset($dados['tipo_exercicio'])) {
        switch ($dados['tipo_exercicio']) {
            case 'listening':
            case 'audicao':
                $controller->processarListening();
                break;
            case 'fala':
            case 'speech':
                $controller->processarFala();
                break;
            default:
                $controller->processarResposta();
                break;
        }
    } else {
        // Processamento genérico
        $controller->processarResposta();
    }

} catch (Exception $e) {
    $codigo = $e->getCode() ?: 500;
    http_response_code($codigo);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $codigo
    ]);
}
?>