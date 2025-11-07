<?php
session_start();
// Corrigir o caminho da conexão
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

$database = new Database();
$conn = $database->conn;

// Lógica para buscar os idiomas únicos do banco de dados das tabelas caminhos_aprendizagem e quiz_nivelamento
$sql_idiomas = "(SELECT DISTINCT idioma FROM caminhos_aprendizagem) UNION (SELECT DISTINCT idioma FROM quiz_nivelamento) ORDER BY idioma";
$stmt_idiomas = $conn->prepare($sql_idiomas);
$stmt_idiomas->execute();
$idiomas_db = $stmt_idiomas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_idiomas->close();

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;


// Processar ações (adicionar, editar, eliminar)
$action = $_GET['action'] ?? '';
$unidade_id = $_GET['id'] ?? '';

// Eliminar unidade
if ($action === 'delete' && $unidade_id) {
    // Verificar se existem teorias associadas a esta unidade
    $sql_check_teorias = "SELECT COUNT(*) as total FROM teorias WHERE id_unidade = ?";
    $stmt_check = $conn->prepare($sql_check_teorias);
    $stmt_check->bind_param("i", $unidade_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    
    if ($row_check['total'] > 0) {
        $_SESSION['error'] = "Não é possível eliminar a unidade porque existem teorias associadas a ela.";
    } else {
        // Eliminar a unidade
        $sql_delete = "DELETE FROM unidades WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $unidade_id);
        
        if ($stmt_delete->execute()) {
            $_SESSION['success'] = "Unidade eliminada com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao eliminar a unidade: " . $conn->error;
        }
        $stmt_delete->close();
    }
    $stmt_check->close();
    
    header("Location: gerenciar_unidades.php");
    exit();
}

// Buscar dados para edição
$unidade_edit = null;
if ($action === 'edit' && $unidade_id) {
    $sql_edit = "SELECT * FROM unidades WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("i", $unidade_id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    
    if ($result_edit->num_rows > 0) {
        $unidade_edit = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}

// Processar formulário (adicionar/editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_unidade = $_POST['nome_unidade'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $numero_unidade = $_POST['numero_unidade'] ?? '';
    $id_idioma = $_POST['id_idioma'] ?? '';
    
    if ($action === 'edit' && $unidade_id) {
        // Atualizar unidade existente
        $sql_update = "UPDATE unidades SET nome_unidade = ?, descricao = ?, nivel = ?, numero_unidade = ?, id_idioma = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssiii", $nome_unidade, $descricao, $nivel, $numero_unidade, $id_idioma, $unidade_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['success'] = "Unidade atualizada com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar a unidade: " . $conn->error;
        }
        $stmt_update->close();
    } else {
        // Adicionar nova unidade
        $sql_insert = "INSERT INTO unidades (nome_unidade, descricao, nivel, numero_unidade, id_idioma) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sssii", $nome_unidade, $descricao, $nivel, $numero_unidade, $id_idioma);
        
        if ($stmt_insert->execute()) {
            $_SESSION['success'] = "Unidade adicionada com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao adicionar a unidade: " . $conn->error;
        }
        $stmt_insert->close();
    }
    
    header("Location: gerenciar_unidades.php");
    exit();
}

// Buscar idiomas para o formulário
$idiomas = [];
$sql_idiomas = "SELECT id, nome_idioma FROM idiomas ORDER BY nome_idioma";
$result_idiomas = $conn->query($sql_idiomas);
if ($result_idiomas->num_rows > 0) {
    while ($row = $result_idiomas->fetch_assoc()) {
        $idiomas[] = $row;
    }
}

// Buscar unidades para a tabela
$unidades = [];
$sql_unidades = "SELECT u.id, u.nome_unidade, u.descricao, u.nivel, u.numero_unidade, i.nome_idioma 
                 FROM unidades u 
                 JOIN idiomas i ON u.id_idioma = i.id 
                 ORDER BY i.nome_idioma, u.nivel, u.numero_unidade, u.nome_unidade";
