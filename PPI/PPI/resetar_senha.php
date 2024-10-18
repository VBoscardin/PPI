<?php
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Conectar ao banco de dados
    $conn = new mysqli("localhost", "root", "", "bd_ppi");

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Verificar o token
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $novaSenha = password_hash($_POST['password'], PASSWORD_BCRYPT);

            // Atualizar a senha no banco de dados e limpar o token
            $stmt->bind_result($userId);
            $stmt->fetch();
            $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->bind_param("si", $novaSenha, $userId);
            $stmt->execute();

            echo "Senha alterada com sucesso.";
        } else {
            // Exibir o formulário para redefinir a nova senha
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
                <form method="POST" action="">
                    <label for="password">Nova Senha:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <input type="submit" value="Redefinir Senha">
                </form>
            </body>
            </html>
            <?php
        }
    } else {
        echo "Token inválido ou expirado.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Token não fornecido.";
}
?>
