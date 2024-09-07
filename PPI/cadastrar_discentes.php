<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um setor
if ($_SESSION['user_type'] !== 'setor') {
    // Redirecionar para uma página de acesso negado ou qualquer outra página
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$success_message = ''; // Variável para a mensagem de sucesso
$nome = ''; // Inicializar a variável $nome

// Consultar o nome do setor e a foto de perfil
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Função para cadastrar discente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $numero_matricula = $_POST['numero_matricula'];
    $nome = $_POST['nome'];
    $cidade = $_POST['cidade'];
    $email = $_POST['email'];
    $genero = $_POST['genero'];
    $data_nascimento = $_POST['data_nascimento'];
    $observacoes = $_POST['observacoes'];

    // Verificar se o campo foto está preenchido
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $foto_temp = $_FILES['foto']['tmp_name'];
        $foto_path = 'uploads/' . basename($foto);

        // Mover o arquivo para o diretório de uploads
        if (!move_uploaded_file($foto_temp, $foto_path)) {
            die("Erro ao fazer upload da foto.");
        }
    } else {
        $foto_path = null; // Se não houver foto, define como nulo
    }

    // Verificar se os campos não estão vazios
    if (!empty($numero_matricula) && !empty($nome) && !empty($cidade) && !empty($email) && !empty($genero) && !empty($data_nascimento)) {
        // Iniciar uma transação
        $conn->begin_transaction();
        
        try {
            // Inserir na tabela 'discentes'
            $stmt = $conn->prepare('INSERT INTO discentes (numero_matricula, nome, cidade, email, genero, data_nascimento, observacoes, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssssss', $numero_matricula, $nome, $cidade, $email, $genero, $data_nascimento, $observacoes, $foto_path);

            if (!$stmt->execute()) {
                throw new Exception('Erro ao cadastrar discente: ' . $stmt->error);
            }

            // Confirmar a transação
            $conn->commit();
            $success_message = 'Discente cadastrado com sucesso!';
        } catch (Exception $e) {
            // Reverter a transação em caso de erro
            $conn->rollback();
            $success_message = 'Erro: ' . $e->getMessage();
        }

        // Fechar a declaração
        $stmt->close();
    } else {
        $success_message = 'Por favor, preencha todos os campos obrigatórios.';
    }
}

// Fechar a conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Discente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
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
                <button onclick="location.href='f_pagina_setor.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
                <!-- Menu expansível com Bootstrap -->
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_discentes.php'">
                            <i class="fas fa-plus"></i> Cadastrar Discente
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

            <!-- Conteúdo principal -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Cadastrar Discente</div>
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
                    <div class="card shadow-container">
                        <div class="card-body">
                            <form action="cadastrar_discentes.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="numero_matricula" class="form-label">Número da Matrícula:</label>
                                    <input type="text" id="numero_matricula" name="numero_matricula" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome:</label>
                                    <input type="text" id="nome" name="nome" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="cidade" class="form-label">Cidade:</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail:</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="genero" class="form-label">Gênero:</label>
                                    <select id="genero" name="genero" class="form-select" required>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Feminino">Feminino</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                                    <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="observacoes" class="form-label">Observações:</label>
                                    <textarea id="observacoes" name="observacoes" class="form-control"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="foto" class="form-label">Foto:</label>
                                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                </div>

                                <button type="submit" class="btn btn-light">Cadastrar Discentes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
