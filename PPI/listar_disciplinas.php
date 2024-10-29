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
$mensagem = '';
$erro = '';

// Deletar disciplina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    header("Location: listar_disciplinas.php");
    exit;
}

// Atualizar disciplina, turma e docentes
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
        $erro = 'Já existe uma disciplina com esse nome na mesma turma. Escolha um nome diferente ou outra turma.';
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
        $conn->query("DELETE FROM docentes_disciplinas WHERE disciplina_id = $editarId"); // Remove docentes antigos
        foreach ($docentesSelecionados as $docenteId) {
            $stmt = $conn->prepare("INSERT INTO docentes_disciplinas (disciplina_id, docente_id, turma_numero) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $editarId, $docenteId, $novaTurma);
            $stmt->execute();
        }
        
         // Definir mensagem de sucesso
         $_SESSION['mensagem'] = 'Disciplina atualizada com sucesso!';

         header("Location: listar_disciplinas.php");
         exit;
    }
}

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
        disciplinas.id";

$disciplinas = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Obter lista de turmas e docentes
$turmas = $conn->query("SELECT numero, ano FROM turmas")->fetch_all(MYSQLI_ASSOC);
$docentes = $conn->query("SELECT id, nome FROM docentes")->fetch_all(MYSQLI_ASSOC);
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
                   <!-- Exibir mensagens de sucesso e erro -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success" role="alert">
                            <?php
                                echo htmlspecialchars($_SESSION['mensagem']);
                                unset($_SESSION['mensagem']); // Limpar a mensagem após exibição
                            ?>
                        </div>
                    <?php endif; ?>


                    <div class="card shadow">
                        <div class="card-body">
                            <?php if (!empty($disciplinas)): ?>
                                <table class="table table-bordered">
                                    <thead>
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
                                            <tr>
                                                <td><?php echo htmlspecialchars($disciplina['id']); ?></td>
                                                <td><?php echo htmlspecialchars($disciplina['disciplina_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($disciplina['turma_numero']); ?> - <?php echo htmlspecialchars($disciplina['turma_ano']); ?></td>
                                                <td><?php echo htmlspecialchars($disciplina['docentes_nomes']); ?></td>
                                                <td>
                                                    <!-- Botões de ação -->
                                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $disciplina['id']; ?>">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $disciplina['id']; ?>">
                                                        <i class="fas fa-trash-alt"></i> Excluir
                                                    </button>
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
                                                                <div class="mb-3">
                                                                    <label for="novo_nome" class="form-label">Nome da Disciplina</label>
                                                                    <input type="text" name="novo_nome" class="form-control" value="<?php echo htmlspecialchars($disciplina['disciplina_nome']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="nova_turma" class="form-label">Turma</label>
                                                                    <select name="nova_turma" class="form-select" required>
                                                                        <option value="">Selecione uma turma</option>
                                                                        <?php foreach ($turmas as $turma): ?>
                                                                            <option value="<?php echo htmlspecialchars($turma['numero']); ?>" <?php echo ($turma['numero'] === $disciplina['turma_numero']) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($turma['numero']); ?> - <?php echo htmlspecialchars($turma['ano']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="docentes" class="form-label">Docentes</label>
                                                                    <select name="docentes[]" class="form-select" multiple required>
                                                                        <?php foreach ($docentes as $docente): ?>
                                                                            <option value="<?php echo htmlspecialchars($docente['id']); ?>"
                                                                                <?php echo (strpos($disciplina['docentes_nomes'], htmlspecialchars($docente['nome'])) !== false) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($docente['nome']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
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
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-danger">Excluir</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>Nenhuma disciplina encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
