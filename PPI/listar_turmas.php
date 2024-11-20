<?php
// Inclui o arquivo de configuração para conexão com o banco de dados
include 'config.php';




if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se o campo 'numero_atual' existe no POST
    if (isset($_POST['numero_atual'])) {
        // Captura os dados do formulário
        $numero_atual = $_POST['numero_atual']; // Número da turma a ser atualizado
        $novo_numero = $_POST['numero']; // Novo número da turma
        $ano = $_POST['ano'];
        $ano_ingresso = $_POST['ano_ingresso'];
        $ano_oferta = $_POST['ano_oferta'];
        $curso_id = $_POST['curso_id'];
        $professor_regente = $_POST['professor_regente'];
        $presidente = $_POST['presidente'];

        // Verificar se o novo número da turma já está vinculado a outra turma
        $verificar_numero_query = "SELECT COUNT(*) FROM turmas WHERE numero = ?";
        $stmt_verificar_numero = $conn->prepare($verificar_numero_query);
        $stmt_verificar_numero->bind_param('i', $novo_numero);
        $stmt_verificar_numero->execute();
        $stmt_verificar_numero->bind_result($count);
        $stmt_verificar_numero->fetch();
        $stmt_verificar_numero->close();

        if ($count == 0) {
            echo "<script>alert('Erro: Este número de turma não existe na tabela de turmas!');</script>";
        } else {
            // Verificar se o novo número de turma já está vinculado a discentes
            $verificar_discente_query = "SELECT COUNT(*) FROM discentes_turmas WHERE turma_numero = ?";
            $stmt_verificar_discente = $conn->prepare($verificar_discente_query);
            $stmt_verificar_discente->bind_param('i', $novo_numero);
            $stmt_verificar_discente->execute();
            $stmt_verificar_discente->bind_result($count_discente);
            $stmt_verificar_discente->fetch();
            $stmt_verificar_discente->close();

            // Se já houver discentes na turma, podemos atualizar a tabela discentes_turmas
            if ($count_discente > 0) {
                $update_discente_turma_query = "
                    UPDATE discentes_turmas 
                    SET turma_numero = ? 
                    WHERE turma_numero = ?
                ";
                $stmt_discente_turma = $conn->prepare($update_discente_turma_query);
                $stmt_discente_turma->bind_param('ii', $novo_numero, $numero_atual);
                $stmt_discente_turma->execute();
                $stmt_discente_turma->close();
            }

            // Atualiza a tabela 'turmas'
            $update_numero_query = "
                UPDATE turmas 
                SET numero = ?, ano = ?, ano_ingresso = ?, ano_oferta = ?, curso_id = ?, professor_regente = ?, presidente_id = ? 
                WHERE numero = ?
            ";
            $stmt_numero = $conn->prepare($update_numero_query);
            $stmt_numero->bind_param('iiiiiiii', $novo_numero, $ano, $ano_ingresso, $ano_oferta, $curso_id, $professor_regente, $presidente, $numero_atual);
            if ($stmt_numero->execute()) {
                echo "<script>alert('Turma atualizada com sucesso!');</script>";
            } else {
                echo "<script>alert('Erro ao atualizar a turma: " . $stmt_numero->error . "');</script>";
            }
            $stmt_numero->close();
        }
    } else {
        echo "<script>alert('Campo numero_atual não encontrado!');</script>";
    }
}





// Consulta SQL para obter as turmas
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

                        <!-- Campo oculto para passar o número atual -->
                        <input type="hidden" name="numero_atual" value="<?php echo $row['numero']; ?>">

                        <!-- Número da Turma -->
                        <input type="hidden" name="numero_atual" value="<?php echo $row['numero']; ?>">
                                    <div class="mb-3">
                                        <label for="numero_<?php echo $row['numero']; ?>" class="form-label">Número da Turma</label>
                                        <input type="number" class="form-control" id="numero_<?php echo $row['numero']; ?>" name="numero" value="<?php echo $row['numero']?>" s step="10" required>
                                        </div>

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
                            <label for="curso_id_<?php echo $row['numero']; ?>" class="form-label">Curso</label>
                            <select class="form-select" id="curso_id_<?php echo $row['numero']; ?>" name="curso_id" required>
                                <?php
                                $curso_query = "SELECT * FROM cursos";
                                $curso_result = $conn->query($curso_query);
                                while ($curso = $curso_result->fetch_assoc()):
                                    $selected = ($curso['id'] == $row['curso_id']) ? 'selected' : '';
                                    echo "<option value='" . $curso['id'] . "' $selected>" . htmlspecialchars($curso['nome']) . "</option>";
                                endwhile;
                                ?>
                            </select>
                        </div>

                        <!-- Professor Regente -->
                        <!-- Professor Regente -->
<div class="mb-3">
    <label for="professor_regente_<?php echo $row['numero']; ?>" class="form-label">Professor Regente</label>
    <select class="form-select" id="professor_regente_<?php echo $row['numero']; ?>" name="professor_regente" required>
        <?php
        // Consultar todos os professores (docentes)
        $docentes_query = "SELECT id, nome FROM docentes ORDER BY nome";
        $docentes_result = $conn->query($docentes_query);

        // Exibir todos os professores no select
        while ($docente = $docentes_result->fetch_assoc()):
            // Verificar se o docente é o professor regente da turma
            $selected = ($docente['id'] == $row['professor_regente']) ? 'selected' : '';
            echo "<option value='" . $docente['id'] . "' $selected>" . htmlspecialchars($docente['nome']) . "</option>";
        endwhile;
        ?>
    </select>
</div>


                        <!-- Presidente -->
                        <!-- Presidente -->
<div class="mb-3">
    <label for="presidente_<?php echo $row['numero']; ?>" class="form-label">Presidente</label>
    <select class="form-select" id="presidente_<?php echo $row['numero']; ?>" name="presidente" required>
        <?php
        // Consulta para obter os discentes dessa turma específica
        $discentes_turma_query = "
        SELECT discentes.numero_matricula, discentes.nome 
        FROM discentes_turmas
        INNER JOIN discentes ON discentes_turmas.numero_matricula = discentes.numero_matricula
        WHERE discentes_turmas.turma_numero = " . $row['numero'];
        
        $discentes_turma_result = $conn->query($discentes_turma_query);
        
        // Exibe os discentes associados a essa turma
        while ($discente = $discentes_turma_result->fetch_assoc()):
            $selected = ($discente['numero_matricula'] == $row['presidente_id']) ? 'selected' : '';
            echo "<option value='" . $discente['numero_matricula'] . "' $selected>" . htmlspecialchars($discente['nome']) . "</option>";
        endwhile;
        ?>
    </select>
</div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
