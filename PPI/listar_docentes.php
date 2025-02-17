<?php
require 'config.php';
session_start();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sucesso = "";
$erro = "";


$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Verificando se o método de requisição é POST e se o formulário foi enviado para editar um docente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_docente'])) {
    // Pegando os dados do formulário
    $docente_id = $_POST['docente_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $siape = $_POST['siape'];  // Verifique se este campo está sendo enviado corretamente
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];

    // Atualizando os dados do docente (incluindo o SIAPE)
    $sql_edit = "UPDATE docentes SET nome = ?, email = ?, siape = ? WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);

    // Verifique se a consulta de atualização foi preparada corretamente
    if ($stmt_edit) {
        $stmt_edit->bind_param("sssi", $nome, $email, $siape, $docente_id);
        if ($stmt_edit->execute()) {
            // Se a atualização foi bem-sucedida, atualize as disciplinas associadas
            $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $docente_id");
            $stmt_disciplinas = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)");
            foreach ($disciplinas as $disciplina_id) {
                $stmt_disciplinas->bind_param("ii", $docente_id, $disciplina_id);
                $stmt_disciplinas->execute();
            }
            $sucesso = "Docente atualizado com sucesso!";
        } else {
            // Se ocorrer um erro na atualização
            $erro = "Erro ao atualizar docente.";
        }
    } else {
        // Se não conseguir preparar a consulta SQL
        $erro = "Erro ao preparar consulta para atualizar docente.";
    }
}


// Excluir Docente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_docente'])) {
    $docente_id = $_POST['docente_id'];

    $sql_delete = "DELETE FROM docentes WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    if ($stmt_delete->bind_param("i", $docente_id) && $stmt_delete->execute()) {
        $sucesso = "Docente excluído com sucesso!";
    } else {
        $erro = "Erro ao excluir docente.";
    }
}



// Obter docentes, disciplinas e turmas
$sql = "
    SELECT 
        d.id AS docente_id,
        d.nome AS docente_nome,
        d.email AS docente_email,
        d.siape AS docente_siape,
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
        d.id
    ORDER BY 
        d.nome ASC;
";

$result = $conn->query($sql);

