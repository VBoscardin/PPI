<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Obter o nome e a foto do perfil do administrador logado
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Mensagens
// Mensagens (capturando e limpando)
$sucesso = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['erro']);


// Excluir disciplina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];

    // Verificar se a disciplina possui notas associadas
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notas WHERE disciplina_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $_SESSION['erro'] = 'Não é possível excluir esta disciplina. Existem notas associadas.';
        header("Location: listar_disciplinas.php");
        exit;
    } else {
        // Excluir dependências e a disciplina
        $stmt = $conn->prepare("DELETE FROM turmas_disciplinas WHERE disciplina_id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();

        $_SESSION['mensagem'] = 'Disciplina excluída com sucesso!';
        header("Location: listar_disciplinas.php");
        exit;
    }
}


// Atualizar disciplina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'])) {
    $editarId = $_POST['editar_id'];
    $novoNome = $_POST['novo_nome'];
    $novaTurma = $_POST['nova_turma'];
    $docentesSelecionados = isset($_POST['docentes']) ? $_POST['docentes'] : [];

    // Verificar se já existe uma disciplina com o mesmo nome na mesma turma
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM disciplinas 
                            JOIN turmas_disciplinas ON disciplinas.id = turmas_disciplinas.disciplina_id
                            WHERE disciplinas.nome = ? AND turmas_disciplinas.turma_numero = ? AND disciplinas.id != ?");
    $stmt->bind_param("sii", $novoNome, $novaTurma, $editarId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $erro = "Já existe uma disciplina com esse nome na mesma turma. Escolha um nome diferente ou outra turma.";
    } else {
        // Atualizar nome da disciplina
        $stmt = $conn->prepare("UPDATE disciplinas SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $novoNome, $editarId);
        $stmt->execute();

        // Atualizar a turma associada
        $stmt = $conn->prepare("UPDATE turmas_disciplinas SET turma_numero = ? WHERE disciplina_id = ?");
        $stmt->bind_param("ii", $novaTurma, $editarId);
        $stmt->execute();

        // Atualizar os docentes associados
        $conn->query("DELETE FROM docentes_disciplinas WHERE disciplina_id = $editarId");
        foreach ($docentesSelecionados as $docenteId) {
            $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (disciplina_id, docente_id, turma_numero) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $editarId, $docenteId, $novaTurma);
            $stmt->execute();
        }

        $_SESSION['mensagem'] = "Disciplina atualizada com sucesso!";
        header("Location: listar_disciplinas.php");
        exit;
    }
}

// Selecionar disciplinas com turma e docentes associados
// Selecionar disciplinas com turma e docentes associados
$query = "
    SELECT 
        disciplinas.id, 
        disciplinas.nome AS disciplina_nome, 
        turmas.numero AS turma_numero, 
        turmas.ano AS turma_ano,
        GROUP_CONCAT(docentes.nome SEPARATOR ', ') AS docentes_nomes
    FROM 
        disciplinas
    LEFT JOIN 
        turmas_disciplinas ON disciplinas.id = turmas_disciplinas.disciplina_id
    LEFT JOIN 
        turmas ON turmas_disciplinas.turma_numero = turmas.numero
    LEFT JOIN 
        docentes_disciplinas ON disciplinas.id = docentes_disciplinas.disciplina_id
    LEFT JOIN 
        docentes ON docentes_disciplinas.docente_id = docentes.id
    GROUP BY 
        disciplinas.id
    ORDER BY disciplinas.nome ASC
";

