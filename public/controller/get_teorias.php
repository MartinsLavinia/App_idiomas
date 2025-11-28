<?php
header('Content-Type: application/json');
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$nivel = $_GET['nivel'] ?? 'A1';
$idioma = $_GET['idioma'] ?? null;
$caminho_id = $_GET['caminho_id'] ?? null;

// Se não há idioma no parâmetro, tentar obter do progresso do usuário
if (!$idioma && isset($_SESSION['id_usuario'])) {
    $database = new Database();
    $conn = $database->conn;
    
    $sql_idioma = "SELECT idioma FROM progresso_usuario WHERE id_usuario = ? ORDER BY ultima_atividade DESC LIMIT 1";
    $stmt_idioma = $conn->prepare($sql_idioma);
    $stmt_idioma->bind_param("i", $_SESSION['id_usuario']);
    $stmt_idioma->execute();
    $result_idioma = $stmt_idioma->get_result();
    if ($row_idioma = $result_idioma->fetch_assoc()) {
        $idioma = $row_idioma['idioma'];
    }
    $stmt_idioma->close();
    $database->closeConnection();
}

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Normalizar nome do idioma para busca
    $idioma_normalizado = $idioma;
    if ($idioma) {
        // Mapear nomes de idiomas para o formato do banco
        $mapeamento_idiomas = [
            'ingles' => 'Inglês',
            'portugues' => 'Português', 
            'espanhol' => 'Espanhol',
            'frances' => 'Francês',
            'alemao' => 'Alemão',
            'italiano' => 'Italiano',
            'japones' => 'Japonês',
            'coreano' => 'Coreano'
        ];
        
        $idioma_normalizado = $mapeamento_idiomas[strtolower($idioma)] ?? ucfirst(strtolower($idioma));
    }
    
    // Buscar teorias por idioma e nível
    if ($idioma_normalizado) {
        // Primeiro tentar buscar com o campo idioma diretamente
        $sql = "SELECT id, titulo, nivel, ordem, resumo, conteudo FROM teorias 
                WHERE nivel = ? AND (idioma = ? OR idioma = ? OR idioma = ?) 
                ORDER BY ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nivel, $idioma, $idioma_normalizado, ucfirst($idioma));
    } else {
        // Fallback para quando não há idioma definido - buscar todas
        $sql = "SELECT id, titulo, nivel, ordem, resumo, conteudo FROM teorias WHERE nivel = ? ORDER BY ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nivel);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teorias = [];
    while ($row = $result->fetch_assoc()) {
        $teorias[] = $row;
    }
    
    $stmt->close();
    
    // Se não encontrou teorias com o idioma específico, buscar teorias genéricas
    if (empty($teorias) && $idioma_normalizado) {
        $sql_generic = "SELECT id, titulo, nivel, ordem, resumo, conteudo FROM teorias 
                       WHERE nivel = ? AND (idioma IS NULL OR idioma = '' OR idioma = 'Geral') 
                       ORDER BY ordem ASC";
        $stmt_generic = $conn->prepare($sql_generic);
        $stmt_generic->bind_param("s", $nivel);
        $stmt_generic->execute();
        $result_generic = $stmt_generic->get_result();
        
        while ($row = $result_generic->fetch_assoc()) {
            $teorias[] = $row;
        }
        $stmt_generic->close();
    }
    
    $database->closeConnection();
    
    echo json_encode([
        'success' => true,
        'teorias' => $teorias,
        'debug' => [
            'idioma_original' => $idioma,
            'idioma_normalizado' => $idioma_normalizado,
            'nivel' => $nivel,
            'total_teorias' => count($teorias)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar teorias: ' . $e->getMessage()
    ]);
}
?>