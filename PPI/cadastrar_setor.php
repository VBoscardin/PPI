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
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];

    // Verificar se os campos não estão vazios
    if (!empty($local) && !empty($nome) && !empty($email) && !empty($cpf) && !empty($senha)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Caminho para upload da foto de perfil
        $upload_dir = 'uploads/';
        $foto_perfil_path = '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $foto_perfil_name = basename($_FILES['photo']['name']);
            $foto_perfil_path = $upload_dir . $foto_perfil_name;
            
            if ($_FILES['photo']['size'] > 5000000) {
                echo 'Erro: O arquivo é muito grande!';
            } else {
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $foto_perfil_path)) {
                    echo 'Erro ao fazer upload da foto!';
                    $foto_perfil_path = '';
                }
            }
        }

        // Verificar se o email já está registrado na tabela setores
        $stmt_setores_check = $conn->prepare('SELECT id FROM setores WHERE email = ?');
        $stmt_setores_check->bind_param('s', $email);
        $stmt_setores_check->execute();
        $stmt_setores_check->store_result();

        if ($stmt_setores_check->num_rows > 0) {
            echo 'O email já está registrado como Setor!';
        } else {
            // Verificar se o email já está registrado na tabela usuarios
            $stmt_usuarios_check = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt_usuarios_check->bind_param('s', $email);
            $stmt_usuarios_check->execute();
            $stmt_usuarios_check->store_result();

            if ($stmt_usuarios_check->num_rows > 0) {
                echo 'O email já está registrado como Usuário!';
            } else {
                // Inserir o setor na tabela setores
                $stmt_setor = $conn->prepare('INSERT INTO setores (local, nome, email, cpf, senha) VALUES (?, ?, ?, ?, ?)');
                $stmt_setor->bind_param('sssss', $local, $nome, $email, $cpf, $senha_hash);

                if ($stmt_setor->execute()) {
                    $setor_id = $stmt_setor->insert_id;

                    // Inserir o usuário na tabela usuarios com a foto de perfil
                    $username = $nome;
                    $tipo = 'setor';

                    $stmt_usuario = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, tipo, foto_perfil) VALUES (?, ?, ?, ?, ?)');
                    $stmt_usuario->bind_param('sssss', $username, $email, $senha_hash, $tipo, $foto_perfil_path);

                    if ($stmt_usuario->execute()) {
                        echo 'Setor cadastrado com sucesso!';
                    } else {
                        echo 'Erro ao cadastrar usuário: ' . $stmt_usuario->error;
                    }

                    $stmt_usuario->close();
                } else {
                    echo 'Erro ao cadastrar setor: ' . $stmt_setor->error;
                }

                $stmt_setor->close();
            }

            $stmt_usuarios_check->close();
        }

        $stmt_setores_check->close();
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

    <form action="cadastrar_setor.php" method="post" enctype="multipart/form-data">
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

        <label for="photo">Foto de Perfil:</label>
        <input type="file" id="photo" name="photo" accept="image/*" required><br>
        
        <input type="submit" name="cadastrar_setor" value="Cadastrar Setor">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
