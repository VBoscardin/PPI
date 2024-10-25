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
$mensagem = '';
$erro = '';

// Editar disciplina
if (isset($_POST['update_disciplina'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $curso_id = $_POST['curso_id'];
    $turma_ids = isset($_POST['turma_id']) ? $_POST['turma_id'] : []; // Recebe múltiplas turmas
    $docente_id = $_POST['docente_id']; 

    // Atualizar a disciplina
    $stmt = $conn->prepare("UPDATE disciplinas SET nome = ?, curso_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $nome, $curso_id, $id);
    
    if ($stmt->execute()) {
        // Excluir associações anteriores na tabela turmas_disciplinas
        $stmt = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Inserir novas associações na tabela turmas_disciplinas
        foreach ($turma_ids as $turma_id) {
            // Obter o ano e o ano de ingresso da turma
            $stmt = $conn->prepare("SELECT ano, ano_ingresso FROM turmas WHERE numero = ?");
            $stmt->bind_param("i", $turma_id);
            $stmt->execute();
            $stmt->bind_result($turma_ano, $turma_ano_ingresso);
            $stmt->fetch();
            $stmt->close();

            // Inserir nova associação
            $stmt = $conn->prepare("INSERT INTO turmas_disciplinas (disciplina_id, turma_numero, turma_ano, turma_ano_ingresso) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $id, $turma_id, $turma_ano, $turma_ano_ingresso);
            $stmt->execute();
        }

        // Atualizar a associação na tabela docentes_disciplinas
        $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE docente_id = ?");
        $stmt->bind_param("iii", $docente_id, $id, $docente_id);
        $stmt->execute();

        $_SESSION['mensagem'] = 'Disciplina atualizada com sucesso!';
        header("Location: listar_disciplinas.php"); 
        exit();
    } else {
        $erro = "Erro ao atualizar disciplina: " . $conn->error;
    }
    $stmt->close();
}

// Excluir disciplina
if (isset($_POST['delete_disciplina'])) {
    $id = $_POST['id'];

    // Excluir associações na tabela turmas_disciplinas
    $stmt = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Excluir associações na tabela docentes_disciplinas
    $stmt = $conn->prepare("DELETE FROM docentes_disciplinas WHERE disciplina_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Excluir disciplina do banco de dados
    $stmt = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Disciplina excluída com sucesso!';
        header("Location: listar_disciplinas.php");
        exit();
    } else {
        $erro = "Erro ao excluir disciplina: " . $conn->error;
    }
    $stmt->close();
}

// Consulta SQL para listar disciplinas com os nomes dos cursos e docentes
$sql = "
SELECT d.id, d.nome AS disciplina_nome, c.nome AS curso_nome, c.id AS curso_id, 
       GROUP_CONCAT(DISTINCT t.numero SEPARATOR ', ') AS turma_numero, 
       GROUP_CONCAT(DISTINCT doc.nome SEPARATOR ', ') AS docente_nome,
       GROUP_CONCAT(DISTINCT dd.docente_id SEPARATOR ', ') AS docente_id
FROM disciplinas d
JOIN cursos c ON d.curso_id = c.id
LEFT JOIN turmas_disciplinas td ON td.disciplina_id = d.id
LEFT JOIN turmas t ON t.numero = td.turma_numero AND t.ano = td.turma_ano AND t.ano_ingresso = td.turma_ano_ingresso
LEFT JOIN docentes_disciplinas dd ON dd.disciplina_id = d.id
LEFT JOIN docentes doc ON doc.id = dd.docente_id
GROUP BY d.id
";
$result = $conn->query($sql);

// Verificando se a consulta retornou algum erro
if (!$result) {
    die("Erro na consulta SQL: " . $conn->error);
}

// Verificando se existem registros retornados
if ($result->num_rows == 0) {
    $erro = "Nenhuma disciplina encontrada.";
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Obter lista de turmas
$turmas = [];
$stmt = $conn->prepare("SELECT numero, ano FROM turmas");
$stmt->execute();
$stmt->bind_result($turma_numero, $turma_ano);
while ($stmt->fetch()) {
    $turmas[] = ['numero' => $turma_numero, 'ano' => $turma_ano];
}
$stmt->close();

// Obter lista de cursos
$cursos = [];
$stmt = $conn->prepare("SELECT id, nome FROM cursos");
$stmt->execute();
$stmt->bind_result($curso_id, $curso_nome);
while ($stmt->fetch()) {
    $cursos[] = ['id' => $curso_id, 'nome' => $curso_nome];
}
$stmt->close();

// Obter lista de docentes
$docentes = [];
$stmt = $conn->prepare("SELECT id, nome FROM docentes");
$stmt->execute();
$stmt->bind_result($docente_id, $docente_nome);
while ($stmt->fetch()) {
    $docentes[] = ['id' => $docente_id, 'nome' => $docente_nome];
}
$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Disciplinas</title>
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
                        <div class="title ms-3">Listar e Editar Disciplinas</div>
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
                                        <th>Disciplina</th>
                                        <th>Curso</th>
                                        <th>Turma</th>
                                        <th>Docente(s)</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['disciplina_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($row['turma_numero']); ?></td>
                                            <td><?php echo htmlspecialchars($row['docente_nome']); ?></td>
                                            <td>
                                                <!-- Botões de ação -->
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-trash-alt"></i> Excluir
                                                </button>
                                                
                                                <!-- Modal Editar -->
                                                <div class="modal fade" id="editarModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning text-white">
                                                                <h5 class="modal-title" id="editarModalLabel<?php echo $row['id']; ?>"><i class="fas fa-edit"></i> Editar Disciplina</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="listar_disciplinas.php" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="nome" class="form-label">Nome da Disciplina</label>
                                                                        <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($row['disciplina_nome']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="curso_id" class="form-label">Curso</label>
                                                                        <select name="curso_id" class="form-select" required>
                                                                            <option value="">Selecione um curso</option>
                                                                            <?php foreach ($cursos as $curso): ?>
                                                                                <option value="<?php echo $curso['id']; ?>" <?php echo $curso['id'] == $row['curso_id'] ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Turmas</label>
                                                                        <div>
                                                                            <?php foreach ($turmas as $turma): ?>
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" name="turma_id[]" value="<?php echo $turma['numero']; ?>" 
                                                                                        <?php echo in_array($turma['numero'], explode(',', $row['turma_numero'])) ? 'checked' : ''; ?>>
                                                                                    <label class="form-check-label">
                                                                                        Turma <?php echo htmlspecialchars($turma['numero']); ?> - Ano <?php echo htmlspecialchars($turma['ano']); ?>
                                                                                    </label>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="docente_id" class="form-label">Docente</label>
                                                                        <select name="docente_id" class="form-select" required>
                                                                            <option value="">Selecione um docente</option>
                                                                            <?php foreach ($docentes as $docente): ?>
                                                                                <option value="<?php echo $docente['id']; ?>" <?php echo in_array($docente['id'], explode(',', $row['docente_id'])) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars($docente['nome']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" name="update_disciplina" class="btn btn-success">Salvar Alterações</button>
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
                                                                <h5 class="modal-title" id="excluirModalLabel<?php echo $row['id']; ?>"><i class="fas fa-trash-alt"></i> Excluir Disciplina</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <p>Tem certeza que deseja excluir a disciplina "<strong><?php echo htmlspecialchars($row['disciplina_nome']); ?></strong>"?</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" name="delete_disciplina" class="btn btn-danger">Excluir</button>
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
                                <p>Nenhuma disciplina encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
