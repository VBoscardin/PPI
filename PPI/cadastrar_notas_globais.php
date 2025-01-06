<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é do setor
if ($_SESSION['user_type'] !== 'setor') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Buscar informações do usuário (setor)
$stmt = $conn->prepare("SELECT nome, foto_perfil FROM setores WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Buscar todas as turmas
$turmasQuery = $conn->prepare("SELECT numero, ano FROM turmas ORDER BY ano DESC");
$turmasQuery->execute();
$turmasResult = $turmasQuery->get_result();

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turma_numero'])) {
    $selected_turma_numero = intval($_POST['turma_numero']);
    $selected_turma_ano = intval($_POST['turma_ano']);

    if (!isset($_POST['notas']) || !is_array($_POST['notas'])) {
        echo "Erro: Dados incompletos.";
        exit();
    }

    // Buscar todas as disciplinas da turma selecionada
    $stmt_disciplines = $conn->prepare(
        "SELECT td.disciplina_id FROM turmas_disciplinas td WHERE td.turma_numero = ? AND td.turma_ano = ?"
    );
    $stmt_disciplines->bind_param("ii", $selected_turma_numero, $selected_turma_ano);
    $stmt_disciplines->execute();
    $result_disciplines = $stmt_disciplines->get_result();
    $disciplinas = [];
    while ($discipline = $result_disciplines->fetch_assoc()) {
        $disciplinas[] = $discipline['disciplina_id'];
    }
    $stmt_disciplines->close();

    // Atualizar ou inserir notas para todos os alunos e disciplinas
    foreach ($_POST['notas'] as $matricula => $nota_data) {
        $matricula = intval($matricula);
        $ais = isset($nota_data['ais']) ? floatval($nota_data['ais']) : null;
        $mostra_ciencias = isset($nota_data['mostra_ciencias']) ? floatval($nota_data['mostra_ciencias']) : null;
        $ppi = isset($nota_data['ppi']) ? floatval($nota_data['ppi']) : null;
        $nota_exame = isset($nota_data['nota_exame']) ? floatval($nota_data['nota_exame']) : null;

        // Verificar se já existe registro para o aluno na turma
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM notas WHERE discente_id = ? AND turma_numero = ? AND turma_ano = ?");
        $stmt_check->bind_param("iii", $matricula, $selected_turma_numero, $selected_turma_ano);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        // Atualizar ou inserir as notas para todas as disciplinas da turma para o aluno
        foreach ($disciplinas as $disciplina_id) {
            // Verificar se já existe registro para essa disciplina
            $stmt_check_discipline = $conn->prepare(
                "SELECT COUNT(*) FROM notas WHERE discente_id = ? AND turma_numero = ? AND turma_ano = ? AND disciplina_id = ?"
            );
            $stmt_check_discipline->bind_param("iiii", $matricula, $selected_turma_numero, $selected_turma_ano, $disciplina_id);
            $stmt_check_discipline->execute();
            $stmt_check_discipline->bind_result($count_discipline);
            $stmt_check_discipline->fetch();
            $stmt_check_discipline->close();

            // Se já existir registro, atualize as notas
            if ($count_discipline > 0) {
                $stmt = $conn->prepare(
                    "UPDATE notas SET ais = ?, mostra_ciencias = ?, ppi = ?, nota_exame = ? WHERE discente_id = ? AND turma_numero = ? AND turma_ano = ? AND disciplina_id = ?"
                );
                $stmt->bind_param("ddddiiii", $ais, $mostra_ciencias, $ppi, $nota_exame, $matricula, $selected_turma_numero, $selected_turma_ano, $disciplina_id);
            } else {
                // Caso contrário, insira novas notas para essa disciplina
                $stmt = $conn->prepare(
                    "INSERT INTO notas (discente_id, turma_numero, turma_ano, disciplina_id, ais, mostra_ciencias, ppi, nota_exame) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("iiiddddd", $matricula, $selected_turma_numero, $selected_turma_ano, $disciplina_id, $ais, $mostra_ciencias, $ppi, $nota_exame);
            }

            if (!$stmt->execute()) {
                echo "Erro ao salvar dados para o aluno $matricula na disciplina $disciplina_id: " . $stmt->error . "<br>";
            }
            $stmt->close();
        }
    }

    // Redirecionar após salvar os dados
    header("Location: cadastrar_notas_globais.php?turma_numero=" . $selected_turma_numero . "&turma_ano=" . $selected_turma_ano);
    exit();
}




// Obter alunos da turma selecionada
$selected_turma_numero = isset($_GET['turma_numero']) ? intval($_GET['turma_numero']) : null;
$selected_turma_ano = isset($_GET['turma_ano']) ? intval($_GET['turma_ano']) : null;

if ($selected_turma_numero && $selected_turma_ano) {
    $studentsQuery = $conn->prepare(
        "SELECT d.numero_matricula, d.nome, n.ais, n.mostra_ciencias, n.ppi, n.nota_exame FROM discentes_turmas dt JOIN discentes d ON dt.numero_matricula = d.numero_matricula LEFT JOIN notas n ON n.discente_id = dt.numero_matricula AND n.turma_numero = ? AND n.turma_ano = ? WHERE dt.turma_numero = ? AND dt.turma_ano = ? ORDER BY d.nome ASC"
    );
    $studentsQuery->bind_param("iiii", $selected_turma_numero, $selected_turma_ano, $selected_turma_numero, $selected_turma_ano);
    $studentsQuery->execute();
    $studentsResult = $studentsQuery->get_result();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Notas Globais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        h3 {
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
                <button onclick="location.href='f_pagina_setor.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button onclick="location.href='cadastrar_notas_globais.php'">
                    <i class="fas fa-plus"></i> Cadastrar Notas Globais
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
                        <div class="title ms-3">Página do Setor</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Setor">
                                <?php else: ?>
                                    <img src="imgs/setor-photo.png" alt="Foto do Setor">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid mt-4">
                    <div class="card shadow">
                        <div class="card-body">

                            <h3>Selecione a turma:</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <?php while ($turma = $turmasResult->fetch_assoc()): ?>
                                    <button class="btn btn-primary mb-2" onclick="window.location.href='cadastrar_notas_globais.php?turma_numero=<?= $turma['numero'] ?>&turma_ano=<?= $turma['ano'] ?>'">
                                        Turma: <?= htmlspecialchars($turma['numero']) ?> | Ano: <?= htmlspecialchars($turma['ano']) ?>
                                    </button><br>
                                <?php endwhile; ?>
                            </div>

                            <?php if ($selected_turma_numero && $selected_turma_ano): ?>
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="turma_numero" value="<?= $selected_turma_numero ?>">
                                    <input type="hidden" name="turma_ano" value="<?= $selected_turma_ano ?>">
                                    <hr>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead class="table-dark text-center">
                                                <tr>
                                                    <th>Aluno</th>
                                                    <th>AIS</th>
                                                    <th>Mostra de Ciências</th>
                                                    <th>PPI</th>
                                                    <th>Exame</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($student['nome']) ?></strong></td>

                                                        <!-- AIS -->
                                                        <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                                name="notas[<?= $student['numero_matricula'] ?>][ais]" 
                                                                value="<?= number_format($student['ais'], 2, '.', '') ?>"></td>

                                                        <!-- Mostra de Ciências -->
                                                        <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                                name="notas[<?= $student['numero_matricula'] ?>][mostra_ciencias]" 
                                                                value="<?= number_format($student['mostra_ciencias'], 2, '.', '') ?>"></td>

                                                        <!-- PPI -->
                                                        <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                                name="notas[<?= $student['numero_matricula'] ?>][ppi]" 
                                                                value="<?= number_format($student['ppi'], 2, '.', '') ?>"></td>

                                                        <!-- Exame -->
                                                        <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                                name="notas[<?= $student['numero_matricula'] ?>][nota_exame]" 
                                                                value="<?= number_format($student['nota_exame'], 2, '.', '') ?>"></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-save"></i> Salvar Notas
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
