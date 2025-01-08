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

// Mensagens
$sucesso = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['erro']);

// Editar curso
if (isset($_POST['update_curso'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $coordenador_email = $_POST['coordenador'];

    // Obter o ID do coordenador usando o email selecionado
    $stmt = $conn->prepare("SELECT id FROM docentes WHERE email = ?");
    $stmt->bind_param("s", $coordenador_email);
    $stmt->execute();
    $stmt->bind_result($coordenador_id);
    $stmt->fetch();
    $stmt->close();

    // Atualizar o curso com o novo nome e coordenador
    $stmt = $conn->prepare("UPDATE cursos SET nome = ?, coordenador = ? WHERE id = ?");
    $stmt->bind_param("sii", $nome, $coordenador_id, $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Curso atualizado com sucesso!';
        header("Location: listar_cursos.php"); // Redireciona para evitar envio duplo
        exit();
    } else {
        $erro = "Erro ao atualizar curso: " . $conn->error;
    }
    $stmt->close();
}

// Excluir curso
if (isset($_POST['delete_curso'])) {
    $id = $_POST['id'];

    // Excluir curso do banco de dados
    $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Curso excluído com sucesso!';
        header("Location: listar_cursos.php"); // Redireciona para evitar envio duplo
        exit();
    } else {
        $erro = "Erro ao excluir curso: " . $conn->error;
    }
    $stmt->close();
}

// Consulta SQL para listar cursos com os nomes dos coordenadores
$sql = "
    SELECT c.id, c.nome AS curso_nome, d.nome AS coordenador_nome, d.email AS coordenador_email
    FROM cursos c
    LEFT JOIN docentes d ON c.coordenador = d.id
";

$result = $conn->query($sql);

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
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        #cursoTable td {
            background-color: white; /* Sem aspas no valor */
        }
    </style>
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
                    <!-- Exibir mensagens de sucesso e erro -->
                    <?php if (!empty($sucesso)): ?>
    <div id="mensagem-sucesso" class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($sucesso); ?>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div id="mensagem-erro" class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($erro); ?>
    </div>
<?php endif; ?>

                    <div class="card shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                        
                        </div>
                        
                        <?php if ($result->num_rows > 0): ?>
                            <table id="cursoTable" class="table table-bordered table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome do Curso</th>
                                        <th>Coordenador</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="courseTableBody">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($row['coordenador_nome']); ?></td>
                                            <td>
                                                <!-- Botões de ação com ícones estilizados lado a lado -->
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['id']; ?>">
                                                        <i class="fas fa-edit me-2"></i> Editar
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $row['id']; ?>">
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
                                                        <h5 class="modal-title" id="editarModalLabel"><i class="fas fa-edit"></i> Editar Curso</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="nome" class="form-label">Nome do Curso</label>
                                                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($row['curso_nome']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="coordenador" class="form-label">Coordenador</label>
                                                                <select name="coordenador" class="form-select" required>
                                                                    <option value="">Selecione um coordenador</option>
                                                                    <?php foreach ($docentes as $docente): ?>
                                                                        <option value="<?php echo htmlspecialchars($docente['email']); ?>" <?php echo ($docente['email'] === $row['coordenador_email']) ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($docente['username']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" name="update_curso" class="btn btn-success">Salvar Alterações</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Excluir -->
                                        <div class="modal fade" id="excluirModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="excluirModalLabel"><i class="fas fa-trash-alt"></i> Excluir Curso</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <p>Tem certeza que deseja excluir o curso "<strong><?php echo htmlspecialchars($row['curso_nome']); ?></strong>"?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <div class="d-flex gap-2 justify-content-center">
                                                                <button type="button" class="btn btn-secondary custom-btn" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" name="delete_curso" class="btn btn-danger custom-btn">Excluir</button>
                                                            </div>
                                                        </div>                
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        
                        <?php else: ?>
                            <p>Nenhum curso encontrado.</p>
                        <?php endif; ?>
                    </div>
                </div>


<!-- Script para o filtro de busca -->
<script>
    // Ocultar mensagens automaticamente após 5 segundos
    setTimeout(() => {
        const sucesso = document.getElementById('mensagem-sucesso');
        const erro = document.getElementById('mensagem-erro');
        if (sucesso) sucesso.style.display = 'none';
        if (erro) erro.style.display = 'none';
    }, 2000); // 5 segundos
</script>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>