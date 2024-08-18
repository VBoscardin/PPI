<?php
$host = 'localhost';
$db = 'bd_ppi';
$user = 'root'; // Seu usuário do banco de dados
$pass = ''; // Sua senha do banco de dados

// Conectar ao banco de dados
$mysqli = new mysqli($host, $user, $pass, $db);

// Definir tipo de usuário como 'docente'
 $tipo = 'docente';

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

        // Preparar a consulta para evitar SQL Injection
        $stmt = $mysqli->prepare('INSERT INTO docentes (nome, email, cpf, senha) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $nome, $email, $cpf, $senha_hash);

        if ($stmt->execute()) {
            $docente_id = $stmt->insert_id;

            // Associar disciplinas ao docente
            foreach ($disciplinas as $disciplina_id) {
                $stmt_disciplina = $mysqli->prepare('INSERT INTO docentes_disciplinas (docente_id, disciplina_id) VALUES (?, ?)');
                $stmt_disciplina->bind_param('ii', $docente_id, $disciplina_id);
                $stmt_disciplina->execute();
                $stmt_disciplina->close();
            }

            echo 'Docente cadastrado com sucesso!';
        } else {
            echo 'Erro ao cadastrar docente: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Obter lista de disciplinas para o menu suspenso
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
        
        <label for="disciplinas">Disciplinas:</label>
        <select id="disciplinas" name="disciplinas[]" multiple required>
            <?php foreach ($disciplinas as $disciplina): ?>
                <option value="<?php echo htmlspecialchars($disciplina['id']); ?>">
                    <?php echo htmlspecialchars($disciplina['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="submit" name="cadastrar_docente" value="Cadastrar Docente">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
