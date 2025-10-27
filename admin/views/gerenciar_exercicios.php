<?php
session_start();
include_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['caminho_id']) || !is_numeric($_GET['caminho_id'])) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$caminho_id = $_GET['caminho_id'];
$mensagem = '';

if (isset($_GET['status']) && $_GET['status'] == 'sucesso_exclusao') {
    $mensagem = '<div class="alert alert-success">Exercício excluído com sucesso!</div>';
}

$database = new Database();
$conn = $database->conn;

$id_admin = $_SESSION['id_admin'];
$sql_foto = "SELECT foto_perfil FROM administradores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $id_admin);
$stmt_foto->execute();
$resultado_foto = $stmt_foto->get_result();
$admin_foto = $resultado_foto->fetch_assoc();
$stmt_foto->close();

$foto_admin = !empty($admin_foto['foto_perfil']) ? '../../' . $admin_foto['foto_perfil'] : null;

$sql_caminho = "SELECT nome_caminho, nivel FROM caminhos_aprendizagem WHERE id = ?";
$stmt_caminho = $conn->prepare($sql_caminho);
$stmt_caminho->bind_param("i", $caminho_id);
$stmt_caminho->execute();
$caminho_info = $stmt_caminho->get_result()->fetch_assoc();
$stmt_caminho->close();

if (!$caminho_info) {
    header("Location: gerenciar_caminho.php");
    exit();
}

