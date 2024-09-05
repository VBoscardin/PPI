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

$conn->close();

$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

// Função para cadastrar disciplina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_disciplina'])) {
    $curso_id = $_POST['curso_id'];
    $nome = $_POST['nome'];

    if (!empty($curso_id) && !empty($nome)) {
        // Verificar se a disciplina já existe
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM disciplinas WHERE curso_id = ? AND nome = ?');
        $stmt->bind_param('is', $curso_id, $nome);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $_SESSION['mensagem_erro'] = 'Disciplina já cadastrada para este curso!';
        } else {
            // Preparar e executar a inserção do novo curso
            $stmt = $mysqli->prepare('INSERT INTO disciplinas (curso_id, nome) VALUES (?, ?)');
            $stmt->bind_param('is', $curso_id, $nome);

            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = 'Disciplina cadastrada com sucesso!';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao cadastrar disciplina: ' . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        $_SESSION['mensagem_erro'] = 'Todos os campos são obrigatórios!';
    }

    header("Location: cadastrar_disciplina.php"); // Redirecionar para evitar reenvio do formulário
    exit();
}

// Obter lista de cursos para o menu suspenso
$cursos_result = $mysqli->query('SELECT id, nome FROM cursos');
$cursos = [];
if ($cursos_result) {
    while ($row = $cursos_result->fetch_assoc()) {
        $cursos[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Disciplina</title>
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
                <div class="title ms-3">Cadastrar Disciplina</div>
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
                    

                    <form action="cadastrar_disciplina.php" method="post">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Disciplina:</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="curso" class="form-label">Curso:</label>
                            <select id="curso" name="curso_id" class="form-select" required>
                                <option value="">Selecione um Curso</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo htmlspecialchars($curso['id']); ?>"><?php echo htmlspecialchars($curso['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="cadastrar_disciplina" class="btn btn-light">Cadastrar Disciplina</button>
                        
                            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                            <div id="mensagem-sucesso" class="alert alert-success">
                                <?php echo $_SESSION['mensagem_sucesso']; ?>
                            </div>
                            <?php unset($_SESSION['mensagem_sucesso']); ?>
                        <?php elseif (isset($_SESSION['mensagem_erro'])): ?>
                            <div id="mensagem-erro" class="alert alert-danger">
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
