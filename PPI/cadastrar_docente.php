<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um administrador
if ($_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

// Conectar ao banco de dados
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

$mysqli = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

// Código da página para administradores aqui
$stmt = $mysqli->prepare("SELECT username FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome);
$stmt->fetch();
$stmt->close();

// Função para cadastrar docente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_docente'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];
    $disciplinas = $_POST['disciplinas']; // Array de IDs de disciplinas

    // Verificar se os campos não estão vazios
    if (!empty($nome) && !empty($email) && !empty($cpf) && !empty($senha)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Caminho para upload da foto de perfil
        $upload_dir = 'uploads/';
        $foto_perfil_path = '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $foto_perfil_name = basename($_FILES['photo']['name']);
            $foto_perfil_path = $upload_dir . $foto_perfil_name;
            
            if ($_FILES['photo']['size'] > 5000000) {
                echo 'Erro: O arquivo é muito grande!';
            } else {
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $foto_perfil_path)) {
                    echo 'Erro ao fazer upload da foto!';
                    $foto_perfil_path = '';
                }
            }
        }

        // Verificar se o email já está registrado na tabela docentes
        $stmt_docentes_check = $mysqli->prepare('SELECT id FROM docentes WHERE email = ?');
        $stmt_docentes_check->bind_param('s', $email);
        $stmt_docentes_check->execute();
        $stmt_docentes_check->store_result();

        if ($stmt_docentes_check->num_rows > 0) {
            echo 'O email já está registrado como docente!';
        } else {
            // Verificar se o email já está registrado na tabela usuarios
            $stmt_usuarios_check = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt_usuarios_check->bind_param('s', $email);
            $stmt_usuarios_check->execute();
            $stmt_usuarios_check->store_result();

            if ($stmt_usuarios_check->num_rows > 0) {
                echo 'O email já está registrado como usuário!';
            } else {
                // Inserir o docente na tabela docentes
                $stmt_docente = $mysqli->prepare('INSERT INTO docentes (nome, email, cpf, senha) VALUES (?, ?, ?, ?)');
                $stmt_docente->bind_param('ssss', $nome, $email, $cpf, $senha_hash);

                if ($stmt_docente->execute()) {
                    $docente_id = $stmt_docente->insert_id;

                    // Inserir o usuário na tabela usuarios com a foto de perfil
                    $username = $nome; // Alterado para usar o nome
                    $tipo = 'docente';

                    $stmt_usuario = $mysqli->prepare('INSERT INTO usuarios (username, email, password_hash, tipo, foto_perfil) VALUES (?, ?, ?, ?, ?)');
                    $stmt_usuario->bind_param('sssss', $username, $email, $senha_hash, $tipo, $foto_perfil_path);

                    if ($stmt_usuario->execute()) {
                        echo 'Docente cadastrado com sucesso!';
                    } else {
                        echo 'Erro ao cadastrar usuário: ' . $stmt_usuario->error;
                    }

                    $stmt_usuario->close();
                } else {
                    echo 'Erro ao cadastrar docente: ' . $stmt_docente->error;
                }

                // Associar disciplinas ao docente
                if (!empty($disciplinas)) {
                    foreach ($disciplinas as $disciplina_id) {
                        $stmt_disciplina = $mysqli->prepare('INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)');
                        $stmt_disciplina->bind_param('ii', $docente_id, $disciplina_id);
                        $stmt_disciplina->execute();
                        $stmt_disciplina->close();
                    }
                }

                $stmt_docente->close();
            }

            $stmt_usuarios_check->close();
        }

        $stmt_docentes_check->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Obter lista de disciplinas para checkboxes
$disciplinas_result = $mysqli->query('SELECT id, nome FROM disciplinas');
$disciplinas = [];
if ($disciplinas_result) {
    while ($row = $disciplinas_result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Docente</title>
    <style>
        .checkbox-group {
            margin-bottom: 10px;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
        fieldset {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Cadastrar Docente</h1>

    <form action="cadastrar_docente.php" method="post" enctype="multipart/form-data">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" required>
        
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>
        
        <label for="photo">Foto de Perfil:</label>
        <input type="file" id="photo" name="photo" accept="image/*"><br>

        <fieldset>
            <legend>Disciplinas:</legend>
            <?php foreach ($disciplinas as $disciplina): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="disciplina_<?php echo htmlspecialchars($disciplina['id']); ?>" name="disciplinas[]" value="<?php echo htmlspecialchars($disciplina['id']); ?>">
                    <label for="disciplina_<?php echo htmlspecialchars($disciplina['id']); ?>">
                        <?php echo htmlspecialchars($disciplina['nome']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        
        <input type="submit" name="cadastrar_docente" value="Cadastrar Docente">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
