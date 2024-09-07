<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'administrador') {
    // Redirecionar para uma página de acesso negado ou qualquer outra página
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter lista de docentes
$docentes_result = $conn->query('SELECT id, nome FROM docentes');
$docentes = [];
if ($docentes_result) {
    while ($row = $docentes_result->fetch_assoc()) {
        $docentes[] = $row;
    }
}

// Obter lista de disciplinas
$disciplinas_result = $conn->query('SELECT id, nome FROM disciplinas');
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Obter lista de disciplinas com o nome do curso
$disciplinas_result = $conn->query('
    SELECT disciplinas.id, disciplinas.nome AS disciplina_nome, cursos.nome AS curso_nome
    FROM disciplinas
    JOIN cursos ON disciplinas.curso_id = cursos.id
');
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}


// Função para cadastrar turma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_turma'])) {
    $numero = $_POST['numero'];
    $ano = $_POST['ano'];
    $ano_ingresso = $_POST['ano_ingresso'];
    $ano_oferta = $_POST['ano_oferta'];
    $professor_regente = $_POST['professor_regente'];
    $disciplinas_selecionadas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];

    // Verificar se os campos não estão vazios
    if (!empty($numero) && !empty($ano) && !empty($ano_ingresso) && !empty($ano_oferta) && !empty($professor_regente) && !empty($disciplinas_selecionadas)) {
        // Iniciar transação
        $conn->begin_transaction();

        try {
            // Inserir a turma na tabela turmas
            $stmt = $conn->prepare('INSERT INTO turmas (numero, ano, ano_ingresso, ano_oferta, professor_regente) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiii', $numero, $ano, $ano_ingresso, $ano_oferta, $professor_regente);
            $stmt->execute();
            $stmt->close();

            // Inserir as disciplinas selecionadas na tabela turmas_disciplinas
            $stmt = $conn->prepare('INSERT INTO turmas_disciplinas (turma_numero, turma_ano, turma_ano_ingresso, disciplina_id) VALUES (?, ?, ?, ?)');
            foreach ($disciplinas_selecionadas as $disciplina_id) {
                $stmt->bind_param('iiii', $numero, $ano, $ano_ingresso, $disciplina_id);
                $stmt->execute();
            }
            $stmt->close();

            // Confirmar transação
            $conn->commit();

            echo 'Turma cadastrada com sucesso!';
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $conn->rollback();
            echo 'Erro ao cadastrar turma: ' . $e->getMessage();
        }
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Turma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
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
                <button onclick="location.href='listar.php'">
                    <i class="fas fa-list"></i> Listar
                </button>
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
                        <div class="title ms-3">Cadastrar Setor</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador" class="profile-photo">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador" class="profile-photo">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mt-4">
                    <div class="card shadow-container">
                        <div class="card-body">
                            <form action="cadastrar_turma.php" method="post">
                                <div class="mb-3">
                                    <label for="numero" class="form-label">Número da Turma:</label>
                                    <input type="number" id="numero" name="numero" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="ano" class="form-label">Ano:</label>
                                    <input type="number" id="ano" name="ano" class="form-control" min="2000" max="2099" step="1" required>
                                </div>

                                <div class="mb-3">
                                    <label for="ano_ingresso" class="form-label">Ano de Ingresso:</label>
                                    <input type="number" id="ano_ingresso" name="ano_ingresso" class="form-control" min="2000" max="2099" step="1" required>
                                </div>

                                <div class="mb-3">
                                    <label for="ano_oferta" class="form-label">Ano de Oferta:</label>
                                    <input type="number" id="ano_oferta" name="ano_oferta" class="form-control" min="2000" max="2099" step="1" required>
                                </div>

                                <div class="mb-3">
                                    <label for="professor_regente" class="form-label">Professor Regente:</label>
                                    <select id="professor_regente" name="professor_regente" class="form-select" required>
                                        <option value="">Selecione um professor</option>
                                        <?php foreach ($docentes as $docente): ?>
                                            <option value="<?php echo htmlspecialchars($docente['id']); ?>">
                                                <?php echo htmlspecialchars($docente['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <fieldset>
                                    <legend>Disciplinas Associadas</legend>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                        <div class="form-check checkbox-group">
                                            <input type="checkbox" id="disciplina-<?php echo htmlspecialchars($disciplina['id']); ?>" name="disciplinas[]" value="<?php echo htmlspecialchars($disciplina['id']); ?>" class="form-check-input">
                                            <label for="disciplina-<?php echo htmlspecialchars($disciplina['id']); ?>" class="form-check-label">
                                                <?php echo htmlspecialchars($disciplina['disciplina_nome']); ?> (<?php echo htmlspecialchars($disciplina['curso_nome']); ?>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </fieldset>                        
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Remover a mensagem de sucesso ou erro após 5 segundos
        setTimeout(function() {
            var mensagemSucesso = document.getElementById('mensagem-sucesso');
            var mensagemErro = document.getElementById('mensagem-erro');
            if (mensagemSucesso) {
                mensagemSucesso.style.display = 'none';
            }
            if (mensagemErro) {
                mensagemErro.style.display = 'none';
            }
        }, 5000); // 5 segundos
    </script>
</body>
</html>
