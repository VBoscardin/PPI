<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (isset($_SESSION['user_id'])) {
    // Destruir a sessão
    session_unset();
    session_destroy();
    
    // Redirecionar para a página de login
    header('Location: f_login.php');
    exit();
} else {
    // Se não estiver logado, redirecionar para a página de login
    header('Location: f_login.php');
    exit();
}
?>
