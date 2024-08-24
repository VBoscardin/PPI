<?php
session_start();

// Configurações do banco de dados
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

// Criar conexão
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar e executar a consulta SQL
    $stmt = $conn->prepare("SELECT password_hash, tipo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password, $user_type);
        $stmt->fetch();

        // Verificar a senha
        if (password_verify($password, $hashed_password)) {
            // Credenciais corretas, armazenar o tipo de usuário na sessão
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $user_type;

            // Redirecionar para a página apropriada com base no tipo de usuário
            switch ($user_type) {
                case 'administrador':
                    header("Location: f_pagina_adm.php");
                    break;
                case 'docente':
                    header("Location: f_pagina_docente.php");
                    break;
                case 'setor':
                    header("Location: f_pagina_setor.php");
                    break;
                default:
                    $error = "Tipo de usuário desconhecido.";
                    break;
            }
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) { ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form method="POST" action="">
        <label for="email">E-mail:</label><br>
        <input type="email" id="email" name="email" required><br>
        <label for="password">Senha:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <p><a href="recuperar_senha.php">Esqueceu a senha?</a></p>
        <input type="submit" name="login" value="Login">
    </form>
</body>
</html>
