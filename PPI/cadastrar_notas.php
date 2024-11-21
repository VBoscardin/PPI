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

$docente_id = $_SESSION['user_id'];

// Buscar disciplinas que o docente leciona
$disciplinesQuery = $conn->prepare("
    SELECT d.id, d.nome, td.turma_numero, t.ano AS turma_ano
    FROM disciplinas d
    JOIN docentes_disciplinas dd ON d.id = dd.disciplina_id
    JOIN turmas_disciplinas td ON d.id = td.disciplina_id
    JOIN turmas t ON td.turma_numero = t.numero
    WHERE dd.docente_id = ?
");
$disciplinesQuery->bind_param("i", $docente_id);
$disciplinesQuery->execute();
$disciplinesResult = $disciplinesQuery->get_result();

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disciplina_id'])) {
    $selected_discipline_id = intval($_POST['disciplina_id']);

    if (!isset($_POST['notas']) || !is_array($_POST['notas'])) {
        echo "Erro: Dados incompletos.";
        exit();
    }

    // Obter turma correspondente à disciplina
    $turmaQuery = $conn->prepare("SELECT turma_numero FROM turmas_disciplinas WHERE disciplina_id = ? LIMIT 1");
    $turmaQuery->bind_param("i", $selected_discipline_id);
    $turmaQuery->execute();
    $turmaQuery->bind_result($turma_numero);
    $turmaQuery->fetch();
    $turmaQuery->close();

    if (!$turma_numero) {
        echo "Erro: Turma não encontrada para a disciplina selecionada.";
        exit();
    }

    // Atualizar notas dos alunos
    foreach ($_POST['notas'] as $matricula => $nota_data) {
        $matricula = intval($matricula);
        $parcial_1 = floatval($nota_data['parcial_1']);
        $nota_semestre_1 = floatval($nota_data['nota_semestre_1']);
        $parcial_2 = floatval($nota_data['parcial_2']);
        $nota_semestre_2 = floatval($nota_data['nota_semestre_2']);
        $nota_final = isset($nota_data['nota_final']) ? floatval($nota_data['nota_final']) : null;
        $nota_exame = isset($nota_data['nota_exame']) ? floatval($nota_data['nota_exame']) : null;
        $faltas = intval($nota_data['faltas']);
        $observacoes = isset($nota_data['observacoes']) ? $nota_data['observacoes'] : null;

        // Verificar se já existe registro
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM notas WHERE discente_id = ? AND disciplina_id = ?");
        $stmt_check->bind_param("ii", $matricula, $selected_discipline_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            // Atualizar nota
            $stmt = $conn->prepare("
                UPDATE notas 
                SET parcial_1 = ?, nota_semestre_1 = ?, parcial_2 = ?, nota_semestre_2 = ?, 
                    nota_final = ?, nota_exame = ?, faltas = ?, observacoes = ? 
                WHERE discente_id = ? AND disciplina_id = ?
            ");
            $stmt->bind_param("dddddisssi", 
                $parcial_1, $nota_semestre_1, $parcial_2, $nota_semestre_2, 
                $nota_final, $nota_exame, $faltas, $observacoes, $matricula, $selected_discipline_id);
        } else {
            // Inserir nova nota
            $stmt = $conn->prepare("
                INSERT INTO notas (discente_id, disciplina_id, turma_numero, parcial_1, nota_semestre_1, parcial_2, nota_semestre_2, 
                                   nota_final, nota_exame, faltas, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiddddddis", 
                $matricula, $selected_discipline_id, $turma_numero, $parcial_1, $nota_semestre_1, 
                $parcial_2, $nota_semestre_2, $nota_final, $nota_exame, $faltas, $observacoes);
        }

        if (!$stmt->execute()) {
            echo "Erro ao salvar dados para o aluno $matricula: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
    echo "Notas atualizadas com sucesso!";
}

// Obter disciplina selecionada
$selected_discipline_id = isset($_GET['disciplina_id']) ? intval($_GET['disciplina_id']) : null;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Notas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        h3{
            font-family: "Forum", "serif";
        }
        
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Notas</h1>

        <h2>Disciplinas que você leciona:</h2>
        <ul class="discipline-list">
            <?php while ($discipline = $disciplinesResult->fetch_assoc()): ?>
                <li>
                    <a href="cadastrar_notas.php?disciplina_id=<?= $discipline['id'] ?>">
                        <?= htmlspecialchars($discipline['nome']) ?> 
                        (Turma: <?= $discipline['turma_numero'] ?> - Ano: <?= $discipline['turma_ano'] ?>)
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>

        <?php if ($selected_discipline_id): ?>
            <h2>Alunos na disciplina</h2>
            <?php
            $studentsQuery = $conn->prepare("
                SELECT dt.numero_matricula, ds.nome, 
                       MAX(n.parcial_1) AS parcial_1, MAX(n.nota_semestre_1) AS nota_semestre_1,
                       MAX(n.parcial_2) AS parcial_2, MAX(n.nota_semestre_2) AS nota_semestre_2,
                       MAX(n.nota_final) AS nota_final, MAX(n.nota_exame) AS nota_exame,
                       MAX(n.faltas) AS faltas, MAX(n.observacoes) AS observacoes
                FROM discentes_turmas dt
                JOIN discentes ds ON dt.numero_matricula = ds.numero_matricula
                LEFT JOIN notas n ON n.discente_id = dt.numero_matricula AND n.disciplina_id = ?
                WHERE dt.turma_numero = (SELECT turma_numero FROM turmas_disciplinas WHERE disciplina_id = ? LIMIT 1)
                GROUP BY dt.numero_matricula
            ");
            $studentsQuery->bind_param("ii", $selected_discipline_id, $selected_discipline_id);
            $studentsQuery->execute();
            $studentsResult = $studentsQuery->get_result();
            ?>

            <form method="POST">
                <input type="hidden" name="disciplina_id" value="<?= $selected_discipline_id ?>">
                <table class="table table-bordered table-hover table-sm" style="border-radius: 4px; overflow: hidden;">
                                <thead class="table-dark">
                    <tr>
                        <th>Aluno</th>
                        <th>Parcial 1</th>
                        <th>Semestre 1</th>
                        <th>Parcial 2</th>
                        <th>Semestre 2</th>
                        <th>Nota Final</th>
                        <th>Nota Exame</th>
                        <th>Faltas</th>
                        <th>Observações</th>
                    </tr>
                    <?php while ($student = $studentsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['nome']) ?></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][parcial_1]" value="<?= $student['parcial_1'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_1]" value="<?= $student['nota_semestre_1'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][parcial_2]" value="<?= $student['parcial_2'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_2]" value="<?= $student['nota_semestre_2'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][nota_final]" value="<?= $student['nota_final'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][nota_exame]" value="<?= $student['nota_exame'] ?>"></td>
                            <td><input type="text" name="notas[<?= $student['numero_matricula'] ?>][faltas]" value="<?= $student['faltas'] ?>"></td>
                            <td><textarea name="notas[<?= $student['numero_matricula'] ?>][observacoes]"><?= htmlspecialchars($student['observacoes']) ?></textarea></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit" class="btn">Salvar Notas</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
