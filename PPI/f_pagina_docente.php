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
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            min-height: 100vh; /* Definir altura mínima da janela de visualização */
        }
        #sidebar {
            width: 200px;
            background-color: #f0f0f0;
            padding: 20px;
        }
        #content {
            flex-grow: 1;
            padding: 20px;
            margin-left: auto;
            text-align: right;
            margin-right: 200px; /* Ajuste conforme necessário */
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        li {
            margin-bottom: 10px;
        }
        a {
            text-decoration: none;
            color: #333;
        }
        a:hover {
            color: #555;
        }
        #sidebar {
            height: 100%; /* Estender até o final da janela de visualização */
            position: fixed; /* Fixar a barra lateral */
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <ul>
            <li><a href="#">Início</a></li>
            <li><a href="#">Turmas</a></li>
            <li><a href="#">Disciplinas</a></li>
            <li><a href="#">Cadastrar Notas</a></li>
            <li><a href="#">Meu Perfil</a></li>
            <li><a href="logout.php">Sair</a></li>
        </ul>
    </div>
    <div id="content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>
</body>
</html>
