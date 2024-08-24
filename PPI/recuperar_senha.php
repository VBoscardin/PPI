<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Conectar ao banco de dados
    $conn = new mysqli("localhost", "root", "", "bd_ppi");

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Verificar se o email existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Gerar um token de recuperação
        $token = bin2hex(random_bytes(50));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Armazenar o token no banco de dados
        $stmt = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expires, $email);
        $stmt->execute();

        // Enviar o email com o link de recuperação
        $resetLink = "http://seusite.com/resetar_senha.php?token=" . $token;
        $mensagem = "Clique no link para redefinir sua senha: " . $resetLink;
        mail($email, "Recuperação de Senha", $mensagem);

        echo "Um email foi enviado com instruções para redefinir sua senha.";
    } else {
        echo "Email não encontrado.";
    }

    $stmt->close();
    $conn->close();
}
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
    <form method="POST" action="">
        <label for="email">Digite seu e-mail:</label><br>
        <input type="email" id="email" name="email" required><br><br>
        <input type="submit" value="Enviar">
    </form>
</body>
</html>
