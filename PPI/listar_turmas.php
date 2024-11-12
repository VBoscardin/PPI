<?php
// Inclui o arquivo de configuração para conexão com o banco de dados
include 'config.php';

// Consulta SQL para obter as turmas, incluindo curso, professor regente, disciplinas, e o presidente da turma
$sql = "
    SELECT 
        turmas.numero, 
        turmas.ano, 
        turmas.ano_ingresso, 
        turmas.ano_oferta, 
        cursos.nome AS curso_nome, 
        docentes.nome AS professor_regente,
        disciplinas.nome AS disciplina_nome,
        discentes.nome AS presidente_nome
    FROM 
        turmas
    INNER JOIN cursos ON turmas.curso_id = cursos.id
    INNER JOIN docentes ON turmas.professor_regente = docentes.id
    LEFT JOIN turmas_disciplinas ON turmas.numero = turmas_disciplinas.turma_numero 
    LEFT JOIN disciplinas ON turmas_disciplinas.disciplina_id = disciplinas.id
    LEFT JOIN discentes ON turmas.presidente_id = discentes.numero_matricula
    ORDER BY turmas.ano DESC, turmas.numero ASC, disciplinas.nome ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Turmas</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <h1>Lista de Turmas</h1>

    <?php if ($result->num_rows > 0): ?>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Ano</th>
                    <th>Ano de Ingresso</th>
                    <th>Ano de Oferta</th>
                    <th>Curso</th>
                    <th>Professor Regente</th>
                    <th>Presidente da Turma</th>
                    <th>Disciplinas</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_turma = null;
                while($row = $result->fetch_assoc()): 
                    if ($current_turma !== $row['numero']) {
                        if ($current_turma !== null) {
                            echo "</ul></td><td>";
                            ?>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $current_turma; ?>">Editar</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $current_turma; ?>">Excluir</button>
                            <?php
                            echo "</td></tr>";
                        }
                        $current_turma = $row['numero'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['numero']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano_ingresso']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano_oferta']); ?></td>
                        <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['professor_regente']); ?></td>
                        <td><?php echo htmlspecialchars($row['presidente_nome'] ?: 'N/A'); ?></td>
                        <td>
                            <ul>
                <?php 
                    }
                    if ($row['disciplina_nome']) {
                        echo "<li>" . htmlspecialchars($row['disciplina_nome']) . "</li>";
                    }
                endwhile;

                if ($current_turma !== null) {
                    echo "</ul></td><td>";
                    ?>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $current_turma; ?>">Editar</button>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $current_turma; ?>">Excluir</button>
                    <?php
                    echo "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma turma encontrada.</p>
    <?php endif; ?>

    <!-- Modais para Edição e Exclusão -->
    <?php 
    $result->data_seek(0);
    while($row = $result->fetch_assoc()):
        $turmaNumero = $row['numero'];
    ?>
        <!-- Modal Editar -->
        <div class="modal fade" id="editarModal<?php echo $turmaNumero; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="editarModalLabel">Editar Turma</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="editar_turma.php" method="POST">
                            <input type="hidden" name="turma_id" value="<?php echo $turmaNumero; ?>">

                            <!-- Campos de edição da turma -->
                            <div class="mb-3">
                                <label for="ano" class="form-label">Ano</label>
                                <input type="text" name="ano" class="form-control" value="<?php echo htmlspecialchars($row['ano']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="ano_ingresso" class="form-label">Ano de Ingresso</label>
                                <input type="text" name="ano_ingresso" class="form-control" value="<?php echo htmlspecialchars($row['ano_ingresso']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="ano_oferta" class="form-label">Ano de Oferta</label>
                                <input type="text" name="ano_oferta" class="form-control" value="<?php echo htmlspecialchars($row['ano_oferta']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <input type="text" name="curso" class="form-control" value="<?php echo htmlspecialchars($row['curso_nome']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="professor_regente" class="form-label">Professor Regente</label>
                                <input type="text" name="professor_regente" class="form-control" value="<?php echo htmlspecialchars($row['professor_regente']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="presidente" class="form-label">Presidente da Turma</label>
                                <input type="text" name="presidente" class="form-control" value="<?php echo htmlspecialchars($row['presidente_nome']); ?>">
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Excluir -->
        <div class="modal fade" id="excluirModal<?php echo $turmaNumero; ?>" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="excluirModalLabel">Excluir Turma</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="excluir_turma.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="turma_id" value="<?php echo $turmaNumero; ?>">
                            <p>Tem certeza de que deseja excluir a turma "<?php echo htmlspecialchars($row['numero']); ?>"?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
