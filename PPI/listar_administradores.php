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

// Obter lista de administradores
$sql = "SELECT id, username, email, tipo, foto_perfil FROM usuarios WHERE tipo = 'administrador'";
$result = $conn->query($sql);

// Atualizar administrador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_adm'])) {
    $id = $_POST['id'];

    // Impedir exclusão do próprio usuário
    if ($id == $_SESSION['user_id']) {
        $_SESSION['mensagem_erro'] = 'Você não pode editar seu próprio usuário!';
        header("Location: listar_administradores.php");
        exit();
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $tipo = trim($_POST['tipo']);
    
    // Processar a foto de perfil
    $foto_perfil = '';
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $tmp_name = $_FILES['foto_perfil']['tmp_name'];
        $foto_perfil = basename($_FILES['foto_perfil']['name']);
        $upload_file = $upload_dir . $foto_perfil;

        // Verificar extensão da imagem
        $ext = pathinfo($foto_perfil, PATHINFO_EXTENSION);
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowed_types)) {
            $_SESSION['mensagem_erro'] = 'Formato de arquivo inválido. Apenas JPG, JPEG, PNG e GIF são permitidos.';
            header("Location: listar_administradores.php");
            exit();
        }

        if (!move_uploaded_file($tmp_name, $upload_file)) {
            $_SESSION['mensagem_erro'] = 'Erro ao carregar a foto de perfil.';
            header("Location: listar_administradores.php");
            exit();
        }
    } else {
        // Se não houve upload de arquivo, mantenha a foto atual
        $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($foto_perfil_atual);
        $stmt->fetch();
        $stmt->close();
        $foto_perfil = $foto_perfil_atual;
    }

    // Atualizar informações no banco de dados
    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, email = ?,  foto_perfil = ? WHERE id = ?");
    $stmt->bind_param('sssi', $username, $email, $foto_perfil, $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Administrador atualizado com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao atualizar administrador: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_administradores.php");
    exit();
}

// Excluir administrador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_adm'])) {
    $id = $_POST['id'];

    // Impedir exclusão do próprio usuário
    if ($id == $_SESSION['user_id']) {
        $_SESSION['mensagem_erro'] = 'Você não pode excluir seu próprio usuário!';
        header("Location: listar_administradores.php");
        exit();
    }

    // Excluir administrador do banco de dados
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Administrador excluído com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao excluir administrador: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: listar_administradores.php");
    exit();
}


// Obter o nome e a foto do perfil do administrador logado
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
    <title>Listar Administradores</title>
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
                        <div class="title ms-3">Listar e Editar Administradores</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador"  width="50">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador"  width="50">
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
                    <?php elseif (isset($_SESSION['mensagem_erro'])): ?>
                        <div id="mensagem-erro" class="alert alert-danger">
                            <?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow">
                        <div class="card-body">
                             <!-- Campo de Pesquisa -->
<div class="mb-3">
    <div class="row">
        <div class="col-md-6">
            <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por Nome do Administrador...">
        </div>
        <div class="col-md-3">
            <input type="text" id="filterEmail" class="form-control" placeholder="Filtrar por E-mail...">
        </div>
        
    </div>
