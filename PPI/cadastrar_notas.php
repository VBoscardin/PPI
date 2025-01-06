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
    ORDER BY d.nome ASC
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
                    nota_final = ?, faltas = ?, observacoes = ? 
                WHERE discente_id = ? AND disciplina_id = ?
            ");
            $stmt->bind_param("dddddisssi", 
                $parcial_1, $nota_semestre_1, $parcial_2, $nota_semestre_2, 
                $nota_final, $faltas, $observacoes, $matricula, $selected_discipline_id);
        } else {
            // Inserir nova nota
            $stmt = $conn->prepare("
                INSERT INTO notas (discente_id, disciplina_id, turma_numero, parcial_1, nota_semestre_1, parcial_2, nota_semestre_2, 
                                   nota_final, faltas, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiidddddis", 
                $matricula, $selected_discipline_id, $turma_numero, $parcial_1, $nota_semestre_1, 
                $parcial_2, $nota_semestre_2, $nota_final, $faltas, $observacoes);
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
            /* Estilo customizado para o botão */
        .custom-btn {
            transition: all 0.3s ease;
        }

        /* Quando passar o mouse */
        .custom-btn:hover {
            background-color: #28a745;  /* Cor verde */
            color: #000;  /* Texto preto */
        }

        .nota-vermelha {
        color: red;
        
        }
        .nota-amarela {
            color: orange;
            
        }
        .nota-verde {
            color: green;
            
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
            
                <div class="container-fluid mt-4"> <!-- Alterado para container-fluid para largura total -->
    <div class="card shadow">
        <div class="card-body">

            <h3>Disciplinas que você leciona:</h3>
            <div class="d-flex flex-wrap gap-2">
                <?php while ($discipline = $disciplinesResult->fetch_assoc()): ?>
                    <button class="btn btn-success mb-2" onclick="window.location.href='cadastrar_notas.php?disciplina_id=<?= $discipline['id'] ?>'">
                        <strong><?= htmlspecialchars($discipline['nome']) ?></strong><br>Turma: <?= htmlspecialchars($discipline['turma_numero']) ?> | Ano: <?= htmlspecialchars($discipline['turma_ano']) ?>
                    </button><br>
                <?php endwhile; ?>
            </div>

            <?php if ($selected_discipline_id): ?>
                <?php
                $studentsQuery = $conn->prepare("
                    SELECT 
                        dt.numero_matricula, 
                        ds.nome, 
                        MAX(n.parcial_1) AS parcial_1, 
                        MAX(n.nota_semestre_1) AS nota_semestre_1,
                        MAX(n.parcial_2) AS parcial_2, 
                        MAX(n.nota_semestre_2) AS nota_semestre_2,
                        MAX(n.nota_final) AS nota_final, 
                        MAX(n.nota_exame) AS nota_exame,
                        MAX(n.faltas) AS faltas, 
                        MAX(n.observacoes) AS observacoes
                    FROM 
                        discentes_turmas dt
                    JOIN 
                        discentes ds 
                        ON dt.numero_matricula = ds.numero_matricula
                    LEFT JOIN 
                        notas n 
                        ON n.discente_id = dt.numero_matricula 
                        AND n.disciplina_id = ?
                    WHERE 
                        dt.turma_numero = (
                            SELECT turma_numero 
                            FROM turmas_disciplinas 
                            WHERE disciplina_id = ? 
                            LIMIT 1
                        )
                    GROUP BY 
                        dt.numero_matricula
                    ORDER BY 
                        ds.nome ASC
                ");
            
                $studentsQuery->bind_param("ii", $selected_discipline_id, $selected_discipline_id);
                $studentsQuery->execute();
                $studentsResult = $studentsQuery->get_result();
                ?>
                
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="disciplina_id" value="<?= $selected_discipline_id ?>">
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover "> <!-- Largura mínima ajustada -->
                                    <thead class="table-dark text-center">
                                        
                                    <?php if ($selected_discipline_id): ?>
                                        <?php
                                        // Obter o nome da disciplina e o número da turma selecionada
                                        $disciplineQuery = $conn->prepare("
                                            SELECT d.nome, td.turma_numero
                                            FROM disciplinas d
                                            JOIN turmas_disciplinas td ON d.id = td.disciplina_id
                                            WHERE d.id = ?
                                            ORDER BY d.nome ASC
                                        ");
                                        $disciplineQuery->bind_param("i", $selected_discipline_id);
                                        $disciplineQuery->execute();
                                        $disciplineQuery->bind_result($discipline_name, $turma_numero);
                                        $disciplineQuery->fetch();
                                        $disciplineQuery->close();
                                        ?>

                                        <h3>Alunos na disciplina de <?= htmlspecialchars($discipline_name) ?> - Turma: <?= htmlspecialchars($turma_numero) ?></h3>
                                    <?php endif; ?>


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

                                                <!-- Parcial 1 -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][parcial_1]" 
                                                        value="<?= number_format($student['parcial_1'], 2, '.', '') ?>"></td>

                                            
                                                <!-- Nota semestre 1 -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_1]" 
                                                        value="<?= number_format($student['nota_semestre_1'], 2, '.', '') ?>"></td>

                                                <!-- Parcial 2 -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][parcial_2]" 
                                                        value="<?= number_format($student['parcial_2'], 2, '.', '') ?>"></td>

                                                <!-- Nota semestre 2 -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][nota_semestre_2]" 
                                                        value="<?= number_format($student['nota_semestre_2'], 2, '.', '') ?>"></td>

                                                <!-- Nota final - Somente leitura -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][nota_final]" 
                                                        value="<?= number_format($student['nota_final'], 2, '.', '') ?>" readonly></td>

                                                <!-- Nota exame -->
                                                <td><input type="text" inputmode="decimal" step="0.01" class="form-control nota-input" 
                                                        name="notas[<?= $student['numero_matricula'] ?>][nota_exame]" 
                                                        value="<?= number_format($student['nota_exame'], 2, '.', '') ?>"></td>

                                                <!-- Faltas -->
                                                <td><input type="number" class="form-control" name="notas[<?= $student['numero_matricula'] ?>][faltas]" 
                                                        value="<?= $student['faltas'] ?>"></td>

                                                <!-- Observações -->
                                                <td><textarea class="form-control" rows="2" name="notas[<?= $student['numero_matricula'] ?>][observacoes]"><?= htmlspecialchars($student['observacoes']) ?></textarea></td>
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
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>




