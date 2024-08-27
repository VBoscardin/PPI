<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

// Conectar ao banco de dados
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para cadastrar administrador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_adm'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Verificar se os campos não estão vazios
    if (!empty($username) && !empty($email) && !empty($password)) {
        // Verificar se o email já está cadastrado
        $stmt = $conn->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($email_existente);
        $stmt->fetch();
        $stmt->close();

        if ($email_existente > 0) {
            echo 'Erro: Este email já está cadastrado!';
        } else {
            // Hash da senha
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserir o administrador na tabela usuarios
            $stmt = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, tipo) VALUES (?, ?, ?, "administrador")');
            $stmt->bind_param('sss', $username, $email, $password_hash);

            if ($stmt->execute()) {
                echo 'Administrador cadastrado com sucesso!';
            } else {
                echo 'Erro ao cadastrar administrador: ' . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Administrador</title>
</head>
<body>
    <h1>Cadastrar Administrador</h1>

    <form action="cadastrar_adm.php" method="post">
        <label for="username">Nome de Usuário:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" name="cadastrar_adm" value="Cadastrar Administrador">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
