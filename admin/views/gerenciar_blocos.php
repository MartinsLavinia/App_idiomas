<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

// Ativar exibição de erros (apenas para desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificação de segurança: Garante que apenas administradores logados possam acessar
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Verifica se o ID do caminho foi passado via URL
if (!isset($_GET['caminho_id']) || !is_numeric($_GET['caminho_id'])) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$caminho_id = $_GET['caminho_id'];
$mensagem = '';

// LÓGICA PARA ADICIONAR NOVO BLOCO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_bloco'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $nome_bloco = trim($_POST['nome_bloco'] ?? '');
    $ordem_bloco = intval($_POST['ordem_bloco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($titulo) || empty($nome_bloco) || $ordem_bloco <= 0) {
        $mensagem = '<div class="alert alert-danger">Título, nome do bloco e ordem são obrigatórios.</div>';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Verifica se já existe um bloco com essa ordem no mesmo caminho
        $sql_verifica = "SELECT id FROM blocos WHERE caminho_id = ? AND ordem = ?";
        $stmt_verifica = $conn->prepare($sql_verifica);
        $stmt_verifica->bind_param("ii", $caminho_id, $ordem_bloco);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();
        
        if ($result_verifica->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Já existe um bloco com esta ordem neste caminho.</div>';
        } else {
            // Insere o novo bloco
            $sql_insert = "INSERT INTO blocos (caminho_id, titulo, nome_bloco, ordem, descricao, data_criacao) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            
            if ($stmt_insert) {
                $stmt_insert->bind_param("issis", $caminho_id, $titulo, $nome_bloco, $ordem_bloco, $descricao);
                
                if ($stmt_insert->execute()) {
                    $mensagem = '<div class="alert alert-success">Bloco adicionado com sucesso!</div>';
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro ao adicionar bloco: ' . $stmt_insert->error . '</div>';
                }
                $stmt_insert->close();
            } else {
                $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
            }
        }
        $stmt_verifica->close();
        $database->closeConnection();
    }
}

