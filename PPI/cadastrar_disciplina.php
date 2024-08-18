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

// Função para cadastrar disciplina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_disciplina'])) {
    $curso_id = $_POST['curso_id'];
    $nome = $_POST['nome'];
    $docente = $_POST['docente'];
    $turma = $_POST['turma'];

    // Verificar se os campos não estão vazios
    if (!empty($curso_id) && !empty($nome) && !empty($docente) && !empty($turma)) {
        // Preparar a consulta para evitar SQL Injection
        $stmt = $mysqli->prepare('INSERT INTO disciplinas (curso_id, nome, docente, turma) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $curso_id, $nome, $docente, $turma);

        if ($stmt->execute()) {
            echo 'Disciplina cadastrada com sucesso!';
        } else {
            echo 'Erro ao cadastrar disciplina: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'Todos os campos são obrigatórios!';
    }
}

// Obter lista de cursos para o menu suspenso
$cursos_result = $mysqli->query('SELECT id, nome FROM cursos');
$cursos = [];
if ($cursos_result) {
    while ($row = $cursos_result->fetch_assoc()) {
        $cursos[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Disciplina</title>
</head>
<body>
    <h1>Cadastrar Disciplina</h1>

    <form action="cadastrar_disciplina.php" method="post">
        <label for="curso_id">Curso:</label>
        <select id="curso_id" name="curso_id" required>
            <option value="">Selecione um curso</option>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?php echo htmlspecialchars($curso['id']); ?>">
                    <?php echo htmlspecialchars($curso['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="nome">Nome da Disciplina:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="docente">Docente:</label>
        <input type="text" id="docente" name="docente" required>
        
        <label for="turma">Turma:</label>
        <input type="text" id="turma" name="turma" required>
        
        <input type="submit" name="cadastrar_disciplina" value="Cadastrar Disciplina">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
