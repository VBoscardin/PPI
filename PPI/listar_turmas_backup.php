<?php
// Inclui o arquivo de configuração para conexão com o banco de dados
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start(); // Inicia a sessão para usar $_SESSION
    if (isset($_POST['numero_atual'])) {
        $numero_atual = $_POST['numero_atual'];
        $novo_numero = $_POST['numero'];
        $ano = $_POST['ano'];
        $ano_ingresso = $_POST['ano_ingresso'];
        $ano_oferta = $_POST['ano_oferta'];
        $curso_id = $_POST['curso_id'];
        $professor_regente = !empty($_POST['professor_regente']) ? $_POST['professor_regente'] : null;
        $presidente = !empty($_POST['presidente']) ? $_POST['presidente'] : null;

        $conn->begin_transaction();
        try {
            // Atualiza as tabelas relacionadas
            $update_notas = "UPDATE notas SET turma_numero = ? WHERE turma_numero = ?";
            $stmt1 = $conn->prepare($update_notas);
            $stmt1->bind_param('ii', $novo_numero, $numero_atual);
            $stmt1->execute();

            $update_discentes = "UPDATE discentes_turmas SET turma_numero = ? WHERE turma_numero = ?";
            $stmt2 = $conn->prepare($update_discentes);
            $stmt2->bind_param('ii', $novo_numero, $numero_atual);
            $stmt2->execute();

            $update_turmas_disciplinas = "UPDATE turmas_disciplinas SET turma_numero = ? WHERE turma_numero = ?";
            $stmt3 = $conn->prepare($update_turmas_disciplinas);
            $stmt3->bind_param('ii', $novo_numero, $numero_atual);
            $stmt3->execute();

            // Atualiza a tabela `turmas`
            $update_turmas = "
                UPDATE turmas 
                SET numero = ?, ano = ?, ano_ingresso = ?, ano_oferta = ?, curso_id = ?, professor_regente = ?, presidente_id = ? 
                WHERE numero = ?";
            $stmt4 = $conn->prepare($update_turmas);
            $stmt4->bind_param(
                'iiiiiiii',
                $novo_numero,
                $ano,
                $ano_ingresso,
                $ano_oferta,
                $curso_id,
                $professor_regente,
                $presidente,
                $numero_atual
            );
            $stmt4->execute();

            $conn->commit();

            // Define mensagem de sucesso
            $_SESSION['mensagem'] = 'Turma atualizada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header("Location: listar_turmas.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();

            // Define mensagem de erro
            $_SESSION['mensagem'] = 'Erro ao atualizar a turma: ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'danger';
            header("Location: listar_turmas.php");
            exit;
        }
    } else {
        // Campo `numero_atual` não foi encontrado
        $_SESSION['mensagem'] = 'O campo número atual não foi enviado.';
        $_SESSION['tipo_mensagem'] = 'warning';
        header("Location: listar_turmas.php");
        exit;
    }
}





// Consulta SQL para obter as turmas
$sql = "
    SELECT 
        turmas.numero, 
        turmas.ano, 
        turmas.ano_ingresso, 
        turmas.ano_oferta, 
        cursos.nome AS curso_nome, 
        turmas.curso_id,
        turmas.professor_regente,
        turmas.professor_regente AS professor_regente_id,
        turmas.presidente_id,
        discentes.nome AS presidente_nome
    FROM 
        turmas
    INNER JOIN cursos ON turmas.curso_id = cursos.id
    LEFT JOIN discentes ON turmas.presidente_id = discentes.numero_matricula
    ORDER BY turmas.ano DESC, turmas.numero ASC
";

$result = $conn->query($sql);

// Consultas para obter todos os docentes e discentes
$docentes_query = "SELECT id, nome FROM docentes ORDER BY nome";
$docentes_result = $conn->query($docentes_query);

$discentes_query = "SELECT numero_matricula, nome FROM discentes ORDER BY nome";
$discentes_result = $conn->query($discentes_query);

// Consulta para obter as disciplinas e seus respectivos docentes para cada turma
$disciplinas_query = "
    SELECT 
        disciplinas.nome AS disciplina_nome,
        docentes.nome AS docente_nome,
        turmas_disciplinas.turma_numero
    FROM 
        turmas_disciplinas
    INNER JOIN disciplinas ON turmas_disciplinas.disciplina_id = disciplinas.id
    INNER JOIN docentes_disciplinas ON disciplinas.id = docentes_disciplinas.disciplina_id
    INNER JOIN docentes ON docentes_disciplinas.docente_id = docentes.id
    ORDER BY disciplinas.nome