// LÓGICA PARA EDITAR BLOCO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_bloco'])) {
    $bloco_id = intval($_POST['bloco_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $nome_bloco = trim($_POST['nome_bloco'] ?? '');
    $ordem_bloco = intval($_POST['ordem_bloco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($titulo) || empty($nome_bloco) || $ordem_bloco <= 0) {
        $mensagem = '<div class="alert alert-danger">Título, nome do bloco e ordem são obrigatórios.</div>';
    } else {
        $database = new Database();
        $conn = $database->conn;
        
        // Verifica se já existe outro bloco com essa ordem no mesmo caminho
        $sql_verifica = "SELECT id FROM blocos WHERE caminho_id = ? AND ordem = ? AND id != ?";
        $stmt_verifica = $conn->prepare($sql_verifica);
        $stmt_verifica->bind_param("iii", $caminho_id, $ordem_bloco, $bloco_id);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();
        
        if ($result_verifica->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Já existe outro bloco com esta ordem neste caminho.</div>';
        } else {
            // Atualiza o bloco
            $sql_update = "UPDATE blocos SET titulo = ?, nome_bloco = ?, ordem = ?, descricao = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if ($stmt_update) {
                $stmt_update->bind_param("ssisi", $titulo, $nome_bloco, $ordem_bloco, $descricao, $bloco_id);
                
                if ($stmt_update->execute()) {
                    $mensagem = '<div class="alert alert-success">Bloco atualizado com sucesso!</div>';
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro ao atualizar bloco: ' . $stmt_update->error . '</div>';
                }
                $stmt_update->close();
            } else {
                $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
            }
        }
        $stmt_verifica->close();
        $database->closeConnection();
    }
}

// LÓGICA PARA EXCLUIR BLOCO
if (isset($_GET['excluir_bloco'])) {
    $bloco_id = intval($_GET['excluir_bloco']);
    
    $database = new Database();
    $conn = $database->conn;
    
    // Verifica se existem atividades neste bloco
    // PRIMEIRO VERIFICA SE A COLUNA EXISTE
    $coluna_existe = false;
    $sql_verifica_coluna = "SHOW COLUMNS FROM exercicios LIKE 'bloco_id'";
    $result_coluna = $conn->query($sql_verifica_coluna);
    if ($result_coluna && $result_coluna->num_rows > 0) {
        $coluna_existe = true;
    }
    
    if ($coluna_existe) {
        $sql_verifica_atividades = "SELECT COUNT(*) as total FROM exercicios WHERE bloco_id = ?";
        $stmt_verifica = $conn->prepare($sql_verifica_atividades);
        
        if ($stmt_verifica) {
            $stmt_verifica->bind_param("i", $bloco_id);
            $stmt_verifica->execute();
            $result_verifica = $stmt_verifica->get_result();
            $row_verifica = $result_verifica->fetch_assoc();
            
            if ($row_verifica['total'] > 0) {
                $mensagem = '<div class="alert alert-danger">Não é possível excluir este bloco pois existem atividades vinculadas a ele.</div>';
            } else {
                // Exclui o bloco
                $sql_delete = "DELETE FROM blocos WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $bloco_id);
                    
                    if ($stmt_delete->execute()) {
                        $mensagem = '<div class="alert alert-success">Bloco excluído com sucesso!</div>';
                    } else {
                        $mensagem = '<div class="alert alert-danger">Erro ao excluir bloco: ' . $stmt_delete->error . '</div>';
                    }
                    $stmt_delete->close();
                } else {
                    $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
                }
            }
            $stmt_verifica->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na verificação de atividades: ' . $conn->error . '</div>';
        }
    } else {
        // Se a coluna não existe, pode excluir o bloco sem verificar atividades
        $sql_delete = "DELETE FROM blocos WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $bloco_id);
            
            if ($stmt_delete->execute()) {
                $mensagem = '<div class="alert alert-success">Bloco excluído com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao excluir bloco: ' . $stmt_delete->error . '</div>';
            }
            $stmt_delete->close();
        } else {
            $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta: ' . $conn->error . '</div>';
        }
    }
    
    $database->closeConnection();
}

// BUSCA AS INFORMAÇÕES DO CAMINHO E SEUS BLOCOS
$database = new Database();
$conn = $database->conn;

// Informações do caminho
$sql_caminho = "SELECT nome_caminho, nivel, id_unidade FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);

if ($stmt_caminho) {
    $stmt_caminho->bind_param("i", $caminho_id);
    $stmt_caminho->execute();
    $caminho_info = $stmt_caminho->get_result()->fetch_assoc();
    $stmt_caminho->close();
} else {
    $mensagem = '<div class="alert alert-danger">Erro ao buscar informações do caminho: ' . $conn->error . '</div>';
    $caminho_info = ['nome_caminho' => 'Erro', 'nivel' => 'Erro', 'id_unidade' => 'Erro'];
}

// Lista de blocos do caminho - COM VERIFICAÇÃO DA COLUNA bloco_id
$blocos = [];

// Primeiro verifica se a coluna bloco_id existe na tabela exercicios
$coluna_bloco_id_existe = false;
$sql_verifica_coluna = "SHOW COLUMNS FROM exercicios LIKE 'bloco_id'";
$result_coluna = $conn->query($sql_verifica_coluna);
if ($result_coluna && $result_coluna->num_rows > 0) {
    $coluna_bloco_id_existe = true;
}

// Query diferente baseada na existência da coluna
if ($coluna_bloco_id_existe) {
    $sql_blocos = "SELECT b.*, 
                   (SELECT COUNT(*) FROM exercicios e WHERE e.bloco_id = b.id) as total_atividades
                   FROM blocos b 
                   WHERE b.caminho_id = ? 
                   ORDER BY b.ordem ASC";
} else {
    $sql_blocos = "SELECT b.*, 0 as total_atividades
                   FROM blocos b 
                   WHERE b.caminho_id = ? 
                   ORDER BY b.ordem ASC";
}

