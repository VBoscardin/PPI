<?php
session_start();

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $email = $_POST['email'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Verificar se o token é válido
        $stmt = $conn->prepare("SELECT reset_expires FROM usuarios WHERE email = ? AND reset_token = ?");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($reset_expires);
            $stmt->fetch();

            if (date("U") < $reset_expires) {
                // Token válido, atualizar a senha
                $stmt->close();
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
                $stmt->bind_param("ss", $new_password_hash, $email);
                $stmt->execute();

                $success = "Sua senha foi redefinida com sucesso.";
            } else {
                $error = "O token de redefinição de senha expirou.";
            }
        } else {
            $error = "Token inválido.";
        }
    } else {
        $error = "As senhas não coincidem.";
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
    <title>Redefinir Senha</title>
</head>
<body>
    <h2>Redefinir Senha</h2>
    <?php if (isset($error)) { ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php } elseif (isset($success)) { ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php } ?>
    <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
        <label for="password">Nova Senha:</label><br>
        <input type="password" id="password" name="password" required><br>
        <label for="confirm_password">Confirme a Nova Senha:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>
        <input type="submit" name="reset_password" value="Redefinir Senha">
    </form>
</body>
</html>
