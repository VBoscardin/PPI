<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php'; // Inclua o arquivo de configuração

// Verificar conexão
if ($conn->connect_error) {
    die('Conexão falhou: ' . $conn->connect_error);
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Conectar ao banco de dados
$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

// Função para cadastrar docente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_docente'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $siape = $_POST['siape'];
    $senha = $_POST['senha'];
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];

    // Verificar se os campos não estão vazios
    if (!empty($nome) && !empty($email) && !empty($siape) && !empty($senha)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Caminho para upload da foto de perfil
        $upload_dir = 'uploads/';
        $foto_perfil_path = '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $foto_perfil_name = basename($_FILES['photo']['name']);
            $foto_perfil_path = $upload_dir . $foto_perfil_name;
            
            if ($_FILES['photo']['size'] > 5000000) {
                $_SESSION['mensagem_erro'] = 'Erro: O arquivo é muito grande!';
            } else {
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $foto_perfil_path)) {
                    $_SESSION['mensagem_erro'] = 'Erro ao fazer upload da foto!';
                    $foto_perfil_path = '';
                }
            }
        }

        // Verificar se o email já está registrado na tabela docentes
        $stmt_docentes_check = $mysqli->prepare('SELECT id FROM docentes WHERE email = ?');
        $stmt_docentes_check->bind_param('s', $email);
        $stmt_docentes_check->execute();
        $stmt_docentes_check->store_result();

        if ($stmt_docentes_check->num_rows > 0) {
            $_SESSION['mensagem_erro'] = 'O email já está registrado como docente!';
        } else {
            // Verificar se o email já está registrado na tabela usuarios
            $stmt_usuarios_check = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt_usuarios_check->bind_param('s', $email);
            $stmt_usuarios_check->execute();
            $stmt_usuarios_check->store_result();

            if ($stmt_usuarios_check->num_rows > 0) {
                $_SESSION['mensagem_erro'] = 'O email já está registrado como usuário!';
            } else {
                // Inserir o docente na tabela docentes
                $stmt_docente = $mysqli->prepare('INSERT INTO docentes (nome, email, siape, senha) VALUES (?, ?, ?, ?)');
                $stmt_docente->bind_param('ssss', $nome, $email, $siape, $senha_hash);

                if ($stmt_docente->execute()) {
                    $docente_id = $stmt_docente->insert_id;

                    // Inserir o usuário na tabela usuarios com a foto de perfil
                    $username = $nome;
                    $tipo = 'docente';

                    $stmt_usuario = $mysqli->prepare('INSERT INTO usuarios (username, email, password_hash, tipo, foto_perfil) VALUES (?, ?, ?, ?, ?)');
                    $stmt_usuario->bind_param('sssss', $username, $email, $senha_hash, $tipo, $foto_perfil_path);

                    if ($stmt_usuario->execute()) {
                        $_SESSION['mensagem_sucesso'] = 'Docente cadastrado com sucesso!';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao cadastrar usuário: ' . $stmt_usuario->error;
                    }

                    $stmt_usuario->close();
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar docente: ' . $stmt_docente->error;
                }

                // Associar disciplinas ao docente
                if (!empty($disciplinas)) {
                    foreach ($disciplinas as $disciplina_id) {
                        $stmt_disciplina = $mysqli->prepare('INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)');
                        $stmt_disciplina->bind_param('ii', $docente_id, $disciplina_id);
                        $stmt_disciplina->execute();
                        $stmt_disciplina->close();
                    }
                }

                $stmt_docente->close();
            }

            $stmt_usuarios_check->close();
        }

        $stmt_docentes_check->close();
    } else {
        $_SESSION['mensagem_erro'] = 'Todos os campos são obrigatórios!';
    }

    header("Location: cadastrar_docente.php"); // Redirecionar para evitar reenvio do formulário
    exit();
}

// Obter lista de disciplinas para checkboxes
$query = 'SELECT disciplinas.id, disciplinas.nome AS disciplina_nome, cursos.nome AS curso_nome,
          turmas_disciplinas.turma_numero
          FROM disciplinas
          JOIN cursos ON disciplinas.curso_id = cursos.id
          LEFT JOIN turmas_disciplinas ON turmas_disciplinas.disciplina_id = disciplinas.id';
$disciplinas_result = $mysqli->query($query);
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}


$disciplinas_result = $mysqli->query($query);
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Docente</title>
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
                 <!-- Botão expansível "Listar" -->
                 <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#list-menu" aria-expanded="false" aria-controls="list-menu">
                    <i id="toggle-icon" class="fas fa-list"></i> Listar
                </button>

                <!-- Menu expansível para listar opções -->
                <div id="list-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='listar_administradores.php'">
                            <i class="fas fa-list"></i> Administradores
                        </button>
                        <button onclick="location.href='listar_cursos.php'">
                            <i class="fas fa-list"></i> Cursos
                        </button>
                        <button onclick="location.href='listar_discentes.php'">
                            <i class="fas fa-list"></i> Discentes
                        </button>
                        <button onclick="location.href='listar_disciplinas.php'">
                            <i class="fas fa-list"></i> Disciplinas
                        </button>
                        <button onclick="location.href='listar_docentes.php'">
                            <i class="fas fa-list"></i> Docentes
                        </button>
                        <button onclick="location.href='listar_setores.php'">
                            <i class="fas fa-list"></i> Setores
                        </button>
                        <button onclick="location.href='listar_turmas.php'">
                            <i class="fas fa-list"></i> Turmas
                        </button>
                    </div>
                </div>
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
                        <div class="title ms-3">Cadastrar Docente</div>
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
                    <div class="card shadow">
                        <div class="card-body">
                            <form action="cadastrar_docente.php" method="post" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome:</label>
                                        <input type="text" id="nome" name="nome" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email:</label>
                                        <input type="email" id="email" name="email" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="siape" class="form-label">Siape:</label>
                                        <input type="text" id="siape" name="siape" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="senha" class="form-label">Senha:</label>
                                        <input type="password" id="senha" name="senha" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="photo" class="form-label">Foto de Perfil:</label>
                                        <input type="file" id="photo" name="photo" class="form-control">
                                    </div>
                                </div>
                                <hr>                       
                                <fieldset class="mb-3">
                                    <legend>Disciplinas Associadas</legend>
                                    <div class="row">
                                        <?php foreach ($disciplinas as $disciplina): ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input type="checkbox" id="disciplina-<?php echo $disciplina['id']; ?>" name="disciplinas[]" value="<?php echo $disciplina['id']; ?>" class="form-check-input">
                                                    <label for="disciplina-<?php echo $disciplina['id']; ?>" class="form-check-label">
                                                        <?php echo htmlspecialchars($disciplina['disciplina_nome']); ?> 
                                                        (<?php echo htmlspecialchars($disciplina['curso_nome']); ?>) - 
                                                        Turma: <?php echo htmlspecialchars($disciplina['turma_numero']) ? htmlspecialchars($disciplina['turma_numero']) : 'Sem turma'; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>


                                <hr>                   
                                <button type="submit" name="cadastrar_docente" class="btn btn-light">Cadastrar Docente</button>

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
