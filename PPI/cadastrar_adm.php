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

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_adm'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $photo = $_FILES['photo'];

    // Verificação de todos os campos obrigatórios
    if (!empty($username) && !empty($email) && !empty($password) && !empty($photo['name'])) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($email_existente);
        $stmt->fetch();
        $stmt->close();

        if ($email_existente > 0) {
            $message = 'Erro: Este email já está cadastrado!';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $upload_dir = 'uploads/';
            $foto_perfil_path = '';

            if ($photo['error'] === UPLOAD_ERR_OK) {
                $foto_perfil_name = basename($photo['name']);
                $foto_perfil_path = $upload_dir . $foto_perfil_name;
                
                if ($photo['size'] > 5000000) {
                    $message = 'Erro: O arquivo é muito grande!';
                } else {
                    if (!move_uploaded_file($photo['tmp_name'], $foto_perfil_path)) {
                        $message = 'Erro ao fazer upload da foto!';
                        $foto_perfil_path = '';
                    }
                }
            } else {
                $message = 'Erro: Nenhum arquivo foi enviado ou houve um erro no envio!';
            }

            if (empty($message)) {
                $stmt = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, tipo, foto_perfil) VALUES (?, ?, ?, "administrador", ?)');
                $stmt->bind_param('ssss', $username, $email, $password_hash, $foto_perfil_path);

                if ($stmt->execute()) {
                    $message = 'Administrador cadastrado com sucesso!';
                } else {
                    $message = 'Erro ao cadastrar administrador: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    } else {
        $message = 'Todos os campos são obrigatórios!';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_turmas.png" alt="Logo">
        </div>
        <button onclick="location.href='inicio.php'">Início</button>
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
        <?php if ($message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="cadastrar_adm.php" method="post" enctype="multipart/form-data">
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required><br>

            <label for="photo">Foto de Perfil:</label>
            <input type="file" id="photo" name="photo" accept="image/*" required><br>

            <input type="submit" name="cadastrar_adm" value="Cadastrar Administrador">
        </form>
    </div>

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
</body>
</html>
