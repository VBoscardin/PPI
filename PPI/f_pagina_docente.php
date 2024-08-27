<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um docente
if ($_SESSION['user_type'] !== 'docente') {
    // Redirecionar para uma página de acesso negado ou a página principal
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

// Buscar informações do usuário, se necessário
$stmt = $conn->prepare("SELECT nome FROM docentes WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página do Docente</title>
    <link rel="stylesheet" href="css_inicio.css"> <!-- Incluindo o arquivo CSS -->
</head>
<body>
    <div class="sidebar">
        <img src="imgs/logo.png" alt="Logo">
        <button onclick="location.href='inicio.php'">Início</button>
        <button onclick="location.href='turmas.php'">Turmas</button>
        <button onclick="location.href='disciplinas.php'">Disciplinas</button>
        <button onclick="location.href='cadastrar_notas.php'">Cadastrar Notas</button>
        <button onclick="location.href='meu_perfil.php'">Meu Perfil</button>
        <button onclick="location.href='logout.php'">Sair</button>

    </div>
    <div id="content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>
</body>
</html>