$stmt_blocos = $conn->prepare($sql_blocos);

if ($stmt_blocos) {
    $stmt_blocos->bind_param("i", $caminho_id);
    $stmt_blocos->execute();
    $result_blocos = $stmt_blocos->get_result();
    
    if ($result_blocos) {
        $blocos = $result_blocos->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao buscar blocos: ' . $conn->error . '</div>';
    }
    $stmt_blocos->close();
} else {
    $mensagem = '<div class="alert alert-danger">Erro na preparação da consulta de blocos: ' . $conn->error . '</div>';
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Blocos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
<style>
:root {
    --roxo-principal: #6a0dad;
    --roxo-escuro: #4c087c;
    --roxo-claro: #8b5cf6;
    --amarelo-detalhe: #ffd700;
    --amarelo-botao: #ffd700;
    --amarelo-hover: #e6c200;
    --branco: #ffffff;
    --preto-texto: #212529;
    --cinza-claro: #f8f9fa;
    --cinza-medio: #dee2e6;
    --gradiente-roxo: linear-gradient(135deg, #6a0dad 0%, #4c087c 100%);
    --gradiente-amarelo: linear-gradient(135deg, #ffd700 0%, #e6c200 100%);
    --gradiente-verde: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    --gradiente-azul: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    --amarelo-acoes: #cfa90dff;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: var(--preto-texto);
    animation: fadeIn 1s ease-in-out;
    min-height: 100vh;
}

@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(20px);
    }
    to { 
        opacity: 1; 
        transform: translateY(0);
    }
}

.settings-icon {
    color: var(--roxo-principal) !important;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1.2rem;
}

.settings-icon:hover {
    color: var(--roxo-escuro) !important;
    transform: rotate(90deg) scale(1.1);
}

.table-container {
    background: var(--branco);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 30px rgba(106, 13, 173, 0.15);
    border: 2px solid rgba(106, 13, 173, 0.1);
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    backdrop-filter: blur(10px);
}

.table-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(106, 13, 173, 0.25);
    border-color: rgba(106, 13, 173, 0.2);
}

.card-header {
    background: var(--gradiente-roxo);
    color: white;
    padding: 20px 25px;
    border-radius: 15px 15px 0 0 !important;
    position: relative;
    overflow: hidden;
}

.card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

.card-header h5 {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 2;
}

.card-header h5 i {
    color: var(--amarelo-detalhe);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Cartões de Estatísticas Melhorados */
.stats-card {
    background: var(--branco);
    color: var(--preto-texto);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 25px;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 2px solid rgba(106, 13, 173, 0.1);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    animation: statsCardAnimation 0.8s ease-out;
    position: relative;
    overflow: hidden;
    text-align: center;
    backdrop-filter: blur(10px);
}

@keyframes statsCardAnimation {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(106, 13, 173, 0.08), transparent);
    transition: left 0.6s ease;
}

.stats-card:hover::before {
    left: 100%;
}

.stats-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(106, 13, 173, 0.3);
    border-color: rgba(106, 13, 173, 0.3);
}

.stats-card h3 {
    font-size: 2.8rem;
    font-weight: 800;
    margin-bottom: 8px;
    background: var(--gradiente-roxo);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 4px 8px rgba(106, 13, 173, 0.2);
}

.stats-card p {
    margin-bottom: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--roxo-escuro);
}

.stats-card i {
    font-size: 2.5rem;
    color: var(--amarelo-detalhe);
    margin-bottom: 1.2rem;
    text-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
}

/* Barra de Navegação */
.navbar {
    background: transparent !important;
    border-bottom: 3px solid var(--amarelo-detalhe);
    box-shadow: 0 6px 20px rgba(255, 238, 0, 0.25);
    backdrop-filter: blur(10px);
}

