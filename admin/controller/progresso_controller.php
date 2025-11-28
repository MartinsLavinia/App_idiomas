<?php
session_start();
include_once __DIR__ . "/../../conexao.php";

header('Content-Type: application/json');

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$database = new Database();
$conn = $database->conn;
$id_usuario = $_SESSION["id_usuario"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'finalizar_bloco') {
        $bloco_id = intval($_POST['bloco_id']);
        $usuario_id = intval($_POST['usuario_id']);
        
        try {
            // Iniciar transação
            $conn->begin_transaction();
            
            // 1. Marcar bloco como finalizado
            $sql_finalizar = "UPDATE progresso_blocos SET 
                             concluido = 1, 
                             progresso_percentual = 100,
                             data_conclusao = NOW()
                             WHERE bloco_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql_finalizar);
            $stmt->bind_param("ii", $bloco_id, $usuario_id);
            
            if (!$stmt->execute()) {
                // Se não existe registro, criar um
                $sql_criar = "INSERT INTO progresso_blocos (bloco_id, usuario_id, concluido, progresso_percentual, data_conclusao) 
                             VALUES (?, ?, 1, 100, NOW())";
                $stmt_criar = $conn->prepare($sql_criar);
                $stmt_criar->bind_param("ii", $bloco_id, $usuario_id);
                $stmt_criar->execute();
                $stmt_criar->close();
            }
            $stmt->close();
            
            // 2. Buscar informações do bloco para determinar unidade
            $sql_bloco = "SELECT unidade_id, ordem FROM blocos WHERE id = ?";
            $stmt_bloco = $conn->prepare($sql_bloco);
            $stmt_bloco->bind_param("i", $bloco_id);
            $stmt_bloco->execute();
            $result_bloco = $stmt_bloco->get_result()->fetch_assoc();
            $stmt_bloco->close();
            
            if (!$result_bloco) {
                throw new Exception("Bloco não encontrado");
            }
            
            $unidade_id = $result_bloco['unidade_id'];
            $ordem_atual = $result_bloco['ordem'];
            
            // 3. Calcular progresso da unidade
            $sql_progresso_unidade = "
                SELECT 
                    COUNT(*) as total_blocos,
                    SUM(CASE WHEN pb.concluido = 1 THEN 1 ELSE 0 END) as blocos_concluidos
                FROM blocos b
                LEFT JOIN progresso_blocos pb ON b.id = pb.bloco_id AND pb.usuario_id = ?
                WHERE b.unidade_id = ? AND b.tipo != 'especial'
            ";
            $stmt_prog = $conn->prepare($sql_progresso_unidade);
            $stmt_prog->bind_param("ii", $usuario_id, $unidade_id);
            $stmt_prog->execute();
            $result_prog = $stmt_prog->get_result()->fetch_assoc();
            $stmt_prog->close();
            
            $progresso_percentual = 0;
            if ($result_prog['total_blocos'] > 0) {
                $progresso_percentual = round(($result_prog['blocos_concluidos'] / $result_prog['total_blocos']) * 100);
            }
            
            // 4. Verificar se deve liberar exercício especial
            $exercicio_especial_liberado = false;
            
            // Verificar se todos os blocos normais da unidade foram concluídos
            if ($result_prog['blocos_concluidos'] == $result_prog['total_blocos']) {
                // Liberar exercícios especiais da unidade
                $sql_especiais = "SELECT id FROM blocos WHERE unidade_id = ? AND tipo = 'especial'";
                $stmt_especiais = $conn->prepare($sql_especiais);
                $stmt_especiais->bind_param("i", $unidade_id);
                $stmt_especiais->execute();
                $result_especiais = $stmt_especiais->get_result();
                
                while ($especial = $result_especiais->fetch_assoc()) {
                    $sql_liberar_especial = "INSERT IGNORE INTO progresso_blocos (bloco_id, usuario_id, liberado, data_liberacao) 
                                           VALUES (?, ?, 1, NOW())";
                    $stmt_liberar = $conn->prepare($sql_liberar_especial);
                    $stmt_liberar->bind_param("ii", $especial['id'], $usuario_id);
                    $stmt_liberar->execute();
                    $stmt_liberar->close();
                    $exercicio_especial_liberado = true;
                }
                $stmt_especiais->close();
            }
            
            // 5. Verificar se deve liberar próximo bloco
            $proximo_bloco_liberado = false;
            $sql_proximo = "SELECT id FROM blocos WHERE unidade_id = ? AND ordem = ? AND tipo != 'especial'";
            $stmt_proximo = $conn->prepare($sql_proximo);
            $proximo_ordem = $ordem_atual + 1;
            $stmt_proximo->bind_param("ii", $unidade_id, $proximo_ordem);
            $stmt_proximo->execute();
            $result_proximo = $stmt_proximo->get_result()->fetch_assoc();
            
            if ($result_proximo) {
                $sql_liberar_proximo = "INSERT IGNORE INTO progresso_blocos (bloco_id, usuario_id, liberado, data_liberacao) 
                                       VALUES (?, ?, 1, NOW())";
                $stmt_liberar_prox = $conn->prepare($sql_liberar_proximo);
                $stmt_liberar_prox->bind_param("ii", $result_proximo['id'], $usuario_id);
                $stmt_liberar_prox->execute();
                $stmt_liberar_prox->close();
                $proximo_bloco_liberado = true;
            }
            $stmt_proximo->close();
            
            // 6. Verificar se unidade foi concluída
            $unidade_concluida = ($progresso_percentual >= 100);
            
            // 7. Atualizar progresso da unidade na tabela de progresso do usuário
            $sql_update_unidade = "UPDATE progresso_usuario SET 
                                  progresso_unidade = ?,
                                  ultima_atividade = NOW()
                                  WHERE id_usuario = ?";
            $stmt_update = $conn->prepare($sql_update_unidade);
            $stmt_update->bind_param("ii", $progresso_percentual, $usuario_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            // Confirmar transação
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Bloco finalizado com sucesso',
                'progresso_unidade' => $progresso_percentual,
                'exercicio_especial_liberado' => $exercicio_especial_liberado,
                'proximo_bloco_liberado' => $proximo_bloco_liberado,
                'unidade_concluida' => $unidade_concluida,
                'blocos_concluidos' => $result_prog['blocos_concluidos'],
                'total_blocos' => $result_prog['total_blocos']
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao finalizar bloco: ' . $e->getMessage()
            ]);
        }
    }
    
    elseif ($action === 'finalizar_exercicio_especial') {
        $exercicio_id = intval($_POST['exercicio_id']);
        $usuario_id = intval($_POST['usuario_id']);
        
        try {
            // Marcar exercício especial como concluído
            $sql_finalizar_especial = "UPDATE progresso_blocos SET 
                                      concluido = 1, 
                                      progresso_percentual = 100,
                                      data_conclusao = NOW()
                                      WHERE bloco_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql_finalizar_especial);
            $stmt->bind_param("ii", $exercicio_id, $usuario_id);
            $stmt->execute();
            $stmt->close();
            
            // Verificar se deve liberar próxima unidade ou conteúdo
            // (implementar lógica específica se necessário)
            
            echo json_encode([
                'success' => true,
                'message' => 'Exercício especial concluído',
                'proximo_conteudo_liberado' => true
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao finalizar exercício especial: ' . $e->getMessage()
            ]);
        }
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}

$database->closeConnection();
?>