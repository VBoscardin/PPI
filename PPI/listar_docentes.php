<?php
// incluir o arquivo de configuração
require 'config.php';

// Verificar a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obter os docentes, suas disciplinas e turmas
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Docentes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Lista de Docentes</h2>
    <table class="table table-bordered">
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
                        <img src="<?php echo $row['foto_perfil']; ?>" alt="Foto de <?php echo $row['docente_nome']; ?>" style="width: 50px; height: 50px; border-radius: 50%;">
                    <?php else: ?>
                        <img src="path/to/default/image.jpg" alt="Foto padrão" style="width: 50px; height: 50px; border-radius: 50%;">
                    <?php endif; ?>
                </td>
                <td><?php echo $row['docente_nome']; ?></td>
                <td><?php echo $row['docente_email']; ?></td>
                <td><?php echo $row['docente_cpf']; ?></td>
                <td>
                                                <?php
                                                $docente_id = $row['docente_id'];
                                                // Buscar disciplinas associadas
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
                <button class="btn btn-primary" data-toggle="modal" data-target="#editModal<?php echo $row['docente_id']; ?>">Editar</button>
<button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['docente_id']; ?>">Excluir</button>
                        </td>
                    </tr>

<!-- Modal de Edição -->
<div class="modal fade" id="editModal<?php echo $row['docente_id']; ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Docente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="editar_docente.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $row['docente_id']; ?>">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" class="form-control" name="nome" value="<?php echo $row['docente_nome']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $row['docente_email']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" class="form-control" name="cpf" value="<?php echo $row['docente_cpf']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Disciplinas</label><br>
                        <?php
                        // Obter disciplinas disponíveis com turmas
                        $disciplinasQuery = "
                            SELECT 
    d.id AS disciplina_id, 
    d.nome AS disciplina_nome, 
    t.numero AS turma_numero, 
    t.ano AS turma_ano
FROM 
    disciplinas d
JOIN 
    turmas_disciplinas td ON d.id = td.disciplina_id
JOIN 
    turmas t ON td.turma_numero = t.numero AND td.turma_ano = t.ano
ORDER BY 
    d.nome, t.ano, t.numero;

                        ";
                        $disciplinasResult = $conn->query($disciplinasQuery);
                        
                        // Obter disciplinas atribuídas ao docente
$docenteDisciplinasQuery = "
SELECT 
    d.id AS disciplina_id,
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
    dd.docente_id = {$row['docente_id']}
";
$docenteDisciplinasResult = $conn->query($docenteDisciplinasQuery);


// Debug: Verifique o resultado
if ($docenteDisciplinasResult) {
echo "Disciplinas atribuídas ao docente {$row['docente_nome']}:<br>";
while ($row = $docenteDisciplinasResult->fetch_assoc()) {
    print_r($row);
}
} else {
echo "Erro na consulta de disciplinas atribuídas: " . $conn->error;
}


                        // Exibir disciplinas disponíveis
                        while ($disciplinha = $disciplinasResult->fetch_assoc()) {
                            $checked = isset($docenteDisciplinas[$disciplinha['disciplina_id']]) ? 'checked' : '';
                            $turma_numero = $docenteDisciplinas[$disciplinha['disciplina_id']]['turma_numero'] ?? 'N/A';
                            $ano = $docenteDisciplinas[$disciplinha['disciplina_id']]['ano'] ?? 'N/A';

                            echo '<div class="form-check">';
                            echo '<input class="form-check-input" type="checkbox" name="disciplinas[]" value="' . $disciplinha['disciplina_id'] . '" ' . $checked . '>';
                            echo '<label class="form-check-label">' . htmlspecialchars($disciplinha['disciplina_nome']) . 
                                 ' (Turma ' . htmlspecialchars($turma_numero) . 
                                 ' - ' . htmlspecialchars($ano) . 
                                 ')</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>



                    <!-- Modal de Exclusão -->
                    <div class="modal fade" id="deleteModal<?php echo $row['docente_id']; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Excluir Docente</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form action="excluir_docente.php" method="POST">
                                    <div class="modal-body">
                                        <p>Tem certeza de que deseja excluir o docente <strong><?php echo $row['docente_nome']; ?></strong>?</p>
                                        <input type="hidden" name="id" value="<?php echo $row['docente_id']; ?>">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhum docente encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>


