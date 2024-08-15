<?php
session_start();

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar as credenciais (exemplo fictício)
    if($username === 'usuario' && $password === 'senha') {
        // Credenciais corretas, redirecionar para página principal
        $_SESSION['username'] = $username;
        header("Location: pagina_principal.php");
        exit();
    } else {
        // Credenciais incorretas, exibir mensagem de erro
        $error = "Credenciais inválidas. Por favor, tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if(isset($error)) { ?>
        <p><?php echo $error; ?></p>
    <?php } ?>
    <form method="POST" action="">
        <label for="username">Usuário:</label><br>
        <input type="text" id="username" name="username" required><br>
        <label for="password">Senha:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" name="login" value="Login">
    </form>
</body>
</html>