$result_unidades = $conn->query($sql_unidades);

if ($result_unidades->num_rows > 0) {
    while ($row = $result_unidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Buscar estatísticas
$total_unidades = count($unidades);
$sql_idiomas = "SELECT COUNT(DISTINCT id_idioma) as total_idiomas FROM unidades";
$result_idiomas = $conn->query($sql_idiomas);
$total_idiomas = $result_idiomas->fetch_assoc()['total_idiomas'];

$sql_niveis = "SELECT COUNT(DISTINCT nivel) as total_niveis FROM unidades WHERE nivel IS NOT NULL";
$result_niveis = $conn->query($sql_niveis);
$total_niveis = $result_niveis->fetch_assoc()['total_niveis'];

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Unidades - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">
<<<<<<< HEAD

    <style>
=======
    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --amarelo-botao: #ffd700;
            --amarelo-hover: #e7c500;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --cinza-medio: #dee2e6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }

>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        .settings-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .settings-icon:hover {
            color: var(--roxo-escuro) !important;
            transform: rotate(90deg);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(106, 13, 173, 0.1);
            transition: all 0.3s ease;
        }

        .card-header h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .card-header h5 i {
            color: var(--amarelo-detalhe);
        }

<<<<<<< HEAD
        /* Cartões de Estatísticas - MESMO ESTILO DO GERENCIAR_CAMINHO */
=======
        /* Cartões de Estatísticas */
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        .stats-card {
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--preto-texto);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 2px solid rgba(106, 13, 173, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: statsCardAnimation 0.8s ease-out;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        @keyframes statsCardAnimation {
            from {
                opacity: 0;
                transform: translateY(30px) rotateX(-10deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(106, 13, 173, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .stats-card:hover::before {
            left: 100%;
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 30px rgba(106, 13, 173, 0.25);
            border-color: rgba(106, 13, 173, 0.3);
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--roxo-principal);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-card p {
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 1.1rem;
            color: var(--preto-texto);
        }

        .stats-card i {
            font-size: 2rem;
            color: var(--amarelo-detalhe);
            margin-bottom: 1rem;
        }

        /* Animações adicionais para stats-card */
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }

<<<<<<< HEAD
        /* Container do avatar - PARA QUANDO TEM FOTO */
=======
        /* Barra de Navegação - MODIFICADA PARA TRANSPARENTE */
        .navbar {
            background-color: transparent !important;
            border-bottom: 3px solid var(--amarelo-detalhe);
            box-shadow: 0 4px 15px rgba(255, 238, 0, 0.38);
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
            height: 70px;
            width: auto;
            display: block;
        }

        .btn-outline-light {
            color: var(--amarelo-detalhe);
            border-color: var(--amarelo-detalhe);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-warning:hover {
            background-color: var(--amarelo-detalhe);
            border: 0 4px 8px rgba(235, 183, 14, 0.77);
        }

        /* Menu Lateral */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3);
            color: var(--branco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        .profile-avatar-sidebar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid var(--amarelo-detalhe);
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-avatar-sidebar:has(.profile-avatar-img) i {
            display: none;
        }

        .profile-avatar-sidebar i {
            font-size: 3.5rem;
            color: var(--amarelo-detalhe);
        }

<<<<<<< HEAD
        /* Estilo para botões de ação */
        .btn-acoes {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-blocos, .btn-editar, .btn-eliminar {
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            background-color: transparent !important;
            border-width: 2px;
            border-style: solid;
            color: inherit !important;
        }

        .btn-editar {
            border-color: #3b82f6;
            color: #3b82f6 !important;
        }

        .btn-eliminar {
            border-color: #dc2626;
            color: #dc2626 !important;
        }

        .btn-editar:hover,
        .btn-editar:focus {
            background-color: #60a5fa !important;
            color: #fff !important;
            border-color: #60a5fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);
        }

        .btn-eliminar:hover,
        .btn-eliminar:focus {
            background-color: #ef4444 !important;
            color: #fff !important;
            border-color: #ef4444;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-editar:active,
        .btn-eliminar:active {
            transform: scale(0.97);
            box-shadow: none;
        }

        /* Estilo para logout no sidebar */
=======
        .sidebar .profile h5 {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--branco);
            font-size: 1.1rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.3;
        }

        .sidebar .profile small {
            color: var(--cinza-claro);
            font-size: 0.9rem;
            word-wrap: break-word;
            max-width: 200px;
            text-align: center;
            line-height: 1.2;
            margin-top: 5px;
        }

        .sidebar .list-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        .sidebar .list-group-item.sair {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 40px !important;
        }

<<<<<<< HEAD
=======
        .sidebar .list-group-item {
            background-color: transparent;
            color: var(--branco);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .sidebar .list-group-item:hover {
            background-color: var(--roxo-escuro);
            cursor: pointer;
        }

        .sidebar .list-group-item.active {
            background-color: var(--roxo-escuro) !important;
            color: var(--branco) !important;
            font-weight: 600;
            border-left: 4px solid var(--amarelo-detalhe);
        }

        .sidebar .list-group-item i {
            color: var(--amarelo-detalhe);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Menu Hamburguer */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--roxo-principal) !important;
            font-size: 1.5rem;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            color: var(--roxo-escuro) !important;
            transform: scale(1.1);
        }

        /* CORREÇÃO: Quando a sidebar está ativa */
        body:has(.sidebar.active) .menu-toggle,
        .sidebar.active ~ .menu-toggle {
            color: var(--amarelo-detalhe) !important;
        }

        body:has(.sidebar.active) .menu-toggle:hover,
        .sidebar.active ~ .menu-toggle:hover {
            color: var(--amarelo-hover) !important;
        }

        /* Overlay para mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--amarelo-botao) 0%, #f39c12 100%);
            color: var(--preto-texto);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            min-width: 180px;
            border: none;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--amarelo-hover) 0%, var(--amarelo-botao) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 217, 0, 0.66);
            color: var(--preto-texto);
        }

>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        .logout-icon {
            color: var(--roxo-principal) !important;
            transition: all 0.3s ease;
            text-decoration: none;
<<<<<<< HEAD
=======
            font-size: 1.2rem;
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        }

        .logout-icon:hover {
            color: var(--roxo-escuro) !important;
<<<<<<< HEAD
=======
            transform: translateY(-2px);
        }

        /* Estilos específicos para a página de unidades */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, #7e22ce, #581c87, #3730a3) !important;
            color: var(--branco);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }

        .card-header h2, .card-header h3 {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background-color: var(--roxo-principal);
            border-color: var(--roxo-principal);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--roxo-escuro);
            border-color: var(--roxo-escuro);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        }

        .btn-secondary {
            background-color: var(--cinza-medio);
            border-color: var(--cinza-medio);
            color: var(--preto-texto);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #b8b9bdd3;
            border-color: #c8c9cb;
            transform: scale(1.05);
            color: var(--preto-texto);
            box-shadow: 0 4px 12px rgba(194, 192, 192, 0.53);
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
            transform: scale(1.05);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
            transform: scale(1.05);
        }

        /* Estilo fixo e elegante para o header da tabela */
        .table thead th {
            background: linear-gradient(145deg, #6a0dad, #5a0b9a);
            color: var(--branco);
            border: none;
            font-weight: 600;
            padding: 16px 15px;
            text-align: center;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            border-bottom: 2px solid var(--amarelo-detalhe);
            position: relative;
            transition: all 0.2s ease;
        }

        .table thead th:first-child {
            border-top-left-radius: 10px;
        }

        .table thead th:last-child {
            border-top-right-radius: 10px;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background-color: var(--branco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(106, 13, 173, 0.08);
            margin-bottom: 0;
            border: 1px solid rgba(106, 13, 173, 0.08);
        }

        .table tbody td {
            padding: 14px 15px;
            border-bottom: 1px solid rgba(106, 13, 173, 0.08);
            text-align: center;
            vertical-align: middle;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(106, 13, 173, 0.02);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(106, 13, 173, 0.05);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .badge {
            font-weight: 600;
            padding: 0.5em 1em;
            border-radius: 50px;
        }

        .badge.bg-primary {
            background-color: var(--roxo-principal) !important;
        }

        .badge.bg-warning {
            background-color: var(--amarelo-detalhe) !important;
            color: var(--preto-texto);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(106, 13, 173, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .unidades-table {
            background: var(--branco);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px #ab4aef63;
            border: none;
        }

        .empty-state {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--cinza-medio);
        }

        /* Otimização da visualização da tabela */
        .table-unidades .col-nome-unidade,
        .table-unidades .col-descricao {
            min-width: 150px;
            text-align: left;
            word-wrap: break-word;
        }

        .table-unidades .col-idioma {
            white-space: nowrap;
        }

        /* Para telas menores, permitir quebra de linha */
        @media (max-width: 768px) {
            .table-unidades .col-nome-unidade,
            .table-unidades .col-descricao {
                min-width: 120px;
                white-space: normal;
                line-height: 1.4;
            }
            
            .table-unidades .col-descricao {
                max-width: 200px;
            }
            
            .table-unidades td,
            .table-unidades th {
                padding: 8px 6px;
                font-size: 0.85rem;
            }
            
            .table-unidades td {
                vertical-align: top;
            }
            
            .btn-group-sm .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        /* Garantir que a tabela seja responsiva */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
            
            .stats-card h3 {
                font-size: 2rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn-sm {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
                margin-top: 15px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
            }
            
            .stats-card {
                padding: 15px;
            }
            
            .stats-card h3 {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table-container {
                padding: 15px;
            }

            .btn-group-sm .btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
            
            .table thead th {
                padding: 12px 8px;
                font-size: 0.8rem;
            }
            
            .table tbody td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
        }
    </style>
</head>

<body>

<script>
document.addEventListener('DOMContentLoaded', function() {
<<<<<<< HEAD
    // Menu hamburguer functionality
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        });
    }

    // Fechar menu ao clicar em um link (em dispositivos móveis)
    const sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
                mainContent.classList.remove('expanded');
            }
        });
    });

    // Fechar menu ao redimensionar a janela para tamanho maior
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('show');
            mainContent.classList.remove('expanded');
        }
    });
});
</script>

    <!-- Botão Hamburguer -->
    <button class="hamburger-btn" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
=======
    // Menu Hamburguer Functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Fechar menu ao clicar em um link (mobile)
        const sidebarLinks = sidebar.querySelectorAll('.list-group-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        });
    }
});
</script>

    <!-- Menu Hamburguer -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#" style="margin-left: 0; margin-right: 0;">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
                <a href="logout.php" class="logout-icon" title="Sair">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
