<?php
session_start();

// Verificar se o usuário está autenticado e é um administrador
if (!isset($_SESSION['email']) || $_SESSION['user_type'] !== 'administrador') {
    // Redirecionar para a página de login se o usuário não estiver autenticado
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

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página do Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa; /* Cor de fundo clara */
            margin: 0; /* Remove a margem padrão do body */
            padding: 0; /* Remove o padding padrão do body */
            overflow-x: hidden; /* Evita rolagem horizontal */
        }

        /* Barra lateral */
        .sidebar {
            width: 250px;
            padding: 20px;
            background-color: #003d00; /* Verde escuro */
            height: 100vh; /* Altura fixa para a barra lateral */
            position: fixed;
            color: white;
            overflow-y: auto; /* Adiciona rolagem vertical se necessário */
            top: 0;
            left: 0;
            z-index: 1000; /* Garante que a barra lateral esteja acima de outros elementos */
        }

        .sidebar .separator {
            border-bottom: 1px solid white; /* Linha separadora */
            margin-bottom: 10px; /* Espaço abaixo da linha */
        }

        .sidebar .signe-text {
            font-family: 'Forum', sans-serif; /* Fonte Forum */
            font-size: 3rem; /* Aumenta o tamanho do SIGNE */
            color: white; /* Cor do texto SIGNE */
            text-align: center;
            margin-bottom: 10px; /* Espaço abaixo do SIGNE */
        }

        /* Estilo dos botões na barra lateral */
        .sidebar button {
            width: 100%;
            margin-bottom: 10px;
            border: none;
            background-color: white; /* Cor de fundo dos botões */
            color: black; /* Cor do texto dos botões */
            text-align: left; /* Alinha o texto à esquerda */
            display: flex;
            align-items: center; /* Alinha ícones e texto verticalmente */
            padding: 10px; /* Espaço interno do botão */
            border-radius: 4px; /* Bordas arredondadas para os botões */
        }

        .sidebar button i {
            margin-right: 10px; /* Espaço entre o ícone e o texto */
        }

        /* Estilo dos botões ao passar o mouse */
        .sidebar button:hover {
            background-color: black; /* Cor de fundo ao passar o mouse */
            color: white; /* Cor do texto ao passar o mouse */
        }

        /* Estilo dos botões no menu expansível de cadastro */
        .expandable-menu button {
            width: 100%;
            margin-bottom: 5px;
            border: none;
            background-color: white; /* Cor de fundo dos botões */
            color: black; /* Cor do texto dos botões */
            text-align: left; /* Alinha o texto à esquerda */
            display: flex;
            align-items: center; /* Alinha ícones e texto verticalmente */
            padding: 6px; /* Espaço interno do botão */
            border-radius: 4px; /* Bordas arredondadas para os botões */
        }

        /* Estilo dos botões no menu expansível de cadastro ao passar o mouse */
        .expandable-menu button:hover {
            background-color: black; /* Cor de fundo ao passar o mouse */
            color: white; /* Cor do texto ao passar o mouse */
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px; /* Espaço para a sidebar */
            padding: 20px;
            height: 100vh; /* Altura fixa para o conteúdo principal */
            overflow-y: auto; /* Adiciona rolagem vertical ao conteúdo principal */
        }

        /* Container do cabeçalho */
        .header-container {
            background-color: white;
            border-radius: 8px; /* Bordas arredondadas */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra leve */
            padding: 20px;
            margin-bottom: 20px; /* Espaço abaixo do container */
            display: flex;
            align-items: center;
        }

        .header-container .logo {
            max-width: 120px; /* Tamanho da logo na área de conteúdo */
            height: auto;
        }

        .header-container .title {
            font-size: 2rem; /* Tamanho do título */
            font-weight: bold;
            margin-bottom: 0; /* Remove a margem inferior do título */
            margin-left: 20px; /* Espaço entre logo e título */
        }

        /* Informações do perfil */
        .profile-info {
            display: flex;
            align-items: center;
            margin-left: 20px; /* Distância adicional entre a foto e o nome */
        }

        .profile-info img {
            width: 60px; /* Tamanho da foto do perfil */
            height: 60px; /* Tamanho da foto do perfil */
            object-fit: cover; /* Faz a foto ter formato quadrado */
            border-radius: 4px; /* Bordas arredondadas para a foto */
            margin-right: 10px; /* Espaço entre a foto e o nome */
        }

        .profile-details {
            font-size: 1.2rem; /* Tamanho da fonte do nome */
            font-family: 'Forum', sans-serif; /* Fonte Forum */
            font-weight: bold; /* Fonte em negrito */
        }

        /* Menu expansível */
        .expandable-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            margin-top: 20px; /* Espaço acima do menu expansível */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="separator mb-3"></div>
        <div class="signe-text">SIGNE</div>
        <div class="separator mt-3 mb-3"></div>
        <button onclick="location.href='f_pagina_adm.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
            <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
        </button>
        <!-- Menu expansível com Bootstrap -->
        <div id="expandable-menu" class="collapse expandable-container">
            <div class="expandable-menu">
                <button onclick="location.href='cadastrar_adm.php'">
                    <i class="fas fa-plus"></i> Cadastrar Administrador
                </button>
                <button onclick="location.href='cadastrar_curso.php'">
                    <i class="fas fa-plus"></i> Cadastrar Curso
                </button>
                <button onclick="location.href='cadastrar_disciplina.php'">
                    <i class="fas fa-plus"></i> Cadastrar Disciplina
                </button>
                <button onclick="location.href='cadastrar_docente.php'">
                    <i class="fas fa-plus"></i> Cadastrar Docente
                </button>
                <button onclick="location.href='cadastrar_setor.php'">
                    <i class="fas fa-plus"></i> Cadastrar Setor
                </button>
                <button onclick="location.href='cadastrar_turma.php'">
                    <i class="fas fa-plus"></i> Cadastrar Turma
                </button>
            </div>
        </div>
        <button onclick="location.href='gerar_boletim.php'">
            <i class="fas fa-file-alt"></i> Gerar Boletim
        </button>
        <button onclick="location.href='gerar_slide.php'">
            <i class="fas fa-sliders-h"></i> Gerar Slide Pré Conselho
        </button>
        <button onclick="location.href='listar.php'">
            <i class="fas fa-list"></i> Listar
        </button>
        <button onclick="location.href='meu_perfil.php'">
            <i class="fas fa-user"></i> Meu Perfil
        </button>
        <button class="btn btn-danger" onclick="location.href='sair.php'">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header-container">
                <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                <div class="title ms-3">Página do Administrador</div>
                <div class="ms-auto d-flex align-items-center">
                    <div class="profile-info d-flex align-items-center">
                        <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                            <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                        <?php else: ?>
                            <img src="imgs/admin-photo.png" alt="Foto do Administrador">
                        <?php endif; ?>
                        <div class="profile-details ms-2">
                            <span><?php echo htmlspecialchars($nome); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
