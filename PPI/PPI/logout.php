<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se você também deseja apagar o cookie da sessão, descomente a linha abaixo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: f_login.php");
exit();
?>
