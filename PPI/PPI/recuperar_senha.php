<?php
session_start();

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    $email = $_POST['email'];

    // Verificar se o e-mail existe no banco de dados
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Gerar um token único para redefinição de senha
        $token = bin2hex(random_bytes(50));
        $expires = date("U") + 1800; // Token válido por 30 minutos

        // Armazenar o token e a data de expiração no banco de dados
        $stmt->close();
        $stmt = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sis", $token, $expires, $email);
        $stmt->execute();

        // Enviar e-mail com o link de redefinição de senha
        $reset_link = "http://seusite.com/redefinir_senha.php?token=" . $token . "&email=" . $email;
        $subject = "Redefinição de Senha";
        $message = "Clique no link a seguir para redefinir sua senha: " . $reset_link;
        $headers = "From: no-reply@seusite.com";

        if (mail($email, $subject, $message, $headers)) {
            $success = "Um e-mail com instruções de redefinição de senha foi enviado.";
        } else {
            $error = "Houve um erro ao enviar o e-mail.";
        }
    } else {
        $error = "E-mail não encontrado.";
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
    <title>Recuperar Senha</title>
</head>
<body>
    <h2>Recuperar Senha</h2>
    <?php if (isset($error)) { ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php } elseif (isset($success)) { ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php } ?>
    <form method="POST" action="">
        <label for="email">E-mail:</label><br>
        <input type="email" id="email" name="email" required><br><br>
        <input type="submit" name="submit" value="Enviar">
    </form>
</body>
</html>
