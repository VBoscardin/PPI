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
    $turma_id = $_POST['turma_id']; // Obter a turma selecionada
    $docente_id = $_POST['docente_id']; // Obter o docente selecionado

    // Atualizar a disciplina com o novo nome, curso, turma e docente
    $stmt = $conn->prepare("UPDATE disciplinas SET nome = ?, curso_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $nome, $curso_id, $id);
    
    if ($stmt->execute()) {
        // Atualizar a associação na tabela turmas_disciplinas
        $stmt = $conn->prepare("INSERT INTO turmas_disciplinas (disciplina_id, turma_numero) VALUES (?, ?) ON DUPLICATE KEY UPDATE turma_numero = ?");
        $stmt->bind_param("iii", $id, $turma_id, $turma_id);
        $stmt->execute();
        
        // Atualizar a associação na tabela docentes_disciplinas
        $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE docente_id = ?");
        $stmt->bind_param("iii", $docente_id, $id, $docente_id);
        $stmt->execute();

        $_SESSION['mensagem'] = 'Disciplina atualizada com sucesso!';
        header("Location: listar_disciplinas.php"); // Redireciona para evitar envio duplo
        exit();
    } else {
        $erro = "Erro ao atualizar disciplina: " . $conn->error;
    }
    $stmt->close();
}


// Excluir disciplina
if (isset($_POST['delete_disciplina'])) {
    $id = $_POST['id'];

    // Excluir disciplina do banco de dados
    $stmt = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Disciplina excluída com sucesso!';
        header("Location: listar_disciplinas.php"); // Redireciona para evitar envio duplo
        exit();
    } else {
        $erro = "Erro ao excluir disciplina: " . $conn->error;
    }
    $stmt->close();
}



// Consulta SQL para listar disciplinas com os nomes dos cursos e docentes
$sql = "
SELECT d.id, d.nome AS disciplina_nome, c.nome AS curso_nome, 
       t.numero AS turma_numero,
       GROUP_CONCAT(DISTINCT doc.nome SEPARATOR ', ') AS docente_nome
FROM disciplinas d
JOIN cursos c ON d.curso_id = c.id
LEFT JOIN turmas_disciplinas td ON td.disciplina_id = d.id
LEFT JOIN turmas t ON t.numero = td.turma_numero AND t.ano = td.turma_ano AND t.ano_ingresso = td.turma_ano_ingresso
LEFT JOIN docentes_disciplinas dd ON dd.disciplina_id = d.id
LEFT JOIN docentes doc ON doc.id = dd.docente_id
GROUP BY d.id
";



// Executando a consulta
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

// Obter lista de cursos e docentes para os seletores nos modais
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
                <!-- Sidebar omitido -->
            </div>
            <div class="col-md-9 main-content">
                <div class="container mt-4">
                    <!-- Exibir mensagens de sucesso e erro -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success" role="alert">
                            <?php
                                echo htmlspecialchars($_SESSION['mensagem']);
                                unset($_SESSION['mensagem']); // Limpar a mensagem após exibição
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
                                        <th>Turma</th> <!-- Adicione esta linha -->
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
                                            <td><?php echo htmlspecialchars($row['turma_numero']); ?></td> <!-- Adicione esta linha -->
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
                                                <div class="modal fade" id="editarModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning text-white">
                                                                <h5 class="modal-title" id="editarModalLabel"><i class="fas fa-edit"></i> Editar Disciplina</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="editar_disciplina.php" method="POST">
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
                                                                        <label for="turma_id" class="form-label">Turma</label>
                                                                        <select name="turma_id" class="form-select" required>
                                                                            <option value="">Selecione uma turma</option>
                                                                            <?php foreach ($turmas as $turma): ?>
                                                                                <option value="<?php echo $turma['numero']; ?>" <?php echo $turma['numero'] == $row['turma_numero'] ? 'selected' : ''; ?>>
                                                                                    Turma <?php echo htmlspecialchars($turma['numero']); ?> - Ano <?php echo htmlspecialchars($turma['ano']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="docente_id" class="form-label">Docente</label>
                                                                        <select name="docente_id" class="form-select" required>
                                                                            <option value="">Selecione um docente</option>
                                                                            <?php foreach ($docentes as $docente): ?>
                                                                                <option value="<?php echo $docente['id']; ?>" <?php echo $docente['id'] == $row['docente_id'] ? 'selected' : ''; ?>>
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
<div class="modal fade" id="excluirModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="excluirModalLabel"><i class="fas fa-trash-alt"></i> Excluir Disciplina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="listar_disciplinas.php" method="POST">
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
