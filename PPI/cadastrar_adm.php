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
// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

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
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="sidebar">
        <div class="separator mb-3"></div>
        <div class="signe-text">SIGNE</div>
        <div class="separator mt-3 mb-3"></div>
        <button onclick="location.href='f_pagina_adm.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
            <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
        </button>
        <!-- Menu expansível com Bootstrap -->
        <div id="expandable-menu" class="collapse expandable-container">
            <div class="expandable-menu">
                <button onclick="location.href='cadastrar_adm.php'">
                    <i class="fas fa-plus"></i> Cadastrar Administrador
                </button>
                <button onclick="location.href='cadastrar_curso.php'">
                    <i class="fas fa-plus"></i> Cadastrar Curso
                </button>
                <button onclick="location.href='cadastrar_disciplina.php'">
                    <i class="fas fa-plus"></i> Cadastrar Disciplina
                </button>
                <button onclick="location.href='cadastrar_docente.php'">
                    <i class="fas fa-plus"></i> Cadastrar Docente
                </button>
                <button onclick="location.href='cadastrar_setor.php'">
                    <i class="fas fa-plus"></i> Cadastrar Setor
                </button>
                <button onclick="location.href='cadastrar_turma.php'">
                    <i class="fas fa-plus"></i> Cadastrar Turma
                </button>
            </div>
        </div>
        <button onclick="location.href='gerar_boletim.php'">
            <i class="fas fa-file-alt"></i> Gerar Boletim
        </button>
        <button onclick="location.href='gerar_slide.php'">
            <i class="fas fa-sliders-h"></i> Gerar Slide Pré Conselho
        </button>
        <button onclick="location.href='listar.php'">
            <i class="fas fa-list"></i> Listar
        </button>
        <button onclick="location.href='meu_perfil.php'">
            <i class="fas fa-user"></i> Meu Perfil
        </button>
        <button class="btn btn-danger" onclick="location.href='sair.php'">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header-container">
                <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                <div class="title ms-3">Cadastrar Administrador</div>
                <div class="ms-auto d-flex align-items-center">
                    <div class="profile-info d-flex align-items-center">
                        <div class="profile-details me-2">
                            <span><?php echo htmlspecialchars($nome); ?></span>
                        </div>
                        <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                            <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                        <?php else: ?>
                            <img src="imgs/admin-photo.png" alt="Foto do Administrador">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container do Formulário -->
        <div class="container mt-4">
            <div class="card shadow-container">
                <div class="card-body">
                    <form action="cadastrar_adm.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome de Usuário:</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Senha:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="photo" class="form-label">Foto de Perfil:</label>
                            <input type="file" id="photo" name="photo" class="form-control" accept="image/*" required>
                        </div>

                        <button type="submit" name="cadastrar_adm" class="btn btn-light">
                            Cadastrar Administrador
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
