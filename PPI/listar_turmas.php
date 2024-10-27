<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Excluir docente
if (isset($_POST['excluir_id'])) {
    $excluirId = $_POST['excluir_id'];
    $erro = false;

    $conn->begin_transaction();

    try {
        // Recuperar o email do docente para excluir o usuário associado
        $stmt = $conn->prepare("SELECT email FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->bind_result($emailDocente);
        $stmt->fetch();
        $stmt->close();

        // Excluir dados relacionados ao docente
        $stmt = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id IN (SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = ?)");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM docentes_disciplinas WHERE docente_id = ?");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM turmas WHERE professor_regente = ?");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->close();

        if ($emailDocente) {
            $stmtUsuarios = $conn->prepare("DELETE FROM usuarios WHERE email = ?");
            $stmtUsuarios->bind_param("s", $emailDocente);
            $stmtUsuarios->execute();
            $stmtUsuarios->close();
        }

        $conn->commit();
        header("Location: listar_docentes.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo $e->getMessage();
    }
}

// Atualizar docente
if (isset($_POST['salvar_edicao'])) {
    $editarId = $_POST['editar_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $disciplinasSelecionadas = $_POST['disciplinas'] ?? [];
    $foto = $_FILES['foto'] ?? null;

    // Atualizar dados básicos do docente
    $stmt = $conn->prepare("UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $cpf, $editarId);
    $stmt->execute();
    $stmt->close();

    // Excluir associações antigas de disciplinas
    try {
        $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $editarId");

        // Inserir novas associações de disciplinas
        foreach ($disciplinasSelecionadas as $disciplinaId) {
            $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $editarId, $disciplinaId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        echo "Erro ao atualizar disciplinas do docente: " . $e->getMessage();
    }

    // Atualizar a foto do docente
    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nomeFoto = "docente_{$editarId}." . $extensao;
        $caminhoFoto = 'uploads/' . $nomeFoto;

        if (move_uploaded_file($foto['tmp_name'], $caminhoFoto)) {
            // Atualizar caminho da foto na tabela usuarios
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE email = (SELECT email FROM docentes WHERE id = ?)");
            $stmt->bind_param("si", $nomeFoto, $editarId);
            if (!$stmt->execute()) {
                echo "Erro ao atualizar a foto na tabela usuarios: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Erro ao fazer upload da foto.";
        }
    }

    header("Location: listar_docentes.php");
    exit();
}

// Consultas de docentes e disciplinas
$sql = "SELECT d.id, d.nome, d.email, d.cpf, u.foto_perfil, GROUP_CONCAT(CONCAT(dis.nome, ' - Turma: ', td.turma_numero, ', Ano: ', td.turma_ano) SEPARATOR '; ') AS disciplinas
FROM docentes AS d
LEFT JOIN usuarios AS u ON d.email = u.email
LEFT JOIN docentes_disciplinas AS dd ON d.id = dd.docente_id
LEFT JOIN disciplinas AS dis ON dd.disciplina_id = dis.id
LEFT JOIN turmas_disciplinas AS td ON td.disciplina_id = dis.id
GROUP BY d.id";
$result = $conn->query($sql);

$disciplinasQuery = "SELECT dis.id AS disciplina_id, dis.nome AS disciplina_nome, td.turma_numero, td.turma_ano FROM disciplinas AS dis LEFT JOIN turmas_disciplinas AS td ON dis.id = td.disciplina_id";
$disciplinasResult = $conn->query($disciplinasQuery);
$disciplinasTurmas = [];
while ($row = $disciplinasResult->fetch_assoc()) {
    $disciplinasTurmas[] = $row;
}

$editarDocente = null;
$disciplinasDocente = [];
if (isset($_POST['exibir_edicao'])) {
    $editarId = $_POST['editar_id'];
    $editarDocente = $conn->query("SELECT * FROM docentes WHERE id = $editarId")->fetch_assoc();

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
    <title>Listagem de Docentes</title>
</head>
<body>
    <h1>Docentes</h1>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Foto</th>
                <th>Email</th>
                <th>CPF</th>
                <th>Disciplinas e Turmas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($docente = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $docente['id']; ?></td>
                <td><?php echo htmlspecialchars($docente['nome']); ?></td>
                <td>
                    <?php 
                    // Verifica se a foto do docente existe
                    $caminhoFoto = 'uploads/' . htmlspecialchars(basename($docente['foto_perfil']));
                    if (!empty($docente['foto_perfil']) && file_exists($caminhoFoto)): ?>
                        <img src="<?php echo $caminhoFoto; ?>" alt="Foto" width="50">
                    <?php else: ?>
                        <img src="imgs/docente-photo.png" alt="Foto padrão" width="50">
                        <p>Foto não disponível.</p> <!-- Mensagem de erro se a foto não estiver disponível -->
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($docente['email']); ?></td>
                <td><?php echo htmlspecialchars($docente['cpf']); ?></td>
                <td><?php echo $docente['disciplinas'] ? '<ul><li>' . implode('</li><li>', explode('; ', $docente['disciplinas'])) . '</li></ul>' : 'Nenhuma disciplina'; ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="editar_id" value="<?php echo $docente['id']; ?>">
                        <button name="exibir_edicao">Editar</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="excluir_id" value="<?php echo $docente['id']; ?>">
                        <button type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($editarDocente): ?>
        <h2>Editar Docente</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="editar_id" value="<?php echo $editarDocente['id']; ?>">
            <label>Nome:</label><input type="text" name="nome" value="<?php echo htmlspecialchars($editarDocente['nome']); ?>" required><br>
            <label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($editarDocente['email']); ?>" required><br>
            <label>CPF:</label><input type="text" name="cpf" value="<?php echo htmlspecialchars($editarDocente['cpf']); ?>" required><br>
            <label>Disciplinas:</label><br>
            <?php foreach ($disciplinasTurmas as $dt): ?>
                <input type="checkbox" name="disciplinas[]" value="<?php echo $dt['disciplina_id']; ?>" <?php echo in_array($dt['disciplina_id'], $disciplinasDocente) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($dt['disciplina_nome']) . " - Turma: {$dt['turma_numero']}, Ano: {$dt['turma_ano']}"; ?><br>
            <?php endforeach; ?>
            <label>Foto:</label><input type="file" name="foto"><br>
            <button type="submit" name="salvar_edicao">Salvar</button>
        </form>
    <?php endif; ?>
</body>
</html>