</div>
                        <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
    <table id="admTable" class="table table-bordered table-hover table-sm align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nome de Usuário</th>
                <th>Email</th>
                <th>Foto de Perfil</th>
                <th>Ações</th>
                <p id="noResultsMessage" class="text-center text-danger" style="display:none;">Nenhum Administrador encontrado.</p>

            </tr>
        </thead>
        <tbody>
            
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="adm-row">
                <td class="adm-id"><?php echo $row['id']; ?></td>
                <td class="adm-nome"><?php echo htmlspecialchars($row['username']); ?></td>
                <td class="adm-email"><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php if (!empty($row['foto_perfil']) && file_exists('uploads/' . basename($row['foto_perfil']))): ?>
                        <img src="uploads/<?php echo htmlspecialchars(basename($row['foto_perfil'])); ?>" alt="Foto" class="img-thumbnail" width="50">
                    <?php else: ?>
                        <img src="imgs/admin-photo.png" alt="Foto" class="img-thumbnail" width="50">
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-warning btn-sm custom-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editarModal<?php echo $row['id']; ?>" 
                            data-id="<?php echo $row['id']; ?>"
                            data-username="<?php echo htmlspecialchars($row['username']); ?>"
                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                            data-foto="<?php echo htmlspecialchars($row['foto_perfil']); ?>">
                            <i class="fas fa-edit me-2"></i> Editar
                        </button>

                        <button class="btn btn-danger btn-sm custom-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#excluirModal<?php echo $row['id']; ?>">
                            <i class="fas fa-trash-alt me-2"></i> Excluir
                        </button>
                    </div>
                </td>
            </tr>

            <!-- Modal Editar -->
            <div class="modal fade" id="editarModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title" id="editarModalLabel"><i class="fas fa-edit"></i> Editar Administrador</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editForm" action="listar_administradores.php" method="post" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="editId">
                                <div class="mb-3">
                                    <label for="editUsername" class="form-label">Nome de Usuário</label>
                                    <input type="text" name="username" id="editUsername" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editEmail" class="form-label">Email</label>
                                    <input type="email" name="email" id="editEmail" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editFotoPerfil" class="form-label">Foto de Perfil</label>
                                    <input type="file" name="foto_perfil" id="editFotoPerfil" class="form-control">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update_adm" class="btn btn-success">Salvar</button>
                                    <a href="listar_administradores.php" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Excluir -->
            <div class="modal fade" id="excluirModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="excluirModalLabel"><i class="fas fa-trash-alt"></i> Excluir Administrador</h5>                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="listar_administradores.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <p>Tem certeza que deseja excluir o administrador "<strong><?php echo htmlspecialchars($row['username']); ?></strong>"?</p>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-secondary custom-btn" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="delete_adm" class="btn btn-danger custom-btn">Excluir</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Nenhum administrador encontrado.</div>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
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
        }, 3000);

        document.addEventListener('DOMContentLoaded', function () {
    var editModals = document.querySelectorAll('[data-bs-target^="#editarModal"]');
    editModals.forEach(function (button) {
        button.addEventListener('click', function () {
            var modalId = button.getAttribute('data-bs-target');
            var modal = document.querySelector(modalId);
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var email = button.getAttribute('data-email');
            var foto = button.getAttribute('data-foto');

            modal.querySelector('#editId').value = id;
            modal.querySelector('#editUsername').value = username;
            modal.querySelector('#editEmail').value = email;

            if (foto && foto !== '') {
                modal.querySelector('#editFotoPerfilPreview').src = 'uploads/' + foto;
            } else {
                modal.querySelector('#editFotoPerfilPreview').src = 'imgs/admin-photo.png';
            }
        });
    });
});

    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Captura os elementos de entrada de pesquisa
        const searchInput = document.getElementById('searchInput');
        const filterEmail = document.getElementById('filterEmail');

        const tableRows = document.querySelectorAll('.adm-row'); // Todas as linhas da tabela
        const noResultsMessage = document.getElementById('noResultsMessage'); // Mensagem de "Nenhum resultado encontrado"

        // Função de filtragem
        function filterTable() {
            const searchValue = searchInput.value.toLowerCase();
            const emailValue = filterEmail.value.toLowerCase();

            let hasResults = false;

            // Percorre todas as linhas da tabela
            tableRows.forEach(row => {
                // Captura os valores das colunas relevantes
                const nomeUsuario = row.querySelector('.adm-nome').textContent.toLowerCase();
                const email = row.querySelector('.adm-email').textContent.toLowerCase();

                // Verifica se os valores digitados correspondem ao conteúdo das colunas
                const matchesSearch = !searchValue || nomeUsuario.includes(searchValue);
                const matchesEmail = !emailValue || email.includes(emailValue);

                // Exibe ou oculta a linha com base na correspondência
                if (matchesSearch && matchesEmail) {
                    row.style.display = ""; // Mostra a linha
                    hasResults = true;
                } else {
                    row.style.display = "none"; // Oculta a linha
                }
            });

            // Exibe ou oculta a mensagem de "Nenhum resultado encontrado"
            if (noResultsMessage) {
                noResultsMessage.style.display = hasResults ? "none" : "block";
            }
        }

        // Adiciona os eventos de digitação (input) nos campos de pesquisa
        searchInput.addEventListener('input', filterTable);
        filterEmail.addEventListener('input', filterTable);
    });
</script>

</body>
</html>