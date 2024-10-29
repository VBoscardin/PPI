<?php
// Inclua o arquivo de configuração que contém a conexão ao banco de dados
include 'config.php';

// Verificar se o formulário de edição foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turma_numero'])) {
    // Capturar os dados do formulário
    $turma_numero = intval($_POST['turma_numero']);
    $turma_ano = intval($_POST['turma_ano']);
    $ano_ingresso = intval($_POST['ano_ingresso']);
    $ano_oferta = intval($_POST['ano_oferta']);
    $professor_regente = intval($_POST['professor_regente']);
    $curso_id = intval($_POST['curso_id']);

    // Atualizar os dados da turma
    $sql_update = "UPDATE turmas SET ano = ?, ano_ingresso = ?, ano_oferta = ?, professor_regente = ?, curso_id = ? WHERE numero = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iiiiii", $turma_ano, $ano_ingresso, $ano_oferta, $professor_regente, $curso_id, $turma_numero);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
}

// Verificar se um ID de turma foi passado para exclusão
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM turmas WHERE numero = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $delete_id);
    $stmt_delete->execute();
}

// Consulta para obter todas as turmas, suas disciplinas e docentes
$sql_turmas = "
    SELECT t.numero, t.ano, t.ano_ingresso, t.ano_oferta, t.professor_regente, c.nome AS curso_nome, 
           d.nome AS docente_nome, di.nome AS disciplina_nome
    FROM turmas t
    LEFT JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN turmas_disciplinas td ON t.numero = td.turma_numero
    LEFT JOIN disciplinas di ON td.disciplina_id = di.id
    LEFT JOIN docentes d ON di.id = (SELECT dd.docente_id FROM docentes_disciplinas dd WHERE dd.disciplina_id = di.id LIMIT 1)
";

$result_turmas = $conn->query($sql_turmas);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Turmas</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Lista de Turmas</h1>

        <?php if ($result_turmas->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Ano</th>
                        <th>Ano de Ingresso</th>
                        <th>Ano de Oferta</th>
                        <th>Professor Regente</th>
                        <th>Curso</th>
                        <th>Disciplina</th>
                        <th>Docente</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $turmas_data = []; // Array para agrupar turmas
                    while ($turma = $result_turmas->fetch_assoc()) {
                        $turma_numero = $turma['numero'];

                        if (!isset($turmas_data[$turma_numero])) {
                            $turmas_data[$turma_numero] = [
                                'ano' => $turma['ano'],
                                'ano_ingresso' => $turma['ano_ingresso'],
                                'ano_oferta' => $turma['ano_oferta'],
                                'professor_regente' => $turma['professor_regente'],
                                'curso_nome' => $turma['curso_nome'],
                                'disciplinas' => []
                            ];
                        }

                        if ($turma['disciplina_nome']) {
                            $turmas_data[$turma_numero]['disciplinas'][] = [
                                'disciplina' => $turma['disciplina_nome'],
                                'docente' => $turma['docente_nome'] ?: 'Não atribuído'
                            ];
                        }
                    }

                    // Exibir as turmas e suas disciplinas
                    foreach ($turmas_data as $numero => $data): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($numero); ?></td>
                            <td><?php echo htmlspecialchars($data['ano']); ?></td>
                            <td><?php echo htmlspecialchars($data['ano_ingresso']); ?></td>
                            <td><?php echo htmlspecialchars($data['ano_oferta']); ?></td>
                            <td><?php echo htmlspecialchars($data['professor_regente']); ?></td>
                            <td><?php echo htmlspecialchars($data['curso_nome']); ?></td>
                            <td>
                                <?php foreach ($data['disciplinas'] as $disciplina): ?>
                                    <p><?php echo htmlspecialchars($disciplina['disciplina']); ?> (<?php echo htmlspecialchars($disciplina['docente']); ?>)</p>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button onclick="openEditModal(<?php echo htmlspecialchars($numero); ?>, <?php echo htmlspecialchars($data['ano']); ?>, <?php echo htmlspecialchars($data['ano_ingresso']); ?>, <?php echo htmlspecialchars($data['ano_oferta']); ?>, <?php echo htmlspecialchars($data['professor_regente']); ?>)">Editar</button>
                                <a href="?delete_id=<?php echo htmlspecialchars($numero); ?>" onclick="return confirm('Tem certeza que deseja excluir esta turma?');">Deletar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma turma encontrada.</p>
        <?php endif; ?>

        <!-- Modal -->
        <div class="modal" id="editModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Editar Turma</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm" method="POST">
                            <input type="hidden" name="turma_numero" id="turma_numero">
                            <div class="form-group">
                                <label for="turma_ano">Ano:</label>
                                <input type="number" id="turma_ano" name="turma_ano" required>
                            </div>
                            <div class="form-group">
                                <label for="ano_ingresso">Ano de Ingresso:</label>
                                <input type="number" id="ano_ingresso" name="ano_ingresso" required>
                            </div>
                            <div class="form-group">
                                <label for="ano_oferta">Ano de Oferta:</label>
                                <input type="number" id="ano_oferta" name="ano_oferta" required>
                            </div>
                            <div class="form-group">
                                <label for="professor_regente">Professor Regente:</label>
                                <input type="number" id="professor_regente" name="professor_regente" required>
                            </div>
                            <div class="form-group">
                                <label for="curso_id">Curso ID:</label>
                                <input type="number" id="curso_id" name="curso_id" required>
                            </div>
                            <button type="submit">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Inclua jQuery e Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function openEditModal(numero, ano, ano_ingresso, ano_oferta, professor_regente) {
            // Preencher os campos do modal
            document.getElementById('turma_numero').value = numero;
            document.getElementById('turma_ano').value = ano;
            document.getElementById('ano_ingresso').value = ano_ingresso;
            document.getElementById('ano_oferta').value = ano_oferta;
            document.getElementById('professor_regente').value = professor_regente;

            // Abrir o modal
            $('#editModal').modal('show');
        }
    </script>
</body>
</html>
