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

include 'config.php';

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
                <div class="title ms-3">Cadastrar Setor</div>
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
                    <form action="cadastrar_setor.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome de Usuário:</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="local">Local:</label>
                            <input type="text" id="local" name="local" required>
                        </div>

                        <div class="mb-3">
                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="cpf">CPF:</label>
                            <input type="text" id="cpf" name="cpf" required>
                        </div>

                        <div class="mb-3">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="senha">Senha:</label>
                            <input type="password" id="senha" name="senha" required>
                        </div>

                        <div class="mb-3">
                            <label for="photo">Foto de Perfil:</label>
                            <input type="file" id="photo" name="photo" accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <input type="submit" name="cadastrar_setor" value="Cadastrar Setor">
                        </div>


                        <button type="submit" name="cadastrar_setor" class="btn btn-light">
                            Cadastrar Setor
                        </button>
                        <!-- Exibir mensagem de sucesso ou erro -->
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

