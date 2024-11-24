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

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

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
    
    header("Location: cadastrar_notas.php"); // Troque "selecionar_disciplinas.php" pelo nome da sua página de seleção
    echo "Notas atualizadas com sucesso!";
    exit();
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
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
            <div class="col-md-3 sidebar">
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_docente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button onclick="location.href='cadastrar_notas.php'">
                <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar Notas
                </button>
                
            
                <button onclick="location.href='meu_perfil.php'">
                    <i class="fas fa-user"></i> Meu Perfil
                </button>
                <button class="btn btn-danger" onclick="location.href='sair.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Página do Docente</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                                <?php else: ?>
                                    <img src="imgs/setor-photo.png" alt="Foto do Setor">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            
            <div class="container mt-4">
                <div class="card shadow">
                    <div class="card-body">

                        <h3>Disciplinas que você leciona:</h3>
                        <div class="d-flex flex-wrap gap-3">
                            <?php while ($discipline = $disciplinesResult->fetch_assoc()): ?>
                            
                                <button class="btn btn-success mb-2" onclick="window.location.href='cadastrar_notas.php?disciplina_id=<?= $discipline['id'] ?>'">
                                <strong><?= htmlspecialchars($discipline['nome']) ?></strong><br>Turma: <?= htmlspecialchars($discipline['turma_numero']) ?> | Ano: <?= htmlspecialchars($discipline['turma_ano']) ?>
                                </button><br>

                            <?php endwhile; ?>
                        </div>

                        <?php if ($selected_discipline_id): ?>
                            
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
                            <div class="card shadow">
                                <div class="card-body">
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="disciplina_id" value="<?= $selected_discipline_id ?>">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover align-middle">
                                            <thead class="table-dark text-center">
                                                <h3>Alunos na disciplina</h3>
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
                                            </thead>
                                            <tbody>
                                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($student['nome']) ?></strong></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][parcial_1]" value="<?= $student['parcial_1'] ?>"></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_1]" value="<?= $student['nota_semestre_1'] ?>"></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][parcial_2]" value="<?= $student['parcial_2'] ?>"></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_2]" value="<?= $student['nota_semestre_2'] ?>"></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][nota_final]" value="<?= $student['nota_final'] ?>"></td>
                                                        <td><input type="number" step="0.01" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][nota_exame]" value="<?= $student['nota_exame'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][faltas]" value="<?= $student['faltas'] ?>"></td>
                                                        <td><textarea class="form-control" rows="2" name="notas[<?= $student['numero_matricula'] ?>][observacoes]"><?= htmlspecialchars($student['observacoes']) ?></textarea></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-save"></i> Salvar Notas
                                        </button>
                                    </div>

                                </form>
                            <?php endif; ?>
         
        </div>
</body>

<style>
    /* Estilo customizado para o botão */
    .custom-btn {
        transition: all 0.3s ease;
    }

    /* Quando passar o mouse */
    .custom-btn:hover {
        background-color: #28a745;  /* Cor verde */
        color: #000;  /* Texto preto */
    }
</style>

</html>
