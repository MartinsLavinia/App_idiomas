
<?php
session_start();

// Destroi todas as variáveis da sessão
$_SESSION = [];

// Destroi a sessão
session_destroy();

// Redireciona para a página de login
header("Location:  public/Views/index.php");
exit();
?>
    