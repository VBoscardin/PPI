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

// Obter lista de docentes e suas fotos
$sql = "SELECT d.id, d.nome, d.email, d.cpf, u.foto_perfil
        FROM docentes d 
        JOIN usuarios u ON d.email = u.email";
$result = $conn->query($sql);

// Obter lista de disciplinas
$sql_disciplinas = "SELECT id, nome FROM disciplinas";
$result_disciplinas = $conn->query($sql_disciplinas);
$disciplinas = [];
if ($result_disciplinas) {
    while ($row = $result_disciplinas->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}

// Atualizar docente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_docente'])) {
    $id = $_POST['id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cpf = trim($_POST['cpf']);
    
    // Processar a foto de perfil
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $tmp_name = $_FILES['foto']['tmp_name'];
        $foto = basename($_FILES['foto']['name']);
        $upload_file = $upload_dir . $foto;

        if (!move_uploaded_file($tmp_name, $upload_file)) {
            $_SESSION['mensagem_erro'] = 'Erro ao carregar a foto de perfil.';
            header("Location: listar_docentes.php");
            exit();
        }
    } else {
        // Se não houve upload de arquivo, mantenha a foto atual
        $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($foto_atual);
        $stmt->fetch();
        $stmt->close();
        $foto = $foto_atual;
    }

    // Atualizar informações no banco de dados
    $stmt = $conn->prepare("UPDATE usuarios SET foto = ? WHERE email = ?");
    $stmt->bind_param('ss', $foto, $email);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Docente atualizado com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao atualizar docente: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_docentes.php");
    exit();
}

// Excluir docente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_docente'])) {
    $id = $_POST['id'];

    // Excluir docente do banco de dados
    $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Docente excluído com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao excluir docente: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_docentes.php"); // Redirecionar para evitar reenvio do formulário
    exit();
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
    <title>Listar Docentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
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
                <!-- Restante do menu ... -->
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
                        <div class="title ms-3">Listar e Editar Docentes</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))) : ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto de Perfil" class="profile-photo">
                                <?php else : ?>
                                    <img src="imgs/default-profile.png" alt="Foto de Perfil" class="profile-photo">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="container mt-4">
                        <!-- Exibir mensagens de sucesso e erro -->
                        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?php
                                    echo htmlspecialchars($_SESSION['mensagem_sucesso']);
                                    unset($_SESSION['mensagem_sucesso']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['mensagem_erro'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_SESSION['mensagem_erro']); unset($_SESSION['mensagem_erro']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow">
                            <div class="card-body">
                                <?php if ($result->num_rows > 0): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>CPF</th>
                                                <th>Foto</th>
                                                <th>Disciplinas</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['cpf']); ?></td>
                                                    <td>
                                                        <?php if (!empty($row['foto_perfil']) && file_exists('uploads/' . basename($row['foto_perfil']))): ?>
                                                            <img src="uploads/<?php echo htmlspecialchars(basename($row['foto_perfil'])); ?>" alt="Foto" class="img-thumbnail" style="max-width: 100px;">
                                                        <?php else: ?>
                                                            <img src="imgs/default-profile.png" alt="Foto" class="img-thumbnail" style="max-width: 100px;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form action="listar_docentes.php" method="post" class="d-inline">
                                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                                            <?php foreach ($disciplinas as $disciplina): ?>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="disciplinas[]" value="<?php echo htmlspecialchars($disciplina['id']); ?>" 
                                                                        <?php 
                                                                            // Lógica para verificar se a disciplina já está associada ao docente
                                                                            // Aqui você pode implementar a lógica que verifica se a disciplina está associada ao docente
                                                                            // Por exemplo:
                                                                            // echo in_array($disciplina['id'], $disciplinas_associadas) ? 'checked' : '';
                                                                        ?>>
                                                                    <label class="form-check-label">
                                                                        <?php echo htmlspecialchars($disciplina['nome']); ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                            <button type="submit" name="update_docente" class="btn btn-warning btn-sm mt-2">Editar</button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <!-- Botão Excluir -->
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $row['id']; ?>">
                                                            <i class="fas fa-trash-alt"></i> Excluir
                                                        </button>

                                                        <!-- Modal Excluir -->
                                                        <div class="modal fade" id="excluirModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog modal-md">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title" id="excluirModalLabel<?php echo $row['id']; ?>"><i class="fas fa-trash-alt"></i> Excluir Docente</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form action="listar_docentes.php" method="POST">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                            <p>Tem certeza que deseja excluir o docente "<strong><?php echo htmlspecialchars($row['nome']); ?></strong>"?</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                            <button type="submit" name="delete_docente" class="btn btn-danger">Excluir</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>Nenhum docente encontrado.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
                </body>
</html>