<<<<<<< HEAD
        <div class="profile">
            <?php if ($foto_admin): ?>
                <div class="profile-avatar-sidebar">
                    <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
=======
    <div class="profile">
        <?php if ($foto_admin): ?>
            <div class="profile-avatar-sidebar">
                <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
            </div>
        <?php else: ?>
            <div class="profile-avatar-sidebar">
                <i class="fa-solid fa-user" style="color: var(--amarelo-detalhe); font-size: 3.5rem;"></i>
            </div>
        <?php endif; ?>
        <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
        <small>Administrador(a)</small>
    </div>

    <div class="list-group">
        <a href="gerenciar_caminho.php" class="list-group-item ">
            <i class="fas fa-plus-circle"></i> Adicionar Caminho
        </a>
        <a href="pagina_adicionar_idiomas.php" class="list-group-item">
            <i class="fas fa-language"></i> Gerenciar Idiomas
        </a>
        <a href="gerenciar_teorias.php" class="list-group-item">
            <i class="fas fa-book-open"></i> Gerenciar Teorias
        </a>
        <a href="gerenciar_unidades.php" class="list-group-item active">
            <i class="fas fa-cubes"></i> Gerenciar Unidades
        </a>
        <a href="gerenciar_usuarios.php" class="list-group-item">
            <i class="fas fa-users"></i> Gerenciar Usuários
        </a>
        <a href="estatisticas_usuarios.php" class="list-group-item">
            <i class="fas fa-chart-bar"></i> Estatísticas
        </a>
    </div>