$disciplinas = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Consultar as notas para cada disciplina
foreach ($disciplinas as &$disciplina) {
    $notasQuery = "
        SELECT 
            notas.id, 
            discentes.nome AS discente_nome,
            notas.parcial_1, 
            notas.ais, 
            notas.nota_semestre_1,
            notas.parcial_2, 
            notas.mostra_ciencias, 
            notas.ppi,
            notas.nota_semestre_2, 
            notas.nota_final, 
            notas.nota_exame, 
            notas.faltas
        FROM 
            notas
        JOIN 
            discentes ON notas.discente_id = discentes.numero_matricula
        WHERE 
            notas.disciplina_id = ? AND notas.turma_numero = ?
    ";
    
    $stmt = $conn->prepare($notasQuery);
    $stmt->bind_param("ii", $disciplina['id'], $disciplina['turma_numero']);
    $stmt->execute();
    $result = $stmt->get_result();
    $disciplina['notas'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Disciplinas</title>
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
                        <div class="title ms-3">Listar e Editar Disciplinas</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador" width="50">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador" width="50">
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
                   
                        <!-- Campo de Pesquisa -->
<div class="mb-3">
    <div class="row">
        <div class="col-md-6">
            <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por Nome da Disciplina...">
        </div>
        <div class="col-md-3">
            <input type="text" id="filterTurma" class="form-control" placeholder="Filtrar por Turma...">
        </div>
        <div class="col-md-3">
            <input type="text" id="filterDocente" class="form-control" placeholder="Filtrar por Docente...">
        </div>
    </div>
</div>

                        <!-- Tabela de Disciplinas -->
                        <?php if (!empty($disciplinas)): ?>
                            <div class="table-responsive">
                                <table id="disciplinasTable" class="table table-bordered table-hover table-sm align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome da Disciplina</th>
                                            <th>Turma</th>
                                            <th>Docentes</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($disciplinas as $disciplina): ?>
                                            <tr class="disciplina-row">
                                            <p id="noResultsMessage" class="text-center text-danger" style="display:none;">Nenhuma Disciplina encontrada.</p>

                                                <td class="text-center"><?php echo htmlspecialchars($disciplina['id']); ?></td>
                                                <td class="disciplina-nome"><?php echo htmlspecialchars($disciplina['disciplina_nome']); ?></td>
                                                <td class="disciplina-turma"><?php echo htmlspecialchars($disciplina['turma_numero']); ?> - <?php echo htmlspecialchars($disciplina['turma_ano']); ?></td>
                                                <td class="disciplina-docentes"><?php echo htmlspecialchars($disciplina['docentes_nomes']); ?></td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center ">
                                                        <button class="btn btn-warning btn-sm custom-btn" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $disciplina['id']; ?>">
                                                            <i class="fas fa-edit me-2"></i> Editar
                                                        </button>
                                                        <button class="btn btn-success btn-sm custom-btn" type="button" onclick="toggleNotas(<?php echo $disciplina['id']; ?>)">
                                                            <i class="fas fa-book me-2"></i> Ver Notas
                                                        </button><button class="btn btn-danger btn-sm custom-btn" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $disciplina['id']; ?>">
                                                            <i class="fas fa-trash-alt me-2"></i> Excluir
                                                        </button>
                                                        
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Tabela de Notas (oculta inicialmente) -->
                                            <tr id="notasRow<?php echo $disciplina['id']; ?>" style="display: none;">
                                                <td colspan="5">
                                                    <table class="table  table-hover table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Nome do Aluno</th>
                                                                <th>Parcial 1</th>
                                                                <th>Nota Semestre 1</th>
                                                                <th>Parcial 2</th>
                                                                <th>Nota Semestre 2</th>
                                                                <th>Nota Final</th>
                                                                <th>Nota Exame</th>
                                                                <th>Faltas</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($disciplina['notas'] as $nota): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($nota['discente_nome']); ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['parcial_1']); ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['nota_semestre_1']); ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['parcial_2']); ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['nota_semestre_2']); ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['nota_final']); ?></td>
                                                                    <td><?php echo isset($nota['exame']) ? htmlspecialchars($nota['exame']) : 'N/A'; ?></td>
                                                                    <td><?php echo htmlspecialchars($nota['faltas']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal Editar -->
                                            <div class="modal fade" id="editarModal<?php echo $disciplina['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-white">
                                                            <h5 class="modal-title" id="editarModalLabel"><i class="fas fa-edit"></i> Editar Disciplina</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="editar_id" value="<?php echo $disciplina['id']; ?>">
                                                                
                                                                <!-- Nome da Disciplina -->
                                                                <div class="mb-3">
                                                                    <label for="novo_nome" class="form-label">Nome da Disciplina</label>
                                                                    <input type="text" name="novo_nome" class="form-control" value="<?php echo htmlspecialchars($disciplina['disciplina_nome']); ?>" required>
                                                                </div>
                                                                
                                                                <!-- Turma -->
                                                                <div class="mb-3">
                                                                    <label for="nova_turma" class="form-label">Turma</label>
                                                                    <select name="nova_turma" class="form-select" required>
                                                                        <option value="">Selecione uma turma</option>
                                                                        <?php
                                                                        // Consultar todas as turmas
                                                                        $stmt = $conn->prepare("SELECT numero, ano FROM turmas");
                                                                        $stmt->execute();
                                                                        $result = $stmt->get_result();

                                                                        // Carregar as turmas e selecionar a turma da disciplina
                                                                        while ($row = $result->fetch_assoc()): ?>
                                                                            <option value="<?php echo htmlspecialchars($row['numero']); ?>" 
                                                                                <?php echo ($row['numero'] == $disciplina['turma_numero']) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($row['numero']); ?> - <?php echo htmlspecialchars($row['ano']); ?>
                                                                            </option>
                                                                        <?php endwhile; ?>
                                                                    </select>
                                                                </div>
                                                                
                                                                <!-- Docentes -->
                                                                <div class="mb-3">
                                                                    <label for="docentes" class="form-label">Docentes</label>
                                                                    <select name="docentes[]" class="form-select" multiple required>
                                                                        <?php
                                                                        // Buscar docentes associados à disciplina atual
                                                                        $stmt = $conn->prepare("SELECT d.id, d.nome FROM docentes d
                                                                                                JOIN docentes_disciplinas dd ON d.id = dd.docente_id
                                                                                                WHERE dd.disciplina_id = ?");
                                                                        $stmt->bind_param("i", $disciplina['id']);
                                                                        $stmt->execute();
                                                                        $result = $stmt->get_result();
                                                                        $docentesSelecionados = [];
                                                                        while ($row = $result->fetch_assoc()) {
                                                                            $docentesSelecionados[] = $row['id'];
                                                                        }
                                                                        $stmt->close();

                                                                        // Exibir todos os docentes e marcar os selecionados
                                                                        $stmt = $conn->prepare("SELECT id, nome FROM docentes");
                                                                        $stmt->execute();
                                                                        $result = $stmt->get_result();
                                                                        while ($row = $result->fetch_assoc()): ?>
                                                                            <option value="<?php echo htmlspecialchars($row['id']); ?>"
                                                                                <?php echo in_array($row['id'], $docentesSelecionados) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($row['nome']); ?>
                                                                            </option>
                                                                        <?php endwhile; ?>
                                                                    </select>
                                                                    <small>Segure Ctrl para selecionar múltiplos docentes</small>
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
                                            <div class="modal fade" id="excluirModal<?php echo $disciplina['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-md">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="excluirModalLabel"><i class="fas fa-trash-alt"></i> Excluir Disciplina</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="delete_id" value="<?php echo $disciplina['id']; ?>">
                                                                <p>Tem certeza que deseja excluir a disciplina "<strong><?php echo htmlspecialchars($disciplina['disciplina_nome']); ?></strong>"?</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <button type="button" class="btn btn-secondary custom-btn" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-danger custom-btn">Excluir</button>
                                                                </div>
                                                            </div>    
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                           


                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">Nenhuma disciplina encontrada.</p>
                        <?php endif; ?>
                    </div>



                </div>
            </div>
        </div>
    </div>

     <!-- Scripts -->
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
     <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Captura os elementos de entrada de pesquisa
        const searchInput = document.getElementById('searchInput');
        const filterTurma = document.getElementById('filterTurma');
        const filterDocente = document.getElementById('filterDocente');
        const tableRows = document.querySelectorAll('.disciplina-row'); // Todas as linhas da tabela
        const noResultsMessage = document.getElementById('noResultsMessage'); // Mensagem de "Nenhum resultado encontrado"

        // Função de filtragem
        function filterTable() {
            const searchValue = searchInput.value.toLowerCase();
            const turmaValue = filterTurma.value.toLowerCase();
            const docenteValue = filterDocente.value.toLowerCase();

            let hasResults = false;

            // Percorre todas as linhas da tabela
            tableRows.forEach(row => {
                // Captura os valores das colunas relevantes
                const nomeDisciplina = row.querySelector('.disciplina-nome').textContent.toLowerCase();
                const turma = row.querySelector('.disciplina-turma').textContent.split('-')[0].trim().toLowerCase(); // Apenas o número da turma
                const docentes = row.querySelector('.disciplina-docentes').textContent.toLowerCase();

                // Verifica se os valores digitados correspondem ao conteúdo das colunas
                const matchesSearch = !searchValue || nomeDisciplina.includes(searchValue);
                const matchesTurma = !turmaValue || turma.includes(turmaValue); // Compara apenas o número da turma
                const matchesDocente = !docenteValue || docentes.includes(docenteValue);

                // Exibe ou oculta a linha com base na correspondência
                if (matchesSearch && matchesTurma && matchesDocente) {
                    row.style.display = ""; // Mostra a linha
                    hasResults = true;
                } else {
                    row.style.display = "none"; // Oculta a linha
                }
            });

            // Exibe ou oculta a mensagem de "Nenhum resultado encontrado"
            noResultsMessage.style.display = hasResults ? "none" : "";
        }

        // Adiciona os eventos de digitação (input) nos campos de pesquisa
        searchInput.addEventListener('input', filterTable);
        filterTurma.addEventListener('input', filterTable);
        filterDocente.addEventListener('input', filterTable);
    });
</script>

<script>

        // Ocultar mensagens automaticamente após 5 segundos
        setTimeout(() => {
            const sucesso = document.getElementById('mensagem-sucesso');
            const erro = document.getElementById('mensagem-erro');
            if (sucesso) sucesso.style.display = 'none';
            if (erro) erro.style.display = 'none';
        }, 3000); // 5 segundos
    </script>
<script>
    // Função para exibir ou ocultar as notas
    function toggleNotas(disciplinaId) {
        var notasRow = document.getElementById('notasRow' + disciplinaId);
        // Alternar a exibição das notas
        if (notasRow.style.display === 'none') {
            notasRow.style.display = 'table-row';  // Exibir as notas
        } else {
            notasRow.style.display = 'none';  // Ocultar as notas
        }
    }
</script>

</body>
</html>
