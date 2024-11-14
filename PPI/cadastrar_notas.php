<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um docente
if ($_SESSION['user_type'] !== 'docente') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Utilize o user_id ao invés de docente_id
$docente_id = $_SESSION['user_id'];

// Buscar as disciplinas que o docente leciona com informações da turma
$disciplinesQuery = $conn->prepare("
    SELECT d.id, d.nome, td.turma_numero, td.turma_ano
    FROM disciplinas d
    JOIN docentes_disciplinas dd ON d.id = dd.disciplina_id
    JOIN turmas_disciplinas td ON d.id = td.disciplina_id
    WHERE dd.docente_id = ?
");
$disciplinesQuery->bind_param("i", $docente_id);
$disciplinesQuery->execute();
$disciplinesResult = $disciplinesQuery->get_result();

// Verificar se o formulário de notas foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disciplina_id'])) {
    $selected_discipline_id = intval($_POST['disciplina_id']);
    
    // Verificar se todos os dados necessários foram enviados
    if (!isset($_POST['notas']) || !isset($_POST['data_avaliacao']) || !isset($_POST['tipo_avaliacao'])) {
        echo "Erro: Dados de notas incompletos.";
        exit();
    }

    // Obter alunos na disciplina e turma selecionadas
    $studentsQuery = $conn->prepare("
        SELECT dt.numero_matricula, ds.nome 
        FROM discentes_turmas dt
        JOIN discentes ds ON dt.numero_matricula = ds.numero_matricula
        JOIN turmas_disciplinas td ON dt.turma_numero = td.turma_numero
        WHERE td.disciplina_id = ?
    ");
    $studentsQuery->bind_param("i", $selected_discipline_id);
    $studentsQuery->execute();
    $studentsResult = $studentsQuery->get_result();

    // Processar as notas enviadas
    foreach ($_POST['notas'] as $matricula => $nota_data) {
        $data_avaliacao = $_POST['data_avaliacao'][$matricula];
        $tipo_avaliacao = $_POST['tipo_avaliacao'][$matricula];
        
        // Verificar se todos os campos de notas foram preenchidos
        if (empty($nota_data['nota_parcial_1']) || empty($nota_data['semestre_1']) || empty($nota_data['nota_parcial_2']) || empty($nota_data['semestre_2']) || empty($nota_data['nota_exame']) || empty($nota_data['faltas_bio']) || empty($nota_data['np_bio']) || empty($nota_data['nf_bio']) || empty($nota_data['sit_bio'])) {
            echo "Erro: Dados de notas incompletos para o aluno de matrícula $matricula.<br>";
            continue;
        }

        // Validar notas
        foreach ($nota_data as $key => $value) {
            if ($key != 'sit_bio' && ($value < 0 || $value > 10)) {
                echo "Erro: A $key deve estar entre 0 e 10 para o aluno de matrícula $matricula.<br>";
                continue 2; // Pula para o próximo aluno
            }
        }

        $matricula = intval($matricula);
        $nota_parcial_1 = floatval($nota_data['nota_parcial_1']);
        $semestre_1 = floatval($nota_data['semestre_1']);
        $nota_parcial_2 = floatval($nota_data['nota_parcial_2']);
        $semestre_2 = floatval($nota_data['semestre_2']);
        $nota_exame = floatval($nota_data['nota_exame']);
        $faltas_bio = floatval($nota_data['faltas_bio']);
        $np_bio = floatval($nota_data['np_bio']);
        $nf_bio = floatval($nota_data['nf_bio']);
        $sit_bio = $nota_data['sit_bio'];

        // Inserir as notas no banco de dados
        $stmt = $conn->prepare("
            INSERT INTO notas (discente_id, disciplina_id, turma_numero, turma_ano, 
                               nota_parcial_1, semestre_1, nota_parcial_2, semestre_2, 
                               nota_exame, faltas_bio, np_bio, nf_bio, sit_bio, 
                               data_avaliacao, tipo_avaliacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Obter os dados da turma
        $turmaQuery = $conn->prepare("
            SELECT turma_numero, turma_ano
            FROM turmas_disciplinas
            WHERE disciplina_id = ?
            LIMIT 1
        ");
        $turmaQuery->bind_param("i", $selected_discipline_id);
        $turmaQuery->execute();
        $turmaQuery->bind_result($turma_numero, $turma_ano);
        $turmaQuery->fetch();
        $turmaQuery->close();

        $stmt->bind_param("iiiiddddddssss", $matricula, $selected_discipline_id, $turma_numero, $turma_ano,
                          $nota_parcial_1, $semestre_1, $nota_parcial_2, $semestre_2, $nota_exame,
                          $faltas_bio, $np_bio, $nf_bio, $sit_bio, $data_avaliacao, $tipo_avaliacao);

        if ($stmt->execute()) {
            echo "Notas cadastradas para o aluno de matrícula $matricula.<br>";
        } else {
            echo "Erro ao cadastrar as notas para o aluno de matrícula $matricula: " . $stmt->error . "<br>";
        }

        $stmt->close();
    }
}

$selected_discipline_id = isset($_GET['disciplina_id']) ? intval($_GET['disciplina_id']) : null;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Notas</title>
</head>
<body>

<h1>Cadastrar Notas</h1>

<h2>Disciplinas que você leciona:</h2>
<ul>
    <?php while ($discipline = $disciplinesResult->fetch_assoc()): ?>
        <li>
            <a href="cadastrar_notas.php?disciplina_id=<?= $discipline['id'] ?>">
                <?= htmlspecialchars($discipline['nome']) ?> (Turma: <?= $discipline['turma_numero'] ?>, Ano: <?= $discipline['turma_ano'] ?>)
            </a>
        </li>
    <?php endwhile; ?>
</ul>

<?php if ($selected_discipline_id): ?>
    <h2>Alunos na disciplina</h2>
    
    <?php
    // Obter alunos na disciplina e turma selecionadas
    $studentsQuery = $conn->prepare("
        SELECT dt.numero_matricula, ds.nome 
        FROM discentes_turmas dt
        JOIN discentes ds ON dt.numero_matricula = ds.numero_matricula
        JOIN turmas_disciplinas td ON dt.turma_numero = td.turma_numero
        WHERE td.disciplina_id = ?
    ");
    $studentsQuery->bind_param("i", $selected_discipline_id);
    $studentsQuery->execute();
    $studentsResult = $studentsQuery->get_result();
    ?>

    <form action="cadastrar_notas.php" method="post">
        <input type="hidden" name="disciplina_id" value="<?= $selected_discipline_id ?>">
        
        <table border="1">
            <tr>
                <th>Nome do Aluno</th>
                <th>Nota Parcial 1</th>
                <th>Semestre 1</th>
                <th>Nota Parcial 2</th>
                <th>Semestre 2</th>
                <th>Nota Exame</th>
                <th>Faltas (BIO)</th>
                <th>NP BIO</th>
                <th>NF BIO</th>
                <th>SIT BIO</th>
                <th>Data da Avaliação</th>
                <th>Tipo de Avaliação</th>
            </tr>

            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($student['nome']) ?></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][nota_parcial_1]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][semestre_1]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][nota_parcial_2]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][semestre_2]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][nota_exame]" required></td>
                    <td><input type="number" step="0.01" min="0" name="notas[<?= $student['numero_matricula'] ?>][faltas_bio]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][np_bio]" required></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="notas[<?= $student['numero_matricula'] ?>][nf_bio]" required></td>
                    <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][sit_bio]" required></td>
                    <td><input type="date" name="data_avaliacao[<?= $student['numero_matricula'] ?>]" required></td>
                    <td>
                        <select name="tipo_avaliacao[<?= $student['numero_matricula'] ?>]" required>
                            <option value="prova">Prova</option>
                            <option value="trabalho">Trabalho</option>
                            <option value="atividade">Atividade</option>
                            <option value="participacao">Participação</option>
                        </select>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <button type="submit">Cadastrar Notas</button>
    </form>

<?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