<script>
    document.addEventListener("DOMContentLoaded", function () {
    const tabela = document.querySelector("table");

    // Função para formatar valores somente ao sair do campo
    function formatarValor(input) {
        let value = input.value.replace(",", "."); // Substitui vírgulas por pontos
        value = parseFloat(value); // Converte para número

        if (!isNaN(value)) {
            if (value >= 100) {
                value = value / 100; // Ajusta valores no formato como 875 -> 8,75
            } else if (value > 10 && value < 100) {
                value = value / 10; // Ajusta valores no formato como 87 -> 8,7
            }
            value = Math.min(Math.max(value, 0), 10); // Restringe ao intervalo de 0 a 10
            input.value = value.toFixed(2).replace(".", ","); // Atualiza o valor com duas casas decimais
        } else {
            input.value = ""; // Limpa o campo se o valor não for válido
        }
    }

    // Função para calcular a Nota Final
    function calcularNotaFinal(row) {
        const semestre1Field = row.querySelector('[name*="[nota_semestre_1]"]');
        const semestre2Field = row.querySelector('[name*="[nota_semestre_2]"]');
        const notaFinalField = row.querySelector('[name*="[nota_final]"]');
        const notaExameField = row.querySelector('[name*="[nota_exame]"]');

        const semestre1 = parseFloat(semestre1Field?.value.replace(",", "."));
        const semestre2 = parseFloat(semestre2Field?.value.replace(",", "."));

        // Exemplo de cálculo: média simples entre Semestre 1 e Semestre 2
        if (!isNaN(semestre1) && !isNaN(semestre2)) {
            const notaFinal = (semestre1 + semestre2) / 2;
            notaFinalField.value = notaFinal.toFixed(2).replace(".", ",");
        } else {
            notaFinalField.value = ""; // Limpa a Nota Final se os valores forem inválidos
        }

        formatarNotaFinal(notaFinalField); // Aplica estilos à Nota Final

        // Verifica se Nota Final >= 7 para limpar ou liberar o campo Nota Exame
        const notaFinal = parseFloat(notaFinalField?.value.replace(",", "."));
        if (notaFinal >= 7) {
            notaExameField.value = ""; // Limpa o campo Nota Exame
            notaExameField.setAttribute("readonly", "readonly"); // Torna o campo somente leitura
            notaExameField.classList.remove("nota-vermelha", "nota-amarela", "nota-verde"); // Remove qualquer estilo
        } else {
            notaExameField.removeAttribute("readonly"); // Desbloqueia o campo para edição
        }
    }

    // Função para aplicar estilos gerais (incluindo Parcial 1 e Parcial 2)
    function formatarNota(input) {
        const valor = parseFloat(input.value.replace(",", "."));
        input.classList.remove("nota-vermelha", "nota-amarela", "nota-verde");

        if (!isNaN(valor)) {
            if (valor < 6) {
                input.classList.add("nota-vermelha");
            } else if (valor >= 6 && valor < 7) {
                input.classList.add("nota-amarela");
            } else if (valor >= 7) {
                input.classList.add("nota-verde");
            }
        }
    }

    // Função para aplicar estilos SOMENTE ao campo de Nota Final
    function formatarNotaFinal(input) {
        const valor = parseFloat(input.value.replace(",", "."));
        const isNA = input.value === "N/A";

        input.classList.remove("nota-vermelha", "nota-amarela", "nota-verde");

        if (isNA || valor >= 7) {
            input.classList.add("nota-verde");
        } else if (!isNaN(valor)) {
            if (valor < 6) {
                input.classList.add("nota-vermelha");
            } else if (valor >= 6 && valor < 7) {
                input.classList.add("nota-amarela");
            }
        }
    }

    // Função para inicializar os estilos da tabela ao carregar a página
    function inicializarTabela() {
        const rows = tabela.querySelectorAll("tr");

        rows.forEach(row => {
            const parcial1Field = row.querySelector('[name*="[parcial_1]"]');
            const parcial2Field = row.querySelector('[name*="[parcial_2]"]');
            const notaFinalField = row.querySelector('[name*="[nota_final]"]');
            const semestre1Field = row.querySelector('[name*="[nota_semestre_1]"]');
            const semestre2Field = row.querySelector('[name*="[nota_semestre_2]"]');
            const notaExameField = row.querySelector('[name*="[nota_exame]"]');

            if (parcial1Field) {
                formatarValor(parcial1Field); // Formata valores
                formatarNota(parcial1Field); // Aplica estilo
            }

            if (parcial2Field) {
                formatarValor(parcial2Field);
                formatarNota(parcial2Field);
            }

            if (semestre1Field) {
                formatarValor(semestre1Field);
                formatarNota(semestre1Field);
            }

            if (semestre2Field) {
                formatarValor(semestre2Field);
                formatarNota(semestre2Field);
            }

            if (notaFinalField) {
                calcularNotaFinal(row); // Calcula Nota Final ao carregar
            }

            if (notaExameField) {
                const notaFinal = parseFloat(notaFinalField?.value.replace(",", "."));
                if (notaFinal >= 7) {
                    notaExameField.value = ""; // Limpa o campo Nota Exame
                    notaExameField.setAttribute("readonly", "readonly"); // Torna o campo somente leitura
                    notaExameField.classList.remove("nota-vermelha", "nota-amarela", "nota-verde");
                } else {
                    notaExameField.removeAttribute("readonly"); // Desbloqueia o campo para edição
                }
            }
        });
    }

    // Chama a função de inicialização ao carregar a página
    inicializarTabela();

    // Evento de input e blur para formatação e estilos dinâmicos
    tabela.addEventListener("blur", function (event) {
        const input = event.target;
        const row = input.closest("tr");

        if (row) {
            const parcial1Field = row.querySelector('[name*="[parcial_1]"]');
            const parcial2Field = row.querySelector('[name*="[parcial_2]"]');
            const semestre1Field = row.querySelector('[name*="[nota_semestre_1]"]');
            const semestre2Field = row.querySelector('[name*="[nota_semestre_2]"]');

            if (input === parcial1Field || input === parcial2Field || input === semestre1Field || input === semestre2Field) {
                formatarValor(input); // Aplica formatação de valor
                formatarNota(input); // Aplica estilos
                calcularNotaFinal(row); // Recalcula a Nota Final
            }
        }
    }, true);
});

</script>
</html>
