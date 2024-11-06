<?php
require 'config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Editar Docente e suas Disciplinas
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_docente'])) {
    $docente_id = $_POST['docente_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];

    // Atualizar dados do docente
    $sql_edit = "UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("sssi", $nome, $email, $cpf, $docente_id);
    $stmt_edit->execute();

    // Atualizar disciplinas associadas
    $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $docente_id");
    $stmt_disciplinas = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)");
    foreach ($disciplinas as $disciplina_id) {
        $stmt_disciplinas->bind_param("ii", $docente_id, $disciplina_id);
        $stmt_disciplinas->execute();
    }
}

// Excluir Docente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_docente'])) {
    $docente_id = $_POST['docente_id'];

    $sql_delete = "DELETE FROM docentes WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $docente_id);
    $stmt_delete->execute();
}

// Obter docentes, disciplinas e turmas
$sql = "
    SELECT 
        d.id AS docente_id,
        d.nome AS docente_nome,
        d.email AS docente_email,
        d.cpf AS docente_cpf,
        u.foto_perfil,
        GROUP_CONCAT(DISTINCT CONCAT(di.nome, ' (', t.numero, '/', t.ano, ')') SEPARATOR ', ') AS disciplinas_turmas
    FROM 
        docentes d
    JOIN 
        usuarios u ON d.id = u.id
    LEFT JOIN 
        docentes_disciplinas dd ON d.id = dd.docente_id
    LEFT JOIN 
        disciplinas di ON dd.disciplina_id = di.id
    LEFT JOIN 
        turmas_disciplinas td ON di.id = td.disciplina_id
    LEFT JOIN 
        turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
    GROUP BY 
        d.id;
";

$result = $conn->query($sql);

