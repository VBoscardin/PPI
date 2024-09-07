<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_setor'])) {
    $local = trim($_POST['local']);
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cpf = trim($_POST['cpf']);
    $senha = trim($_POST['senha']);
    $photo = $_FILES['photo'];

    // Verificação de todos os campos obrigatórios
    if (!empty($local) && !empty($nome) && !empty($email) && !empty($cpf) && !empty($senha) && !empty($photo['name'])) {
        // Verificar se o email já está registrado
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM setores WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($email_existente);
        $stmt->fetch();
        $stmt->close();

        if ($email_existente > 0) {
            $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: Este email já está registrado como Setor!';
        } else {
            // Verificar se o email já está registrado na tabela usuarios
            $stmt = $mysqli->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($email_existente);
            $stmt->fetch();
            $stmt->close();

            if ($email_existente > 0) {
                $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: Este email já está registrado como Usuário!';
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $upload_dir = 'uploads/';
                $foto_perfil_path = '';

                if ($photo['error'] === UPLOAD_ERR_OK) {
                    $foto_perfil_name = basename($photo['name']);
                    $foto_perfil_path = $upload_dir . $foto_perfil_name;

                    if ($photo['size'] > 5000000) {
                        $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: O arquivo é muito grande!';
                    } else {
                        if (!move_uploaded_file($photo['tmp_name'], $foto_perfil_path)) {
                            $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: Falha ao fazer upload da foto!';
                            $foto_perfil_path = '';
                        }
                    }
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: Nenhum arquivo enviado ou erro no envio!';
                }

                if (!isset($_SESSION['mensagem_erro'])) {
                    // Inserir o setor na tabela setores
                    $stmt = $mysqli->prepare('INSERT INTO setores (local, nome, email, cpf, senha) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $local, $nome, $email, $cpf, $senha_hash);

                    if ($stmt->execute()) {
                        $setor_id = $stmt->insert_id;

                        // Inserir o usuário na tabela usuarios com a foto de perfil
                        $tipo = 'setor';

                        $stmt = $mysqli->prepare('INSERT INTO usuarios (username, email, password_hash, tipo, foto_perfil) VALUES (?, ?, ?, ?, ?)');
                        $stmt->bind_param('sssss', $nome, $email, $senha_hash, $tipo, $foto_perfil_path);

                        if ($stmt->execute()) {
                            $_SESSION['mensagem_sucesso'] = 'Setor cadastrado com sucesso!';
                        } else {
                            $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: ' . $stmt->error;
                        }

                        $stmt->close();
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao cadastrar setor: ' . $stmt->error;
                    }
                }
            }
        }
    } else {
        $_SESSION['mensagem_erro'] = 'Todos os campos são obrigatórios!';
    }

    header("Location: cadastrar_setor.php"); // Redirecionar para evitar reenvio do formulário
    exit();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Setor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
            <div class="col-md-3 sidebar">
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_adm.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
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

            <!-- Conteúdo principal -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Cadastrar Setor</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador" class="profile-photo">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador" class="profile-photo">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mt-4">
                    <div class="card shadow-container">
                        <div class="card-body">
                            <form action="cadastrar_setor.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="local" class="form-label">Local:</label>
                                    <input type="text" id="local" name="local" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome:</label>
                                    <input type="text" id="nome" name="nome" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="cpf" class="form-label">CPF:</label>
                                    <input type="text" id="cpf" name="cpf" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail:</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha:</label>
                                    <input type="password" id="senha" name="senha" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Foto de Perfil:</label>
                                    <input type="file" id="photo" name="photo" accept="image/*" class="form-control" required>
                                </div>
                                <button type="submit" name="cadastrar_setor" class="btn btn-light">
                                    Cadastrar Setor
                                </button>
                                <!-- Exibir mensagem de sucesso ou erro -->
                                <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                                    <div id="mensagem-sucesso" class="alert alert-success mt-3">
                                        <?php echo $_SESSION['mensagem_sucesso']; ?>
                                    </div>
                                    <?php unset($_SESSION['mensagem_sucesso']); ?>
                                <?php elseif (isset($_SESSION['mensagem_erro'])): ?>
                                    <div id="mensagem-erro" class="alert alert-danger mt-3">
                                        <?php echo $_SESSION['mensagem_erro']; ?>
                                    </div>
                                    <?php unset($_SESSION['mensagem_erro']); ?>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remover a mensagem de sucesso ou erro após 5 segundos
        setTimeout(function() {
            var mensagemSucesso = document.getElementById('mensagem-sucesso');
            var mensagemErro = document.getElementById('mensagem-erro');
            if (mensagemSucesso) {
                mensagemSucesso.style.display = 'none';
            }
            if (mensagemErro) {
                mensagemErro.style.display = 'none';
            }
        }, 5000); // 5 segundos
    </script>
</body>
</html>