$sql_exercicios = "SELECT id, ordem, tipo, pergunta FROM exercicios WHERE caminho_id = ? ORDER BY ordem";
$stmt_exercicios = $conn->prepare($sql_exercicios);
$stmt_exercicios->bind_param("i", $caminho_id);
$stmt_exercicios->execute();
$exercicios = $stmt_exercicios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exercicios->close();

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Exercícios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../imagens/mini-esquilo.png">

    <style>
        :root {
            --roxo-principal: #6a0dad;
            --roxo-escuro: #4c087c;
            --amarelo-detalhe: #ffd700;
            --branco: #ffffff;
            --preto-texto: #212529;
            --cinza-claro: #f8f9fa;
            --verde: #28a745;
            --azul: #007bff;
            --vermelho: #dc3545;
            --cinza-escuro: #2c3e50;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            color: var(--preto-texto);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

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

        /* NOVO ESTILO DA TABELA */
        .modern-table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(106, 13, 173, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .modern-table-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(106, 13, 173, 0.15);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .modern-table thead {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
        }

        .modern-table th {
            color: white;
            font-weight: 600;
            padding: 18px 16px;
            text-align: left;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .modern-table th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255, 255, 255, 0.3);
            
        }

        .modern-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: white;
            color: var(--cinza-escuro);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

       

        /* CORES DAS CÉLULAS */
        .modern-table td:nth-child(1) { /* ID */
            color: rgba(177, 183, 187, 1);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .modern-table td:nth-child(2) { /* Ordem */
            color: #e74c3c;
            font-weight: 600;
        }

        .modern-table td:nth-child(3) { /* Tipo */
            color: var(--cinza-escuro);
        }

        .modern-table td:nth-child(4) { /* Pergunta */
            color: #34495e;
            font-weight: 400;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* BADGE MODERNO */
        .badge-modern {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

      /* ESTILOS ESPECÍFICOS PARA OS BOTÕES SOLICITADOS */
.btn-voltar-blocos {
    background: #4c087c28;
    border: 2px solid var(--roxo-principal);
    color: var(--roxo-principal);
    border-radius: 25px;
    padding: 0.8rem 1.8rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
    font-size: 0.95rem;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.btn-voltar-blocos::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s ease;
}

.btn-voltar-blocos:hover {
    background: var(--roxo-principal);
    color: var(--branco);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
    border-color: var(--roxo-principal);
}

.btn-voltar-blocos:hover::before {
    left: 100%;
}

.btn-criar-exercicio-stack {
    background: linear-gradient(135deg, var(--amarelo-detalhe) 0%, #f39c12 100%);
    color: var(--preto-texto);
    border: none;
    border-radius: 12px;
    padding: 20px 30px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    min-width: 180px;
    
    /* Novos efeitos */
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-criar-exercicio-stack::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-criar-exercicio-stack:hover::before {
    left: 100%;
}

.btn-criar-exercicio-stack:hover {
    background: linear-gradient(135deg, var(--amarelo-detalhe) 0%, #f39c12 100%);
    color: var(--preto-texto);
    transform: translateY(-3px);
    text-decoration: none;
    box-shadow: 0 8px 25px rgba(243, 156, 18, 0.4);
}

.btn-icon {
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-text {
    font-size: 14px;
    font-weight: 600;
    text-align: center;
}

/* Container especial para o botão */
.text-center.mt-4 {
    margin-top: 2rem !important;
    padding: 1rem 0;
}
        /* BOTÕES DE AÇÃO NA TABELA */
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s ease;
            margin: 0 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--azul), #0056b3);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--vermelho), #c82333);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px) scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* HEADER */
        h2 {
            color: black;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            
        }

        .alert-success {
            background: linear-gradient(135deg, #d1e7dd, #a3cfbb);
            color: #0f5132;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            font-weight: 500;
        }

        /* LAYOUT DOS BOTÕES - BOTÃO DIREITO ALINHADO À DIREITA */
        .buttons-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        .left-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

      

        /* ESTADO VAZIO */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
            font-weight: 500;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bdc3c7;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .modern-table-container {
                overflow-x: auto;
            }
            
            .modern-table {
                min-width: 600px;
            }
            
            .btn-modern {
                padding: 8px 16px;
                font-size: 0.8rem;
            }
            
            .btn-action {
                width: 32px;
                height: 32px;
            }

            .buttons-container {
                flex-direction: column;
                align-items: stretch;
            }

            .left-buttons, .right-buttons {
                justify-content: center;
                width: 100%;
            }
        }

        /* ANIMAÇÕES */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .modern-table tbody tr {
            animation: slideIn 0.5s ease-out;
        }

        /* SIDEBAR E NAVBAR (MANTIDOS ORIGINAIS) */
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
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

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
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }

        /* ESTADO VAZIO MODERNO - ESTILO DA IMAGEM */
.empty-state-modern {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-modern h3 {
    color: var(--cinza-escuro);
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.empty-state-modern p {
    color: #6c757d;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}
    </style>
</head>

<body>
    <!-- Barra de Navegação Superior (mantida original) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid d-flex justify-content-end align-items-center">
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

    <!-- Menu Lateral (mantido original) -->
    <div class="sidebar">
        <div class="profile">
            <?php if ($foto_admin): ?>
                <div class="profile-avatar-sidebar">
                    <img src="<?= htmlspecialchars($foto_admin) ?>" alt="Foto de perfil" class="profile-avatar-img">
                </div>
            <?php else: ?>
                <div class="profile-avatar-sidebar">
                    <i class="fas fa-user-circle"></i>
                </div>
            <?php endif; ?>
            <h5><?php echo htmlspecialchars($_SESSION['nome_admin']); ?></h5>
            <small>Administrador(a)</small>
        </div>

        <div class="list-group">
            <a href="gerenciar_caminho.php" class="list-group-item active" >
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
            <a href="logout.php" class="list-group-item sair">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <!-- Conteúdo Principal -->
<div class="main-content">
    <div class="container mt-4">
        <div class="page-header">
            <h2 class="mb-4">
                <i class="fas fa-tasks me-2"></i>
                Exercícios do Caminho: <?php echo htmlspecialchars($caminho_info['nome_caminho']) . ' (' . htmlspecialchars($caminho_info['nivel']) . ')'; ?>
            </h2>
        </div>
        
        <!-- Container dos botões com layout organizado -->
        <div class="buttons-container">
            <div class="left-buttons">
               <a href="#" onclick="voltarParaBlocos()" class="btn-voltar-blocos">
    <i class="fas fa-arrow-left me-2"></i>Voltar para Blocos
</a>
<script>
function voltarParaBlocos() {
    window.location.href = 'http://localhost/App_idiomas/admin/views/gerenciar_blocos.php?caminho_id=10';
}
</script>
            </div>
        </div>
        
        <?php echo $mensagem; ?>

        <!-- Tabela Moderna (novo estilo) -->
        <div class="modern-table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ordem</th>
                        <th>Tipo</th>
                        <th>Pergunta</th>
                        <th>Ações</th>
                    </tr>
    <tbody>
    <?php if (!empty($exercicios)): ?>
        <?php foreach ($exercicios as $exercicio): ?>
            <tr>
                <td><?php echo htmlspecialchars($exercicio['id']); ?></td>
                <td><?php echo htmlspecialchars($exercicio['ordem']); ?></td>
                <td>
                    <span class="badge-modern"><?php echo htmlspecialchars($exercicio['tipo']); ?></span>
                </td>
                <td title="<?php echo htmlspecialchars($exercicio['pergunta']); ?>">
                    <?php echo htmlspecialchars(substr($exercicio['pergunta'], 0, 50)) . '...'; ?>
                </td>
                <td>
                    <div class="d-flex justify-content-start">
                        <a href="editar_exercicio.php?id=<?php echo htmlspecialchars($exercicio['id']); ?>" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="eliminar_exercicio.php?id=<?php echo htmlspecialchars($exercicio['id']); ?>" class="btn-action btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este exercício?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5">
                <div class="empty-state-modern">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>Nenhum exercício encontrado</h3>
                    <p>Gerencie exercícios ou ajuste atividades.</p>
                    <a href="adicionar_atividades.php?caminho_id=<?php echo htmlspecialchars($caminho_id); ?>" class="btn-criar-exercicio-stack">
                        <div class="btn-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="btn-text">Adicionar Exercício</div>
                    </a>
                </div>
            </td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adiciona animação de entrada para as linhas da tabela
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.modern-table tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>