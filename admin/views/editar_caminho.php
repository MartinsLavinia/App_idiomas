<?php
session_start();
include_once __DIR__ . '/../../conexao.php';
include_once __DIR__ . '/../models/CaminhoAprendizagem.php';

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}
$mensagem = '';
$database = new Database();
$conn = $database->conn;
$caminhoObj = new CaminhoAprendizagem($conn);

// 1. Bloco de processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $idioma = $_POST['idioma'];
    $nome_caminho = $_POST['nome_caminho'];
    $nivel = $_POST['nivel'];

    // Chama o método para atualizar os dados no banco
    if ($caminhoObj->atualizarCaminho($id, $idioma, $nome_caminho, $nivel)) {
        $mensagem = "<div class='alert alert-success'>Caminho atualizado com sucesso!</div>";
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao atualizar o caminho.</div>";
    }
}

// 2. Lógica para buscar e exibir os dados (para GET ou após o POST)
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($id <= 0) {
    header("Location: gerenciar_caminho.php?erro=ID_invalido");
    exit();
}

$caminho = $caminhoObj->buscarPorId($id);

if (!$caminho) {
    header("Location: gerenciar_caminho.php?erro=caminho_nao_encontrado");
    exit();
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Caminho - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="gerenciamento.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ===== VARIÁVEIS DE CORES ===== */
        :root {
            --roxo-principal: #6f42c1;
            --roxo-escuro: #5a32a3;
            --roxo-claro: #8c68cd;
            --laranja: #fd7e14;
            --laranja-escuro: #e56a00;
            --cinza-claro: #f8f9fa;
            --cinza-borda: #dee2e6;
            --cinza-texto: #6c757d;
            --verde: #198754;
            --vermelho: #dc3545;
            --azul: #0d6efd;
            --azul-info: #0dcaf0;
        }

        /* ===== ESTILOS GERAIS ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 12px 0;
        }

        .logo-header {
            height: 40px;
            width: auto;
        }

        .settings-icon {
            color: white !important;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .settings-icon:hover {
            color: var(--laranja) !important;
            transform: rotate(90deg);
        }

        /* ===== CARD PRINCIPAL ===== */
        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 30px;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
            padding: 25px 30px;
            border-bottom: none;
        }

        .card-header-custom h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }

        .card-body-custom {
            padding: 40px;
        }

        /* ===== FORMULÁRIO ===== */
        .form-label {
            font-weight: 600;
            color: var(--roxo-escuro);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--roxo-principal);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }

        /* ===== BOTÕES ===== */
        .btn {
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--roxo-escuro), var(--roxo-principal));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #495057, #6c757d);
            transform: translateY(-2px);
        }

        /* ===== ALERTAS ===== */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid var(--verde);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid var(--vermelho);
        }

        /* ===== BREADCRUMB ===== */
        .breadcrumb {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .breadcrumb-item a {
            color: var(--roxo-principal);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: var(--cinza-texto);
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card-body-custom {
                padding: 25px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
                justify-content: center;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
            }
        }

        /* ===== ANIMAÇÕES ===== */
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

        .main-card {
            animation: fadeIn 0.5s ease-out;
        }

        /* ===== LOADING ===== */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* ===== ICONES ===== */
        .fas {
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div></div>
            <div class="d-flex align-items-center" style="gap: 24px;">
                <a class="navbar-brand" href="#">
                    <img src="../../imagens/logo-idiomas.png" alt="Logo do Site" class="logo-header">
                </a>
                <a href="editar_perfil.php" class="settings-icon">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="gerenciar_caminho.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="gerenciar_caminho.php">Caminhos</a></li>
                <li class="breadcrumb-item active">Editar Caminho</li>
            </ol>
        </nav>

        <!-- Card Principal -->
        <div class="main-card">
            <div class="card-header-custom">
                <h2><i class="fas fa-edit me-2"></i>Editar Caminho de Aprendizagem</h2>
            </div>
            
            <div class="card-body-custom">
                <?php echo $mensagem; ?>

                <form action="editar_caminho.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($caminho['id']); ?>">

                    <div class="mb-4">
                        <label for="idioma" class="form-label"><i class="fas fa-language me-2"></i>Idioma</label>
                        <input type="text" class="form-control" id="idioma" name="idioma" 
                               value="<?php echo htmlspecialchars($caminho['idioma']); ?>" required
                               placeholder="Digite o idioma">
                    </div>

                    <div class="mb-4">
                        <label for="nome_caminho" class="form-label"><i class="fas fa-road me-2"></i>Nome do Caminho</label>
                        <input type="text" class="form-control" id="nome_caminho" name="nome_caminho" 
                               value="<?php echo htmlspecialchars($caminho['nome_caminho']); ?>" required
                               placeholder="Digite o nome do caminho">
                    </div>

                    <div class="mb-4">
                        <label for="nivel" class="form-label"><i class="fas fa-chart-line me-2"></i>Nível</label>
                        <input type="text" class="form-control" id="nivel" name="nivel" 
                               value="<?php echo htmlspecialchars($caminho['nivel']); ?>" required
                               placeholder="Digite o nível (A1, A2, B1, etc.)">
                    </div>

                    <div class="d-flex gap-3 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <a href="gerenciar_caminho.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adicionar validação básica
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const nivel = document.getElementById('nivel').value.toUpperCase();
                const niveisValidos = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
                
                if (!niveisValidos.includes(nivel)) {
                    e.preventDefault();
                    alert('Por favor, insira um nível válido: A1, A2, B1, B2, C1 ou C2');
                    document.getElementById('nivel').focus();
                }
            });

            // Auto-uppercase para nível
            document.getElementById('nivel').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        });
    </script>
</body>
</html>