.navbar-brand {
    margin-left: auto;
    margin-right: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    width: 100%;
}

.navbar-brand .logo-header {
    height: 75px;
    width: auto;
    display: block;
    transition: transform 0.3s ease;
}

.navbar-brand .logo-header:hover {
    transform: scale(1.05);
}

/* Menu Lateral */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100%;
    background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
    color: var(--branco);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-top: 25px;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.sidebar .profile {
    text-align: center;
    margin-bottom: 35px;
    padding: 0 20px;
}

.sidebar .profile i {
    font-size: 4.5rem;
    color: var(--amarelo-detalhe);
    margin-bottom: 15px;
    text-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
    transition: transform 0.3s ease;
}

.sidebar .profile:hover i {
    transform: scale(1.1);
}

.sidebar .profile h5 {
    font-weight: 700;
    margin-bottom: 5px;
    color: var(--branco);
    font-size: 1.2rem;
}

.sidebar .profile small {
    color: var(--cinza-claro);
    font-size: 0.9rem;
}

.sidebar .list-group {
    width: 100%;
}

.sidebar .list-group-item {
    background-color: transparent;
    color: var(--branco);
    border: none;
    padding: 18px 25px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    margin: 2px 0;
}

.sidebar .list-group-item:hover {
    background: linear-gradient(135deg, rgba(106, 13, 173, 0.3), rgba(76, 8, 124, 0.3));
    border-left-color: var(--amarelo-detalhe);
    transform: translateX(5px);
}

.sidebar .list-group-item.active {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
    color: var(--branco) !important;
    font-weight: 700;
    border-left: 4px solid var(--amarelo-detalhe);
    box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.1);
}

.sidebar .list-group-item i {
    color: var(--amarelo-detalhe);
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

/* Conteúdo principal */
.main-content {
    margin-left: 280px;
    padding: 25px;
    background: transparent;
}

/* Botões Melhorados */
.btn {
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 700;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 2px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -150%;
    width: 150%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transform: skewX(-25deg);
    transition: left 0.8s ease;
}

.btn:hover::before {
    left: 150%;
}

.btn-warning {
    background: var(--gradiente-amarelo);
    color: var(--preto-texto);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    border: none;
    min-width: 200px;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e6c200 0%, #cc9900 100%);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 30px var(--roxo-principal);
    color: var(--preto-texto);
}

.btn-primary {
    background: var(--gradiente-roxo);
    border: none;
    color: white;
    box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 30px rgba(106, 13, 173, 0.6);
    color: var(--amarelo-detalhe);
}
.btn-outline-warning {
    background: var(--branco);
    color: var(--roxo-principal);
    border-color: var(--roxo-principal);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
}

.btn-outline-warning:hover {
    background: var(--roxo-principal);
    color: white;
    border-color: var(--roxo-principal);
    box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
    transform: translateY(-2px);
}

/* BOTÕES DE AÇÕES RÁPIDAS - ESTILO MINIMALISTA */
.acoes-rapidas-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #6a0dad;
    border: 2px solid #6a0dad;
    border-radius: 12px;
    padding: 12px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-transform: none;
    font-size: 0.9rem;
    min-width: 200px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    text-decoration: none;
    cursor: pointer;
}

/* Efeito hover sutil */
.acoes-rapidas-btn:hover {
    background: #6a0dad;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
    border-color: #6a0dad;
}

.acoes-rapidas-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(106, 13, 173, 0.2);
}

/* Botão Adicionar - Verde sutil */
.acoes-rapidas-btn.adicionar {
    color: #6a0dad;
    border-color: #6a0dad;
    background: #6a0dad22;
}

.acoes-rapidas-btn.adicionar:hover {
    background: #8212d3ff;
    color: var(--amarelo-detalhe);
    box-shadow: 0 4px 12px #6a0dad46;
}