</div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="page-header flex-column flex-sm-row">
                <h2 class="mb-0"><i class="fas fa-cubes"></i> Gerenciar Unidades</h2>
                <div class="action-buttons">
                    <?php if ($action === 'edit'): ?>
                        <a href="gerenciar_unidades.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar Edição
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
                </div>
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
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
            <a href="gerenciar_unidades.php" class="list-group-item active">
                <i class="fas fa-cubes"></i> Gerenciar Unidades
            </a>
            <a href="gerenciar_usuarios.php" class="list-group-item">
                <i class="fas fa-users"></i> Gerenciar Usuários
            </a>
            <a href="estatisticas_usuarios.php" class="list-group-item">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </a>
            <a href="logout.php" class="list-group-item sair">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="container-fluid mt-4">
            <?php
            if (isset($_GET["message_type"]) && isset($_GET["message_content"])) {
                $message_type = htmlspecialchars($_GET["message_type"]);
                $message_content = htmlspecialchars(urldecode($_GET["message_content"]));
                echo '<div class="alert alert-' . ($message_type == 'success' ? 'success' : 'danger') . ' mt-3">' . $message_content . '</div>';
            }
            ?>

            <h2 class="mb-4">Gerenciar Unidades</h2>

            <a href="adicionar_unidade.php" class="btn btn-warning mb-4">
                <i class="fas fa-plus-circle me-2"></i>Adicionar Unidade
            </a>

            <!-- Notificações -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
