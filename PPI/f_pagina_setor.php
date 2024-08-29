<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    // Redirecionar para a página de login se o usuário não estiver autenticado
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário é um setor
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

// Consultar o nome do setor
$stmt = $conn->prepare("SELECT username FROM usuarios WHERE email = ?");
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
    <title>Página do Setor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">
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
        .logo-container {
            display: flex;
            align-items: center; /* Alinha a logo e a sigla verticalmente */
            margin-bottom: 20px; /* Espaço abaixo do logo */
        }
        .logo-container img {
            max-width: 75px; /* Define um tamanho máximo menor para a imagem */
            height: auto; /* Mantém a proporção da imagem */
            margin-right: 10px; /* Espaço entre a imagem e a sigla */
        }
        .logo-container .sigla {
            font-size: 35px; /* Tamanho da fonte da sigla */
            font-weight: bold; /* Deixa a sigla em negrito */
            color: white; /* Cor da sigla para combinar com o texto da sidebar */
            font-family: 'Forum', sans-serif; /* Aplica a fonte Forum */
        }
        #content {
            margin-left: 270px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_if.png" alt="Logo">
            <span class="sigla">SIGNE</span>
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
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial do setor.</p>
    </div>
</body>
</html>
