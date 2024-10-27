<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Mensagens
$mensagem = '';
$erro = '';

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Verifique se a variável $docente_id foi definida antes de usar
if (isset($docente_id) && !empty($_POST['disciplinas'])) {
    // Remover associações existentes antes de inserir novas
    $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $docente_id");

    foreach ($_POST['disciplinas'] as $disciplinaData) {
        list($disciplina_id, $turma_numero, $turma_ano) = explode('|', $disciplinaData);

        // Verifica se a disciplina existe
        $checkDisc = "SELECT COUNT(*) as count FROM disciplinas WHERE id = $disciplina_id";
        $resultCheck = mysqli_query($conn, $checkDisc);
        $exists = mysqli_fetch_assoc($resultCheck)['count'];

        // Se a disciplina existir, insira
        if ($exists > 0) {
            $insertDisc = "INSERT INTO docentes_disciplinas (docente_id, disciplina_id, turma_numero, turma_ano) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertDisc);
            $stmt->bind_param("iiss", $docente_id, $disciplina_id, $turma_numero, $turma_ano);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Disciplina ID $disciplina_id não existe.";
        }
    }
}


// Excluir docente e dados associados
if (isset($_POST['delete_docente'])) {
    $excluirId = $_POST['id']; // Certifique-se de que este campo seja 'id' no form
    $erro = false; // Variável para rastrear erros

// Iniciar uma transação
$conn->begin_transaction();
try {
    // Código para deletar e inserir disciplinas
    $conn->commit(); // Confirmar transação
} catch (Exception $e) {
    $conn->rollback(); // Reverter se houve erro
    $erro = $e->getMessage();
}

}