// Obter lista de disciplinas disponíveis para a seleção
$disciplinas_query = "SELECT id, nome FROM disciplinas";
$disciplinas_result = $conn->query($disciplinas_query);
$disciplinas_options = [];
while ($disciplina = $disciplinas_result->fetch_assoc()) {
    $disciplinas_options[] = $disciplina;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Listar Docentes</title>
</head>
<body>
<div>
    <h2>Lista de Docentes</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Foto</th>
                <th>Nome</th>
                <th>Email</th>
                <th>CPF</th>
                <th>Disciplinas e Turmas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['docente_id']; ?></td>
                <td>
                    <?php if ($row['foto_perfil']): ?>
                        <img src="<?php echo $row['foto_perfil']; ?>" alt="Foto de <?php echo $row['docente_nome']; ?>" width="50" height="50">
                    <?php else: ?>
                        <img src="path/to/default/image.jpg" alt="Foto padrão" width="50" height="50">
                    <?php endif; ?>
                </td>
                <td><?php echo $row['docente_nome']; ?></td>
                <td><?php echo $row['docente_email']; ?></td>
                <td><?php echo $row['docente_cpf']; ?></td>
                <td>
                    <?php
                    $docente_id = $row['docente_id'];
                    $sql_disciplinas_docente = "
                        SELECT 
                            d.nome AS disciplina_nome,
                            t.numero AS turma_numero,
                            t.ano AS turma_ano
                        FROM 
                            docentes_disciplinas dd
                        JOIN 
                            disciplinas d ON dd.disciplina_id = d.id
                        JOIN 
                            turmas_disciplinas td ON d.id = td.disciplina_id
                        JOIN 
                            turmas t ON td.turma_numero = t.numero
                        WHERE 
                            dd.docente_id = ?
                    ";
                    $stmt_disciplinas = $conn->prepare($sql_disciplinas_docente);
                    $stmt_disciplinas->bind_param("i", $docente_id);
                    $stmt_disciplinas->execute();
                    $result_disciplinas_docente = $stmt_disciplinas->get_result();
                    
                    if ($result_disciplinas_docente->num_rows > 0) {
                        while ($disciplina_row = $result_disciplinas_docente->fetch_assoc()) {
                            echo htmlspecialchars($disciplina_row['disciplina_nome']) . " (Turma " . htmlspecialchars($disciplina_row['turma_numero']) . " - " . htmlspecialchars($disciplina_row['turma_ano']) . ")<br>";
                        }
                    } else {
                        echo "Nenhuma disciplina atribuída.";
                    }
                    ?>
                </td>
 
                <td>
                    <button onclick="document.getElementById('editModal<?php echo $row['docente_id']; ?>').style.display='block'">Editar</button>
                    <button onclick="document.getElementById('deleteModal<?php echo $row['docente_id']; ?>').style.display='block'">Excluir</button>
                </td>
            </tr>

            <!-- Modal de Edição -->
           <div id="editModal<?php echo $row['docente_id']; ?>" style="display: none;">
    <form method="post" action="">
        <input type="hidden" name="edit_docente" value="1">
        <input type="hidden" name="docente_id" value="<?php echo $row['docente_id']; ?>">
        <h3>Editar Docente</h3>
        <label>Nome:</label>
        <input type="text" name="nome" value="<?php echo $row['docente_nome']; ?>" required>
        <label>Email:</label>
        <input type="email" name="email" value="<?php echo $row['docente_email']; ?>" required>
        <label>CPF:</label>
        <input type="text" name="cpf" value="<?php echo $row['docente_cpf']; ?>" required>
        <label>Disciplinas e Turmas:</label><br>

        <?php
// Listar todas as disciplinas e turmas disponíveis
foreach ($disciplinas_options as $disciplina) {
    // Consultar as disciplinas já atribuídas ao docente
    $disciplinas_docente = [];
    $sql_check = "SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $row['docente_id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    while ($checked_row = $result_check->fetch_assoc()) {
        $disciplinas_docente[] = $checked_row['disciplina_id'];
    }

    // Verificar se a disciplina já está atribuída ao docente
    $checked = in_array($disciplina['id'], $disciplinas_docente) ? 'checked' : '';

    // Consultar turma e ano associada à disciplina
    $sql_turma = "
        SELECT 
            t.numero AS turma_numero, 
            t.ano AS turma_ano
        FROM 
            turmas_disciplinas td
        JOIN 
            turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
        WHERE 
            td.disciplina_id = ?
    ";
    $stmt_turma = $conn->prepare($sql_turma);
    $stmt_turma->bind_param("i", $disciplina['id']);
    $stmt_turma->execute();
    $result_turma = $stmt_turma->get_result();

    // Exibir a disciplina com a turma e ano
    $turma_info = 'N/A';
    if ($result_turma->num_rows > 0) {
        $turmas = [];
        while ($turma_row = $result_turma->fetch_assoc()) {
            $turmas[] = 'Turma ' . htmlspecialchars($turma_row['turma_numero']) . ' - ' . htmlspecialchars($turma_row['turma_ano']);
        }
        $turma_info = implode(', ', $turmas);
    }

    // Exibe o checkbox com o nome da disciplina, número da turma e ano
    echo '<input type="checkbox" name="disciplinas[]" value="' . $disciplina['id'] . '" ' . $checked . '> ';
    echo htmlspecialchars($disciplina['nome']) . ' (' . $turma_info . ')<br>';
}
?>


        <button type="submit">Salvar</button>
        <button type="button" onclick="document.getElementById('editModal<?php echo $row['docente_id']; ?>').style.display='none'">Cancelar</button>
    </form>
</div>

            <!-- Modal de Exclusão -->
            <div id="deleteModal<?php echo $row['docente_id']; ?>" style="display: none;">
                <form method="post" action="">
                    <input type="hidden" name="delete_docente" value="1">
                    <input type="hidden" name="docente_id" value="<?php echo $row['docente_id']; ?>">
                    <h3>Excluir Docente</h3>
                    <p>Tem certeza de que deseja excluir o docente <strong><?php echo $row['docente_nome']; ?></strong>?</p>
                    <button type="submit">Excluir</button>
                    <button type="button" onclick="document.getElementById('deleteModal<?php echo $row['docente_id']; ?>').style.display='none'">Cancelar</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" style="text-align: center;">Nenhum docente encontrado.</td>
        </tr>
    <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