/* Botão Ver - Azul sutil */
.acoes-rapidas-btn.ver {
    color: #2563eb;
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.05);
}

.acoes-rapidas-btn.ver:hover {
    background: #2563eb;
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Botão Gerenciar - Roxo sutil */
.acoes-rapidas-btn.gerenciar {
    color: #7c3aed;
    border-color: #7c3aed;
    background: rgba(124, 58, 237, 0.05);
}

.acoes-rapidas-btn.gerenciar:hover {
    background: #7c3aed;
    color: white;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
}

/* Estados desabilitados */
.acoes-rapidas-btn:disabled {
    color: #9ca3af;
    border-color: #d1d5db;
    background: rgba(156, 163, 175, 0.05);
    transform: none;
    box-shadow: none;
    cursor: not-allowed;
}

.acoes-rapidas-btn:disabled:hover {
    transform: none;
    box-shadow: none;
    background: rgba(156, 163, 175, 0.05);
    color: #9ca3af;
    border-color: #d1d5db;
}

/* Ícones menores */
.acoes-rapidas-btn i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.acoes-rapidas-btn:hover i {
    transform: scale(1.1);
}

/* Container minimalista */
.acoes-rapidas-container {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(106, 13, 173, 0.1);
    backdrop-filter: blur(15px);
}

.acoes-rapidas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

/* Dropdown minimalista */
.dropdown .acoes-rapidas-btn.dropdown-toggle::after {
    margin-left: 6px;
    transition: transform 0.3s ease;
}

.dropdown.show .acoes-rapidas-btn.dropdown-toggle::after {
    transform: rotate(180deg);
}

.dropdown-menu {
    border-radius: 12px;
    border: 1px solid rgba(106, 13, 173, 0.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background: rgba(255, 255, 255, 0.95);
    padding: 8px;
    margin-top: 5px !important;
}

.dropdown-item {
    border-radius: 8px;
    padding: 10px 12px;
    transition: all 0.2s ease;
    font-weight: 500;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 2px 0;
    font-size: 0.85rem;
}

.dropdown-item:hover {
    background: rgba(106, 13, 173, 0.08);
    color: #6a0dad;
    transform: none;
    box-shadow: none;
}

.dropdown-item i {
    font-size: 0.9rem;
    width: 16px;
    text-align: center;
}

/* Responsividade */
@media (max-width: 768px) {
    .acoes-rapidas-btn {
        min-width: 100%;
        padding: 10px 16px;
        font-size: 0.85rem;
    }
    
    .acoes-rapidas-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .acoes-rapidas-container {
        padding: 16px;
    }
    
    .acoes-rapidas-btn i {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .acoes-rapidas-btn {
        padding: 8px 14px;
    }
}
/* Efeitos de brilho adicionais */
.glow-effect {
    position: relative;
}

.glow-effect::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, var(--roxo-principal), var(--amarelo-detalhe), var(--roxo-claro));
    border-radius: inherit;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.glow-effect:hover::after {
    opacity: 0.3;
}

/* Efeito de profundidade */
.acoes-rapidas-btn {
    position: relative;
    z-index: 1;
}

.acoes-rapidas-btn:hover {
    z-index: 2;
}
.bloco-actions {
    opacity: 1 !important;
    transform: translateY(0) !important;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-acao {
    border-radius: 8px;
    padding: 6px 12px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: auto;
}

.btn-acao.btn-sm {
    padding: 4px 8px;
    font-size: 0.75rem;
}

.btn-acao.primary {
    background: var(--gradiente-roxo);
    color: white;
}

.btn-acao.primary:hover {
    background: linear-gradient(135deg, var(--roxo-escuro) 0%, var(--roxo-principal) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.4);
}

.btn-acao.warning {
    background: var(--gradiente-amarelo);
    color: var(--preto-texto);
}

.btn-acao.warning:hover {
    background: linear-gradient(135deg, #e6c200 0%, #cc9900 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
}

.btn-acao.danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn-acao.danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* Cartões de bloco melhorados */
.bloco-card {
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border-left: 5px solid var(--roxo-principal);
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    height: 100%;
    background: var(--branco);
    border: 2px solid rgba(106, 13, 173, 0.1);
}

.bloco-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 15px 35px rgba(106, 13, 173, 0.25);
    border-left-color: var(--amarelo-detalhe);
    border-color: rgba(106, 13, 173, 0.2);
}

.stats-badge {
    font-size: 0.8rem;
    background: var(--gradiente-roxo);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
}

/* Cards de formulário e listas */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    background: var(--branco);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Formulários */
.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid var(--cinza-medio);
    padding: 12px 18px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--roxo-principal);
    box-shadow: 0 0 0 0.3rem rgba(106, 13, 173, 0.2);
    transform: translateY(-2px);
}

/* Alertas */
.alert {
    border-radius: 15px;
    border: none;
    padding: 18px 22px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-left: 5px solid;
    backdrop-filter: blur(10px);
}

.alert-success {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(32, 201, 151, 0.1));
    color: #155724;
    border-left-color: #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(232, 62, 140, 0.1));
    color: #721c24;
    border-left-color: #dc3545;
}

