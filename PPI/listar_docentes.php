<?php
require 'config.php';

// Função para buscar todos os docentes
function listarDocentes($conn) {
    $result = $conn->query("SELECT * FROM docentes");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Função para buscar todas as disciplinas com turma e ano
function obterTodasDisciplinasETurmas($conn) {
    $result = $conn->query("
        SELECT d.id AS disciplina_id, d.nome AS disciplina, t.numero AS turma_numero, t.ano AS turma_ano
        FROM disciplinas d
        JOIN turmas t ON d.turma_id = t.id
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}


// Função para obter disciplinas e turmas de um docente específico
function obterDisciplinasETurmas($conn, $docente_id) {
    $stmt = $conn->prepare("
        SELECT d.nome AS disciplina, t.numero AS turma_numero, t.ano AS turma_ano
        FROM docentes_disciplinas dd
        JOIN disciplinas d ON dd.disciplina_id = d.id
        JOIN turmas t ON dd.turma_numero = t.numero
        WHERE dd.docente_id = ?
    ");
    $stmt->bind_param("i", $docente_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Função para editar um docente
function editarDocente($conn, $id, $nome, $email, $cpf) {
    $stmt = $conn->prepare("UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $cpf, $id);
    return $stmt->execute();
}

// Função para excluir um docente
function excluirDocente($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Verificar se um formulário de edição ou exclusão foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cpf = $_POST['cpf'];
        editarDocente($conn, $id, $nome, $email, $cpf);
    } elseif (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        excluirDocente($conn, $id);
    }
}

// Obter a lista de docentes
$docentes = listarDocentes($conn);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Docentes</title>
</head>
<body>
    <h1>Docentes</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Foto</th>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>CPF</th>
                <th>Disciplinas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($docentes as $docente): ?>
                <?php 
                    $disciplinas = obterDisciplinasETurmas($conn, $docente['id']); 
                    $disciplinasIds = array_column($disciplinas, 'disciplina_id'); // IDs das disciplinas associadas
                ?>
                <tr>
                    <td>
                        <?php if (!empty($docente['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($docente['foto_perfil']) ?>" alt="Foto de <?= htmlspecialchars($docente['nome']) ?>" width="50" height="50">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($docente['id']) ?></td>
                    <td><?= htmlspecialchars($docente['nome']) ?></td>
                    <td><?= htmlspecialchars($docente['email']) ?></td>
                    <td><?= htmlspecialchars($docente['cpf']) ?></td>
                    <td>
                        <ul>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <li><?= htmlspecialchars($disciplina['disciplina']) ?> - Turma <?= htmlspecialchars($disciplina['turma_numero']) ?>, Ano <?= htmlspecialchars($disciplina['turma_ano']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <form action="" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($docente['id']) ?>">
                            <button type="submit" name="excluir" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
                        </form>
                        <button onclick="document.getElementById('editForm<?= $docente['id'] ?>').style.display='block'">Editar</button>
                        <div id="editForm<?= $docente['id'] ?>" style="display:none;">
                            <form action="" method="post">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($docente['id']) ?>">
                                <input type="text" name="nome" value="<?= htmlspecialchars($docente['nome']) ?>" required>
                                <input type="email" name="email" value="<?= htmlspecialchars($docente['email']) ?>" required>
                                <input type="text" name="cpf" value="<?= htmlspecialchars($docente['cpf']) ?>" required>
                                
                                <!-- Disciplinas como Checkboxes -->
                                <h4>Disciplinas:</h4>
                                <?php foreach ($disciplinasETurmas as $disciplina): ?>
                                    <label>
                                        <input type="checkbox" name="disciplinas[]" value="<?= htmlspecialchars($disciplina['disciplina_id']) ?>"
                                            <?php if (in_array($disciplina['disciplina_id'], $disciplinasIds)) echo 'checked'; ?>>
                                        <?= htmlspecialchars($disciplina['disciplina']) ?> - Turma <?= htmlspecialchars($disciplina['turma_numero']) ?>, Ano <?= htmlspecialchars($disciplina['turma_ano']) ?>
                                    </label><br>
                                <?php endforeach; ?>

                                <button type="submit" name="editar">Salvar</button>
                                <button type="button" onclick="document.getElementById('editForm<?= $docente['id'] ?>').style.display='none'">Cancelar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>