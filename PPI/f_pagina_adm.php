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
    <title>Página do Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        .sidebar .logo-container img {
            max-width: 100%;
            height: auto;
        }
        #content {
            margin-left: 270px;
            padding: 20px;
        }
        .form-container {
            background-color: white; /* Fundo branco para o formulário */
            padding: 20px;
            border-radius: 8px; /* Bordas arredondadas */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra leve */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="imgs/logo_if.png" alt="Logo">
        </div>
        <button class="btn btn-primary" onclick="location.href='f_pagina_adm.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button class="btn btn-primary" onclick="toggleOptions()">
            <i class="fas fa-plus"></i> Cadastrar
        </button>
        <div id="cadastrar-opcoes" style="display: none;">
            <button class="btn btn-secondary" onclick="location.href='cadastrar_adm.php'">
                <i class="fas fa-plus"></i> Cadastrar Administrador
            </button>
            <button class="btn btn-secondary" onclick="location.href='cadastrar_curso.php'">
                <i class="fas fa-plus"></i> Cadastrar Curso
            </button>
            <button class="btn btn-secondary" onclick="location.href='cadastrar_disciplina.php'">
                <i class="fas fa-plus"></i> Cadastrar Disciplina
            </button>
            <button class="btn btn-secondary" onclick="location.href='cadastrar_docente.php'">
                <i class="fas fa-plus"></i> Cadastrar Docente
            </button>
            <button class="btn btn-secondary" onclick="location.href='cadastrar_setor.php'">
                <i class="fas fa-plus"></i> Cadastrar Setor
            </button>
            <button class="btn btn-secondary" onclick="location.href='cadastrar_turma.php'">
                <i class="fas fa-plus"></i> Cadastrar Turma
            </button>
        </div>
        <button class="btn btn-primary" onclick="location.href='gerar_boletim.php'">
            <i class="fas fa-file-alt"></i> Gerar Boletim
        </button>
        <button class="btn btn-primary" onclick="location.href='gerar_slide.php'">
            <i class="fas fa-sliders-h"></i> Gerar Slide Pré Conselho
        </button>
        <button class="btn btn-primary" onclick="location.href='listar.php'">
            <i class="fas fa-list"></i> Listar
        </button>
        <button class="btn btn-primary" onclick="location.href='meu_perfil.php'">
            <i class="fas fa-user"></i> Meu Perfil
        </button>
        <button class="btn btn-danger" onclick="location.href='sair.php'">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </div>

    <div id="content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
        <p>Esta é a página inicial.</p>
    </div>

    <script>
        function toggleOptions() {
            var options = document.getElementById('cadastrar-opcoes');
            if (options.style.display === 'none' || options.style.display === '') {
                options.style.display = 'block';
            } else {
                options.style.display = 'none';
            }
        }
    </script>
</body>
</html>