.alert-warning {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(253, 126, 20, 0.1));
    color: #856404;
    border-left-color: #ffc107;
}

/* Badges */
.badge {
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.8rem;
}

/* Títulos e textos */
h2 {
    color: var(--roxo-principal);
    font-weight: 800;
    margin-bottom: 15px;
    font-size: 2.2rem;
    text-shadow: 0 2px 4px rgba(106, 13, 173, 0.1);
}

.text-muted {
    color: #6c757d !important;
}

/* Dropdown */
.dropdown-menu {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    padding: 10px 0;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.dropdown-item {
    padding: 10px 20px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
    color: white;
    transform: translateX(5px);
}

/* Animações adicionais para stats-card */
.stats-card:nth-child(1) { animation-delay: 0.1s; }
.stats-card:nth-child(2) { animation-delay: 0.2s; }
.stats-card:nth-child(3) { animation-delay: 0.3s; }
.stats-card:nth-child(4) { animation-delay: 0.4s; }

@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    .stats-card h3 {
        font-size: 2.2rem;
    }
    .navbar-brand .logo-header {
        height: 60px;
    }
    .btn {
        padding: 10px 20px;
        font-size: 0.85rem;
    }
    .acoes-rapidas-btn {
        min-width: 160px;
        padding: 12px 16px;
    }
    .bloco-actions {
        flex-direction: column;
        gap: 8px;
    }
    .btn-acao {
        width: 100%;
        justify-content: center;
    }
}

/* Container para ações rápidas */
.acoes-rapidas-container {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.8));
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(106, 13, 173, 0.1);
    backdrop-filter: blur(10px);
}

.acoes-rapidas-container .card-header {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
}

.acoes-rapidas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

/* Efeitos de brilho adicionais */
.glow-effect {
    position: relative;
}

.glow-effect::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, var(--roxo-principal), var(--amarelo-detalhe), var(--roxo-claro));
    border-radius: inherit;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.glow-effect:hover::after {
    opacity: 0.3;
}


