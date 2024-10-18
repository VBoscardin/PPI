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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar</title>
</head>
<body>
    <h1>Página de Cadastro</h1>
    <p>
        <a href="cadastrar_adm.php">Cadastrar Administrador</a>
    </p>
    <p>
        <a href="cadastrar_curso.php">Cadastrar Curso</a>
    </p>
    <p>
        <a href="cadastrar_disciplina.php">Cadastrar Disciplina</a>
    </p>
    <p>
        <a href="cadastrar_docente.php">Cadastrar Docente</a>
    </p>
    <p>
        <a href="cadastrar_setor.php">Cadastrar Setor</a>
    </p>
    <p>
        <a href="cadastrar_turma.php">Cadastrar Turma</a>
    </p>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
