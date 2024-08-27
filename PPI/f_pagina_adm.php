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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css_inicio.css"> <!-- Incluindo o arquivo CSS -->
    <title>Página do Administrador</title>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_turmas.png" alt="Logo">
        </div>
        <button onclick="location.href='inicio.php'">Início</button>
        <button onclick="location.href='cadastrar.php'">Cadastrar</button>
        <button onclick="location.href='gerar_boletim.php'">Gerar Boletim</button>
        <button onclick="location.href='gerar_slide.php'">Gerar Slide Pré Conselho</button>
        <button onclick="location.href='listar.php'">Listar</button>
        <button onclick="location.href='meu_perfil.php'">Meu Perfil</button>
        <button onclick="location.href='sair.php'">Sair</button>
    </div>

    <div id="content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>
</body>
</html>