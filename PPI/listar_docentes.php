<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Excluir docente
if (isset($_POST['excluir_id'])) {
    $excluirId = $_POST['excluir_id'];
    $erro = false; // Variável para rastrear erros

    // Iniciar uma transação
    $conn->begin_transaction();

    try {
        // Recuperar o email do docente para excluir o usuário associado
        $stmt = $conn->prepare("SELECT email FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $excluirId);
        $stmt->execute();
        $stmt->bind_result($emailDocente);
        $stmt->fetch();
        $stmt->close();

        // 1. Remover as associações de disciplinas com turmas
        $stmt = $conn->prepare("DELETE FROM turmas_disciplinas 
                                 WHERE disciplina_id IN (SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = ?)");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir associações de turmas e disciplinas: " . $stmt->error);
        }
        $stmt->close();

        // 2. Remover as disciplinas associadas ao docente
        $stmt = $conn->prepare("DELETE FROM docentes_disciplinas WHERE docente_id = ?");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir disciplinas: " . $stmt->error);
        }
        $stmt->close();

        // 3. Remover turmas (se for o caso, somente se existirem turmas que não precisam de um professor)
        $stmt = $conn->prepare("DELETE FROM turmas WHERE professor_regente = ?");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir turmas: " . $stmt->error);
        }
        $stmt->close();

        // 4. Remover o docente
        $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $excluirId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao excluir docente: " . $stmt->error);
        }
        $stmt->close();

        // 5. Excluir o usuário associado na tabela usuarios
        if ($emailDocente) {
            $stmtUsuarios = $conn->prepare("DELETE FROM usuarios WHERE email = ?");
            $stmtUsuarios->bind_param("s", $emailDocente);
            if (!$stmtUsuarios->execute()) {
                throw new Exception("Erro ao excluir o usuário na tabela usuarios: " . $stmtUsuarios->error);
            }
            $stmtUsuarios->close();
        }

        // Se tudo ocorrer bem, confirmar a transação
        $conn->commit();
        header("Location: listar_docentes.php");
        exit();

    } catch (Exception $e) {
        // Em caso de erro, reverter a transação
        $conn->rollback();
        echo $e->getMessage();
    }
}

