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
    <link rel="stylesheet" href="css_formulario.css"> <!-- Incluindo o arquivo CSS -->
</head>
<script>
function toggleOptions() {
    var options = document.getElementById('cadastrar-opcoes');
    if (options.style.display === 'none' || options.style.display === '') {
        options.style.display = 'block';
    } else {
        options.style.display = 'none';
    }
}
</script>

<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_turmas.png" alt="Logo">
        </div>
        <button onclick="location.href='inicio.php'">Início</button>

        <!-- Label para "Cadastrar" estilizado como um botão -->
        <button class="cadastrar-button" onclick="toggleOptions()">Cadastrar</button>

        <div id="cadastrar-opcoes">
            <button onclick="location.href='cadastrar_adm.php'">Cadastrar Administrador</button>
            <button onclick="location.href='cadastrar_curso.php'">Cadastrar Curso</button>
            <button onclick="location.href='cadastrar_disciplina.php'">Cadastrar Disciplina</button>
            <button onclick="location.href='cadastrar_docente.php'">Cadastrar Docente</button>
            <button onclick="location.href='cadastrar_setor.php'">Cadastrar Setor</button>
            <button onclick="location.href='cadastrar_turma.php'">Cadastrar Turma</button>
            <button onclick="location.href='f_pagina_adm.php'">Voltar para Início</button>
        </div>

        <button onclick="location.href='gerar_boletim.php'">Gerar Boletim</button>
        <button onclick="location.href='gerar_slide.php'">Gerar Slide Pré Conselho</button>
        <button onclick="location.href='listar.php'">Listar</button>
        <button onclick="location.href='meu_perfil.php'">Meu Perfil</button>
        <button onclick="location.href='sair.php'">Sair</button>
    </div>
    <div id="content">
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
    </div>
</body>
</html>

