<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'administrador') {
    // Redirecionar para uma página de acesso negado ou qualquer outra página
    header("Location: f_login.php");
    exit();
}

// Conectar ao banco de dados, se necessário
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Código da página para administradores aqui
$stmt = $conn->prepare("SELECT username FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome);
$stmt->fetch();
$stmt->close();
?>
<?php
$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

// Conectar ao banco de dados
$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexão
if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

// Função para cadastrar setor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_setor'])) {
    $local = $_POST['local'];
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];

    // Verificar se os campos não estão vazios
    if (!empty($local) && !empty($nome) && !empty($cpf) && !empty($senha)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Preparar a consulta para evitar SQL Injection
        $stmt = $mysqli->prepare('INSERT INTO setores (local, nome, cpf, senha) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $local, $nome, $cpf, $senha_hash);

        if ($stmt->execute()) {
            echo 'Setor cadastrado com sucesso!';
        } else {
            echo 'Erro ao cadastrar setor: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Setor</title>
</head>
<body>
    <h1>Cadastrar Setor</h1>

    <form action="cadastrar_setor.php" method="post">
        <label for="local">Local:</label>
        <input type="text" id="local" name="local" required>
        
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" required>
        
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>
        
        <input type="submit" name="cadastrar_setor" value="Cadastrar Setor">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
