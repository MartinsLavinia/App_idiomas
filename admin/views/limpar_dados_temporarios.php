<?php
// limpar_dados_temporarios.php
session_start();

// Verificação de segurança
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit();
}

// Limpar dados temporários do quiz
unset($_SESSION['quiz_data']);
unset($_SESSION['idioma_novo']);

$_SESSION['success'] = "Dados temporários do formulário foram limpos com sucesso!";
header("Location: pagina_adicionar_idiomas.php");
exit();
?>