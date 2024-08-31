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

// Conectar ao banco de dados, se necessário
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "bd_ppi";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Código da página para administradores aqui
$stmt = $conn->prepare("SELECT username FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome);
$stmt->fetch();
$stmt->close();
?>
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

    // Verificar se os campos não estão vazios
    if (!empty($curso_id) && !empty($nome)) {
        // Preparar a consulta para evitar SQL Injection
        $stmt = $mysqli->prepare('INSERT INTO disciplinas (curso_id, nome) VALUES (?, ?)');
        $stmt->bind_param('is', $curso_id, $nome);

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
        
        <input type="submit" name="cadastrar_disciplina" value="Cadastrar Disciplina">
    </form>
    <p>
        <a href="f_pagina_adm.php">Voltar para Início</a>
    </p>
</body>
</html>
