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
