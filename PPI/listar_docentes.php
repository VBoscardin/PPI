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

// Excluir docente
if (isset($_POST['excluir_id'])) {
    $excluirId = $_POST['excluir_id'];
    $erro = false; // Variável para rastrear erros

    // Iniciar uma transação
    $conn->begin_transaction();

    try {
        // 1. Remover as associações de disciplinas do docente
        $stmt = $conn->prepare("DELETE FROM docentes_disciplinas WHERE docente_id = ?");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir associações de disciplinas: " . $stmt->error);
        }
        $stmt->close();

        // 2. Remover o docente da tabela de docentes
        $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir o docente: " . $stmt->error);
        }
        $stmt->close();

        // 3. Remover informações do usuário (se necessário) - isso é opcional e depende de como você deseja gerenciar os usuários.
        // $stmt = $conn->prepare("DELETE FROM usuarios WHERE email = ?");
        // $stmt->bind_param("s", $emailDoDocente); // Obtenha o email do docente se necessário
        // $stmt->execute();
        // $stmt->close();

        // Se tudo ocorrer bem, confirmar a transação
        $conn->commit();
        header("Location: listar_docentes.php");
        exit();

    } catch (Exception $e) {
        // Em caso de erro, reverter a transação
        $conn->rollback();
        $erro = $e->getMessage();
    }
}



// Atualizar docente
if (isset($_POST['salvar_edicao'])) {
    $editarId = $_POST['editar_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $disciplinasSelecionadas = $_POST['disciplinas'] ?? [];

    // Atualizar dados do docente
    $stmt = $conn->prepare("UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $cpf, $editarId);
    $stmt->execute();
    $stmt->close();

    // Atualizar disciplinas do docente
    $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $editarId");
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
    $disciplinasDoDocenteQuery = $conn->query("SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = $editarId");
    while ($row = $disciplinasDoDocenteQuery->fetch_assoc()) {
        $disciplinasDocente[] = $row['disciplina_id'];
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
            <div class="separator mt-3 mb-3"></div>
            <button onclick="location.href='sair.php'" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
        </div>
        <div class="col-md-9">
            <h1>Listagem de Docentes</h1>
            <div class="alert alert-danger" role="alert" style="<?php echo empty($erro) ? 'display:none;' : ''; ?>">
                <?php echo $erro; ?>
            </div>
            <div class="alert alert-success" role="alert" style="<?php echo empty($mensagem) ? 'display:none;' : ''; ?>">
                <?php echo $mensagem; ?>
            </div>
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
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['nome']; ?></td>
                            <td>
                                <img src="<?php echo $row['foto_perfil']; ?>" alt="Foto de <?php echo $row['nome']; ?>" width="50">
                            </td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['cpf']; ?></td>
                            <td><?php echo $row['disciplinas'] ?: 'Nenhuma'; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="editar_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="exibir_edicao" class="btn btn-warning">
                                        Editar
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="excluir_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="excluir" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir o vínculo deste docente com as disciplinas?')">
                                        Excluir
                                    </button>
                                    </td>
                                </tr>

                                <!-- Modal Editar -->
                                <div class="modal fade" id="editarModal<?php echo $docente['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $docente['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-white">
                                                <h5 class="modal-title" id="editarModalLabel<?php echo $docente['id']; ?>"><i class="fas fa-edit"></i> Editar Docente</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="listar_docentes.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="editar_id" value="<?php echo htmlspecialchars($docente['id']); ?>">
                                                    <div class="mb-3">
                                                        <label for="nome" class="form-label">Nome:</label>
                                                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($docente['nome']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email:</label>
                                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($docente['email']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="cpf" class="form-label">CPF:</label>
                                                        <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($docente['cpf']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Disciplinas:</label><br>
                                                        <?php foreach ($disciplinasTurmas as $disciplina): ?>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" id="disciplina<?php echo htmlspecialchars($disciplina['disciplina_id']); ?>" name="disciplinas[]" value="<?php echo htmlspecialchars($disciplina['disciplina_id']); ?>" <?php echo in_array($disciplina['disciplina_id'], $disciplinasDocente) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="disciplina<?php echo htmlspecialchars($disciplina['disciplina_id']); ?>">
                                                                    <?php echo htmlspecialchars($disciplina['disciplina_nome']) . ' - Turma: ' . htmlspecialchars($disciplina['turma_numero']) . ', Ano: ' . htmlspecialchars($disciplina['turma_ano']); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="salvar_edicao" class="btn btn-success">Salvar Alterações</button>
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
            <form action="listar_docentes.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="excluir_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                    <p>Tem certeza que deseja excluir o docente "<strong><?php echo htmlspecialchars($row['nome']); ?></strong>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
                </div>
            </form>
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