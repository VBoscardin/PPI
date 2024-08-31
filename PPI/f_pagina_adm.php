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
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet"> <!-- Importar a fonte Forum -->
    <style>
        /* Estilos do corpo */
        body {
            background-color: #f8f9fa; /* Cor de fundo clara */
        }
        
        /* Barra lateral */
        .sidebar {
            width: 250px;
            padding: 20px;
            background-color: green; /* Cor escura para a barra lateral */
            height: 100vh;
            position: fixed;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-y: auto; /* Adiciona rolagem vertical se necessário */
        }

        .sidebar .separator {
            width: 100%;
            height: 2px;
            background-color: white;
        }

        .sidebar .signe-text {
            font-family: 'Forum', sans-serif; /* Fonte Forum */
            font-size: 45px;
            font-weight: bold;
            text-align: center;
            margin: 0; /* Remove margens para ajuste da linha */
        }

        .sidebar .btn-container {
            width: 100%;
        }

        .sidebar button {
            width: 100%;
            margin-bottom: 10px;
            border: none;
            background-color: white; /* Fundo branco para os botões */
            color: black; /* Texto preto para os botões */
            text-align: left; /* Alinha o texto à esquerda */
            display: flex;
            align-items: center; /* Alinha ícones e texto verticalmente */
        }

        .sidebar button i {
            margin-right: 10px; /* Espaço entre o ícone e o texto */
        }

        .sidebar button:hover {
            background-color: black; /* Cor de fundo ao passar o mouse */
            color: white; /* Texto branco ao passar o mouse */
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px; /* Espaço para a sidebar */
            padding: 20px;
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
            max-width: 100px; /* Tamanho da logo */
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
            width: 80px; /* Tamanho da foto do perfil */
            height: 80px;
            border-radius: 50%; /* Faz a imagem ser circular */
            margin-right: 20px; /* Espaço entre a foto e o nome */
        }

        .profile-info .profile-details {
            font-size: 1.2rem; /* Tamanho da fonte do nome */
            font-family: 'Forum', sans-serif; /* Fonte Forum */
            font-weight: bold; /* Fonte em negrito */
        }

        /* Menu expansível de cadastro */
        #cadastrar-opcoes {
            display: none;
            position: absolute; /* Posiciona o menu acima dos outros elementos */
            margin-top: 10px; /* Espaçamento adicional abaixo do botão */
            left: 0;
            width: 100%;
            background-color: white; /* Fundo branco para os botões internos */
            padding: 10px 0;
            z-index: 1000; /* Garante que o menu esteja acima de outros elementos */
        }

        #cadastrar-opcoes button {
            width: 100%;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="separator mb-3"></div> <!-- Linha acima do SIGNE com espaçamento abaixo -->
        <div class="signe-text">SIGNE</div>
        <div class="separator mt-3 mb-3"></div> <!-- Linha abaixo do SIGNE com espaçamento acima e abaixo -->
        <div class="btn-container"> <!-- Contêiner para os botões -->
            <button class="btn btn-primary" onclick="location.href='f_pagina_adm.php'">
                <i class="fas fa-home"></i> Início
            </button>
            <button class="btn btn-primary" onclick="toggleOptions()">
            <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
            </button>
            <div id="cadastrar-opcoes">
                <button class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Cadastrar Administrador
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Cadastrar Curso
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Cadastrar Disciplina
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Cadastrar Docente
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Cadastrar Setor
                </button>
                <button class="btn btn-secondary">
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
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header-container d-flex align-items-center">
                <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                <div class="title">Página do Administrador</div>
                <div class="ms-auto d-flex align-items-center">
                    <div class="profile-info">
                        <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                            <!-- Exibir a foto do perfil se ela existir e o arquivo estiver no diretório -->
                            <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                        <?php else: ?>
                            <!-- Foto padrão se a foto do perfil não existir -->
                            <img src="imgs/admin-photo.png" alt="Foto do Administrador">
                        <?php endif; ?>
                        <div class="profile-details">
                            <span><?php echo htmlspecialchars($nome); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleOptions() {
            var options = document.getElementById('cadastrar-opcoes');
            var icon = document.getElementById('toggle-icon');
            if (options.style.display === 'none' || options.style.display === '') {
                options.style.display = 'block';
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            } else {
                options.style.display = 'none';
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        }

        // Adiciona eventos de clique aos botões do menu de cadastro
        document.addEventListener('DOMContentLoaded', function() {
            var cadastrarAdmBtn = document.querySelector('#cadastrar-opcoes button:nth-child(1)');
            cadastrarAdmBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_adm.php';
            });

            var cadastrarCursoBtn = document.querySelector('#cadastrar-opcoes button:nth-child(2)');
            cadastrarCursoBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_curso.php';
            });

            var cadastrarDisciplinaBtn = document.querySelector('#cadastrar-opcoes button:nth-child(3)');
            cadastrarDisciplinaBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_disciplina.php';
            });

            var cadastrarDocenteBtn = document.querySelector('#cadastrar-opcoes button:nth-child(4)');
            cadastrarDocenteBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_docente.php';
            });

            var cadastrarSetorBtn = document.querySelector('#cadastrar-opcoes button:nth-child(5)');
            cadastrarSetorBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_setor.php';
            });

            var cadastrarTurmaBtn = document.querySelector('#cadastrar-opcoes button:nth-child(6)');
            cadastrarTurmaBtn.addEventListener('click', function() {
                window.location.href = 'cadastrar_turma.php';
            });
        });

    </script>
</body>
</html>
