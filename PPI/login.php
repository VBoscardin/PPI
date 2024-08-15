<?php
// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['senha'])) {
    // Recuperar a senha do formulário
    $senha = $_POST['senha'];
    
    // Gerar o hash da senha
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
    
    // Exibir o hash da senha
    echo "<h1>Hash da Senha</h1>";
    echo "<p><strong>Senha:</strong> " . htmlspecialchars($senha) . "</p>";
    echo "<p><strong>Hash:</strong> " . htmlspecialchars($senha_hash) . "</p>";
} else {
    // Formulário não foi enviado, exibir o formulário
    echo '<!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gerar Hash da Senha</title>
    </head>
    <body>
        <h1>Gerar Hash da Senha</h1>
        <form action="" method="post">
            <label for="senha">Senha:</label>
            <input type="text" id="senha" name="senha" required>
            <button type="submit">Gerar Hash</button>
        </form>
    </body>
    </html>';
}
?>