// Obter lista de disciplinas disponíveis para a seleção
$disciplinas_query = "SELECT id, nome FROM disciplinas";
$disciplinas_result = $conn->query($disciplinas_query);
$disciplinas_options = [];
while ($disciplina = $disciplinas_result->fetch_assoc()) {
    $disciplinas_options[] = $disciplina;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Listar Docentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar">
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_adm.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_adm.php'">
                            <i class="fas fa-plus"></i> Cadastrar Administrador
                        </button>
                        <button onclick="location.href='cadastrar_curso.php'">
                            <i class="fas fa-plus"></i> Cadastrar Curso
                        </button>
                        <button onclick="location.href='cadastrar_disciplina.php'">
                            <i class="fas fa-plus"></i> Cadastrar Disciplina
                        </button>
                        <button onclick="location.href='cadastrar_docente.php'">
                            <i class="fas fa-plus"></i> Cadastrar Docente
                        </button>
                        <button onclick="location.href='cadastrar_setor.php'">
                            <i class="fas fa-plus"></i> Cadastrar Setor
                        </button>
                        <button onclick="location.href='cadastrar_turma.php'">
                            <i class="fas fa-plus"></i> Cadastrar Turma
                        </button>
                    </div>
                </div>
                <button onclick="location.href='gerar_boletim.php'">
                    <i class="fas fa-file-alt"></i> Gerar Boletim
                </button>
                <button onclick="location.href='gerar_slide.php'">
                    <i class="fas fa-sliders-h"></i> Gerar Slide Pré Conselho
                </button>
                
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#list-menu" aria-expanded="false" aria-controls="list-menu">
                    <i id="toggle-icon" class="fas fa-list"></i> Listar
                </button>

                <div id="list-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='listar_administradores.php'">
                            <i class="fas fa-list"></i> Administradores
                        </button>
                        <button onclick="location.href='listar_cursos.php'">
                            <i class="fas fa-list"></i> Cursos
                        </button>
                        <button onclick="location.href='listar_discentes.php'">
                            <i class="fas fa-list"></i> Discentes
                        </button>
                        <button onclick="location.href='listar_disciplinas.php'">
                            <i class="fas fa-list"></i> Disciplinas
                        </button>
                        <button onclick="location.href='listar_docentes.php'">
                            <i class="fas fa-list"></i> Docentes
                        </button>
                        <button onclick="location.href='listar_setores.php'">
                            <i class="fas fa-list"></i> Setores
                        </button>
                        <button onclick="location.href='listar_turmas.php'">
                            <i class="fas fa-list"></i> Turmas
                        </button>
                    </div>
                </div>
                <button onclick="location.href='meu_perfil.php'">
                    <i class="fas fa-user"></i> Meu Perfil
                </button>
                <button class="btn btn-danger" onclick="location.href='sair.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>

            <div class="col-md-9 main-content">
            <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Listar e Editar Docentes</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                            <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container mt-4">
                     <!-- Mensagens de sucesso e erro -->
                     <?php if (!empty($sucesso)): ?>
                        <div id="mensagem-sucesso" class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($sucesso); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erro)): ?>
                        <div id="mensagem-erro" class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($erro); ?>
                        </div>
                    <?php endif; ?>


                    <div class="card shadow">
                        <div class="card-body">
                           <!-- Campos de Filtros -->
        <div class="mb-3 row g-3">
            <div class="col-md-3">
                <input type="text" id="filterNome" class="form-control" placeholder="Filtrar por Nome" onkeyup="filterTable()">
            </div>
            <div class="col-md-3">
                <input type="text" id="filterEmail" class="form-control" placeholder="Filtrar por Email" onkeyup="filterTable()">
            </div>
            <div class="col-md-3">
                <input type="text" id="filterSiape" class="form-control" placeholder="Filtrar por Siape" onkeyup="filterTable()">
            </div>
