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

// Consultar o nome do setor e a foto de perfil
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

$conn->close();

$host = 'localhost';
$db = 'bd_ppi';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_disciplina'])) {
    // Capturando os dados do formulário
    $turma_numero = $_POST['turma_numero'];
    $nome = $_POST['nome'];

    // Verificando se os campos não estão vazios
    if (!empty($turma_numero) && !empty($nome)) {
        // Verificar se já existe uma disciplina com o mesmo nome para a turma selecionada
        $stmt = $mysqli->prepare('
            SELECT COUNT(*) 
            FROM disciplinas d 
            JOIN turmas_disciplinas td ON d.id = td.disciplina_id 
            WHERE td.turma_numero = ? AND d.nome = ?');
        $stmt->bind_param('is', $turma_numero, $nome);
        $stmt->execute();
        $stmt->bind_result($existing_count);
        $stmt->fetch();
        $stmt->close();

        if ($existing_count > 0) {
            $_SESSION['mensagem_erro'] = 'Já existe uma disciplina com esse nome para a turma selecionada!';
        } else {
            // Obter o curso_id associado à turma
            $stmt = $mysqli->prepare('SELECT curso_id FROM turmas WHERE numero = ?');
            $stmt->bind_param('i', $turma_numero);
            $stmt->execute();
            $stmt->bind_result($curso_id);
            $stmt->fetch();
            $stmt->close();

            if ($curso_id) {
                // Inserir a nova disciplina na tabela disciplinas
                $stmt = $mysqli->prepare('INSERT INTO disciplinas (curso_id, nome) VALUES (?, ?)');
                $stmt->bind_param('is', $curso_id, $nome);

                if ($stmt->execute()) {
                    $disciplina_id = $stmt->insert_id;

                    // Associar a disciplina à turma na tabela turmas_disciplinas
                    $stmt = $mysqli->prepare('INSERT INTO turmas_disciplinas (turma_numero, disciplina_id) VALUES (?, ?)');
                    $stmt->bind_param('ii', $turma_numero, $disciplina_id);

                    if ($stmt->execute()) {
                        $_SESSION['mensagem_sucesso'] = 'Disciplina cadastrada e associada à turma com sucesso!';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao associar disciplina à turma: ' . $stmt->error;
                    }
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar disciplina: ' . $stmt->error;
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Turma não encontrada!';
            }
        }
    } else {
        $_SESSION['mensagem_erro'] = 'Todos os campos são obrigatórios!';
    }

    header("Location: cadastrar_disciplina.php");
    exit();
}

// Obter lista de turmas para o menu suspenso
$turmas_result = $mysqli->query('SELECT numero, ano FROM turmas');
$turmas = [];
if ($turmas_result) {
    while ($row = $turmas_result->fetch_assoc()) {
        $turmas[] = $row;
    }
}

// Obter lista de disciplinas cadastradas
$disciplinas_result = $mysqli->query('SELECT d.nome, t.numero FROM disciplinas d JOIN turmas_disciplinas td ON d.id = td.disciplina_id JOIN turmas t ON td.turma_numero = t.numero');

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
    <title>Cadastrar Disciplina</title>
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
            <!-- Mensagens de sucesso ou erro -->
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['mensagem_sucesso']; ?>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['mensagem_erro']; ?>
                <?php unset($_SESSION['mensagem_erro']); ?>
            </div>
        <?php endif; ?>
                    <div class="card shadow">
                        <div class="card-body">
                        <form action="cadastrar_disciplina.php" method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome da Disciplina:</label>
                                    <input type="text" id="nome" name="nome" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                <label for="turma_numero" class="form-label">Número da Turma</label>
                        <select name="turma_numero" class="form-select" required>
                            <option value="">Selecione a Turma</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['numero']; ?>">
                                    <?php echo htmlspecialchars($turma['numero'] . " (" . $turma['ano'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                                </div>
                            </div>

                            <button type="submit" name="cadastrar_disciplina" class="btn btn-light">Cadastrar Disciplina</button>
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
