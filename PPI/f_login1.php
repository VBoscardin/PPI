<?php
session_start();

// Configurações do banco de dados
$servername = "localhost"; // ou o endereço do seu servidor MySQL
$db_username = "root"; // substitua pelo seu nome de usuário do MySQL
$db_password = ""; // substitua pela sua senha do MySQL
$dbname = "bd_ppi";

// Criar conexão
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar e executar a consulta SQL
    $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verificar a senha
        if(password_verify($password, $hashed_password)) {
            // Credenciais corretas, redirecionar para a página principal
            $_SESSION['email'] = $email;
            header("Location: f_pagina_adm.php");
            exit();
        } else {
            // Senha incorreta
            $error = "Credenciais inválidas. Por favor, tente novamente.";
        }
    } else {
        // E-mail não encontrado
        $error = "Credenciais inválidas. Por favor, tente novamente.";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    

</head>
<body>
    <div class = "cabecalho"><h1>SIGNE - SISTEMA GERENCIADOR <br> DE NOTAS ESCOLARES</h1></div>
    <?php if(isset($error)) { ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <div class = "login">
        <h1>Entrar no Sistema</h1>
    <form method="POST" action="">
        <div>
        <label for="email">Usuário:</label>
        <input type="email" id="email" name="email" class="digit" required>
        </div>
        <div>
        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" class="digit" required>
        </div>
        <input type="submit" name="login" value="Entrar">
    </form>
    </div>
</body>
</html>