</div>



                            <div class="row">
                                <!-- Primeira coluna -->
                                
                                    <div class="table-responsive">
                                    <table id="docentesTable1" class="table table-bordered table-hover table-sm align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Siape</th>
                                                <th>Ações</th>
                                                <p id="noResultsMessage" class="text-center text-danger" style="display:none;">Nenhum Docente encontrado.</p>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $row['docente_id']; ?></td>
                                                        <td><?php echo $row['docente_nome']; ?></td>
                                                        <td><?php echo $row['docente_email']; ?></td>
                                                        <td><?php echo $row['docente_siape']; ?></td>
                                                        <td class="text-center">
                                                            <button class="btn btn-info btn-sm" onclick="toggleDetalhes(<?php echo $row['docente_id']; ?>)">
                                                                <i class="fas fa-eye" id="eye-icon-<?php echo $row['docente_id']; ?>"></i>
                                                                <span id="toggle-text-<?php echo $row['docente_id']; ?>">Ver Mais</span>
                                                            </button>
                                                        </td>
                                                        
                                                    </tr>

                                                    <!-- Linha de detalhes -->
                                                    <tr id="detalhes-<?php echo $row['docente_id']; ?>" class="detalhes-linha" style="display:none;">
                                                        <td colspan="5">
                                                            <table class="table table-bordered" style="background-color: white;">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Disciplinas e Turmas</th>
                                                                        <th>Ações</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody style="background-color: white;">
                                                                    <tr>
                                                                        <td style="background-color: white;">
                                                                            <?php
                                                                            $docente_id = $row['docente_id'];
                                                                            // Consultar disciplinas e turmas associadas ao docente
                                                                            $sql_disciplinas_turmas = "
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
                                                                                ORDER BY d.nome ASC, t.ano DESC, t.numero ASC;
                                                                            ";
                                                                            $stmt_disciplinas_turmas = $conn->prepare($sql_disciplinas_turmas);
                                                                            $stmt_disciplinas_turmas->bind_param("i", $docente_id);
                                                                            $stmt_disciplinas_turmas->execute();
                                                                            $result_disciplinas_turmas = $stmt_disciplinas_turmas->get_result();

                                                                            if ($result_disciplinas_turmas->num_rows > 0) {
                                                                                $disciplinas_turmas = [];
                                                                                while ($row_discip_turma = $result_disciplinas_turmas->fetch_assoc()) {
                                                                                    $disciplinas_turmas[] = htmlspecialchars($row_discip_turma['disciplina_nome']) . " - Turma " . htmlspecialchars($row_discip_turma['turma_numero']) . " (" . htmlspecialchars($row_discip_turma['turma_ano']) . ")";
                                                                                }
                                                                                echo implode('<br>', $disciplinas_turmas);
                                                                            } else {
                                                                                echo "Nenhuma disciplina e turma atribuída.";
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <td style="background-color: white;">
                                                                            <!-- Botões de Ação -->
                                                                            <button class="btn btn-warning btn-sm custom-btn" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['docente_id']; ?>">
                                                                                <i class="fas fa-edit me-2"></i> Editar
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm custom-btn" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['docente_id']; ?>">
                                                                                <i class="fas fa-trash-alt me-2"></i> Excluir
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>

                                                    <!-- Modal de Edição -->
                                                    <div class="modal fade" id="editarModal<?php echo $row['docente_id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title" id="editarModalLabel"><i class="fas fa-edit"></i> Editar Docente</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="" method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="edit_docente" value="1">
                                                                        <input type="hidden" name="docente_id" value="<?php echo $row['docente_id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="nome" class="form-label">Nome</label>
                                                                            <input type="text" name="nome" class="form-control" value="<?php echo $row['docente_nome']; ?>" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="email" class="form-label">Email</label>
                                                                            <input type="email" name="email" class="form-control" value="<?php echo $row['docente_email']; ?>" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="siape" class="form-label">Siape</label>
                                                                            <input type="text" name="siape" class="form-control" value="<?php echo $row['docente_siape']; ?>" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="disciplinas" class="form-label">Disciplinas e Turmas</label><br>
                                                                            <?php
                                                                            // Listar todas as disciplinas e turmas disponíveis
                                                                            foreach ($disciplinas_options as $disciplina) {
                                                                                $disciplinas_docente = [];
                                                                                $sql_check = "SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = ?";
                                                                                $stmt_check = $conn->prepare($sql_check);
                                                                                $stmt_check->bind_param("i", $row['docente_id']);
                                                                                $stmt_check->execute();
                                                                                $result_check = $stmt_check->get_result();

                                                                                while ($checked_row = $result_check->fetch_assoc()) {
                                                                                    $disciplinas_docente[] = $checked_row['disciplina_id'];
                                                                                }

                                                                                $checked = in_array($disciplina['id'], $disciplinas_docente) ? 'checked' : '';

                                                                                $sql_turma = "
                                                                                    SELECT t.numero AS turma_numero, t.ano AS turma_ano
                                                                                    FROM turmas_disciplinas td
                                                                                    JOIN turmas t ON td.turma_numero = t.numero
                                                                                    WHERE td.disciplina_id = ?";
                                                                                $stmt_turma = $conn->prepare($sql_turma);
                                                                                $stmt_turma->bind_param("i", $disciplina['id']);
                                                                                $stmt_turma->execute();
                                                                                $result_turma = $stmt_turma->get_result();

                                                                                if ($result_turma->num_rows > 0) {
                                                                                    while ($turma_row = $result_turma->fetch_assoc()) {
                                                                                        echo "<input type='checkbox' name='disciplinas[]' value='" . $disciplina['id'] . "' $checked> " . $disciplina['nome'] . " (Turma " . $turma_row['turma_numero'] . " - " . $turma_row['turma_ano'] . ")<br>";
                                                                                    }
                                                                                }
                                                                            }
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Modal Excluir -->
                                                    <div class="modal fade" id="deleteModal<?php echo $row['docente_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-md">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-trash-alt"></i> Excluir Docente</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST" action="">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="delete_docente" value="1">
                                                                        <input type="hidden" name="docente_id" value="<?php echo $row['docente_id']; ?>">
                                                                        <p>Tem certeza de que deseja excluir o docente <strong><?php echo $row['docente_nome']; ?></strong>?</p>
                                                                    
                                                                        <div class="modal-footer">
                                                                        <div class="d-flex gap-2 justify-content-center">
                                                                        <button type="button" class="btn btn-secondary custom-btn" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-danger custom-btn">Excluir</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>Nenhum docente encontrado.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleDetalhes(docenteId) {
    const detalhesLinha = document.getElementById(`detalhes-${docenteId}`);
    const eyeIcon = document.getElementById(`eye-icon-${docenteId}`);
    const toggleText = document.getElementById(`toggle-text-${docenteId}`);

    if (detalhesLinha.style.display === "none") {
        // Exibe a linha de detalhes
        detalhesLinha.style.display = "table-row";
        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");
        toggleText.textContent = "Ver Menos";
    } else {
        // Oculta a linha de detalhes
        detalhesLinha.style.display = "none";
        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
        toggleText.textContent = "Ver Mais";
    }

    // Remova esta linha abaixo (opcional):
    // filterTable();
}



    </script>