";
$disciplinas_result = $conn->query($disciplinas_query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Turmas</title>
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
                <!-- Mensagens de Sucesso e Erro -->
                <?php
   
                if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem']); ?> alert-dismissible fade show" role="alert" id="alertaMensagem">
                        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
                    </div>
                    <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                    <!-- Campo de Pesquisa -->
                    <div class="mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar...">
                    </div>
                    <div class="table-responsive">


                    <?php if ($result->num_rows > 0): ?>
                    <table id="turmasTable" class="table table-bordered table-hover table-sm align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Número</th>
                                <th>Ano</th>
                                <th>Ano de Ingresso</th>
                                <th>Ano de Oferta</th>
                                <th>Curso</th>
                                <th>Professor Regente</th>
                                <th>Presidente da Turma</th>
                                <th>Disciplinas</th>
                                <th>Discentes</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['numero']); ?></td>
                                <td><?php echo htmlspecialchars($row['ano']); ?></td>
                                <td><?php echo htmlspecialchars($row['ano_ingresso']); ?></td>
                                <td><?php echo htmlspecialchars($row['ano_oferta']); ?></td>
                                <td><?php echo htmlspecialchars($row['curso_nome']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($row['professor_regente_id'])) {
                                        $professor_query = "SELECT nome FROM docentes WHERE id = ?";
                                        $stmt_prof = $conn->prepare($professor_query);
                                        $stmt_prof->bind_param('i', $row['professor_regente_id']);
                                        $stmt_prof->execute();
                                        $professor_result = $stmt_prof->get_result();
                                        $professor = $professor_result->fetch_assoc();
                                        echo htmlspecialchars($professor['nome']);
                                    } else {
                                        echo "Sem Regente";
                                    }
                                    ?>
                                </td>

                                <td><?php echo htmlspecialchars($row['presidente_nome'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $disciplinas_result->data_seek(0);
                                    while ($disciplina = $disciplinas_result->fetch_assoc()) {
                                        if ($disciplina['turma_numero'] == $row['numero']) {
                                            echo "<p>" . htmlspecialchars($disciplina['disciplina_nome']) . " - " . htmlspecialchars($disciplina['docente_nome']) . "</p>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $discentes_turma_query = "
                                    SELECT 
                                        discentes.nome 
                                    FROM 
                                        discentes_turmas
                                    INNER JOIN discentes ON discentes_turmas.numero_matricula = discentes.numero_matricula
                                    WHERE 
                                        discentes_turmas.turma_numero = " . $row['numero'];
                                    
                                    $discentes_turma_result = $conn->query($discentes_turma_query);
                                    
                                    if ($discentes_turma_result->num_rows > 0) {
                                        while ($discente = $discentes_turma_result->fetch_assoc()) {
                                            echo "<p>" . htmlspecialchars($discente['nome']) . "</p>";
                                        }
                                    } else {
                                        echo "<p>Nenhum discente associado.</p>";
                                    }
                                    ?>
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#informacoesModal<?php echo $row['numero']; ?>">
                                        <i class="fas fa-info-circle"></i> Mais Informações
                                    </button>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $row['numero']; ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                </td>

                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p>Nenhuma turma encontrada.</p>
                        <?php endif; ?>

                        <?php 
                        $result->data_seek(0);
                        while($row = $result->fetch_assoc()):
                            $turmaNumero = $row['numero'];
                        ?>
                        <div class="modal fade" id="editarModal<?php echo $row['numero']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $row['numero']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    
                                    <form method="POST" action="">
                                    <div class="modal-header bg-warning text-white">
                                            <h5 class="modal-title" id="editarModalLabel<?php echo $row['numero']; ?>"><i class="fas fa-edit"></i>Editar Turma</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="numero_atual" value="<?php echo $turmaNumero; ?>">
                                            <!-- Campos do formulário com valores pré-preenchidos -->
                                            <div class="form-group">
                                                <label for="numero">Número</label>
                                                <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($row['numero']); ?>" required>
                                            </div>


                                            <!-- Ano -->
                                            <div class="mb-3">
                                                <label for="ano_<?php echo $row['numero']; ?>" class="form-label">Ano</label>
                                                <input type="number" class="form-control" id="ano_<?php echo $row['numero']; ?>" name="ano" value="<?php echo htmlspecialchars($row['ano']); ?>" required>
                                            </div>

                                            <!-- Ano de Ingresso -->
                                            <div class="mb-3">
                                                <label for="ano_ingresso_<?php echo $row['numero']; ?>" class="form-label">Ano de Ingresso</label>
                                                <input type="number" class="form-control" id="ano_ingresso_<?php echo $row['numero']; ?>" name="ano_ingresso" value="<?php echo htmlspecialchars($row['ano_ingresso']); ?>" required>
                                            </div>

                                            <!-- Ano de Oferta -->
                                            <div class="mb-3">
                                                <label for="ano_oferta_<?php echo $row['numero']; ?>" class="form-label">Ano de Oferta</label>
                                                <input type="number" class="form-control" id="ano_oferta_<?php echo $row['numero']; ?>" name="ano_oferta" value="<?php echo htmlspecialchars($row['ano_oferta']); ?>" required>
                                            </div>

                                            <!-- Curso -->
                                            <div class="mb-3">
                                                <label for="curso_id_<?php echo $row['numero']; ?>" class="form-label">Curso</label>
                                                <select class="form-select" id="curso_id_<?php echo $row['numero']; ?>" name="curso_id" required>
                                                    <?php
                                                    $curso_query = "SELECT * FROM cursos";
                                                    $curso_result = $conn->query($curso_query);
                                                    while ($curso = $curso_result->fetch_assoc()):
                                                        $selected = ($curso['id'] == $row['curso_id']) ? 'selected' : '';
                                                        echo "<option value='" . $curso['id'] . "' $selected>" . htmlspecialchars($curso['nome']) . "</option>";
                                                    endwhile;
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Professor Regente -->
                                            <div class="mb-3">
                                                <label for="professor_regente_<?php echo $row['numero']; ?>" class="form-label">Professor Regente</label>
                                                <select class="form-select" id="professor_regente_<?php echo $row['numero']; ?>" name="professor_regente" >
                                                <option value="" <?php echo is_null($row['professor_regente']) ? 'selected' : ''; ?>>Sem Regente</option>
                                                
                                                    <?php
                                                    // Consultar todos os professores (docentes)
                                                    $docentes_query = "SELECT id, nome FROM docentes ORDER BY nome";
                                                    $docentes_result = $conn->query($docentes_query);

                                                    // Exibir todos os professores no select
                                                    while ($docente = $docentes_result->fetch_assoc()):
                                                        // Verificar se o docente é o professor regente da turma
                                                        $selected = ($docente['id'] == $row['professor_regente']) ? 'selected' : '';
                                                        echo "<option value='" . $docente['id'] . "' $selected>" . htmlspecialchars($docente['nome']) . "</option>";
                                                    endwhile;
                                                    ?>
                                                </select>
                                            </div>


                                            
                                            <!-- Presidente -->
                                            <div class="mb-3">
                                                <label for="presidente_<?php echo $row['numero']; ?>" class="form-label">Presidente</label>
                                                <select class="form-select" id="presidente_<?php echo $row['numero']; ?>" name="presidente" >
                                                <option value="" <?php echo is_null($row['presidente_id']) ? 'selected' : ''; ?>>Sem Presidente</option>

                                                    <?php
                                                    // Consulta para obter os discentes dessa turma específica
                                                    $discentes_turma_query = "
                                                    SELECT discentes.numero_matricula, discentes.nome 
                                                    FROM discentes_turmas
                                                    INNER JOIN discentes ON discentes_turmas.numero_matricula = discentes.numero_matricula
                                                    WHERE discentes_turmas.turma_numero = " . $row['numero'];
                                                    
                                                    $discentes_turma_result = $conn->query($discentes_turma_query);
                                                    
                                                    // Exibe os discentes associados a essa turma
                                                    while ($discente = $discentes_turma_result->fetch_assoc()):
                                                        $selected = ($discente['numero_matricula'] == $row['presidente_id']) ? 'selected' : '';
                                                        echo "<option value='" . $discente['numero_matricula'] . "' $selected>" . htmlspecialchars($discente['nome']) . "</option>";
                                                    endwhile;
                                                    ?>
                                                </select>
                                            </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success">Salvar alterações</button>
                                            </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    
                        <?php endwhile; ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <div class="modal fade" id="informacoesModal<?php echo $row['numero']; ?>" tabindex="-1" aria-labelledby="informacoesModalLabel<?php echo $row['numero']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title" id="informacoesModalLabel<?php echo $row['numero']; ?>"><i class="fas fa-info-circle"></i> Mais Informações - Turma <?php echo htmlspecialchars($row['numero']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                <div class="modal-body"><div class="modal fade" id="informacoesModal<?php echo $row['numero']; ?>" tabindex="-1" aria-labelledby="informacoesModalLabel<?php echo $row['numero']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title" id="informacoesModalLabel<?php echo $row['numero']; ?>"><i class="fas fa-info-circle"></i> Mais Informações da Turma</h5>
                                                <p><strong>Ano:</strong> <?php echo htmlspecialchars($row['ano']); ?></p>
                                                <p><strong>Ano de Ingresso:</strong> <?php echo htmlspecialchars($row['ano_ingresso']); ?></p>
                                                <p><strong>Ano de Oferta:</strong> <?php echo htmlspecialchars($row['ano_oferta']); ?></p>
                                                <p><strong>Curso:</strong> <?php echo htmlspecialchars($row['curso_nome']); ?></p>
                                                <p><strong>Professor Regente:</strong> 
                                                    <?php 
                                                        if (!empty($row['professor_regente_id'])) {
                                                            $professor_query = "SELECT nome FROM docentes WHERE id = ?";
                                                            $stmt_prof = $conn->prepare($professor_query);
                                                            $stmt_prof->bind_param('i', $row['professor_regente_id']);
                                                            $stmt_prof->execute();
                                                            $professor_result = $stmt_prof->get_result();
                                                            $professor = $professor_result->fetch_assoc();
                                                            echo htmlspecialchars($professor['nome']);
                                                        } else {
                                                            echo "Sem Regente";
                                                        }
                                                    ?>
                                                </p>
                                                <p><strong>Presidente da Turma:</strong> <?php echo htmlspecialchars($row['presidente_nome'] ?: 'N/A'); ?></p>
                                                <p><strong>Disciplinas:</strong></p>
                                                <ul>
                                                    <?php
                                                        $disciplinas_result->data_seek(0);
                                                        while ($disciplina = $disciplinas_result->fetch_assoc()) {
                                                            if ($disciplina['turma_numero'] == $row['numero']) {
                                                                echo "<li>" . htmlspecialchars($disciplina['disciplina_nome']) . " - " . htmlspecialchars($disciplina['docente_nome']) . "</li>";
                                                            }
                                                        }
                                                    ?>
                                                </ul>
                                                <p><strong>Discentes:</strong></p>
                                                <ul>
                                                    <?php
                                                        $discentes_turma_query = "
                                                        SELECT 
                                                            discentes.nome 
                                                        FROM 
                                                            discentes_turmas
                                                        INNER JOIN discentes ON discentes_turmas.numero_matricula = discentes.numero_matricula
                                                        WHERE 
                                                            discentes_turmas.turma_numero = " . $row['numero'];

                                                        $discentes_turma_result = $conn->query($discentes_turma_query);

                                                        if ($discentes_turma_result->num_rows > 0) {
                                                            while ($discente = $discentes_turma_result->fetch_assoc()) {
                                                                echo "<li>" . htmlspecialchars($discente['nome']) . "</li>";
                                                            }
                                                        } else {
                                                            echo "<li>Nenhum discente associado.</li>";
                                                        }
                                                    ?>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>


                            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                            <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const searchInput = document.getElementById("searchInput");
                                const table = document.getElementById("turmasTable");
                                const rows = table.getElementsByTagName("tr");

                                searchInput.addEventListener("keyup", function () {
                                    const filter = searchInput.value.toLowerCase();

                                    // Itera pelas linhas da tabela, exceto o cabeçalho
                                    for (let i = 1; i < rows.length; i++) {
                                        const cells = rows[i].getElementsByTagName("td");
                                        let rowContainsText = false;

                                        // Itera pelas células da linha
                                        for (let j = 0; j < cells.length; j++) {
                                            // Normaliza o texto, incluindo números, para comparação
                                            const cellText = cells[j].textContent || cells[j].innerText;

                                            // Permite a pesquisa por texto e números
                                            if (cellText.toLowerCase().includes(filter)) {
                                                rowContainsText = true;
                                                break;
                                            }
                                        }

                                        // Exibe ou oculta a linha com base na pesquisa
                                        rows[i].style.display = rowContainsText ? "" : "none";
                                    }
                                });
                            });
                        </script>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                // Verifica se existe a mensagem
                                var alerta = document.getElementById('alertaMensagem');
                                if (alerta) {
                                    // Define o tempo para a mensagem desaparecer
                                    setTimeout(function() {
                                        alerta.classList.remove('show');  // Remove a classe "show"
                                        alerta.classList.add('fade');     // Adiciona a classe "fade"
                                    }, 3000);  // 3000 milissegundos = 3 segundos
                                }
                            });
                        </script>


</body>
</html>
