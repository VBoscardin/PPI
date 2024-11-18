<?php
// Inclui o arquivo de configuração para conexão com o banco de dados
include 'config.php';

// Processamento do formulário de edição (se os dados forem enviados via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['numero'])) {
        $numero = $_POST['numero'];
        $ano = $_POST['ano'];
        $ano_ingresso = $_POST['ano_ingresso'];
        $ano_oferta = $_POST['ano_oferta'];
        $curso_id = $_POST['curso_id'];
        $professor_regente = $_POST['professor_regente'];
        $presidente = $_POST['presidente'];

        // Atualiza as informações da turma no banco de dados
        $update_query = "
            UPDATE turmas 
            SET ano = ?, ano_ingresso = ?, ano_oferta = ?, curso_id = ?, professor_regente = ?, presidente_id = ?
            WHERE numero = ?
        ";

        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('iiiiiii', $ano, $ano_ingresso, $ano_oferta, $curso_id, $professor_regente, $presidente, $numero);

        if ($stmt->execute()) {
            echo "<script>alert('Turma atualizada com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao atualizar a turma.');</script>";
        }
    }
}

// Consulta SQL para obter as turmas, incluindo curso, professor regente, presidente da turma
$sql = "
    SELECT 
        turmas.numero, 
        turmas.ano, 
        turmas.ano_ingresso, 
        turmas.ano_oferta, 
        cursos.nome AS curso_nome, 
        turmas.curso_id,
        turmas.professor_regente,
        turmas.professor_regente AS professor_regente_id,
        turmas.presidente_id,
        discentes.nome AS presidente_nome
    FROM 
        turmas
    INNER JOIN cursos ON turmas.curso_id = cursos.id
    LEFT JOIN discentes ON turmas.presidente_id = discentes.numero_matricula
    ORDER BY turmas.ano DESC, turmas.numero ASC
";

$result = $conn->query($sql);

// Consultas para obter todos os docentes e discentes
$docentes_query = "SELECT id, nome FROM docentes ORDER BY nome";
$docentes_result = $conn->query($docentes_query);

$discentes_query = "SELECT numero_matricula, nome FROM discentes ORDER BY nome";
$discentes_result = $conn->query($discentes_query);

// Consulta para obter as disciplinas e seus respectivos docentes para cada turma
$disciplinas_query = "
    SELECT 
        disciplinas.nome AS disciplina_nome,
        docentes.nome AS docente_nome,
        turmas_disciplinas.turma_numero
    FROM 
        turmas_disciplinas
    INNER JOIN disciplinas ON turmas_disciplinas.disciplina_id = disciplinas.id
    INNER JOIN docentes_disciplinas ON disciplinas.id = docentes_disciplinas.disciplina_id
    INNER JOIN docentes ON docentes_disciplinas.docente_id = docentes.id
    ORDER BY disciplinas.nome
