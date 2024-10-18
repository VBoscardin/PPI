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

// Obter lista de cursos com coordenador
$sql = "SELECT cursos.id, cursos.nome, usuarios.username AS coordenador, cursos.coordenador AS coordenador_email 
FROM cursos 
LEFT JOIN usuarios ON cursos.coordenador = usuarios.email
";

// Executar a consulta
$result = $conn->query($sql);

// Verificar se a consulta foi bem-sucedida
if (!$result) {
    $_SESSION['mensagem_erro'] = 'Erro ao obter cursos: ' . $conn->error;
    header("Location: listar_cursos.php");
    exit();
}

// Atualizar curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_curso'])) {
    $id = $_POST['id'];
    $nome = trim($_POST['nome']);
    $coordenador = trim($_POST['coordenador']);

    // Atualizar informações no banco de dados
    $stmt = $conn->prepare("UPDATE cursos SET nome = ?, coordenador = ? WHERE id = ?");
    $stmt->bind_param('ssi', $nome, $coordenador, $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Curso atualizado com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao atualizar curso: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_cursos.php");
    exit();
}

// Excluir curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_curso'])) {
    $id = $_POST['id'];

    // Excluir curso do banco de dados
    $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Curso excluído com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao excluir curso: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_cursos.php");
    exit();
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Obter lista de docentes para o seletor no modal
$docentes = [];
$stmt = $conn->prepare("SELECT username, email FROM usuarios WHERE tipo = 'docente'");
$stmt->execute();
$stmt->bind_result($username, $email);
while ($stmt->fetch()) {
    $docentes[] = ['username' => $username, 'email' => $email];
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
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
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#list-menu" aria-expanded="false" aria-controls="list-menu">
                    <i id="toggle-icon" class="fas fa-list"></i> Listar
                </button>
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

            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Listar e Editar Cursos</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador" width="50">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador" width="50">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mt-4">
                    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                        <div id="mensagem-sucesso" class="alert alert-success">
                            <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['mensagem_erro'])): ?>
                        <div id="mensagem-erro" class="alert alert-danger">
                            <?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?>
                        </div>
                    <?php endif; ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome do Curso</th>
                                <th>Coordenador</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['coordenador']); ?></td>

                                    <td>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                            Editar
                                        </button>
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id']; ?>">
                                            Excluir
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Editar -->
                                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel">Editar Curso</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="nome" class="form-label">Nome do Curso</label>
                                                        <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($row['nome']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="coordenador" class="form-label">Coordenador</label>
                                                        <select name="coordenador" class="form-select" required>
                                                            <option value="" disabled>Selecione o Coordenador</option>
                                                            <?php foreach ($docentes as $docente): ?>
                                                                <option value="<?php echo htmlspecialchars($docente['email']); ?>" <?php echo ($docente['email'] === $row['coordenador_email']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($docente['username']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="update_curso" class="btn btn-primary">Salvar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Excluir -->
                                <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel">Excluir Curso</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <p>Tem certeza que deseja excluir o curso "<?php echo htmlspecialchars($row['nome']); ?>"?</p>
                                                    <button type="submit" name="delete_curso" class="btn btn-danger">Excluir</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
