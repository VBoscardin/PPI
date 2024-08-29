<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'setor') {
    // Redirecionar para uma página de acesso negado ou qualquer outra página
    header("Location: f_login.php");
    exit();
}

// Conectar ao banco de dados
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$success_message = ''; // Variável para a mensagem de sucesso

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
    <title>Cadastrar Discentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa; /* Cor de fundo clara */
        }
        .sidebar {
            width: 250px;
            padding: 20px;
            background-color: #343a40; /* Cor escura para a barra lateral */
            height: 100vh;
            position: fixed;
            color: white;
        }
        .sidebar button {
            width: 100%;
            margin-bottom: 10px;
            border: none;
            color: white;
            text-align: left; /* Alinha o texto à esquerda */
            display: flex;
            align-items: center; /* Alinha ícones e texto verticalmente */
        }
        .sidebar button i {
            margin-right: 10px; /* Espaço entre o ícone e o texto */
        }
        .sidebar button:hover {
            background-color: #495057; /* Cor de fundo ao passar o mouse */
        }
        .sidebar .logo-container {
            text-align: center; /* Centraliza a imagem */
            margin-bottom: 20px; /* Espaço abaixo do logo */
        }
        .sidebar .logo-container img {
            max-width: 200px; /* Define um tamanho máximo para a imagem */
            height: auto; /* Mantém a proporção da imagem */
        }
        #content {
            margin-left: 270px;
            padding: 20px;
        }
        .form-container {
            background-color: white; /* Fundo branco para o formulário */
            padding: 20px;
            border-radius: 8px; /* Bordas arredondadas */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra leve */
        }
        .alert {
            margin-bottom: 20px; /* Espaço abaixo da mensagem */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_turmas.png" alt="Logo">
        </div>
        <button class="btn btn-primary" onclick="location.href='f_pagina_setor.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button class="btn btn-primary" onclick="location.href='cadastrar_discentes.php'">
            <i class="fas fa-user-plus"></i> Cadastrar Discentes
        </button>
        <button class="btn btn-danger" onclick="location.href='sair.php'">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </div>

    <div id="content">
        <h1>Cadastrar Discentes</h1>
        <div class="form-container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
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

                <button type="submit" class="btn btn-primary">Cadastrar Discentes</button>
            </form>
        </div>
    </div>
</body>
</html>