// Atualizar docente
if (isset($_POST['salvar_edicao'])) {
    $editarId = $_POST['editar_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $disciplinasSelecionadas = $_POST['disciplinas'] ?? []; // Obtém as disciplinas selecionadas

    // Atualizar dados do docente
    $stmt = $conn->prepare("UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $cpf, $editarId);
    $stmt->execute();
    $stmt->close();

    // Atualizar disciplinas do docente
$conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $editarId"); // Remove associações existentes
foreach ($disciplinasSelecionadas as $disciplinaId) {
    // Inserir a nova associação
    $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $editarId, $disciplinaId);
    $stmt->execute();
    $stmt->close();
}


    header("Location: listar_docentes.php");
    exit();
}

if (!empty($_POST['disciplinas'])) {
    foreach ($_POST['disciplinas'] as $disciplinaData) {
        list($disciplina_id, $turma_numero, $turma_ano) = explode('|', $disciplinaData);

        // Verifica se a disciplina existe
        $checkDisc = "SELECT COUNT(*) as count FROM disciplinas WHERE id = $disciplina_id";
        $resultCheck = mysqli_query($conn, $checkDisc);
        $exists = mysqli_fetch_assoc($resultCheck)['count'];

        // Se a disciplina existir, insira
        if ($exists > 0) {
            $insertDisc = "INSERT INTO docentes_disciplinas (docente_id, disciplina_id, turma_numero, turma_ano) VALUES ($docente_id, $disciplina_id, '$turma_numero', '$turma_ano')";
            mysqli_query($conn, $insertDisc);
        } else {
            echo "Disciplina ID $disciplina_id não existe.";
        }
    }
}


// Consulta para selecionar os docentes e as disciplinas/turmas que lecionam
$sql = "
    SELECT d.id, d.nome, d.email, d.cpf,
       u.foto_perfil,
       GROUP_CONCAT(CONCAT(dis.nome, ' - Turma: ', td.turma_numero, ', Ano: ', td.turma_ano) SEPARATOR '; ') AS disciplinas
FROM docentes AS d
LEFT JOIN usuarios AS u ON d.email = u.email
LEFT JOIN docentes_disciplinas AS dd ON d.id = dd.docente_id
LEFT JOIN disciplinas AS dis ON dd.disciplina_id = dis.id
LEFT JOIN turmas_disciplinas AS td ON td.disciplina_id = dis.id
GROUP BY d.id
";

$result = $conn->query($sql);

// Verifique se a consulta foi bem-sucedida e se há resultados
if ($result === false) {
    echo "Erro na consulta: " . $conn->error;
    exit();
}

// Obter todas as disciplinas e turmas disponíveis
$disciplinasQuery = "
    SELECT dis.id AS disciplina_id, dis.nome AS disciplina_nome, td.turma_numero, td.turma_ano
    FROM disciplinas AS dis
    LEFT JOIN turmas_disciplinas AS td ON dis.id = td.disciplina_id
";
$disciplinasResult = $conn->query($disciplinasQuery);
$disciplinasTurmas = [];
while ($row = $disciplinasResult->fetch_assoc()) {
    $disciplinasTurmas[] = $row;
}

// Se estiver editando um docente, obter suas informações
$editarDocente = null;
$disciplinasDocente = [];
if (isset($_POST['exibir_edicao'])) {
    $editarId = $_POST['editar_id'];
    $editarDocente = $conn->query("SELECT * FROM docentes WHERE id = $editarId")->fetch_assoc();

    // Consultar disciplinas que o docente leciona
    if ($editarDocente) {
        $disciplinasDoDocenteQuery = $conn->query("SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = $editarId");
        while ($row = $disciplinasDoDocenteQuery->fetch_assoc()) {
            $disciplinasDocente[] = $row['disciplina_id'];
        }
    } else {
        // Exibir mensagem de erro se o docente não for encontrado
        echo "Docente não encontrado.";
        exit();
    }
}


?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Docentes</title>
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
                <button onclick="location.href='meu_perfil.php'" class="btn btn-danger">
                    <i class="fas fa-user"></i> Meu  Perfil
                </button>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='sair.php'" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
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
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-success" role="alert">
                <?php
                    echo htmlspecialchars($_SESSION['mensagem']);
                    unset($_SESSION['mensagem']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($erro); ?>
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
                                <th>Foto</th>
                                <th>Email</th>
                                <th>CPF</th>
                                <th>Disciplinas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td>
                                        <img src="<?php echo $row['foto_perfil']; ?>" alt="Foto de <?php echo htmlspecialchars($row['nome']); ?>" width="50">
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['cpf']); ?></td>
                                    <td><?php echo htmlspecialchars($row['disciplinas']) ?: 'Nenhuma'; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['id']; ?>">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $row['id']; ?>">
                                            <i class="fas fa-trash-alt"></i> Excluir
                                        </button>

                                        <!-- Modal Editar -->
<!-- Modal Editar -->
<div class="modal fade" id="editarModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editarModalLabel<?php echo $row['id']; ?>"><i class="fas fa-edit"></i> Editar Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Formulário de Edição do Docente -->
            <form action="listar_docentes.php" method="POST">
                <input type="hidden" name="editar_id" value="<?php echo $row['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Docente</label>
                        <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($row['nome']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($row['cpf']); ?>" required>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Disciplinas</label>
    <div>
        <?php foreach ($disciplinasTurmas as $disciplina): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="disciplinas[]" value="<?php echo $disciplina['disciplina_id']; ?>"
                    <?php echo in_array($disciplina['disciplina_id'], $disciplinasDocente) ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <?php echo htmlspecialchars($disciplina['disciplina_nome'] . ' - Turma: ' . $disciplina['turma_numero'] . ', Ano: ' . $disciplina['turma_ano']); ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
</div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_edicao" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>



                                        <!-- Modal Excluir -->
<div class="modal fade" id="excluirModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="excluirModalLabel<?php echo $row['id']; ?>"><i class="fas fa-trash-alt"></i> Excluir Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <p>Tem certeza que deseja excluir o vínculo deste docente com as disciplinas?</p>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>