<<<<<<< HEAD

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Estatísticas - MESMO LAYOUT DO GERENCIAR_CAMINHO -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-cubes"></i>
                        <h3><?= $total_unidades ?></h3>
                        <p>Total de Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-language"></i>
                        <h3><?= $total_idiomas ?></h3>
                        <p>Idiomas com Unidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-layer-group"></i>
                        <h3><?= $total_niveis ?></h3>
                        <p>Níveis Diferentes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line"></i>
                        <h3><?= $total_unidades ?></h3>
                        <p>Unidades Ativas</p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-container table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome da Unidade</th>
                            <th>Idioma</th>
                            <th>Nível</th>
                            <th>Número</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($unidades)): ?>
                        <?php foreach ($unidades as $unidade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unidade['id']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['nome_unidade']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['nome_idioma']); ?></td>
                            <td>
                                <?php if ($unidade['nivel']): ?>
                                    <span class="badge bg-warning"><?php echo htmlspecialchars($unidade['nivel']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($unidade['numero_unidade']): ?>
                                    <span class="fw-bold"><?php echo htmlspecialchars($unidade['numero_unidade']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($unidade['descricao'], 0, 50)) . (strlen($unidade['descricao']) > 50 ? '...' : ''); ?>
                                </small>
                            </td>
                            <td class="btn-acoes">
                                <a href="editar_unidade.php?id=<?php echo htmlspecialchars($unidade['id']); ?>"
                                    class="btn btn-sm btn-primary btn-editar">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-danger delete-btn btn-eliminar"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-id="<?php echo htmlspecialchars($unidade['id']); ?>"
                                    data-nome="<?php echo htmlspecialchars($unidade['nome_unidade']); ?>"
                                    data-tipo="unidade" 
                                    data-action="eliminar_unidade.php">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma unidade encontrada.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal de Confirmação de Eliminação -->
            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminação</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Tem certeza que deseja eliminar esta unidade?</p>
                            <p><strong id="itemNome"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Eliminar</a>
                        </div>
                    </div>
=======
            
            <!-- Formulário de Adicionar/Editar Unidade -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas <?= $action === 'edit' ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i>
                        <?= $action === 'edit' ? 'Editar Unidade' : 'Adicionar Nova Unidade' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="gerenciar_unidades.php?action=<?= $action ?>&id=<?= $unidade_id ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_unidade" class="form-label">Nome da Unidade *</label>
                                <input type="text" class="form-control" id="nome_unidade" name="nome_unidade" 
                                       value="<?= htmlspecialchars($unidade_edit['nome_unidade'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_idioma" class="form-label">Idioma *</label>
                                <select class="form-select" id="id_idioma" name="id_idioma" required>
                                    <option value="">Selecione um idioma</option>
                                    <?php foreach ($idiomas as $idioma): ?>
                                        <option value="<?= $idioma['id'] ?>" 
                                            <?= isset($unidade_edit['id_idioma']) && $unidade_edit['id_idioma'] == $idioma['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($idioma['nome_idioma']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="nivel" class="form-label">Nível</label>
                                <input type="text" class="form-control" id="nivel" name="nivel" 
                                       value="<?= htmlspecialchars($unidade_edit['nivel'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="numero_unidade" class="form-label">Número da Unidade</label>
                                <input type="number" class="form-control" id="numero_unidade" name="numero_unidade" 
                                       value="<?= htmlspecialchars($unidade_edit['numero_unidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($unidade_edit['descricao'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas <?= $action === 'edit' ? 'fa-save' : 'fa-plus' ?> me-2"></i>
                                <?= $action === 'edit' ? 'Atualizar Unidade' : 'Adicionar Unidade' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Unidades -->
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-list me-2"></i> Unidades Cadastradas</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($unidades)): ?>
                        <div class="text-center py-5 empty-state">
                            <i class="fas fa-cubes fa-3x mb-3 text-muted"></i>
                            <h4 class="text-muted">Nenhuma unidade cadastrada</h4>
                            <p class="text-muted">Adicione a primeira unidade usando o formulário acima.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-unidades">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th class="col-nome-unidade">Nome da Unidade</th>
                                        <th class="col-descricao">Descrição</th>
                                        <th>Nível</th>
                                        <th>Nº Unidade</th>
                                        <th class="col-idioma">Idioma</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unidades as $unidade): ?>
                                        <tr>
                                            <td><?= $unidade['id'] ?></td>
                                            <td class="col-nome-unidade" title="<?= htmlspecialchars($unidade['nome_unidade']) ?>">
                                                <?= htmlspecialchars($unidade['nome_unidade']) ?>
                                            </td>
                                            <td class="col-descricao" title="<?= htmlspecialchars($unidade['descricao']) ?>">
                                                <?= htmlspecialchars($unidade['descricao']) ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($unidade['nivel'])): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($unidade['nivel']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($unidade['numero_unidade'])): ?>
                                                    <span class="badge bg-warning"><?= htmlspecialchars($unidade['numero_unidade']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-idioma">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($unidade['nome_idioma']) ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="gerenciar_unidades.php?action=edit&id=<?= $unidade['id'] ?>" 
                                                       class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger delete-btn"
                                                            data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                                            data-id="<?= $unidade['id'] ?>"
                                                            data-nome="<?= htmlspecialchars($unidade['nome_unidade']) ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD

=======
    
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o item <strong id="itemNome"></strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>
    
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
<<<<<<< HEAD
        // Modal de confirmação de eliminação
        $(document).ready(function() {
            $('.delete-btn').on('click', function() {
                var id = $(this).data('id');
                var nome = $(this).data('nome');
                var action = $(this).data('action');
                var tipo = $(this).data('tipo');

                $('#itemNome').text(nome);
                $('#confirmDeleteBtn').attr('href', action + '?id=' + id + '&tipo=' + tipo);
=======
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const itemNome = document.getElementById('itemNome');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    itemNome.textContent = `"${nome}"`;
                    confirmDeleteBtn.href = `gerenciar_unidades.php?action=delete&id=${id}`;
                });
>>>>>>> 8657775199f686e857d86dd1f1b0bc174e6224b3
            });
        });
    </script>
</body>
</html>