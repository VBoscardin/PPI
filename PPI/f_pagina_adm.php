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
<style>
#content {
            flex-grow: 1;
            padding: 20px;
            margin-left: auto;
            text-align: right;
            margin-right: 200px; /* Ajuste conforme necessário */
        }
</style>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="conteudo.css">
    <title>Página do Administrador</title>
</head>
<body>
    <h1>Página do Administrador</h1>

    <p>
        <a href="inicio.php">Início</a>
    </p>
    <p>
        <a href="cadastrar.php">Cadastrar</a>
    </p>
    <p>
        <a href="gerar_boletim.php">Gerar Boletim</a>
    </p>
    <p>
        <a href="gerar_slide.php">Gerar Slide Pré Conselho</a>
    </p>
    <p>
        <a href="listar.php">Listar</a>
    </p>
    <p>
        <a href="meu_perfil.php">Meu Perfil</a>
    </p>
    <p>
        <a href="sair.php">Sair</a>
    </p>
    <div id="content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>
</body>
</html>