.btn-back {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid var(--roxo-principal);
    color: var(--roxo-principal);
    padding: 0.6rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-back:hover {
    background-color:var(--roxo-escuro);
    border-color: var(--branco); 
    color: var(--branco);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}



</style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item">
                <i class="fas fa-plus-circle"></i> Adicionar Caminho
            </a>
            <a href="pagina_adicionar_idiomas.php" class="list-group-item">
                <i class="fas fa-language"></i> Gerenciar Idiomas
            </a>
            <a href="gerenciar_teorias.php" class="list-group-item">
                <i class="fas fa-book-open"></i> Gerenciar Teorias
            </a>
            <a href="gerenciar_unidades.php" class="list-group-item">
                <i class="fas fa-cubes"></i> Gerenciar Unidades
            </a>
            <a href="gerenciar_usuarios.php" class="list-group-item">
                <i class="fas fa-users"></i> Gerenciar Usuários
            </a>
            <a href="estatisticas_usuarios.php" class="list-group-item">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </a>
            <a href="logout.php" class="list-group-item mt-auto">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <!-- Cabeçalho original mantido, apenas o botão alterado -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-cubes me-2"></i>Gerenciar Blocos
                    </h2>
                    <p class="text-muted mb-0">
                        Caminho: <strong><?php echo htmlspecialchars($caminho_info['nome_caminho']); ?></strong> 
                        (<?php echo htmlspecialchars($caminho_info['nivel']); ?>)
                    </p>
                </div>
                <div>
                    <a href="gerenciar_caminho.php" class="btn-back">
    <i class="fas fa-arrow-left"></i>Voltar para Caminhos
</a>
                </div>
            </div>

            <?php echo $mensagem; ?>

            <!-- Alerta se a coluna bloco_id não existir -->
            <?php if (!$coluna_bloco_id_existe): ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Atenção: Coluna bloco_id não encontrada</h5>
                <p>A coluna <strong>bloco_id</strong> não existe na tabela <strong>exercicios</strong>.</p>
                <p class="mb-2">Execute este comando SQL para adicionar a coluna:</p>
                <pre class="bg-dark text-light p-3 rounded small">ALTER TABLE exercicios ADD COLUMN bloco_id INT;
ALTER TABLE exercicios ADD CONSTRAINT fk_exercicios_bloco 
FOREIGN KEY (bloco_id) REFERENCES blocos(id) ON DELETE SET NULL;</pre>
                <p class="mt-2 mb-0"><small>Enquanto a coluna não for criada, as atividades não serão vinculadas aos blocos.</small></p>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Formulário para Adicionar/Editar Bloco -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plus-circle me-1"></i>
                                <?php echo isset($_GET['editar']) ? 'Editar Bloco' : 'Adicionar Novo Bloco'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $edit_mode = isset($_GET['editar']);
                            $bloco_edit = null;
                            
                            if ($edit_mode) {
                                $bloco_id_edit = intval($_GET['editar']);
                                foreach ($blocos as $bloco) {
                                    if ($bloco['id'] == $bloco_id_edit) {
                                        $bloco_edit = $bloco;
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título do Bloco *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" 
                                           value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['titulo']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="nome_bloco" class="form-label">Nome do Bloco *</label>
                                    <input type="text" class="form-control" id="nome_bloco" name="nome_bloco" 
                                           value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['nome_bloco']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ordem_bloco" class="form-label">Ordem no Caminho *</label>
                                    <input type="number" class="form-control" id="ordem_bloco" name="ordem_bloco" 
                                           value="<?php echo $bloco_edit ? htmlspecialchars($bloco_edit['ordem']) : ''; ?>" 
                                           min="1" required>
                                    <div class="form-text">Define a sequência deste bloco no caminho</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $bloco_edit ? htmlspecialchars($bloco_edit['descricao']) : ''; ?></textarea>
                                </div>
                                
                                <?php if ($edit_mode): ?>
                                    <input type="hidden" name="bloco_id" value="<?php echo $bloco_edit['id']; ?>">
                                    <button type="submit" name="editar_bloco" class="btn btn-warning w-100">
                                        <i class="fas fa-save me-1"></i>Atualizar Bloco
                                    </button>
                                    <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>" class="btn btn-secondary w-100 mt-2">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                <?php else: ?>
                                    <button type="submit" name="adicionar_bloco" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-1"></i>Adicionar Bloco
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-1"></i>Estatísticas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-0"><?php echo count($blocos); ?></h4>
                                        <small class="text-muted">Total de Blocos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div>
                                        <?php
                                        $total_atividades = 0;
                                        foreach ($blocos as $bloco) {
                                            $total_atividades += $bloco['total_atividades'];
                                        }
                                        ?>
                                        <h4 class="text-success mb-0"><?php echo $total_atividades; ?></h4>
                                        <small class="text-muted">Total de Atividades</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Blocos Existentes -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-1"></i>Blocos do Caminho
                            </h5>
                            <span class="badge bg-primary"><?php echo count($blocos); ?> bloco(s)</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($blocos)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-cubes fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Nenhum bloco criado ainda.</p>
                                    <p class="text-muted small">Use o formulário ao lado para adicionar o primeiro bloco.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($blocos as $bloco): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bloco-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0">
                                                            <i class="fas fa-cube me-1 text-primary"></i>
                                                            <?php echo htmlspecialchars($bloco['titulo']); ?>
                                                        </h6>
                                                        <span class="badge bg-light text-light stats-badge">
                                                            <?php echo $bloco['total_atividades']; ?> ativid.
                                                        </span>
                                                    </div>
                                                    
                                                    <p class="card-text small text-muted mb-2">
                                                        <strong>Nome:</strong> <?php echo htmlspecialchars($bloco['nome_bloco']); ?>
                                                    </p>
                                                    
                                                    <p class="card-text small text-muted mb-2">
                                                        <?php echo !empty($bloco['descricao']) ? htmlspecialchars($bloco['descricao']) : '<em>Sem descrição</em>'; ?>
                                                    </p>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            Ordem: <strong><?php echo $bloco['ordem']; ?></strong> | 
                                                            Criado: <?php echo date('d/m/Y', strtotime($bloco['data_criacao'])); ?>
                                                        </small>
                                                        
                                                        <div class="bloco-actions">
                                                            <div class="btn-group btn-group-sm">
                                                                <?php if ($coluna_bloco_id_existe): ?>
                                                                <a href="gerenciar_exercicios.php?bloco_id=<?php echo $bloco['id']; ?>" 
                                                                   class="btn btn-outline-primary" title="Gerenciar Atividades">
                                                                    <i class="fas fa-tasks"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                                <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>&editar=<?php echo $bloco['id']; ?>" 
                                                                   class="btn btn-outline-warning" title="Editar Bloco">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="gerenciar_blocos.php?caminho_id=<?php echo $caminho_id; ?>&excluir_bloco=<?php echo $bloco['id']; ?>" 
                                                                   class="btn btn-outline-danger" 
                                                                   title="Excluir Bloco"
                                                                   onclick="return confirm('Tem certeza que deseja excluir este bloco?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bolt me-1"></i>Ações Rápidas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <?php if ($coluna_bloco_id_existe && !empty($blocos)): ?>
                                        <!-- Dropdown para escolher em qual bloco adicionar a atividade -->
                                        <div class="dropdown">
                                            <button class="acoes-rapidas-btn adicionar w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i>Adicionar Atividade
                                            </button>
                                            <ul class="dropdown-menu w-100">
                                                <?php foreach ($blocos as $bloco): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="adicionar_atividades.php?unidade_id=<?php echo $caminho_info['id_unidade']; ?>&bloco_id=<?php echo $bloco['id']; ?>">
                                                            <i class="fas fa-cube me-2"></i><?php echo htmlspecialchars($bloco['titulo']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <button class="acoes-rapidas-btn" disabled 
                                                title="<?php echo !$coluna_bloco_id_existe ? 'Coluna bloco_id não existe' : 'Crie um bloco primeiro'; ?>">
                                            <i class="fas fa-plus me-1"></i>Adicionar Atividade
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="gerenciar_exercicios.php?caminho_id=<?php echo $caminho_id; ?>" 
                                       class="acoes-rapidas-btn ver w-100">
                                        <i class="fas fa-list me-1"></i>Ver Todas as Atividades
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>