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

// Função para cadastrar curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_curso'])) {
    $nome = $_POST['nome'];
    $coordenador = $_POST['coordenador'];

    // Preparar a consulta para evitar SQL Injection
    $stmt = $mysqli->prepare('INSERT INTO cursos (nome, coordenador) VALUES (?, ?)');
    $stmt->bind_param('ss', $nome, $coordenador);

    if ($stmt->execute()) {
        echo 'Curso cadastrado com sucesso!';
    } else {
        echo 'Erro ao cadastrar curso: ' . $stmt->error;
    }

    $stmt->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Curso</title>
</head>
<body>
    <h1>Cadastrar Curso</h1>

    <form action="cadastrar_curso.php" method="post">
        <label for="nome">Nome do Curso:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="coordenador">Coordenador:</label>
        <input type="text" id="coordenador" name="coordenador" required>
        
        <input type="submit" name="cadastrar_curso" value="Cadastrar Curso">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
