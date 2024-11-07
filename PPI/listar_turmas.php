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
    <link rel="stylesheet" href="styles.css"> <!-- Adicione um arquivo CSS para estilos -->
</head>
<body>
    <h1>Lista de Turmas</h1>

    <?php if ($result->num_rows > 0): ?>
        <table border="1">
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
                </tr>
            </thead>
            <tbody>
                <?php 
                // Variável para rastrear a turma atual no loop
                $current_turma = null;

                while($row = $result->fetch_assoc()): 
                    // Verifica se estamos na mesma turma
                    if ($current_turma !== $row['numero']) {
                        if ($current_turma !== null) {
                            echo "</ul></td></tr>"; // Fecha a lista de disciplinas da turma anterior
                        }
                        // Define a turma atual e exibe os dados da nova turma
                        $current_turma = $row['numero'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['numero']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano_ingresso']); ?></td>
                        <td><?php echo htmlspecialchars($row['ano_oferta']); ?></td>
                        <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['professor_regente']); ?></td>
                        <td><?php echo htmlspecialchars($row['presidente_nome'] ?: 'N/A'); ?></td> <!-- Exibe o nome do presidente ou 'N/A' se não houver -->
                        <td>
                            <ul>
                <?php 
                    }
                    // Exibe cada disciplina como um item de lista
                    if ($row['disciplina_nome']) {
                        echo "<li>" . htmlspecialchars($row['disciplina_nome']) . "</li>";
                    }
                endwhile;
                
                if ($current_turma !== null) {
                    echo "</ul></td></tr>"; // Fecha a lista de disciplinas da última turma
                }
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma turma encontrada.</p>
    <?php endif; ?>

</body>
</html>

<?php
// Fecha a conexão com o banco de dados
$conn->close();
?>