</body>
</html>
<script>
// Função de filtro para a tabela
function filterTable() {
    const nomeFilter = document.getElementById('filterNome').value.trim().toLowerCase();
    const emailFilter = document.getElementById('filterEmail').value.trim().toLowerCase();
    const siapeFilter = document.getElementById('filterSiape').value.trim().toLowerCase();
    const rows = document.querySelectorAll('#docentesTable1 tbody tr');
    let resultsFound = false; // Variável para verificar se há resultados

    rows.forEach(row => {
        // Ignora as linhas de detalhes (id começa com "detalhes-")
        if (row.id.startsWith("detalhes-")) {
            return;
        }

        const nome = row.cells[1]?.textContent.trim().toLowerCase() || '';
        const email = row.cells[2]?.textContent.trim().toLowerCase() || '';
        const siape = row.cells[3]?.textContent.trim().toLowerCase() || '';

        // Aplica os filtros (verifica se todos os filtros batem)
        if (
            nome.includes(nomeFilter) &&
            email.includes(emailFilter) &&
            siape.includes(siapeFilter)
        ) {
            row.style.display = ''; // Mostra a linha principal
            const detalhesRow = document.getElementById(`detalhes-${row.cells[0].textContent}`);
            if (detalhesRow) {
                detalhesRow.style.display = 'none'; // Oculta os detalhes inicialmente
            }
            resultsFound = true; // Encontrou resultados
        } else {
            row.style.display = 'none'; // Esconde a linha principal
            const detalhesRow = document.getElementById(`detalhes-${row.cells[0].textContent}`);
            if (detalhesRow) {
                detalhesRow.style.display = 'none'; // Esconde também os detalhes
            }
        }
    });

    // Exibe ou oculta a mensagem de "Nenhum resultado encontrado"
    const noResultsMessage = document.getElementById('noResultsMessage');
    if (resultsFound) {
        noResultsMessage.style.display = 'none'; // Esconde a mensagem se houver resultados
    } else {
        noResultsMessage.style.display = 'block'; // Exibe a mensagem se não houver resultados
    }
}







</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"> </script>
<script>
// Ocultar mensagens automaticamente após 5 segundos
setTimeout(() => {
    const sucesso = document.getElementById('mensagem-sucesso');
    const erro = document.getElementById('mensagem-erro');
    if (sucesso) sucesso.style.display = 'none'; // Ocultar mensagem de sucesso
    if (erro) erro.style.display = 'none'; // Ocultar mensagem de erro
}, 5000); // 5 segundos

</script>

<?php
$conn->close();
?>