";
$disciplinas_result = $conn->query($disciplinas_query);
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
        <table class="table">
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
                    <th>Discentes</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['numero']); ?></td>
                    <td><?php echo htmlspecialchars($row['ano']); ?></td>
                    <td><?php echo htmlspecialchars($row['ano_ingresso']); ?></td>
                    <td><?php echo htmlspecialchars($row['ano_oferta']); ?></td>
                    <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                    <td><?php 
                        // Nome do professor regente
                        $professor_query = "SELECT nome FROM docentes WHERE id = " . $row['professor_regente_id'];
                        $professor_result = $conn->query($professor_query);
                        $professor = $professor_result->fetch_assoc();
                        echo htmlspecialchars($professor['nome']);
                    ?></td>
                    <td><?php echo htmlspecialchars($row['presidente_nome'] ?: 'N/A'); ?></td>
                    
                    <!-- Exibindo as disciplinas da turma -->
                    <td>
                        <?php
                        // Reseta o ponteiro para as disciplinas e exibe apenas as associadas à turma
                        $disciplinas_result->data_seek(0); // Reseta o ponteiro da consulta de disciplinas
                        while ($disciplina = $disciplinas_result->fetch_assoc()) {
                            if ($disciplina['turma_numero'] == $row['numero']) {
                                echo "<p>" . htmlspecialchars($disciplina['disciplina_nome']) . " - " . htmlspecialchars($disciplina['docente_nome']) . "</p>";
                            }
                        }
                        ?>
                    </td>
                    
                    <!-- Exibindo os discentes da turma -->
                    <td>
                        <?php
                        // Consulta para pegar os discentes dessa turma específica
                        $discentes_turma_query = "
                        SELECT 
                            discentes.nome 
                        FROM 
                            discentes_turmas
                        INNER JOIN discentes ON discentes_turmas.numero_matricula = discentes.numero_matricula
                        WHERE 
                            discentes_turmas.turma_numero = " . $row['numero'];
                        
                        $discentes_turma_result = $conn->query($discentes_turma_query);
                        
                        if ($discentes_turma_result->num_rows > 0) {
                            while ($discente = $discentes_turma_result->fetch_assoc()) {
                                echo "<p>" . htmlspecialchars($discente['nome']) . "</p>";
                            }
                        } else {
                            echo "<p>Nenhum discente associado.</p>";
                        }
                        ?>
                    </td>

                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['numero']; ?>">Editar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma turma encontrada.</p>
    <?php endif; ?>

    <!-- Modais para Edição -->
    <?php 
    $result->data_seek(0); // Reset the result pointer
    while($row = $result->fetch_assoc()):
        $turmaNumero = $row['numero'];
    ?>
    <!-- Modal de Edição -->
    <div class="modal fade" id="editarModal<?php echo $row['numero']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $row['numero']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarModalLabel<?php echo $row['numero']; ?>">Editar Turma</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="numero" value="<?php echo htmlspecialchars($row['numero']); ?>">
                        
                        <!-- Ano -->
                        <div class="mb-3">
                            <label for="ano_<?php echo $row['numero']; ?>" class="form-label">Ano</label>
                            <input type="number" class="form-control" id="ano_<?php echo $row['numero']; ?>" name="ano" value="<?php echo htmlspecialchars($row['ano']); ?>" required>
                        </div>

                        <!-- Ano de Ingresso -->
                        <div class="mb-3">
                            <label for="ano_ingresso_<?php echo $row['numero']; ?>" class="form-label">Ano de Ingresso</label>
                            <input type="number" class="form-control" id="ano_ingresso_<?php echo $row['numero']; ?>" name="ano_ingresso" value="<?php echo htmlspecialchars($row['ano_ingresso']); ?>" required>
                        </div>

                        <!-- Ano de Oferta -->
                        <div class="mb-3">
                            <label for="ano_oferta_<?php echo $row['numero']; ?>" class="form-label">Ano de Oferta</label>
                            <input type="number" class="form-control" id="ano_oferta_<?php echo $row['numero']; ?>" name="ano_oferta" value="<?php echo htmlspecialchars($row['ano_oferta']); ?>" required>
                        </div>

                        <!-- Curso -->
                        <div class="mb-3">
                            <label for="curso_<?php echo $row['numero']; ?>" class="form-label">Curso</label>
                            <select class="form-select" id="curso_<?php echo $row['numero']; ?>" name="curso_id" required>
                                <option value="">Selecione</option>
                                <?php 
                                $curso_result = $conn->query("SELECT id, nome FROM cursos");
                                while ($curso = $curso_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $curso['id']; ?>" <?php echo $curso['id'] == $row['curso_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($curso['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Professor Regente -->
                        <div class="mb-3">
                            <label for="professor_<?php echo $row['numero']; ?>" class="form-label">Professor Regente</label>
                            <select class="form-select" name="professor_regente">
                                <option value="">Selecione</option>
                                <?php
                                $docentes_result->data_seek(0);
                                while ($docente = $docentes_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $docente['id']; ?>" <?php echo $docente['id'] == $row['professor_regente_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($docente['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Presidente -->
                        <div class="mb-3">
                            <label for="presidente_<?php echo $row['numero']; ?>" class="form-label">Presidente</label>
                            <select class="form-select" name="presidente">
                                <option value="">Selecione</option>
                                <?php
                                $discentes_result->data_seek(0);
                                while ($discente = $discentes_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $discente['numero_matricula']; ?>" <?php echo $discente['numero_matricula'] == $row['presidente_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($discente['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
