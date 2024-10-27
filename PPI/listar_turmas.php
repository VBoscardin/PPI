<?php
include 'config.php';

// Carregar todas as disciplinas para os checkboxes
$queryDisciplinas = "SELECT id, nome FROM disciplinas";
$resultDisciplinas = mysqli_query($conn, $queryDisciplinas);

// Consulta para buscar todos os docentes com a foto do perfil
$query = "
    SELECT d.id, d.nome, d.email, u.foto_perfil 
    FROM docentes d
    LEFT JOIN usuarios u ON d.id = u.id"; // Certifique-se que a chave 'usuario_id' é a correta
$resultDocentes = mysqli_query($conn, $query);

// Armazenar os docentes em um array
$docentes = [];
while ($row = mysqli_fetch_assoc($resultDocentes)) {
    $docentes[] = $row;
}

// Atualizar docente
if (isset($_POST['update'])) {
    $docente_id = $_POST['docente_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];

    $updateDocente = "UPDATE docentes SET nome='$nome', email='$email' WHERE id=$docente_id";
    mysqli_query($conn, $updateDocente);

    // Atualizar disciplinas associadas ao docente
    $deleteDisc = "DELETE FROM docentes_disciplinas WHERE docente_id=$docente_id";
    mysqli_query($conn, $deleteDisc);

    // Verifica se disciplinas foram selecionadas
    if (!empty($_POST['disciplinas'])) {
        foreach ($_POST['disciplinas'] as $disciplinaData) {
            // Explode the checkbox value into disciplina_id, turma_numero, and turma_ano
            list($disciplina_id, $turma_numero, $turma_ano) = explode('|', $disciplinaData);

            // Verifica se a disciplina existe
            $checkDisc = "SELECT COUNT(*) as count FROM disciplinas WHERE id = $disciplina_id";
            $resultCheck = mysqli_query($conn, $checkDisc);
            $exists = mysqli_fetch_assoc($resultCheck)['count'];

            // Se a disciplina existir, insira
            if ($exists > 0) {
                $insertDisc = "INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES ($docente_id, $disciplina_id)";
                mysqli_query($conn, $insertDisc);
            } else {
                // Você pode optar por lidar com a situação em que a disciplina não existe, se necessário
                echo "Disciplina ID $disciplina_id não existe.";
            }
        }
    }
}

// Deletar docente
if (isset($_GET['delete'])) {
    $docente_id = $_GET['delete'];

    // Excluir o docente da tabela 'usuarios' e da tabela 'docentes'
    $deleteUsuario = "DELETE FROM usuarios WHERE id=(SELECT usuario_id FROM docentes WHERE id=$docente_id)";
    mysqli_query($conn, $deleteUsuario);

    $deleteDocente = "DELETE FROM docentes WHERE id=$docente_id";
    mysqli_query($conn, $deleteDocente);

    header("Location: docentes.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Docentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Docentes</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Foto</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Disciplinas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($docentes as $row) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td>
                    <?php if (!empty($row['foto_perfil'])) { ?>
                        <img src="<?php echo $row['foto_perfil']; ?>" alt="Foto de <?php echo htmlspecialchars($row['nome']); ?>" width="50">
                    <?php } else { ?>
                        <img src="caminho/para/imagem/padrao.png" alt="Sem Foto" width="50">
                    <?php } ?>
                </td>
                <td><?php echo $row['nome']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <?php
                    // Buscar disciplinas associadas ao docente com os anos de ingresso
                    $docenteDisciplinas = "
                    SELECT d.nome AS disciplina_nome, t.numero, t.ano_ingresso 
                    FROM disciplinas d
                    INNER JOIN turmas_disciplinas td ON d.id = td.disciplina_id
                    INNER JOIN turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
                    INNER JOIN docentes_disciplinas dd ON dd.disciplina_id = d.id 
                    WHERE dd.docente_id = " . $row['id'];

                    $resultDocenteDisc = mysqli_query($conn, $docenteDisciplinas);

                    // Exibir disciplinas associadas com turma e ano de ingresso
                    while ($disciplina = mysqli_fetch_assoc($resultDocenteDisc)) {
                        echo $disciplina['disciplina_nome'] . " - Turma: " . $disciplina['numero'] . " - Ano de Ingresso: " . $disciplina['ano_ingresso'] . "<br>";
                    }
                    ?>
                </td>
                <td>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Editar</button>
                    <a href="docentes.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger">Deletar</a>
                </td>
            </tr>

            <!-- Modal de Edição -->
            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Editar Docente - <?php echo $row['nome']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="docente_id" value="<?php echo $row['id']; ?>">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" name="nome" class="form-control" value="<?php echo $row['nome']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="text" name="email" class="form-control" value="<?php echo $row['email']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label>Disciplinas e Turmas</label><br>
                                    <?php
                                    // Obter as disciplinas associadas ao docente
                                    $docenteDisciplinasQuery = "
                                        SELECT d.id AS disciplina_id, d.nome AS disciplina_nome, td.turma_numero, t.ano_ingresso 
                                        FROM docentes_disciplinas dd
                                        JOIN disciplinas d ON dd.disciplina_id = d.id
                                        JOIN turmas_disciplinas td ON td.disciplina_id = d.id
                                        JOIN turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
                                        WHERE dd.docente_id = " . $row['id'];

                                    $resultDocenteDisc = mysqli_query($conn, $docenteDisciplinasQuery);
                                    $docenteDiscIds = [];
                                    while ($disc = mysqli_fetch_assoc($resultDocenteDisc)) {
                                        $docenteDiscIds[] = [
                                            'disciplina_id' => $disc['disciplina_id'],
                                            'disciplina_nome' => $disc['disciplina_nome'],
                                            'turma_numero' => $disc['turma_numero'],
                                            'ano_ingresso' => $disc['ano_ingresso'],
                                        ];
                                    }

                                    // Listar todas as disciplinas como checkboxes
                                    mysqli_data_seek($resultDisciplinas, 0); // Resetar o ponteiro do resultado para a primeira linha
                                    while ($disciplina = mysqli_fetch_assoc($resultDisciplinas)) {
                                        // Verificar quantas turmas existem para esta disciplina
                                        $turmasQuery = "
                                            SELECT t.numero AS turma_numero, t.ano_ingresso 
                                            FROM turmas_disciplinas td
                                            JOIN turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
                                            WHERE td.disciplina_id = " . $disciplina['id'];

                                        $resultTurmas = mysqli_query($conn, $turmasQuery);
                                        $turmas = [];
                                        while ($turma = mysqli_fetch_assoc($resultTurmas)) {
                                            $turmas[] = $turma;
                                        }

                                        // Criar uma linha de checkbox para cada turma
                                        foreach ($turmas as $turma) {
                                            // Verifica se a combinação disciplina-turma já está associada ao docente
                                            $checked = in_array([
                                                'disciplina_id' => $disciplina['id'],
                                                'turma_numero' => $turma['turma_numero'],
                                                'ano_ingresso' => $turma['ano_ingresso'],
                                            ], $docenteDiscIds) ? "checked" : "";

                                            echo "<div>";
                                            echo "<input type='checkbox' name='disciplinas[]' value='{$disciplina['id']}|{$turma['turma_numero']}|{$turma['ano_ingresso']}' $checked> {$disciplina['nome']} - Turma: {$turma['turma_numero']} - Ano: {$turma['ano_ingresso']}<br>";
                                            echo "</div>";
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update" class="btn btn-success">Salvar</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