// Atualizar docente
if (isset($_POST['salvar_edicao'])) {
    $editarId = $_POST['editar_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $disciplinasSelecionadas = $_POST['disciplinas'] ?? [];
    $foto = $_FILES['foto'] ?? null;

    // Atualizar dados do docente
    $stmt = $conn->prepare("UPDATE docentes SET nome = ?, email = ?, cpf = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $cpf, $editarId);
    $stmt->execute();
    $stmt->close();

    // Atualizar disciplinas do docente
    $conn->query("DELETE FROM docentes_disciplinas WHERE docente_id = $editarId");
    foreach ($disciplinasSelecionadas as $disciplinaId) {
        $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $editarId, $disciplinaId);
        $stmt->execute();
        $stmt->close(); // Fechar a instrução
    }

    // Atualizar foto do docente
    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nomeFoto = "docente_{$editarId}." . $extensao;
        $caminhoFoto = 'uploads/' . $nomeFoto;

        if (move_uploaded_file($foto['tmp_name'], $caminhoFoto)) {
            // Atualizar a foto na tabela usuarios
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE email = (SELECT email FROM docentes WHERE id = ?)");
            $stmt->bind_param("si", $nomeFoto, $editarId);
            if (!$stmt->execute()) {
                echo "Erro ao atualizar a foto na tabela usuarios: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Erro ao fazer upload da foto.";
        }
    }

    header("Location: listar_docentes.php");
    exit();
}

// Consulta para selecionar os docentes e as disciplinas/turmas que lecionam
$sql = "
    SELECT d.id, d.nome, d.email, d.cpf,
       u.foto_perfil,
       GROUP_CONCAT(CONCAT(dis.nome, ' - Turma: ', td.turma_numero, ', Ano: ', td.turma_ano) SEPARATOR '; ') AS disciplinas
FROM docentes AS d
LEFT JOIN usuarios AS u ON d.email = u.email
LEFT JOIN docentes_disciplinas AS dd ON d.id = dd.docente_id
LEFT JOIN disciplinas AS dis ON dd.disciplina_id = dis.id
LEFT JOIN turmas_disciplinas AS td ON td.disciplina_id = dis.id
GROUP BY d.id
";
$result = $conn->query($sql);

// Obter todas as disciplinas e turmas disponíveis
$disciplinasQuery = "
    SELECT dis.id AS disciplina_id, dis.nome AS disciplina_nome, td.turma_numero, td.turma_ano
    FROM disciplinas AS dis
    LEFT JOIN turmas_disciplinas AS td ON dis.id = td.disciplina_id
";
$disciplinasResult = $conn->query($disciplinasQuery);
$disciplinasTurmas = [];
while ($row = $disciplinasResult->fetch_assoc()) {
    $disciplinasTurmas[] = $row;
}

// Se estiver editando um docente, obter suas informações
$editarDocente = null;
$disciplinasDocente = [];
if (isset($_POST['exibir_edicao'])) {
    $editarId = $_POST['editar_id'];
    $editarDocente = $conn->query("SELECT * FROM docentes WHERE id = $editarId")->fetch_assoc();

    // Consultar disciplinas que o docente leciona
    $disciplinasDoDocenteQuery = $conn->query("SELECT disciplina_id FROM docentes_disciplinas WHERE docente_id = $editarId");
    while ($row = $disciplinasDoDocenteQuery->fetch_assoc()) {
        $disciplinasDocente[] = $row['disciplina_id'];
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Docentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
                    <h1>Docentes</h1>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Foto</th>
                                <th>Email</th>
                                <th>CPF</th>
                                <th>Disciplinas e Turmas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($docente = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $docente['id']; ?></td>
                                <td><?php echo htmlspecialchars($docente['nome']); ?></td>
                                <td>
    <?php if (!empty($docente['foto_perfil']) && file_exists('uploads/' . basename($docente['foto_perfil']))): ?>
        <img src="uploads/<?php echo htmlspecialchars(basename($docente['foto_perfil'])); ?>" alt="Foto" class="img-thumbnail" width="50">
    <?php else: ?>
        <img src="imgs/docente-photo.png" alt="Foto" class="img-thumbnail" width="50">
    <?php endif; ?>
</td>

                                <td><?php echo htmlspecialchars($docente['email']); ?></td>
                                <td><?php echo htmlspecialchars($docente['cpf']); ?></td>
                                <td>
                                    <?php 
                                    if ($docente['disciplinas']) {
                                        // Explode as disciplinas em um array
                                        $disciplinasArray = explode('; ', $docente['disciplinas']);
                                        echo '<ul>';
                                        foreach ($disciplinasArray as $disciplina) {
                                            echo '<li>' . htmlspecialchars($disciplina) . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo 'Nenhuma disciplina';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="editar_id" value="<?php echo $docente['id']; ?>">
                                        <button class="btn btn-warning btn-sm" name="exibir_edicao"><i class="fas fa-edit"></i> Editar</button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este docente?');">
                                        <input type="hidden" name="excluir_id" value="<?php echo $docente['id']; ?>">
                                        <button class="btn btn-danger btn-sm">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>

                    <?php if ($editarDocente): ?>
                        <h2>Editar Docente</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="editar_id" value="<?php echo $editarDocente['id']; ?>">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome:</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($editarDocente['nome']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editarDocente['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF:</label>
                                <input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($editarDocente['cpf']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="disciplinas" class="form-label">Disciplinas:</label><br>
                                <?php foreach ($disciplinasTurmas as $dt): ?>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="disciplinas[]" value="<?php echo $dt['disciplina_id']; ?>" <?php echo in_array($dt['disciplina_id'], $disciplinasDocente) ? 'checked' : ''; ?>>
                                        <label class="form-check-label"><?php echo htmlspecialchars($dt['disciplina_nome']) . ' - Turma: ' . htmlspecialchars($dt['turma_numero']) . ', Ano: ' . htmlspecialchars($dt['turma_ano']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto:</label>
                                <input type="file" name="foto" class="form-control">
                            </div>
                            <button type="submit" name="salvar_edicao" class="btn btn-success">Salvar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButtons = document.querySelectorAll('.btn-light');
            menuButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const icon = button.querySelector('#toggle-icon');
                    icon.classList.toggle('fa-plus');
                    icon.classList.toggle('fa-minus');
                });
            });
        });
    </script>
</body>
</html>

