<?php
$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

// Conectar ao banco de dados
$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexão
if ($mysqli->connect_error) {
    die('Conexão falhou: ' . $mysqli->connect_error);
}

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

                    // Inserir o usuário na tabela usuarios
                    $username = $email; // Ou qualquer outro nome de usuário que você deseje usar
                    $tipo = 'docente';

                    $stmt_usuario = $mysqli->prepare('INSERT INTO usuarios (username, email, password_hash, tipo) VALUES (?, ?, ?, ?)');
                    $stmt_usuario->bind_param('ssss', $username, $email, $senha_hash, $tipo);

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

    <form action="cadastrar_docente.php" method="post">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" required>
        
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>
        
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
