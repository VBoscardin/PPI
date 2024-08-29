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

// Função para cadastrar setor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_setor'])) {
    $local = $_POST['local'];
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];
    $email = $_POST['email']; // Adicionando o e-mail

    // Verificar se os campos não estão vazios
    if (!empty($local) && !empty($nome) && !empty($cpf) && !empty($senha) && !empty($email)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Iniciar uma transação
        $conn->begin_transaction();
        
        try {
            // Inserir na tabela 'usuarios'
            $stmt_usuario = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, tipo) VALUES (?, ?, ?, ?)');
            $stmt_usuario->bind_param('ssss', $nome, $email, $senha_hash, $tipo);
            
            $tipo = 'setor'; // Definindo o tipo de usuário como 'setor'

            if (!$stmt_usuario->execute()) {
                throw new Exception('Erro ao cadastrar usuário: ' . $stmt_usuario->error);
            }

            // Inserir na tabela 'setores'
            $stmt_setor = $conn->prepare('INSERT INTO setores (local, nome, cpf, senha, email) VALUES (?, ?, ?, ?, ?)');
            $stmt_setor->bind_param('sssss', $local, $nome, $cpf, $senha_hash, $email);

            if (!$stmt_setor->execute()) {
                throw new Exception('Erro ao cadastrar setor: ' . $stmt_setor->error);
            }

            // Confirmar a transação
            $conn->commit();
            echo 'Setor e usuário cadastrados com sucesso!';
        } catch (Exception $e) {
            // Reverter a transação em caso de erro
            $conn->rollback();
            echo 'Erro: ' . $e->getMessage();
        }

        $stmt_setor->close();
        $stmt_usuario->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

$conn->close();
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
        
        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>
        
        <input type="submit" name="cadastrar_setor" value="Cadastrar Setor">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>

